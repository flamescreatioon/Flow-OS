#!/usr/bin/env bash

set -euo pipefail

if [ "$(docker compose exec -T postgres psql -U flowos -d postgres -tAc "SELECT 1 FROM pg_database WHERE datname = 'flowos_test'")" != "1" ]; then
  docker compose exec -T postgres createdb -U flowos flowos_test
  echo "Created PostgreSQL test database flowos_test."
else
  echo "PostgreSQL test database flowos_test already exists."
fi
