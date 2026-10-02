# Frontend Architecture

The web client is the Next.js App Router and TypeScript application described in [web architecture](web-architecture.md). This page preserves the historical entry point without duplicating a second source of truth.

The current web application is a scaffold for future modules. It keeps presentation and client state concerns in the web layer and accesses server data through the API client. Browser authentication follows the Sanctum stateful cookie and CSRF direction; browser access tokens are not stored in `localStorage` or `sessionStorage`.

Use [API architecture](api-architecture.md) for the HTTP boundary and [security architecture](security-architecture.md) for the detailed security references.
