<?php

namespace Tests\Feature\Purchasing;

use App\Models\GoodsReceipt;
use App\Models\InventoryLocation;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\WithRbac;
use Tests\TestCase;

class GoodsReceiptTest extends TestCase
{
    use WithRbac;

    /**
     * @return array{0: Organization, 1: User, 2: Product, 3: InventoryLocation}
     */
    protected function receivingContext(): array
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

    /**
     * @return array<string, mixed>
     */
    private function receiptPayload(string $productId, string $locationId, string $quantity = '10.000', string $unitCost = '1500.0000', ?string $supplierId = null): array
    {
        return array_filter([
            'supplier_id' => $supplierId,
            'location_id' => $locationId,
            'lines' => [
                [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                ],
            ],
        ], fn ($value) => $value !== null);
    }

    public function test_a_receipt_records_a_movement_with_cost_and_increases_the_balance(): void
    {
        [$organization, , $product, $location] = $this->receivingContext();
        $supplier = Supplier::factory()->for($organization)->create();

        $response = $this->postJson(
            "/api/v1/organizations/{$organization->id}/goods-receipts",
            $this->receiptPayload($product->id, $location->id, '12.500', '2500.5000', $supplier->id)
        )->assertCreated();

        $receiptId = $response->json('data.id');

        $response->assertJsonPath('data.document_number', 'GRN-000001')
            ->assertJsonPath('data.supplier_name', $supplier->name)
            ->assertJsonPath('data.received_on', now()->format('Y-m-d'))
            ->assertJsonPath('data.total_cost', '31256.2500')
            ->assertJsonPath('data.lines.0.quantity', '12.500')
            ->assertJsonPath('data.lines.0.unit_cost', '2500.5000')
            ->assertJsonPath('data.lines.0.line_total', '31256.2500');

        $this->assertDatabaseHas('goods_receipts', [
            'organization_id' => $organization->id,
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
            'receipt_number' => 1,
            'document_number' => 'GRN-000001',
            'status' => 'recorded',
            'total_cost' => 312562500,
        ]);

        $this->assertDatabaseHas('goods_receipt_lines', [
            'goods_receipt_id' => $receiptId,
            'product_name' => $product->name,
            'quantity' => 12500,
            'unit_cost' => 25005000,
            'line_total' => 312562500,
        ]);

        // The stock effect is a receipt movement that carries the cost and
        // points back at the receipt.
        $this->assertDatabaseHas('stock_movements', [
            'organization_id' => $organization->id,
            'product_id' => $product->id,
            'location_id' => $location->id,
            'type' => 'receipt',
            'quantity' => 12500,
            'unit_cost' => 25005000,
            'reference' => $receiptId,
        ]);

        $this->assertDatabaseHas('stock_balances', [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => 12500,
        ]);
    }

    public function test_line_totals_round_half_up_at_the_currency_scale(): void
    {
        [$organization, , $product, $location] = $this->receivingContext();

        // 2.500 x 1.3333 = 3.33325 -> 3.3333 at scale 4.
        $this->postJson(
            "/api/v1/organizations/{$organization->id}/goods-receipts",
            $this->receiptPayload($product->id, $location->id, '2.500', '1.3333')
        )->assertCreated()
            ->assertJsonPath('data.total_cost', '3.3333');

        $this->assertDatabaseHas('goods_receipt_lines', [
            'quantity' => 2500,
            'unit_cost' => 13333,
            'line_total' => 33333,
        ]);
    }

    public function test_each_line_becomes_its_own_movement(): void
    {
        [$organization, , $product, $location] = $this->receivingContext();
        $unit = $product->unitOfMeasure;
        $second = Product::factory()->for($organization)->withUnit($unit)->stocked()->create();

        $this->postJson("/api/v1/organizations/{$organization->id}/goods-receipts", [
            'location_id' => $location->id,
            'lines' => [
                ['product_id' => $product->id, 'quantity' => '5.000', 'unit_cost' => '1000.0000'],
                ['product_id' => $second->id, 'quantity' => '2.000', 'unit_cost' => '4000.0000'],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.total_cost', '13000.0000');

        $this->assertDatabaseCount('goods_receipt_lines', 2);
        $this->assertDatabaseCount('stock_movements', 2);

        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'quantity' => 5000]);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $second->id, 'quantity' => 2000]);

