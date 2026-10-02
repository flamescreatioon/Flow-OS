# Sync Security

Sync endpoints will require Sanctum authentication, explicit device identity, active organization membership, tenant context, and per-operation authorization. The server validates the relationship among authenticated user, device, record organization, and branch; local IDs and cached permissions are untrusted inputs.

Offline capability never bypasses tenant isolation. Rejected operations are surfaced without exposing another tenant's records. Sensitive token/device data is kept in platform secure storage later; sync telemetry avoids payloads, credentials, and secrets.
