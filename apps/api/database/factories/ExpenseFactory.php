<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'organization_id' => Organization::factory(),
            'branch_id' => null,
            'expense_category_id' => ExpenseCategory::factory(),
            'expense_number' => fake()->unique()->numberBetween(1, 100000),
            'document_number' => 'EXP-'.str_pad((string) fake()->unique()->numberBetween(1, 100000), 6, '0', STR_PAD_LEFT),
            'status' => 'recorded',
            'voided_at' => null,
            'description' => fake()->sentence(3),
            'amount' => 25000000,
            'spent_on' => fake()->date(),
            'payee' => fake()->company(),
            'payment_method' => 'cash',
            'note' => null,
        ];
    }

    public function voided(): static
    {
        return $this->state(fn (): array => [
            'status' => 'voided',
            'voided_at' => now(),
        ]);
    }
}
