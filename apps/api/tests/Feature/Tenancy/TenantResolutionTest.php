<?php

namespace Tests\Feature\Tenancy;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Laravel\Sanctum\Sanctum;
use Spatie\Multitenancy\Models\Tenant;
use Tests\TestCase;

class TenantResolutionTest extends TestCase
{
    /** @test */
    public function active_member_can_establish_tenant_context_via_subdomain()
    {
        $org = Organization::factory()->create([
            'slug' => 'test-org',
        ]);
        $user = User::factory()->for($org)->create();
        $this->membership($user, $org);
        Sanctum::actingAs($user);

        $response = $this->get('http://test-org.flowos.local/api/v1/test-tenant-context');

        $response->assertOk()->assertJson(['organization_id' => $org->id]);
        $this->assertNull(Tenant::current());
    }

    /** @test */
    public function active_member_can_establish_tenant_context_via_header()
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();
        $this->membership($user, $org);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $org->id])
            ->get('/api/v1/test-tenant-context');

        $response->assertOk()->assertJson(['organization_id' => $org->id]);
        $this->assertNull(Tenant::current());
    }

    /** @test */
    public function header_alone_cannot_bypass_membership()
    {
        $allowedOrganization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $user = User::factory()->for($allowedOrganization)->create();
        $this->membership($user, $allowedOrganization);
        Sanctum::actingAs($user);

        $this->withHeaders(['X-Organization-ID' => $otherOrganization->id])
            ->get('/api/v1/test-tenant-context')
            ->assertForbidden();
        $this->assertNull(Tenant::current());
    }

    public function test_unauthenticated_request_cannot_enter_a_tenant_route(): void
    {
        $organization = Organization::factory()->create();

        $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->get('/api/v1/test-tenant-context')
            ->assertUnauthorized();

        $this->assertNull(Tenant::current());
    }

    public function test_inactive_membership_cannot_establish_tenant_context(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();
        $this->membership($user, $organization, 'suspended');
        Sanctum::actingAs($user);

        $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->get('/api/v1/test-tenant-context')
            ->assertForbidden();

        $this->assertNull(Tenant::current());
    }

    public function test_tenant_context_is_cleared_when_downstream_code_throws(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();
        $this->membership($user, $organization);
        Sanctum::actingAs($user);

        $threw = false;

        try {
            $this->withoutExceptionHandling()
                ->withHeaders(['X-Organization-ID' => $organization->id])
                ->get('/api/v1/test-tenant-exception');
        } catch (\RuntimeException $exception) {
            $threw = true;
            $this->assertSame('Test downstream failure.', $exception->getMessage());
        }

        $this->assertTrue($threw);
        $this->assertNull(Tenant::current());
    }

    /** @test */
    public function subdomain_alone_cannot_bypass_membership()
    {
        $allowedOrganization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create(['slug' => 'other-org']);
        $user = User::factory()->for($allowedOrganization)->create();
        $this->membership($user, $allowedOrganization);
        Sanctum::actingAs($user);

        $this->assertNull(Tenant::current());
        $this->get('http://other-org.flowos.local/api/v1/test-tenant-context')
            ->assertForbidden();
        $this->assertNull(Tenant::current());
        $this->assertNull(Tenant::current());
    }

    /** @test */
    public function health_endpoint_accessible_without_tenant_context()
    {
        $response = $this->get('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'service' => 'flowos-api',
            ]);
    }

    public function test_health_endpoint_does_not_use_automatic_tenant_finding(): void
    {
        $this->assertNull(config('multitenancy.tenant_finder'));

        $response = $this->withHeaders([
            'X-Organization-ID' => '00000000-0000-0000-0000-000000000000',
            'Host' => 'unknown.flowos.local',
        ])->get('/api/v1/health');

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'service' => 'flowos-api',
            ]);
        $this->assertNull(Tenant::current());
    }

    public function test_api_group_uses_sanctum_stateful_request_protection(): void
    {
        $this->assertContains(
            EnsureFrontendRequestsAreStateful::class,
            app('router')->getMiddlewareGroups()['api'],
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth:sanctum', 'tenant'])->get('/api/v1/test-tenant-context', function () {
            return response()->json([
                'organization_id' => Tenant::current()?->id,
            ]);
        });

        Route::middleware(['auth:sanctum', 'tenant'])->get('/api/v1/test-tenant-exception', function () {
            throw new \RuntimeException('Test downstream failure.');
        });
    }

    private function membership(User $user, Organization $organization, string $status = 'active'): OrganizationMembership
    {
        $membership = new OrganizationMembership;
        $membership->forceFill([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'viewer',
            'status' => $status,
            'joined_at' => now(),
        ]);
        $membership->save();

        return $membership;
    }
}
