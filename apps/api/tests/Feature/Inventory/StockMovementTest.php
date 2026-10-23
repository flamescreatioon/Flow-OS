<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryLocation;
use App\Models\Organization;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\WithRbac;
use Tests\TestCase;

class StockMovementTest extends TestCase
{
    use WithRbac;

    /**
     * @return array{0: Organization, 1: User, 2: Product, 3: InventoryLocation}
     */
    protected function stockingContext(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationAdmin($organization, $user);
        $unit = UnitOfMeasure::factory()->for($organization)->withPrecision(3)->create();
        $product = Product::factory()->for($organization)->withUnit($unit)->stocked()->create();
        $location = InventoryLocation::factory()->for($organization)->create();
        Sanctum::actingAs($user);

        return [$organization, $user, $product, $location];
    }

    public function test_a_receipt_records_a_movement_and_increases_the_balance(): void
    {
        [$organization, , $product, $location] = $this->stockingContext();

        $this->postJson("/api/v1/organizations/{$organization->id}/stock-movements", [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'type' => 'receipt',
            'quantity' => '12.500',
        ])->assertCreated()
            ->assertJsonPath('data.type', 'receipt')
            ->assertJsonPath('data.quantity', '12.500');

        $this->assertDatabaseHas('stock_movements', [
            'organization_id' => $organization->id,
            'product_id' => $product->id,
            'location_id' => $location->id,
            'type' => 'receipt',
            'quantity' => 12500,
        ]);

        $this->assertDatabaseHas('stock_balances', [
            'organization_id' => $organization->id,
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => 12500,
        ]);
    }

    public function test_a_sale_decreases_the_balance(): void
    {
        [$organization, , $product, $location] = $this->stockingContext();

        $this->postJson("/api/v1/organizations/{$organization->id}/stock-movements", [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'type' => 'receipt',
            'quantity' => '10.000',
        ])->assertCreated();

        $this->postJson("/api/v1/organizations/{$organization->id}/stock-movements", [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'type' => 'sale',
            'quantity' => '4.250',
        ])->assertCreated()
            ->assertJsonPath('data.quantity', '-4.250');

        $this->assertDatabaseHas('stock_balances', [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => 5750,
        ]);
    }

