# Offline-First Principles

For an operation explicitly declared offline-capable, clients validate locally, commit the business mutation and outbox entry in one local transaction, update the UI, and synchronize later. Mobile and desktop use SQLite as the immediate data source; the web app is not currently a local-first client.

PostgreSQL remains the canonical shared organizational state. This does not require cloud-generated IDs: valid offline records keep a client-generated UUIDv7 when accepted by the server. UUIDv7 is identity, not synchronization ordering; client clocks are not authoritative.

Capability is classified per feature: **offline required** for operationally critical work, **offline preferred** where practical, and **online required** for server-authoritative or security-sensitive actions such as membership administration, platform administration, and some payments. Correctness takes priority over convenience for money, stock, permissions, and tenant ownership.
