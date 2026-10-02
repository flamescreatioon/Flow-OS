# Authentication

Laravel Sanctum is the authentication foundation. Compatible first-party web deployments use Sanctum SPA/session authentication with HTTP-only cookies, Secure cookies in production, appropriate SameSite settings, and CSRF protection. Mobile and desktop use revocable, scoped Sanctum personal-access tokens; tokens are hashed by Sanctum server-side and never appear in logs or URLs.

Laravel-supported password hashing is required; no custom hashing. Initial password policy favors a reasonable minimum length over arbitrary composition rules. Future login and reset flows must be rate limited and return non-enumerating failures. Resets need short-lived, single-use tokens and secure transport. Session IDs regenerate on login; logout invalidates the applicable session/token. Password hashes are hidden from API output.

Token abilities will use names such as `sales.read`, `inventory.adjust`, `sync.push`, and `sync.pull`. PAT revocation is the current foundation; refresh-token infrastructure is not introduced. MFA, email verification enforcement, and authentication endpoints remain future implementation work.
