# Audit Events

Audit events are append-oriented durable records, not operational logs, accounting journals, or inventory movements. Future persistence is PostgreSQL-backed and requires a deliberate migration.

Canonical fields: `id`, `organization_id`, `branch_id`, `actor_user_id`, `actor_device_id`, `event_type`, `subject_type`, `subject_id`, `occurred_at`, `request_id`, `correlation_id`, and allowlisted `metadata`. Examples include membership changes, device revocation, payment refunds, invoice finalization, and security-setting changes. Do not audit every read or dump full request bodies.
