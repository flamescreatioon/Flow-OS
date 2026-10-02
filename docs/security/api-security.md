# API Security

API controllers parse requests, invoke application use cases, and return responses. Writes use explicit Form Requests/DTO mapping and allowlists, never `request()->all()`. Security-sensitive fields—including organization ID, role, membership status, platform privilege, financial approval state, and `is_admin`—are server-controlled.

Use Eloquent/query builder/bound parameters; raw SQL requires bindings. Before using a resource ID, verify tenant ownership, active membership, and permission to prevent IDOR/BOLA. API errors may expose safe validation fields but never stack traces, SQL, filesystem paths, tokens, or environment data. Production requires `APP_DEBUG=false`.

Production traffic requires TLS. Reverse-proxy trust must be narrowly configured to the known topology; do not trust arbitrary proxies. Uploaded content is governed by the upload policy. Financial endpoints recalculate authoritative totals and protect finalized records through voids, reversals, adjustments, or compensating entries rather than generic edit/delete.
