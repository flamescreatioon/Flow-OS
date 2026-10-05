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
        $user = User::factory()->create();
        $organizations = Organization::factory()->count(2)->create();

        foreach ($organizations as $organization) {
            OrganizationMembership::factory()->for($user)->for($organization)->active()->create();
        }

        $this->assertTrue($user->hasActiveMembershipIn($organizations->first()));
        $this->assertTrue($user->hasActiveMembershipIn($organizations->last()));
        $this->assertCount(2, $user->memberships()->get());
    }

    public function test_duplicate_membership_is_prevented(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organization)->create();

        $this->expectException(QueryException::class);
        OrganizationMembership::factory()->for($user)->for($organization)->create();
    }
}
