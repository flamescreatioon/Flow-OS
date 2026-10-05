# Branch Management

## Overview

This document describes the organization-scoped Branch management implementation in flowOS Sprint 1.4.

## Organization Ownership

Every Branch belongs to exactly one Organization. The `organization_id` field is authoritative and immutable through normal update APIs. A branch must never be moved between organizations through normal update APIs.

Nested routes verify both:
- User has active membership in the organization
- Branch actually belongs to that organization

Do not assume that because a user belongs to both organizations, a branch from one organization may be addressed through another organization's URL.

## Routes

All branch routes are nested under `/api/v1/organizations/{organization}/`:

| Method | Path | Description |
|--------|------|-------------|
| GET | `/branches` | List branches (active, inactive, closed) |
| POST | `/branches` | Create a new branch |
| GET | `/branches/{branch}` | Get branch details |
| PATCH | `/branches/{branch}` | Update branch profile fields |
| POST | `/branches/{branch}/activate` | Activate an inactive branch |
| POST | `/branches/{branch}/deactivate` | Deactivate an active branch |
| POST | `/branches/{branch}/close` | Close an active or inactive branch |
| POST | `/branches/{branch}/make-primary` | Make a branch the primary branch |

**DELETE is not implemented.** Branches are not hard deleted; closed is terminal.

## Authorization

Until Sprint 1.6 RBAC exists:
- **Read** requires active membership in the organization
- **Branch mutation** (create, update, lifecycle, make-primary) may be performed by active members

This broad active-member mutation authorization is **temporary** and will be replaced in Sprint 1.6 with proper role-based access control.

The organization must also be active for normal tenant branch operations.

## Branch Creation

Supported fields:
- `name` (required)
- `code` (required) - normalized to uppercase
- `phone`
- `email`
- `address_line_1`
- `address_line_2`
- `city`
- `state`
- `country_code` (ISO 3166-1 alpha-2)
- `is_primary` (controlled creation intent; server may override)

### Code Normalization

Branch codes are consistently normalized to uppercase. The existing database rule remains:

```
UNIQUE(organization_id, code)
```

Therefore:
- Org A / MAIN + Org B / MAIN = valid
- Org A / MAIN + Org A / MAIN = invalid

### First Branch Rule

When an organization has no branches, the first created branch **must** become `is_primary=true` automatically. This remains true even if the client omitted `is_primary` or attempted `false`.

## Primary Branch

An organization should have at most one primary branch.

### Make Primary Endpoint

Explicit endpoint: `POST /organizations/{organization}/branches/{branch}/make-primary`

When a new branch becomes primary:
- Previous primary becomes `false` (transactionally)
- New branch becomes `true`

Only an active branch can become primary. Inactive or closed branches cannot become primary.

### Database-Level Protection

A PostgreSQL partial unique index enforces at most one primary branch per organization:

```sql
CREATE UNIQUE INDEX branches_organization_id_primary_unique
ON branches (organization_id) WHERE is_primary = true;
```

This provides database-level protection against race conditions.

## Primary Lifecycle Safety

A current primary branch **must not** be deactivated while it is primary.
A current primary branch **must not** be closed while it is primary.

The caller must first make another active branch primary. The system does not silently choose a replacement branch.

## Branch Update (PATCH)

PATCH may update ordinary profile fields:
- `name`
- `code` (uniqueness enforced per organization)
- `phone`
- `email`
- `address_line_1`
- `address_line_2`
- `city`
- `state`
- `country_code`

PATCH must **NOT** change:
- `id`
- `organization_id`
- `status`
- `is_primary`
- `created_at`
- `deleted_at`

Lifecycle and primary changes use dedicated endpoints.

## Lifecycle

Valid transitions:
```
active    -> inactive
active    -> closed
inactive  -> active
inactive  -> closed
```

`closed` is **terminal**.

Rejected transitions:
```
closed -> active    (denied)
closed -> inactive  (denied)
```

Same-state transitions (e.g., deactivate an already inactive branch) return a 422 error with an appropriate message.

No hard deletion is implemented.

## Listing

GET `/branches` returns branches belonging only to the requested organization.

Management listing retains visibility of:
- `active`
- `inactive`
- `closed`

Historical branches are not silently discarded.

## Show

Branch detail requires:
- Authenticated active user
- Active membership in organization
- Active organization
- Branch belongs to organization

A branch from another organization **must fail** even if the same user belongs to both organizations.

## Request Classes

- `StoreBranchRequest` - creation validation
- `UpdateBranchRequest` - update validation (all fields `sometimes`)

Validation is appropriate and simple. No full postal-address validation.

## Resource

`BranchResource` returns:
- `id`
- `organization_id`
- `name`
- `code`
- `phone`
- `email`
- `address_line_1`
- `address_line_2`
- `city`
- `state`
- `country_code`
- `is_primary`
- `status`
- `created_at`
- `updated_at`

## Service/Action Layer

Centralized in `app/Application/Branches/`:

- `BranchService` - branch creation with first-branch-primary logic
- `BranchLifecycleService` - lifecycle transitions with validation
- `BranchPrimaryService` - primary switching in a database transaction

Primary switching uses a database transaction.

## Tenancy / Route Binding

Laravel scoped route binding is used. Explicit validation in controller:
```php
abort_unless($branch->organization_id === $organization->id, 404);
```

Do not rely on insecure unscoped implicit bindings.

## Tests

Comprehensive test coverage for:
- Creation (active member, non-member, suspended/revoked membership, suspended/closed org, first branch primary, UUID generation, ownership, code normalization, duplicate code rejection, cross-org code reuse)
- Listing (org isolation, all statuses visible, unrelated excluded, non-member denied, primary-first ordering)
- Show (valid nested, cross-org fails, unrelated user denied, full field inclusion)
- Update (allowed fields, code uniqueness, org_id immutable, status immutable, is_primary immutable, cross-org fails, code normalization)
- Lifecycle (all valid transitions, terminal closed, primary protection, same-state behavior)
- Primary (first branch primary, second doesn't auto-replace, atomic switch, previous becomes false, exactly one primary, inactive/closed cannot become primary, cross-org fails, idempotent for already-primary)

## Concurrency Considerations

The partial unique index on `(organization_id) WHERE is_primary = true` provides database-level protection for primary branch uniqueness. However, there is a theoretical race condition between:
1. Application-level check for existing primary
2. Database index enforcement

This is mitigated by:
- All primary switching happens in a database transaction
- The partial unique index is the ultimate authority

For extremely high-contention scenarios, application-level locking could be added later, but the current implementation is sufficient for the expected load profile.

## Temporary Authorization

The current authorization model (any active member can mutate branches) is explicitly **temporary**. Sprint 1.6 RBAC will introduce proper roles (Owner, Admin, Manager, Staff) with granular permissions for branch operations.

Do not create temporary Owner/Admin/Manager roles in this sprint.