# Offline and Device Security

Device identity is distinct from user identity: user → device → device credential/session. A future device record will include ID, organization and user IDs, name, platform, timestamps, and `revoked_at`. Registration and revocation are future work; a device is never treated as a user.

Cached server-issued permission snapshots may authorize limited temporary offline work, but are not permanent authority. They need version/timestamp data; high-risk actions require fresher authorization than low-risk reads. On sync the server authenticates the user/device, re-authorizes active membership and permission, validates payload/domain invariants and record ownership, checks durable idempotency, and applies conflict/version rules. Client totals, client timestamps, and local financial state are not authoritative.

Sync endpoints must have fixed supported operation types (never arbitrary model/table names), organization scope, payload/batch limits, replay protection, and dedicated rate limits. A stolen device is handled by revoking its server token and device, rejecting future sync, and relying on local encryption where feasible; remote wipe cannot be assumed.
