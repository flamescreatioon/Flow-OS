<?php

namespace Database\Factories;

use App\Models\InventoryLocation;
use App\Models\Organization;
use App\Models\Product;
use App\Models\StockBalance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

/**
 * @extends Factory<StockBalance>
 */
class StockBalanceFactory extends Factory
{
    protected $model = StockBalance::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'organization_id' => Organization::factory(),
            'product_id' => Product::factory(),
            'location_id' => InventoryLocation::factory(),
            'quantity' => 0,
        ];
    }
}
