# Data Architecture

PostgreSQL is the authoritative cloud store. Laravel migrations are the active schema authority; `infrastructure/database/legacy/02-schema.sql` is historical reference only. Tenant-owned cloud records require `organization_id`; branches are subordinate organization units.

Mobile and future desktop SQLite stores are local working state, outbox data, and sync metadata. Redis supports cache, queue transport, sessions, rate limits, and temporary coordination only; Redis failure must not lose business truth.

Business IDs use UUIDv7-style values. Money uses BIGINT minor units or field-specific exact `NUMERIC(p,s)`; never floating point or a universal `NUMERIC(19,4)` rule. Instants use UTC/TIMESTAMPTZ/RFC3339; calendar concepts use their appropriate types and IANA organization timezones such as `Africa/Lagos`.
