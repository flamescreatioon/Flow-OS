# Dates and Business Dates

Pure dates use PostgreSQL `DATE` and API `YYYY-MM-DD`, never UTC conversion. A future `business_date` is distinct from `created_at`; `occurred_at` records event time while `created_at` records persistence time. Financial records may also need transaction, posting, and due dates.
