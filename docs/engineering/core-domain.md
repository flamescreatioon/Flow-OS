# Core Domain Contract

Sprint 1.1 establishes the platform identity and tenancy contract used by later domains.

## Identity and tenancy

`User` is a platform-level identity with a UUIDv7-style identifier and globally unique email. It has no `organization_id`, `branch_id`, or role field. Its lifecycle status is `active`, `suspended`, or `disabled`.

`OrganizationMembership` is the sole authoritative relationship granting a user access to an organization. It is unique per `(organization_id, user_id)` and may be `invited`, `active`, `suspended`, or `revoked`. Only an active membership of an active user in an active organization can establish tenant context.

The tenant selector (subdomain or `X-Organization-ID`) proposes context only. `TenantMiddleware` authenticates the request, validates the organization and membership, then establishes the request-scoped `TenantContext`. It clears both the application context and the existing multitenancy current tenant after the request.

## Organization and branch ownership

`Organization` is the shared-schema tenant. It uses a UUIDv7-style ID, globally unique slug, Nigerian defaults (`NGN`, `Africa/Lagos`), business/contact metadata, and lifecycle status `active`, `suspended`, or `closed`.

`Branch` belongs to an organization; it never replaces organization ownership. It has a UUIDv7-style ID, a code unique within its organization, contact/address metadata, `is_primary`, and lifecycle status `active`, `inactive`, or `closed`.

All future tenant-owned records must have `organization_id`, and all access paths must scope their queries to the validated tenant context. A `branch_id`, where relevant, is additional scope and never a substitute for `organization_id`.

## Lifecycle and deletion

Core hierarchy records use lifecycle states rather than cascade deletion. Foreign keys from branches and memberships restrict deletion of their organizations and users. This preserves auditability and prevents an accidental parent deletion from erasing business records.

## Offline implication

UUIDv7-style identities can be generated without coordination. Sync transports must submit domain operations within validated organization context; client-selected tenant IDs, clocks, and authorization claims are not trusted.
