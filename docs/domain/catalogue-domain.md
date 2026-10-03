# Catalogue Domain

**Status: ARCHITECTED.** Catalogue owns Product, Service, Category, UnitOfMeasure, optional Variant/Barcode, base/list Price, and tax classification direction. Product types may include stocked item, non-stocked item, service, and future bundle/composite; manufacturing/BOM is not designed here.

Catalogue describes what can be bought or sold. It does not own stock quantity, historical sale price, purchase cost, or accounting valuation. Units are organization-configurable and support quantities such as piece, carton, kg, litre, bag, roll, and pack.

Transaction lines snapshot product reference, description, unit, price/tax/discount information as required. Historical commercial records must not render solely from current catalogue data.
