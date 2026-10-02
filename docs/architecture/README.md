# flowOS Architecture

flowOS is an offline-capable, multi-tenant business operating system. It is a modular monolith: one Laravel API deployable with explicit internal boundaries, used by Next.js web, Flutter mobile, and WPF desktop clients.

The source-of-truth hierarchy is: these architecture documents for system boundaries; [ADRs](../adr/) for significant decisions and their rationale; [OpenAPI](../../packages/contracts/openapi/v1.yaml) for HTTP contracts; Laravel migrations for cloud schema; and the detailed [security](../security/README.md), [observability](../observability/README.md), [offline](../offline/README.md), and [API](../api/README.md) documents for specialized rules.

## Map

- [System overview](system-overview.md), [system context](system-context.md), and [containers](container-architecture.md)
- [Modular monolith and API](modular-monolith.md), [API architecture](api-architecture.md), and [web](web-architecture.md)
- [Mobile](mobile-architecture.md), [desktop](desktop-architecture.md), and [data](data-architecture.md)
- [Tenancy](tenancy-architecture.md), [identity and access](identity-access-architecture.md), and [offline sync](offline-sync-architecture.md)
- [Security](security-architecture.md), [observability](observability-architecture.md), [deployment](deployment-architecture.md), [integrations](integration-architecture.md), and [boundaries](architecture-boundaries.md)
- [Current architecture audit](current-architecture-audit.md)

`IMPLEMENTED` describes code present now; `ARCHITECTED` describes accepted direction; `PLANNED` is not implementation. Sprint 1 may build identity, organizations, branches, memberships, roles, tenant-safe APIs, and device/audit foundations using these boundaries.
