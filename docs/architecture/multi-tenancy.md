# Multi-Tenancy

flowOS uses a shared PostgreSQL database and shared schema. `Organization` is the tenant discriminator and `Branch` is a subordinate organizational unit. Tenant-owned cloud data is scoped by `organization_id`.

Access is modeled as:

```text
User â†’ OrganizationMembership â†’ Organization
```

The active membership verifies organization access. Legacy `users.organization_id` and `users.role` fields are not authorization sources. The architecture does not use database-per-tenant, schema-per-tenant, or PostgreSQL RLS.

See [tenancy architecture](tenancy-architecture.md) for request lifecycle and query boundaries.
