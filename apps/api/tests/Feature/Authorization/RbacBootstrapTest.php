<?php

namespace Tests\Feature\Authorization;

use App\Application\Authorization\AuthorizationService;
use App\Application\Organizations\OrganizationLifecycleService;
use App\Enums\PermissionKey;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Tests\TestCase;

class RbacBootstrapTest extends TestCase
{
    public function test_creator_is_owner_and_owner_has_current_catalogue(): void
    {
        $user = User::factory()->create();
        $org = app(OrganizationLifecycleService::class)->create($user, ['name' => 'RBAC Test']);
        $membership = $org->memberships()->where('user_id', $user->id)->first();
        $this->assertTrue($membership->roles()->where('slug', 'owner')->exists());
        $this->assertTrue(app(AuthorizationService::class)->allows($user, $org, PermissionKey::RoleAssign->value));
    }

    public function test_backfill_is_idempotent(): void
    {
        $org = Organization::factory()->create();
        $first = OrganizationMembership::factory()->for(User::factory())->for($org)->active()->create();
        $second = OrganizationMembership::factory()->for(User::factory())->for($org)->active()->create();
        $service = app(AuthorizationService::class);
        $service->backfill();
        $service->backfill();
        $this->assertSame(3, $org->roles()->count());
        $this->assertTrue($first->fresh()->roles()->exists());
        $this->assertTrue($second->fresh()->roles()->exists());
    }
}
