# ADR 0027: Organization Lifecycle

Status: ACCEPTED
Date: 2026-10-05

Organizations are created with a transactional active creator membership and Nigerian defaults. Lifecycle is explicit (`active`, `suspended`, `closed`), with closed terminal and no hard deletion. Until Sprint 1.6 introduces RBAC, active members may mutate their organizations; membership remains the read/access boundary.
