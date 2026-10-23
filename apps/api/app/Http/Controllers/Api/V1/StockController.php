<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Authorization\AuthorizationService;
use App\Application\Inventory\StockMovementService;
use App\Enums\PermissionKey;
use App\Enums\StockMovementType;
use App\Http\Requests\Inventory\CountStockRequest;
use App\Http\Requests\Inventory\ListStockMovementsRequest;
use App\Http\Requests\Inventory\RecordStockMovementRequest;
use App\Http\Requests\Inventory\TransferStockRequest;
use App\Http\Resources\StockBalanceResource;
use App\Http\Resources\StockMovementResource;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use LogicException;

class StockController
{
    public function indexMovements(ListStockMovementsRequest $request, Organization $organization)
    {
        $this->permission($request, $organization, PermissionKey::StockView->value);
        $filters = $request->validated();

        $query = $organization->stockMovements()->with(['product', 'location']);

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (! empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // The server establishes the order. A client supplied occurrence time
        // is business context, not the sequence the server applied.
        return StockMovementResource::collection(
            $query->orderByDesc('created_at')->orderByDesc('id')->paginate($request->perPage())
        );
    }

    public function storeMovement(RecordStockMovementRequest $request, Organization $organization, StockMovementService $service)
    {
        $this->permission($request, $organization, $this->permissionFor($request->validated('type')));

        try {
            $movement = $service->record($organization, $request->validated(), $request->user());
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            abort(422, $exception->getMessage());
        }

        return (new StockMovementResource($movement->load(['product', 'location'])))->response()->setStatusCode(201);
    }

    public function storeCount(CountStockRequest $request, Organization $organization, StockMovementService $service)
    {
        $this->permission($request, $organization, PermissionKey::StockCount->value);

        try {
            $movement = $service->countStock($organization, $request->validated(), $request->user());
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            abort(422, $exception->getMessage());
        }

        return (new StockMovementResource($movement->load(['product', 'location'])))->response()->setStatusCode(201);
    }

    public function storeTransfer(TransferStockRequest $request, Organization $organization, StockMovementService $service): JsonResponse
    {
        $this->permission($request, $organization, PermissionKey::StockTransfer->value);

        try {
            [$outbound, $inbound] = $service->transfer($organization, $request->validated(), $request->user());
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            abort(422, $exception->getMessage());
        }

        return response()->json([
            'data' => [
                'outbound' => new StockMovementResource($outbound->load(['product', 'location'])),
                'inbound' => new StockMovementResource($inbound->load(['product', 'location'])),
            ],
        ], 201);
    }

    public function indexBalances(Request $request, Organization $organization)
    {
        $this->permission($request, $organization, PermissionKey::StockView->value);

        $query = $organization->stockBalances()->with(['product', 'location']);

        if ($productId = $request->query('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($locationId = $request->query('location_id')) {
            $query->where('location_id', $locationId);
        }

        return StockBalanceResource::collection(
            $query->orderBy('product_id')->orderBy('location_id')->paginate(100)
        );
    }

    /**
     * An adjustment needs the adjustment permission. Every other movement
     * needs the general movement permission.
     */
    private function permissionFor(string $type): string
    {
        return $type === StockMovementType::Adjustment->value
            ? PermissionKey::StockAdjust->value
            : PermissionKey::StockMove->value;
    }

    private function permission(Request $request, Organization $organization, string $permission): void
    {
        abort_unless(app(AuthorizationService::class)->allows($request->user(), $organization, $permission), 403);
    }
}
