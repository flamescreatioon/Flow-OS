<?php

namespace Tests\Feature\Tenancy;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganizationIsolationTest extends TestCase
{
    public function test_organization_a_user_cannot_access_organization_b_resources(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $user = User::factory()->create();
        OrganizationMembership::factory()->for($user)->for($organizationA)->active()->create();
        Branch::factory()->for($organizationB)->create();
        Sanctum::actingAs($user);

        $this->withHeaders(['X-Organization-ID' => $organizationB->id])
            ->get('/api/v1/test-branches')
            ->assertForbidden();

        $this->withHeaders(['X-Organization-ID' => $organizationA->id])
            ->get('/api/v1/test-branches')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth:sanctum', 'tenant'])->get('/api/v1/test-branches', function () {
            return response()->json([
                'data' => Branch::query()
                    ->where('organization_id', app(TenantContext::class)->organization()?->id)
                    ->get(),
            ]);
        });
    }
}
