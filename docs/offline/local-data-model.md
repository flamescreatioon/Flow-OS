# Local Data Model

Mobile (sqflite) and desktop (EF Core/SQLite) local schemas support offline workflows and synchronization; they need not duplicate PostgreSQL exactly. Every synchronized tenant record retains `organization_id`, and relevant records retain `branch_id`. Local queries must always scope by organization so switching organizations cannot reveal another tenant's data.

Normal synchronized records use the same UUIDv7 locally and in PostgreSQL—no routine `local_id`/`server_id` mapping. Local-only tables hold outbox operations, cursors, conflict state, and permission snapshots. Server-only change/operation metadata must not be copied into every business table without a later schema decision.

Initial bootstrap is authenticate → select organization → identify device → download a bounded required dataset → save cursor → permit offline work. Dataset scope may later use branch, active reference data, and recent records; large history and attachments are loaded separately/on demand. Attachments use separate transfer flows with synchronized metadata.
