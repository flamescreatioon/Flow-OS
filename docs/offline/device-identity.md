# Device Identity

A future device identity represents an installation, distinct from a human user and from a token/session. It has an ID, client type (`mobile`, `desktop`, or `pos`), installation identity, organization/branch context where relevant, and registration/revocation state. A user can use many devices; a shared POS can host different user sessions.

Device persistence, registration, and revocation are deferred. Device identity provides accountability and sync controls but never replaces Sanctum authentication or active organization membership.
