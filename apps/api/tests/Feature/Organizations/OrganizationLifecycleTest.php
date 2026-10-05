<?php

namespace Tests\Feature\Organizations;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganizationLifecycleTest extends TestCase
{
    public function test_active_user_creates_organization_and_bootstrap_membership(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $r = $this->postJson('/api/v1/organizations', ['name' => 'Acme Trading']);
        $r->assertCreated()->assertJsonPath('data.slug', 'acme-trading')->assertJsonPath('data.country_code', 'NG');
        $org = Organization::firstOrFail();
        $this->assertNotNull($org->id);
        $this->assertDatabaseHas('organization_memberships', ['organization_id' => $org->id, 'user_id' => $user->id, 'status' => 'active']);
        $this->assertDatabaseCount('branches', 0);
    }

    public function test_listing_show_update_and_isolation_require_active_membership(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $org = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($org)->active()->create();
        Sanctum::actingAs($user);
        $this->getJson('/api/v1/organizations')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/organizations/'.$org->id)->assertOk();
        $this->patchJson('/api/v1/organizations/'.$org->id, ['name' => 'Renamed', 'country_code' => 'NG', 'currency_code' => 'NGN', 'timezone' => 'Africa/Lagos'])->assertOk()->assertJsonPath('data.slug', $org->slug);
        Sanctum::actingAs($other);
        $this->getJson('/api/v1/organizations/'.$org->id)->assertForbidden();
    }

    public function test_slugs_and_lifecycle_transitions_are_enforced(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $a = $this->postJson('/api/v1/organizations', ['name' => 'Same Name'])->json('data');
        $b = $this->postJson('/api/v1/organizations', ['name' => 'Same Name'])->json('data');
        $this->assertSame('same-name', $a['slug']);
        $this->assertSame('same-name-2', $b['slug']);
        $this->postJson('/api/v1/organizations/'.$a['id'].'/suspend')->assertOk()->assertJsonPath('data.status', 'suspended');
        $this->postJson('/api/v1/organizations/'.$a['id'].'/activate')->assertOk();
        $this->postJson('/api/v1/organizations/'.$a['id'].'/close')->assertOk()->assertJsonPath('data.status', 'closed');
        $this->postJson('/api/v1/organizations/'.$a['id'].'/activate')->assertStatus(422);
    }

    public function test_suspended_membership_is_excluded(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        OrganizationMembership::factory()->for($user)->for($org)->suspended()->create();
        Sanctum::actingAs($user);
        $this->getJson('/api/v1/organizations')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/organizations/'.$org->id)->assertForbidden();
    }
}
