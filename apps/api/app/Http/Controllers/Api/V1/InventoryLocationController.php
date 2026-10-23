<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Authorization\AuthorizationService;
use App\Application\Inventory\InventoryLocationService;
use App\Enums\PermissionKey;
use App\Http\Requests\Inventory\ListInventoryLocationsRequest;
use App\Http\Requests\Inventory\StoreInventoryLocationRequest;
use App\Http\Requests\Inventory\UpdateInventoryLocationRequest;
use App\Http\Resources\InventoryLocationResource;
use App\Models\InventoryLocation;
use App\Models\Organization;
use Illuminate\Http\Request;
use LogicException;

class InventoryLocationController
{
    public function index(ListInventoryLocationsRequest $request, Organization $organization)
    {
        $this->permission($request, $organization, PermissionKey::StockView->value);
        $filters = $request->validated();

        $query = $organization->inventoryLocations();

        $archived = $filters['archived'] ?? 'false';

        if ($archived === 'true') {
            $query->whereNotNull('archived_at');
        } elseif ($archived === 'all') {
            // Both archived and active locations are returned.
        } else {
            $query->whereNull('archived_at');
        }

        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (! empty($filters['q'])) {
            $query->search($filters['q'], ['name', 'code']);
        }

        $sort = $filters['sort'] ?? null;
        if ($sort) {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $query->orderBy(ltrim($sort, '-'), $direction);
        } else {
            $query->orderBy('name');
        }

        return InventoryLocationResource::collection($query->paginate($request->perPage()));
    }

    public function store(StoreInventoryLocationRequest $request, Organization $organization, InventoryLocationService $service)
    {
        $this->permission($request, $organization, PermissionKey::StockMove->value);

        try {
            $location = $service->create($organization, $request->validated());
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        }

        return (new InventoryLocationResource($location))->response()->setStatusCode(201);
    }

    public function show(Request $request, Organization $organization, InventoryLocation $location)
    {
        $this->permission($request, $organization, PermissionKey::StockView->value);
        $this->assertBelongsToOrganization($location, $organization);

        return new InventoryLocationResource($location);
    }

    public function update(UpdateInventoryLocationRequest $request, Organization $organization, InventoryLocation $location, InventoryLocationService $service)
    {
        $this->permission($request, $organization, PermissionKey::StockMove->value);
        $this->assertBelongsToOrganization($location, $organization);

        try {
            $location = $service->update($organization, $location, $request->validated());
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        }

        return new InventoryLocationResource($location);
    }

    public function archive(Request $request, Organization $organization, InventoryLocation $location, InventoryLocationService $service)
    {
        $this->permission($request, $organization, PermissionKey::StockMove->value);
        $this->assertBelongsToOrganization($location, $organization);

        try {
            $location = $service->archive($organization, $location);
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        }

        return new InventoryLocationResource($location);
    }

    private function assertBelongsToOrganization(InventoryLocation $location, Organization $organization): void
    {
        abort_unless($location->organization_id === $organization->id, 404);
    }

    private function permission(Request $request, Organization $organization, string $permission): void
    {
        abort_unless(app(AuthorizationService::class)->allows($request->user(), $organization, $permission), 403);
    }
}
