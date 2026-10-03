# Current Domain Audit

| Area | Status | Current state |
|---|---|---|
| Platform identity/organization/branch | IMPLEMENTED | User, Organization, OrganizationMembership, Branch foundation exists. |
| Roles/permissions | PARTIAL | Membership role/status exists; full permission foundation is Sprint 1. |
| Device | ARCHITECTED | No lifecycle implementation. |
| Catalogue | PLANNED | No business models. |
| Inventory | ARCHITECTED | Movement model accepted; no implementation. |
| Purchasing | PLANNED | No implementation. |
| Customers/Suppliers | ARCHITECTED | Boundary chosen; no models. |
| Sales/POS | PLANNED | No implementation. |
| Invoicing | PLANNED | No implementation. |
| Payments | PLANNED | No implementation. |
| Expenses/Accounting | ARCHITECTED | Double-entry target; no ledger. |
| Productivity | PLANNED | No implementation. |
| Reporting/analytics | ARCHITECTED | Non-authoritative direction only. |
| Sync | PARTIAL | Mobile prototype exists; approved protocol is unimplemented. |
| Audit | ARCHITECTED | Audit persistence is future. |

## Delivery sequence

1. Sprint 1: platform identity, organizations, branches, memberships, role/permission foundation, device/audit foundation.
2. Sprint 2: catalogue and inventory foundations.
3. Sprint 3: sales and POS workflows, payment foundations.
4. Sprint 4: production sync protocol and conflict handling.
5. Sprint 5: invoicing, finance, accounting, and commercial-to-ledger integrations.
