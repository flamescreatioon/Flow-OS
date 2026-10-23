# Identity and Access Architecture

`User` is a human identity, not permanent ownership by one organization. A user may have zero, one, or many organization memberships. Account state (`active`, `suspended`, `disabled`) is distinct from membership status.

RBAC is membership-scoped: `User -> OrganizationMembership -> role -> permissions`. Stored role identifiers are `owner`, `admin`, `manager`, `staff`, and `viewer`. They are provisional; future permission identifiers use `resource.action` (for example, `inventory.view`). An owner is organization-specific, not a platform administrator; transfer and last-owner protections are deferred.

Platform actors (`platform_admin`, `support_operator`, `system_service`) are separate from memberships and never receive an implicit tenant-isolation bypass. A later branch-access mapping may grant all or selected branches, but never access outside the membership's organization. User, device, and session/token identity remain distinct.

A previously authenticated device may work offline only inside cached authorization limits. Future offline clients need cached identity, membership, permissions, expiry, and revalidation rules; offline mode creates no new authority.

## Multi-factor authentication

Multi-factor authentication uses a time-based one-time password from an authenticator application, with single-use recovery codes as the fallback. It is enforced where a credential is issued, not on every request. A correct password for an enrolled account produces a short-lived, single-use challenge instead of a session or token; the credential only exists once the challenge is redeemed with a second factor. The shared secret is encrypted at rest, recovery codes are hashed, a used code cannot be replayed inside its validity window, and a challenge cannot be redeemed twice. The second factor establishes platform identity only and never substitutes for organization membership. See [ADR 0032](../adr/0032-multi-factor-authentication.md).
