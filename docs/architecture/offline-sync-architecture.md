# Offline Sync Architecture

Mobile and desktop/POS are local-first. The target lifecycle is:

```mermaid
flowchart LR
  Local[Local transaction] --> Domain[Client domain write]
  Domain --> Outbox[Transactional outbox operation]
  Outbox --> Push[Bounded push]
  Push --> Server[Authentication, tenant/permission authorization, validation, idempotency]
  Server --> Commit[Server commit]
  Commit --> Pull[Incremental pull with opaque cursor]
  Pull --> Apply[Local application and tombstones]
```

`operation_id` is UUIDv7-style durable idempotency identity, not a log correlation ID. Pull uses server ordering and opaque cursors, not timestamp cursors. Editable data may require version conflict workflows; inventory is movement-derived, and finalized financial records use reversals/voids/adjustments rather than generic overwrite/delete. See the detailed [offline documentation](../offline/README.md).

**Current prototype warning:** `SyncEngine` still contains generic CRUD, UUIDv4/timestamp, and replace-on-pull assumptions. It is not the approved production protocol.
