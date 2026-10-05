#!/usr/bin/env pwsh

$ErrorActionPreference = 'Stop'

$existing = [string] (docker compose exec -T postgres psql -U flowos -d postgres -tAc "SELECT 1 FROM pg_database WHERE datname = 'flowos_test'")

if ($existing -ne '1') {
    docker compose exec -T postgres createdb -U flowos flowos_test
    Write-Host 'Created PostgreSQL test database flowos_test.'
} else {
    Write-Host 'PostgreSQL test database flowos_test already exists.'
}
