# Precision and Scale

Final payments may use integer minor units; unit prices, quantities, percentages, tax, weighted costs, and exchange rates use exact domain-selected decimals. Quantities are not money and may be fractional with a unit of measure. API decimal transport uses locale-neutral strings (`"1500.5000"`), period separator, no commas; clients preserve strings until exact parsing. JS minor-unit values beyond safe integer range require string transport or enforced safe bounds.
