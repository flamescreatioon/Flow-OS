# Currencies

Use ISO 4217 codes such as `NGN`, `USD`, and `GBP`. NGN is the initial default, not an architectural limit. Organizations will later configure a base currency; transaction currency, base currency, and reporting currency are distinct. Currency metadata must define scale—never assume two decimals or branch-specific base currencies.
