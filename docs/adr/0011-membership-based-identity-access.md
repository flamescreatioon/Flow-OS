# ADR 0011: Membership-Based Identity and Access

## Title

Adopt membership-based identity and access.

## Status

Accepted

## Context

The initial schema attaches `users.organization_id` and `users.role` directly to a user. That cannot represent one human participating in several organizations with distinct roles.

## Decision

`User` represents a human identity independent of any organization. `OrganizationMembership` represents participation in one organization and stores its provisional role and status. RBAC is evaluated in the membership context. Laravel Sanctum remains the first-party authentication mechanism; platform roles remain separate from memberships.

`users.organization_id` and `users.role` are retained as legacy/default data during a staged migration. Tenant selection is authorized only after the authenticated user has an active membership.

## Consequences

- A user can hold different roles in multiple organizations.
- Existing data is backfilled additively; identity consolidation and removal of legacy columns are later work.
- No Passport, JWT library, external IAM, or role/permission package is introduced.
