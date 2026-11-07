# ADR 0035: Purchasing Goods Receipt Foundation

Status: ACCEPTED
Date: 2026-11-07

## Context

The inventory movement engine (ADR 0033) is live: immutable `stock_movements`, derived `stock_balances`, the `receipt` movement category, and a nullable `unit_cost` on every movement. Nothing generates receipt movements automatically yet, and purchasing has no records at all. The purchasing domain doc (`docs/domain/purchasing-domain.md`) is a boundary statement: purchasing owns GoodsReceipt direction, may record purchases without formal purchase orders, requests inventory receipt effects through Inventory, and never edits stock balances directly.

This decision defines the first implementation slice of purchasing: suppliers as master data and lightweight goods receipts that put stock into a location with a cost. Purchase orders, purchase returns, and supplier payables stay out of scope until their own designs exist.

## Decision

**Suppliers are organization-scoped master data.** A supplier carries a name and optional contact details (email, phone, address). There is no shared customer/supplier "Party" abstraction (per `docs/domain/customer-supplier-domain.md`). Suppliers are archived, never deleted, and historical receipts keep a `supplier_name` snapshot so a later rename or archive cannot rewrite history. A receipt may be recorded without a supplier to keep lightweight one-off purchases possible; attribution can be added by later corrections, not by editing the receipt.

**A goods receipt is a finalized-on-creation, immutable commercial record.** There is no draft or approval state in this slice: `POST /goods-receipts` creates the receipt, its lines, and the stock movements in one transaction, and the receipt has no update or delete path. A receipt carries an organization-scoped sequential `receipt_number` (allocated from a `document_sequences` table under the same transaction lock as the write) rendered as `GRN-000001`, a business date `received_on` (defaults to the server's current date; client-supplied dates are business context, not server ordering), one receiving location, and per-line quantity, unit cost, and line total.

**Line totals and document totals are exact.** Quantities use the platform scale 3, unit costs and totals use the unit price scale 4 (ADR 0033 storage). The line total is `round-half-up(quantity × unit_cost, scale 4)`; the receipt total is the sum of line totals, computed by the server from validated inputs. Client-supplied totals are never accepted (invariant #5, `docs/finance/`).

**Receipt effects go through Inventory.** Each receipt line produces one `receipt`-category stock movement recorded by `StockMovementService` inside the receipt's transaction, carrying the line's unit cost and the receipt id as the movement `reference`. Purchasing code writes no `stock_*` tables directly. Only stocked, non-archived products may be received; an archived supplier cannot receive goods; an archived location is rejected by Inventory's existing guards.

**Document numbers are organization-scoped sequences.** The `document_sequences` table holds `(organization_id, document_type, next_value)` and is allocated with `lockForUpdate` inside the write transaction, so concurrent receipts cannot collide or skip numbers under normal operation. Branch-aware numbering and prefixes beyond `GRN`/`INV` are deferred.

**Corrections are future purchase returns.** Wrong receipts are corrected by explicit corrective records (returns producing opposite movements), never by editing or deleting receipts or movements. The return design is deferred; until it exists, a mistaken receipt can be compensated with an inventory adjustment movement by an operator with the adjust permission.

## Alternatives Considered

- **Draft/approved receipt state machine** — rejected for this slice: lightweight recording is the documented purchasing direction, and a state machine without returns or payables has nothing to approve against. The `status` column exists so a later state machine does not need a migration of intent.
- **Required supplier on every receipt** — rejected: the domain doc explicitly permits lightweight purchase recording; forcing master data creation blocks the fastest path to correct stock and cost.
- **Writing stock balances directly from purchasing** — forbidden by the domain boundary (`Purchasing → Inventory COMMAND`); every balance write stays behind `StockMovementService`.
- **Storing line totals at the full multiplication scale (7)** — rejected: commercial documents state totals at the currency working scale (4) per `docs/finance/quantities-and-unit-prices.md`; rounding is half-up, declared here, and applied once at the line boundary.

## Consequences

### Positive

- The inventory loop closes: goods in now flow through the same movement authority as manual movements, with cost capture for future valuation.
- Receipts are auditable, idempotent (middleware), and immutable, matching ADR 0033 semantics.
- The sequence allocator is generic and reusable by invoicing (ADR 0036).

### Negative / Trade-offs

- Receipts cannot be edited after creation, including to attach a forgotten supplier; corrections wait for the purchase return design.
- The sequence table serializes concurrent receipts per organization on one row lock; acceptable at current write volumes and replaceable later with gap-tolerant allocation.

## Security / Tenancy Impact

Every table carries `organization_id`; services re-resolve all referenced records through organization-scoped queries and reject cross-tenant references with a validation error. Receipt creation requires the `purchase.receive` permission, supplier management requires `supplier.*` permissions, and reads require the matching view permission. Supplier contact details are business data, not authentication identities, and are never treated as accounts.

## Offline / Data Impact

A queued offline receipt replays through the same idempotent endpoint (`Idempotency-Key` + `document_sequences` allocation inside the transaction), so a replayed receipt cannot double-receive stock or double-consume a number. The movement `reference` links each receipt movement back to its source receipt (ADR 0033).

## References

- `docs/domain/purchasing-domain.md`, `docs/domain/customer-supplier-domain.md`
- `docs/domain/domain-relationships.md` (Purchasing → Inventory COMMAND)
- `docs/adr/0033-catalogue-and-inventory-foundation.md` (movement authority, scaled storage)
- `docs/finance/quantities-and-unit-prices.md`, `docs/finance/rounding.md`

## Supersedes / Superseded By

None.
