# Privacy and Redaction

Never log Authorization/Bearer values, Cookie/Set-Cookie, passwords, APP_KEY, database passwords, API secrets, access/refresh/PAT tokens, private keys, card/payment secrets, OTPs, full request/response bodies, customer records, notes, or financial exports. Prefer stable IDs over names, emails, phones, and addresses.

`SensitiveDataRedactor` provides recursive key-based redaction for dynamic logging contexts. It is not permission to log arbitrary payloads: default logging omits dynamic headers and bodies entirely.
