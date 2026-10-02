# Database Testing

SQLite in-memory is appropriate for fast Laravel feature/application tests, but it is not proof of PostgreSQL behavior. PostgreSQL-specific integration tests are required for UUID/default functions, constraints, tenant uniqueness, TIMESTAMPTZ, JSON/numeric semantics, indexes, migrations, and concurrency/locking when those features are implemented.

Such tests must use a dedicated local database such as `flowos_test`, never the development `flowos` database. The integration environment must require `APP_ENV=testing`, a local/test host, and the dedicated database name before migrations or resets. Use Laravel-supported isolation such as `RefreshDatabase` or transactions as appropriate; do not manually truncate arbitrary tables after every test.

Current status: no PostgreSQL integration suite or test environment is configured. Normal tests therefore remain fast and do not start Docker. Future migration coverage includes a clean migrate and, where practical, migrate/rollback/migrate against PostgreSQL.
