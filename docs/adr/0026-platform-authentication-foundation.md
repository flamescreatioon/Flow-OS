# ADR 0026: Platform Authentication Foundation

Status: ACCEPTED
Date: 2026-10-05

## Context

flowOS needs browser, mobile, and desktop authentication without conflating identity with organization access.

## Decision

Use Laravel Sanctum sessions for first-party browser clients and Sanctum personal access tokens for Flutter and desktop clients. Browser sessions use HttpOnly cookies and Sanctum CSRF protection; bearer tokens are not used for normal web authentication. Personal access tokens have UUID identifiers and wildcard abilities until RBAC is introduced.

All protected endpoints enforce active account lifecycle state after authentication. Password resets use Laravel's broker and revoke existing personal access tokens. Email verification uses signed temporary URLs but is not a global gate in this sprint.

## Consequences

Authentication establishes platform identity only. Organization membership remains the authority for organization selection, and future RBAC remains the authority for in-organization actions.

## Security / Tenancy Impact

Suspended and disabled accounts cannot use existing sessions or tokens. Authentication endpoints do not establish tenant context. Tenant authorization remains membership-based.

## Supersedes / Superseded By

Extends ADR 0011 and ADR 0023 without changing their membership or tenancy decisions.
