#!/bin/bash
set -e

# ============ KONFIGURASI PAPYRUS ANTRIAN ============
DATE=$(date +%Y%m%d_%H%M%S)
APP_DIR="/home/itsupportjogja/apps/papyrus-antrian"
BACKUP_DIR="/home/itsupportjogja/backups-papyrus"
REMOTE="b2-papyrus:papyrus-backup"
LOG_FILE="$APP_DIR/backup.log"

DB_SERVICE="db"
DB_NAME="papyrus_antrian"
DB_USER="root"
DB_PASS='#Td500mblue!'

RETENTION_DAYS=30
# =====================================================

cd "$APP_DIR"
mkdir -p "$BACKUP_DIR"

echo "$(date): === Mulai backup Papyrus Antrian ===" >> "$LOG_FILE"

echo "Backup database: $DB_NAME"
docker compose exec -T "$DB_SERVICE" mysqldump -u"$DB_USER" -p"$DB_PASS" --single-transaction --routines --triggers "$DB_NAME" > "$BACKUP_DIR/papyrus_${DATE}.sql"
gzip "$BACKUP_DIR/papyrus_${DATE}.sql"
rclone copy "$BACKUP_DIR/papyrus_${DATE}.sql.gz" "$REMOTE/database/"
rm "$BACKUP_DIR/papyrus_${DATE}.sql.gz"

rclone delete "$REMOTE/database/" --min-age "${RETENTION_DAYS}d" --rmdirs || true

echo "$(date): === Backup selesai ===" >> "$LOG_FILE"
echo "-------------------------------------------" >> "$LOG_FILE"
