<?php

namespace Tests\Unit\Models;

use App\Enums\OrganizationStatus;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    public function test_organization_has_branches_and_memberships(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $branch = Branch::factory()->for($organization)->create();
        OrganizationMembership::factory()->for($user)->for($organization)->create();

        $this->assertSame(OrganizationStatus::Active, $organization->status);
        $this->assertTrue($organization->branches->contains($branch));
        $this->assertTrue($organization->memberships->contains('user_id', $user->id));
        $this->assertSame('Africa/Lagos', $organization->timezone);
        $this->assertSame('NGN', $organization->currency);
    }
}
