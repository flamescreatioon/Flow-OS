<?php

namespace Tests\Feature\Catalogue;

use App\Models\Category;
use App\Models\InventoryLocation;
use App\Models\Organization;
use App\Models\Product;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\WithRbac;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use WithRbac;

    public function test_a_user_can_create_a_product_with_an_exact_price(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationAdmin($organization, $user);
        $unit = UnitOfMeasure::factory()->for($organization)->create();
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/organizations/{$organization->id}/products", [
            'name' => 'Cement Bag 50kg',
            'sku' => 'CEM-50',
            'type' => 'stocked',
            'price' => '4750.5000',
            'unit_of_measure_id' => $unit->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Cement Bag 50kg')
            ->assertJsonPath('data.type', 'stocked')
            ->assertJsonPath('data.price', '4750.5000');

        $this->assertDatabaseHas('products', [
            'organization_id' => $organization->id,
            'sku' => 'CEM-50',
        ]);
    }

    public function test_a_product_price_must_be_a_canonical_decimal_string(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationAdmin($organization, $user);
        $unit = UnitOfMeasure::factory()->for($organization)->create();
        Sanctum::actingAs($user);

        foreach (['.50', '1,200.50', '12.', 'abc', '12.99999'] as $price) {
            $this->postJson("/api/v1/organizations/{$organization->id}/products", [
                'name' => 'Bad Price',
                'type' => 'stocked',
                'price' => $price,
                'unit_of_measure_id' => $unit->id,
            ])->assertUnprocessable()
                ->assertJsonPath('error.code', 'VALIDATION_ERROR');
        }
    }

    public function test_a_product_cannot_reference_a_unit_from_another_organization(): void
    {
        $organization = Organization::factory()->create();
        $other = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationAdmin($organization, $user);
        $foreignUnit = UnitOfMeasure::factory()->for($other)->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/organizations/{$organization->id}/products", [
            'name' => 'Foreign Unit',
            'type' => 'stocked',
            'price' => '100.0000',
            'unit_of_measure_id' => $foreignUnit->id,
        ])->assertUnprocessable();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_a_product_cannot_reference_a_category_from_another_organization(): void
    {
        $organization = Organization::factory()->create();
        $other = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationAdmin($organization, $user);
        $unit = UnitOfMeasure::factory()->for($organization)->create();
        $foreignCategory = Category::factory()->for($other)->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/organizations/{$organization->id}/products", [
            'name' => 'Foreign Category',
            'type' => 'stocked',
            'price' => '100.0000',
            'unit_of_measure_id' => $unit->id,
            'category_id' => $foreignCategory->id,
        ])->assertUnprocessable();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_a_product_from_another_organization_is_not_found(): void
    {
        $organization = Organization::factory()->create();
        $other = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationAdmin($organization, $user);
        $foreignProduct = Product::factory()->for($other)->create();
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/organizations/{$organization->id}/products/{$foreignProduct->id}")
            ->assertNotFound();
    }

    public function test_listing_products_is_scoped_to_the_organization(): void
    {
        $organization = Organization::factory()->create();
        $other = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationAdmin($organization, $user);
        Product::factory()->for($organization)->create(['name' => 'Mine']);
        Product::factory()->for($other)->create(['name' => 'Theirs']);
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/organizations/{$organization->id}/products")
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links']);

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Mine', $response->json('data.0.name'));
    }

    public function test_a_member_may_read_products_but_not_create_them(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationMember($organization, $user);
        $unit = UnitOfMeasure::factory()->for($organization)->create();
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/organizations/{$organization->id}/products")->assertOk();

        $this->postJson("/api/v1/organizations/{$organization->id}/products", [
            'name' => 'Not Allowed',
            'type' => 'stocked',
            'price' => '100.0000',
            'unit_of_measure_id' => $unit->id,
        ])->assertForbidden();
    }

    public function test_a_user_without_a_membership_cannot_read_the_catalogue(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/organizations/{$organization->id}/products")->assertForbidden();
    }

    public function test_an_unverified_email_cannot_create_a_product(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->unverified()->create();
        $this->createOrganizationAdmin($organization, $user);
        $unit = UnitOfMeasure::factory()->for($organization)->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/organizations/{$organization->id}/products", [
            'name' => 'Unverified',
            'type' => 'stocked',
            'price' => '100.0000',
            'unit_of_measure_id' => $unit->id,
        ])->assertForbidden()
            ->assertJsonPath('error.code', 'EMAIL_NOT_VERIFIED');
    }

    public function test_a_product_is_archived_rather_than_deleted(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationAdmin($organization, $user);
        $product = Product::factory()->for($organization)->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/organizations/{$organization->id}/products/{$product->id}/archive")
            ->assertOk()
            ->assertJsonPath('data.archived_at', fn (?string $value): bool => $value !== null);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'archived_at' => $product->fresh()->archived_at]);
    }

    public function test_archived_products_are_hidden_from_the_default_listing(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationAdmin($organization, $user);
        Product::factory()->for($organization)->create(['name' => 'Active']);
        Product::factory()->for($organization)->archived()->create(['name' => 'Archived']);
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/organizations/{$organization->id}/products")->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Active', $response->json('data.0.name'));
    }

    public function test_a_product_with_movements_cannot_stop_tracking_stock(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationAdmin($organization, $user);
        $product = Product::factory()->for($organization)->stocked()->create();
        $product->stockMovements()->create([
            'organization_id' => $organization->id,
            'location_id' => InventoryLocation::factory()->for($organization)->create()->id,
            'type' => 'receipt',
            'quantity' => 1000,
            'occurred_at' => now(),
        ]);
        Sanctum::actingAs($user);

        $this->patchJson("/api/v1/organizations/{$organization->id}/products/{$product->id}", [
            'type' => 'service',
        ])->assertUnprocessable();
    }
}
