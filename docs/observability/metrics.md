# Metrics

Metrics are numerical aggregates, not logs. Future vendor-neutral categories include HTTP request count/latency/error rate, database errors, queue execution, sync operation/conflict/duration, and cache hit/miss behavior. Business analytics such as POS sales remain product data, not operational metrics.

Do not use high-cardinality labels such as request, user, invoice, sale, or operation IDs. Organization IDs are usually unsuitable labels at scale. OpenTelemetry is a future compatibility candidate; no backend or collector is selected.
