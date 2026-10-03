# flowOS Documentation Directory

The [domain map](domain/README.md) defines initial business-domain ownership, tenant/branch scoping, and future module invariants.

This directory contains the canonical documentation for **flowOS**, an offline-capable, multi-tenant business operating system designed for Nigerian businesses.

---

## Documentation Map

```text
docs/
├── architecture/      Current system architecture, domain boundaries, and target designs
├── adr/               Architectural Decision Records (ADRs) recording historical decisions
├── development/       Developer workstation setup, daily workflows, Docker, and scripts
├── engineering/       Engineering standards, code quality, CI/CD, Git, and pull request workflows
├── testing/           Testing strategy, test pyramids, and verification requirements
├── security/          Security guardrails, authentication architecture, and dependency advisories
├── observability/     Structured logging, request ID tracing, and diagnostic standards
├── offline/           Offline-first design, synchronization patterns, and local persistence
├── api/               API standards, versioning rules, and HTTP contract governance
├── database/          Database conventions, integer money rules, and schema migration standards
├── datetime/          DateTime, timezone, and calendar date handling rules
├── finance/           Financial calculation, precision, and currency standards
└── product/           Product vision, target modules, and user personas
```

---

## Canonical Source-of-Truth Hierarchy

When consulting documentation, adhere to the established authority hierarchy:

1. **Repository Introduction**: [README.md](../README.md)
2. **Current System Architecture**: [docs/architecture/README.md](architecture/README.md)
3. **Architectural Decision History**: [docs/adr/](adr/)
4. **Developer Workstation Onboarding & Daily Workflows**: [docs/development/README.md](development/README.md)
5. **Engineering Quality, CI & Git Policies**: [docs/engineering/code-quality.md](engineering/code-quality.md)
6. **Testing Philosophy & Baseline**: [docs/testing/README.md](testing/README.md)
7. **Security Guardrails**: [docs/security/README.md](security/README.md)
8. **Observability & Logging Standards**: [docs/observability/README.md](observability/README.md)
9. **Offline Architecture & Sync**: [docs/offline/README.md](offline/README.md)
10. **HTTP API Contract Authority**: `packages/contracts/openapi/v1.yaml`
11. **Cloud Database Schema Authority**: Laravel Migrations in `apps/api/database/migrations/`
