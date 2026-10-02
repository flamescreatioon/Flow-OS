# Database Security

PostgreSQL is authoritative storage. Production databases are private, use separate credentials per environment, encrypted transport when infrastructure requires/supports it, protected backups, and least privilege. Runtime application accounts are not PostgreSQL superusers; a separately controlled deployment role may run migrations.

Database constraints remain part of security and integrity: foreign keys, non-null rules, uniqueness, and tenant relationships where appropriate. Redis locks do not replace durable constraints. The current architecture does not use PostgreSQL RLS; application-layer organization scoping and active-membership authorization are mandatory.
