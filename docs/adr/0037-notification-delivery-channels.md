# ADR 0037: Notification Delivery Channels

Status: ACCEPTED
Date: 2026-11-07

## Context

`docs/architecture/integration-architecture.md` declares email, SMS, and related transports as future adapter boundaries with no providers selected, and `docs/domain/platform-domain.md` owns notification delivery as a platform capability that must not determine transactional correctness. Invoicing (ADR 0036) needs to deliver invoice summaries by email, SMS, and WhatsApp. Only one Laravel notification exists today (organization invitations, sent synchronously by mail); there are no queue jobs, no mailables, and no third-party messaging code.

The user has chosen a provider-agnostic approach: email works through Laravel Mail (Mailpit locally), while SMS and WhatsApp run through a channel abstraction with a log driver until a real provider is selected and credentialed.

## Decision

**Delivery goes through one application-layer interface.** `NotificationChannel` (in `app/Application/Notifications/`) receives a fully validated delivery request — organization context, recipient, and a message body built from server-side data — and returns the delivery outcome. Nothing outside this layer talks to a transport. Channels are resolved from `config/notifications.php`, which maps each channel (`email`, `sms`, `whatsapp`) to a driver; the drivers shipped now are `mail` (Laravel Mailable over SMTP/Mailpit) and `log` (writes a structured log entry and reports success). SMS and WhatsApp default to the `log` driver. Selecting a real provider (e.g. Termii, Twilio, Meta WhatsApp Cloud API) is a later ADR plus one adapter class — no re-architecture.

**Delivery attempts are tracked in a `notification_deliveries` table**: organization, entity type/id (e.g. invoice), channel, resolved recipient, driver, status (`queued`/`sent`/`failed`), a redacted failure message, and `sent_at`. A row is created synchronously with the send request; the transport call itself runs in a queued job (`DeliverInvoiceNotification`) that updates the row. The job deliberately does not implement Spatie's `TenantAware` marker: tenant context in this codebase is explicit and no current tenant is bound on API routes, so the job carries the organization id captured at dispatch, re-resolves it in the worker, and fails loudly when the organization is missing — the same loud-failure guarantee `config/multitenancy.php` asks of tenant-aware jobs.

**Recipients are server-validated, never trusted.** The send endpoint accepts a channel and an optional recipient override; otherwise it derives the recipient from the attached customer's email (email channel) or phone (sms/whatsapp channel). A missing or invalid recipient is a validation error. Message content is built server-side from the invoice snapshot; client-supplied totals or bodies are never rendered.

**Delivery is fire-and-track.** A failed transport call marks the delivery row `failed` with a redacted message and logs a structured event; it does not retry forever, does not block the invoice, and is not business truth (the invoice state remains authoritative). No delivery record ever mutates invoice state.

**Credentials live in the environment** (`.env.example` documents placeholders); they are never logged, never embedded in delivery rows, and never returned by the API.

## Alternatives Considered

- **Selecting a provider now** — rejected: no provider is credentialed yet, and the architecture docs require the provider choice to be its own deliberate decision.
- **Laravel Notification classes per channel** — rejected for this slice: Laravel's notification system optimizes multi-notifiable fan-out, while flowOS needs per-organization channel configuration and tracked, tenant-aware delivery rows; a single explicit interface keeps the adapter boundary honest. Revisiting Laravel notifications later is possible without changing the boundary.
- **Sending synchronously in the request** — rejected: an HTTP call to a slow transport would hold the API request and break the response-time contract; the delivery row + queued job pattern keeps the request fast and the attempt durable.
- **Retry queue with exponential backoff** — rejected for v1: premature infrastructure; failed sends are visible and can be re-triggered by issuing a new send request (a new delivery row).

## Consequences

### Positive

- Email delivery works today (Mailpit locally, SMTP in production config), and every channel shares one tracking model, one audit event name, and one job.
- Adding a provider later is a config change plus one class implementing the interface.
- Delivery status is inspectable per invoice without introducing an external observability dependency.

### Negative / Trade-offs

- SMS and WhatsApp messages are logged, not sent, until a provider adapter exists.
- A lost queue worker leaves a delivery row `queued` permanently until something reconciles it; acceptable at this scale, noted for the observability backlog.

## Security / Tenancy Impact

The job implements `TenantAware` and re-resolves the organization inside the worker, so no delivery crosses tenant context. Recipient addresses and phone numbers are personal data: they are stored only in the delivery row for the delivery itself, error messages stored on rows are redacted of credentials, and neither API responses nor logs expose provider credentials. Permission to trigger a send is `invoice.send`; reading delivery rows requires `invoice.view`.

## Offline / Data Impact

`POST /invoices/{invoice}/send` is idempotency-protected, so a replayed offline send cannot double-message a customer. Offline-first clients enqueue the same request shape; delivery remains a server-side concern and never determines transactional correctness (platform rule).

## References

- `docs/architecture/integration-architecture.md` (adapter boundary, providers not selected)
- `docs/domain/platform-domain.md` (notification capability boundary)
- `docs/adr/0036-invoicing-foundation.md` (the first consumer)
- `docs/observability/README.md` (structured log events)

## Supersedes / Superseded By

None.
