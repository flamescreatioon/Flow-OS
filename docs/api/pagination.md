# Pagination

Use `page` and bounded `per_page` (default/maximum are endpoint configuration). Collections return `data`, `meta` (`page`, `per_page`, `total`, `last_page`) and `links` (`first`, `last`, `prev`, `next`). Cursor pagination is reserved for large chronological feeds; its cursors are separate from sync cursors.
