# flowOS Domain Map

This directory defines ownership and boundaries for future flowOS business domains. It is not a database schema or implementation plan. A domain owns its invariants and lifecycle; another domain may reference it through an explicit application interface, event, or read dependency.

`IMPLEMENTED` means code exists, `PARTIAL` means a foundation exists, `ARCHITECTED` means an accepted target boundary, and `PLANNED` means future product work.

Platform domains provide identity, tenancy, devices, sync, audit, notifications, and configuration. Business domains own commercial and operational records. See the [domain map](domain-map.md), [ownership matrix](domain-relationships.md), [invariants](domain-invariants.md), and [tenant scoping](tenant-scoping.md).

## Domain documents

- [Platform](platform-domain.md), [organization](organization-domain.md), and [device/sync](device-sync-domain.md)
- [Catalogue](catalogue-domain.md), [inventory](inventory-domain.md), [purchasing](purchasing-domain.md), and [customers/suppliers](customer-supplier-domain.md)
- [Sales](sales-domain.md), [invoicing](invoicing-domain.md), [payments](payments-domain.md), [expenses](finance-accounting-domain.md), and [finance/accounting](finance-accounting-domain.md)
- [Notes/tasks](notes-tasks-domain.md), [reporting](reporting-domain.md), and [audit](audit-domain.md)
- [Current audit](current-domain-audit.md) and [open questions](open-domain-questions.md)
