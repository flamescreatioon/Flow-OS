# Session Strategy

## Overview

Redis is an acceptable production session backend for web sessions.

## Configuration

```env
# Production web sessions
SESSION_DRIVER=redis

# Local development / testing
SESSION_DRIVER=array  # or file

# Mobile/desktop Sanctum tokens do NOT use sessions
```

## Web vs Mobile/Desktop

| Client Type | Session Backend | Notes |
|-------------|----------------|-------|
| Browser SPA (future) | Redis (production) | Server-side sessions for auth |
| Mobile (Flutter) | N/A | Sanctum personal access tokens |
| Desktop (.NET) | N/A | Sanctum personal access tokens |
| API (stateless) | N/A | Token-based auth |

## Health Independence

`/api/v1/health` MUST NOT require a Redis session.

Public API routes MUST NOT fail because Redis is unavailable unless they explicitly need Redis.

## Session Tenant Context

Do NOT store authoritative tenant authorization solely inside session data.

A session MAY remember:
```
last selected organization
```
for UX convenience.

The server MUST still verify active membership/authorization on each request.

## Session Security

Session data may contain sensitive identifiers.

Requirements:
- Secure cookies in production (`SESSION_SECURE_COOKIE=true`)
- HTTP-only browser cookies (`http_only: true`)
- Production TLS
- Reasonable session expiry (`SESSION_LIFETIME=120`)
- Session invalidation on security-sensitive events (password change, role change, logout)

## Configuration Details

Current `config/session.php` uses:
- Driver: `env('SESSION_DRIVER', 'file')`
- Lifetime: 120 minutes
- Secure cookies: `env('SESSION_SECURE_COOKIE')`
- Same-site: `lax`
- HTTP-only: `true`

Production `.env` should set:
```env
SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=.flowos.local
```

## Session Prefix

If session data shares a Redis service, session keys should remain clearly namespaced by Laravel/application configuration.

Do NOT manually manipulate raw session Redis keys.