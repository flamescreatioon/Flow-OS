<?php

namespace Database\Factories;

use App\Models\GoodsReceipt;
use App\Models\InventoryLocation;
use App\Models\Organization;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

/**
 * @extends Factory<GoodsReceipt>
 */
class GoodsReceiptFactory extends Factory
{
    protected $model = GoodsReceipt::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'organization_id' => Organization::factory(),
            'branch_id' => null,
            'supplier_id' => null,
            'location_id' => InventoryLocation::factory(),
            'supplier_name' => null,
            'receipt_number' => fake()->unique()->numberBetween(1, 100000),
            'document_number' => 'GRN-'.str_pad((string) fake()->unique()->numberBetween(1, 100000), 6, '0', STR_PAD_LEFT),
            'status' => 'recorded',
            'received_on' => fake()->date(),
            'note' => null,
            'total_cost' => 0,
        ];
    }

    public function forSupplier(Supplier $supplier): static
    {
        return $this->state(fn (): array => [
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
        ]);
    }

    public function atLocation(InventoryLocation $location): static
    {
        return $this->state(fn (): array => ['location_id' => $location->id]);
    }
}
