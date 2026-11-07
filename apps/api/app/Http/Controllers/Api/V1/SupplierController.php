<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Authorization\AuthorizationService;
use App\Application\Purchasing\SupplierService;
use App\Enums\PermissionKey;
use App\Http\Requests\Purchasing\ListSuppliersRequest;
use App\Http\Requests\Purchasing\StoreSupplierRequest;
use App\Http\Requests\Purchasing\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Organization;
use App\Models\Supplier;
use Illuminate\Http\Request;
use LogicException;

class SupplierController
{
    public function index(ListSuppliersRequest $request, Organization $organization)
    {
        $this->permission($request, $organization, PermissionKey::SupplierView->value);
        $filters = $request->validated();

        $query = $organization->suppliers();

        $archived = $filters['archived'] ?? 'false';

        if ($archived === 'true') {
            $query->whereNotNull('archived_at');
        } elseif ($archived === 'all') {
            // Both archived and active suppliers are returned.
        } else {
            $query->whereNull('archived_at');
        }

        if (! empty($filters['q'])) {
            $query->search($filters['q'], ['name', 'email', 'phone']);
        }

        $sort = $filters['sort'] ?? null;
        if ($sort) {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $query->orderBy(ltrim($sort, '-'), $direction);
        } else {
            $query->orderBy('name');
        }

        return SupplierResource::collection($query->paginate($request->perPage()));
    }

    public function store(StoreSupplierRequest $request, Organization $organization, SupplierService $service)
    {
        $this->permission($request, $organization, PermissionKey::SupplierCreate->value);

        try {
            $supplier = $service->create($organization, $request->validated());
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        }

        return (new SupplierResource($supplier))->response()->setStatusCode(201);
    }

    public function show(Request $request, Organization $organization, Supplier $supplier)
    {
        $this->permission($request, $organization, PermissionKey::SupplierView->value);
        $this->assertBelongsToOrganization($supplier, $organization);

        return new SupplierResource($supplier);
    }

    public function update(UpdateSupplierRequest $request, Organization $organization, Supplier $supplier, SupplierService $service)
    {
        $this->permission($request, $organization, PermissionKey::SupplierUpdate->value);
        $this->assertBelongsToOrganization($supplier, $organization);

        try {
            $supplier = $service->update($organization, $supplier, $request->validated());
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        }

        return new SupplierResource($supplier);
    }

    public function archive(Request $request, Organization $organization, Supplier $supplier, SupplierService $service)
    {
        $this->permission($request, $organization, PermissionKey::SupplierArchive->value);
        $this->assertBelongsToOrganization($supplier, $organization);

        try {
            $supplier = $service->archive($organization, $supplier);
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        }

        return new SupplierResource($supplier);
    }

    private function assertBelongsToOrganization(Supplier $supplier, Organization $organization): void
    {
        abort_unless($supplier->organization_id === $organization->id, 404);
    }

    private function permission(Request $request, Organization $organization, string $permission): void
    {
        abort_unless(app(AuthorizationService::class)->allows($request->user(), $organization, $permission), 403);
    }
}
