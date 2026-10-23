# Backend Architecture

The backend is the Laravel modular monolith described in [API architecture](api-architecture.md). This page remains as a focused entry point for contributors looking for the previous backend architecture document.

## Current foundation

- Laravel exposes the versioned HTTP boundary under `/api/v1`.
- Middleware establishes request context, authentication, validated tenant context, and cleanup boundaries.
- The canonical HTTP contract is [OpenAPI v1](../../packages/contracts/openapi/v1.yaml).
- Laravel migrations are the authoritative cloud-schema evolution mechanism.

## Target dependency direction

```text
HTTP / Infrastructure
        â†“
Application
        â†“
Domain
```

The Sprint 0 scaffold is not represented as a claim of complete clean architecture. Future modules must keep business invariants outside controllers and avoid arbitrary cross-module model mutation.

## Security status

The API runs on Laravel 12.70.0, a supported patched framework line. The pre-production security migration requirement to a supported Laravel 12+ line has been satisfied. See [security architecture](security-architecture.md) and [dependency security](../security/dependency-security.md).
