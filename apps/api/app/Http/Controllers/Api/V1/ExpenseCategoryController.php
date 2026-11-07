<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Authorization\AuthorizationService;
use App\Application\Finance\ExpenseCategoryService;
use App\Enums\PermissionKey;
use App\Http\Requests\Finance\ListExpenseCategoriesRequest;
use App\Http\Requests\Finance\StoreExpenseCategoryRequest;
use App\Http\Requests\Finance\UpdateExpenseCategoryRequest;
use App\Http\Resources\ExpenseCategoryResource;
use App\Models\ExpenseCategory;
use App\Models\Organization;
use Illuminate\Http\Request;
use LogicException;

class ExpenseCategoryController
{
    public function index(ListExpenseCategoriesRequest $request, Organization $organization)
    {
        $this->permission($request, $organization, PermissionKey::ExpenseCategoryView->value);
        $filters = $request->validated();

        $query = $organization->expenseCategories();

        $archived = $filters['archived'] ?? 'false';

        if ($archived === 'true') {
            $query->whereNotNull('archived_at');
        } elseif ($archived === 'all') {
            // Both archived and active categories are returned.
        } else {
            $query->whereNull('archived_at');
        }

        if (! empty($filters['q'])) {
            $query->search($filters['q'], ['name']);
        }

        return ExpenseCategoryResource::collection($query->orderBy('name')->paginate($request->perPage()));
    }

    public function store(StoreExpenseCategoryRequest $request, Organization $organization, ExpenseCategoryService $service)
    {
        $this->permission($request, $organization, PermissionKey::ExpenseCategoryCreate->value);

        try {
            $category = $service->create($organization, $request->validated());
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        }

        return (new ExpenseCategoryResource($category))->response()->setStatusCode(201);
    }

    public function show(Request $request, Organization $organization, ExpenseCategory $category)
    {
        $this->permission($request, $organization, PermissionKey::ExpenseCategoryView->value);
        $this->assertBelongsToOrganization($category, $organization);

        return new ExpenseCategoryResource($category);
    }

    public function update(UpdateExpenseCategoryRequest $request, Organization $organization, ExpenseCategory $category, ExpenseCategoryService $service)
    {
        $this->permission($request, $organization, PermissionKey::ExpenseCategoryUpdate->value);
        $this->assertBelongsToOrganization($category, $organization);

        try {
            $category = $service->update($organization, $category, $request->validated());
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        }

        return new ExpenseCategoryResource($category);
    }

    public function archive(Request $request, Organization $organization, ExpenseCategory $category, ExpenseCategoryService $service)
    {
        $this->permission($request, $organization, PermissionKey::ExpenseCategoryArchive->value);
        $this->assertBelongsToOrganization($category, $organization);

        try {
            $category = $service->archive($organization, $category);
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        }

        return new ExpenseCategoryResource($category);
    }

    private function assertBelongsToOrganization(ExpenseCategory $category, Organization $organization): void
    {
        abort_unless($category->organization_id === $organization->id, 404);
    }

    private function permission(Request $request, Organization $organization, string $permission): void
    {
        abort_unless(app(AuthorizationService::class)->allows($request->user(), $organization, $permission), 403);
    }
}
