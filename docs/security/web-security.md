# Web Security

React escaping is the default. `dangerouslySetInnerHTML` requires a documented need and robust sanitization; future rich text must be sanitized before rendering. The current generic local-storage hook is not an authentication mechanism, and web auth tokens must not default to `localStorage` or `sessionStorage`.

For first-party browser auth, the web API client sends cookies with `credentials: 'include'` and configured Sanctum stateful origins use the cookie/CSRF flow. Production CORS origins must be explicit when credentials are allowed—never wildcard origins with credentialed authentication. TLS is mandatory in production; public HTTP cannot protect cookies or tokens. Security headers should be introduced in a tested phased rollout: CSP (including `frame-ancestors`), `X-Content-Type-Options`, `Referrer-Policy`, and `Permissions-Policy`; compatibility may justify `X-Frame-Options` too.

`NEXT_PUBLIC_*` values are public configuration, never secrets.
