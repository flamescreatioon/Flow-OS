<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

/**
 * @extends Factory<UnitOfMeasure>
 */
class UnitOfMeasureFactory extends Factory
{
    protected $model = UnitOfMeasure::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'organization_id' => Organization::factory(),
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'name' => fake()->word(),
            'precision' => 0,
        ];
    }

    public function withPrecision(int $precision): static
    {
        return $this->state(fn (): array => ['precision' => $precision]);
    }
}
