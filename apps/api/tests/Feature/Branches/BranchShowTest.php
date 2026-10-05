<?php

namespace Tests\Feature\Branches;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BranchShowTest extends TestCase
{
    public function test_valid_nested_resource_works(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organization)->active()->create();
        $branch = Branch::factory()->for($organization)->create(['name' => 'Test Branch']);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->getJson('/api/v1/organizations/'.$organization->id.'/branches/'.$branch->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $branch->id)
            ->assertJsonPath('data.name', 'Test Branch')
            ->assertJsonPath('data.organization_id', $organization->id);
    }

    public function test_org_a_url_org_b_branch_fails(): void
    {
        $user = User::factory()->create();
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organizationA)->active()->create();
        OrganizationMembership::factory()->for($user)->for($organizationB)->active()->create();
        $branch = Branch::factory()->for($organizationB)->create();
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organizationA->id])
            ->getJson('/api/v1/organizations/'.$organizationA->id.'/branches/'.$branch->id);

        $response->assertNotFound();
    }

    public function test_unrelated_user_denied(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->for($otherUser)->for($organization)->active()->create();
        $branch = Branch::factory()->for($organization)->create();
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->getJson('/api/v1/organizations/'.$organization->id.'/branches/'.$branch->id);

        $response->assertForbidden();
    }

    public function test_suspended_membership_denied_show(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organization)->suspended()->create();
        $branch = Branch::factory()->for($organization)->create();
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->getJson('/api/v1/organizations/'.$organization->id.'/branches/'.$branch->id);

        $response->assertForbidden();
    }

    public function test_branch_detail_includes_all_fields(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organization)->active()->create();
        $branch = Branch::factory()->for($organization)->create([
            'name' => 'Full Branch',
            'code' => 'FULL',
            'phone' => '+2348012345678',
            'email' => 'full@example.com',
            'address_line_1' => '123 Main St',
            'address_line_2' => 'Suite 100',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'country_code' => 'NG',
            'is_primary' => true,
        ]);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->getJson('/api/v1/organizations/'.$organization->id.'/branches/'.$branch->id);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Full Branch')
            ->assertJsonPath('data.code', 'FULL')
            ->assertJsonPath('data.phone', '+2348012345678')
            ->assertJsonPath('data.email', 'full@example.com')
            ->assertJsonPath('data.address_line_1', '123 Main St')
            ->assertJsonPath('data.address_line_2', 'Suite 100')
            ->assertJsonPath('data.city', 'Lagos')
            ->assertJsonPath('data.state', 'Lagos')
            ->assertJsonPath('data.country_code', 'NG')
            ->assertJsonPath('data.is_primary', true)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonStructure(['data' => ['id', 'organization_id', 'name', 'code', 'phone', 'email', 'address_line_1', 'address_line_2', 'city', 'state', 'country_code', 'is_primary', 'status', 'created_at', 'updated_at']]);
    }
}
