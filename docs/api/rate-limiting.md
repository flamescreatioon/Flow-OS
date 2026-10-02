# Rate Limiting

Future limits distinguish public, authentication, standard authenticated, sync, and high-cost routes. Exceeded limits return 429 and Retry-After where appropriate. Limits, page sizes, bulk sizes, search input, and upload sizes are bounded by endpoint configuration; no arbitrary production values are chosen here.
