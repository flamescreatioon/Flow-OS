<?php

namespace App\Application\Finance;

use App\Models\ExpenseCategory;
use App\Models\Organization;
use LogicException;

final class ExpenseCategoryService
{
    public function create(Organization $organization, array $data): ExpenseCategory
    {
        $this->assertNameAvailable($organization, $data['name']);

        $category = new ExpenseCategory([
            'organization_id' => $organization->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        $category->save();

        return $category;
    }

    public function update(Organization $organization, ExpenseCategory $category, array $data): ExpenseCategory
    {
        if ($category->organization_id !== $organization->id) {
            throw new LogicException('The expense category does not belong to this organization.');
        }

        if (array_key_exists('name', $data)) {
            $this->assertNameAvailable($organization, $data['name'], $category->id);
            $category->name = $data['name'];
        }

        if (array_key_exists('description', $data)) {
            $category->description = $data['description'];
        }

        $category->save();

        return $category;
    }

    public function archive(Organization $organization, ExpenseCategory $category): ExpenseCategory
    {
        if ($category->organization_id !== $organization->id) {
            throw new LogicException('The expense category does not belong to this organization.');
        }

        if (! $category->isArchived()) {
            $category->forceFill(['archived_at' => now()])->save();
        }

        return $category;
    }

    /**
     * The (organization_id, name) unique constraint remains the authority
     * against concurrent writes; this check only turns the common duplicate
     * into a validation error instead of a constraint failure. Archived
     * categories are included because the constraint applies to them too.
     */
    private function assertNameAvailable(Organization $organization, string $name, ?string $ignoreCategoryId = null): void
    {
        $query = ExpenseCategory::query()
            ->where('organization_id', $organization->id)
            ->where('name', $name);

        if ($ignoreCategoryId !== null) {
            $query->whereKeyNot($ignoreCategoryId);
        }

        if ($query->exists()) {
            throw new LogicException('An expense category with this name already exists in this organization.');
        }
    }
}
