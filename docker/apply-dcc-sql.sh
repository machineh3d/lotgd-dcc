#!/usr/bin/env bash
# ============================================================================
# Apply / Re-apply DCC SQL Packs to a Dockerized Database Container
# Usage (run on your Docker host after installer.php finishes, or on upgrades):
#   DB_CONTAINER="your-db-container" DB_USER="YOUR_DB_USER" \
#   DB_PASS="YOUR_DB_PASSWORD" DB_NAME="YOUR_DB_NAME" ./docker/apply-dcc-sql.sh
# ============================================================================
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SQL_DIR="${SQL_DIR:-${SCRIPT_DIR}/../sql}"
DB_CONTAINER="${DB_CONTAINER:-your-db-container}"
WEB_CONTAINER="${WEB_CONTAINER:-your-web-container}"
DB_USER="${DB_USER:?Set DB_USER}"
DB_PASS="${DB_PASS:?Set DB_PASS}"
DB_NAME="${DB_NAME:?Set DB_NAME}"

run_sql() {
    if docker exec "${DB_CONTAINER}" sh -c "command -v mariadb >/dev/null 2>&1"; then
        docker exec -i "${DB_CONTAINER}" mariadb -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}"
    else
        docker exec -i "${DB_CONTAINER}" mysql -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}"
    fi
}

for f in \
    lotgd_dcc_total_conversion.sql \
    lotgd_dcc_mounts.sql \
    lotgd_dcc_taunts_deaths.sql \
    lotgd_dcc_shop_dialogue.sql
do
    echo "[SYSTEM AI] Importing ${f}..."
    run_sql < "${SQL_DIR}/${f}"
done

echo "[SYSTEM AI] Setting defaultskin = dcc_hud_minimal..."
run_sql << 'EOF'
INSERT INTO settings (setting, value) VALUES ('defaultskin', 'dcc_hud_minimal')
ON DUPLICATE KEY UPDATE value = 'dcc_hud_minimal';
DELETE FROM module_hooks WHERE modulename IN ('raceelf', 'racedwarf', 'racetroll', 'racehuman', 'oldman', 'statue', 'fairy');
DELETE FROM modules WHERE modulename IN ('raceelf', 'racedwarf', 'racetroll', 'racehuman', 'oldman', 'statue', 'fairy');
EOF

if docker ps --format '{{.Names}}' | grep -qx "${WEB_CONTAINER}"; then
    echo "[SYSTEM AI] Clearing cache in ${WEB_CONTAINER}..."
    docker exec -i "${WEB_CONTAINER}" sh -c 'rm -rf /var/www/html/cache/* /var/www/html/datacache/* /var/www/html/data/cache/* 2>/dev/null || true'
fi

echo "[OK] All DCC SQL packs applied and cache cleared!"
