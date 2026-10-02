# Logging Standard

Levels are TRACE, DEBUG, INFO, WARNING, ERROR, and CRITICAL, mapped to platform equivalents. Validation, ordinary 404s, and expected authorization denials are not ERROR by default.

Event names are stable, lowercase, dot-separated (for example `request.completed`, `tenant.context.established`, `sync.push.completed`). Canonical optional fields are `timestamp`, `level`, `message`, `event`, `service`, `environment`, `request_id`, `correlation_id`, `trace_id`, `organization_id`, `branch_id`, `user_id`, `device_id`, `operation_id`, `route`, `method`, `status_code`, `duration_ms`, `error_type`, and `error_code`. Omit unavailable fields.

Service names are `flowos-api`, `flowos-web`, `flowos-mobile`, and `flowos-desktop`. Development may use DEBUG; production direction is INFO/WARNING and structured JSON to stdout/stderr. Do not add external shipping without architecture review.
