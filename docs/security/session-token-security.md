# Session and Token Security

Sanctum personal-access tokens are hashed by Sanctum; their abilities, last use, and expiry fields support future scoped/revocable sessions. Token issuance and refresh are not implemented in Sprint 0.10; expired tokens require re-authentication unless a later approved design adds renewal.

Web sessions should use secure HTTP-only cookies where deployment permits. Mobile and desktop tokens belong in platform secure storage, never logs or application preferences. Logout revokes the relevant token/session. Authentication endpoints require rate limiting when implemented.
