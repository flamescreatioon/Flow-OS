# Integration Architecture

Payments, SMS/WhatsApp providers, fiscal/tax services, accounting exports, and printer/peripheral support are future adapter boundaries. Providers are not selected. Integrations must pass through application/domain interfaces, preserve tenant authorization, redact credentials, and avoid becoming business source-of-truth without an ADR.

The first adapter boundary now exists: notification delivery channels (ADR 0037). Email runs through Laravel Mail (SMTP/Mailpit); SMS and WhatsApp resolve to a log driver until a provider is selected by its own ADR and added as a driver adapter — no provider code, credentials, or SDK dependencies exist yet.
