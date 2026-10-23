<?php

namespace Tests\Feature\Authorization;

use App\Application\Authorization\AuthorizationService;
use App\Enums\PermissionKey;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Tests\Concerns\WithRbac;
use Tests\TestCase;

class RoleCrudAndAssignmentTest extends TestCase
{
    use WithRbac;

    // --- 1. Role Listing ---

    public function test_owner_can_list_roles(): void
    {
        $org = Organization::factory()->create();
        $owner = User::factory()->create();
        $this->createOrganizationOwner($org, $owner);

        $response = $this->actingAs($owner)->getJson("/api/v1/organizations/{$org->id}/roles");
        $response->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links'])
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_admin_can_list_roles(): void
    {
        $org = Organization::factory()->create();
        $admin = User::factory()->create();
        $this->createOrganizationAdmin($org, $admin);

        $response = $this->actingAs($admin)->getJson("/api/v1/organizations/{$org->id}/roles");
        $response->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links'])
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_member_cannot_list_roles(): void
    {
        $org = Organization::factory()->create();
        $member = User::factory()->create();
        $this->createOrganizationMember($org, $member);

        $this->actingAs($member)->getJson("/api/v1/organizations/{$org->id}/roles")
            ->assertForbidden();
    }

    public function test_role_less_membership_cannot_list_roles(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create();
        OrganizationMembership::factory()->for($user)->for($org)->active()->create();

        $this->actingAs($user)->getJson("/api/v1/organizations/{$org->id}/roles")
            ->assertForbidden();
    }

    public function test_cross_tenant_role_listing_is_strictly_scoped(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $ownerA = User::factory()->create();
        $this->createOrganizationOwner($orgA, $ownerA);

        // Owner of orgA cannot view orgB roles
        $this->actingAs($ownerA)->getJson("/api/v1/organizations/{$orgB->id}/roles")
            ->assertForbidden();
    }

    public function test_permission_catalogue_listing(): void
    {
        $org = Organization::factory()->create();
        $admin = User::factory()->create();
        $this->createOrganizationAdmin($org, $admin);

        $response = $this->actingAs($admin)->getJson("/api/v1/organizations/{$org->id}/permissions");
        $response->assertOk();
        $this->assertCount(count(PermissionKey::cases()), $response->json('data'));
    }

    // --- 2. Role Creation ---

    public function test_owner_can_create_custom_role(): void
    {
        $org = Organization::factory()->create();
        $owner = User::factory()->create();
        $this->createOrganizationOwner($org, $owner);

        $perm = Permission::firstOrCreate(['key' => PermissionKey::BranchView->value]);

        $response = $this->actingAs($owner)->postJson("/api/v1/organizations/{$org->id}/roles", [
            'name' => 'Auditor',
            'description' => 'Audits branches and records',
            'permission_ids' => [$perm->id],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Auditor')
            ->assertJsonPath('data.slug', 'auditor')
            ->assertJsonPath('data.is_system', false)
            ->assertJsonCount(1, 'data.permissions');
    }

    public function test_admin_cannot_create_roles(): void
    {
        $org = Organization::factory()->create();
        $admin = User::factory()->create();
        $this->createOrganizationAdmin($org, $admin);

        $this->actingAs($admin)->postJson("/api/v1/organizations/{$org->id}/roles", [
            'name' => 'Auditor',
        ])->assertForbidden();
    }

    public function test_member_cannot_create_roles(): void
    {
        $org = Organization::factory()->create();
        $member = User::factory()->create();
        $this->createOrganizationMember($org, $member);

        $this->actingAs($member)->postJson("/api/v1/organizations/{$org->id}/roles", [
            'name' => 'Auditor',
        ])->assertForbidden();
    }

    public function test_cannot_create_custom_role_with_reserved_names(): void
    {
        $org = Organization::factory()->create();
        $owner = User::factory()->create();
        $this->createOrganizationOwner($org, $owner);

        foreach (['Owner', 'OWNER', 'Admin', 'ADMIN', 'Member', 'MEMBER', 'owner', 'admin', 'member'] as $reserved) {
            $response = $this->actingAs($owner)->postJson("/api/v1/organizations/{$org->id}/roles", [
                'name' => $reserved,
            ]);
            $response->assertUnprocessable();
        }
    }

    public function test_cannot_create_duplicate_custom_role_name_in_same_organization(): void
    {
        $org = Organization::factory()->create();
        $owner = User::factory()->create();
        $this->createOrganizationOwner($org, $owner);

        $this->actingAs($owner)->postJson("/api/v1/organizations/{$org->id}/roles", [
            'name' => 'Custom Role',
        ])->assertCreated();

        $this->actingAs($owner)->postJson("/api/v1/organizations/{$org->id}/roles", [
            'name' => 'Custom Role',
        ])->assertUnprocessable();
    }

    public function test_same_custom_role_name_allowed_across_different_organizations(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();
        $this->createOrganizationOwner($orgA, $ownerA);
        $this->createOrganizationOwner($orgB, $ownerB);

        $this->actingAs($ownerA)->postJson("/api/v1/organizations/{$orgA->id}/roles", [
            'name' => 'Custom Role',
        ])->assertCreated();

        $this->actingAs($ownerB)->postJson("/api/v1/organizations/{$orgB->id}/roles", [
            'name' => 'Custom Role',
        ])->assertCreated();
    }

    // --- 3. Role Update & System Role Protection ---

    public function test_owner_can_update_custom_role(): void
    {
        $org = Organization::factory()->create();
        $owner = User::factory()->create();
        $this->createOrganizationOwner($org, $owner);

        $perm1 = Permission::firstOrCreate(['key' => PermissionKey::BranchView->value]);
        $perm2 = Permission::firstOrCreate(['key' => PermissionKey::BranchCreate->value]);

        $createRes = $this->actingAs($owner)->postJson("/api/v1/organizations/{$org->id}/roles", [
            'name' => 'Custom Role',
            'permission_ids' => [$perm1->id],
        ])->assertCreated();

        $roleId = $createRes->json('data.id');

        $this->actingAs($owner)->patchJson("/api/v1/organizations/{$org->id}/roles/{$roleId}", [
            'name' => 'Updated Custom Role',
            'description' => 'Updated description',
            'permission_ids' => [$perm1->id, $perm2->id],
        ])->assertOk()
            ->assertJsonPath('data.name', 'Updated Custom Role')
            ->assertJsonPath('data.slug', 'updated-custom-role')
            ->assertJsonPath('data.description', 'Updated description')
            ->assertJsonCount(2, 'data.permissions');
    }

    public function test_admin_and_member_cannot_update_custom_role(): void
    {
        $org = Organization::factory()->create();
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $this->createOrganizationOwner($org, $owner);
        $this->createOrganizationAdmin($org, $admin);
        $this->createOrganizationMember($org, $member);

        $createRes = $this->actingAs($owner)->postJson("/api/v1/organizations/{$org->id}/roles", [
            'name' => 'Custom Role',
        ])->assertCreated();

        $roleId = $createRes->json('data.id');

        $this->actingAs($admin)->patchJson("/api/v1/organizations/{$org->id}/roles/{$roleId}", [
            'name' => 'Renamed',
        ])->assertForbidden();

        $this->actingAs($member)->patchJson("/api/v1/organizations/{$org->id}/roles/{$roleId}", [
            'name' => 'Renamed',
        ])->assertForbidden();
    }

    public function test_built_in_system_roles_cannot_be_updated(): void
    {
        $org = Organization::factory()->create();
        $owner = User::factory()->create();
        $this->createOrganizationOwner($org, $owner);

        $roles = $org->roles()->get();
        $this->assertNotEmpty($roles);

        foreach ($roles as $role) {
            $this->actingAs($owner)->patchJson("/api/v1/organizations/{$org->id}/roles/{$role->id}", [
                'name' => 'Mutated Role',
            ])->assertUnprocessable();
        }
    }

    public function test_cannot_rename_custom_role_to_reserved_system_name(): void
    {
        $org = Organization::factory()->create();
        $owner = User::factory()->create();
        $this->createOrganizationOwner($org, $owner);

        $role = Role::create([
            'organization_id' => $org->id,
            'name' => 'Old Name',
            'slug' => 'old-name',
            'is_system' => false,
        ]);

        foreach (['Owner', 'OWNER', 'Admin', 'ADMIN', 'Member', 'MEMBER'] as $reserved) {
            $this->actingAs($owner)->patchJson("/api/v1/organizations/{$org->id}/roles/{$role->id}", [
                'name' => $reserved,
            ])->assertUnprocessable();
        }
    }

    public function test_cross_tenant_role_update_fails(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $ownerA = User::factory()->create();
        $this->createOrganizationOwner($orgA, $ownerA);

        $roleB = Role::create([
            'organization_id' => $orgB->id,
            'name' => 'Org B Role',
            'slug' => 'org-b-role',
            'is_system' => false,
        ]);

        // Attempting to update roleB via orgA route must return 404
        $this->actingAs($ownerA)->patchJson("/api/v1/organizations/{$orgA->id}/roles/{$roleB->id}", [
            'name' => 'Hijacked',
        ])->assertNotFound();
    }

    // --- 4. Role Deletion ---

    public function test_owner_can_delete_unassigned_custom_role(): void
    {
        $org = Organization::factory()->create();
        $owner = User::factory()->create();
        $this->createOrganizationOwner($org, $owner);

        $role = Role::create([
            'organization_id' => $org->id,
            'name' => 'Temporary Role',
            'slug' => 'temporary-role',
            'is_system' => false,
        ]);

        $this->actingAs($owner)->deleteJson("/api/v1/organizations/{$org->id}/roles/{$role->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_cannot_delete_system_roles(): void
    {
        $org = Organization::factory()->create();
        $owner = User::factory()->create();
        $this->createOrganizationOwner($org, $owner);

        foreach ($org->roles()->where('is_system', true)->get() as $systemRole) {
            $this->actingAs($owner)->deleteJson("/api/v1/organizations/{$org->id}/roles/{$systemRole->id}")
                ->assertUnprocessable();

            $this->assertDatabaseHas('roles', ['id' => $systemRole->id]);
        }
    }

    public function test_cannot_delete_role_assigned_to_memberships(): void
    {
        $org = Organization::factory()->create();
        $owner = User::factory()->create();
        $this->createOrganizationOwner($org, $owner);

        $role = Role::create([
            'organization_id' => $org->id,
            'name' => 'Assigned Role',
            'slug' => 'assigned-role',
            'is_system' => false,
        ]);

        $user = User::factory()->create();
        $membership = $this->createOrganizationMember($org, $user);
        $membership->roles()->attach($role->id);

        $this->actingAs($owner)->deleteJson("/api/v1/organizations/{$org->id}/roles/{$role->id}")
            ->assertUnprocessable();

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_cross_tenant_role_deletion_fails(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $ownerA = User::factory()->create();
        $this->createOrganizationOwner($orgA, $ownerA);

        $roleB = Role::create([
            'organization_id' => $orgB->id,
            'name' => 'Role B',
            'slug' => 'role-b',
            'is_system' => false,
        ]);

        $this->actingAs($ownerA)->deleteJson("/api/v1/organizations/{$orgA->id}/roles/{$roleB->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('roles', ['id' => $roleB->id]);
    }

    // --- 5. Membership Role Assignment ---

    public function test_owner_can_assign_roles_to_membership(): void
    {
        $org = Organization::factory()->create();
        $owner = User::factory()->create();
        $this->createOrganizationOwner($org, $owner);

        $targetUser = User::factory()->create();
        $targetMembership = $this->createOrganizationMember($org, $targetUser);

        $adminRole = $org->roles()->where('slug', 'admin')->firstOrFail();

        $response = $this->actingAs($owner)->putJson("/api/v1/organizations/{$org->id}/memberships/{$targetMembership->id}/roles", [
            'role_ids' => [$adminRole->id],
        ]);

        $response->assertOk();
        $this->assertTrue($targetMembership->fresh()->roles()->where('slug', 'admin')->exists());
        $this->assertFalse($targetMembership->fresh()->roles()->where('slug', 'member')->exists());
    }

    public function test_admin_and_member_cannot_assign_roles(): void
    {
        $org = Organization::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $this->createOrganizationAdmin($org, $admin);
        $targetMembership = $this->createOrganizationMember($org, $member);

        $ownerRole = $org->roles()->where('slug', 'owner')->firstOrFail();

        // Admin attempts privilege escalation / role assignment
        $this->actingAs($admin)->putJson("/api/v1/organizations/{$org->id}/memberships/{$targetMembership->id}/roles", [
            'role_ids' => [$ownerRole->id],
        ])->assertForbidden();

        // Member attempts role assignment
        $this->actingAs($member)->putJson("/api/v1/organizations/{$org->id}/memberships/{$targetMembership->id}/roles", [
            'role_ids' => [$ownerRole->id],
        ])->assertForbidden();
    }

    public function test_cannot_assign_role_from_different_organization(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $ownerA = User::factory()->create();
        $this->createOrganizationOwner($orgA, $ownerA);

        $targetUser = User::factory()->create();
        $membershipA = $this->createOrganizationMember($orgA, $targetUser);

        $rolesB = app(AuthorizationService::class)->bootstrap($orgB);
        $roleFromB = $rolesB['admin'];

        $this->actingAs($ownerA)->putJson("/api/v1/organizations/{$orgA->id}/memberships/{$membershipA->id}/roles", [
            'role_ids' => [$roleFromB->id],
        ])->assertUnprocessable();
    }

    public function test_cross_tenant_role_assignment_fails(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $ownerA = User::factory()->create();
        $this->createOrganizationOwner($orgA, $ownerA);

        $userB = User::factory()->create();
        $membershipB = $this->createOrganizationMember($orgB, $userB);

        $adminRoleA = $orgA->roles()->where('slug', 'admin')->firstOrFail();

        // Attempting to modify membershipB via orgA
        $this->actingAs($ownerA)->putJson("/api/v1/organizations/{$orgA->id}/memberships/{$membershipB->id}/roles", [
            'role_ids' => [$adminRoleA->id],
        ])->assertNotFound();
    }

    // --- 6. Owner Escalation & Last Owner Safety ---

    public function test_sole_active_owner_cannot_demote_themselves(): void
    {
        $org = Organization::factory()->create();
        $owner = User::factory()->create();
        $ownerMembership = $this->createOrganizationOwner($org, $owner);

        $adminRole = $org->roles()->where('slug', 'admin')->firstOrFail();

        // Sole owner tries to replace their role with admin
        $this->actingAs($owner)->putJson("/api/v1/organizations/{$org->id}/memberships/{$ownerMembership->id}/roles", [
            'role_ids' => [$adminRole->id],
        ])->assertUnprocessable();

        $this->assertTrue($ownerMembership->fresh()->roles()->where('slug', 'owner')->exists());
    }

    public function test_owner_can_demote_another_owner_if_multiple_owners_exist(): void
    {
        $org = Organization::factory()->create();
        $owner1 = User::factory()->create();
        $owner2 = User::factory()->create();
        $this->createOrganizationOwner($org, $owner1);
        $ownerMembership2 = $this->createOrganizationOwner($org, $owner2);

        $adminRole = $org->roles()->where('slug', 'admin')->firstOrFail();

        // owner1 demotes owner2 to admin
        $this->actingAs($owner1)->putJson("/api/v1/organizations/{$org->id}/memberships/{$ownerMembership2->id}/roles", [
            'role_ids' => [$adminRole->id],
        ])->assertOk();

        $this->assertFalse($ownerMembership2->fresh()->roles()->where('slug', 'owner')->exists());
        $this->assertTrue($ownerMembership2->fresh()->roles()->where('slug', 'admin')->exists());
    }

    // --- 7. Multiple Roles & Permission Union ---

    public function test_multiple_roles_union_permissions(): void
    {
        $org = Organization::factory()->create();
        $owner = User::factory()->create();
        $this->createOrganizationOwner($org, $owner);

        $perm1 = Permission::firstOrCreate(['key' => PermissionKey::BranchCreate->value]);
        $perm2 = Permission::firstOrCreate(['key' => PermissionKey::MembershipInvite->value]);

        $role1 = Role::create(['organization_id' => $org->id, 'name' => 'Branch Manager', 'slug' => 'branch-manager', 'is_system' => false]);
        $role1->permissions()->sync([$perm1->id]);

        $role2 = Role::create(['organization_id' => $org->id, 'name' => 'Staff Recruiter', 'slug' => 'staff-recruiter', 'is_system' => false]);
        $role2->permissions()->sync([$perm2->id]);

        $user = User::factory()->create();
        $membership = $this->createOrganizationMember($org, $user);

        // Assign both custom roles to membership
        $this->actingAs($owner)->putJson("/api/v1/organizations/{$org->id}/memberships/{$membership->id}/roles", [
            'role_ids' => [$role1->id, $role2->id],
        ])->assertOk();

        $authService = app(AuthorizationService::class);
        $this->assertTrue($authService->allows($user, $org, PermissionKey::BranchCreate->value));
        $this->assertTrue($authService->allows($user, $org, PermissionKey::MembershipInvite->value));
        $this->assertFalse($authService->allows($user, $org, PermissionKey::BranchUpdate->value));

        // Effective permissions endpoint returns union
        $effectiveRes = $this->actingAs($user)->getJson("/api/v1/organizations/{$org->id}/me/permissions");
        $effectiveRes->assertOk();
        $perms = $effectiveRes->json('data.permissions');
        $this->assertContains(PermissionKey::BranchCreate->value, $perms);
        $this->assertContains(PermissionKey::MembershipInvite->value, $perms);
    }

    public function test_removing_one_role_preserves_other_role_permissions(): void
    {
        $org = Organization::factory()->create();
        $owner = User::factory()->create();
        $this->createOrganizationOwner($org, $owner);

        $perm1 = Permission::firstOrCreate(['key' => PermissionKey::BranchCreate->value]);
        $perm2 = Permission::firstOrCreate(['key' => PermissionKey::MembershipInvite->value]);

        $role1 = Role::create(['organization_id' => $org->id, 'name' => 'Branch Manager', 'slug' => 'branch-manager', 'is_system' => false]);
        $role1->permissions()->sync([$perm1->id]);

        $role2 = Role::create(['organization_id' => $org->id, 'name' => 'Staff Recruiter', 'slug' => 'staff-recruiter', 'is_system' => false]);
        $role2->permissions()->sync([$perm2->id]);

        $user = User::factory()->create();
        $membership = $this->createOrganizationMember($org, $user);
        $membership->roles()->sync([$role1->id, $role2->id]);

        // Remove role1, keep only role2
        $this->actingAs($owner)->putJson("/api/v1/organizations/{$org->id}/memberships/{$membership->id}/roles", [
            'role_ids' => [$role2->id],
        ])->assertOk();

        $authService = app(AuthorizationService::class);
        $this->assertFalse($authService->allows($user, $org, PermissionKey::BranchCreate->value));
        $this->assertTrue($authService->allows($user, $org, PermissionKey::MembershipInvite->value));
    }

    // --- 8. Transaction Safety & Rollback ---

    public function test_role_creation_rolls_back_if_permission_sync_fails(): void
    {
        $org = Organization::factory()->create();
        $owner = User::factory()->create();
        $this->createOrganizationOwner($org, $owner);

        $nonExistentUuid = '018f0000-0000-7000-8000-000000000000';

        // Validation rejects nonexistent permission UUID before creating role
        $this->actingAs($owner)->postJson("/api/v1/organizations/{$org->id}/roles", [
            'name' => 'Rollback Role',
            'permission_ids' => [$nonExistentUuid],
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('roles', ['slug' => 'rollback-role']);
    }
}
