<?php

namespace Tests\Feature\Identity;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class OrganizationMembershipTest extends TestCase
{
    public function test_user_can_belong_to_multiple_organizations(): void
    {
        $primaryOrganization = Organization::factory()->create();
        $additionalOrganization = Organization::factory()->create();
        $user = User::factory()->for($primaryOrganization)->create();

        $this->membership($user, $primaryOrganization, 'owner');
        $this->membership($user, $additionalOrganization, 'manager');

        $this->assertTrue($user->hasActiveMembershipIn($primaryOrganization));
        $this->assertTrue($user->hasActiveMembershipIn($additionalOrganization));
        $this->assertCount(2, $user->memberships()->get());
    }

    public function test_duplicate_membership_is_prevented(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();

        $this->membership($user, $organization);

        $this->expectException(QueryException::class);
        $this->membership($user, $organization);
    }

    public function test_suspended_or_revoked_membership_is_not_active(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();

        $this->membership($user, $organization, status: 'suspended');

        $this->assertFalse($user->hasActiveMembershipIn($organization));

        $user->memberships()->update(['status' => 'revoked']);

        $this->assertFalse($user->hasActiveMembershipIn($organization));
    }

    private function membership(
        User $user,
        Organization $organization,
        string $role = 'viewer',
        string $status = 'active',
    ): OrganizationMembership {
        return OrganizationMembership::query()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => $role,
            'status' => $status,
            'joined_at' => now(),
        ]);
    }
}
