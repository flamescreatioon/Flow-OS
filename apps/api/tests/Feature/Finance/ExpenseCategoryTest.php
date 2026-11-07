<?php

namespace Tests\Feature\Finance;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Organization;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\WithRbac;
use Tests\TestCase;

class ExpenseCategoryTest extends TestCase
{
    use WithRbac;

    /**
     * @return array{0: Organization, 1: User}
     */
    protected function categoryContext(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationAdmin($organization, $user);
        Sanctum::actingAs($user);

        return [$organization, $user];
    }

    public function test_a_category_is_created_with_a_unique_name(): void
    {
        [$organization] = $this->categoryContext();

        $this->postJson("/api/v1/organizations/{$organization->id}/expense-categories", [
            'name' => 'Transport',
            'description' => 'Fuel, fares, and logistics',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Transport');

        $this->postJson("/api/v1/organizations/{$organization->id}/expense-categories", [
            'name' => 'Transport',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('expense_categories', 1);
    }

    public function test_a_category_is_updated_and_archived_but_never_deleted(): void
    {
        [$organization] = $this->categoryContext();
        $category = ExpenseCategory::factory()->for($organization)->create();

        $this->patchJson("/api/v1/organizations/{$organization->id}/expense-categories/{$category->id}", [
            'description' => 'Operations travel',
        ])->assertOk()
            ->assertJsonPath('data.description', 'Operations travel');

        $this->postJson("/api/v1/organizations/{$organization->id}/expense-categories/{$category->id}/archive")
            ->assertOk()
            ->assertJsonPath('data.archived_at', fn ($value) => $value !== null);

        $this->deleteJson("/api/v1/organizations/{$organization->id}/expense-categories/{$category->id}")
            ->assertMethodNotAllowed();

        $this->assertDatabaseHas('expense_categories', ['id' => $category->id]);
    }

    public function test_archived_categories_are_hidden_from_the_default_list(): void
    {
        [$organization] = $this->categoryContext();

        ExpenseCategory::factory()->for($organization)->create(['name' => 'Active Cat']);
        ExpenseCategory::factory()->for($organization)->archived()->create(['name' => 'Archived Cat']);

        $active = $this->getJson("/api/v1/organizations/{$organization->id}/expense-categories")->assertOk();
        $this->assertSame(['Active Cat'], array_column($active->json('data'), 'name'));

        $archived = $this->getJson("/api/v1/organizations/{$organization->id}/expense-categories?archived=true")->assertOk();
        $this->assertSame(['Archived Cat'], array_column($archived->json('data'), 'name'));
    }

    public function test_a_category_from_another_organization_is_not_visible(): void
    {
        [$organization] = $this->categoryContext();
        $foreign = ExpenseCategory::factory()->create();

        $this->getJson("/api/v1/organizations/{$organization->id}/expense-categories/{$foreign->id}")
            ->assertNotFound();
    }

    public function test_a_member_can_view_but_not_write_categories(): void
    {
        [$organization] = $this->categoryContext();
        $member = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationMember($organization, $member);
        Sanctum::actingAs($member);

        $this->getJson("/api/v1/organizations/{$organization->id}/expense-categories")->assertOk();

        $this->postJson("/api/v1/organizations/{$organization->id}/expense-categories", [
            'name' => 'Forbidden Category',
        ])->assertForbidden();

        $this->assertDatabaseCount('expense_categories', 0);
    }
}
