<?php

namespace Tests\Unit\Tenancy;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Tenancy\TenantContext;
use LogicException;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    public function test_context_cannot_exist_without_valid_active_membership(): void
    {
        $context = app(TenantContext::class);
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        try {
            $context->establish($user, $organization);
            $this->fail('Expected invalid tenant context establishment to fail.');
        } catch (LogicException) {
            $this->assertFalse($context->hasOrganization());
        }

        OrganizationMembership::factory()->for($user)->for($organization)->active()->create();
        $context->establish($user, $organization);

        $this->assertTrue($context->hasOrganization());
        $this->assertTrue($context->organization()->is($organization));
    }
}
