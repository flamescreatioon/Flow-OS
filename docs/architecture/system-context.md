# System Context

Business owners, managers, staff/cashiers, and viewers use flowOS through web, mobile, or desktop clients. A platform operator is a separate future platform-authorization concern, not an organization role.

```mermaid
flowchart LR
  Users[Business users] --> Web[flowOS Web]
  Users --> Mobile[flowOS Mobile]
  Users --> Desktop[flowOS Desktop / POS]
  Web --> API[flowOS API]
  Mobile --> API
  Desktop --> API
  API --> PG[(PostgreSQL)]
  API --> Redis[(Redis)]
  API -. future adapters .-> External[Payments, email, SMS, fiscal services]
```

External services are future adapters behind the API. Clients never connect directly to cloud PostgreSQL or Redis.
