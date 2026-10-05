<?php

namespace App\Application\Branches;

use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

final class BranchService
{
    public function create(Organization $organization, array $data): Branch
    {
        return DB::transaction(function () use ($organization, $data) {
            $isFirstBranch = $organization->branches()->count() === 0;

            $branch = Branch::create(array_merge($data, [
                'organization_id' => $organization->id,
                'status' => 'active',
                'is_primary' => $isFirstBranch ? true : ($data['is_primary'] ?? false),
            ]));

            if ($branch->is_primary && !$isFirstBranch) {
                $this->ensureSinglePrimary($organization, $branch);
            }

            return $branch;
        });
    }

    private function ensureSinglePrimary(Organization $organization, Branch $newPrimary): void
    {
        $organization->branches()
            ->where('id', '!=', $newPrimary->id)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);
    }
}