# Observability Architecture

Detailed standards live in [docs/observability](../observability/README.md). Implemented API foundation: validated/generated request IDs, `X-Request-ID`, structured safe request context, validated tenant/user context, redaction, and dependency-independent health.

Correlation IDs, device context, durable audit events, sync lifecycle instrumentation, metrics, readiness, and external telemetry remain future work. Audit events are neither ordinary logs nor accounting journal entries.
