# Redis Security

Redis is temporary infrastructure, not authoritative business storage or a tenant-security boundary. Production uses private networking, authentication/ACLs and TLS where required, with no public exposure. Avoid caching sensitive plaintext unless necessary.

Tenant data cache keys include organization context; Redis logical databases are not tenant isolation. Rate-limit counters and cache entries are temporary control state. Critical idempotency and integrity need durable PostgreSQL records. Queue workers establish and clear tenant context for every job.
