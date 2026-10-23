<?php

namespace App\Application\Catalogue;

use App\Domain\Catalogue\ValueObjects\UnitPrice;
use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Organization;
use App\Models\Product;
use App\Models\UnitOfMeasure;
use LogicException;

final class ProductService
{
    public function create(Organization $organization, array $data): Product
    {
        $unitOfMeasure = $this->resolveUnitOfMeasure($organization, $data['unit_of_measure_id']);
        $category = isset($data['category_id']) && $data['category_id'] !== null
            ? $this->resolveCategory($organization, $data['category_id'])
            : null;

        $product = new Product([
            'organization_id' => $organization->id,
            'unit_of_measure_id' => $unitOfMeasure->id,
            'category_id' => $category?->id,
            'name' => $data['name'],
            'sku' => $data['sku'] ?? null,
            'type' => ProductType::from($data['type']),
            'tax_code' => $data['tax_code'] ?? null,
            'price' => UnitPrice::parse($data['price'])->scaledValue(),
        ]);

        $product->save();

        return $product;
    }

    public function update(Organization $organization, Product $product, array $data): Product
    {
        if ($product->organization_id !== $organization->id) {
            throw new LogicException('The product does not belong to this organization.');
        }

        if (array_key_exists('unit_of_measure_id', $data)) {
            $product->unit_of_measure_id = $this->resolveUnitOfMeasure($organization, $data['unit_of_measure_id'])->id;
        }

        if (array_key_exists('category_id', $data)) {
            $product->category_id = $data['category_id'] === null
                ? null
                : $this->resolveCategory($organization, $data['category_id'])->id;
        }

        if (array_key_exists('name', $data)) {
            $product->name = $data['name'];
        }

        if (array_key_exists('sku', $data)) {
            $product->sku = $data['sku'];
        }

        if (array_key_exists('type', $data)) {
            $type = ProductType::from($data['type']);

            if ($product->tracksStock() && ! $type->tracksStock() && $product->stockMovements()->exists()) {
                throw new LogicException('A product with stock movements cannot stop tracking stock.');
            }

            $product->type = $type;
        }

        if (array_key_exists('tax_code', $data)) {
            $product->tax_code = $data['tax_code'];
        }

        if (array_key_exists('price', $data)) {
            $product->price = UnitPrice::parse($data['price'])->scaledValue();
        }

        $product->save();

        return $product;
    }

    /**
     * Archive a product. A product is never deleted because a movement may
     * reference it.
     */
    public function archive(Organization $organization, Product $product): Product
    {
        if ($product->organization_id !== $organization->id) {
            throw new LogicException('The product does not belong to this organization.');
        }

        if (! $product->isArchived()) {
            $product->forceFill(['archived_at' => now()])->save();
        }

        return $product;
    }

    public function restore(Organization $organization, Product $product): Product
    {
        if ($product->organization_id !== $organization->id) {
            throw new LogicException('The product does not belong to this organization.');
        }

        if ($product->isArchived()) {
            $product->forceFill(['archived_at' => null])->save();
        }

        return $product;
    }

    private function resolveUnitOfMeasure(Organization $organization, string $id): UnitOfMeasure
    {
        $unitOfMeasure = UnitOfMeasure::query()->where('organization_id', $organization->id)->find($id);

        if (! $unitOfMeasure) {
            throw new LogicException('The unit of measure does not belong to this organization.');
        }

        return $unitOfMeasure;
    }

    private function resolveCategory(Organization $organization, string $id): Category
    {
        $category = Category::query()->where('organization_id', $organization->id)->find($id);

        if (! $category) {
            throw new LogicException('The category does not belong to this organization.');
        }

        return $category;
    }
}
