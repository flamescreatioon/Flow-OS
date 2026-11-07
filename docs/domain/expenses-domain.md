# Expenses Domain

**Status: ARCHITECTED (ADR 0039).** Finance owns operational Expense, ExpenseCategory, payee/reference direction. Expenses are organization-scoped records with optional branch context, financially sensitive, and corrected by void — never deleted.

Implemented so far (ADR 0039): expense categories (org master data, unique name, archive-not-delete) and operational expenses (category, description, exact positive amount at the unit price scale, spent-on calendar date, payee free text, payment method, note; `EXP-000001` organization-scoped numbering; editable while recorded, void is terminal and excluded from aggregates). No ledger effects in v1 — expenses are posting inputs for a future accounting integration.

Deferred: supplier-linked payees, receipt attachments, recurring expenses, cash/bank accounts and reconciliation, ledger posting.
