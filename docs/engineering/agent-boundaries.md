# Agent Boundaries

Defines conceptual ownership areas for coding agents working on flowOS.

---

## Primary Ownership

| Area | Path | Description |
|------|------|-------------|
| Web Application | `apps/web/` | Next.js frontend, React components, pages, hooks |
| API Backend | `apps/api/` | Laravel application, domain, application, infrastructure layers |
| Mobile App | `apps/mobile/` | Flutter application, Dart code, platform channels |
| Desktop App | `apps/desktop/` | .NET WPF application, C# projects |
| Shared Contracts | `packages/contracts/` | OpenAPI specs, shared schemas |
| Shared Config | `packages/config/` | Shared development configuration |
| Design Tokens | `packages/design-tokens/` | Colors, spacing, typography, sizing |
| Shared Types | `packages/types/` | TypeScript type definitions |
| Infrastructure | `infrastructure/` | Docker, database, nginx, scripts |
| Documentation | `docs/` | Architecture, engineering, product docs |
| Scripts | `scripts/` | Build, dev, test automation |
| CI/CD | `.github/` | GitHub workflows, templates |

---

## Cross-Cutting Concerns

Some tasks span multiple areas. Agents working on these should explicitly state the scope:

| Concern | Involved Areas | Coordination Required |
|---------|----------------|----------------------|
| Authentication | Web, API, Mobile, Desktop | API owns auth contracts; clients implement |
| API Contracts | All clients + `packages/contracts` | Contract changes require all clients to update |
| Database Migrations | API (migrations) + Mobile/Desktop (local DB schema) | Schema changes must be backward-compatible |
| Offline Sync | Mobile, Desktop, API | Sync protocol defined in `docs/offline/` |
| Multi-Tenancy | API (enforcement) + all clients (context) | API owns tenant resolution logic |
| Design System | Web, Mobile, Desktop | Tokens in `packages/design-tokens/` |
| Feature Flags | API + all clients | Centralized in API, consumed by clients |

---

## Task Assignment Guidelines

1. **Narrow scope**: Each agent task should target one primary area
2. **No overlap**: Avoid assigning overlapping files to multiple agents simultaneously
3. **Architecture first**: Architecture changes (ADRs, contracts, shared types) should complete before implementation tasks
4. **State inspection**: Agents must inspect current repository state before writing
5. **No assumptions**: Do not assume another agent's work has been merged
6. **Handoff documentation**: Incomplete work must be documented in handoff notes

---

## Escalation

If a task requires changes across multiple ownership boundaries:
1. Document the scope in the task description
2. Create an ADR if architectural
3. Coordinate via the task system
4. The agent owning the primary area leads implementation