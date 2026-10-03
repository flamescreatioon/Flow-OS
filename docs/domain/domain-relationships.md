# Domain Relationships and Entity Ownership

| Source | Target | Interaction | Reason |
|---|---|---|---|
| Sales | Catalogue | REFERENCE | Product/service selection and snapshots |
| Sales | Inventory | COMMAND | Finalization requests valid stock movements |
| Sales / Invoicing | Payments | REFERENCE / COMMAND | Obligations are settled through allocations |
| Purchasing | Catalogue | REFERENCE | Purchased items/services |
| Purchasing | Inventory | COMMAND | Receipt/return movement effects |
| Commercial domains | Accounting | DOMAIN EVENT / COMMAND | Request validated posting inputs |
| All high-risk domains | Audit | DOMAIN EVENT | Durable action evidence |
| Reporting | All owners | READ MODEL | Non-authoritative summaries |
| Sync | All owners | COMMAND | Delivers operations to owning services |

No arrow permits direct mutation of another domain's internal tables. Operations requiring atomic correctness may use one PostgreSQL transaction inside the modular monolith.

## Entity ownership matrix

| Entity | Owning domain | Tenant scope | Branch scope | Offline | Financially sensitive | Deletion direction |
|---|---|---|---|---|---|---|
| User | Platform | Global | None | No | No | Deactivate/retention policy |
| Organization | Platform | Tenant root | None | No | Indirect | Deactivate lifecycle |
| OrganizationMembership | Platform | Organization | Optional future access dimension | No | Security | Revoke/suspend |
| Branch | Organization | Organization | Self | Limited | Indirect | Archive/deactivate |
| Device | Platform | Organization | Optional | Yes | Security | Revoke |
| Product / Service | Catalogue | Organization | Organization only | Yes | Indirect | Archive/deactivate |
| Customer / Supplier | Commercial master data | Organization | Organization only | Yes | Indirect | Archive/deactivate |
| StockMovement | Inventory | Organization | Inventory location required | High | High | Correction movement |
| Purchase / receipt | Purchasing | Organization | Branch/location contextual | High | High | Return/cancel workflow |
| Sale / SaleLine | Sales | Organization | Branch contextual/required for POS | High | High | Void/return/refund |
| Invoice / InvoiceLine | Invoicing | Organization | Branch optional/contextual | High | High | Credit note/void/reversal |
| Payment / allocation | Payments | Organization | Branch optional/contextual | High | High | Refund/reversal |
| Expense | Finance | Organization | Branch optional | High | High | Correct/reverse when posted |
| JournalEntry / JournalLine | Accounting | Organization | Branch reporting dimension optional | High | Critical | Reversal only after posting |
| Note / Task | Productivity | Organization | Branch optional | Medium | Low | Archive/delete policy per feature |
| AuditEvent | Audit | Organization where applicable | Branch optional | No | Security | Append-only/retention policy |
