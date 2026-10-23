<?php

namespace App\Application\Memberships;

use App\Application\Audit\AuditLogger;
use App\Enums\MembershipStatus;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

final class MembershipLifecycleService
{
    public function transition(OrganizationMembership $m, MembershipStatus $to, User|string $actor): OrganizationMembership
    {
        return DB::transaction(function () use ($m, $to, $actor) {
            $actorUser = $actor instanceof User ? $actor : User::find($actor);
            $m = $m->newQuery()->lockForUpdate()->findOrFail($m->id);
            if ($actorUser && $m->user_id === $actorUser->id) {
                throw new LogicException('Self-lockout is not allowed.');
            } if ($m->status === MembershipStatus::Revoked || ! in_array($to, $m->status === MembershipStatus::Active ? [MembershipStatus::Suspended, MembershipStatus::Revoked] : [MembershipStatus::Active, MembershipStatus::Revoked], true)) {
                throw new LogicException('Invalid membership transition.');
            }
            if (in_array($to, [MembershipStatus::Suspended, MembershipStatus::Revoked], true)) {
                $activeMemberships = $m->organization->memberships()
                    ->where('status', MembershipStatus::Active)
                    ->lockForUpdate()
                    ->get();
                if ($activeMemberships->count() <= 1) {
                    throw new LogicException('Organization must retain an active membership.');
                }
                $activeOwners = $m->organization->memberships()
                    ->where('status', MembershipStatus::Active)
                    ->whereHas('roles', fn ($query) => $query->where('slug', 'owner'))
                    ->lockForUpdate()
                    ->get();
                if ($activeOwners->contains('id', $m->id) && $activeOwners->count() <= 1) {
                    throw new LogicException('Organization must retain an active owner.');
                }
            } $m->forceFill(['status' => $to, 'suspended_at' => $to === MembershipStatus::Suspended ? now() : null, 'revoked_at' => $to === MembershipStatus::Revoked ? now() : null])->save();

            if ($m->organization) {
                app(AuditLogger::class)->record($m->organization, $actorUser, 'membership.'.$to->value, 'membership', $m->id, ['organization_id' => $m->organization_id, 'membership_id' => $m->id, 'to' => $to->value]);
            }

            return $m;
        });
    }
}
