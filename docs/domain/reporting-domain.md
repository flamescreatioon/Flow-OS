# Reporting and Analytics Domain

**Status: ARCHITECTED.** Reporting owns derived read models, materialized summaries, analytical queries, and presentation-oriented aggregates. It consumes authoritative records from owning domains and never becomes transactional truth.

Operational reporting can derive from commercial domains; future financial statements derive from Accounting's ledger. Redis caches and reporting tables are recreatable/read-oriented, not business authority.

Implemented so far: the organization dashboard summary (`GET /organizations/{organization}/dashboard-summary`) — the first derived read model. It aggregates issued-invoice totals and monthly trend, purchase totals, master-data counts, derived stock on hand, and invoice status counts, all computed on demand from the owning domains' records and never stored as business truth.
