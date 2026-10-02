# Offline Authorization

Clients may later cache a temporary membership and permission snapshot with organization, branch scope where applicable, captured time, and expiry/revalidation threshold. It is local operating authority only, not permanent server authority. Some sensitive actions remain online-required.

If a session expires while offline, local unsynced work must not be destroyed merely by ending cloud-sync ability; final local-access rules require security review. On reconnect, revoked membership or changed permissions cause the server to reject new operations. The client preserves data safely, stops authorized sync, and informs the user.
