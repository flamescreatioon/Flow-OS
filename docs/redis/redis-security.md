# Redis Security

## Network Isolation

Production Redis MUST NOT be exposed publicly.

- Private network only (VPC, Docker network, etc.)
- No public internet access
- Application servers only clients

## Authentication

- Redis `requirepass` or ACLs where supported/required
- Password via environment variable, NOT committed to Git
- Rotate periodically

## TLS

- TLS where architecture/provider requires it
- Use `rediss://` scheme for TLS connections
- Verify certificates in production

## Secrets Management

- Passwords via environment variables / secret management (Vault, AWS Secrets Manager, etc.)
- NO credentials committed to `.env.example` (use clearly non-production placeholders)
- `.env` files in `.gitignore`

## Redis URL Configuration

Support both conventional and URL-based configuration:

```env
# Conventional
REDIS_HOST=redis.internal
REDIS_PORT=6379
REDIS_PASSWORD=secret

# Or URL-based
REDIS_URL=redis://:secret@redis.internal:6379/0
```

Laravel's `config/database.php` supports both.

## Development vs Production

| Aspect | Development | Production |
|--------|-------------|------------|
| Network | Docker bridge | Private VPC |
| Auth | Optional | Required |
| TLS | No | Yes |
| Password | `null` or simple | Strong, rotated |

## Current `.env.example` Audit

Root `.env.example`:
```
REDIS_HOST=localhost
REDIS_PORT=6379
```

API `.env.example`:
```
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

Both use clearly non-production values (`null`, `localhost`). Acceptable.

## Credential Rotation

- Document rotation procedure
- Use secret manager for production
- Audit access logs periodically

## Access Control

- Restrict Redis commands where possible (e.g., disable `FLUSHALL`, `CONFIG` via rename or ACL)
- Monitor for unusual command patterns
- Limit max connections