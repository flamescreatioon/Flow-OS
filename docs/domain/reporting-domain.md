# Reporting and Analytics Domain

**Status: ARCHITECTED.** Reporting owns derived read models, materialized summaries, analytical queries, and presentation-oriented aggregates. It consumes authoritative records from owning domains and never becomes transactional truth.

Operational reporting can derive from commercial domains; future financial statements derive from Accounting's ledger. Redis caches and reporting tables are recreatable/read-oriented, not business authority.
