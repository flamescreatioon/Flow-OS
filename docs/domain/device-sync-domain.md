# Device and Sync Domain

**Status: PARTIAL.** Device lifecycle is architected; mobile sync is prototype-only. The platform owns device registration/revocation direction, local outbox coordination, operation identity, incremental cursor progression, and sync diagnostics.

Sync transports domain operations; it does not own business invariants or perform generic table CRUD. Every pushed operation is authenticated, tenant/permission authorized, idempotent through durable `operation_id`, and validated by the owning application/domain service. Local schemas need not mirror cloud tables.

Offline conflict sensitivity is low for notes, medium for catalogue/customer metadata, and high for stock adjustments, sales, payments, invoices, financial records, and permissions.
