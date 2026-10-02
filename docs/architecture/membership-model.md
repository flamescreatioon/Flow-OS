# Organization Membership Model

`organization_memberships` is the participation boundary between a user and organization. It contains UUIDv7 `id`, `user_id`, `organization_id`, `role`, `status`, `joined_at`, and timestamps. `UNIQUE (organization_id, user_id)` prevents duplicate participation.

Status values are `invited`, `active`, `suspended`, and `revoked`. Sprint 0.10 enforces only that `active` memberships may authorize tenant context; invitation and lifecycle workflows are not implemented.

Legacy `users.organization_id` and `users.role` columns may remain for compatibility/default data, but they are not authorization sources. `OrganizationMembership` is the authoritative organization-access boundary. A later phase may remove or repurpose legacy columns after callers and global identity constraints are reconciled.

Membership roles are lowercase string identifiers for now. A future role-to-permission mapping may replace this without speculative role/permission tables. Invitations can create an `invited` membership and activate it after acceptance.
