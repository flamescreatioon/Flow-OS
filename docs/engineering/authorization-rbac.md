# Organization-scoped authorization

Authentication answers who a platform user is. OrganizationMembership answers which organization an active user may access. RBAC answers what that membership may do in that organization.

Permissions are a global canonical catalogue; roles are scoped to one organization. Built-in Owner, Admin, and Member roles are system roles. Owner dynamically resolves every current permission, including permissions registered by future modules. Admin has operational organization, branch, membership, and role-view permissions; Member has organization and branch read permissions only.

Memberships may hold multiple roles and their effective permissions are the union. The backend remains authoritative; `GET /organizations/{organization}/me/permissions` is only for client capability rendering. Role assignment is organization-scoped. Only an active Owner may grant or remove Owner, and an active organization must retain at least one active Owner. System roles are immutable and custom roles cannot use reserved normalized identities.

The RBAC backfill is idempotent: it creates system roles, selects the earliest active membership as Owner when none exists, and assigns other role-less active memberships Member. Organizations without active memberships are intentionally left without an Owner and require administrative remediation; no user or membership is fabricated.
