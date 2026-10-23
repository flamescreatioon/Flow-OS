# Security Architecture

Security requirements are detailed in [docs/security](../security/README.md). Architecture boundaries are active membership authorization, shared-schema tenant isolation, Sanctum/CSRF, explicit credentialed CORS origins, safe token storage, exact financial authority, secret hygiene, and dependency monitoring.

The Laravel 10 and Next 14 dependency advisories have been **RESOLVED**: the API now runs on Laravel 12.70.0 and the web client on Next.js 15.5.28 with React 19, so the platform is on supported, patched framework lines. `composer audit` and `npm audit --omit=dev` both report no advisories. The remaining `npm audit` findings are development-tooling transitive dependencies with no published upstream fix; they are not shipped to production. See [dependency security](../security/dependency-security.md).
