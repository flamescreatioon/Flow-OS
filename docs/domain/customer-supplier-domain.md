# Customers and Suppliers Domain

**Status: ARCHITECTED.** Customer and Supplier are separate organization-scoped master-data entities. A shared Party abstraction is deliberately not adopted now: it adds polymorphic complexity before requirements prove one entity must be both roles. Shared contact/address patterns may be introduced later if justified.

Customers own contacts, addresses, and commercial terms direction. Suppliers own analogous procurement-facing details. Neither is a User or authentication identity. Customer receivables and supplier payables must derive from authoritative obligations, payments, and accounting records, never casually editable balance fields.

Master data may be archived/deactivated when safe; historical commercial records retain snapshots.

Implemented so far: Supplier entities with goods receipts (ADR 0035) and Customer entities with invoices (ADR 0036) — both with name, contact details, archive-not-delete semantics, and name snapshots on the commercial documents. Contact details on customers also serve as default delivery recipients (ADR 0037). Commercial terms and contact/address structure beyond the current fields remain future work.
