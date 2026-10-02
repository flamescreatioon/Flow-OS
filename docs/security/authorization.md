# Authorization

Tenant authorization is: authenticate -> resolve organization -> find active membership -> verify membership -> check role/permission -> establish tenant context. `X-Organization-ID`, subdomains, branch IDs, and UUIDs select or identify only; none is authorization. Tenant leakage is a critical-severity defect.

Tenant routes compose `auth:sanctum` then `tenant`. The middleware returns 401 without identity, 400 when a required organization is absent, and 403 for inactive/absent membership. Resource handlers may use 404 where existence must not leak. Future business authorization uses Laravel policies/gates and application operations, not controller conditionals.

Public, authentication, tenant-authenticated, and platform routes remain separate. Health is public. Platform privilege must be an explicit path, never an admin bypass of tenant isolation. Authorization occurs before object use: tenant ownership, active membership, then permission. Future roles/policies belong in application/domain operations and Laravel policies/gates, not controller conditionals. Legacy `users.organization_id` and `users.role` are compatibility fields, not authorization sources; memberships are authoritative and only `active` grants access.
