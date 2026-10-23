# Membership lifecycle and invitations

An invitation is not a membership. Sending an invitation creates neither a User nor an OrganizationMembership; an unknown recipient registers normally and can then accept with the same normalized (trimmed, lowercase) email address.

Invitation tokens are random 64-character secrets. FlowOS stores only their SHA-256 hash. The raw token is used only in the notification URL, `${FRONTEND_URL}/invitations/{token}`, and is never returned by the API or persisted. Invitations expire after `ORGANIZATION_INVITATION_LIFETIME_HOURS` (168 hours by default). Resend is allowed for pending and expired invitations, rotates the token and expiry, and immediately invalidates the old token. Accepted, declined, and cancelled invitations are terminal; cancellation requires a new invitation later.

Acceptance is transactional: a valid pending invitation and matching active platform user create an active membership with `joined_at`, then mark the invitation accepted. Existing active, suspended, or revoked memberships prevent acceptance; no membership lifecycle is bypassed. Decline records `declined_at` and creates no membership. Inspection returns only organization name, email, expiry, and lifecycle state.

Memberships transition `active -> suspended|revoked` and `suspended -> active|revoked`; `revoked` is terminal. Suspending or revoking immediately prevents tenant context, without disabling the platform-level user or access to other organizations. A member cannot suspend or revoke themselves, and every organization must retain at least one active membership. These checks execute in database transactions.

Sprint 1.6 RBAC governs invitation and membership management. Authentication identifies the user; membership identifies accessible organizations; RBAC determines permitted actions within them.
