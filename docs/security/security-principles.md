# Security Principles

flowOS applies least privilege, deny by default, explicit authorization, tenant isolation, defense in depth, and secure defaults. Client input, client clocks, identifiers, headers, subdomains, and UUIDs are not authority. Server application/domain code remains authoritative for business and security decisions.

Credentials and secrets never enter source, URLs, or logs. Controls are proportional to risk, but obscurity is never a security control. Financial totals, approval state, balances, and posted-record transitions are recalculated and enforced server-side.
