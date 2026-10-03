# Organization and Branch Domain

**Status: IMPLEMENTED foundation.** Organization owns the tenant boundary and organization-wide configuration direction; Branch belongs to exactly one Organization and represents an operational location/unit.

Organization-scoped configuration may later include legal identity, locale, base currency, timezone, tax configuration, document numbering, and feature settings. Branch may be a POS, cash, staff, inventory, and reporting dimension, but it is not automatically required on every organization record.

Branch references must belong to the same organization as their parent record. Customer-facing document numbers are organization-scoped business identifiers, not database UUIDs.
