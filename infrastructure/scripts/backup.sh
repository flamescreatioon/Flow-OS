#!/bin/bash
# Backup script for flowOS database

set -e

BACKUP_DIR="/backups"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="flowos"
DB_USER="flowos"
DB_HOST="postgres"
RETENTION_DAYS=30

mkdir -p "$BACKUP_DIR"

echo "Starting backup at $(date)"

# Create backup
pg_dump -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" \
  --no-owner --no-privileges --clean --if-exists \
  | gzip > "$BACKUP_DIR/flowos_$DATE.sql.gz"

echo "Backup created: flowos_$DATE.sql.gz"

# Cleanup old backups
find "$BACKUP_DIR" -name "flowos_*.sql.gz" -mtime +$RETENTION_DAYS -delete
echo "Cleaned up backups older than $RETENTION_DAYS days"

echo "Backup completed at $(date)"