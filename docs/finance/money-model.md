# Money Model

Money is an exact amount plus ISO 4217 currency. PostgreSQL uses BIGINT minor units where fixed currency precision is sufficient, or field-specific `NUMERIC(p,s)` for unit prices, prorations, rates, and exact intermediates. There is no universal `NUMERIC(19,4)` scale. Never use REAL/DOUBLE, PHP float, JS number, Dart double, or C# float/double as authoritative money.

Future Money value objects are immutable, currency-aware, exact, and reject cross-currency arithmetic without explicit exchange. Negative and zero values remain domain decisions.
