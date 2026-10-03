# Sprint 0 Technical Debt Register

This register documents all acknowledged technical debt, accepted baseline findings, deferred architectural implementations, and pre-production blockers identified during **Sprint 0: Engineering & Architecture Foundation**.

---

## 1. Must Resolve Before Production

The following items are explicit blockers that must be fully resolved before any production deployment or exposure to live customers:

| Issue / Blocker | Current State (Sprint 0) | Required Production Resolution | Owning Subsystem |
|-----------------|--------------------------|--------------------------------|------------------|
| **Laravel Security Advisories** | Laravel 10.50.3 in use; 4 security advisories reported by `composer audit` | Controlled upgrade to supported Laravel 12+ line | `apps/api` |
| **Next.js Security Advisories** | Next.js 14.2.35 in use; 2 advisories (1 high, 1 critical) reported by `npm audit` | Controlled upgrade to Next.js `>=15.5.24` | `apps/web` |
| **Production Headers & CORS** | Local development origins configured (`localhost:3000`) | Explicit HTTPS production domains, HSTS, CSP nonces, secure cookie flags | `apps/api`, `apps/web` |
| **Secret Management** | Local development uses `.env` files | Integration with HashiCorp Vault, AWS Secrets Manager, or managed cloud secret store | Infrastructure / DevOps |
| **Production Infrastructure Topology** | Docker Compose used solely for local workstation infrastructure | Production VPS/container cluster, managed PostgreSQL 16 with automated backups and replicas, TLS termination | Infrastructure / Cloud |
| **Client Storage Encryption at Rest** | Mobile uses `flutter_secure_storage` for tokens, but SQLite database is unencrypted | Formal architecture decision on SQLCipher / client database encryption at rest | `apps/mobile`, `apps/desktop` |

---

## 2. Before Relevant Feature Release

The following architectural foundations must be completed before the corresponding functional modules are released:

| Area | Current State (Sprint 0) | Required Resolution | Milestone |
|------|--------------------------|---------------------|-----------|
| **Authentication Endpoints** | Sanctum stateful cookie direction established; zero auth endpoints deployed | Implement login, logout, password reset, session refresh, and token issuance | Sprint 1 (Platform Foundation) |
| **Device Lifecycle & Registration** | Device model architected; no registration API | Implement device enrollment, fingerprinting, authorization, and revocation | Sprint 1 / Mobile Milestone |
| **Audit Event Persistence** | Architecture separated from diagnostics; no audit table | Implement immutable append-only audit event schema and persistence pipeline | Sprint 1 / Security Milestone |
| **Production Synchronization Engine** | Mobile `SyncEngine` is a prototype with last-write-wins debt | Implement transactional outbox, UUIDv7 `operation_id`, server ordering, and domain-specific conflict handlers | Synchronization Milestone |
| **Flutter Static Analyzer Debt** | 186 accepted analyzer findings in `apps/mobile` | Systematic remediation of lint warnings, deprecation notices, and style lints | Mobile Quality Milestone |
| **Desktop Test Coverage** | 3 test projects wired into `FlowOS.sln`, but zero discovered tests | Implement unit and domain test cases for WPF desktop layer | Desktop Domain Sprints |
| **Database Seeders** | No domain factories or seeders exist (`NOT YET ESTABLISHED`) | Implement realistic domain seeders and factories for development and testing | Sprint 1 / Domain Sprints |

---

## 3. Future Infrastructure

The following capabilities are architected and configured in repository files, but inactive until external services are attached:

| Component | Repository Status | Current Operational Status | Trigger |
|-----------|-------------------|----------------------------|---------|
| **Remote GitHub Repository** | `.github/` workflows configured | `NOT CONFIGURED` (No remote attached) | Upstream repository creation and push |
| **Hosted CI Pipeline** | `.github/workflows/ci.yml` complete | `UNAVAILABLE` locally | Attachment to GitHub Actions |
| **CodeQL Security Scanning** | `.github/workflows/codeql.yml` configured | `UNAVAILABLE` locally | Attachment to GitHub Actions |
| **Dependabot Version Updates** | `.github/dependabot.yml` configured | `UNAVAILABLE` locally | Attachment to GitHub |
| **Metrics & Distributed Tracing** | Logging foundation implemented; metrics/tracing deferred | `NOT CONFIGURED` (Redis/PostgreSQL only) | Observability Infrastructure Milestone |
| **Deep Readiness Diagnostics** | Liveness `/api/v1/health` implemented; deep readiness deferred | `NOT IMPLEMENTED` (Intentionally decoupled) | Operations / Orchestration Milestone |
