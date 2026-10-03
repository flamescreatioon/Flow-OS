# Finance, Expenses, Cash, and Accounting Domain

**Status: ARCHITECTED.** Finance owns operational Expense, ExpenseCategory, payee/reference direction, CashAccount, BankAccount, cash/till sessions, transfers, and reconciliation direction. Accounting owns Account, JournalEntry, JournalLine, AccountingPeriod, and posting rules.

Accounting targets double entry: every posted journal entry has total debits equal to total credits. Commercial domains supply posting inputs/events; Accounting constructs and validates ledger records. Sales, Payments, Inventory, and Purchasing do not mutate arbitrary account balances. Inventory valuation and COGS method remain future decisions.

Simple operational adoption must remain possible before full accounting configuration. Financial statements derive from the ledger; operational reports may derive from commercial records.
