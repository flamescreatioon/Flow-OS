<?php

namespace Database\Factories;

use App\Models\InventoryLocation;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

/**
 * @extends Factory<InventoryLocation>
 */
class InventoryLocationFactory extends Factory
{
    protected $model = InventoryLocation::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'organization_id' => Organization::factory(),
            'branch_id' => null,
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'name' => fake()->unique()->words(2, true),
            'archived_at' => null,
        ];
    }

    public function inBranch(string $branchId): static
    {
        return $this->state(fn (): array => ['branch_id' => $branchId]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['archived_at' => now()]);
    }
}
