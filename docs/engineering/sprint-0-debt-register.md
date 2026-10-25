# Sprint 0 Technical Debt Register

This register documents all acknowledged technical debt, accepted baseline findings, deferred architectural implementations, and pre-production blockers identified during **Sprint 0: Engineering & Architecture Foundation**.

---

## 1. Must Resolve Before Production

The following items are explicit blockers that must be fully resolved before any production deployment or exposure to live customers:

| Issue / Blocker | Current State (Sprint 0) | Required Production Resolution | Owning Subsystem |
|-----------------|--------------------------|--------------------------------|------------------|
| **Laravel Security Advisories** | Resolved: Laravel 12.70.0 in use; `composer audit` reports no advisories | None outstanding | `apps/api` |
| **Next.js Security Advisories** | Resolved: Next.js 16.4.2 in use; `npm audit --omit=dev` reports no advisories | None outstanding for production dependencies | `apps/web` |
| **Production Headers & CORS** | Local development origins configured (`localhost:3000`) | Explicit HTTPS production domains, HSTS, CSP nonces, secure cookie flags | `apps/api`, `apps/web` |
| **Secret Management** | Local development uses `.env` files | Integration with HashiCorp Vault, AWS Secrets Manager, or managed cloud secret store | Infrastructure / DevOps |
| **Production Infrastructure Topology** | Docker Compose used solely for local workstation infrastructure | Production VPS/container cluster, managed PostgreSQL 16 with automated backups and replicas, TLS termination | Infrastructure / Cloud |
| **Client Storage Encryption at Rest** | Mobile uses `flutter_secure_storage` for tokens, but SQLite database is unencrypted | Formal architecture decision on SQLCipher / client database encryption at rest | `apps/mobile`, `apps/desktop` |

---

## 2. Before Relevant Feature Release

The following architectural foundations must be completed before the corresponding functional modules are released:

| Area | Current State (Sprint 0) | Required Resolution | Milestone |
|------|--------------------------|---------------------|-----------|
| **Authentication Endpoints** | Implemented (Sprint 1 platform slice): session/token issuance, password reset, email verification, TOTP MFA with recovery codes | None outstanding | Delivered |
| **Device Lifecycle & Registration** | Implemented (Sprint 1 platform slice): device registration and revocation API | None outstanding | Delivered |
| **Audit Event Persistence** | Implemented (Sprint 1 platform slice): append-only `audit_events` schema and `AuditLogger` pipeline | None outstanding | Delivered |
| **Production Synchronization Engine** | Mobile `SyncEngine` is a prototype with last-write-wins debt | Implement transactional outbox, UUIDv7 `operation_id`, server ordering, and domain-specific conflict handlers | Synchronization Milestone |
| **Flutter Static Analyzer Debt** | 186 accepted analyzer findings in `apps/mobile` | Systematic remediation of lint warnings, deprecation notices, and style lints | Mobile Quality Milestone |
| **Desktop Test Coverage** | 3 test projects wired into `FlowOS.sln`, but zero discovered tests | Implement unit and domain test cases for WPF desktop layer | Desktop Domain Sprints |
| **Database Seeders** | No domain factories or seeders exist (`NOT YET ESTABLISHED`) | Implement realistic domain seeders and factories for development and testing | Sprint 1 / Domain Sprints |
| **FluentAssertions 8 upgrade** | License-blocked: FluentAssertions 8 uses the Xceed commercial license; staying on Apache-licensed 6.x | Decide when desktop development resumes: purchase, stay on 6.x, or switch to a free alternative (e.g. Shouldly, AwesomeAssertions) | Desktop Domain Sprints |
| **MediatR 14 upgrade** | License-blocked: MediatR 13+ uses Lucky Penny commercial licensing; staying on Apache-licensed 12.x | Decide when desktop development resumes: purchase, stay on 12.x, or replace with plain handler interfaces | Desktop Domain Sprints |
| **TypeScript 7 upgrade** | Toolchain-blocked: ts-jest 29.x peer-requires `typescript <7`, so the Jest suite cannot run on TS 7; staying on 5.9.x | Revisit when ts-jest supports TS 7, or as a deliberate PR swapping the Jest transform to babel-jest (typecheck stays with `tsc`) | Web Tooling Milestone |
| **EF Core 9 upgrade (desktop)** | Reverted after CI failure: the bump pulls `Microsoft.Extensions.*` 9.0.20 transitively while `FlowOS.Sync`/`FlowOS.Desktop` pin 8.0.1 (NU1605 as error) | Align `Microsoft.Extensions.*` versions across the desktop solution, then re-apply the EF Core 9 bump | Desktop Domain Sprints |

---

## 3. Future Infrastructure

The following capabilities are architected and configured in repository files, but inactive until external services are attached:

| Component | Repository Status | Current Operational Status | Trigger |
|-----------|-------------------|----------------------------|---------|
| **Remote GitHub Repository** | `.github/` workflows configured | `CONFIGURED` (origin attached; Dependabot active) | N/A |
| **Hosted CI Pipeline** | `.github/workflows/ci.yml` complete | `UNAVAILABLE` locally | Attachment to GitHub Actions |
| **CodeQL Security Scanning** | `.github/workflows/codeql.yml` configured | `UNAVAILABLE` locally | Attachment to GitHub Actions |
| **Dependabot Version Updates** | `.github/dependabot.yml` configured | `UNAVAILABLE` locally | Attachment to GitHub |
| **Metrics & Distributed Tracing** | Logging foundation implemented; metrics/tracing deferred | `NOT CONFIGURED` (Redis/PostgreSQL only) | Observability Infrastructure Milestone |
| **Deep Readiness Diagnostics** | Liveness `/api/v1/health` implemented; deep readiness deferred | `NOT IMPLEMENTED` (Intentionally decoupled) | Operations / Orchestration Milestone |
