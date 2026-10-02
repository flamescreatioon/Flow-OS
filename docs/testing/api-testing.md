# API Testing

Laravel `tests/Feature` exercises HTTP/application behavior. The default suite uses `RefreshDatabase`, SQLite `:memory:`, array cache/session/mail, and synchronous queue configuration from `phpunit.xml`; it is fast and independent from PostgreSQL and Redis.

Feature tests verify real behavior at the boundary: HTTP status, documented `data`/`error` envelopes, validation, authentication, membership, authorization, and tenant cleanup. Existing health tests are architectural tests: `GET /api/v1/health` must be 200 and must not require tenant database lookup or Redis.

Future endpoints test 401, 403, 404, 409, 422, and 429 where that behavior exists. OpenAPI remains canonical; contract-shape coverage should be added incrementally with real endpoints, not through a large new framework.
