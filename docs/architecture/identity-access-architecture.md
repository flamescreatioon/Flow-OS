# Identity and Access Architecture

Identity is independent of organizations. Authoritative organization access is `User → OrganizationMembership → Organization`; an active membership authorizes access. Legacy `users.organization_id` and `users.role` are compatibility/default data and never authorization sources.

Roles are organization-scoped (`owner`, `admin`, `manager`, `staff`, `viewer`) and permissions will be enforced in application/domain operations and policies. Platform roles are separate. Web uses Sanctum stateful cookies/CSRF; mobile and desktop use revocable Sanctum PAT/device-aware direction. A device is distinct from a user and remains architected until registration/revocation exists.
