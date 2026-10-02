# ADR 0018: Testing Strategy

## Status

ACCEPTED

## Decision

flowOS uses a testing pyramid: fast deterministic domain/unit tests form the base, followed by application/API and selective real-infrastructure integration tests, with rare high-value E2E tests. There is no arbitrary global coverage threshold.

Laravel's normal suite uses SQLite in-memory for fast behavior tests. PostgreSQL-specific behavior requires a separate, dedicated, safety-gated PostgreSQL integration database; Redis integration is similarly limited to Redis-specific behavior. Normal tests do not require Docker or live infrastructure.

The web uses its existing Jest, ts-jest, and Testing Library dependencies as one non-interactive test runner. Platform suites remain separate. Tests must be deterministic, independent, and behavior-focused; financial, tenancy, authorization, and sync/idempotency behavior receive negative-path coverage when implemented.

## Consequences

- E2E, coverage gates, mutation testing, and broad infrastructure test frameworks are deferred.
- SQLite is not evidence of PostgreSQL correctness.
- Current PostgreSQL/Redis integration is intentionally not configured until a safe dedicated test environment is introduced.
- New behavior changes require relevant tests, not placeholder assertions.
