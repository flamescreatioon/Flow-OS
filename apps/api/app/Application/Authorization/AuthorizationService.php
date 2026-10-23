<?php

namespace App\Application\Authorization;

use App\Application\Audit\AuditLogger;
use App\Enums\MembershipStatus;
use App\Enums\PermissionKey;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

final class AuthorizationService
{
    public const OWNER = 'owner';

    public const ADMIN = 'admin';

    public const MEMBER = 'member';

    public function bootstrap(Organization $organization): array
    {
        foreach (PermissionKey::cases() as $permission) {
            Permission::firstOrCreate(['key' => $permission->value], ['description' => $permission->value]);
        }
        $owner = $this->systemRole($organization, 'Owner', self::OWNER);
        $admin = $this->systemRole($organization, 'Admin', self::ADMIN);
        $member = $this->systemRole($organization, 'Member', self::MEMBER);
        $admin->permissions()->sync(Permission::whereIn('key', [PermissionKey::OrganizationView->value, PermissionKey::OrganizationUpdate->value, PermissionKey::BranchView->value, PermissionKey::BranchCreate->value, PermissionKey::BranchUpdate->value, PermissionKey::BranchLifecycle->value, PermissionKey::BranchSetPrimary->value, PermissionKey::MembershipView->value, PermissionKey::MembershipInvite->value, PermissionKey::MembershipSuspend->value, PermissionKey::MembershipActivate->value, PermissionKey::MembershipRevoke->value, PermissionKey::RoleView->value, PermissionKey::ProductView->value, PermissionKey::ProductCreate->value, PermissionKey::ProductUpdate->value, PermissionKey::ProductArchive->value, PermissionKey::CatalogueView->value, PermissionKey::CatalogueManage->value, PermissionKey::StockView->value, PermissionKey::StockMove->value, PermissionKey::StockAdjust->value, PermissionKey::StockTransfer->value, PermissionKey::StockCount->value])->pluck('id'));
        $member->permissions()->sync(Permission::whereIn('key', [PermissionKey::OrganizationView->value, PermissionKey::BranchView->value, PermissionKey::ProductView->value, PermissionKey::CatalogueView->value, PermissionKey::StockView->value])->pluck('id'));

        return compact('owner', 'admin', 'member');
    }

    public function backfill(): void
    {
        Organization::query()->each(function (Organization $o) {
            $roles = $this->bootstrap($o);
            $active = $o->memberships()->where('status', MembershipStatus::Active)->orderBy('joined_at')->orderBy('created_at')->orderBy('id')->get();
            if ($active->isEmpty()) {
                return;
            } if (! $active->contains(fn ($m) => $m->roles()->where('slug', self::OWNER)->exists())) {
                $active->first()->roles()->syncWithoutDetaching([$roles['owner']->id]);
            } foreach ($active as $m) {
                if (! $m->roles()->exists()) {
                    $m->roles()->syncWithoutDetaching([$roles['member']->id]);
                }
            }
        });
    }

    public function allows(User $user, Organization $organization, string $permission): bool
    {
        $m = $organization->memberships()->where('user_id', $user->id)->where('status', MembershipStatus::Active)->with('roles.permissions')->first();
        if (! $m) {
            return false;
        } if ($m->roles->contains('slug', self::OWNER)) {
            return true;
        }

        return $m->roles->flatMap->permissions->pluck('key')->contains($permission);
    }

    public function membership(User $user, Organization $organization): ?OrganizationMembership
    {
        return $organization->memberships()->where('user_id', $user->id)->where('status', MembershipStatus::Active)->first();
    }

    public function assign(Organization $organization, OrganizationMembership $membership, array $roleIds, User $actor): void
    {
        DB::transaction(function () use ($organization, $membership, $roleIds, $actor) {
            if ($membership->organization_id !== $organization->id) {
                throw new LogicException('Membership mismatch.');
            } $roles = Role::where('organization_id', $organization->id)->whereIn('id', array_unique($roleIds))->lockForUpdate()->get();
            if ($roles->count() !== count(array_unique($roleIds))) {
                throw new LogicException('Role mismatch.');
            } $currentOwner = $membership->roles()->where('slug', self::OWNER)->exists();
            $ownerChanging = $roles->contains('slug', self::OWNER) !== $currentOwner;
            if ($ownerChanging && ! $this->isOwner($actor, $organization)) {
                throw new LogicException('Owner required.');
            } if ($membership->status === MembershipStatus::Revoked) {
                throw new LogicException('Revoked membership.');
            }
            if ($currentOwner && ! $roles->contains('slug', self::OWNER) && $membership->status === MembershipStatus::Active) {
                $owners = $organization->memberships()->where('status', MembershipStatus::Active)
                    ->whereHas('roles', fn ($query) => $query->where('slug', self::OWNER))
                    ->lockForUpdate()->get();
                if ($owners->count() <= 1) {
                    throw new LogicException('Organization must retain an active owner.');
                }
            }
            $membership->roles()->sync($roles->pluck('id'));
            if (! $this->hasActiveOwner($organization)) {
                throw new LogicException('Organization needs an owner.');
            }
            app(AuditLogger::class)->record($organization, $actor, 'membership.roles_assigned', 'membership', $membership->id, ['organization_id' => $organization->id, 'membership_id' => $membership->id]);
        });
    }

    public function isOwner(User $u, Organization $o): bool
    {
        return $this->allows($u, $o, PermissionKey::RoleAssign->value) && $o->memberships()->where('user_id', $u->id)->where('status', MembershipStatus::Active)->whereHas('roles', fn ($q) => $q->where('slug', self::OWNER))->exists();
    }

    private function hasActiveOwner(Organization $o): bool
    {
        return $o->memberships()->where('status', MembershipStatus::Active)->whereHas('roles', fn ($q) => $q->where('slug', self::OWNER))->lockForUpdate()->get()->isNotEmpty();
    }

    private function systemRole(Organization $o, string $name, string $slug): Role
    {
        return Role::firstOrCreate(['organization_id' => $o->id, 'slug' => $slug], ['name' => $name, 'is_system' => true]);
    }
}
