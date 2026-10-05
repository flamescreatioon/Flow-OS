# ADR 0028: Branch Management Implementation

## Status

Accepted

## Context

Sprint 1.4 implements complete organization-scoped Branch management for flowOS. The system already has:
- Laravel API with PostgreSQL
- Sanctum authentication
- Active-user enforcement
- Shared-schema multi-tenancy
- Organization lifecycle (active, suspended, closed)
- Branch model and BranchStatus enum from Sprint 1.1 (active, inactive, closed)
- UUID identifiers
- Organization-scoped branch code uniqueness
- Protected PostgreSQL flowos_test database

## Decision

We implement the following branch management capabilities:

### 1. Nested Organization-Scoped Routes

All branch routes are nested under `/api/v1/organizations/{organization}/`:
- GET/POST `/branches`
- GET/PATCH `/branches/{branch}`
- POST `/branches/{branch}/activate`
- POST `/branches/{branch}/deactivate`
- POST `/branches/{branch}/close`
- POST `/branches/{branch}/make-primary`

### 2. Authorization Model

Until Sprint 1.6 RBAC:
- Read requires active membership in an active organization
- Mutation (create, update, lifecycle, make-primary) allowed for any active member
- Explicitly documented as temporary

### 3. Branch Creation

Fields: name, code, phone, email, address_line_1, address_line_2, city, state, country_code
Code normalized to uppercase. UNIQUE(organization_id, code) enforced at database level.

### 4. First Branch Auto-Primary

When an organization has zero branches, the first created branch automatically becomes `is_primary=true`, regardless of client input.

### 5. Primary Branch Management

- Explicit `make-primary` endpoint
- Transactional switch: previous primary becomes false, new becomes true
- Only active branches can become primary
- PostgreSQL partial unique index: `CREATE UNIQUE INDEX branches_organization_id_primary_unique ON branches (organization_id) WHERE is_primary = true`

### 6. Primary Lifecycle Safety

Primary branch cannot be deactivated or closed. Caller must first make another branch primary.

### 7. Lifecycle Transitions

Valid: active↔inactive, active→closed, inactive→closed
Terminal: closed (no transitions out)
Same-state returns 422

### 8. Update Restrictions

PATCH only allows profile fields. Cannot change: id, organization_id, status, is_primary, created_at.

### 9. Cross-Organization Protection

Explicit validation: `branch.organization_id === organization.id`. Returns 404 (not 403) to avoid information leakage.

## Consequences

### Positive
- Complete branch management for Sprint 1.4
- Strong multi-tenancy isolation
- Database-level primary uniqueness
- Clear lifecycle rules
- Comprehensive test coverage
- OpenAPI documentation updated

### Negative/Risks
- Temporary broad authorization (active members can mutate) - will be replaced in Sprint 1.6
- Race condition theoretical risk on primary switching (mitigated by DB transaction + partial index)
- No DELETE endpoint (by design - closed is terminal)

### Neutral
- Migration adds address fields and partial index
- New service classes follow existing conventions

## Alternatives Considered

1. **Flat routes** (`/api/v1/branches`) - rejected: doesn't enforce organization context in URL
2. **Automatic primary selection on deactivation** - rejected: explicit is safer, caller must decide
3. **Soft delete** - rejected: closed status is sufficient, no hard deletion needed
4. **RBAC now** - rejected: explicitly deferred to Sprint 1.6

## Related

- ADR 0023: Shared Schema Tenancy and Authorization
- ADR 0027: Organization Lifecycle
- docs/engineering/branch-management.md