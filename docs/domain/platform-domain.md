# Platform Domain

**Status: PARTIAL.** `User`, `Organization`, `OrganizationMembership`, and `Branch` exist. Role/permission formalization, Device, AuditEvent, notification delivery, and organization configuration expansion are Sprint 1 or later foundations.

`User` is global identity. `Organization` is tenant, business identity, base-currency, timezone, and configuration boundary. `OrganizationMembership` owns organization role and access status. `Branch` is an operational/reporting unit, never a tenant. Future branch access belongs to authorization design, not fields on User.

Device is distinct from User and will represent registered mobile/POS installations, revocation, sync identity, platform, and last-seen context. Notifications are a platform capability and must not determine transactional correctness.
