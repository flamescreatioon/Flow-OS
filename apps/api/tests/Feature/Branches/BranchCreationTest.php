<?php

namespace Tests\Feature\Branches;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\WithRbac;
use Tests\TestCase;

class BranchCreationTest extends TestCase
{
    use WithRbac;

    public function test_active_member_can_create_branch(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $this->createOrganizationAdmin($organization, $user);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/'.$organization->id.'/branches', [
                'name' => 'Lagos Branch',
                'code' => 'LAG',
                'phone' => '+2348012345678',
                'email' => 'lagos@example.com',
                'address_line_1' => '123 Lagos Street',
                'city' => 'Lagos',
                'state' => 'Lagos',
                'country_code' => 'NG',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Lagos Branch')
            ->assertJsonPath('data.code', 'LAG')
            ->assertJsonPath('data.organization_id', $organization->id)
            ->assertJsonPath('data.status', 'active');
    }

    public function test_non_member_cannot_create_branch(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->for($otherUser)->for($organization)->active()->create();
        Sanctum::actingAs($user);

        $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/'.$organization->id.'/branches', [
                'name' => 'Lagos Branch',
                'code' => 'LAG',
            ])
            ->assertForbidden();
    }

    public function test_suspended_membership_cannot_create_branch(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organization)->suspended()->create();
        Sanctum::actingAs($user);

        $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/'.$organization->id.'/branches', [
                'name' => 'Lagos Branch',
                'code' => 'LAG',
            ])
            ->assertForbidden();
    }

    public function test_revoked_membership_cannot_create_branch(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organization)->revoked()->create();
        Sanctum::actingAs($user);

        $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/'.$organization->id.'/branches', [
                'name' => 'Lagos Branch',
                'code' => 'LAG',
            ])
            ->assertForbidden();
    }

    public function test_suspended_organization_cannot_create_branch(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->suspended()->create();
        $this->createOrganizationAdmin($organization, $user);
        Sanctum::actingAs($user);

        $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/'.$organization->id.'/branches', [
                'name' => 'Lagos Branch',
                'code' => 'LAG',
            ])
            ->assertForbidden();
    }

    public function test_closed_organization_cannot_create_branch(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->closed()->create();
        $this->createOrganizationAdmin($organization, $user);
        Sanctum::actingAs($user);

        $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/'.$organization->id.'/branches', [
                'name' => 'Lagos Branch',
                'code' => 'LAG',
            ])
            ->assertForbidden();
    }

    public function test_first_branch_automatically_becomes_primary(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $this->createOrganizationAdmin($organization, $user);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/'.$organization->id.'/branches', [
                'name' => 'First Branch',
                'code' => 'FIRST',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.is_primary', true);
    }

    public function test_first_branch_becomes_primary_even_if_client_sends_false(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $this->createOrganizationAdmin($organization, $user);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/'.$organization->id.'/branches', [
                'name' => 'First Branch',
                'code' => 'FIRST',
                'is_primary' => false,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.is_primary', true);
    }

    public function test_uuid_is_generated(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $this->createOrganizationAdmin($organization, $user);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/'.$organization->id.'/branches', [
                'name' => 'Test Branch',
                'code' => 'TEST',
            ]);

        $response->assertCreated();
        $this->assertNotNull($response->json('data.id'));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $response->json('data.id'));
    }

    public function test_organization_ownership_is_correct(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $this->createOrganizationAdmin($organization, $user);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/'.$organization->id.'/branches', [
                'name' => 'Test Branch',
                'code' => 'TEST',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('branches', [
            'id' => $response->json('data.id'),
            'organization_id' => $organization->id,
        ]);
    }

    public function test_code_normalization_to_uppercase(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $this->createOrganizationAdmin($organization, $user);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/'.$organization->id.'/branches', [
                'name' => 'Test Branch',
                'code' => 'lowercase',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.code', 'LOWERCASE');
    }

    public function test_duplicate_code_in_same_organization_rejected(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $this->createOrganizationAdmin($organization, $user);
        Branch::factory()->for($organization)->create(['code' => 'LAGOS']);
        Sanctum::actingAs($user);

        $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->postJson('/api/v1/organizations/'.$organization->id.'/branches', [
                'name' => 'Another Lagos Branch',
                'code' => 'LAGOS',
            ])
            ->assertUnprocessable();
    }

    public function test_same_code_across_organizations_allowed(): void
    {
        $user = User::factory()->create();
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $this->createOrganizationAdmin($organizationA, $user);
        $this->createOrganizationAdmin($organizationB, $user);
        Branch::factory()->for($organizationA)->create(['code' => 'MAIN']);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organizationB->id])
            ->postJson('/api/v1/organizations/'.$organizationB->id.'/branches', [
                'name' => 'Main Branch',
                'code' => 'MAIN',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.code', 'MAIN');
    }
}
