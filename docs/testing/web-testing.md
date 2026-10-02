# Web Testing

The web uses the already-installed Jest, ts-jest, and React Testing Library stack. `npm run test` is non-interactive (`jest --runInBand --ci`); `npm run test:watch` is explicitly interactive. Jest uses jsdom, `setupFilesAfterEnv`, and the existing `@/` mapping.

Foundation tests are behavior-focused pure/component tests. Sprint 0.19 adds exact financial input parsing vectors from `packages/types`; this verifies a shared trusted boundary without browser E2E or a second test runner. Components should be tested through visible behavior and interaction, not whole-app snapshots or implementation details.

No browser E2E framework is configured. Add it only when a real, high-value workflow warrants the operational cost. Web tests do not call a live API; API clients are tested later with controlled responses at a meaningful boundary.
