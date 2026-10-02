# Security Architecture

Security requirements are detailed in [docs/security](../security/README.md). Architecture boundaries are active membership authorization, shared-schema tenant isolation, Sanctum/CSRF, explicit credentialed CORS origins, safe token storage, exact financial authority, secret hygiene, and dependency monitoring.

The Laravel 10 and Next 14 dependency advisories are **MUST RESOLVE BEFORE PRODUCTION**: migrate Laravel to a supported patched 12+ line and Next to at least 15.5.24. Current versions are not production-ready.
