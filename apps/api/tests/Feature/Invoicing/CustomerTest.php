<?php

namespace Tests\Feature\Invoicing;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\WithRbac;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use WithRbac;

    /**
     * @return array{0: Organization, 1: User}
     */
    protected function customerContext(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationAdmin($organization, $user);
        Sanctum::actingAs($user);

        return [$organization, $user];
    }

    public function test_an_admin_creates_a_customer(): void
    {
        [$organization] = $this->customerContext();

        $this->postJson("/api/v1/organizations/{$organization->id}/customers", [
            'name' => 'Abuja Retail Group',
            'email' => 'orders@abujaretail.example',
            'phone' => '0803 111 2222',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Abuja Retail Group');

        $this->assertDatabaseHas('customers', [
            'organization_id' => $organization->id,
            'name' => 'Abuja Retail Group',
        ]);
    }

    public function test_a_customer_is_updated_and_archived_but_never_deleted(): void
    {
        [$organization] = $this->customerContext();
        $customer = Customer::factory()->for($organization)->create();

        $this->patchJson("/api/v1/organizations/{$organization->id}/customers/{$customer->id}", [
            'phone' => '0803 111 3333',
        ])->assertOk()
            ->assertJsonPath('data.phone', '0803 111 3333');

        $this->postJson("/api/v1/organizations/{$organization->id}/customers/{$customer->id}/archive")
            ->assertOk()
            ->assertJsonPath('data.archived_at', fn ($value) => $value !== null);

        $this->deleteJson("/api/v1/organizations/{$organization->id}/customers/{$customer->id}")
            ->assertMethodNotAllowed();
    }

    public function test_a_customer_from_another_organization_is_not_visible(): void
    {
        [$organization] = $this->customerContext();
        $foreign = Customer::factory()->create();

        $this->getJson("/api/v1/organizations/{$organization->id}/customers/{$foreign->id}")
            ->assertNotFound();
    }

    public function test_a_member_can_read_but_not_write_customers(): void
    {
        [$organization] = $this->customerContext();
        $member = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationMember($organization, $member);
        Sanctum::actingAs($member);

        $this->getJson("/api/v1/organizations/{$organization->id}/customers")->assertOk();

        $this->postJson("/api/v1/organizations/{$organization->id}/customers", [
            'name' => 'Forbidden Customer',
        ])->assertForbidden();

        $this->assertDatabaseCount('customers', 0);
    }

    public function test_an_unverified_email_cannot_create_a_customer(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->createOrganizationAdmin($organization, $user);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/organizations/{$organization->id}/customers", [
            'name' => 'Unverified Customer',
        ])->assertForbidden();

        $this->assertDatabaseCount('customers', 0);
    }
}
