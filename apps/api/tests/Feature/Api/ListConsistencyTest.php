<?php

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\WithRbac;
use Tests\TestCase;

class ListConsistencyTest extends TestCase
{
    use WithRbac;

    public function test_organization_listing_is_paginated_with_bounded_per_page(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/organizations', ['name' => "Org {$i}"])->assertCreated();
        }

        $response = $this->getJson('/api/v1/organizations?per_page=2');
        $response->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);
        $this->assertCount(2, $response->json('data'));

        $this->getJson('/api/v1/organizations?per_page=500')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_organization_listing_paginates_beyond_a_single_page(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        for ($i = 0; $i < 26; $i++) {
            $this->postJson('/api/v1/organizations', ['name' => "Paged Org {$i}"])->assertCreated();
        }

        $firstPage = $this->getJson('/api/v1/organizations');
        $firstPage->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links'])
            ->assertJsonPath('meta.total', 26)
            ->assertJsonPath('meta.per_page', 25)
            ->assertJsonPath('meta.current_page', 1);
        $this->assertCount(25, $firstPage->json('data'));

        $secondPage = $this->getJson('/api/v1/organizations?page=2');
        $secondPage->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links'])
            ->assertJsonPath('meta.total', 26)
            ->assertJsonPath('meta.current_page', 2);
        $this->assertCount(1, $secondPage->json('data'));
    }

    public function test_search_filters_are_case_insensitive_on_every_database(): void
    {
        $user = User::factory()->create(['name' => 'Ada Lovelace']);
        $organization = Organization::factory()->create(['name' => 'Lagos Trading', 'slug' => 'lagos-trading']);
        $this->createOrganizationOwner($organization, $user);
        Branch::factory()->for($organization)->create(['name' => 'Lagos Main', 'code' => 'LOS', 'city' => 'Lagos']);
        Sanctum::actingAs($user);

        foreach (['lagos', 'LAGOS', 'LaGoS'] as $term) {
            $this->withHeaders(['X-Organization-ID' => $organization->id])
                ->getJson("/api/v1/organizations/{$organization->id}/branches?q={$term}")
                ->assertOk()
                ->assertJsonCount(1, 'data');

            $this->withHeaders(['X-Organization-ID' => $organization->id])
                ->getJson("/api/v1/organizations/{$organization->id}/branches?city={$term}")
                ->assertOk()
                ->assertJsonCount(1, 'data');

            $this->getJson("/api/v1/organizations?q={$term}")
                ->assertOk()
                ->assertJsonCount(1, 'data');
        }

        $this->getJson("/api/v1/organizations/{$organization->id}/memberships?q=ada")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson("/api/v1/organizations/{$organization->id}/memberships?q=ADA")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_organization_listing_supports_status_filter_and_search(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $alpha = $this->postJson('/api/v1/organizations', ['name' => 'Alpha Traders'])->json('data');
        $beta = $this->postJson('/api/v1/organizations', ['name' => 'Beta Stores'])->json('data');

        $this->postJson("/api/v1/organizations/{$beta['id']}/suspend")->assertOk();

        $suspended = $this->getJson('/api/v1/organizations?status=suspended')->json('data');
        $this->assertCount(1, $suspended);
        $this->assertSame($beta['id'], $suspended[0]['id']);

        $search = $this->getJson('/api/v1/organizations?q=alpha')->json('data');
        $this->assertCount(1, $search);
        $this->assertSame($alpha['id'], $search[0]['id']);
    }

    public function test_branch_listing_is_paginated_and_filterable(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $this->createOrganizationMember($organization, $user);
        Branch::factory()->for($organization)->create(['name' => 'Lagos Main', 'city' => 'Lagos']);
        Branch::factory()->for($organization)->create(['name' => 'Abuja Point', 'city' => 'Abuja']);
        Sanctum::actingAs($user);

        $response = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->getJson("/api/v1/organizations/{$organization->id}/branches?q=lagos");
        $response->assertOk()->assertJsonStructure(['data', 'meta']);
        $this->assertCount(1, $response->json('data'));

        $city = $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->getJson("/api/v1/organizations/{$organization->id}/branches?city=abuja");
        $city->assertOk();
        $this->assertCount(1, $city->json('data'));

        $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->getJson("/api/v1/organizations/{$organization->id}/branches?per_page=500")
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_member_and_role_listings_are_paginated(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $this->createOrganizationOwner($organization, $user);
        OrganizationMembership::factory()->for(Organization::factory())->create();
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/organizations/{$organization->id}/memberships?per_page=1")
            ->assertOk()->assertJsonStructure(['data', 'meta']);

        $this->getJson("/api/v1/organizations/{$organization->id}/roles?q=adm")
            ->assertOk()->assertJsonStructure(['data', 'meta']);
    }

    public function test_tenant_errors_use_envelope_codes(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        Sanctum::actingAs($user);

        $this->withHeaders(['X-Organization-ID' => $organization->id])
            ->getJson("/api/v1/organizations/{$organization->id}/branches")
            ->assertForbidden()
            ->assertJsonPath('error.code', 'TENANT_ACCESS_DENIED');

        // withHeaders() persists for the remainder of the test, so the
        // organization selector must be cleared before asserting the
        // no-tenant-context path.
        $this->flushHeaders();

        $this->getJson("/api/v1/organizations/{$organization->id}/branches")
            ->assertBadRequest()
            ->assertJsonPath('error.code', 'TENANT_REQUIRED');
    }
}
