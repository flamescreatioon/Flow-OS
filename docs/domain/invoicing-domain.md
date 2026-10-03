# Invoicing Domain

**Status: PLANNED.** Invoicing owns Invoice, InvoiceLine, and CreditNote direction. It is separate but closely related to Sales because invoices can arise independently of an immediate POS sale and can represent credit obligations.

Invoice/document numbers are organization-scoped business identifiers and may be branch-aware; UUIDv7 entity IDs are separate. Finalized invoice lines snapshot commercial values. Corrections use credit notes, voids, reversals, or future accounting-approved workflows rather than generic deletion.
