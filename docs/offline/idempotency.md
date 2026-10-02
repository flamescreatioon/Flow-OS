# Idempotency

Every pushed mutation has a UUIDv7 `operation_id`. The server later records processed operations by operation, organization, device, status, result, and processing time. A repeated operation returns its prior safe result and never repeats sale, payment, stock, or ledger effects.

Idempotency is operation-level and complements record IDs and optimistic versions; none replaces the others. The server-side operation store is intentionally not implemented in Sprint 0.11.
