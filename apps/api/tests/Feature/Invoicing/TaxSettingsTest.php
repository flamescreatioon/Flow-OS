<?php

namespace Tests\Feature\Invoicing;

use App\Models\Organization;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\WithRbac;
use Tests\TestCase;

class TaxSettingsTest extends TestCase
{
    use WithRbac;

    public function test_reading_returns_the_defaults_when_nothing_is_configured(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationAdmin($organization, $user);
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/organizations/{$organization->id}/tax-settings")
            ->assertOk()
            ->assertJsonPath('data.tax_enabled', false)
            ->assertJsonPath('data.tax_rate_percent', null)
            ->assertJsonPath('data.tax_mode', 'exclusive');

        // Reading never persists a row.
        $this->assertDatabaseCount('tax_settings', 0);
    }

    public function test_an_admin_enables_vat_exclusively(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationAdmin($organization, $user);
        Sanctum::actingAs($user);

        $this->patchJson("/api/v1/organizations/{$organization->id}/tax-settings", [
            'tax_enabled' => true,
            'tax_rate_percent' => '7.5',
            'tax_mode' => 'exclusive',
        ])->assertOk()
            ->assertJsonPath('data.tax_enabled', true)
            ->assertJsonPath('data.tax_rate_percent', '7.5000')
            ->assertJsonPath('data.tax_mode', 'exclusive');

        $this->assertDatabaseHas('tax_settings', [
            'organization_id' => $organization->id,
            'tax_enabled' => true,
            'tax_rate_percent' => 75000,
            'tax_mode' => 'exclusive',
        ]);
    }

    public function test_enabling_tax_without_a_rate_is_rejected(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationAdmin($organization, $user);
        Sanctum::actingAs($user);

        $this->patchJson("/api/v1/organizations/{$organization->id}/tax-settings", [
            'tax_enabled' => true,
        ])->assertUnprocessable();

        $this->assertDatabaseCount('tax_settings', 0);
    }

    public function test_a_member_cannot_change_tax_settings(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationAdmin($organization, $admin);
        $member = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationMember($organization, $member);
        Sanctum::actingAs($member);

        // A member can read but the manage permission is required to write.
        $this->getJson("/api/v1/organizations/{$organization->id}/tax-settings")->assertOk();

        $this->patchJson("/api/v1/organizations/{$organization->id}/tax-settings", [
            'tax_enabled' => true,
            'tax_rate_percent' => '7.5',
        ])->assertForbidden();

        $this->assertDatabaseCount('tax_settings', 0);
    }
}
