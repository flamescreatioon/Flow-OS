## Summary
<!-- Concise summary of what this pull request does. -->

## Why
<!-- What problem does this solve or what value does it add? Link related issues (e.g., Closes #123). -->

## Changes
<!-- Bulleted list of notable changes. -->
-

## Architecture / Data Impact
<!-- Detail any database migrations, schema evolution, API changes, or architectural shifts. Mark N/A if none. -->

## Testing
<!-- Provide exact verification commands executed and their factual outcomes. Avoid generic "tested". -->
<!-- Example:
- `php artisan test` — PASS (X tests, Y assertions)
- `npm run test` — PASS
- `flutter test` — PASS
- `dotnet test` — PASS
-->
-

### Known Debt / Limitations
<!-- List any pre-existing failures, deferred debt, or local environment limitations. Mark N/A if none. -->
- N/A

## Security / Tenancy
<!-- Verify organization scoping (`organization_id`), authentication/authorization, and secret hygiene. Mark N/A if none. -->

## Offline Impact
<!-- Offline local-first considerations, outbox/sync event handling, idempotency, or conflict resolution. Mark N/A if none. -->

## Financial Impact
<!-- Monetary integer minor units (kobo/cents), precision, rounding, or ledger immutability. Mark N/A if none. -->

## Screenshots / UI
<!-- Visual changes, before/after captures for Web, Mobile, or Desktop. Mark N/A if non-visual. -->
N/A

## Checklist
- [ ] Relevant test suites run and passing locally
- [ ] Code formatting and linter checks pass without new warnings or broad suppressions
- [ ] No secrets, credentials, or private configuration committed (`.env` excluded)
- [ ] Database migrations are reversible (`down()` implemented and tested where practical)
- [ ] Multi-tenancy isolation respected (`organization_id` scoped where appropriate)
- [ ] Authentication and role-based permissions verified
- [ ] Monetary values adhere to integer minor units (no floating-point money)
- [ ] Date/time semantics follow UTC instant / business date standards
- [ ] Offline behavior and sync idempotency considered where applicable
- [ ] Documentation or ADRs updated if architectural changes were introduced
- [ ] No generated files or build artifacts accidentally committed
- [ ] Commit history is clean and follows Conventional Commits
