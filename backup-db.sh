#!/bin/bash
# ============================================================
# Backup database Bimba KSR
# Container MySQL: adminlte_db
# ============================================================

set -e

# Konfigurasi (sesuai env container adminlte_db)
CONTAINER_NAME="adminlte_db"
DB_NAME="bimba_ksr"
DB_USER="root"
DB_PASS="rootpass"

# Nama file backup (dengan timestamp)
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="backup_${DB_NAME}_${TIMESTAMP}.sql"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
BACKUP_PATH="${SCRIPT_DIR}/${BACKUP_FILE}"

echo "=========================================="
echo " Backup Database: ${DB_NAME}"
echo " Container     : ${CONTAINER_NAME}"
echo " File          : ${BACKUP_FILE}"
echo "=========================================="

# Cek container jalan
if ! docker ps --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
    echo "ERROR: Container '${CONTAINER_NAME}' tidak ditemukan / tidak berjalan."
    echo "Jalankan: docker ps"
    exit 1
fi

# Backup
echo "Sedang membackup..."
docker exec "${CONTAINER_NAME}" mysqldump \
    -u "${DB_USER}" \
    -p"${DB_PASS}" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    "${DB_NAME}" > "${BACKUP_PATH}"

# Cek hasil
SIZE=$(du -h "${BACKUP_PATH}" | cut -f1)
LINES=$(wc -l < "${BACKUP_PATH}")

if [ "${LINES}" -lt 10 ]; then
    echo "WARNING: File backup terlihat terlalu kecil (${LINES} baris)."
    echo "Cek apakah database '${DB_NAME}' benar-benar ada."
    exit 1
fi

echo ""
echo "Backup berhasil!"
echo "  Path : ${BACKUP_PATH}"
echo "  Size : ${SIZE}"
echo "  Lines: ${LINES}"
echo ""
echo "Selesai. Silakan lanjut docker compose down / build."