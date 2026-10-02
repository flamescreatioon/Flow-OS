# API Architecture

The Laravel API is the modular-monolith boundary. HTTP controllers parse requests and return responses; application/domain layers own workflows and business rules. Middleware provides CORS, Sanctum stateful SPA support, request context, and explicit tenant context.

The canonical contract is [`packages/contracts/openapi/v1.yaml`](../../packages/contracts/openapi/v1.yaml); the canonical prefix is `/api/v1`. Current implementation is limited to health and foundation behavior—planned domain endpoints are not implemented.

Success responses use `data`; errors use `error.code`, `error.message`, and optional `details`. API failures return `X-Request-ID`; server logs retain safe diagnostics. REST `Idempotency-Key` is distinct from offline `operation_id`.
