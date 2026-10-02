# Tenant Resolution

`Organization` is the tenant domain entity. A request can propose an organization selector, but the selector is only context selection; it is never authorization.

## Request lifecycle

```text
Request
  â†’ authentication
  â†’ tenant selector (subdomain or supported header)
  â†’ active OrganizationMembership verification
  â†’ tenant context
  â†’ authorization and operation
  â†’ guaranteed context cleanup
```

The public health endpoint has no tenant requirement. Tenant-owned queries use the validated server context, never raw client input. Cross-tenant activity requires an explicit platform-level purpose and authorization.

JWT claims and session fallbacks are not tenant-resolution authorities in the current architecture. Future queue workers must establish and clear tenant and logging context explicitly.

See [tenancy architecture](tenancy-architecture.md) and [identity and access architecture](identity-access-architecture.md).
