<?php

namespace Tests\Feature\Branches;

use App\Enums\BranchStatus;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\WithRbac;
use Tests\TestCase;

class BranchLifecycleTest extends TestCase
{
    use WithRbac;

    public function test_active_to_inactive(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $this->createOrganizationAdmin($organization, $user);
        $branch = Branch::factory()->for($organization)->create(['status' => BranchStatus::Active, 'is_primary' => false]);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/'.$organization->id.'/branches/'.$branch->id.'/deactivate');

        $response->assertOk()
            ->assertJsonPath('data.status', 'inactive');
    }

    public function test_inactive_to_active(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $this->createOrganizationAdmin($organization, $user);
        $branch = Branch::factory()->for($organization)->create(['status' => BranchStatus::Inactive]);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/'.$organization->id.'/branches/'.$branch->id.'/activate');

        $response->assertOk()
            ->assertJsonPath('data.status', 'active');
    }

    public function test_active_to_closed(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $this->createOrganizationAdmin($organization, $user);
        $branch = Branch::factory()->for($organization)->create(['status' => BranchStatus::Active, 'is_primary' => false]);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/'.$organization->id.'/branches/'.$branch->id.'/close');

        $response->assertOk()
            ->assertJsonPath('data.status', 'closed');
    }

    public function test_inactive_to_closed(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $this->createOrganizationAdmin($organization, $user);
        $branch = Branch::factory()->for($organization)->create(['status' => BranchStatus::Inactive]);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/'.$organization->id.'/branches/'.$branch->id.'/close');

        $response->assertOk()
            ->assertJsonPath('data.status', 'closed');
    }

    public function test_closed_to_active_denied(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $this->createOrganizationAdmin($organization, $user);
        $branch = Branch::factory()->for($organization)->create(['status' => BranchStatus::Closed]);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/'.$organization->id.'/branches/'.$branch->id.'/activate');

        $response->assertUnprocessable();
    }

    public function test_closed_to_inactive_denied(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $this->createOrganizationAdmin($organization, $user);
        $branch = Branch::factory()->for($organization)->create(['status' => BranchStatus::Closed]);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/'.$organization->id.'/branches/'.$branch->id.'/deactivate');

        $response->assertUnprocessable();
    }

    public function test_primary_branch_cannot_be_deactivated(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $this->createOrganizationAdmin($organization, $user);
        $branch = Branch::factory()->for($organization)->create(['status' => BranchStatus::Active, 'is_primary' => true]);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/'.$organization->id.'/branches/'.$branch->id.'/deactivate');

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Primary branch cannot be deactivated or closed. Make another branch primary first.');
    }

    public function test_primary_branch_cannot_be_closed(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $this->createOrganizationAdmin($organization, $user);
        $branch = Branch::factory()->for($organization)->create(['status' => BranchStatus::Active, 'is_primary' => true]);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/'.$organization->id.'/branches/'.$branch->id.'/close');

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Primary branch cannot be deactivated or closed. Make another branch primary first.');
    }

    public function test_deactivate_then_activate_returns_to_active(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $this->createOrganizationAdmin($organization, $user);
        $branch = Branch::factory()->for($organization)->create(['status' => BranchStatus::Active, 'is_primary' => false]);
        Sanctum::actingAs($user);

        $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/'.$organization->id.'/branches/'.$branch->id.'/deactivate')
            ->assertOk();

        $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/'.$organization->id.'/branches/'.$branch->id.'/activate')
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
    }

    public function test_same_state_transition_returns_error(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $this->createOrganizationAdmin($organization, $user);
        $branch = Branch::factory()->for($organization)->create(['status' => BranchStatus::Active, 'is_primary' => false]);
        Sanctum::actingAs($user);

        // Deactivate twice
        $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/'.$organization->id.'/branches/'.$branch->id.'/deactivate')
            ->assertOk();

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/'.$organization->id.'/branches/'.$branch->id.'/deactivate');

        $response->assertUnprocessable();
    }
}
