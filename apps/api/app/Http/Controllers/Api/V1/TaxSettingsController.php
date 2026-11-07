<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Authorization\AuthorizationService;
use App\Application\Invoicing\TaxSettingsService;
use App\Enums\PermissionKey;
use App\Http\Requests\Invoicing\UpdateTaxSettingsRequest;
use App\Http\Resources\TaxSettingsResource;
use App\Models\Organization;
use Illuminate\Http\Request;
use LogicException;

class TaxSettingsController
{
    public function show(Request $request, Organization $organization)
    {
        $this->permission($request, $organization, PermissionKey::InvoiceView->value);

        return new TaxSettingsResource(app(TaxSettingsService::class)->show($organization));
    }

    public function update(UpdateTaxSettingsRequest $request, Organization $organization, TaxSettingsService $service)
    {
        $this->permission($request, $organization, PermissionKey::InvoiceManage->value);

        try {
            $settings = $service->update($organization, $request->validated(), $request->user());
        } catch (LogicException $exception) {
            abort(422, $exception->getMessage());
        }

        // An update is always 200, even when the row was created here.
        return (new TaxSettingsResource($settings))->response()->setStatusCode(200);
    }

    private function permission(Request $request, Organization $organization, string $permission): void
    {
        abort_unless(app(AuthorizationService::class)->allows($request->user(), $organization, $permission), 403);
    }
}
