# Database Tenancy

PostgreSQL uses a shared schema. `Organization` is the tenant domain entity and tenant-owned cloud tables carry `organization_id`, usually with appropriate foreign keys, indexes, tenant-scoped uniqueness, and query scoping.

`Branch` belongs to an organization. A branch is not a tenant boundary that substitutes for organization authorization.

## Access boundary

`User â†’ OrganizationMembership â†’ Organization` is the authoritative organization-access model. Legacy `users.organization_id` and `users.role` fields are compatibility/default data only and must not authorize tenant context.

A supported subdomain or `X-Organization-ID` header can propose context. Active membership verification authorizes it. Tenant-owned queries use validated server context, and request tenant context is cleared after each request.

## Data integrity

- Tenant-owned foreign-key relationships must preserve the organization boundary.
- Tenant-scoped uniqueness must include `organization_id` where the business rule requires it.
- Cross-tenant queries require explicit platform-level authorization and purpose.
- Laravel migrations are the canonical cloud-schema evolution mechanism.

The architecture does not use database-per-tenant, schema-per-tenant, or PostgreSQL RLS. See [tenancy architecture](../architecture/tenancy-architecture.md) and [tenant isolation](../security/tenant-isolation.md).
