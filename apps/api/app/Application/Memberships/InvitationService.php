<?php

namespace App\Application\Memberships;

use App\Application\Audit\AuditLogger;
use App\Application\Authorization\AuthorizationService;
use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use LogicException;

final class InvitationService
{
    public function create(Organization $o, User $by, string $email): array
    {
        $email = strtolower(trim($email));
        if (OrganizationMembership::where('organization_id', $o->id)->whereHas('user', fn ($q) => $q->where('email', $email))->whereIn('status', [MembershipStatus::Active, MembershipStatus::Suspended])->exists()) {
            throw new LogicException('Membership already exists.');
        }
        if (OrganizationInvitation::where('organization_id', $o->id)->where('email', $email)->get()->contains(fn ($i) => $i->state() === 'pending')) {
            throw new LogicException('Pending invitation exists.');
        }

        $invitation = new OrganizationInvitation;
        $invitation->organization_id = $o->id;
        $invitation->invited_by_user_id = $by->id;
        $invitation->email = $email;

        return $this->issue($invitation);
    }

    public function issue(OrganizationInvitation $i): array
    {
        if ($i->exists && ! in_array($i->state(), ['pending', 'expired'], true)) {
            throw new LogicException('Invitation cannot be resent.');
        }

        $token = Str::random(64);
        $i->token_hash = hash('sha256', $token);
        $i->expires_at = now()->addHours(config('invitations.expires_in_hours'));
        $i->save();

        return [$i, $token];
    }

    public function send(OrganizationInvitation $invitation, string $token): void
    {
        Notification::route('mail', $invitation->email)
            ->notify(new OrganizationInvitationNotification($invitation, $token));
    }

    public function inspect(string $token): OrganizationInvitation
    {
        $invitation = OrganizationInvitation::with('organization')
            ->where('token_hash', hash('sha256', $token))->first();

        if (! $invitation) {
            throw new LogicException('Invalid invitation.');
        }

        return $invitation;
    }

    public function accept(string $token, User $user, bool $decline = false): OrganizationInvitation
    {
        return DB::transaction(function () use ($token, $user, $decline) {
            $i = OrganizationInvitation::where('token_hash', hash('sha256', $token))->lockForUpdate()->first();
            if (! $i || $i->state() !== 'pending' || $i->email !== strtolower(trim($user->email))) {
                throw new LogicException('Invalid invitation.');
            } if ($decline) {
                $i->declined_at = now();
                $i->save();
                app(AuditLogger::class)->record($i->organization, $user, 'invitation.declined', 'invitation', $i->id, ['organization_id' => $i->organization_id, 'invitation_id' => $i->id]);

                return $i;
            } if (OrganizationMembership::where('organization_id', $i->organization_id)->where('user_id', $user->id)->exists()) {
                throw new LogicException('Membership already exists.');
            } $m = new OrganizationMembership;
            $m->forceFill(['organization_id' => $i->organization_id, 'user_id' => $user->id, 'status' => MembershipStatus::Active, 'joined_at' => now()])->save();
            $roles = app(AuthorizationService::class)->bootstrap($i->organization);
            $m->roles()->sync([$roles['member']->id]);
            $i->accepted_at = now();
            $i->save();
            app(AuditLogger::class)->record($i->organization, $user, 'invitation.accepted', 'invitation', $i->id, ['organization_id' => $i->organization_id, 'invitation_id' => $i->id, 'membership_id' => $m->id]);

            return $i;
        });
    }
}
