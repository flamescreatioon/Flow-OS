# ADR 0036: Invoicing Foundation

Status: ACCEPTED
Date: 2026-11-07

## Context

Invoicing is a PLANNED domain: `docs/domain/invoicing-domain.md` owns Invoice, InvoiceLine, and CreditNote direction, requires organization-scoped document numbers (separate from UUID identities), and mandates snapshot-on-finalize with credit-note/void correction semantics. Payments (`docs/domain/payments-domain.md`) are a separate PLANNED domain — split and partial settlement is a later design. The organization-level tax model depth and statutory rates are deliberately deferred (`docs/domain/open-domain-questions.md`), but Nigerian businesses commonly invoice with VAT.

The inventory movement engine (ADR 0033) and the purchasing foundation (ADR 0035) give the storage, rounding, numbering, and idempotency patterns this decision follows. This decision defines the first invoicing slice: customers as master data, organization-level tax settings, and an invoice lifecycle of draft → issued → voided with delivery (ADR 0037). Payments, credit notes, and stock effects stay out of scope.

## Decision

**Customers are organization-scoped master data**, mirroring suppliers (ADR 0035): a name plus optional contact details, archived rather than deleted, with `customer_name` snapshotted onto invoices. An invoice may be recorded without a customer (e.g. a walk-in bill), and recipients for delivery default from the customer's contact fields when one is attached.

**Tax is configured at organization level and snapshotted per invoice.** A `tax_settings` row per organization holds `tax_enabled`, `tax_rate_percent` (a percentage at the unit price scale of 4, e.g. `7.5000`), and `tax_mode` (`exclusive` or `inclusive`). The invoice snapshots the enabled rate and mode at issue time, so later settings changes never rewrite issued invoices.

**The calculation is declared, exact, and tested** (per `docs/finance/rounding.md`, which requires each domain to state its rules): canonical order quantity × price → gross → discount → taxable → tax → line total, per line. All arithmetic is scaled-integer. Line total = `round-half-up(quantity × price, scale 4) − discount`. With `R` the rate scaled at 4 and `A` the discounted line total scaled at 4, exclusive tax per line is `round-half-up(A × R / 10^6)`; inclusive tax is `round-half-up(A × R / (10^6 + R))`, because the stated price already contains the tax. Document totals (`subtotal`, `discount_total`, `tax_total`, `grand_total`) are the sums of the line values at scale 4 — in exclusive mode `grand_total = subtotal + tax_total`, in inclusive mode `subtotal = grand_total − tax_total`. The server always recalculates; client-supplied totals are never accepted.

**The lifecycle is draft → issued → voided.** A draft is freely editable (lines replaced as a whole; nothing is versioned). `issue` assigns the organization-scoped sequential number (`INV-000042` from the ADR 0035 sequence allocator), snapshots the customer, tax, and every line's commercial values, stamps `issued_at`, and freezes the document. `void` is allowed from draft or issued and stamps `voided_at`; a voided invoice is retained as evidence and cannot be reissued. There is no update or delete path for an issued invoice; corrections wait for the credit-note design.

**An issued invoice has no stock effect and no payment state in this slice.** Invoices are commercial obligations; stock consumption belongs to Sales, and settlement belongs to Payments — both deferred. `amount_paid`/`balance_due` direction arrives with Payments; the v1 document carries no payment columns.

**Delivery is an adapter boundary** (ADR 0037): sending records a delivery attempt and hands the message to a channel driver. Delivery status never feeds back into invoice correctness — a failed send does not invalidate an invoice, and an invoice exists before and without any delivery.

## Alternatives Considered

- **Per-line tax rates** — rejected for this slice: the user chose organization-level configuration; a per-line rate engine is a superset that can be layered on later without changing the snapshot columns (rate and mode live on the invoice, amounts on the lines).
- **Numbering at draft creation** — rejected: numbers identify finalized obligations; reserving numbers for drafts that may be deleted would burn sequences. The number is assigned exactly at issue.
- **Unlimited draft revisions with audit per edit** — rejected: drafts have no legal weight; an audit event on issue and void is sufficient. Restoring earlier drafts is unnecessary complexity before anyone asks for it.
- **Storing tax as NUMERIC(19,4)** — rejected: ADR 0033's scaled-BIGINT representation is the platform precedent and is exact on both PostgreSQL and SQLite.

## Consequences

### Positive

- The billing loop starts: an obligation document with exact totals, tax, numbering, and delivery, without depending on any deferred domain.
- The declared rounding makes totals reproducible across server, tests, and any future client-side preview.
- The void path gives businesses a correction story before credit notes exist.

### Negative / Trade-offs

- Issued invoices with a wrong tax mode or customer must be voided and recreated; there is no amendment.
- Invoices do not yet reduce stock or track payment, so the document alone is not a complete receivable story until Payments/Sales land.

## Security / Tenancy Impact

Every table carries `organization_id`; services re-resolve customers and products through organization-scoped queries and reject cross-tenant references. Draft editing requires `invoice.update`, issuing `invoice.issue`, voiding `invoice.send`/`invoice.void` (each permission is separate), and sending `invoice.send`. Delivery recipients are server-validated, and the message content is built server-side from snapshot values — client-provided totals are never rendered. Tax settings changes require `invoice.manage` and are audited.

## Offline / Data Impact

State-changing endpoints (`create`, `update`, `issue`, `void`, `send`) are idempotency-protected, so an offline-queued issue replays safely. Web delivery of invoices is a connected-client flow in this slice; the offline outbox direction is ADR 0034's separate decision and is not extended here.

## References

- `docs/domain/invoicing-domain.md`, `docs/domain/payments-domain.md`, `docs/domain/customer-supplier-domain.md`
- `docs/adr/0033-catalogue-and-inventory-foundation.md` (scaled storage, idempotency), `docs/adr/0035-purchasing-goods-receipt-foundation.md` (sequence allocator)
- `docs/finance/quantities-and-unit-prices.md`, `docs/finance/rounding.md`, `docs/finance/transaction-totals.md`

## Supersedes / Superseded By

None.
