# ADR 0021: Establish Security Foundation

Status: ACCEPTED
Date: 2026-10-02

## Decision

flowOS uses Laravel Sanctum as its authentication foundation. First-party browser requests use Sanctum's configured stateful cookie/CSRF flow; mobile and desktop use revocable Sanctum personal-access tokens, with mobile tokens held in platform secure storage. Tenant identity selection is never authorization: active organization membership is required before tenant context is established, and context is cleared in `finally`.

The shared-schema architecture retains application-layer tenant isolation; PostgreSQL RLS is not currently enabled. Security-sensitive model fields are not mass assignable. Credentialed CORS uses environment-configured explicit origins, never a wildcard.

Dependency monitoring is configured through Dependabot and CodeQL analysis is configured for JavaScript/TypeScript and C#. Hosted GitHub security features are not represented as active without remote confirmation.

## Consequences

Security endpoints, permissions, device management, rate-limit thresholds, and headers remain scoped future work. High/critical dependencies without a compatible patch are tracked as production blockers rather than bypassed with suppressions or blind major upgrades.
