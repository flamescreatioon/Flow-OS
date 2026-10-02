# Development Scripts

flowOS provides cross-platform development scripts for common operations.

## Script Families

| Platform | Primary Script |
|----------|----------------|
| Windows / PowerShell | `.\scripts\dev.ps1` |
| Linux / macOS / WSL | `./scripts/dev.sh` |

Both scripts provide equivalent commands and behavior.

## Command Reference

### Setup
```bash
# Bash
./scripts/setup.sh

# PowerShell
.\scripts\setup.ps1
```
Prepares a new developer environment:
- Checks prerequisites (git, docker, node, php, composer, flutter, dotnet)
- Copies missing `.env` files from examples (never overwrites existing)
- Installs dependencies (composer, npm, flutter pub get, dotnet restore)
- Generates Laravel application key **only if missing or empty**

Does NOT:
- Overwrite existing `.env` files
- Start infrastructure
- Run migrations
- Start application servers
- Regenerate existing valid APP_KEY

### Infrastructure

| Command | Description |
|---------|-------------|
| `dev up` | Start PostgreSQL, Redis, Mailpit (`docker compose up -d`) |
| `dev down` | Stop infrastructure, preserve volumes (`docker compose down`) |
| `dev status` | Show container status and local URLs |
| `dev logs [service]` | Follow logs (all or specific service) |

### Tests
```bash
dev test
```
Runs available test suites:
- API: `php artisan test`
- Web: `npm run test` (if configured)
- Mobile: `flutter test`
- Desktop: `dotnet test`

Skips components where tooling is unavailable.

### Lint
```bash
dev lint
```
Runs linters:
- API: `./vendor/bin/pint --test`
- Web: `npm run lint`
- Mobile: `flutter analyze` (reports pre-existing issues)

### Formatting

| Command | Description |
|---------|-------------|
| `dev format` | Format code (API, Flutter, Desktop) |
| `dev format-check` | Check formatting without changes |

### Verification
```bash
dev verify
```
Runs repository-wide quality snapshot:
1. Docker Compose config validation
2. API tests + Pint (requires php)
3. Web tests + lint + typecheck (requires npm)
4. Mobile tests + analyze (requires flutter)
5. Desktop build + tests + format check (requires dotnet)

Reports per-component results. Known Flutter analyzer debt will cause verify to fail — this is documented pre-existing debt.

**Verification fails if required tools are missing** — missing php, npm, flutter, or dotnet will cause verify to fail with a clear error message.

### Database

| Command | Description |
|---------|-------------|
| `dev db-migrate` | Run Laravel migrations (`php artisan migrate`) |
| `dev db-status` | Show migration status (`php artisan migrate:status`) |
| `dev db-reset` | **Destructive**: reset local schema (`php artisan migrate:fresh`) |

#### db-reset Safety
The `db-reset` command includes multiple safety protections:
1. Refuses if `APP_ENV=production`
2. Refuses if `DB_HOST` is not `127.0.0.1` or `localhost`
3. Requires explicit confirmation: type `RESET`
4. Uses `php artisan migrate:fresh --force`

### Help
```bash
dev help
```
Shows usage and available commands.

## Safety Principles

- **Never overwrite `.env` files** — setup scripts only copy when missing
- **Infrastructure `down` preserves volumes** — `docker compose down` (not `down -v`)
- **Destructive operations require explicit confirmation** — `db-reset` requires typing `RESET`
- **Production protection** — `db-reset` refuses if `APP_ENV=production` or non-local `DB_HOST`
- **Setup is idempotent** — preserves existing environment files and APP_KEY
- **Exit codes** — scripts return non-zero on failure for CI compatibility

## Cross-Platform Equivalence

| Bash | PowerShell | Description |
|------|------------|-------------|
| `./scripts/dev.sh setup` | `.\scripts\dev.ps1 setup` | Setup environment |
| `./scripts/dev.sh up` | `.\scripts\dev.ps1 up` | Start infrastructure |
| `./scripts/dev.sh down` | `.\scripts\dev.ps1 down` | Stop infrastructure |
| `./scripts/dev.sh status` | `.\scripts\dev.ps1 status` | Show status |
| `./scripts/dev.sh logs` | `.\scripts\dev.ps1 logs` | Follow logs |
| `./scripts/dev.sh test` | `.\scripts\dev.ps1 test` | Run tests |
| `./scripts/dev.sh lint` | `.\scripts\dev.ps1 lint` | Run linters |
| `./scripts/dev.sh format` | `.\scripts\dev.ps1 format` | Format code |
| `./scripts/dev.sh format-check` | `.\scripts\dev.ps1 format-check` | Check formatting |
| `./scripts/dev.sh verify` | `.\scripts\dev.ps1 verify` | Full verification |
| `./scripts/dev.sh db-migrate` | `.\scripts\dev.ps1 db-migrate` | Run migrations |
| `./scripts/dev.sh db-status` | `.\scripts\dev.ps1 db-status` | Migration status |
| `./scripts/dev.sh db-reset` | `.\scripts\dev.ps1 db-reset` | Reset schema (destructive) |
| `./scripts/dev.sh help` | `.\scripts\dev.ps1 help` | Show help |

## Known Limitations

- **Flutter analyzer** has pre-existing warnings that cause `lint` and `verify` to fail. These are documented technical debt, not regressions.
- **Web tests** run through the configured non-interactive `npm run test` command.
- **Docker daemon unavailable** — infrastructure commands fail gracefully with clear message.
- **Tool availability** — commands gracefully skip when tooling (flutter, dotnet, npm) is not installed.

## Exit Codes

| Scenario | Exit Code |
|----------|-----------|
| Success | 0 |
| Unknown command | 1 |
| Failed check/verification | 1 |
| Safety refusal (db-reset) | 1 |

## Documentation

- [Local Setup Guide](local-setup.md)
- [Docker Environment](docker.md)
- [Database Reset](database-reset.md)
- [Current Scripts Audit](current-scripts-audit.md)
