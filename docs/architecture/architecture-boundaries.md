# Architecture Boundaries and Invariants

1. Every tenant-owned cloud record is organization-scoped.
2. A tenant selector never grants authorization; active membership is required.
3. Clients never connect directly to cloud PostgreSQL or Redis.
4. Financial authority and final calculations remain server-side and exact.
5. Client clocks never establish authoritative ordering.
6. Redis is never durable business truth.
7. Offline operation IDs survive retries; REST idempotency keys are separate.
8. Inventory is movement-derived; finalized financial records use corrective entries.
9. Secrets never belong in source, logs, or browser storage.
10. OpenAPI is canonical HTTP contract and Laravel migrations are canonical cloud schema.

An ADR is required to change tenancy/auth/sync/financial/source-of-truth/deployment architecture or introduce major infrastructure. Prototypes do not become architecture merely by existing in code.
