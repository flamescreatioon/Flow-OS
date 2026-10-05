<?php

namespace App\Application\Branches;

use App\Enums\BranchStatus;
use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use LogicException;

final class BranchPrimaryService
{
    public function makePrimary(Organization $organization, Branch $branch): Branch
    {
        if ($branch->organization_id !== $organization->id) {
            throw new LogicException('Branch does not belong to the specified organization.');
        }

        if ($branch->status !== BranchStatus::Active) {
            throw new LogicException('Only active branches can be made primary.');
        }

        if ($branch->is_primary) {
            return $branch;
        }

        return DB::transaction(function () use ($organization, $branch) {
            $organization->branches()
                ->where('is_primary', true)
                ->update(['is_primary' => false]);

            $branch->forceFill(['is_primary' => true])->save();

            return $branch->fresh();
        });
    }
}
