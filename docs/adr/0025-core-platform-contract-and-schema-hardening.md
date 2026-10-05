# ADR 0025: Core Platform Contract and Schema Hardening

Status: ACCEPTED
Date: 2026-10-05

## Context

Sprint 0 established membership-based tenancy, but retained legacy user tenant and role columns alongside incomplete lifecycle and hierarchy constraints. Those parallel sources could make later tenant authorization ambiguous.

## Decision

Users are platform-level UUID identities with globally unique email and lifecycle status. They do not own organization, branch, or role fields. `OrganizationMembership` is the authoritative authorization relationship and is unique per organization/user pair. Only active memberships may establish context for active organizations.

Organizations are shared-schema tenants. Every tenant-owned business record will carry `organization_id`; branches are organization-owned secondary scope, not tenant substitutes. Core IDs are UUIDv7-style UUIDs. Core hierarchy deletion is restricted and lifecycle statuses express operational closure, suspension, or revocation instead of cascade deletion.

## Alternatives Considered

- Keep legacy `users.organization_id` and role fields: rejected because membership would no longer be the singular authorization source.
- Use branch as the tenant key: rejected because organizations may have multiple branches and tenant ownership remains organization-wide.
- Cascade deletion through organization hierarchy: rejected because business history must not be erased by parent deletion.

## Consequences

### Positive

- Clear authorization and tenant-isolation invariant.
- Offline-compatible identifiers and deterministic database constraints.
- Later RBAC can be added to membership without changing platform identity.

### Negative / Trade-offs

- Lifecycle management requires explicit application use cases.
- Tenant-scoped queries must continue to be deliberately scoped to validated context.

## Security / Tenancy Impact

Tenant selection is not authorization. The request must authenticate, resolve an active organization, prove active membership, then establish request-scoped tenant context. Suspended and revoked memberships never establish it.

## Offline / Data Impact

UUIDv7-style IDs remain safe for offline creation. The Sprint 1.1 schema is defined by the core Laravel migrations and must be applied through the repository migration workflow before deploying this version.

## Supersedes / Superseded By

Refines ADR 0023 without changing the shared-schema tenancy model.
