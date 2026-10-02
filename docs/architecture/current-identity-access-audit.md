# Current Identity and Access Audit (Sprint 0.10)

- **User schema:** UUIDv7 users have non-null `organization_id`, legacy `role`, `is_active`, hashed password cast, and soft deletes.
- **Organization relationship:** legacy one-user/one-organization remains. Sprint 0.10 adds/backfills `organization_memberships`; legacy columns remain.
- **Roles/memberships:** roles were on users; membership roles now authorize tenant context. No policies/gates or platform-role persistence exists.
- **Sanctum/tokens:** `HasApiTokens`, Sanctum, and custom UUID `personal_access_tokens` exist. No login/logout/me API route or token issuance exists.
- **Middleware/routes:** global API tenancy was removed. Health is public; tenant routes explicitly use `auth:sanctum` plus `tenant`. Middleware verifies active membership and clears context. Spatie's automatic domain tenant finder is disabled to prevent pre-routing organization queries on public routes.
- **Known gaps:** global identity/email migration, identity lifecycle enforcement, invitations, role-permission mappings, branch mappings, platform roles, auth endpoints, email verification, MFA, audit logs, and PostgreSQL-specific validation.
