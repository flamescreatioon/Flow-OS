<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryLocation;
use App\Models\Organization;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\WithRbac;
use Tests\TestCase;

class InventoryLocationTest extends TestCase
{
    use WithRbac;

    /**
     * @return array{0: Organization, 1: User}
     */
    protected function locationContext(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationAdmin($organization, $user);
        Sanctum::actingAs($user);

        return [$organization, $user];
    }

    public function test_a_location_code_must_be_unique_within_the_organization(): void
    {
        [$organization] = $this->locationContext();
        InventoryLocation::factory()->for($organization)->create(['code' => 'MAIN']);

        $this->postJson("/api/v1/organizations/{$organization->id}/inventory-locations", [
            'code' => 'MAIN',
            'name' => 'Second store',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('inventory_locations', 1);
    }

    public function test_a_location_code_may_be_reused_by_another_organization(): void
    {
        [$organization] = $this->locationContext();
        $other = Organization::factory()->create();
        InventoryLocation::factory()->for($other)->create(['code' => 'MAIN']);

        $this->postJson("/api/v1/organizations/{$organization->id}/inventory-locations", [
            'code' => 'MAIN',
            'name' => 'Own main store',
        ])->assertCreated()
            ->assertJsonPath('data.code', 'MAIN');

        $this->assertDatabaseCount('inventory_locations', 2);
    }

    public function test_an_update_may_not_take_another_locations_code(): void
    {
        [$organization] = $this->locationContext();
        $location = InventoryLocation::factory()->for($organization)->create(['code' => 'MAIN']);
        $sibling = InventoryLocation::factory()->for($organization)->create(['code' => 'ANNEX']);

        $this->patchJson("/api/v1/organizations/{$organization->id}/inventory-locations/{$location->id}", [
            'code' => 'ANNEX',
        ])->assertUnprocessable();

        $this->assertDatabaseHas('inventory_locations', [
            'id' => $location->id,
            'code' => 'MAIN',
        ]);
        $this->assertDatabaseHas('inventory_locations', [
            'id' => $sibling->id,
            'code' => 'ANNEX',
        ]);
    }

    public function test_an_update_may_keep_the_locations_own_code(): void
    {
        [$organization] = $this->locationContext();
        $location = InventoryLocation::factory()->for($organization)->create(['code' => 'MAIN']);

        $this->patchJson("/api/v1/organizations/{$organization->id}/inventory-locations/{$location->id}", [
            'code' => 'MAIN',
            'name' => 'Renamed store',
        ])->assertOk()
            ->assertJsonPath('data.code', 'MAIN')
            ->assertJsonPath('data.name', 'Renamed store');
    }

    public function test_an_archived_location_still_holds_its_code(): void
    {
        [$organization] = $this->locationContext();
        $archived = InventoryLocation::factory()->for($organization)->create(['code' => 'OLD']);
        $archived->forceFill(['archived_at' => now()])->save();

        $this->postJson("/api/v1/organizations/{$organization->id}/inventory-locations", [
            'code' => 'OLD',
            'name' => 'Reuse attempt',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('inventory_locations', 1);
    }

    public function test_a_location_of_another_organization_cannot_be_updated(): void
    {
        [$organization] = $this->locationContext();
        $other = Organization::factory()->create();
        $foreignLocation = InventoryLocation::factory()->for($other)->create(['code' => 'FOREIGN']);

        $this->patchJson("/api/v1/organizations/{$organization->id}/inventory-locations/{$foreignLocation->id}", [
            'name' => 'Hijacked name',
        ])->assertNotFound();

        $this->assertDatabaseHas('inventory_locations', [
            'id' => $foreignLocation->id,
            'name' => $foreignLocation->name,
        ]);
    }
}
