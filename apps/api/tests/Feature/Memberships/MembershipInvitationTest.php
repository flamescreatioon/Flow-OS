<?php

namespace Tests\Feature\Memberships;

use App\Application\Memberships\InvitationService;
use App\Application\Memberships\MembershipLifecycleService;
use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Tests\TestCase;

class MembershipInvitationTest extends TestCase
{
    public function test_invitation_stores_only_hash_and_accepts_matching_user(): void
    {
        $org = Organization::factory()->create();
        $by = User::factory()->create();
        OrganizationMembership::factory()->for($by)->for($org)->active()->create();
        [$i,$token] = app(InvitationService::class)->create($org, $by, ' Invited@Example.Test ');
        $this->assertSame('invited@example.test', $i->email);
        $this->assertNotSame($token, $i->token_hash);
        $this->assertDatabaseMissing('organization_invitations', ['token_hash' => $token]);
        $recipient = User::factory()->create(['email' => 'invited@example.test']);
        app(InvitationService::class)->accept($token, $recipient);
        $this->assertNotNull($i->fresh()->accepted_at);
        $this->assertTrue($recipient->hasActiveMembershipIn($org));
    }

    public function test_last_member_and_self_lockout_are_protected(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create();
        $m = OrganizationMembership::factory()->for($user)->for($org)->active()->create();
        $this->expectException(\LogicException::class);
        app(MembershipLifecycleService::class)->transition($m, MembershipStatus::Suspended, $user->id);
    }
}
