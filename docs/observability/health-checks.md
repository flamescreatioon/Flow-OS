# Health Checks

`GET /api/v1/health` is public, process/liveness-only, and deliberately independent of PostgreSQL and Redis. It must not reveal dependency topology or failures.

Readiness is not implemented. A future protected/internal readiness endpoint may validate required dependencies and migration state. Detailed dependency diagnostics remain protected operational information.
