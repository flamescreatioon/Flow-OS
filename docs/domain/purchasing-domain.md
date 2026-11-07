# Purchasing Domain

**Status: ARCHITECTED (ADR 0035).** Purchasing owns PurchaseOrder where used, GoodsReceipt, supplier invoice/obligation direction, and PurchaseReturn. The architecture permits lightweight purchase recording for businesses that do not use formal purchase orders.

Purchasing references Supplier and Catalogue, requests inventory receipt effects through Inventory, and later supplies finance/accounting posting inputs. It never edits stock balances directly. Purchase returns are explicit corrective records rather than edits to historical receipts.

Implemented so far (ADR 0035): Supplier master data and immutable, finalized-on-creation goods receipts that record receipt-category stock movements with unit cost through `StockMovementService`, with organization-scoped document numbering. Purchase orders, purchase returns, and supplier payables remain future work.