        $this->assertDatabaseHas('stock_balances', ['product_id' => $product->id, 'quantity' => 5000]);
        $this->assertDatabaseHas('stock_balances', ['product_id' => $second->id, 'quantity' => 2000]);
    }

    public function test_document_numbers_are_organization_sequential(): void
    {
        [$organization, , $product, $location] = $this->receivingContext();

        $this->postJson(
            "/api/v1/organizations/{$organization->id}/goods-receipts",
            $this->receiptPayload($product->id, $location->id)
        )->assertCreated()->assertJsonPath('data.document_number', 'GRN-000001');

        $this->postJson(
            "/api/v1/organizations/{$organization->id}/goods-receipts",
            $this->receiptPayload($product->id, $location->id)
        )->assertCreated()->assertJsonPath('data.document_number', 'GRN-000002');

        $this->assertDatabaseHas('document_sequences', [
            'organization_id' => $organization->id,
            'document_type' => 'goods_receipt',
            'next_value' => 3,
        ]);

        // A second organization starts its own sequence.
        $other = Organization::factory()->create();
        $otherAdmin = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationAdmin($other, $otherAdmin);
        Sanctum::actingAs($otherAdmin);
        $otherProduct = Product::factory()->for($other)->stocked()->create();
        $otherLocation = InventoryLocation::factory()->for($other)->create();

        $this->postJson(
            "/api/v1/organizations/{$other->id}/goods-receipts",
            $this->receiptPayload($otherProduct->id, $otherLocation->id)
        )->assertCreated()->assertJsonPath('data.document_number', 'GRN-000001');
    }

    public function test_a_receipt_replays_idempotently(): void
    {
        [$organization, , $product, $location] = $this->receivingContext();

        $payload = $this->receiptPayload($product->id, $location->id);
        $headers = ['Idempotency-Key' => 'receipt-key-1'];

        $first = $this->postJson("/api/v1/organizations/{$organization->id}/goods-receipts", $payload, $headers)
            ->assertCreated();
        $second = $this->postJson("/api/v1/organizations/{$organization->id}/goods-receipts", $payload, $headers)
            ->assertCreated();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame('GRN-000001', $second->json('data.document_number'));
        $this->assertDatabaseCount('goods_receipts', 1);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_a_receipt_against_a_product_from_another_organization_is_rejected(): void
    {
        [$organization, , , $location] = $this->receivingContext();
        $foreignProduct = Product::factory()->stocked()->create();

        $this->postJson(
            "/api/v1/organizations/{$organization->id}/goods-receipts",
            $this->receiptPayload($foreignProduct->id, $location->id)
        )->assertUnprocessable();

        $this->assertDatabaseCount('goods_receipts', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('stock_balances', 0);
    }

    public function test_a_receipt_against_a_location_from_another_organization_is_rejected(): void
    {
        [$organization, , $product] = $this->receivingContext();
        $foreignLocation = InventoryLocation::factory()->create();

        $this->postJson(
            "/api/v1/organizations/{$organization->id}/goods-receipts",
            $this->receiptPayload($product->id, $foreignLocation->id)
        )->assertUnprocessable();

        $this->assertDatabaseCount('goods_receipts', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_a_non_stocked_or_archived_product_cannot_be_received(): void
    {
        [$organization, , $product, $location] = $this->receivingContext();

        $service = Product::factory()->for($organization)->withUnit($product->unitOfMeasure)->service()->create();

        $this->postJson(
            "/api/v1/organizations/{$organization->id}/goods-receipts",
            $this->receiptPayload($service->id, $location->id)
        )->assertUnprocessable();

        $product->forceFill(['archived_at' => now()])->save();

        $this->postJson(
            "/api/v1/organizations/{$organization->id}/goods-receipts",
            $this->receiptPayload($product->id, $location->id)
        )->assertUnprocessable();

        $this->assertDatabaseCount('goods_receipts', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_an_archived_supplier_cannot_receive_goods(): void
    {
        [$organization, , $product, $location] = $this->receivingContext();
        $archived = Supplier::factory()->for($organization)->archived()->create();

        $this->postJson(
            "/api/v1/organizations/{$organization->id}/goods-receipts",
            $this->receiptPayload($product->id, $location->id, '1.000', '100.0000', $archived->id)
        )->assertUnprocessable();

        $this->assertDatabaseCount('goods_receipts', 0);
    }

    public function test_a_negative_unit_cost_or_non_positive_quantity_is_rejected(): void
    {
        [$organization, , $product, $location] = $this->receivingContext();

        $this->postJson(
            "/api/v1/organizations/{$organization->id}/goods-receipts",
            $this->receiptPayload($product->id, $location->id, '1.000', '-5.0000')
        )->assertUnprocessable();

        $this->postJson(
            "/api/v1/organizations/{$organization->id}/goods-receipts",
            $this->receiptPayload($product->id, $location->id, '-1.000', '5.0000')
        )->assertUnprocessable();

        $this->postJson(
            "/api/v1/organizations/{$organization->id}/goods-receipts",
            $this->receiptPayload($product->id, $location->id, '0.000', '5.0000')
        )->assertUnprocessable();

        $this->assertDatabaseCount('goods_receipts', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_a_bad_line_rolls_back_the_whole_receipt(): void
    {
        [$organization, , $product, $location] = $this->receivingContext();
        $foreignProduct = Product::factory()->stocked()->create();

        $this->postJson("/api/v1/organizations/{$organization->id}/goods-receipts", [
            'location_id' => $location->id,
            'lines' => [
                ['product_id' => $product->id, 'quantity' => '5.000', 'unit_cost' => '100.0000'],
                ['product_id' => $foreignProduct->id, 'quantity' => '5.000', 'unit_cost' => '100.0000'],
            ],
        ])->assertUnprocessable();

        // Nothing from the valid first line survives.
        $this->assertDatabaseCount('goods_receipts', 0);
        $this->assertDatabaseCount('goods_receipt_lines', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('stock_balances', 0);
        $this->assertDatabaseCount('document_sequences', 0);
    }

    public function test_a_receipt_is_listed_and_shown_but_never_edited_or_deleted(): void
    {
        [$organization, , $product, $location] = $this->receivingContext();

        $receiptId = $this->postJson(
            "/api/v1/organizations/{$organization->id}/goods-receipts",
            $this->receiptPayload($product->id, $location->id)
        )->assertCreated()->json('data.id');

        $this->getJson("/api/v1/organizations/{$organization->id}/goods-receipts")
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links'])
            ->assertJsonCount(1, 'data');

        $this->getJson("/api/v1/organizations/{$organization->id}/goods-receipts/{$receiptId}")
            ->assertOk()
            ->assertJsonPath('data.id', $receiptId);

        // A receipt is immutable: there is no update or delete path.
        $this->patchJson("/api/v1/organizations/{$organization->id}/goods-receipts/{$receiptId}", [
            'note' => 'amended',
        ])->assertMethodNotAllowed();

        $this->deleteJson("/api/v1/organizations/{$organization->id}/goods-receipts/{$receiptId}")
            ->assertMethodNotAllowed();
    }

    public function test_a_receipt_from_another_organization_is_not_visible(): void
    {
        [$organization] = $this->receivingContext();

        $foreign = Organization::factory()->create();
        $foreignReceipt = GoodsReceipt::factory()->for($foreign)->create();

        $this->getJson("/api/v1/organizations/{$organization->id}/goods-receipts/{$foreignReceipt->id}")
            ->assertNotFound();
    }

    public function test_a_member_can_view_but_not_receive_goods(): void
    {
        [$organization, , $product, $location] = $this->receivingContext();
        $member = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationMember($organization, $member);
        Sanctum::actingAs($member);

        $this->getJson("/api/v1/organizations/{$organization->id}/goods-receipts")->assertOk();

        $this->postJson(
            "/api/v1/organizations/{$organization->id}/goods-receipts",
            $this->receiptPayload($product->id, $location->id)
        )->assertForbidden();

        $this->assertDatabaseCount('goods_receipts', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_an_unverified_email_cannot_record_a_receipt(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->createOrganizationAdmin($organization, $user);
        $unit = UnitOfMeasure::factory()->for($organization)->create();
        $product = Product::factory()->for($organization)->withUnit($unit)->stocked()->create();
        $location = InventoryLocation::factory()->for($organization)->create();
        Sanctum::actingAs($user);

        $this->postJson(
            "/api/v1/organizations/{$organization->id}/goods-receipts",
            $this->receiptPayload($product->id, $location->id)
        )->assertForbidden();

        $this->assertDatabaseCount('goods_receipts', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_a_receipt_with_a_supplier_filters_by_supplier_in_the_list(): void
    {
        [$organization, , $product, $location] = $this->receivingContext();
        $supplier = Supplier::factory()->for($organization)->create();

        $receiptId = $this->postJson(
            "/api/v1/organizations/{$organization->id}/goods-receipts",
            $this->receiptPayload($product->id, $location->id, '1.000', '100.0000', $supplier->id)
        )->assertCreated()->json('data.id');

        $this->getJson("/api/v1/organizations/{$organization->id}/goods-receipts?supplier_id={$supplier->id}")
            ->assertOk()
            ->assertJsonPath('data.0.id', $receiptId);

        $this->getJson('/api/v1/organizations/'.$organization->id.'/goods-receipts?supplier_id='.Supplier::factory()->for($organization)->create()->id)
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_a_receipt_leaves_the_balance_exact_after_a_following_sale(): void
    {
        [$organization, , $product, $location] = $this->receivingContext();

        $this->postJson(
            "/api/v1/organizations/{$organization->id}/goods-receipts",
            $this->receiptPayload($product->id, $location->id, '10.000', '100.0000')
        )->assertCreated();

        $this->postJson("/api/v1/organizations/{$organization->id}/stock-movements", [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'type' => 'sale',
            'quantity' => '4.250',
        ])->assertCreated();

        $this->assertDatabaseHas('stock_balances', [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => 5750,
        ]);
    }
}
