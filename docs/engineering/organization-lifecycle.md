# Organization Lifecycle

An authenticated active user can create an organization without existing tenant context. Creation is one database transaction: it creates the organization and the creator's active membership with `joined_at`; it creates no branch, role, device, or subscription.

Organizations default to `NG`, `NGN`, `Africa/Lagos`, and `active`. Slugs are server-generated and globally unique with numeric suffixes. Name changes do not change slugs.

Listing is platform discovery limited to active memberships. Detail, profile updates, and lifecycle actions are governed by Sprint 1.6 organization-scoped RBAC.

Allowed transitions are active→suspended/closed and suspended→active/closed. Closed is terminal. Suspended and closed organizations cannot establish tenant context.
