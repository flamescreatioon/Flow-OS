<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Authorization\AuthorizationService;
use App\Application\Purchasing\GoodsReceiptService;
use App\Enums\PermissionKey;
use App\Http\Requests\Purchasing\ListGoodsReceiptsRequest;
use App\Http\Requests\Purchasing\RecordGoodsReceiptRequest;
use App\Http\Resources\GoodsReceiptResource;
use App\Models\GoodsReceipt;
use App\Models\Organization;
use Illuminate\Http\Request;
use InvalidArgumentException;
use LogicException;

class GoodsReceiptController
{
    public function index(ListGoodsReceiptsRequest $request, Organization $organization)
    {
        $this->permission($request, $organization, PermissionKey::PurchaseView->value);
        $filters = $request->validated();

        $query = $organization->goodsReceipts()->with(['lines', 'supplier', 'branch', 'location']);

        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (! empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        return GoodsReceiptResource::collection(
            $query->orderByDesc('created_at')->orderByDesc('id')->paginate($request->perPage())
        );
    }

    public function store(RecordGoodsReceiptRequest $request, Organization $organization, GoodsReceiptService $service)
    {
        $this->permission($request, $organization, PermissionKey::PurchaseReceive->value);

        try {
            $receipt = $service->record($organization, $request->validated(), $request->user());
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            abort(422, $exception->getMessage());
        }

        $receipt->load(['lines.product']);

        return (new GoodsReceiptResource($receipt))->response()->setStatusCode(201);
    }

    public function show(Request $request, Organization $organization, GoodsReceipt $receipt)
    {
        $this->permission($request, $organization, PermissionKey::PurchaseView->value);
        $this->assertBelongsToOrganization($receipt, $organization);

        return new GoodsReceiptResource($receipt->load(['lines.product', 'supplier', 'branch', 'location']));
    }

    private function assertBelongsToOrganization(GoodsReceipt $receipt, Organization $organization): void
    {
        abort_unless($receipt->organization_id === $organization->id, 404);
    }

    private function permission(Request $request, Organization $organization, string $permission): void
    {
        abort_unless(app(AuthorizationService::class)->allows($request->user(), $organization, $permission), 403);
    }
}
