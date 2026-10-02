# Incident Diagnostics

1. Obtain the request ID, not a password, token, database dump, or full customer record.
2. Locate protected service logs by request ID, then use safe tenant/user/device/correlation IDs.
3. Trace related requests/jobs, check protected dependency status, and classify validation, authorization, conflict, dependency, domain, or unexpected failure.
4. Preserve evidence under access controls and redact before sharing.

Support should request IDs and timestamps first. Do not paste secrets, cookies, payloads, or local paths into tickets.
