# ADR 0039: Expenses Foundation

Status: ACCEPTED
Date: 2026-11-07

## Context

`docs/domain/finance-accounting-domain.md` (ARCHITECTED) assigns Finance ownership of "operational Expense, ExpenseCategory, payee/reference direction". Expenses are org-scoped with branch optional (`docs/domain/tenant-scoping.md`) and financially sensitive — the domain relationships matrix gives the deletion direction as "correct/reverse when posted". No expense code exists; the accounting ledger does not exist yet, so operational expenses must stand alone while remaining posting inputs for a future accounting integration.

This decision defines the first expenses slice: expense categories as organization master data and operational expense records with void-based correction.

## Decision

**ExpenseCategory is organization-scoped master data.** A name (unique within the organization), an optional description, and archive-not-delete semantics (mirroring suppliers/products): a category referenced by expenses can never be silently removed. The category list lives on the record-an-expense page, next to the form that consumes it.

**An Expense is an operational money record: recorded, editable, voided.** Fields: category (required), description, amount (positive, exact at the unit price scale — ADR 0014 scaled-BIGINT), `spent_on` (a calendar DATE defaulting to the server's current date; client dates are business context), payee (free text — the "payee/reference direction"; supplier linking is a later join, not a v1 constraint), payment method (`cash | bank_transfer | card | other`), and a note. A receipt gets an organization-scoped sequential `EXP-000001` number from the shared `document_sequences` allocator (ADR 0035), assigned at creation.

**Corrections are explicit.** A recorded expense is editable (category, description, amount, date, payee, method, note) — these are internal operational records, and editability keeps day-to-day use practical before any ledger exists. A void action is terminal: the record is retained as evidence, excluded from every aggregate, and can never be deleted — matching the financial-integrity direction ("correct/reverse", no casual deletion) and the invoices void pattern. If a future accounting integration posts an expense, the void path becomes the reversal trigger.

**No ledger effects in v1.** Expenses do not touch accounts, balances, or journals; they supply posting inputs later. Stock is unaffected. Cash/bank accounts, till sessions, and reconciliation remain future Finance work.

**No attachments, no recurrence in v1.** Receipt images need storage decisions (local disk vs object storage); recurring expenses need scheduling semantics. Both are flagged deferrals.

## Alternatives Considered

- **Immutable expenses (receipts pattern)** — rejected: internal operational records with typos in notes or wrong dates would need a void-and-recreate cycle for trivial fixes; the audit trail remains meaningful because updates are audited and void is terminal.
- **Hard delete** — rejected: contradicts the financial-integrity direction for money records and destroys evidence the "correct/reverse when posted" accounting future will need.
- **Payee as a required Supplier reference** — rejected for v1: most small-business expenses (fuel, airtime, staff reimbursement) have no supplier record; free text now, linking later.

## Consequences

### Positive

- The Finance domain starts with its first operational records, completing the money picture alongside invoicing (money in) and purchasing (goods in).
- The void-based correction keeps totals honest while the dashboard can show spend immediately.
- The category model is small but gives the reporting hooks (spend by category) a stable anchor.

### Negative / Trade-offs

- Editable money records rely on audit events rather than immutability for the trail; acceptable pre-ledger, revisited when posting exists.
- Payee is unstructured text; spend-by-vendor reporting waits for supplier linking.

## Security / Tenancy Impact

Both tables carry `organization_id`; services re-resolve categories through organization-scoped queries and reject cross-tenant references. Permissions: `expense_category.view/create/update/archive` and `expense.view/create/update/void`, wired into system roles with migration backfill. Amounts are server-validated as exact positive decimals; client totals are never trusted.

## Offline / Data Impact

Create, update, and void are idempotency-protected, so an offline replay cannot double-record or double-void. Web expenses are a connected-client flow; the offline outbox direction remains ADR 0034's separate decision.

## References

- `docs/domain/finance-accounting-domain.md`, `docs/domain/tenant-scoping.md`, `docs/domain/domain-relationships.md`
- `docs/adr/0014-financial-data-representation.md`, `docs/adr/0035-purchasing-goods-receipt-foundation.md` (numbering, idempotency), `docs/adr/0036-invoicing-foundation.md` (void pattern)

## Supersedes / Superseded By

None.
