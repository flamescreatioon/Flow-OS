# Tenancy

Organization routes require authentication, organization selection, active membership verification, then authorization. `X-Organization-ID` and a subdomain select only; neither grants access. Cross-tenant resource lookups may return 404 to avoid existence disclosure. Branch context stays explicit in resource data/parameters; no universal branch header is adopted.
