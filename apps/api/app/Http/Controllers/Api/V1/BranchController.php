<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Authorization\AuthorizationService;
use App\Application\Branches\BranchLifecycleService;
use App\Application\Branches\BranchPrimaryService;
use App\Application\Branches\BranchService;
use App\Enums\PermissionKey;
use App\Http\Requests\Branches\ListBranchesRequest;
use App\Http\Requests\Branches\StoreBranchRequest;
use App\Http\Requests\Branches\UpdateBranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Http\Request;
use LogicException;

class BranchController
{
    public function index(ListBranchesRequest $request, Organization $organization)
    {
        $this->permission($request, $organization, PermissionKey::BranchView->value);
        $filters = $request->validated();

        $query = $organization->branches();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['city'])) {
            $query->search($filters['city'], ['city']);
        }

        if (! empty($filters['q'])) {
            $query->search($filters['q'], ['name', 'code']);
        }

        $sort = $filters['sort'] ?? null;
        if ($sort) {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $query->orderBy(ltrim($sort, '-'), $direction);
        } else {
            $query->orderBy('is_primary', 'desc')->orderBy('name');
        }

        return BranchResource::collection($query->paginate($request->perPage()));
    }

    public function store(StoreBranchRequest $request, Organization $organization, BranchService $service)
    {
        $this->permission($request, $organization, PermissionKey::BranchCreate->value);
        $branch = $service->create($organization, $request->validated());

        return (new BranchResource($branch))->response()->setStatusCode(201);
    }

    public function show(Request $request, Organization $organization, Branch $branch)
    {
        $this->permission($request, $organization, PermissionKey::BranchView->value);
        $this->assertBranchBelongsToOrganization($branch, $organization);

        return new BranchResource($branch);
    }

    public function update(UpdateBranchRequest $request, Organization $organization, Branch $branch)
    {
        $this->permission($request, $organization, PermissionKey::BranchUpdate->value);
        $this->assertBranchBelongsToOrganization($branch, $organization);
        $branch->fill($request->validated());
        $branch->save();

        return new BranchResource($branch);
    }

    public function activate(Request $request, Organization $organization, Branch $branch, BranchLifecycleService $service)
    {
        $this->permission($request, $organization, PermissionKey::BranchLifecycle->value);
        $this->assertBranchBelongsToOrganization($branch, $organization);
        try {
            return new BranchResource($service->activate($branch));
        } catch (LogicException) {
            abort(422, 'Invalid branch lifecycle transition.');
        }
    }

    public function deactivate(Request $request, Organization $organization, Branch $branch, BranchLifecycleService $service)
    {
        $this->permission($request, $organization, PermissionKey::BranchLifecycle->value);
        $this->assertBranchBelongsToOrganization($branch, $organization);
        try {
            return new BranchResource($service->deactivate($branch));
        } catch (LogicException $e) {
            abort(422, $e->getMessage());
        }
    }

    public function close(Request $request, Organization $organization, Branch $branch, BranchLifecycleService $service)
    {
        $this->permission($request, $organization, PermissionKey::BranchLifecycle->value);
        $this->assertBranchBelongsToOrganization($branch, $organization);
        try {
            return new BranchResource($service->close($branch));
        } catch (LogicException $e) {
            abort(422, $e->getMessage());
        }
    }

    public function makePrimary(Request $request, Organization $organization, Branch $branch, BranchPrimaryService $service)
    {
        $this->permission($request, $organization, PermissionKey::BranchSetPrimary->value);
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

    private function permission(Request $request, Organization $organization, string $permission): void
    {
        abort_unless(app(AuthorizationService::class)->allows($request->user(), $organization, $permission), 403);
    }
}
