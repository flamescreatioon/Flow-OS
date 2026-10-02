# Environment Management

## Environment Types

| Environment | Purpose | URL | Data |
|-------------|---------|-----|------|
| `local` | Developer machine | localhost | Synthetic/seed |
| `staging` | Pre-production testing | staging.flowos.com | Production-like (anonymized) |
| `production` | Live customers | flowos.com | Real customer data |

## Environment Variables

### Required Variables

| Variable | Local | Staging | Production | Description |
|----------|-------|---------|------------|-------------|
| `APP_ENV` | local | staging | production | Environment identifier |
| `APP_DEBUG` | true | false | false | Debug mode |
| `APP_URL` | http://localhost:8000 | https://staging.api.flowos.com | https://api.flowos.com | Public API URL |
| `APP_KEY` | generated | from vault | from vault | Laravel encryption key |
| `DB_CONNECTION` | pgsql | pgsql | pgsql | Database driver |
| `DB_HOST` | 127.0.0.1 | db.staging.internal | db.prod.internal | Database host |
| `DB_DATABASE` | flowos | flowos_staging | flowos_prod | Database name |
| `DB_USERNAME` | flowos | flowos_staging | flowos_prod | Database user |
| `DB_PASSWORD` | secret | from vault | from vault | Database password |
| `REDIS_HOST` | 127.0.0.1 | redis.staging.internal | redis.prod.internal | Redis host |
| `REDIS_PASSWORD` | null | from vault | from vault | Redis password |
| `SANCTUM_STATEFUL_DOMAINS` | local web origins | explicit deployed web origins | explicit deployed web origins | Sanctum stateful SPA origins |

### Frontend Variables

| Variable | Local | Staging | Production |
|----------|-------|---------|------------|
| `NEXT_PUBLIC_API_URL` | http://localhost:8000/api/v1 | https://staging.api.flowos.com/api/v1 | https://api.flowos.com/api/v1 |
| `NEXT_PUBLIC_APP_URL` | http://localhost:3000 | https://staging.flowos.com | https://flowos.com |

### Mobile Variables

```dart
// lib/core/config/environment.dart
enum Environment { local, staging, production }

class EnvironmentConfig {
  static const Environment current = Environment.local; // Build-time

  static String get apiBaseUrl {
    switch (current) {
      case Environment.local: return 'http://10.0.2.2:8000/api/v1';
      case Environment.staging: return 'https://staging.api.flowos.com/api/v1';
      case Environment.production: return 'https://api.flowos.com/api/v1';
    }
  }
}
```

## Secrets Management

### Local Development
```bash
# .env files (never committed)
apps/api/.env
apps/web/.env.local
```

### Staging/Production
- **HashiCorp Vault** or **AWS Secrets Manager**
- Injected at runtime via CI/CD
- Never in code, config files, or Docker images

```bash
# Example: Docker secrets
docker secret create db_password /run/secrets/db_password
```

## Configuration Files

### Laravel
```php
// config/app.php
'env' => env('APP_ENV', 'local'),
'debug' => env('APP_DEBUG', false),
'url' => env('APP_URL'),
```

### Next.js
```typescript
// src/config/index.ts
export const config = {
  api: {
    baseUrl: process.env.NEXT_PUBLIC_API_URL!,
  },
  // ...
};
```

### Flutter
```dart
// lib/core/config/app_config.dart
class AppConfig {
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000/api/v1',
  );
}

// Build with: flutter build apk --dart-define=API_BASE_URL=https://api.flowos.com/api/v1
```

### .NET
```csharp
// appsettings.json
{
  "ConnectionStrings": {
    "DefaultConnection": "Data Source=flowos.db"
  },
  "Api": {
    "BaseUrl": "http://localhost:8000/api/v1"
  }
}

// appsettings.Production.json
{
  "ConnectionStrings": {
    "DefaultConnection": "Host=db.prod.internal;Database=flowos_prod;Username=..."
  }
}
```

## Database Migrations

### Local
```bash
php artisan migrate
php artisan migrate:fresh --seed  # Reset with seed data
```

### Staging/Production
```bash
# CI/CD pipeline
php artisan migrate --force --no-interaction

# Rollback
php artisan migrate:rollback --force --step=1
```

### Safety Rules
- ✅ Always reversible (`down()` method)
- ✅ No destructive changes without backup
- ✅ Test migration on staging first
- ✅ Zero-downtime migrations preferred
- ❌ No `dropColumn` in production without deprecation period

## Feature Flags

```php
// Laravel - config/features.php
return [
    'new_inventory_ui' => env('FEATURE_NEW_INVENTORY_UI', false),
    'advanced_reporting' => env('FEATURE_ADVANCED_REPORTING', false),
];

// Usage
if (config('features.new_inventory_ui')) {
    // New feature
}
```

```typescript
// Next.js - src/config/features.ts
export const features = {
  newInventoryUi: process.env.NEXT_PUBLIC_FEATURE_NEW_INVENTORY_UI === 'true',
  advancedReporting: process.env.NEXT_PUBLIC_FEATURE_ADVANCED_REPORTING === 'true',
};
```

## Environment-Specific Behavior

### Local
- Debug toolbar enabled
- Detailed error pages
- Mailpit for email testing
- SQLite/PostgreSQL local
- No rate limiting

### Staging
- Production-like config
- Anonymized production data
- Full monitoring
- Rate limiting enabled
- SSL terminated at load balancer

### Production
- Minimal logging
- Generic error pages
- Full monitoring + alerting
- Rate limiting + WAF
- CDN for static assets
- Read replicas for DB

## Deployment Checklist

### Pre-deploy
- [ ] All tests pass
- [ ] Security scan passes
- [ ] Database migrations reviewed
- [ ] Feature flags configured
- [ ] Secrets updated in vault
- [ ] Rollback plan documented

### Post-deploy
- [ ] Health checks pass
- [ ] Smoke tests pass
- [ ] Monitoring alerts clear
- [ ] Performance metrics normal
- [ ] Error rates baseline
