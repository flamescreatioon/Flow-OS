# Offline Time

Offline events may preserve client-reported occurrence context (`client_occurred_at`, client timezone) separately from server `received_at`/`processed_at`. Client clocks are untrusted for security, expiry, audit timestamps, or sync ordering. Sync remains deterministic server ordering plus opaque cursors.
