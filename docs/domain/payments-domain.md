# Payments Domain

**Status: PLANNED.** Payments owns Payment, PaymentAllocation, PaymentMethod configuration direction, and Refund. A payment is settlement, not a sale or invoice. One sale/invoice may have zero, one, or many allocations; split and partial settlement are required architectural directions.

Payment methods support configured cash, bank transfer, card/terminal, mobile money, credit, and other methods without embedding provider choices in the domain. Providers belong integration adapters. Refunds and reversals are traceable correction records, never payment deletion.
