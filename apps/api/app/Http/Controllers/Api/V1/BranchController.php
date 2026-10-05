<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Branches\BranchLifecycleService;
use App\Application\Branches\BranchPrimaryService;
use App\Application\Branches\BranchService;
use App\Enums\BranchStatus;
use App\Http\Requests\Branches\StoreBranchRequest;
use App\Http\Requests\Branches\UpdateBranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Http\Request;
use LogicException;

class BranchController
{
    public function index(Request $request, Organization $organization)
    {
        $branches = $organization->branches()
            ->orderBy('is_primary', 'desc')
            ->orderBy('name')
            ->paginate(25);

        return BranchResource::collection($branches);
    }

    public function store(StoreBranchRequest $request, Organization $organization, BranchService $service)
    {
        $branch = $service->create($organization, $request->validated());
        return (new BranchResource($branch))->response()->setStatusCode(201);
    }

    public function show(Request $request, Organization $organization, Branch $branch)
    {
        $this->assertBranchBelongsToOrganization($branch, $organization);
        return new BranchResource($branch);
    }

    public function update(UpdateBranchRequest $request, Organization $organization, Branch $branch)
    {
        $this->assertBranchBelongsToOrganization($branch, $organization);
        $branch->fill($request->validated());
        $branch->save();

        return new BranchResource($branch);
    }

    public function activate(Request $request, Organization $organization, Branch $branch, BranchLifecycleService $service)
    {
        $this->assertBranchBelongsToOrganization($branch, $organization);
        try {
            return new BranchResource($service->activate($branch));
        } catch (LogicException) {
            abort(422, 'Invalid branch lifecycle transition.');
        }
    }

    public function deactivate(Request $request, Organization $organization, Branch $branch, BranchLifecycleService $service)
    {
        $this->assertBranchBelongsToOrganization($branch, $organization);
        try {
            return new BranchResource($service->deactivate($branch));
        } catch (LogicException $e) {
            abort(422, $e->getMessage());
        }
    }

    public function close(Request $request, Organization $organization, Branch $branch, BranchLifecycleService $service)
    {
        $this->assertBranchBelongsToOrganization($branch, $organization);
        try {
            return new BranchResource($service->close($branch));
        } catch (LogicException $e) {
            abort(422, $e->getMessage());
        }
    }

    public function makePrimary(Request $request, Organization $organization, Branch $branch, BranchPrimaryService $service)
    {
        $this->assertBranchBelongsToOrganization($branch, $organization);
        try {
            return new BranchResource($service->makePrimary($organization, $branch));
        } catch (LogicException $e) {
            abort(422, $e->getMessage());
        }
    }

    private function assertBranchBelongsToOrganization(Branch $branch, Organization $organization): void
    {
        abort_unless($branch->organization_id === $organization->id, 404);
    }
}