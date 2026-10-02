# API Standards

| Rule | Standard |
|---|---|
| Base/version | `/api/v1` |
| JSON | UTF-8, `snake_case` |
| Auth | Sanctum; never JWT terminology |
| Tenant | `X-Organization-ID` selector plus active membership |
| Success | `{ "data": ... }` |
| Errors | `{ "error": { "code", "message", "details?" } }` |
| Pagination | `page`, `per_page`, `meta`, `links` |
| IDs | UUIDv7, opaque |
| Timestamps | UTC ISO 8601 |
| Contract | `packages/contracts/openapi/v1.yaml` |

See the focused documents in this directory for rules that future endpoints must follow.
