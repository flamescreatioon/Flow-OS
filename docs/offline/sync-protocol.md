# Sync Protocol

Planned push requests contain a versioned contract and bounded batch of operations: `operation_id`, `device_id`, entity type/id, operation type, `base_version` where mutable, and payload. Organization context is selected and independently authorized server-side; it is never trusted merely because the client supplied it.

For each operation Laravel must authenticate, validate device/membership/tenant/permission, deduplicate operation ID, validate data/version, apply it, and return an individual result such as `applied`, `duplicate`, `conflict`, `validation_failed`, or `authorization_denied`. Lost responses are normal: retrying an operation must be safe. Dependency order (for example parent before child) is preserved without a speculative general graph.

Pull uses an opaque cursor and returns deterministically ordered changes plus a next cursor. The client applies each batch and its cursor in one local transaction. A client never constructs, sorts, or edits cursors.
