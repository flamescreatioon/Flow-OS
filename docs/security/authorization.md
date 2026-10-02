# Authorization

Tenant authorization is: authenticate -> resolve organization -> find active membership -> verify membership -> check role/permission -> establish tenant context. `X-Organization-ID` and subdomains select an organization only; neither is authorization.

Tenant routes compose `auth:sanctum` then `tenant`. The middleware returns 401 without identity, 400 when a required organization is absent, and 403 for inactive/absent membership. Resource handlers may use 404 where existence must not leak. Future business authorization uses Laravel policies/gates and application operations, not controller conditionals.

Public, authentication, tenant-authenticated, and platform routes remain separate. Health is public. Platform privilege must be an explicit path, never an admin bypass of tenant isolation.
