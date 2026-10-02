# Tenant Resolution

## Overview

This document describes how flowOS resolves the current tenant (organization) for incoming requests.

## Resolution Priority

The tenant is resolved in the following priority order:

1. **Subdomain** - `org.flowos.local` → lookup by slug
2. **Header** - `X-Organization-ID` → direct lookup (for API clients)
3. **JWT Claim** - `organization_id` from token
4. **Session** - `organization_id` from session

## Resolution Flow

```php
class TenantResolver {
    public function resolve(Request $request): ?Organization {
        // 1. Subdomain (highest priority for web)
        $subdomain = $this->extractSubdomain($request->getHost());
        if ($subdomain) {
            $org = Organization::where('slug', $subdomain)->first();
            if ($org) return $org;
        }

        // 2. Header (for API clients)
        $headerOrgId = $request->header('X-Organization-ID');
        if ($headerOrgId) {
            $org = Organization::find($headerOrgId);
            if ($org) return $org;
        }

        // 3. JWT claim (from authenticated user)
        $jwtOrgId = $request->user()?->getAttribute('organization_id');
        if ($jwtOrgId) {
            $org = Organization::find($jwtOrgId);
            if ($org) return $org;
        }

        // 4. Session (for web sessions)
        $sessionOrgId = $request->session()->get('organization_id');
        if ($sessionOrgId) {
            $org = Organization::find($sessionOrgId);
            if ($org) return $org;
        }

        return null;
    }
}
```

## Resolution Rules

1. **First match wins** - Resolution stops at first successful match
2. **Verification required** - Every resolved organization must be verified:
   - Organization exists and is active
   - Requesting user/device has access to the organization
3. **Fallback** - If no tenant resolved, request proceeds without tenant context (public endpoints only)

## Security Rules

### Tenant Identification ≠ Tenant Authorization

**Critical**: Resolving an organization from a request does NOT automatically grant access.

```php
// DANGEROUS - Never do this
public function handle(Request $request, Closure $next) {
    $orgId = $request->header('X-Organization-ID');
    $org = Organization::find($orgId);
    Multitenancy::setTenant($org); // DANGEROUS: No authorization check!
    return $next($request);
}

// SAFE - Authorization check required
public function handle(Request $request, Closure $next) {
    $orgId = $request->header('X-Organization-ID');
    $org = Organization::find($orgId);

    // Verify the authenticated user has access to this organization
    if ($org && $request->user()->canAccessOrganization($org->id)) {
        Multitenancy::makeCurrent($org);
    }

    return $next($request);
}
```

### Resolution Rules

1. **Never trust client-provided organization IDs** without verification
2. **Always verify** the authenticated identity has access to the resolved organization
3. **Log resolution attempts** for audit trail
4. **Reject** resolution if user cannot access the organization

## Resolution Contexts

### Web Requests (Browser)
- Primary: Subdomain (`org.flowos.local`)
- Fallback: Session `organization_id`

### API Clients
- Primary: `X-Organization-ID` header
- Fallback: JWT `organization_id` claim

### Background Jobs
- Job payload MUST include `organization_id`
- Worker validates organization exists and is active
- Context established before job execution

### Background Workers
```php
class ProcessSale implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $saleId,
        public string $organizationId  // Explicit context
    ) {}

    public function handle() {
        // Explicitly set tenant context
        Multitenancy::setTenant($this->organizationId);

        try {
            // Process with tenant context
        } finally {
            Multitenancy::forgetTenant();
        }
    }
}
```

## Current Implementation Status

### Current Implementation
- `TenantMiddleware` resolves from subdomain or `X-Organization-ID` header only on explicitly composed tenant routes.
- It requires an authenticated user and active `organization_memberships` record before making the tenant current, then clears context after the response.
- Public routes, including health, are not tenant-aware.
- Spatie's automatic `tenant_finder` is disabled. Its `DomainTenantFinder` would otherwise run during package boot before route middleware and query organizations for every HTTP request.

### Known Gaps
1. No session/default-organization selector is implemented.
2. No platform-access route group is implemented.
3. No branch authorization mapping is implemented.

## Implementation Priority

1. **Phase 1** (Current): authorized subdomain/header selection.
2. **Phase 2**: session/default organization selection.
3. **Phase 3**: branch and platform authorization paths.
