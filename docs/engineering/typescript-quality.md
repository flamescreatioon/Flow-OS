# TypeScript and Next.js Quality

`apps/web/tsconfig.json` uses `strict: true`; do not weaken it. Run `npm run lint`, `npm run typecheck`, `npm run build`, and `npm run test` for relevant web changes. ESLint currently extends `next/core-web-vitals`; rules are added only when they catch meaningful defects without creating style-only noise. No separate formatter is selected today, so do not add Prettier casually.

New code avoids `any`. Use `unknown` for genuinely unknown external data and validate/narrow it before use. Type assertions do not validate runtime data. Avoid `@ts-ignore` and `@ts-nocheck`; any unavoidable suppression follows the narrow, documented policy in [warnings-and-suppressions.md](warnings-and-suppressions.md).

Use camelCase internally and preserve snake_case at API boundaries. Components stay cohesive: avoid unnecessary client components, uncontrolled effects, duplicated state, embedded business workflows, and premature memoization. Extract only when responsibilities or testability require it. External data must come from approved contracts or a validated boundary.
