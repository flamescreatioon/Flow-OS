# Sales and POS Domain

**Status: PLANNED.** Sales owns Sale, SaleLine, Discount, tax snapshots, and SalesReturn. POS is a sales workflow/channel, not a separate accounting bounded context. POS sale finalization may request Inventory and Payments effects, then emit later accounting posting inputs.

Finalized sales snapshot product reference/description, quantity, unit price, discount, tax, currency, and totals. Catalogue changes never rewrite historical economics. Lifecycle direction is draft, finalized/completed, voided, and returned/refunded; finalized sales are not casually deleted or edited.

Future POS cash sessions/tills sit at the Salesâ€“Finance boundary. A sale may be unpaid, partly paid, or settled through several payments.
