# Offline and Sync Testing

Future offline tests cover: create/update while offline; restart while offline; retry; duplicate operation; reconnect; idempotent push; pull; conflict; tombstone; revoked permission before reconnect; and cursor expiry/rebootstrap. Test record UUIDv7 and operation UUIDv7 separately.

Server sync tests must show that submitting the same `operation_id` twice produces one side effect. Conflict tests cover current/stale base versions and domain-specific resolution; never assume universal last-write-wins. Test ordering comes from server sequences/cursors, never client clocks.

Use local database and controlled transport fakes for most tests. Production sync endpoints and a full sync engine are not implemented in Sprint 0.19, so this is a required future matrix rather than prototype validation.
