<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Authorization\AuthorizationService;
use App\Application\Invoicing\InvoiceService;
use App\Enums\PermissionKey;
use App\Http\Requests\Invoicing\CreateInvoiceRequest;
use App\Http\Requests\Invoicing\ListInvoiceDeliveriesRequest;
use App\Http\Requests\Invoicing\ListInvoicesRequest;
use App\Http\Requests\Invoicing\SendInvoiceRequest;
use App\Http\Requests\Invoicing\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\NotificationDeliveryResource;
use App\Models\Invoice;
use App\Models\Organization;
use Illuminate\Http\Request;
use InvalidArgumentException;
use LogicException;

class InvoiceController
{
    public function index(ListInvoicesRequest $request, Organization $organization)
    {
        $this->permission($request, $organization, PermissionKey::InvoiceView->value);
        $filters = $request->validated();

        $query = $organization->invoices()->with(['lines', 'customer', 'branch']);

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return InvoiceResource::collection(
            $query->orderByDesc('created_at')->orderByDesc('id')->paginate($request->perPage())
        );
    }

    public function store(CreateInvoiceRequest $request, Organization $organization, InvoiceService $service)
    {
        $this->permission($request, $organization, PermissionKey::InvoiceCreate->value);

        try {
            $invoice = $service->createDraft($organization, $request->validated(), $request->user());
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            abort(422, $exception->getMessage());
        }

        return (new InvoiceResource($invoice->load(['lines', 'customer', 'branch'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Organization $organization, Invoice $invoice)
    {
        $this->permission($request, $organization, PermissionKey::InvoiceView->value);
        $this->assertBelongsToOrganization($invoice, $organization);

        return new InvoiceResource($invoice->load(['lines', 'customer', 'branch']));
    }

    public function update(UpdateInvoiceRequest $request, Organization $organization, Invoice $invoice, InvoiceService $service)
    {
        $this->permission($request, $organization, PermissionKey::InvoiceUpdate->value);
        $this->assertBelongsToOrganization($invoice, $organization);

        try {
            $invoice = $service->updateDraft($organization, $invoice, $request->validated(), $request->user());
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            abort(422, $exception->getMessage());
        }

        return new InvoiceResource($invoice);
    }

    public function issue(Request $request, Organization $organization, Invoice $invoice, InvoiceService $service)
    {
        $this->permission($request, $organization, PermissionKey::InvoiceIssue->value);
        $this->assertBelongsToOrganization($invoice, $organization);

        try {
            $invoice = $service->issue($organization, $invoice, $request->user());
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        }

        return new InvoiceResource($invoice);
    }

    public function void(Request $request, Organization $organization, Invoice $invoice, InvoiceService $service)
    {
        $this->permission($request, $organization, PermissionKey::InvoiceVoid->value);
        $this->assertBelongsToOrganization($invoice, $organization);

        try {
            $invoice = $service->void($organization, $invoice, $request->user());
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        }

        return new InvoiceResource($invoice);
    }

    public function send(SendInvoiceRequest $request, Organization $organization, Invoice $invoice, InvoiceService $service)
    {
        $this->permission($request, $organization, PermissionKey::InvoiceSend->value);
        $this->assertBelongsToOrganization($invoice, $organization);

        try {
            $delivery = $service->send($organization, $invoice, $request->validated(), $request->user());
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        }

        return (new NotificationDeliveryResource($delivery))->response()->setStatusCode(202);
    }

    public function deliveries(ListInvoiceDeliveriesRequest $request, Organization $organization, Invoice $invoice)
    {
        $this->permission($request, $organization, PermissionKey::InvoiceView->value);
        $this->assertBelongsToOrganization($invoice, $organization);

        $query = $invoice->notificationDeliveries()
            ->where('organization_id', $organization->id)
            ->orderByDesc('created_at');

        return NotificationDeliveryResource::collection($query->paginate($request->perPage()));
    }

    private function assertBelongsToOrganization(Invoice $invoice, Organization $organization): void
    {
        abort_unless($invoice->organization_id === $organization->id, 404);
    }

    private function permission(Request $request, Organization $organization, string $permission): void
    {
        abort_unless(app(AuthorizationService::class)->allows($request->user(), $organization, $permission), 403);
    }
}
