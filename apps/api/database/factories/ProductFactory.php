<?php

namespace Database\Factories;

use App\Enums\ProductType;
use App\Models\Organization;
use App\Models\Product;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'organization_id' => Organization::factory(),
            'unit_of_measure_id' => null,
            'category_id' => null,
            'name' => fake()->unique()->words(2, true),
            'sku' => strtoupper(fake()->unique()->bothify('??-####')),
            'type' => ProductType::Stocked,
            'tax_code' => null,
            'price' => 100000,
            'archived_at' => null,
        ];
    }

    /**
     * A product's unit of measure must belong to the same organization, so
     * the unit is created inside the product's organization when the caller
     * did not supply one.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Product $product): void {
            if ($product->unit_of_measure_id !== null) {
                return;
            }

            $unit = UnitOfMeasure::factory()
                ->for($product->organization)
                ->create();

            $product->unit_of_measure_id = $unit->id;
        });
    }

    public function stocked(): static
    {
        return $this->state(fn (): array => ['type' => ProductType::Stocked]);
    }

    public function nonStocked(): static
    {
        return $this->state(fn (): array => ['type' => ProductType::NonStocked]);
    }

    public function service(): static
    {
        return $this->state(fn (): array => ['type' => ProductType::Service]);
    }

    public function withUnit(UnitOfMeasure $unitOfMeasure): static
    {
        return $this->state(fn (): array => ['unit_of_measure_id' => $unitOfMeasure->id]);
    }

    public function withPrice(int $scaledPrice): static
    {
        return $this->state(fn (): array => ['price' => $scaledPrice]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['archived_at' => now()]);
    }
}
