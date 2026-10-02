# Financial Test Vectors

Architecture examples, not tax/legal rules; exact decimal arithmetic, round final result half-up to 2 places.

| Case | Inputs | Expected |
|---|---|---|
| multiplication | 3 × 1250.50 | 3751.50 |
| fractional quantity | 3.125 × 82.375 | 257.921875 before rounding; 257.92 final |
| percentage discount | 3 × 1250.50, 10% | gross 3751.50; discount 375.15; net 3376.35 |
| tax exclusive | net 100.00, rate 7.5% | tax 7.50; total 107.50 |
| rounding edge | 1 × 1.005 | 1.01 |
