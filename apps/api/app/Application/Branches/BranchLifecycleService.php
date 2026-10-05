<?php

namespace App\Application\Branches;

use App\Enums\BranchStatus;
use App\Models\Branch;
use LogicException;

final class BranchLifecycleService
{
    public function activate(Branch $branch): Branch
    {
        return $this->transition($branch, BranchStatus::Active);
    }

    public function deactivate(Branch $branch): Branch
    {
        $this->assertNotPrimary($branch);
        return $this->transition($branch, BranchStatus::Inactive);
    }

    public function close(Branch $branch): Branch
    {
        $this->assertNotPrimary($branch);
        return $this->transition($branch, BranchStatus::Closed);
    }

    private function transition(Branch $branch, BranchStatus $target): Branch
    {
        $current = $branch->status;

        if ($current === BranchStatus::Closed) {
            throw new LogicException('Closed branch cannot be transitioned.');
        }

        $validTransitions = match ($current) {
            BranchStatus::Active => [BranchStatus::Inactive, BranchStatus::Closed],
            BranchStatus::Inactive => [BranchStatus::Active, BranchStatus::Closed],
            BranchStatus::Closed => [],
        };

        if (! in_array($target, $validTransitions, true)) {
            throw new LogicException("Invalid branch lifecycle transition from {$current->value} to {$target->value}.");
        }

        $branch->forceFill(['status' => $target])->save();

        return $branch;
    }

    private function assertNotPrimary(Branch $branch): void
    {
        if ($branch->is_primary) {
            throw new LogicException('Primary branch cannot be deactivated or closed. Make another branch primary first.');
        }
    }
}