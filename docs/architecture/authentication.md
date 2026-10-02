# Authentication Architecture

Authentication answers who the caller is; authorization answers what it may do; tenant authorization answers whether it may act in a selected organization. Laravel Sanctum remains the first-party foundation.

For compatible first-party web/API domains, Sanctum SPA sessions with secure HTTP-only cookies are preferred. Flutter and .NET desktop clients will use revocable Sanctum personal-access tokens, stored later in OS-backed secure storage. Tokens should use narrow abilities where practical, be revocable on logout/compromise, and expire or require re-authentication under a future session policy. Sensitive web tokens must not default to localStorage.

Passwords use Laravel-supported hashing and are never plaintext, logged, or returned. Production accounts will require email verification; phone identity/recovery, MFA (especially owners, finance admins, and platform admins), and login throttling implementation are future work.

The repository has Sanctum's token table and `HasApiTokens`, but no implemented login/logout/me routes or token issuance.
