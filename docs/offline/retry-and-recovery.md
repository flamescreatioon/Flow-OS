# Retry and Recovery

Retryable network and transient-server failures use exponential backoff with jitter; clients never busy-loop. Operations classify as `network_error`, `retryable_server_error`, `authentication_required`, `authorization_denied`, `validation_failed`, `conflict`, `non_retryable_error`, or `rebase_required`.

Authentication or authorization changes stop authorized sync and preserve unsynced local work for user support/recovery. A rebootstrap never silently drops the outbox. Local batch application and cursor persistence are transactional so clients do not advance a cursor after applying only part of a batch.
