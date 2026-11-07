<?php

namespace Database\Factories;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\Organization;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

/**
 * @extends Factory<GoodsReceiptLine>
 */
class GoodsReceiptLineFactory extends Factory
{
    protected $model = GoodsReceiptLine::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'organization_id' => Organization::factory(),
            'goods_receipt_id' => GoodsReceipt::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->words(2, true),
            'quantity' => 5000,
            'unit_cost' => 1000000,
            'line_total' => 50000000,
        ];
    }
}
