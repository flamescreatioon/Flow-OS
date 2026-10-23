# Architecture Decision Records

Current architecture is described in the [architecture entrypoint](../architecture/README.md). ADRs preserve the rationale for significant decisions. `PROPOSED` means under consideration; `ACCEPTED` means active; `SUPERSEDED` means replaced by a later decision; `DEPRECATED` means retained but not for new work; and `REJECTED` means considered but not adopted. Contracts, migrations, and code remain authoritative within their scopes.

| ADR | Title | Status | Summary / relationship |
|---|---|---|---|
| 0001 | Use Monorepo | ACCEPTED | One repository for applications and shared contracts. |
| 0002 | Use Next.js for Web | ACCEPTED | Next.js remains the web framework choice. |
| 0003 | Use Laravel for API | ACCEPTED | Laravel framework choice; historical version wording clarified. |
| 0004 | Use PostgreSQL | ACCEPTED | Authoritative cloud database. |
| 0005 | Use Flutter for Mobile | ACCEPTED | Flutter mobile client. |
| 0006 | Use .NET for Desktop | ACCEPTED | .NET 8 WPF desktop client. |
| 0007 | Offline-First Architecture | SUPERSEDED | Superseded by ADR 0012. |
| 0008 | Shared Schema Multi-Tenancy | SUPERSEDED | Superseded by ADR 0023. |
| 0009 | Use UUIDv7 for IDs | ACCEPTED | UUIDv7-style identifiers. |
| 0010 | Shared Schema Multi-Tenancy | SUPERSEDED | Duplicate historical decision; superseded by ADR 0023. |
| 0011 | Membership-Based Identity and Access | ACCEPTED | Identity/membership/Sanctum direction. |
| 0012 | Offline-First Synchronization | ACCEPTED | Current approved sync protocol direction. |
| 0013 | REST API Standards | ACCEPTED | Versioned REST and OpenAPI standards. |
| 0014 | Financial Data Representation | ACCEPTED | Exact, currency-aware values. |
| 0015 | Date, Time and Localization | ACCEPTED | UTC instants and IANA timezones. |
| 0016 | Redis Infrastructure | ACCEPTED | Temporary infrastructure, not business truth. |
| 0017 | Docker Development Environment | ACCEPTED | Local infrastructure only. |
| 0018 | Testing Strategy | ACCEPTED | Layered, behavior-focused tests. |
| 0019 | Git and GitHub Workflow | ACCEPTED | Main-based workflow; GitFlow rejected. |
| 0020 | Continuous Integration Foundation | ACCEPTED | GitHub Actions configuration; hosted execution unavailable without remote. |
| 0021 | Establish Security Foundation | ACCEPTED | Security boundaries and production blockers. |
| 0022 | Establish Observability Foundation | ACCEPTED | Request IDs, safe logs, audit separation. |
| 0023 | Shared-Schema Tenancy and Membership Authorization | ACCEPTED | Supersedes ADR 0008 and ADR 0010. |
| 0024 | Modular Monolith Architecture | ACCEPTED | Records the server architectural style. |
| 0025 | Core Platform Contract and Schema Hardening | ACCEPTED | Standardizes platform identity, lifecycle, hierarchy constraints, and tenant context. |
| 0026 | Platform Authentication Foundation | ACCEPTED | Sanctum sessions for web and tokens for mobile/desktop, separate from tenant authorization. |
| 0027 | Organization Lifecycle | ACCEPTED | Transactional bootstrap membership and explicit organization lifecycle. |
| 0028 | Branch Management | ACCEPTED | Branch profile and lifecycle under organization ownership. |
| 0029 | Membership and Invitation Lifecycle | ACCEPTED | Hashed expiring invitations and safe membership lifecycle invariants. |
| 0030 | Organization-Scoped RBAC | ACCEPTED | Organization roles and canonical permission authorization. |
| 0031 | Framework Version Upgrade for Production Support | ACCEPTED | Laravel 10 to 12 and Next.js 14 to 15 for upstream security support. |
| 0032 | Multi-Factor Authentication with Authenticator Applications | ACCEPTED | TOTP second factor enforced at credential issuance, with recovery codes. |

Use [the ADR template](template.md) for future significant decisions. An ADR is required for changes to tenancy, authentication, primary source of truth, offline conflict model, financial representation, architecture style, major infrastructure, or deployment architecture. Routine fixes, tests, refactors, and ordinary endpoints under existing boundaries do not require one.
