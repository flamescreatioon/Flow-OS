# Idempotency

Sensitive REST creates may later use `Idempotency-Key`, scoped to authenticated organization/client context. Same key and request safely replay the prior result; same key with materially different payload is 409. Sync uses its distinct `operation_id` protocol. No idempotency store is implemented yet.
