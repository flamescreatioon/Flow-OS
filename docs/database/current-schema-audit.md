# Current Schema Audit

*Generated from existing migrations and models*

## Tables

### organizations (Tenant Root)
| Column | Type | Constraints |
|--------|------|-------------|
| id | UUID | PK, DEFAULT uuid_generate_v7() |
| name | VARCHAR(255) | NOT NULL |
| slug | VARCHAR(100) | NOT NULL, UNIQUE |
| timezone | VARCHAR(50) | NOT NULL DEFAULT 'Africa/Lagos' |
| currency | CHAR(3) | NOT NULL DEFAULT 'NGN' |
| settings | JSONB | DEFAULT '{}' |
| created_at | TIMESTAMPTZ | NOT NULL DEFAULT NOW() |
| updated_at | TIMESTAMPTZ | NOT NULL DEFAULT NOW() |

**Indexes**: PK on id, UNIQUE on slug
**Triggers**: updated_at auto-update

---

### branches
| Column | Type | Constraints |
|--------|------|-------------|
| id | UUID | PK, DEFAULT uuid_generate_v7() |
| organization_id | UUID | NOT NULL, FK → organizations(id) CASCADE |
| name | VARCHAR(255) | NOT NULL |
| address | TEXT | |
| phone | VARCHAR(50) | |
| email | VARCHAR(255) | |
| is_main | BOOLEAN | NOT NULL DEFAULT FALSE |
| created_at | TIMESTAMPTZ | NOT NULL DEFAULT NOW() |
| updated_at | TIMESTAMPTZ | NOT NULL DEFAULT NOW() |

**Indexes**: FK on organization_id
**Triggers**: updated_at auto-update

---

### users
| Column | Type | Constraints |
|--------|------|-------------|
| id | UUID | PK, DEFAULT uuid_generate_v7() |
| organization_id | UUID | NOT NULL, FK → organizations(id) CASCADE |
| name | VARCHAR(255) | NOT NULL |
| email | VARCHAR(255) | NOT NULL |
| password_hash | VARCHAR(255) | NOT NULL |
| role | VARCHAR(50) | NOT NULL DEFAULT 'viewer' |
| is_active | BOOLEAN | NOT NULL DEFAULT TRUE |
| email_verified_at | TIMESTAMPTZ | |
| last_login_at | TIMESTAMPTZ | |
| created_at | TIMESTAMPTZ | NOT NULL DEFAULT NOW() |
| updated_at | TIMESTAMPTZ | NOT NULL DEFAULT NOW() |
| deleted_at | TIMESTAMPTZ | NULL (soft delete) |

**Indexes**:
- PK on id
- FK on organization_id (CASCADE)
- UNIQUE on (organization_id, email) WHERE deleted_at IS NULL

**Triggers**: updated_at auto-update, UUIDv7 on create

---

### personal_access_tokens
| Column | Type | Constraints |
|--------|------|-------------|
| id | UUID | PK, DEFAULT uuid_generate_v7() |
| tokenable_type | VARCHAR(255) | NOT NULL |
| tokenable_id | UUID | NOT NULL |
| name | VARCHAR(255) | NOT NULL |
| token | VARCHAR(64) | NOT NULL, UNIQUE |
| abilities | JSONB | DEFAULT '[]' |
| last_used_at | TIMESTAMPTZ | |
| expires_at | TIMESTAMPTZ | |
| created_at | TIMESTAMPTZ | NOT NULL DEFAULT NOW() |
| updated_at | TIMESTAMPTZ | NOT NULL DEFAULT NOW() |

**Indexes**: FK on (tokenable_type, tokenable_id), UNIQUE on token

---

### password_reset_tokens
| Column | Type | Constraints |
|--------|------|-------------|
| email | VARCHAR(255) | NOT NULL, PK |
| token | VARCHAR(255) | NOT NULL |
| created_at | TIMESTAMPTZ | NOT NULL DEFAULT NOW() |

---

### failed_jobs
| Column | Type | Constraints |
|--------|------|-------------|
| id | BIGSERIAL | PK |
| uuid | UUID | NOT NULL, UNIQUE, DEFAULT uuid_generate_v7() |
| connection | TEXT | NOT NULL |
| queue | TEXT | NOT NULL |
| payload | JSONB | NOT NULL |
| exception | TEXT | NOT NULL |
| failed_at | TIMESTAMPTZ | NOT NULL DEFAULT NOW() |

---

### jobs
| Column | Type | Constraints |
|--------|------|-------------|
| id | BIGSERIAL | PK |
| queue | VARCHAR(255) | NOT NULL |
| payload | JSONB | NOT NULL |
| attempts | SMALLINT | NOT NULL DEFAULT 0 |
| reserved_at | TIMESTAMPTZ | |
| available_at | TIMESTAMPTZ | NOT NULL DEFAULT NOW() |
| created_at | TIMESTAMPTZ | NOT NULL DEFAULT NOW() |

**Indexes**: (queue, available_at)

---

### migrations
| Column | Type | Constraints |
|--------|------|-------------|
| id | SERIAL | PK |
| migration | VARCHAR(255) | NOT NULL |
| batch | INT | NOT NULL |

---

## Extensions Enabled

```sql
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";
```

## Custom Functions

### uuid_generate_v7()
Custom PL/pgSQL function for UUIDv7 generation (timestamp-ordered, RFC 9562 draft).

```sql
CREATE OR REPLACE FUNCTION uuid_generate_v7() RETURNS uuid
LANGUAGE plpgsql AS $$ ... $$;
```

### update_updated_at_column()
Trigger function for automatic `updated_at` updates.

```sql
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER LANGUAGE plpgsql AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$;
```

## Triggers

| Table | Trigger | Function |
|-------|---------|----------|
| organizations | BEFORE UPDATE | update_updated_at_column |
| branches | BEFORE UPDATE | update_updated_at_column |
| users | BEFORE UPDATE | update_updated_at_column |
| personal_access_tokens | BEFORE UPDATE | update_updated_at_column |

---

## Current Gaps / Deferred

| Feature | Status | Notes |
|---------|--------|-------|
| sync_queue table | Deferred | Part of offline sync phase |
| Row Level Security | Deferred | Defense-in-depth, after core stable |
| Audit log table | Deferred | Part of audit-fields phase |
| Partitioning | Not planned | Premature |
| Read replicas | Not configured | Future Sprint |

---

## Schema Diagram (Conceptual)

```
organizations (1) ──< branches
organizations (1) ──< users
organizations (1) ──< personal_access_tokens (polymorphic)
organizations (1) ──< organizations_settings (JSONB)

users (1) ──< personal_access_tokens (polymorphic)
```

All tenant tables include `organization_id` with FK → organizations(id) CASCADE.
