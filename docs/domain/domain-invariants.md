# Domain Invariants

1. Tenant selectors never grant authorization; active membership and permission checks do.
2. Tenant-owned records cannot reference data from another organization.
3. Branch references must belong to the same organization as their parent record.
4. Inventory changes occur only through valid StockMovement records.
5. Authoritative money is exact; client totals are not financial authority.
6. Finalized sales, invoices, payments, inventory movements, and journals use explicit correction semantics, not casual mutation/deletion.
7. Historical transaction economics use snapshots and do not depend solely on current catalogue data.
8. Payment settlement supports zero, partial, and split allocations; a payment is not a sale.
9. Customer and supplier balances derive from authoritative obligations/payments/ledger records.
10. Sync cannot bypass owning domain validation, authentication, tenant authorization, or idempotency.
11. `operation_id` is durable across retries; client clocks do not establish server ordering.
12. Redis, caches, and reporting read models are never business authority.
13. Audit evidence is separate from logs, inventory movements, and accounting journals.
14. Posted journals balance: total debits equal total credits.
