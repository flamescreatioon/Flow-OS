<?php

namespace Tests\Feature\Purchasing;

use App\Models\Organization;
use App\Models\Supplier;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\WithRbac;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use WithRbac;

    /**
     * @return array{0: Organization, 1: User}
     */
    protected function supplierContext(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationAdmin($organization, $user);
        Sanctum::actingAs($user);

        return [$organization, $user];
    }

    public function test_an_admin_creates_a_supplier(): void
    {
        [$organization] = $this->supplierContext();

        $this->postJson("/api/v1/organizations/{$organization->id}/suppliers", [
            'name' => 'Lagos Beverage Supply',
            'email' => 'sales@lagosbev.example',
            'phone' => '0803 000 0001',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Lagos Beverage Supply')
            ->assertJsonPath('data.email', 'sales@lagosbev.example');

        $this->assertDatabaseHas('suppliers', [
            'organization_id' => $organization->id,
            'name' => 'Lagos Beverage Supply',
        ]);
    }

    public function test_a_supplier_name_is_not_unique(): void
    {
        [$organization] = $this->supplierContext();

        Supplier::factory()->for($organization)->create(['name' => 'Abeokuta Traders']);

        $this->postJson("/api/v1/organizations/{$organization->id}/suppliers", [
            'name' => 'Abeokuta Traders',
        ])->assertCreated();
    }

    public function test_suppliers_are_listed_with_pagination(): void
    {
        [$organization] = $this->supplierContext();

        Supplier::factory()->count(3)->for($organization)->create();

        $response = $this->getJson("/api/v1/organizations/{$organization->id}/suppliers")
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links']);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_archived_suppliers_are_hidden_from_the_default_list(): void
    {
        [$organization] = $this->supplierContext();

        Supplier::factory()->for($organization)->create(['name' => 'Active Supplier']);
        Supplier::factory()->for($organization)->archived()->create(['name' => 'Archived Supplier']);

        $active = $this->getJson("/api/v1/organizations/{$organization->id}/suppliers")->assertOk();
        $this->assertSame(['Active Supplier'], array_column($active->json('data'), 'name'));

        $archived = $this->getJson("/api/v1/organizations/{$organization->id}/suppliers?archived=true")->assertOk();
        $this->assertSame(['Archived Supplier'], array_column($archived->json('data'), 'name'));
    }

    public function test_a_supplier_is_updated(): void
    {
        [$organization] = $this->supplierContext();
        $supplier = Supplier::factory()->for($organization)->create();

        $this->patchJson("/api/v1/organizations/{$organization->id}/suppliers/{$supplier->id}", [
            'phone' => '0803 000 0002',
        ])->assertOk()
            ->assertJsonPath('data.phone', '0803 000 0002')
            ->assertJsonPath('data.name', $supplier->name);
    }

    public function test_a_supplier_is_archived_and_never_deleted(): void
    {
        [$organization] = $this->supplierContext();
        $supplier = Supplier::factory()->for($organization)->create();

        $this->postJson("/api/v1/organizations/{$organization->id}/suppliers/{$supplier->id}/archive")
            ->assertOk()
            ->assertJsonPath('data.archived_at', fn ($value) => $value !== null);

        // There is no delete endpoint for a supplier, so the method is not
        // allowed on the item route.
        $this->deleteJson("/api/v1/organizations/{$organization->id}/suppliers/{$supplier->id}")
            ->assertMethodNotAllowed();

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    public function test_a_supplier_from_another_organization_is_not_visible(): void
    {
        [$organization] = $this->supplierContext();
        $foreign = Supplier::factory()->create();

        $this->getJson("/api/v1/organizations/{$organization->id}/suppliers/{$foreign->id}")
            ->assertNotFound();

        $this->getJson("/api/v1/organizations/{$organization->id}/suppliers")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_a_member_can_read_but_not_write_suppliers(): void
    {
        [$organization] = $this->supplierContext();
        $member = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationMember($organization, $member);
        Sanctum::actingAs($member);

        $this->getJson("/api/v1/organizations/{$organization->id}/suppliers")->assertOk();

        $this->postJson("/api/v1/organizations/{$organization->id}/suppliers", [
            'name' => 'Forbidden Supplier',
        ])->assertForbidden();

        $this->assertDatabaseCount('suppliers', 0);
    }

    public function test_an_unverified_email_cannot_create_a_supplier(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->createOrganizationAdmin($organization, $user);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/organizations/{$organization->id}/suppliers", [
            'name' => 'Unverified Supplier',
        ])->assertForbidden();

        $this->assertDatabaseCount('suppliers', 0);
    }
}
