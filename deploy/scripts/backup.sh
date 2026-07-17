#!/usr/bin/env bash

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/hananeel-cinta/current}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/hananeel-cinta}"
MYSQL_DEFAULTS_FILE="${MYSQL_DEFAULTS_FILE:-/etc/mysql/hananeel-backup.cnf}"
RETENTION_DAYS="${RETENTION_DAYS:-14}"
TIMESTAMP="$(date -u +%Y%m%dT%H%M%SZ)"

if [[ ! -r "$MYSQL_DEFAULTS_FILE" ]]; then
    echo "MySQL credential file is not readable: $MYSQL_DEFAULTS_FILE" >&2
    exit 1
fi

mkdir -p "$BACKUP_DIR"

mysqldump \
    --defaults-extra-file="$MYSQL_DEFAULTS_FILE" \
    --single-transaction \
    --quick \
    --lock-tables=false \
    hananeel_cinta \
    | gzip > "$BACKUP_DIR/database-$TIMESTAMP.sql.gz"

tar -C "$APP_DIR" -czf "$BACKUP_DIR/storage-public-$TIMESTAMP.tar.gz" storage/app/public

find "$BACKUP_DIR" -type f -mtime "+$RETENTION_DAYS" -delete

echo "Backup completed: $BACKUP_DIR ($TIMESTAMP)"
