# Current Tenancy Audit

This historical audit has been consolidated after the Sprint 0 tenancy and security work. It is retained as a traceable audit entry, not as a description of the current implementation.

## Current authoritative state

- flowOS uses one shared PostgreSQL schema with `organization_id` tenant discrimination.
- `Organization` selects tenant context; `OrganizationMembership` with active status authorizes organization access.
- Tenant middleware uses validated context and guarantees cleanup after successful requests, denials, and downstream exceptions.
- Tenant-owned queries use validated server context. Raw `X-Organization-ID` input is not authoritative.
- Queue/worker work remains responsible for explicit tenant and log-context establishment and cleanup.

Earlier findings about database-per-tenant package traits, absent membership verification, JWT claim fallback, and uncleared middleware describe superseded implementation history. They are not current architecture and should not guide new work.

See [tenancy architecture](tenancy-architecture.md), [identity and access architecture](identity-access-architecture.md), and [current security audit](../security/current-security-audit.md).
