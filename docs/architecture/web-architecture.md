# Web Architecture

The web client uses Next.js App Router and TypeScript. Source structure separates app routes, features, hooks, `lib`, services, stores, and presentation concerns. It is a foundation scaffold, not a completed business UI.

First-party browser authentication is Sanctum stateful cookie/session/CSRF direction. Browser access tokens must not use localStorage or sessionStorage. The API client sends credentialed requests; request IDs support future diagnostics. The web client runs on Next.js 15.5.28 with React 19, so the pre-production migration blocker to a patched Next 15+ line has been satisfied.
