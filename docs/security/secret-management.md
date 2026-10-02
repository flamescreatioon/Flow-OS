# Secret Management

Secrets include `APP_KEY`, database/Redis credentials, API and payment tokens, email/OAuth credentials, and private keys. `.env` is local and ignored; `.env.example` is tracked and contains no real secret; CI values are disposable; production values come from a managed secret store/environment. Never put secret values in source, documentation, logs, URLs, or `NEXT_PUBLIC_*` values.

Rotate compromised API tokens, database passwords, and third-party keys through controlled operational procedures. `APP_KEY` is unique per environment and requires migration/operational planning in production because it protects encrypted state; do not rotate it casually. Development keys can be regenerated only when persistent encrypted state does not matter. Enable GitHub secret scanning and push protection where repository/account settings permit; this repository does not claim they are enabled without administrative confirmation.
