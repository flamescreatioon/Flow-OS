<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Authorization\AuthorizationService;
use App\Application\Invoicing\CustomerService;
use App\Enums\PermissionKey;
use App\Http\Requests\Invoicing\ListCustomersRequest;
use App\Http\Requests\Invoicing\StoreCustomerRequest;
use App\Http\Requests\Invoicing\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\Organization;
use Illuminate\Http\Request;
use LogicException;

class CustomerController
{
    public function index(ListCustomersRequest $request, Organization $organization)
    {
        $this->permission($request, $organization, PermissionKey::CustomerView->value);
        $filters = $request->validated();

        $query = $organization->customers();

        $archived = $filters['archived'] ?? 'false';

        if ($archived === 'true') {
            $query->whereNotNull('archived_at');
        } elseif ($archived === 'all') {
            // Both archived and active customers are returned.
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

        return CustomerResource::collection($query->paginate($request->perPage()));
    }

    public function store(StoreCustomerRequest $request, Organization $organization, CustomerService $service)
    {
        $this->permission($request, $organization, PermissionKey::CustomerCreate->value);

        try {
            $customer = $service->create($organization, $request->validated());
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        }

        return (new CustomerResource($customer))->response()->setStatusCode(201);
    }

    public function show(Request $request, Organization $organization, Customer $customer)
    {
        $this->permission($request, $organization, PermissionKey::CustomerView->value);
        $this->assertBelongsToOrganization($customer, $organization);

        return new CustomerResource($customer);
    }

    public function update(UpdateCustomerRequest $request, Organization $organization, Customer $customer, CustomerService $service)
    {
        $this->permission($request, $organization, PermissionKey::CustomerUpdate->value);
        $this->assertBelongsToOrganization($customer, $organization);

        try {
            $customer = $service->update($organization, $customer, $request->validated());
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        }

        return new CustomerResource($customer);
    }

    public function archive(Request $request, Organization $organization, Customer $customer, CustomerService $service)
    {
        $this->permission($request, $organization, PermissionKey::CustomerArchive->value);
        $this->assertBelongsToOrganization($customer, $organization);

        try {
            $customer = $service->archive($organization, $customer);
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        }

        return new CustomerResource($customer);
    }

    private function assertBelongsToOrganization(Customer $customer, Organization $organization): void
    {
        abort_unless($customer->organization_id === $organization->id, 404);
    }

    private function permission(Request $request, Organization $organization, string $permission): void
    {
        abort_unless(app(AuthorizationService::class)->allows($request->user(), $organization, $permission), 403);
    }
}