    public function test_a_movement_against_a_product_from_another_organization_is_rejected(): void
    {
        [$organization, , , $location] = $this->stockingContext();
        $other = Organization::factory()->create();
        $foreignProduct = Product::factory()->for($other)->stocked()->create();

        $this->postJson("/api/v1/organizations/{$organization->id}/stock-movements", [
            'product_id' => $foreignProduct->id,
            'location_id' => $location->id,
            'type' => 'receipt',
            'quantity' => '1.000',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('stock_balances', 0);
    }

    public function test_a_movement_against_a_location_from_another_organization_is_rejected(): void
    {
        [$organization, , $product] = $this->stockingContext();
        $other = Organization::factory()->create();
        $foreignLocation = InventoryLocation::factory()->for($other)->create();

        $this->postJson("/api/v1/organizations/{$organization->id}/stock-movements", [
            'product_id' => $product->id,
            'location_id' => $foreignLocation->id,
            'type' => 'receipt',
            'quantity' => '1.000',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_a_product_that_does_not_track_stock_cannot_have_movements(): void
    {
        [$organization, , , $location] = $this->stockingContext();
        $service = Product::factory()->for($organization)->nonStocked()->create();

        $this->postJson("/api/v1/organizations/{$organization->id}/stock-movements", [
            'product_id' => $service->id,
            'location_id' => $location->id,
            'type' => 'receipt',
            'quantity' => '1.000',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_an_archived_product_cannot_have_movements(): void
    {
        [$organization, , $product, $location] = $this->stockingContext();
        $product->forceFill(['archived_at' => now()])->save();

        $this->postJson("/api/v1/organizations/{$organization->id}/stock-movements", [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'type' => 'receipt',
            'quantity' => '1.000',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_a_correction_creates_a_new_movement_and_reverses_the_balance(): void
    {
        [$organization, , $product, $location] = $this->stockingContext();

        $movement = $this->postJson("/api/v1/organizations/{$organization->id}/stock-movements", [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'type' => 'receipt',
            'quantity' => '10.000',
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/organizations/{$organization->id}/stock-movements", [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'type' => 'adjustment',
            'quantity' => '-10.000',
            'corrects_movement_id' => $movement,
        ])->assertCreated()
            ->assertJsonPath('data.corrects_movement_id', $movement)
            ->assertJsonPath('data.quantity', '-10.000');

        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertDatabaseHas('stock_balances', [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => 0,
        ]);
    }

    public function test_a_movement_cannot_be_corrected_twice(): void
    {
        [$organization, , $product, $location] = $this->stockingContext();

        $movement = $this->postJson("/api/v1/organizations/{$organization->id}/stock-movements", [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'type' => 'receipt',
            'quantity' => '10.000',
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/organizations/{$organization->id}/stock-movements", [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'type' => 'adjustment',
            'quantity' => '-10.000',
            'corrects_movement_id' => $movement,
        ])->assertCreated();

        $this->postJson("/api/v1/organizations/{$organization->id}/stock-movements", [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'type' => 'adjustment',
            'quantity' => '-10.000',
            'corrects_movement_id' => $movement,
        ])->assertUnprocessable();

        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_a_correction_must_move_stock_in_the_opposite_direction(): void
    {
        [$organization, , $product, $location] = $this->stockingContext();

        $movement = $this->postJson("/api/v1/organizations/{$organization->id}/stock-movements", [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'type' => 'receipt',
            'quantity' => '10.000',
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/organizations/{$organization->id}/stock-movements", [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'type' => 'adjustment',
            'quantity' => '2.000',
            'corrects_movement_id' => $movement,
        ])->assertUnprocessable();

        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_a_count_records_the_server_computed_variance(): void
    {
        [$organization, , $product, $location] = $this->stockingContext();

        $this->postJson("/api/v1/organizations/{$organization->id}/stock-movements", [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'type' => 'receipt',
            'quantity' => '10.000',
        ])->assertCreated();

        $this->postJson("/api/v1/organizations/{$organization->id}/stock-counts", [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'counted_quantity' => '7.500',
        ])->assertCreated()
            ->assertJsonPath('data.type', 'count')
            ->assertJsonPath('data.quantity', '-2.500');

        $this->assertDatabaseHas('stock_balances', [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => 7500,
        ]);
    }

    public function test_a_count_does_not_accept_a_client_stated_balance(): void
    {
        [$organization, , $product, $location] = $this->stockingContext();

        $this->postJson("/api/v1/organizations/{$organization->id}/stock-counts", [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'counted_quantity' => '5.000',
            'quantity' => '999.000',
        ])->assertCreated();

        $this->assertDatabaseHas('stock_balances', [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => 5000,
        ]);
    }

    public function test_a_count_may_not_use_more_precision_than_the_unit_allows(): void
    {
        [$organization, , $product, $location] = $this->stockingContext();

        $this->postJson("/api/v1/organizations/{$organization->id}/stock-counts", [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'counted_quantity' => '5.12345',
        ])->assertUnprocessable();
    }

    public function test_a_transfer_creates_a_linked_pair_of_movements(): void
    {
        [$organization, , $product, $location] = $this->stockingContext();
        $destination = InventoryLocation::factory()->for($organization)->create();

        $this->postJson("/api/v1/organizations/{$organization->id}/stock-movements", [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'type' => 'receipt',
            'quantity' => '10.000',
        ])->assertCreated();

        $response = $this->postJson("/api/v1/organizations/{$organization->id}/stock-transfers", [
            'product_id' => $product->id,
            'from_location_id' => $location->id,
            'to_location_id' => $destination->id,
            'quantity' => '4.000',
        ])->assertCreated();

        $outbound = $response->json('data.outbound');
        $inbound = $response->json('data.inbound');

        $this->assertSame('transfer_out', $outbound['type']);
        $this->assertSame('-4.000', $outbound['quantity']);
        $this->assertSame('transfer_in', $inbound['type']);
        $this->assertSame('4.000', $inbound['quantity']);
        $this->assertSame($inbound['id'], $outbound['transfer_movement_id']);
        $this->assertSame($outbound['id'], $inbound['transfer_movement_id']);

        $this->assertDatabaseHas('stock_balances', [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => 6000,
        ]);
        $this->assertDatabaseHas('stock_balances', [
            'product_id' => $product->id,
            'location_id' => $destination->id,
            'quantity' => 4000,
        ]);
    }

    public function test_a_member_may_read_stock_but_not_move_it(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationMember($organization, $user);
        $unit = UnitOfMeasure::factory()->for($organization)->create();
        $product = Product::factory()->for($organization)->withUnit($unit)->create();
        $location = InventoryLocation::factory()->for($organization)->create();
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/organizations/{$organization->id}/stock-balances")->assertOk();

        $this->postJson("/api/v1/organizations/{$organization->id}/stock-movements", [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'type' => 'receipt',
            'quantity' => '1.000',
        ])->assertForbidden();

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_the_same_idempotency_key_records_the_movement_once(): void
    {
        [$organization, , $product, $location] = $this->stockingContext();

        $payload = [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'type' => 'receipt',
            'quantity' => '3.000',
        ];

        $first = $this->postJson("/api/v1/organizations/{$organization->id}/stock-movements", $payload, [
            'Idempotency-Key' => 'stock-key-1',
        ])->assertCreated();

        $second = $this->postJson("/api/v1/organizations/{$organization->id}/stock-movements", $payload, [
            'Idempotency-Key' => 'stock-key-1',
        ])->assertCreated();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseHas('stock_balances', [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => 3000,
        ]);
    }

    public function test_negative_stock_is_permitted_and_reported(): void
    {
        [$organization, , $product, $location] = $this->stockingContext();

        $this->postJson("/api/v1/organizations/{$organization->id}/stock-movements", [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'type' => 'sale',
            'quantity' => '2.000',
        ])->assertCreated();

        $balance = StockBalance::query()->firstOrFail();

        $this->assertSame('-2.000', $balance->quantity()->toString());
        $this->assertTrue($balance->isNegative());
    }

    public function test_movements_are_listed_in_server_order(): void
    {
        [$organization, , $product, $location] = $this->stockingContext();

        foreach (['1.000', '2.000', '3.000'] as $quantity) {
            $this->postJson("/api/v1/organizations/{$organization->id}/stock-movements", [
                'product_id' => $product->id,
                'location_id' => $location->id,
                'type' => 'receipt',
                'quantity' => $quantity,
            ])->assertCreated();
        }

        $response = $this->getJson("/api/v1/organizations/{$organization->id}/stock-movements")
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links']);

        $this->assertCount(3, $response->json('data'));
        $this->assertSame('3.000', $response->json('data.0.quantity'));
    }

    public function test_a_movement_record_is_never_edited_or_deleted(): void
    {
        [$organization, , $product, $location] = $this->stockingContext();

        $movement = $this->postJson("/api/v1/organizations/{$organization->id}/stock-movements", [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'type' => 'receipt',
            'quantity' => '5.000',
        ])->assertCreated()->json('data.id');

        // There is no update or delete endpoint for a movement, so the method
        // is not allowed on the collection route.
        $this->patchJson("/api/v1/organizations/{$organization->id}/stock-movements/{$movement}", [
            'quantity' => '999.000',
        ])->assertMethodNotAllowed();

        $this->deleteJson("/api/v1/organizations/{$organization->id}/stock-movements/{$movement}")
            ->assertMethodNotAllowed();

        $this->assertSame(5000, StockMovement::query()->firstOrFail()->quantity);
    }
}
