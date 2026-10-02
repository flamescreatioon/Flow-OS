# Security Foundation

This directory records the enforceable security direction for flowOS. It complements the API, offline, Redis, database, and architecture documentation; it does not replace implementation-specific tests.

- [Principles](security-principles.md) and [threat model](threat-model.md)
- [Authentication](authentication.md), [authorization](authorization.md), and [tenant isolation](tenant-isolation.md)
- Client security: [web](web-security.md), [mobile](mobile-security.md), [desktop](desktop-security.md), and [offline](offline-security.md)
- Platform security: [API](api-security.md), [database](database-security.md), [Redis](redis-security.md), [secrets](secret-management.md), [dependencies](dependency-security.md), [rate limits](rate-limiting.md), [validation](input-validation.md), and [uploads](file-upload-security.md)
- Operations: [incident readiness](incident-readiness.md) and the [current audit](current-security-audit.md)

Tenant data leakage is a critical-severity defect. Security-sensitive changes require a reviewer to check tenant scope, active membership, authorization, input allowlists, secrets, logs, and relevant negative-path tests.
