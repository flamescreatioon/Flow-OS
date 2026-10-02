# Incident Readiness

Security incidents are triaged by impact, with suspected tenant leakage treated as critical. Preserve evidence without copying secrets; contain by revoking affected tokens/devices/credentials, disabling unsafe access where necessary, and scoping affected organizations and records. Rotate secrets through the applicable runbook, considering `APP_KEY` separately.

Future audit trails must cover membership/role changes, ownership transfer, refunds/voids, financial posting, device revocation, and security configuration. Observability design is deferred to Sprint 0.23, but logs must never include passwords, Authorization headers, tokens, payment secrets, `APP_KEY`, database passwords, or unnecessary personal data.
