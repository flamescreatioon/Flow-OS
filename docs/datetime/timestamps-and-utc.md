# Timestamps and UTC

`created_at`, `updated_at`, `deleted_at`, `processed_at`, and similar audit fields are server-authoritative UTC instants. PostgreSQL uses `TIMESTAMPTZ`, which is an instant and does not preserve a timezone name. Server deployment/session timezone should be UTC; display converts separately.
