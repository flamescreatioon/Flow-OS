# Organization Membership Model

`organization_memberships` is the participation boundary between a user and organization. It contains UUIDv7 `id`, `user_id`, `organization_id`, `role`, `status`, `joined_at`, and timestamps. `UNIQUE (organization_id, user_id)` prevents duplicate participation.

Status values are `invited`, `active`, `suspended`, and `revoked`. Sprint 0.10 enforces only that `active` memberships may authorize tenant context; invitation and lifecycle workflows are not implemented.

Current: `users.organization_id` is non-null and `users.role` is organization-role storage. Sprint 0.10 adds memberships and backfills one active membership for every existing user, retaining legacy columns. A later phase must reconcile duplicate user records by identity, establish global identity constraints, update callers, then remove or repurpose legacy columns as a default-organization preference.

Membership roles are lowercase string identifiers for now. A future role-to-permission mapping may replace this without speculative role/permission tables. Invitations can create an `invited` membership and activate it after acceptance.
