<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Organizations\OrganizationLifecycleService;
use App\Enums\MembershipStatus;
use App\Enums\OrganizationStatus;
use App\Http\Requests\Organizations\StoreOrganizationRequest;
use App\Http\Requests\Organizations\UpdateOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use Illuminate\Http\Request;
use LogicException;

class OrganizationController
{
    public function index(Request $request)
    {
        return OrganizationResource::collection(Organization::whereHas('memberships', fn ($q) => $q->where('user_id', $request->user()->id)->where('status', MembershipStatus::Active))->get());
    }

    public function store(StoreOrganizationRequest $request, OrganizationLifecycleService $service)
    {
        return (new OrganizationResource($service->create($request->user(), $request->validated())))->response()->setStatusCode(201);
    }

    public function show(Request $request, Organization $organization)
    {
        $this->member($request, $organization);

        return new OrganizationResource($organization);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization)
    {
        $this->member($request, $organization);
        $organization->fill($request->validated());
        if ($organization->isDirty('currency_code')) {
            $organization->currency = $organization->currency_code;
        } $organization->save();

        return new OrganizationResource($organization);
    }

    public function suspend(Request $request, Organization $organization, OrganizationLifecycleService $service)
    {
        return $this->transition($request, $organization, $service, OrganizationStatus::Suspended);
    }

    public function activate(Request $request, Organization $organization, OrganizationLifecycleService $service)
    {
        return $this->transition($request, $organization, $service, OrganizationStatus::Active);
    }

    public function close(Request $request, Organization $organization, OrganizationLifecycleService $service)
    {
        return $this->transition($request, $organization, $service, OrganizationStatus::Closed);
    }

    private function member(Request $request, Organization $organization): void
    {
        abort_unless($organization->memberships()->where('user_id', $request->user()->id)->where('status', MembershipStatus::Active)->exists(), 403);
    }

    private function transition(Request $request, Organization $organization, OrganizationLifecycleService $service, OrganizationStatus $target)
    {
        $this->member($request, $organization);
        try {
            return new OrganizationResource($service->transition($organization, $target));
        } catch (LogicException) {
            abort(422,'Invalid organization lifecycle transition.');
        }
    }
}
