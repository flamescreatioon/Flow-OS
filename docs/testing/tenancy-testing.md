# Tenancy and Authorization Testing

Tenant isolation is critical. Test authentication, membership, and authorization separately. Existing feature tests cover active membership via subdomain/header, membership denial, health-route bypass, and request-end tenant cleanup.

Every future tenant-owned resource requires negative-path tests proving Org A cannot read or mutate Org B records, including when a UUID is guessed. Required cases include organization resolution, active/inactive/missing membership, branch ownership, platform actors, tenant cleanup after errors/jobs, and permission denial for an authenticated member when authorization exists.

Possession of an organization or resource ID never implies access. Tests must use distinct organizations and explicit membership setup so cross-tenant leakage cannot be hidden by shared fixtures.
