# Bulk Operations

Future bulk APIs are bounded, validate every item, and return per-item outcomes whenever partial success is permitted. They never silently ignore failures. Long-running work may return 202 with an operation resource; ordinary CRUD remains synchronous.
