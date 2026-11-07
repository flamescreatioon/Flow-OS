<?php

namespace Tests\Feature\Finance;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Organization;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\WithRbac;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use WithRbac;

    /**
     * @return array{0: Organization, 1: User, 2: ExpenseCategory}
     */
    protected function expenseContext(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationAdmin($organization, $user);
        $category = ExpenseCategory::factory()->for($organization)->create(['name' => 'Transport']);
        Sanctum::actingAs($user);

        return [$organization, $user, $category];
    }

    /**
     * @return array<string, mixed>
     */
    private function expensePayload(string $categoryId, array $overrides = []): array
    {
        return array_merge([
            'expense_category_id' => $categoryId,
            'description' => 'Fuel for delivery bike',
            'amount' => '1500.0000',
            'payee' => 'Total Filling Station',
            'payment_method' => 'cash',
        ], $overrides);
    }

    public function test_an_expense_is_recorded_with_a_number_and_defaults(): void
    {
        [$organization, , $category] = $this->expenseContext();

        $response = $this->postJson(
            "/api/v1/organizations/{$organization->id}/expenses",
            $this->expensePayload($category->id)
        )->assertCreated();

        $response->assertJsonPath('data.document_number', 'EXP-000001')
            ->assertJsonPath('data.status', 'recorded')
            ->assertJsonPath('data.amount', '1500.0000')
            ->assertJsonPath('data.spent_on', now()->format('Y-m-d'))
            ->assertJsonPath('data.payment_method', 'cash')
            ->assertJsonPath('data.expense_category.name', 'Transport');

        $this->assertDatabaseHas('expenses', [
            'organization_id' => $organization->id,
            'expense_number' => 1,
            'document_number' => 'EXP-000001',
            'amount' => 15000000,
            'status' => 'recorded',
        ]);
    }

    public function test_a_recorded_expense_is_editable_and_void_is_terminal(): void
    {
        [$organization, , $category] = $this->expenseContext();

        $expenseId = $this->postJson(
            "/api/v1/organizations/{$organization->id}/expenses",
            $this->expensePayload($category->id)
        )->assertCreated()->json('data.id');

        $this->patchJson("/api/v1/organizations/{$organization->id}/expenses/{$expenseId}", [
            'amount' => '2000.0000',
            'note' => 'Includes toll',
        ])->assertOk()
            ->assertJsonPath('data.amount', '2000.0000')
            ->assertJsonPath('data.note', 'Includes toll');

        $this->postJson("/api/v1/organizations/{$organization->id}/expenses/{$expenseId}/void")
            ->assertOk()
            ->assertJsonPath('data.status', 'voided');

        $this->patchJson("/api/v1/organizations/{$organization->id}/expenses/{$expenseId}", [
            'amount' => '999.0000',
        ])->assertUnprocessable();

        $this->postJson("/api/v1/organizations/{$organization->id}/expenses/{$expenseId}/void")
            ->assertUnprocessable();

        // There is no delete path for an expense.
        $this->deleteJson("/api/v1/organizations/{$organization->id}/expenses/{$expenseId}")
            ->assertMethodNotAllowed();

        $this->assertDatabaseHas('expenses', [
            'id' => $expenseId,
            'status' => 'voided',
            'amount' => 20000000,
        ]);
    }

    public function test_expense_numbers_are_organization_sequential(): void
    {
        [$organization, , $category] = $this->expenseContext();

        $this->postJson(
            "/api/v1/organizations/{$organization->id}/expenses",
            $this->expensePayload($category->id)
        )->assertCreated()->assertJsonPath('data.document_number', 'EXP-000001');

        $this->postJson(
            "/api/v1/organizations/{$organization->id}/expenses",
            $this->expensePayload($category->id)
        )->assertCreated()->assertJsonPath('data.document_number', 'EXP-000002');

        $this->assertDatabaseHas('document_sequences', [
            'organization_id' => $organization->id,
            'document_type' => 'expense',
            'next_value' => 3,
        ]);
    }

    public function test_an_expense_replays_idempotently(): void
    {
        [$organization, , $category] = $this->expenseContext();
        $headers = ['Idempotency-Key' => 'expense-key-1'];
        $payload = $this->expensePayload($category->id);

        $first = $this->postJson("/api/v1/organizations/{$organization->id}/expenses", $payload, $headers)
            ->assertCreated();
        $second = $this->postJson("/api/v1/organizations/{$organization->id}/expenses", $payload, $headers)
            ->assertCreated();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertDatabaseCount('expenses', 1);
    }

    public function test_a_non_positive_amount_is_rejected(): void
    {
        [$organization, , $category] = $this->expenseContext();

        $this->postJson(
            "/api/v1/organizations/{$organization->id}/expenses",
            $this->expensePayload($category->id, ['amount' => '-5.0000'])
        )->assertUnprocessable();

        $this->postJson(
            "/api/v1/organizations/{$organization->id}/expenses",
            $this->expensePayload($category->id, ['amount' => '0.0000'])
        )->assertUnprocessable();

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_an_archived_category_cannot_be_used(): void
    {
        [$organization, , $category] = $this->expenseContext();
        $category->forceFill(['archived_at' => now()])->save();

        $this->postJson(
            "/api/v1/organizations/{$organization->id}/expenses",
            $this->expensePayload($category->id)
        )->assertUnprocessable();

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_an_expense_from_another_organization_is_not_visible_or_usable(): void
    {
        [$organization, , $category] = $this->expenseContext();

        $other = Organization::factory()->create();
        $foreign = Expense::factory()->for($other)->create();

        $this->getJson("/api/v1/organizations/{$organization->id}/expenses/{$foreign->id}")
            ->assertNotFound();

        $foreignCategory = ExpenseCategory::factory()->create();
        $this->postJson(
            "/api/v1/organizations/{$organization->id}/expenses",
            $this->expensePayload($foreignCategory->id)
        )->assertUnprocessable();

        $this->assertDatabaseCount('expenses', 1);
    }

    public function test_expenses_are_filtered_by_category_and_status(): void
    {
        [$organization, , $category] = $this->expenseContext();

        $recordedId = $this->postJson(
            "/api/v1/organizations/{$organization->id}/expenses",
            $this->expensePayload($category->id)
        )->assertCreated()->json('data.id');

        $voided = Expense::factory()->for($organization)->voided()->create([
            'expense_number' => 99,
            'document_number' => 'EXP-000099',
            'expense_category_id' => $category->id,
        ]);

        $this->getJson("/api/v1/organizations/{$organization->id}/expenses?status=recorded")
            ->assertOk()
            ->assertJsonPath('data.0.id', $recordedId);

        $this->getJson("/api/v1/organizations/{$organization->id}/expenses?status=voided")
            ->assertOk()
            ->assertJsonPath('data.0.id', $voided->id);

        $this->getJson("/api/v1/organizations/{$organization->id}/expenses?expense_category_id={$category->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_a_member_can_view_but_not_record_expenses(): void
    {
        [$organization, , $category] = $this->expenseContext();
        $member = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationMember($organization, $member);
        Sanctum::actingAs($member);

        $this->getJson("/api/v1/organizations/{$organization->id}/expenses")->assertOk();

        $this->postJson(
            "/api/v1/organizations/{$organization->id}/expenses",
            $this->expensePayload($category->id)
        )->assertForbidden();

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_an_unverified_email_cannot_record_an_expense(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->createOrganizationAdmin($organization, $user);
        $category = ExpenseCategory::factory()->for($organization)->create();
        Sanctum::actingAs($user);

        $this->postJson(
            "/api/v1/organizations/{$organization->id}/expenses",
            $this->expensePayload($category->id)
        )->assertForbidden();

        $this->assertDatabaseCount('expenses', 0);
    }
}
