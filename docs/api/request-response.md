# Request and Response

Normal APIs accept/return UTF-8 JSON in `snake_case`. A resource response is `{ "data": {} }`, a collection starts `{ "data": [] }`; 201 returns created data and Location where practical, while genuine empty delete responses use 204. Booleans are JSON booleans and null means genuinely absent. Use ISO 8601 UTC timestamps and `YYYY-MM-DD` business dates. Authoritative money is integer minor units, decimal strings, or structured money—not binary floats; currencies use ISO 4217.
