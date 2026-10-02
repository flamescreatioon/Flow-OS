-- flowOS Database Schema
-- Multi-tenant SaaS with shared schema approach
-- Tenant isolation via organization_id on all tenant-owned tables

-- Organizations (tenants)
CREATE TABLE organizations (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v7(),
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    timezone VARCHAR(50) NOT NULL DEFAULT 'Africa/Lagos',
    currency VARCHAR(3) NOT NULL DEFAULT 'NGN',
    settings JSONB DEFAULT '{}',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Branches
CREATE TABLE branches (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v7(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    address TEXT,
    phone VARCHAR(50),
    email VARCHAR(255),
    is_main BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Users
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v7(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'viewer',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    email_verified_at TIMESTAMPTZ,
    last_login_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at TIMESTAMPTZ
);

-- Create unique index on email per organization
CREATE UNIQUE INDEX idx_users_org_email ON users(organization_id, email) WHERE deleted_at IS NULL;

-- Personal Access Tokens (for API authentication)
CREATE TABLE personal_access_tokens (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v7(),
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    abilities JSONB DEFAULT '[]',
    last_used_at TIMESTAMPTZ,
    expires_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_personal_access_tokens_tokenable ON personal_access_tokens(tokenable_type, tokenable_id);

-- Password reset tokens
CREATE TABLE password_reset_tokens (
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    PRIMARY KEY (email)
);

-- Failed jobs
CREATE TABLE failed_jobs (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID NOT NULL UNIQUE DEFAULT uuid_generate_v7(),
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload JSONB NOT NULL,
    exception TEXT NOT NULL,
    failed_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Jobs queue
CREATE TABLE jobs (
    id BIGSERIAL PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload JSONB NOT NULL,
    attempts SMALLINT NOT NULL DEFAULT 0,
    reserved_at TIMESTAMPTZ,
    available_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_jobs_queue_available ON jobs(queue, available_at);

-- Migrations tracking
CREATE TABLE migrations (
    id SERIAL PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch INT NOT NULL
);

-- ============================================
-- Updated_at trigger function
-- ============================================

CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$;

-- Apply updated_at triggers
CREATE TRIGGER update_organizations_updated_at
    BEFORE UPDATE ON organizations
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_branches_updated_at
    BEFORE UPDATE ON branches
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_users_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_personal_access_tokens_updated_at
    BEFORE UPDATE ON personal_access_tokens
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();