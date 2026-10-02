# Development Database

## Local Setup

### Docker (Recommended)
```bash
# Start all services
docker-compose up -d

# Services:
# - postgres: localhost:5432
# - redis: localhost:6379
# - mailpit: localhost:8025 (UI), 1025 (SMTP)
```

### Environment (.env)
```bash
# apps/api/.env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=flowos
DB_USERNAME=flowos
DB_PASSWORD=secret

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# API
APP_URL=http://localhost:8000
```

### Connection
```bash
# CLI
psql -h localhost -U flowos -d flowos

# Or with password
PGPASSWORD=secret psql -h localhost -U flowos -d flowos
```

## Database Initialization

```bash
# Fresh install (dev only)
cd apps/api
php artisan migrate:fresh --seed
```

### Default Data (Seeds)
```bash
# Runs Organization, Branch, User seeds
php artisan db:seed
```

## Connecting Tools

| Tool | Connection |
|------|------------|
| psql | `psql -h localhost -U flowos -d flowos` |
| pgAdmin | Host: localhost, Port: 5432, DB: flowos, User: flowos |
| DBeaver | PostgreSQL, Host: localhost, Port: 5432 |
| TablePlus | PostgreSQL, Host: localhost:5432 |

## Resetting Database

```bash
# WARNING: Destroys all data
php artisan migrate:fresh --seed

# Or via Docker
docker-compose down -v  # Removes volumes
docker-compose up -d
php artisan migrate --seed
```

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Connection refused | `docker-compose up -d postgres` |
| Auth failed | Check `.env` matches docker-compose.yml |
| Migrations fail | `php artisan migrate:fresh --seed` |
| SSL error | Set `sslmode=prefer` in database.php |

## Production vs Development

| Aspect | Development | Production |
|--------|-------------|------------|
| SSL | `prefer` | `require` |
| Logging | Debug | Error only |
| Pool size | Small | PgBouncer |
| Backups | Manual | Automated + PITR |