# Testing Strategy

Use many fast, deterministic domain/unit tests; fewer application, HTTP, and infrastructure integration tests; and rare high-value end-to-end tests. Tests are independent of execution order and avoid production services, real network calls, sleeps, uncontrolled clocks, random assertions, and developer data.

| Category | Purpose | Normal infrastructure |
|---|---|---|
| Unit/domain | Pure rules, state transitions, parsing, decisions | None |
| Feature/API | Laravel HTTP, middleware, validation, response envelopes | SQLite in-memory where suitable |
| Integration | Real framework/infrastructure or database-specific behavior | Dedicated local PostgreSQL/Redis only when under test |
| E2E | Small set of user-visible critical workflows | Added only with real workflows |

Test behavior rather than private implementation. Use explicit data and clear assertions. One test has one primary behavioral reason to fail, though it may make several assertions about that behavior. Do not set an arbitrary global coverage target; prioritize financial correctness, tenant isolation, authorization, inventory integrity, sync idempotency, and operationally significant errors.

External providers use framework fakes/mocks or contract fixtures, never real credentials or network calls. Mocks belong at meaningful ports; inexpensive real values and collaborators are preferable to deeply mocked object graphs.
