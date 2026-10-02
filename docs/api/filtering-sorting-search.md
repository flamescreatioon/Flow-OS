# Filtering, Sorting, and Search

Use explicit filters such as `status`, `branch_id`, `created_from`, and `created_to`; dates are ISO 8601. Use `q` for broad search and `sort=field` / `sort=-field` for descending order. Each endpoint allowlists fields—never interpolate arbitrary SQL columns or relationships. Sparse fields and generic includes are deferred.
