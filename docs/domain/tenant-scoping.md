# Tenant and Branch Scoping

`Organization` is the tenant. Every tenant-owned cloud record is directly organization-scoped or has an unambiguous organization-owned parent; high-risk/common records should carry explicit `organization_id` for safe querying and constraints.

| Classification | Examples | Direction |
|---|---|---|
| Global | User; platform-wide configuration where authorized | No organization selector as authority |
| Organization only | Product, Customer, Supplier, UnitOfMeasure, Category, Account | `organization_id`; no branch required |
| Branch required/contextual | POS Sale, cash/till session | Branch establishes operational context when relevant |
| Location required | StockMovement, transfer, count | InventoryLocation belongs to one Organization; may be within a Branch |
| Branch optional | Invoice, Payment, Expense, JournalEntry, Note/Task, AuditEvent | Use only where operational/reporting meaning exists |
| Multi-branch | Product catalogue and customer/supplier records | Available across branches through organization ownership |

Branch is a reporting and operating dimension, not a second tenancy boundary. Database/application safeguards must reject cross-organization references.
