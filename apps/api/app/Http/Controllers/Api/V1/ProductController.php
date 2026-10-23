<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Authorization\AuthorizationService;
use App\Application\Catalogue\ProductService;
use App\Enums\PermissionKey;
use App\Http\Requests\Catalogue\ListProductsRequest;
use App\Http\Requests\Catalogue\StoreProductRequest;
use App\Http\Requests\Catalogue\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Organization;
use App\Models\Product;
use Illuminate\Http\Request;
use LogicException;

class ProductController
{
    public function index(ListProductsRequest $request, Organization $organization)
    {
        $this->permission($request, $organization, PermissionKey::ProductView->value);
        $filters = $request->validated();

        $query = $organization->products()->with(['unitOfMeasure', 'category']);

        $archived = $filters['archived'] ?? 'false';

        if ($archived === 'true') {
            $query->whereNotNull('archived_at');
        } elseif ($archived === 'all') {
            // Both archived and active products are returned.
        } else {
            $query->whereNull('archived_at');
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['q'])) {
            $query->search($filters['q'], ['name', 'sku']);
        }

        $sort = $filters['sort'] ?? null;
        if ($sort) {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $query->orderBy(ltrim($sort, '-'), $direction);
        } else {
            $query->orderBy('name');
        }

        return ProductResource::collection($query->paginate($request->perPage()));
    }

    public function store(StoreProductRequest $request, Organization $organization, ProductService $service)
    {
        $this->permission($request, $organization, PermissionKey::ProductCreate->value);

        try {
            $product = $service->create($organization, $request->validated());
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        }

        return (new ProductResource($product->load(['unitOfMeasure', 'category'])))->response()->setStatusCode(201);
    }

    public function show(Request $request, Organization $organization, Product $product)
    {
        $this->permission($request, $organization, PermissionKey::ProductView->value);
        $this->assertBelongsToOrganization($product, $organization);

        return new ProductResource($product->load(['unitOfMeasure', 'category']));
    }

    public function update(UpdateProductRequest $request, Organization $organization, Product $product, ProductService $service)
    {
        $this->permission($request, $organization, PermissionKey::ProductUpdate->value);
        $this->assertBelongsToOrganization($product, $organization);

        try {
            $product = $service->update($organization, $product, $request->validated());
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        }

        return new ProductResource($product->load(['unitOfMeasure', 'category']));
    }

    public function archive(Request $request, Organization $organization, Product $product, ProductService $service)
    {
        $this->permission($request, $organization, PermissionKey::ProductArchive->value);
        $this->assertBelongsToOrganization($product, $organization);

        try {
            $product = $service->archive($organization, $product);
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        }

        return new ProductResource($product);
    }

    public function restore(Request $request, Organization $organization, Product $product, ProductService $service)
    {
        $this->permission($request, $organization, PermissionKey::ProductArchive->value);
        $this->assertBelongsToOrganization($product, $organization);

        try {
            $product = $service->restore($organization, $product);
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        }

        return new ProductResource($product);
    }

    private function assertBelongsToOrganization(Product $product, Organization $organization): void
    {
        abort_unless($product->organization_id === $organization->id, 404);
    }

    private function permission(Request $request, Organization $organization, string $permission): void
    {
        abort_unless(app(AuthorizationService::class)->allows($request->user(), $organization, $permission), 403);
    }
}
