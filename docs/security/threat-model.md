# Initial Threat Model

Assets include organization, financial, inventory, customer/supplier, membership, token, device, sync-operation, and offline-database data. Actors include owners, admins, managers, staff, viewers, platform operators, unauthenticated attackers, malicious members, and compromised devices.

Trust boundaries are browser/mobile/desktop to API; API to PostgreSQL and Redis; offline local database to sync; and GitHub/CI to the source repository. Core threats are cross-tenant access, credential/token theft, privilege escalation, mass assignment, SQL injection, XSS, CSRF, rate abuse, replay/sync tampering, stolen devices, dependency compromise, secret exposure, and financial manipulation.

Primary mitigations are active-membership tenant middleware, tenant-scoped queries and constraints, Sanctum, CSRF-protected first-party sessions, validated allowlisted writes, parameter binding, React escaping, TLS, revocable scoped tokens, durable idempotency, secure client token storage, dependency scanning, and review/testing of negative paths. Residual risks and implementation gaps are tracked in the current audit.
