# Concurrency

Expected state/version conflicts return 409, never 500. REST may later use `If-Match` or request version fields; offline sync uses `base_version`. `updated_at` is not an optimistic concurrency token.
