# Financial Testing

Financial tests use deterministic strings and integer minor-unit outcomes. Never assert authoritative behavior through binary floating point. Cover currency precision, rounding policy when implemented, tax/discount/FX rules when implemented, negatives, zero, safe range, and rejection of malformed/excess precision input.

Canonical parsing vectors:

| Currency | Input | Expected |
|---|---|---|
| NGN | `"1500.50"` | `150050` minor units |
| NGN | `"-1500.50"` | `-150050` minor units |
| NGN | `"0.01"` | `1` minor unit |
| NGN | `"12.999"` | reject |
| JPY | `"1500"` | `1500` minor units |
| JPY | `"1500.00"` | reject under strict policy |

The web/shared test suite executes these parser vectors. Future PHP, Dart, and C# implementations must reproduce the same domain outcomes; no cross-language harness is required yet.
