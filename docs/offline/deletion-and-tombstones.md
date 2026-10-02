# Deletion and Tombstones

Physical deletion alone cannot synchronize to an offline client. The future change stream must publish deletion tombstones, represented by deleted-record metadata or explicit delete changes. Local application of a tombstone removes/hides the active record while retaining sufficient sync knowledge.

Retention depends on maximum offline duration, storage growth, and rebootstrap capability; no duration is chosen yet. If a cursor predates retained history, the client must rebootstrap without silently discarding unsynced outbox operations.
