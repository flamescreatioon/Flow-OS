# Customers and Suppliers Domain

**Status: ARCHITECTED.** Customer and Supplier are separate organization-scoped master-data entities. A shared Party abstraction is deliberately not adopted now: it adds polymorphic complexity before requirements prove one entity must be both roles. Shared contact/address patterns may be introduced later if justified.

Customers own contacts, addresses, and commercial terms direction. Suppliers own analogous procurement-facing details. Neither is a User or authentication identity. Customer receivables and supplier payables must derive from authoritative obligations, payments, and accounting records, never casually editable balance fields.

Master data may be archived/deactivated when safe; historical commercial records retain snapshots.
