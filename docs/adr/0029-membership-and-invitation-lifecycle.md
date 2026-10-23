# ADR 0029: Membership and invitation lifecycle

Status: ACCEPTED

FlowOS keeps invitations separate from OrganizationMembership. Invitations are bearer-secret workflows with hashed, expiring tokens; acceptance transactionally creates an active membership only for the matching authenticated platform user. Membership lifecycle is explicit, with revoked terminal and protections against self-lockout and zero active members.

This preserves OrganizationMembership as the authoritative organization-access relationship. It does not introduce roles or permissions: until Sprint 1.6, active members may manage invitations and memberships subject to the safety invariants.
