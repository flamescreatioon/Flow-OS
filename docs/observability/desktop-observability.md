# Desktop Observability

The WPF host already uses `Microsoft.Extensions.Logging` with console and debug providers. Future code uses structured message templates and IDs, not interpolated sensitive objects. Persistent local log files are not implemented.

If file logging is introduced, design rotation, limits, retention, permissions, user-data location, and redaction first. No token, HTTP, or local database feature currently emits desktop logs.
