# Errors

API errors use `{ "error": { "code": "UPPERCASE_CODE", "message": "Human-readable", "details": {} } }`. Standard codes include `VALIDATION_ERROR`, `UNAUTHENTICATED`, `FORBIDDEN`, `RESOURCE_NOT_FOUND`, `TENANT_REQUIRED`, `TENANT_ACCESS_DENIED`, `CONFLICT`, `VERSION_CONFLICT`, `IDEMPOTENCY_CONFLICT`, and `RATE_LIMITED`. Production errors never expose traces, SQL, paths, tokens, or secrets.
