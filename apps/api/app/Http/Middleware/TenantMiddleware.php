<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);

        if (! $tenant) {
            Log::notice('Tenant context was not supplied.', ['event' => 'tenant.context.missing']);

            return response()->json([
                'message' => 'An organization context is required.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $request->user();
        if (! $user) {
            Log::notice('Tenant access denied for an unauthenticated request.', ['event' => 'tenant.access.denied']);

            return response()->json([
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (! $user->hasActiveMembershipIn($tenant)) {
            Log::notice('Tenant access denied for inactive or missing membership.', ['event' => 'tenant.access.denied']);

            return response()->json([
                'message' => 'You are not authorized for this organization.',
            ], Response::HTTP_FORBIDDEN);
        }

        $tenant->makeCurrent();
        $request->attributes->set('organization_id', $tenant->getKey());
        Log::info('Tenant context established.', [
            'event' => 'tenant.context.established',
            'organization_id' => $tenant->getKey(),
            'user_id' => $user->getAuthIdentifier(),
        ]);

        try {
            return $next($request);
        } finally {
            Tenant::forgetCurrent();
        }
    }

    protected function resolveTenant(Request $request): ?Tenant
    {
        // Resolution selects an organization only. handle() authorizes that selection
        // against the authenticated user's active membership before making it current.

        // 1. Subdomain resolution (highest priority)
        $subdomain = $this->extractSubdomain($request->getHost());
        if ($subdomain) {
            return Organization::where('slug', $subdomain)->first();
        }

        // 2. X-Organization-ID header (selection only, not authorization)
        $organizationId = $request->header('X-Organization-ID');
        if ($organizationId) {
            return Organization::find($organizationId);
        }

        return null;
    }

    protected function extractSubdomain(string $domain): ?string
    {
        $parts = explode('.', $domain);
        if (count($parts) > 2) {
            return $parts[0];
        }

        return null;
    }
}
