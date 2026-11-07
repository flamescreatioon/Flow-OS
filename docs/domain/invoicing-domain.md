# Invoicing Domain

**Status: ARCHITECTED (ADR 0036).** Invoicing owns Invoice, InvoiceLine, and CreditNote direction. It is separate but closely related to Sales because invoices can arise independently of an immediate POS sale and can represent credit obligations.

Invoice/document numbers are organization-scoped business identifiers and may be branch-aware; UUIDv7 entity IDs are separate. Finalized invoice lines snapshot commercial values. Corrections use credit notes, voids, reversals, or future accounting-approved workflows rather than generic deletion.

Implemented so far (ADR 0036): Customer master data, organization-level tax settings (snapshotted at issue), the draft → issued → voided lifecycle with organization-scoped sequential numbering, exact server-computed totals with declared rounding, and delivery through the notification channel boundary (ADR 0037: email via Laravel Mail; SMS/WhatsApp behind the log driver until a provider is selected). Credit notes, payments/settlement, and invoice-driven stock effects remain future work — Payments and Sales own those designs.
