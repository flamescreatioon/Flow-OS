# Deployment Overview

This page is a compatibility entry point. The canonical current/proposed distinction is in [deployment architecture](deployment-architecture.md).

## Current development

Web, API, mobile, and desktop applications normally run natively on the developer host. Docker Compose supplies PostgreSQL 16, Redis 7, and optional Mailpit. Compose infrastructure is local development support, not a production deployment declaration.

## Production direction

Production topology, cloud provider, container orchestration, backup retention, and external monitoring are not finalized. The established boundary is public web/API entrypoints with PostgreSQL and Redis private. PostgreSQL is authoritative and Redis remains temporary infrastructure.

No Kubernetes, Docker Swarm, production compose file, JWT signing secret, external monitoring vendor, or deployment pipeline is selected by this repository architecture.
