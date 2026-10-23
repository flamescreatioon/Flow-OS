<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Authorization\AuthorizationService;
use App\Application\Memberships\MembershipLifecycleService;
use App\Enums\MembershipStatus;
use App\Enums\PermissionKey;
use App\Http\Requests\Memberships\ListMembershipsRequest;
use App\Http\Resources\OrganizationMembershipResource;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use Illuminate\Http\Request;
use LogicException;

class MembershipController
{
    public function index(ListMembershipsRequest $request, Organization $organization)
    {
        $this->permission($request, $organization, PermissionKey::MembershipView->value);
        $filters = $request->validated();

        $query = $organization->memberships()->with('user');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['q'])) {
            $query->whereHas('user', fn ($q) => $q->search($filters['q'], ['name', 'email']));
        }

        return OrganizationMembershipResource::collection($query->orderBy('created_at', 'desc')->paginate($request->perPage()));
    }

    public function show(Request $request, Organization $organization, OrganizationMembership $membership)
    {
        $this->permission($request, $organization, PermissionKey::MembershipView->value);
        $this->belongsToOrganization($membership, $organization);

        return new OrganizationMembershipResource($membership->load('user'));
    }

    public function suspend(Request $request, Organization $organization, OrganizationMembership $membership, MembershipLifecycleService $service)
    {
        return $this->transition($request, $organization, $membership, $service, MembershipStatus::Suspended, PermissionKey::MembershipSuspend->value);
    }

    public function activate(Request $request, Organization $organization, OrganizationMembership $membership, MembershipLifecycleService $service)
    {
        return $this->transition($request, $organization, $membership, $service, MembershipStatus::Active, PermissionKey::MembershipActivate->value);
    }

    public function revoke(Request $request, Organization $organization, OrganizationMembership $membership, MembershipLifecycleService $service)
    {
        return $this->transition($request, $organization, $membership, $service, MembershipStatus::Revoked, PermissionKey::MembershipRevoke->value);
    }

    private function transition(Request $request, Organization $organization, OrganizationMembership $membership, MembershipLifecycleService $service, MembershipStatus $status, string $permission)
    {
        $this->permission($request, $organization, $permission);
        $this->belongsToOrganization($membership, $organization);

        try {
            $membership = $service->transition($membership, $status, $request->user());

            return new OrganizationMembershipResource($membership->load('user'));
        } catch (LogicException) {
            abort(422);
        }
    }

    private function permission(Request $request, Organization $organization, string $permission): void
    {
        abort_unless(app(AuthorizationService::class)->allows($request->user(), $organization, $permission), 403);
    }

    private function belongsToOrganization(OrganizationMembership $membership, Organization $organization): void
    {
        abort_unless($membership->organization_id === $organization->id, 404);
    }
}
