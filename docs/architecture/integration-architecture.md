# Integration Architecture

Payments, email, SMS, fiscal/tax services, accounting exports, and printer/peripheral support are future adapter boundaries. Providers are not selected. Integrations must pass through application/domain interfaces, preserve tenant authorization, redact credentials, and avoid becoming business source-of-truth without an ADR.
