# ADR 0023: Shared-Schema Tenancy and Membership Authorization

Status: ACCEPTED
Date: 2026-10-02
Supersedes: ADR 0008, ADR 0010

## Context

flowOS needs a tenancy boundary that is operationally simple while preventing cross-organization access. Earlier ADRs selected shared schema but also described JWT/session tenant authority and PostgreSQL RLS in ways that no longer match the implemented security foundation.

## Decision

Use one shared PostgreSQL database and schema. `Organization` is the tenant and tenant-owned cloud records carry `organization_id`; `Branch` is subordinate to its organization. A subdomain or supported `X-Organization-ID` header proposes tenant context only. The authenticated user must have an active `OrganizationMembership` before tenant context is established. Authorization remains explicit after membership verification, and tenant context is cleared with guaranteed cleanup.

PostgreSQL RLS is not enabled. Application-layer tenant scoping, membership verification, database constraints, and negative-path tests are the active controls. User identity is independent of organizations; legacy user organization/role fields are not authorization sources.

## Alternatives Considered

- Database-per-tenant: stronger physical separation but disproportionate operational and migration cost at this stage.
- Schema-per-tenant: similar migration and connection-management complexity.
- PostgreSQL RLS: retained as a possible future defense-in-depth evaluation, not current architecture.

## Consequences

### Positive

- One migration path and lower operational complexity.
- Clear organization-scoped data model and membership boundary.

### Negative / Trade-offs

- Application-layer tenancy defects can cause severe cross-tenant exposure.
- All tenant-owned queries and workers require deliberate validated context.

## Security / Tenancy Impact

Tenant selection never grants authorization. IDs are not authorization. Queue workers must explicitly establish and clear tenant context.

## References

- [Tenancy architecture](../architecture/tenancy-architecture.md)
- [Identity and access architecture](../architecture/identity-access-architecture.md)
- [Tenant isolation](../security/tenant-isolation.md)
