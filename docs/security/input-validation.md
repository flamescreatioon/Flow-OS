# Input Validation

Every write endpoint uses explicit allowlisted validation, preferably Laravel Form Requests, followed by authorization and domain validation. Client-supplied organization IDs cannot select authority; the server derives/validates organization context. Do not bind raw request arrays to models.

Bound parameters prevent SQL injection; output encoding and contextual sanitization prevent XSS. Request and JSON payload sizes need explicit limits for future sync/import routes. Validation does not replace database constraints, permissions, or domain invariants.
