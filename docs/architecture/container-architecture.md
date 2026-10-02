# Container Architecture

```mermaid
flowchart TB
  subgraph Clients
    Web[Next.js web]
    Mobile[Flutter mobile\nlocal SQLite]
    Desktop[.NET 8 WPF\nfuture local SQLite]
  end
  Web --> API[Laravel modular-monolith API]
  Mobile --> API
  Desktop --> API
  API --> PG[(PostgreSQL authoritative cloud state)]
  API --> Redis[(Redis: cache, queue, sessions, rate limits)]
```

Web and API are server components. Mobile and desktop are end-user clients with local working state; their copies and pending operations are not cloud authority. Redis is temporary infrastructure, never business truth.
