# Authentication

Sprint 1.2 separates identity authentication from tenancy authorization.

## Clients

The web application uses Sanctum's stateful SPA flow: the browser obtains the CSRF cookie, authenticates through the session endpoint, and uses HttpOnly session cookies. Browser clients must not store bearer tokens in localStorage or sessionStorage.

Flutter and desktop clients exchange credentials and a client name for a Sanctum personal access token. They store that token only in platform secure storage and send it as a Bearer token.

## Account lifecycle and revocation

Every protected authentication route uses `auth:sanctum` followed by `active-user`. Only `active` users may continue. This rechecks account status for sessions and already-issued personal access tokens. Logout revokes the current token or invalidates the current session; logout-all revokes every personal access token and safely clears a session when present.

Password reset uses Laravel's password broker and generic reset-request responses to avoid account enumeration. A successful reset revokes all personal access tokens. Email verification uses Laravel notifications and temporary signed URLs; verification is not globally required in this sprint.

## Multi-factor authentication

Multi-factor authentication uses a time-based one-time password (RFC 6238) from an authenticator application, with single-use recovery codes as the fallback. It is enforced where a credential is issued, not on every request.

A correct password for an enrolled account does **not** produce a session or a personal access token. The server issues a short-lived, single-use challenge instead, and returns it with `202 Accepted` and `mfa_required: true`. The client redeems that challenge with a second factor through `POST /api/v1/auth/mfa/verify`, and only then is the credential created. A stolen password alone therefore produces nothing usable.

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/v1/auth/mfa/enroll` | Start enrolment; returns the shared secret and its otpauth URI |
| POST | `/api/v1/auth/mfa/confirm` | Confirm enrolment with a code; returns the recovery codes exactly once |
| POST | `/api/v1/auth/mfa/verify` | Redeem a challenge for the session or token it was issued for |
| POST | `/api/v1/auth/mfa/disable` | Remove multi-factor authentication with a valid second factor |

The enrolment routes require `auth:sanctum`, `active-user`, and a verified email address. `POST /auth/mfa/verify` sits outside the authenticated group on purpose: the challenge is the credential until it is redeemed.

Invariants:

- The shared secret is encrypted at rest and is never serialized. The pending secret used during enrolment is discarded when enrolment is not confirmed.
- Recovery codes are hashed, are shown exactly once, and each accepted code is removed.
- The last accepted time-step counter is recorded per account, so a code cannot be replayed inside its own validity window.
- A challenge permits a bounded number of failed attempts, is consumed once it is exhausted, and can never be redeemed twice. Redemption is a conditional update, so concurrent redemptions cannot both succeed.
- A challenge is bound to one account and one purpose. A session challenge cannot mint a token and a token challenge cannot establish a browser session.
- A suspended or disabled account cannot redeem a challenge.

The second factor establishes platform identity only. It never establishes tenant context and never substitutes for organization membership.

## Boundary

Authentication answers: **Who is the user?**

`OrganizationMembership` answers: **Which organization can the user access?**

Future RBAC answers: **What can the user do there?**

Tenant routes therefore use `auth:sanctum`, `active-user`, and tenant context in that order. Authentication endpoints such as `/auth/me` do not require tenant context.
