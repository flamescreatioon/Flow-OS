# ADR 0024: Modular Monolith Architecture

Status: ACCEPTED
Date: 2026-10-02

## Context

flowOS must support tenancy, offline synchronization, and financially sensitive workflows without premature distributed-system complexity. Sprint 0 established one Laravel API and multiple client applications, but no ADR explicitly recorded the architectural style.

## Decision

Use a modular monolith for the server: one Laravel deployable with deliberate domain and application boundaries. HTTP and infrastructure concerns depend toward application and domain concerns. Future modules own their business invariants and interact through explicit application/domain interfaces rather than arbitrary cross-module model mutation.

## Alternatives Considered

- Microservices: deferred because distributed consistency, observability, deployment, and operational overhead are not justified by current product scope.
- Unstructured framework monolith: rejected because it weakens module ownership and financial/tenant safeguards.

## Consequences

### Positive

- Simpler deployment and transactional consistency.
- Lower operational overhead while module boundaries can mature.

### Negative / Trade-offs

- Boundary discipline is required inside one codebase.
- Future extraction requires evidence and a separate ADR.

## Security / Tenancy Impact

No module may bypass validated tenant context, authorization, or shared security boundaries.

## Offline / Data Impact

Offline protocol and authoritative cloud writes remain API/domain responsibilities; Redis is not a source of truth.

## References

- [Modular monolith architecture](../architecture/modular-monolith.md)
- [Architecture boundaries](../architecture/architecture-boundaries.md)
