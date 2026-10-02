# Deployment Overview

## Current: Local Development

```
┌─────────────────────────────────────────────────────────────┐
│                    Developer Machine                         │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐    │
│  │  Web     │  │  API     │  │  Mobile  │  │ Desktop  │    │
│  │ (Next.js)│  │ (Laravel)│  │ (Flutter)│  │  (.NET)  │    │
│  │ Port 3000│  │ Port 8000│  │  Device  │  │   App    │    │
│  └────┬─────┘  └────┬─────┘  └──────────┘  └──────────┘    │
│       │             │                                        │
│       └─────────────┼──────────────────────────────────────┘
│                     ▼
│  ┌──────────────────────────────────────────────────────┐  │
│  │              Docker Compose                           │  │
│  │  ┌────────────┐  ┌────────────┐  ┌────────────┐      │  │
│  │  │ PostgreSQL │  │   Redis    │  │  Mailpit   │      │  │
│  │  │  Port 5432 │  │  Port 6379 │  │ Port 1025  │      │  │
│  │  └────────────┘  └────────────┘  └────────────┘      │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### Quick Start

```bash
# 1. Start infrastructure
docker compose up -d

# 2. Web app
cd apps/web && npm install && npm run dev

# 3. API (Laravel)
cd apps/api && composer install && cp .env.example .env && php artisan key:generate && php artisan migrate && php artisan serve

# 4. Mobile
cd apps/mobile && flutter pub get && flutter run

# 5. Desktop
cd apps/desktop && dotnet restore && dotnet run --project src/FlowOS.Desktop
```

## Future: Production Deployment

### Container Orchestration (Docker Swarm / Kubernetes)

```yaml
# docker-compose.prod.yml
version: '3.8'

services:
  postgres:
    image: postgres:16
    environment:
      POSTGRES_DB: flowos
      POSTGRES_USER: ${DB_USER}
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - postgres_data:/var/lib/postgresql/data
    deploy:
      replicas: 1
      placement:
        constraints: [node.role == manager]

  redis:
    image: redis:7-alpine
    volumes:
      - redis_data:/data
    deploy:
      replicas: 1

  api:
    build: ./apps/api
    environment:
      APP_ENV: production
      APP_DEBUG: "false"
      DB_HOST: postgres
      REDIS_HOST: redis
    deploy:
      replicas: 3
      resources:
        limits:
          memory: 512M

  web:
    build: ./apps/web
    environment:
      NEXT_PUBLIC_API_URL: https://api.flowos.com/api/v1
    deploy:
      replicas: 3

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./infrastructure/nginx/nginx.conf:/etc/nginx/nginx.conf
      - ./infrastructure/nginx/ssl:/etc/nginx/ssl
    deploy:
      replicas: 2

volumes:
  postgres_data:
  redis_data:
```

### CI/CD Pipeline

```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Run tests
        run: |
          docker compose -f docker-compose.ci.yml up -d
          # Run all test suites

  build:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Build and push images
        run: |
          docker build -t flowos/api:${{ github.sha }} ./apps/api
          docker build -t flowos/web:${{ github.sha }} ./apps/web
          docker push flowos/api:${{ github.sha }}
          docker push flowos/web:${{ github.sha }}

  deploy:
    needs: build
    runs-on: ubuntu-latest
    environment: production
    steps:
      - name: Deploy to server
        run: |
          ssh deploy@server "docker compose pull && docker compose up -d"
```

### Environment Variables

| Variable | Description | Required |
|----------|-------------|----------|
| `APP_ENV` | Environment (local/staging/production) | Yes |
| `APP_URL` | Public API URL | Yes |
| `DATABASE_URL` | PostgreSQL connection string | Yes |
| `REDIS_URL` | Redis connection string | Yes |
| `APP_KEY` | Laravel encryption key | Yes |
| `JWT_SECRET` | JWT signing secret | Yes |

### SSL/TLS

- Let's Encrypt via Certbot
- Automatic renewal via cron
- HSTS headers in Nginx

### Database Migrations

```bash
# Run migrations on deploy
php artisan migrate --force

# Rollback if needed
php artisan migrate:rollback --force
```

### Backup Strategy

```bash
# Daily automated backup
pg_dump -h $DB_HOST -U $DB_USER -d $DB_NAME | gzip > /backups/flowos_$(date +%Y%m%d).sql.gz

# Retain 30 days
find /backups -name "flowos_*.sql.gz" -mtime +30 -delete

# Point-in-time recovery (PITR) via WAL archiving
```

### Monitoring

| Component | Tool |
|-----------|------|
| Application | Laravel Telescope / Sentry |
| Infrastructure | Prometheus + Grafana |
| Logs | Loki / ELK |
| Uptime | UptimeRobot / Pingdom |

### Scaling Considerations

| Component | Horizontal Scaling |
|-----------|-------------------|
| API | Stateless, add replicas |
| Web | Static export or SSR replicas |
| PostgreSQL | Read replicas, connection pooling |
| Redis | Cluster mode |
| Queue Workers | Add worker processes |

### Disaster Recovery

1. **RPO**: 24 hours (daily backups)
2. **RTO**: 4 hours (documented restore procedure)
3. **Test**: Monthly restore drills
4. **Runbooks**: Documented for each failure scenario
