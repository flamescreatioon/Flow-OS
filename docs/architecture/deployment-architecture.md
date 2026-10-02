# Deployment Architecture

## Current development

Applications normally run natively on the host. Docker Compose provides PostgreSQL 16, Redis 7, and optional Mailpit. Containers are local infrastructure, not a declared production deployment architecture.

## Proposed production direction — not finalized

Public web/API entrypoints communicate with private PostgreSQL and Redis. API instances should be stateless where possible; Redis is shared temporary infrastructure; queue workers and horizontal web/API scaling may follow demonstrated need. Backups, disaster recovery, hosting, and provider selection require future decisions. Kubernetes and microservices are not proposed by this document.
