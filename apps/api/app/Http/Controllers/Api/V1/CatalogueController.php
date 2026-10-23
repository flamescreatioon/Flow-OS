<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Authorization\AuthorizationService;
use App\Application\Catalogue\CatalogueService;
use App\Enums\PermissionKey;
use App\Http\Requests\Catalogue\StoreCategoryRequest;
use App\Http\Requests\Catalogue\StoreUnitOfMeasureRequest;
use App\Http\Requests\Catalogue\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\UnitOfMeasureResource;
use App\Models\Category;
use App\Models\Organization;
use Illuminate\Http\Request;
use LogicException;

class CatalogueController
{
    public function listCategories(Request $request, Organization $organization)
    {
        $this->permission($request, $organization, PermissionKey::CatalogueView->value);

        $query = $organization->categories();

        if ($q = $request->query('q')) {
            $query->search($q, ['name']);
        }

        return CategoryResource::collection($query->orderBy('name')->paginate(100));
    }

    public function storeCategory(StoreCategoryRequest $request, Organization $organization, CatalogueService $service)
    {
        $this->permission($request, $organization, PermissionKey::CatalogueManage->value);

        $category = $service->createCategory($organization, $request->validated());

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    public function updateCategory(UpdateCategoryRequest $request, Organization $organization, Category $category, CatalogueService $service)
    {
        $this->permission($request, $organization, PermissionKey::CatalogueManage->value);
        abort_unless($category->organization_id === $organization->id, 404);

        try {
            $category = $service->updateCategory($organization, $category, $request->validated());
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        }

        return new CategoryResource($category);
    }

    public function listUnitOfMeasures(Request $request, Organization $organization)
    {
        $this->permission($request, $organization, PermissionKey::CatalogueView->value);

        return UnitOfMeasureResource::collection(
            $organization->unitOfMeasures()->orderBy('code')->paginate(100)
        );
    }

    public function storeUnitOfMeasure(StoreUnitOfMeasureRequest $request, Organization $organization, CatalogueService $service)
    {
        $this->permission($request, $organization, PermissionKey::CatalogueManage->value);

        $unitOfMeasure = $service->createUnitOfMeasure($organization, $request->validated());

        return (new UnitOfMeasureResource($unitOfMeasure))->response()->setStatusCode(201);
    }

    private function permission(Request $request, Organization $organization, string $permission): void
    {
        abort_unless(app(AuthorizationService::class)->allows($request->user(), $organization, $permission), 403);
    }
}
