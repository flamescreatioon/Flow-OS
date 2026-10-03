# Audit Domain

**Status: ARCHITECTED.** Audit owns durable append-oriented AuditEvent direction for significant server-side actions: membership changes, device revocation, stock adjustment, sale void, payment refund, invoice void, and accounting posting/reversal.

Audit is distinct from operational logs, inventory movements, and accounting journals. Metadata is allowlisted; audit records are not generic request payload dumps. Audit persistence is future foundation work.
