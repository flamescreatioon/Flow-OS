<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Authorization\AuthorizationService;
use App\Enums\PermissionKey;
use App\Http\Resources\AuditEventResource;
use App\Models\Organization;
use Illuminate\Http\Request;

class AuditEventController
{
    public function index(Request $request, Organization $organization)
    {
        abort_unless(app(AuthorizationService::class)->allows($request->user(), $organization, PermissionKey::MembershipView->value), 403);

        $perPage = (int) $request->integer('per_page', 25);
        $perPage = max(1, min(100, $perPage));

        return AuditEventResource::collection(
            $organization->auditEvents()->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate($perPage)
        );
    }
}
