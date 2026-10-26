# ADR 0034: Web Client Offline Behavior and Write Queue

Status: PROPOSED
Date: 2026-10-26

## Context

ADR 0012 accepted local-first synchronization for supported mobile and desktop
workflows and states explicitly that "the web app is not currently a full
local-first client". The `docs/offline/` suite (outbox pattern, idempotency,
conflict resolution, cursors, pull sync) defines the future direction, and
`docs/offline/local-storage-strategy.md` sketches an IndexedDB (Dexie) design
for the web platform. None of the server-side sync machinery exists yet: ADR
0012 records that full sync endpoints, the change log, the device registry,
the idempotency operation store, and conflict UX "require later
implementation".

The web client (`apps/web`) has now grown real business writes: stock
movements, counts, and transfers. Every such write already sends a
client-generated `Idempotency-Key` (replay-safe at the request level), but the
page itself is lost if the browser is offline, the tab closes, or a response
is never observed.

A decision is needed before any offline behavior is built for the web, because
making the web a local-first client would extend the accepted scope of ADR
0012 and must not happen implicitly.

## Decision

Adopt a phased position for the web client.

**Phase 1 (current, accepted behavior).** The web client is a connected
client. It does not cache business records locally and it does not queue
writes. Inventory writes are replay-safe because the client generates the
`Idempotency-Key` at submit time, so a retried submission or a lost response
replays the original result instead of recording the operation twice.

**Phase 2 (proposed).** Introduce an IndexedDB write outbox for the idempotent
inventory writes only (stock movements, counts, transfers), following
`docs/offline/outbox-pattern.md` and the web row of
`docs/offline/local-storage-strategy.md`:

- UUIDv7 operation IDs; the outbox entry commits with the local draft in one
  IndexedDB transaction.
- Bounded batches, retry with backoff, and a visible sync state per operation
  (pending, in flight, applied, retryable failure, terminal rejection).
- Reads stay server-authoritative; the outbox is a write queue, not a local
  database of record.
- Replays are safe for movements (inventory converges through movements, per
  `docs/offline/conflict-resolution.md`) and for counts (an absolute
  observation whose effect is computed by the server and keyed by the
  idempotency key).

**Phase 3 (deferred).** Full local-first web — local reads, incremental pull
with opaque cursors, optimistic versions, conflict UX — only after the server
implements the sync endpoints ADR 0012 requires, and only through its own
follow-up decision. This ADR does not approve Phase 3.

## Alternatives Considered

- **Implement full local-first web now** (Dexie cache of record plus pull
  sync). Rejected: the server-side sync endpoints do not exist, and ADR 0012
  deliberately excludes the web from its accepted scope.
- **Do nothing further.** Rejected: it leaves the web with no story beyond
  per-request idempotency and gives the Phase 2 work no agreed shape; the
  cost of recording the position now is low.

## Consequences

### Positive

- Today's web writes are replay-safe without any new infrastructure.
- The web's relationship to ADR 0012 is explicit instead of implied.
- Phase 2 has a concrete, reviewable shape that reuses the documented outbox
  and idempotency direction.

### Negative / Trade-offs

- Until Phase 2, an offline browser simply fails the write with an error.
- Phase 2 adds a client dependency (an IndexedDB wrapper such as Dexie),
  which requires separate dependency approval.
- IndexedDB is only available in secure contexts; the queue must degrade
  gracefully where it is absent.

## Security / Tenancy Impact

- The outbox must be organization-scoped and cleared on sign-out and on
  organization switch, mirroring the active-organization selection rules.
- Credentials stay in the Sanctum session cookie per ADR 0026; no tokens are
  stored in IndexedDB or localStorage.
- Queued payloads are tenant-context-only; the server re-authorizes every
  replayed operation as it would a live one.

## Offline / Data Impact

- Aligns the web with `docs/offline/offline-first-principles.md` (cloud
  authority, local-first only where scoped) and `outbox-pattern.md`
  (operation IDs, states, bounded batches, no deletion of unconfirmed
  operations).
- Inventory conflict semantics remain movement-based per
  `docs/offline/conflict-resolution.md`; the queue never writes a balance
  directly.

## References

- ADR 0012 (Offline-First Synchronization) and ADR 0026 (Platform
  Authentication Foundation)
- `docs/offline/` suite, in particular `outbox-pattern.md`,
  `idempotency.md`, `conflict-resolution.md`, and
  `local-storage-strategy.md`

## Supersedes / Superseded By

Supersedes nothing. Refines the web scope of ADR 0012; if accepted, Phase 3
will require a later ADR rather than an edit to this one.
