<?php

namespace Tests\Feature\Tenancy;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Spatie\Multitenancy\Models\Tenant;
use Tests\TestCase;

class TenantResolutionTest extends TestCase
{
    public function test_active_member_can_establish_tenant_context_via_header(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organization)->active()->create();
        Sanctum::actingAs($user);

        $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->get('/api/v1/test-tenant-context')
            ->assertOk()
            ->assertJson(['organization_id' => $organization->id]);

        $this->assertNull(Tenant::current());
        $this->assertFalse(app(TenantContext::class)->hasOrganization());
    }

    public function test_header_alone_cannot_bypass_membership(): void
    {
        $allowedOrganization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $user = User::factory()->create();
        OrganizationMembership::factory()->for($user)->for($allowedOrganization)->active()->create();
        Sanctum::actingAs($user);

        $this->withHeaders(['X-Organization-ID' => $otherOrganization->id])
            ->get('/api/v1/test-tenant-context')
            ->assertForbidden();

        $this->assertNull(Tenant::current());
        $this->assertFalse(app(TenantContext::class)->hasOrganization());
    }

    public function test_suspended_and_revoked_memberships_cannot_establish_tenant_context(): void
    {
        foreach (['suspended', 'revoked'] as $state) {
            $organization = Organization::factory()->create();
            $user = User::factory()->create();
            OrganizationMembership::factory()->for($user)->for($organization)->{$state}()->create();
            Sanctum::actingAs($user);

            $this->withHeaders(['X-Organization-ID' => $organization->id])
                ->get('/api/v1/test-tenant-context')
                ->assertForbidden();

            $this->assertNull(Tenant::current());
            $this->assertFalse(app(TenantContext::class)->hasOrganization());
        }
    }

    public function test_inactive_organization_cannot_establish_tenant_context(): void
    {
        $organization = Organization::factory()->create(['status' => OrganizationStatus::Suspended]);
        $user = User::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organization)->active()->create();
        Sanctum::actingAs($user);

        $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->get('/api/v1/test-tenant-context')
            ->assertForbidden();

        $this->assertFalse(app(TenantContext::class)->hasOrganization());
    }

    public function test_unauthenticated_request_cannot_enter_a_tenant_route(): void
    {
        $organization = Organization::factory()->create();

        $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->get('/api/v1/test-tenant-context')
            ->assertUnauthorized();
    }

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth:sanctum', 'tenant'])->get('/api/v1/test-tenant-context', function () {
            return response()->json([
                'organization_id' => app(TenantContext::class)->organization()?->id,
            ]);
        });
    }
}
