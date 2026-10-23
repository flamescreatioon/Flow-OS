<?php

namespace Tests\Feature\Memberships;

use App\Application\Memberships\InvitationService;
use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\WithRbac;
use Tests\TestCase;

class InvitationHttpTest extends TestCase
{
    use WithRbac;

    public function test_invitation_is_notified_and_inspection_is_safe(): void
    {
        Notification::fake();
        config(['app.frontend_url' => 'https://web.example.test']);
        [$organization, $actor] = $this->activeMember();

        $response = $this->actingAs($actor)->postJson("/api/v1/organizations/{$organization->id}/invitations", ['email' => ' invitee@example.test ']);
        $response->assertCreated()->assertJsonMissing(['token_hash'])->assertJsonPath('data.email', 'invitee@example.test');
        $invitation = OrganizationInvitation::firstOrFail();
        $this->assertSame(64, strlen($invitation->token_hash));
        Notification::assertSentOnDemand(OrganizationInvitationNotification::class, function ($notification, $channels, $notifiable) use ($invitation) {
            $this->assertSame('invitee@example.test', $notifiable->routes['mail']);
            $mail = $notification->toMail($notifiable);
            $this->assertStringStartsWith('https://web.example.test/invitations/', $mail->actionUrl);
            $this->assertStringNotContainsString($invitation->token_hash, $mail->actionUrl);

            return true;
        });
    }

    public function test_resend_invalidates_old_token_and_terminal_invitations_cannot_be_reused(): void
    {
        [$organization, $actor] = $this->activeMember();
        [$invitation, $oldToken] = app(InvitationService::class)->create($organization, $actor, 'invitee@example.test');
        [$invitation, $newToken] = app(InvitationService::class)->issue($invitation);
        $recipient = User::factory()->create(['email' => 'invitee@example.test']);

        $this->actingAs($recipient)->postJson("/api/v1/invitations/{$oldToken}/accept")->assertNotFound();
        $this->actingAs($recipient)->getJson("/api/v1/invitations/{$newToken}")->assertOk()->assertJsonMissing(['token_hash']);
        $this->actingAs($actor)->postJson("/api/v1/organizations/{$organization->id}/invitations/{$invitation->id}/cancel")->assertOk();
        $this->actingAs($recipient)->postJson("/api/v1/invitations/{$newToken}/accept")->assertNotFound();
        $this->actingAs($actor)->postJson("/api/v1/organizations/{$organization->id}/invitations/{$invitation->id}/resend")->assertUnprocessable();
    }

    public function test_membership_detail_and_lifecycle_routes_are_scoped(): void
    {
        [$organization, $actor] = $this->activeMember();
        $otherOrganization = Organization::factory()->create();
        $other = $this->createOrganizationAdmin($otherOrganization, User::factory()->create());
        $target = $this->createOrganizationAdmin($organization, User::factory()->create());

        $this->actingAs($actor)->getJson("/api/v1/organizations/{$organization->id}/memberships/{$other->id}")->assertNotFound();
        $this->actingAs($actor)->postJson("/api/v1/organizations/{$organization->id}/memberships/{$target->id}/suspend")
            ->assertOk()->assertJsonPath('data.status', MembershipStatus::Suspended->value);
        $this->actingAs($actor)->postJson("/api/v1/organizations/{$organization->id}/memberships/{$target->id}/activate")
            ->assertOk()->assertJsonPath('data.status', MembershipStatus::Active->value);
        $this->actingAs($actor)->postJson("/api/v1/organizations/{$organization->id}/memberships/{$target->id}/revoke")
            ->assertOk()->assertJsonPath('data.status', MembershipStatus::Revoked->value);
        $this->actingAs($actor)->postJson("/api/v1/organizations/{$organization->id}/memberships/{$target->id}/activate")->assertUnprocessable();
    }

    public function test_cross_tenant_invitation_is_not_found(): void
    {
        [$organization, $actor] = $this->activeMember();
        [$otherOrganization, $otherActor] = $this->activeMember();
        [$invitation] = app(InvitationService::class)->create($otherOrganization, $otherActor, 'outside@example.test');

        $this->actingAs($actor)->postJson("/api/v1/organizations/{$organization->id}/invitations/{$invitation->id}/cancel")->assertNotFound();
    }

    /** @return array{Organization, User} */
    private function activeMember(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $this->createOrganizationAdmin($organization, $user);

        return [$organization, $user];
    }
}
