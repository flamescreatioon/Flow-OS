<?php

namespace Tests\Unit\Models;

use App\Enums\UserStatus;
use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_user_is_a_platform_identity_without_tenant_or_role_attributes(): void
    {
        $user = User::factory()->create();

        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertTrue(method_exists($user, 'memberships'));
        $this->assertFalse(array_key_exists('organization_id', $user->getAttributes()));
        $this->assertFalse(array_key_exists('role', $user->getAttributes()));
    }
}
