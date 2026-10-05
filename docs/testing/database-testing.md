# Database Testing

SQLite in-memory is appropriate for fast Laravel feature/application tests, but it is not proof of PostgreSQL behavior. PostgreSQL-specific integration tests are required for UUID/default functions, constraints, tenant uniqueness, TIMESTAMPTZ, JSON/numeric semantics, indexes, migrations, and concurrency/locking when those features are implemented.

Such tests must use a dedicated local database such as `flowos_test`, never the development `flowos` database. The integration environment must require `APP_ENV=testing`, a local/test host, and the dedicated database name before migrations or resets. Use Laravel-supported isolation such as `RefreshDatabase` or transactions as appropriate; do not manually truncate arbitrary tables after every test.

## Local PostgreSQL suite

Start the repository infrastructure, then create the dedicated test database once (the helper is idempotent):

```powershell
.\scripts\dev.ps1 up
.\scripts\prepare-pgsql-test.ps1
cd apps/api
composer test:pgsql
```

```bash
./scripts/dev.sh up
./scripts/prepare-pgsql-test.sh
cd apps/api
composer test:pgsql
```

`phpunit.pgsql.xml` forces `APP_ENV=testing`, `DB_CONNECTION=pgsql`, host `127.0.0.1`, user `flowos`, and database `flowos_test`. It intentionally inherits only the local password from `apps/api/.env`; no password is committed. The shared test base class aborts if a PostgreSQL test resolves any database other than `flowos_test`.

`RefreshDatabase` initializes and isolates the schema for each test run. `migrate:fresh` is safe only when explicitly targeted at `flowos_test`; it must never be run against development database `flowos`. The normal SQLite suite remains fast and does not start Docker.

CI creates the same dedicated database before running `phpunit.pgsql.xml`; its migration smoke database remains separate.
