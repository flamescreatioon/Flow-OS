<?php

namespace App\Application\Catalogue;

use App\Models\Category;
use App\Models\Organization;
use App\Models\UnitOfMeasure;
use LogicException;

final class CatalogueService
{
    public function createUnitOfMeasure(Organization $organization, array $data): UnitOfMeasure
    {
        $unitOfMeasure = new UnitOfMeasure([
            'organization_id' => $organization->id,
            'code' => $data['code'],
            'name' => $data['name'],
            'precision' => $data['precision'] ?? 0,
        ]);

        $unitOfMeasure->save();

        return $unitOfMeasure;
    }

    public function createCategory(Organization $organization, array $data): Category
    {
        $category = new Category([
            'organization_id' => $organization->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        $category->save();

        return $category;
    }

    public function updateCategory(Organization $organization, Category $category, array $data): Category
    {
        if ($category->organization_id !== $organization->id) {
            throw new LogicException('The category does not belong to this organization.');
        }

        if (array_key_exists('name', $data)) {
            $category->name = $data['name'];
        }

        if (array_key_exists('description', $data)) {
            $category->description = $data['description'];
        }

        $category->save();

        return $category;
    }
}
