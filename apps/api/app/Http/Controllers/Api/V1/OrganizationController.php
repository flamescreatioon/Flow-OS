<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Authorization\AuthorizationService;
use App\Application\Organizations\OrganizationLifecycleService;
use App\Enums\MembershipStatus;
use App\Enums\OrganizationStatus;
use App\Enums\PermissionKey;
use App\Http\Requests\Organizations\ListOrganizationsRequest;
use App\Http\Requests\Organizations\StoreOrganizationRequest;
use App\Http\Requests\Organizations\UpdateOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use Illuminate\Http\Request;
use LogicException;

class OrganizationController
{
    public function index(ListOrganizationsRequest $request)
    {
        $filters = $request->validated();

        $query = Organization::whereHas('memberships', fn ($q) => $q->where('user_id', $request->user()->id)->where('status', MembershipStatus::Active));

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['q'])) {
            $query->search($filters['q'], ['name', 'slug']);
        }

        $sort = $filters['sort'] ?? 'name';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        $query->orderBy($column === 'created_at' ? 'created_at' : 'name', $direction);

        return OrganizationResource::collection($query->paginate($request->perPage()));
    }

    public function store(StoreOrganizationRequest $request, OrganizationLifecycleService $service)
    {
        return (new OrganizationResource($service->create($request->user(), $request->validated())))->response()->setStatusCode(201);
    }

    public function show(Request $request, Organization $organization)
    {
        $this->permission($request, $organization, PermissionKey::OrganizationView->value);

        return new OrganizationResource($organization);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization)
    {
        $this->permission($request, $organization, PermissionKey::OrganizationUpdate->value);
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

    private function permission(Request $request, Organization $organization, string $permission): void
    {
        abort_unless(app(AuthorizationService::class)->allows($request->user(), $organization, $permission), 403);
    }

    private function transition(Request $request, Organization $organization, OrganizationLifecycleService $service, OrganizationStatus $target)
    {
        $this->permission($request, $organization, PermissionKey::OrganizationLifecycle->value);
        try {
            return new OrganizationResource($service->transition($organization, $target, $request->user()));
        } catch (LogicException) {
            abort(422, 'Invalid organization lifecycle transition.');
        }
    }
}
