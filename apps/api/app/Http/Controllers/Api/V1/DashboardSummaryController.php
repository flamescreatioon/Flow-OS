<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Authorization\AuthorizationService;
use App\Application\Reporting\DashboardSummaryService;
use App\Enums\PermissionKey;
use App\Http\Resources\DashboardSummaryResource;
use App\Models\Organization;
use Illuminate\Http\Request;

class DashboardSummaryController
{
    public function show(Request $request, Organization $organization)
    {
        abort_unless(
            app(AuthorizationService::class)->allows($request->user(), $organization, PermissionKey::OrganizationView->value),
            403
        );

        return new DashboardSummaryResource(
            app(DashboardSummaryService::class)->summary($organization)
        );
    }
}
