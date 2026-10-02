# Modular Monolith

flowOS uses a modular monolith for transactional consistency, simpler deployment, and lower operational complexity. Future extraction requires evidence and an ADR; no microservices, distributed messaging system, or additional primary database is implied.

Future modules own their invariants. Other modules invoke explicit application/domain interfaces rather than mutating another module's models arbitrarily.

```text
HTTP / Infrastructure
        ↓
Application
        ↓
Domain
```

The current Laravel scaffold contains these layers but is not yet a complete domain implementation. Domain logic should avoid framework/infrastructure dependencies where practical.
