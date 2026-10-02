# Rate Limiting

Named rate-limit policy is required before auth and business endpoints are added. Separate categories are login, password reset, registration/invitations, ordinary API, sync, reports, and expensive search. Limits need observed usage and product/security reasoning—not arbitrary production numbers.

Login and reset limits must be stricter than reads and should combine normalized account identifier with IP and device/user context where available, rather than rely only on IP in shared/NAT networks. Sync policy additionally limits request bytes, batch bytes, operations per batch, and request rate without blocking legitimate reconnects. Redis-backed counters are temporary controls, not security authority.
