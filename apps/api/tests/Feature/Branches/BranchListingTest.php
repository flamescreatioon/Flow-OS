<?php

namespace Tests\Feature\Branches;

use App\Enums\BranchStatus;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BranchListingTest extends TestCase
{
    public function test_only_requested_organizations_branches_returned(): void
    {
        $user = User::factory()->create();
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organizationA)->active()->create();
        OrganizationMembership::factory()->for($user)->for($organizationB)->active()->create();

        Branch::factory()->for($organizationA)->create(['name' => 'Org A Branch 1']);
        Branch::factory()->for($organizationA)->create(['name' => 'Org A Branch 2']);
        Branch::factory()->for($organizationB)->create(['name' => 'Org B Branch 1']);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organizationA->id])
            ->getJson('/api/v1/organizations/'.$organizationA->id.'/branches');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Org A Branch 1')
            ->assertJsonPath('data.1.name', 'Org A Branch 2');
    }

    public function test_active_inactive_closed_branches_are_visible(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organization)->active()->create();

        Branch::factory()->for($organization)->create(['name' => 'Active Branch', 'status' => BranchStatus::Active]);
        Branch::factory()->for($organization)->create(['name' => 'Inactive Branch', 'status' => BranchStatus::Inactive]);
        Branch::factory()->for($organization)->create(['name' => 'Closed Branch', 'status' => BranchStatus::Closed]);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->getJson('/api/v1/organizations/'.$organization->id.'/branches');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
        $statuses = collect($response->json('data'))->pluck('status')->toArray();
        $this->assertContains('active', $statuses);
        $this->assertContains('inactive', $statuses);
        $this->assertContains('closed', $statuses);
    }

    public function test_unrelated_branches_excluded(): void
    {
        $user = User::factory()->create();
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organizationA)->active()->create();

        Branch::factory()->for($organizationB)->create(['name' => 'Org B Branch']);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organizationA->id])
            ->getJson('/api/v1/organizations/'.$organizationA->id.'/branches');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_non_member_denied_listing(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->for($otherUser)->for($organization)->active()->create();
        Branch::factory()->for($organization)->create();
        Sanctum::actingAs($user);

        $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->getJson('/api/v1/organizations/'.$organization->id.'/branches')
            ->assertForbidden();
    }

    public function test_suspended_membership_denied_listing(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organization)->suspended()->create();
        Branch::factory()->for($organization)->create();
        Sanctum::actingAs($user);

        $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->getJson('/api/v1/organizations/'.$organization->id.'/branches')
            ->assertForbidden();
    }

    public function test_listing_orders_primary_first(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organization)->active()->create();

        Branch::factory()->for($organization)->create(['name' => 'Secondary', 'is_primary' => false]);
        Branch::factory()->for($organization)->create(['name' => 'Primary Branch', 'is_primary' => true]);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->getJson('/api/v1/organizations/'.$organization->id.'/branches');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'Primary Branch')
            ->assertJsonPath('data.1.name', 'Secondary');
    }
}
