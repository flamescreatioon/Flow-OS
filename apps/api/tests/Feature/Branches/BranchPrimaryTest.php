<?php

namespace Tests\Feature\Branches;

use App\Enums\BranchStatus;
use App\Enums\MembershipStatus;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BranchPrimaryTest extends TestCase
{
    public function test_first_branch_primary(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organization)->active()->create();
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/' . $organization->id . '/branches', [
                'name' => 'First Branch',
                'code' => 'FIRST',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.is_primary', true);
    }

    public function test_second_branch_does_not_replace_primary_automatically(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organization)->active()->create();
        Branch::factory()->for($organization)->create(['code' => 'FIRST', 'is_primary' => true]);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/' . $organization->id . '/branches', [
                'name' => 'Second Branch',
                'code' => 'SECOND',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.is_primary', false);

        $this->assertDatabaseCount('branches', 2);
        $primaries = Branch::where('organization_id', $organization->id)->where('is_primary', true)->count();
        $this->assertEquals(1, $primaries);
    }

    public function test_make_primary_switches_atomically(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organization)->active()->create();
        $first = Branch::factory()->for($organization)->create(['code' => 'FIRST', 'is_primary' => true]);
        $second = Branch::factory()->for($organization)->create(['code' => 'SECOND', 'is_primary' => false]);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/' . $organization->id . '/branches/' . $second->id . '/make-primary');

        $response->assertOk()
            ->assertJsonPath('data.is_primary', true);

        $first->refresh();
        $second->refresh();

        $this->assertFalse($first->is_primary);
        $this->assertTrue($second->is_primary);
    }

    public function test_previous_primary_becomes_false(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organization)->active()->create();
        $first = Branch::factory()->for($organization)->create(['code' => 'FIRST', 'is_primary' => true]);
        $second = Branch::factory()->for($organization)->create(['code' => 'SECOND', 'is_primary' => false]);
        Sanctum::actingAs($user);

        $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/' . $organization->id . '/branches/' . $second->id . '/make-primary')
            ->assertOk();

        $first->refresh();
        $this->assertFalse($first->is_primary);
    }

    public function test_exactly_one_primary_remains(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organization)->active()->create();
        $first = Branch::factory()->for($organization)->create(['code' => 'FIRST', 'is_primary' => true]);
        $second = Branch::factory()->for($organization)->create(['code' => 'SECOND', 'is_primary' => false]);
        $third = Branch::factory()->for($organization)->create(['code' => 'THIRD', 'is_primary' => false]);
        Sanctum::actingAs($user);

        $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/' . $organization->id . '/branches/' . $second->id . '/make-primary')
            ->assertOk();

        $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/' . $organization->id . '/branches/' . $third->id . '/make-primary')
            ->assertOk();

        $primaries = Branch::where('organization_id', $organization->id)->where('is_primary', true)->count();
        $this->assertEquals(1, $primaries);
    }

    public function test_inactive_branch_cannot_become_primary(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organization)->active()->create();
        $branch = Branch::factory()->for($organization)->create(['code' => 'INACTIVE', 'status' => BranchStatus::Inactive, 'is_primary' => false]);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/' . $organization->id . '/branches/' . $branch->id . '/make-primary');

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Only active branches can be made primary.');
    }

    public function test_closed_branch_cannot_become_primary(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organization)->active()->create();
        $branch = Branch::factory()->for($organization)->create(['code' => 'CLOSED', 'status' => BranchStatus::Closed, 'is_primary' => false]);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/' . $organization->id . '/branches/' . $branch->id . '/make-primary');

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Only active branches can be made primary.');
    }

    public function test_branch_from_another_organization_cannot_be_made_primary(): void
    {
        $user = User::factory()->create();
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organizationA)->active()->create();
        OrganizationMembership::factory()->for($user)->for($organizationB)->active()->create();
        $branch = Branch::factory()->for($organizationB)->create(['code' => 'OTHER', 'is_primary' => false]);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organizationA->id])
            ->postJson('/api/v1/organizations/' . $organizationA->id . '/branches/' . $branch->id . '/make-primary');

        $response->assertNotFound();
    }

    public function test_already_primary_branch_returns_same_branch(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organization)->active()->create();
        $branch = Branch::factory()->for($organization)->create(['code' => 'PRIMARY', 'is_primary' => true]);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/' . $organization->id . '/branches/' . $branch->id . '/make-primary');

        $response->assertOk()
            ->assertJsonPath('data.is_primary', true);
    }
}