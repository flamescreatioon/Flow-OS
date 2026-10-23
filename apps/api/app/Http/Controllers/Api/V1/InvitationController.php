<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Authorization\AuthorizationService;
use App\Application\Memberships\InvitationService;
use App\Enums\OrganizationStatus;
use App\Enums\PermissionKey;
use App\Http\Requests\Invitations\ListInvitationsRequest;
use App\Http\Resources\OrganizationInvitationResource;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use Illuminate\Http\Request;
use LogicException;

class InvitationController
{
    public function index(ListInvitationsRequest $request, Organization $organization)
    {
        $this->permission($request, $organization);
        $filters = $request->validated();

        $query = $organization->invitations()->with('inviter');

        if (! empty($filters['state'])) {
            $state = $filters['state'];
            $now = now();
            $query->when($state === 'pending', fn ($q) => $q->whereNull('accepted_at')->whereNull('declined_at')->whereNull('cancelled_at')->where('expires_at', '>', $now))
                ->when($state === 'accepted', fn ($q) => $q->whereNotNull('accepted_at'))
                ->when($state === 'declined', fn ($q) => $q->whereNotNull('declined_at'))
                ->when($state === 'cancelled', fn ($q) => $q->whereNotNull('cancelled_at'))
                ->when($state === 'expired', fn ($q) => $q->whereNull('accepted_at')->whereNull('declined_at')->whereNull('cancelled_at')->where('expires_at', '<=', $now));
        }

        if (! empty($filters['q'])) {
            $search = '%'.str_replace(['%', '_'], '', $filters['q']).'%';
            $query->where('email', 'like', $search);
        }

        return OrganizationInvitationResource::collection($query->orderBy('created_at', 'desc')->paginate($request->perPage()));
    }

    public function store(Request $request, Organization $organization, InvitationService $service)
    {
        $this->permission($request, $organization);
        $data = $request->validate(['email' => ['required', 'email']]);

        try {
            [$invitation, $token] = $service->create($organization, $request->user(), $data['email']);
            $service->send($invitation->load('organization'), $token);

            return (new OrganizationInvitationResource($invitation->load('inviter')))->response()->setStatusCode(201);
        } catch (LogicException) {
            abort(422);
        }
    }

    public function resend(Request $request, Organization $organization, OrganizationInvitation $invitation, InvitationService $service)
    {
        $this->permission($request, $organization);
        $this->belongsToOrganization($invitation, $organization);

        try {
            [$invitation, $token] = $service->issue($invitation);
            $service->send($invitation->load('organization'), $token);

            return new OrganizationInvitationResource($invitation->load('inviter'));
        } catch (LogicException) {
            abort(422);
        }
    }

    public function cancel(Request $request, Organization $organization, OrganizationInvitation $invitation)
    {
        $this->permission($request, $organization);
        $this->belongsToOrganization($invitation, $organization);
        abort_unless($invitation->state() === 'pending', 422);
        $invitation->cancelled_at = now();
        $invitation->save();

        return new OrganizationInvitationResource($invitation->load('inviter'));
    }

    public function inspect(string $token, InvitationService $service)
    {
        try {
            $invitation = $service->inspect($token);
        } catch (LogicException) {
            abort(404);
        }

        return response()->json(['data' => [
            'organization_name' => $invitation->organization->name,
            'email' => $invitation->email,
            'expires_at' => $invitation->expires_at,
            'state' => $invitation->state(),
        ]]);
    }

    public function accept(Request $request, string $token, InvitationService $service)
    {
        try {
            return new OrganizationInvitationResource($service->accept($token, $request->user()));
        } catch (LogicException) {
            abort(404);
        }
    }

    public function decline(Request $request, string $token, InvitationService $service)
    {
        try {
            return new OrganizationInvitationResource($service->accept($token, $request->user(), true));
        } catch (LogicException) {
            abort(404);
        }
    }

    private function permission(Request $request, Organization $organization): void
    {
        abort_unless($organization->status === OrganizationStatus::Active && app(AuthorizationService::class)->allows($request->user(), $organization, PermissionKey::MembershipInvite->value), 403);
    }

    private function belongsToOrganization(OrganizationInvitation $invitation, Organization $organization): void
    {
        abort_unless($invitation->organization_id === $organization->id, 404);
    }
}
