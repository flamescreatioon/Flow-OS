<?php

namespace Tests\Unit\Models;

use App\Enums\MembershipStatus;
use App\Models\OrganizationMembership;
use Tests\TestCase;

class OrganizationMembershipTest extends TestCase
{
    public function test_membership_belongs_to_user_and_organization(): void
    {
        $membership = OrganizationMembership::factory()->active()->create();

        $this->assertSame(MembershipStatus::Active, $membership->status);
        $this->assertSame($membership->user_id, $membership->user->id);
        $this->assertSame($membership->organization_id, $membership->organization->id);
    }
}
