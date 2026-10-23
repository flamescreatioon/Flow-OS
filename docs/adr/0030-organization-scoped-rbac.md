# ADR 0030: Organization-scoped RBAC

Status: ACCEPTED

FlowOS authorizes actions through organization-scoped roles attached to OrganizationMembership, while permissions remain a canonical platform catalogue. Owner has dynamic all-current-permissions semantics; Admin and Member are deliberately limited defaults. This supersedes the temporary active-member mutation rules used before Sprint 1.6.
