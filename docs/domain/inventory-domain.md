# Inventory Domain

**Status: ARCHITECTED.** Inventory owns StockMovement, InventoryLocation, StockBalance/read model, StockAdjustment, StockTransfer, and StockCount direction. A Branch may contain one or more inventory locations; Branch and location are not interchangeable.

The invariant is absolute: stock state changes only through valid inventory movements. Stock balances are derived from, or transactionally maintained from, movement records. Counts create variance and explicit adjustment movements; transfers create linked source and destination movements; no client may overwrite canonical quantity.

Movement categories include opening, receipt, sale, returns, adjustment, transfer, and count correction. Negative-stock policy and valuation method are organization/product and future accounting decisions, not universal rules.
