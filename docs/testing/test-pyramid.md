# Test Pyramid

```text
             E2E
        Integration
     Application / API
       Domain / unit
```

The base should remain fast and local. API feature tests are valuable for route, middleware, tenancy, authorization, and envelope behavior. Integration tests are narrower because SQLite, in-memory stores, and fakes cannot prove PostgreSQL/Redis-specific behavior. E2E is deferred until real user workflows exist; flowOS does not add browser automation merely to populate the top of the pyramid.

Suites must be parallel-safe in design: no fixed shared state, ports, or ordering assumptions. Do not enable aggressive parallel execution until each suite demonstrates isolation.
