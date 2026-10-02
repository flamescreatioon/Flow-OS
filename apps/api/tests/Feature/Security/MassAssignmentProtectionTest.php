<?php

namespace Tests\Feature\Security;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Tests\TestCase;

class MassAssignmentProtectionTest extends TestCase
{
    public function test_security_sensitive_model_attributes_are_not_mass_assignable(): void
    {
        $user = new User;
        $user->fill([
            'name' => 'Allowed Name',
            'organization_id' => 'organization-id',
            'role' => 'owner',
            'is_active' => false,
        ]);

        $organization = new Organization;
        $organization->fill(['id' => 'organization-id', 'name' => 'Allowed Organization']);

        $branch = new Branch;
        $branch->fill(['organization_id' => 'organization-id', 'name' => 'Allowed Branch']);

        $this->assertSame('Allowed Name', $user->name);
        $this->assertNull($user->organization_id);
        $this->assertNull($user->role);
        $this->assertNull($user->is_active);
        $this->assertSame('Allowed Organization', $organization->name);
        $this->assertNull($organization->id);
        $this->assertSame('Allowed Branch', $branch->name);
        $this->assertNull($branch->organization_id);
    }

    public function test_membership_rejects_mass_assignment_entirely(): void
    {
        $membership = new OrganizationMembership;

        $this->expectException(MassAssignmentException::class);

        $membership->fill([
            'user_id' => 'user-id',
            'organization_id' => 'organization-id',
            'role' => 'owner',
            'status' => 'active',
        ]);
    }
}
