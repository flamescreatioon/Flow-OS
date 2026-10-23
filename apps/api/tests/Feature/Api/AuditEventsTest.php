<?php

namespace Tests\Feature\Api;

use App\Models\AuditEvent;
use App\Models\Organization;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\WithRbac;
use Tests\TestCase;

class AuditEventsTest extends TestCase
{
    use WithRbac;

    public function test_organization_lifecycle_is_audited_with_allowlisted_metadata(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Sanctum::actingAs($user);

        $org = $this->postJson('/api/v1/organizations', ['name' => 'Audited Org'])->assertCreated()->json('data');

        $this->postJson("/api/v1/organizations/{$org['id']}/suspend")->assertOk();

        $events = AuditEvent::where('organization_id', $org['id'])->orderBy('created_at')->orderBy('id')->get();
        $this->assertCount(2, $events);
        $this->assertSame('organization.created', $events[0]->event_type);
        $this->assertSame('organization.suspended', $events[1]->event_type);

        $metadata = $events[1]->metadata ?? [];
        $this->assertArrayHasKey('organization_id', $metadata);
        $this->assertArrayNotHasKey('password', $metadata);
    }

    public function test_audit_events_are_read_only_and_scoped(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $organization = Organization::factory()->create();
        $this->createOrganizationOwner($organization, $user);
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/organizations/{$organization->id}/audit-events")
            ->assertOk()->assertJsonStructure(['data', 'meta']);

        $other = Organization::factory()->create();
        $this->getJson("/api/v1/organizations/{$other->id}/audit-events")->assertForbidden();
    }

    public function test_member_without_membership_view_cannot_read_audit_events(): void
    {
        $organization = Organization::factory()->create();
        $member = User::factory()->create();
        $this->createOrganizationMember($organization, $member);

        Sanctum::actingAs($member);
        $this->getJson("/api/v1/organizations/{$organization->id}/audit-events")->assertForbidden();

        $admin = User::factory()->create();
        $this->createOrganizationAdmin($organization, $admin);

        Sanctum::actingAs($admin);
        $this->getJson("/api/v1/organizations/{$organization->id}/audit-events")->assertOk();
    }
}
