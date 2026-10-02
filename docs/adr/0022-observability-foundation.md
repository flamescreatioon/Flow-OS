# ADR 0022: Establish Observability Foundation

- **Status:** Accepted
- **Date:** 2026-10-02

## Decision

flowOS uses structured, vendor-neutral operational logging with safe contextual IDs. API requests receive validated or generated UUID request IDs returned in `X-Request-ID`. Correlation IDs span workflows, while offline operation IDs remain durable idempotency identities. Tenant IDs are logged only after validated context is established.

Operational logs, metrics, health, traces, and durable audit events remain separate. Audit events will be append-oriented PostgreSQL records when implemented; they are neither logs nor financial ledgers. The public health endpoint remains dependency-independent; readiness is a separate future concern. Redaction is mandatory and external telemetry/OTel backends remain deferred.

## Consequences

Laravel has a JSON stderr logging option and request middleware, while web/mobile/desktop use their existing light-weight platform facilities. No external observability SaaS, collector, metrics stack, persistent client file log, or business module is introduced.
