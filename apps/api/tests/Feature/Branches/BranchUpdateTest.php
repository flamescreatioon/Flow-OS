<?php

namespace Tests\Feature\Branches;

use App\Enums\BranchStatus;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BranchUpdateTest extends TestCase
{
    public function test_allowed_profile_fields_work(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organization)->active()->create();
        $branch = Branch::factory()->for($organization)->create(['name' => 'Original Name']);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->patchJson('/api/v1/organizations/'.$organization->id.'/branches/'.$branch->id, [
                'name' => 'Updated Name',
                'phone' => '+2348012345678',
                'email' => 'updated@example.com',
                'address_line_1' => '456 New St',
                'address_line_2' => 'Floor 2',
                'city' => 'Abuja',
                'state' => 'FCT',
                'country_code' => 'NG',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.phone', '+2348012345678')
            ->assertJsonPath('data.email', 'updated@example.com')
            ->assertJsonPath('data.address_line_1', '456 New St')
            ->assertJsonPath('data.address_line_2', 'Floor 2')
            ->assertJsonPath('data.city', 'Abuja')
            ->assertJsonPath('data.state', 'FCT')
            ->assertJsonPath('data.country_code', 'NG');
    }

    public function test_code_uniqueness_remains_enforced(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organization)->active()->create();
        Branch::factory()->for($organization)->create(['code' => 'EXISTING']);
        $branch = Branch::factory()->for($organization)->create(['code' => 'OTHER']);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->patchJson('/api/v1/organizations/'.$organization->id.'/branches/'.$branch->id, [
                'code' => 'EXISTING',
            ]);

        $response->assertUnprocessable();
    }

    public function test_organization_id_cannot_change(): void
    {
        $user = User::factory()->create();
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organizationA)->active()->create();
        OrganizationMembership::factory()->for($user)->for($organizationB)->active()->create();
        $branch = Branch::factory()->for($organizationA)->create();
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organizationA->id])
            ->patchJson('/api/v1/organizations/'.$organizationA->id.'/branches/'.$branch->id, [
                'organization_id' => $organizationB->id,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'organization_id' => $organizationA->id,
        ]);
    }

    public function test_status_cannot_change_by_patch(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organization)->active()->create();
        $branch = Branch::factory()->for($organization)->create(['status' => BranchStatus::Active]);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->patchJson('/api/v1/organizations/'.$organization->id.'/branches/'.$branch->id, [
                'status' => 'closed',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'status' => 'active',
        ]);
    }

    public function test_is_primary_cannot_change_by_patch(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organization)->active()->create();
        $branch = Branch::factory()->for($organization)->create(['is_primary' => false]);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->patchJson('/api/v1/organizations/'.$organization->id.'/branches/'.$branch->id, [
                'is_primary' => true,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'is_primary' => false,
        ]);
    }

    public function test_nested_cross_organization_update_fails(): void
    {
        $user = User::factory()->create();
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organizationA)->active()->create();
        OrganizationMembership::factory()->for($user)->for($organizationB)->active()->create();
        $branch = Branch::factory()->for($organizationB)->create(['name' => 'Original']);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organizationA->id])
            ->patchJson('/api/v1/organizations/'.$organizationA->id.'/branches/'.$branch->id, [
                'name' => 'Updated',
            ]);

        $response->assertNotFound();
    }

    public function test_code_normalization_on_update(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organization)->active()->create();
        $branch = Branch::factory()->for($organization)->create(['code' => 'OLD']);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->patchJson('/api/v1/organizations/'.$organization->id.'/branches/'.$branch->id, [
                'code' => 'newcode',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.code', 'NEWCODE');
    }
}
