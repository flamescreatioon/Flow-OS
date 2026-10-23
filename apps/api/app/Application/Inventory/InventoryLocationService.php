<?php

namespace App\Application\Inventory;

use App\Models\Branch;
use App\Models\InventoryLocation;
use App\Models\Organization;
use LogicException;

final class InventoryLocationService
{
    public function create(Organization $organization, array $data): InventoryLocation
    {
        $branch = isset($data['branch_id']) && $data['branch_id'] !== null
            ? $this->resolveBranch($organization, $data['branch_id'])
            : null;

        $location = new InventoryLocation([
            'organization_id' => $organization->id,
            'branch_id' => $branch?->id,
            'code' => $data['code'],
            'name' => $data['name'],
        ]);

        $location->save();

        return $location;
    }

    public function update(Organization $organization, InventoryLocation $location, array $data): InventoryLocation
    {
        if ($location->organization_id !== $organization->id) {
            throw new LogicException('The location does not belong to this organization.');
        }

        if (array_key_exists('branch_id', $data)) {
            $location->branch_id = $data['branch_id'] === null
                ? null
                : $this->resolveBranch($organization, $data['branch_id'])->id;
        }

        if (array_key_exists('code', $data)) {
            $location->code = $data['code'];
        }

        if (array_key_exists('name', $data)) {
            $location->name = $data['name'];
        }

        $location->save();

        return $location;
    }

    public function archive(Organization $organization, InventoryLocation $location): InventoryLocation
    {
        if ($location->organization_id !== $organization->id) {
            throw new LogicException('The location does not belong to this organization.');
        }

        if (! $location->isArchived()) {
            $location->forceFill(['archived_at' => now()])->save();
        }

        return $location;
    }

    private function resolveBranch(Organization $organization, string $id): Branch
    {
        $branch = Branch::query()->where('organization_id', $organization->id)->find($id);

        if (! $branch) {
            throw new LogicException('The branch does not belong to this organization.');
        }

        return $branch;
    }
}
