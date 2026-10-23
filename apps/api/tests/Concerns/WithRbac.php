<?php

namespace Tests\Concerns;

use App\Application\Authorization\AuthorizationService;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;

trait WithRbac
{
    /**
     * Create an organization membership with the system Owner role.
     */
    public function createOrganizationOwner(Organization $organization, ?User $user = null): OrganizationMembership
    {
        return $this->assignSystemRole($organization, $user ?? User::factory()->create(), AuthorizationService::OWNER);
    }

    /**
     * Create an organization membership with the system Admin role.
     */
    public function createOrganizationAdmin(Organization $organization, ?User $user = null): OrganizationMembership
    {
        return $this->assignSystemRole($organization, $user ?? User::factory()->create(), AuthorizationService::ADMIN);
    }

    /**
     * Create an organization membership with the system Member role.
     */
    public function createOrganizationMember(Organization $organization, ?User $user = null): OrganizationMembership
    {
        return $this->assignSystemRole($organization, $user ?? User::factory()->create(), AuthorizationService::MEMBER);
    }

    /**
     * Assign a system role (owner, admin, member) to a user in the given organization.
     */
    public function assignSystemRole(Organization $organization, User $user, string $systemRoleSlug): OrganizationMembership
    {
        $roles = app(AuthorizationService::class)->bootstrap($organization);

        $membership = OrganizationMembership::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $membership) {
            $membership = OrganizationMembership::factory()
                ->for($user)
                ->for($organization)
                ->active()
                ->create();
        }

        $targetRole = $roles[$systemRoleSlug] ?? $organization->roles()->where('slug', $systemRoleSlug)->firstOrFail();
        $membership->roles()->syncWithoutDetaching([$targetRole->id]);

        return $membership->fresh(['roles']);
    }

    /**
     * Assign a custom role or role model to an organization membership.
     */
    public function assignCustomRole(OrganizationMembership $membership, Role $role): OrganizationMembership
    {
        $membership->roles()->syncWithoutDetaching([$role->id]);

        return $membership->fresh(['roles']);
    }
}
