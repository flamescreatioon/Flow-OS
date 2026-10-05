# Authentication

Sprint 1.2 separates identity authentication from tenancy authorization.

## Clients

The web application uses Sanctum's stateful SPA flow: the browser obtains the CSRF cookie, authenticates through the session endpoint, and uses HttpOnly session cookies. Browser clients must not store bearer tokens in localStorage or sessionStorage.

Flutter and desktop clients exchange credentials and a client name for a Sanctum personal access token. They store that token only in platform secure storage and send it as a Bearer token.

## Account lifecycle and revocation

Every protected authentication route uses `auth:sanctum` followed by `active-user`. Only `active` users may continue. This rechecks account status for sessions and already-issued personal access tokens. Logout revokes the current token or invalidates the current session; logout-all revokes every personal access token and safely clears a session when present.

Password reset uses Laravel's password broker and generic reset-request responses to avoid account enumeration. A successful reset revokes all personal access tokens. Email verification uses Laravel notifications and temporary signed URLs; verification is not globally required in this sprint.

## Boundary

Authentication answers: **Who is the user?**

`OrganizationMembership` answers: **Which organization can the user access?**

Future RBAC answers: **What can the user do there?**

Tenant routes therefore use `auth:sanctum`, `active-user`, and tenant context in that order. Authentication endpoints such as `/auth/me` do not require tenant context.
