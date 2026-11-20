#!/usr/bin/env bash
# ============================================================================
# DUNGEON CRAWLER CARL — TOTAL CONVERSION INSTALLER (Release v0.3.7-alpha)
# Target Engine: Fresh LotGD NB-Core v2.0.9+ (PHP 8.4 / 8.5)
# Repository: https://github.com/machineh3d/lotgd-dcc
# ============================================================================
# USAGE:
#   chmod +x install.sh
#   ./install.sh
#
# OPTIONAL ENVIRONMENT OVERRIDES:
#   WEB_CONTAINER="your-web-container"     # Docker container running PHP/Apache (or leave empty for local)
#   DB_CONTAINER="your-db-container"       # Docker container running MariaDB/MySQL (or leave empty for local)
#   LOTGD_ROOT="/var/www/html"  # Path to LotGD webroot (inside container or local host)
#   DB_HOST="localhost"         # Database host (for local CLI mode)
#   DB_NAME="YOUR_DB_NAME"             # Database name
#   DB_USER="YOUR_DB_USER"         # Database user
#   DB_PASS="YOUR_DB_PASSWORD"     # Database password
#   DB_PREFIX=""                # Optional table prefix (e.g. "lotgd_")
# ============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
RELEASE_VER="$(cat "${SCRIPT_DIR}/VERSION" 2>/dev/null || echo '0.2.0-alpha')"
WEB_CONTAINER="${WEB_CONTAINER:-}"
DB_CONTAINER="${DB_CONTAINER:-}"
LOTGD_ROOT="${LOTGD_ROOT:-/var/www/html}"
DB_HOST="${DB_HOST:-localhost}"
DB_NAME="${DB_NAME:-YOUR_DB_NAME}"
DB_USER="${DB_USER:-YOUR_DB_USER}"
DB_PASS="${DB_PASS:-}"
DB_PREFIX="${DB_PREFIX:-}"
BACKUP_DIR="${SCRIPT_DIR}/backups"
TS="$(date +'%Y%m%d_%H%M%S')"

CYAN='\033[0;36m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'
info() { echo -e "${CYAN}[SYSTEM AI v${RELEASE_VER}]${NC} $*"; }
ok()   { echo -e "${GREEN}[OK]${NC} $*"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $*"; }
err()  { echo -e "${RED}[ERROR]${NC} $*" >&2; }

run_web() {
    if [[ -n "${WEB_CONTAINER}" ]]; then
        docker exec -i "${WEB_CONTAINER}" sh -c "$1"
    else
        sh -c "$1"
    fi
}

copy_to_web() {
    local src="$1"
    local dst="$2"
    if [[ -n "${WEB_CONTAINER}" ]]; then
        docker cp "${src}" "${WEB_CONTAINER}:${dst}"
    else
        cp -rf "${src}" "${dst}"
    fi
}

run_mysql() {
    local pass_arg=()
    [[ -n "${DB_PASS}" ]] && pass_arg=("-p${DB_PASS}")
    if [[ -n "${DB_CONTAINER}" ]]; then
        if docker exec "${DB_CONTAINER}" sh -c "command -v mariadb >/dev/null 2>&1"; then
            docker exec -i "${DB_CONTAINER}" mariadb -u"${DB_USER}" "${pass_arg[@]}" "${DB_NAME}"
        else
            docker exec -i "${DB_CONTAINER}" mysql -u"${DB_USER}" "${pass_arg[@]}" "${DB_NAME}"
        fi
    else
        if command -v mariadb >/dev/null 2>&1; then
            mariadb -h"${DB_HOST}" -u"${DB_USER}" "${pass_arg[@]}" "${DB_NAME}"
        else
            mysql -h"${DB_HOST}" -u"${DB_USER}" "${pass_arg[@]}" "${DB_NAME}"
        fi
    fi
}

run_mysqldump() {
    local pass_arg=()
    [[ -n "${DB_PASS}" ]] && pass_arg=("-p${DB_PASS}")
    if [[ -n "${DB_CONTAINER}" ]]; then
        if docker exec "${DB_CONTAINER}" sh -c "command -v mariadb-dump >/dev/null 2>&1"; then
            docker exec -i "${DB_CONTAINER}" mariadb-dump --single-transaction --no-tablespaces -u"${DB_USER}" "${pass_arg[@]}" "${DB_NAME}"
        else
            docker exec -i "${DB_CONTAINER}" mysqldump --single-transaction --no-tablespaces -u"${DB_USER}" "${pass_arg[@]}" "${DB_NAME}"
        fi
    else
        if command -v mariadb-dump >/dev/null 2>&1; then
            mariadb-dump --single-transaction --no-tablespaces -h"${DB_HOST}" -u"${DB_USER}" "${pass_arg[@]}" "${DB_NAME}"
        else
            mysqldump --single-transaction --no-tablespaces -h"${DB_HOST}" -u"${DB_USER}" "${pass_arg[@]}" "${DB_NAME}"
        fi
    fi
}

# 1. Database Backup
mkdir -p "${BACKUP_DIR}"
BACKUP_FILE="${BACKUP_DIR}/lotgd_pre_dcc_${TS}.sql"
info "Backing up database '${DB_NAME}' to ${BACKUP_FILE}..."
run_mysqldump > "${BACKUP_FILE}"
[[ -s "${BACKUP_FILE}" ]] || { err "Backup failed or empty!"; exit 1; }
ok "Database backup complete."

# 2. Import SQL Packs
for sql_file in lotgd_dcc_total_conversion.sql lotgd_dcc_mounts.sql lotgd_dcc_taunts_deaths.sql lotgd_dcc_shop_dialogue.sql; do
    info "Importing sql/${sql_file}..."
    if [[ -n "${DB_PREFIX}" ]]; then
        sed -E "s/\`(settings|translations|weapons|armor|masters|creatures|mounts|taunts|deathmessages)\`/\`${DB_PREFIX}\1\`/g" \
            "${SCRIPT_DIR}/sql/${sql_file}" | run_mysql
    else
        run_mysql < "${SCRIPT_DIR}/sql/${sql_file}"
    fi
    ok "Imported ${sql_file}."
done

# 3. Install PHP 8.4/8.5 Modules
info "Installing 13 DCC PHP modules into ${LOTGD_ROOT}/modules/..."
run_web "mkdir -p '${LOTGD_ROOT}/modules'"
for mod in "${SCRIPT_DIR}"/modules/*.php; do
    mod_name="$(basename "${mod}")"
    copy_to_web "${mod}" "${LOTGD_ROOT}/modules/${mod_name}"
done
run_web "rm -f '${LOTGD_ROOT}/modules/raceelf.php' '${LOTGD_ROOT}/modules/racedwarf.php' '${LOTGD_ROOT}/modules/racetroll.php' '${LOTGD_ROOT}/modules/racehuman.php' '${LOTGD_ROOT}/modules/oldman.php' '${LOTGD_ROOT}/modules/statue.php' '${LOTGD_ROOT}/modules/fairy.php' && chmod 0644 '${LOTGD_ROOT}/modules/'*.php"
ok "All 13 modules installed."

# 4. Build & Install NB-Core Twig Themes (dcc_hud & dcc_hud_minimal)
info "Installing Twig themes (dcc_hud & dcc_hud_minimal) from aurora base..."
run_web "
for theme in dcc_hud dcc_hud_minimal; do
  rm -rf '${LOTGD_ROOT}/templates_twig/'\${theme}
  if [ -d '${LOTGD_ROOT}/templates_twig/aurora_minimal' ] && [ \"\${theme}\" = 'dcc_hud_minimal' ]; then
    cp -a '${LOTGD_ROOT}/templates_twig/aurora_minimal' '${LOTGD_ROOT}/templates_twig/'\${theme}
    sed -i 's|templates_twig/aurora_minimal|templates_twig/'\${theme}'|g' '${LOTGD_ROOT}/templates_twig/'\${theme}'/'*.twig
  else
    cp -a '${LOTGD_ROOT}/templates_twig/aurora' '${LOTGD_ROOT}/templates_twig/'\${theme}
    sed -i 's|templates_twig/aurora|templates_twig/'\${theme}'|g' '${LOTGD_ROOT}/templates_twig/'\${theme}'/'*.twig
  fi
done
sed -i 's|<div class=\"login-hero\"></div>||g' '${LOTGD_ROOT}/templates_twig/dcc_hud/login'*.twig '${LOTGD_ROOT}/templates_twig/dcc_hud_minimal/login'*.twig
sed -i -E 's~<img[^>]*nav-(top|bottom)\\.png[^>]*>~~g' '${LOTGD_ROOT}/templates_twig/dcc_hud_minimal/page.twig'
sed -i -E 's|<img[^>]*title-logo\\.png[^>]*>|<div style=\"color:#00f0ff;font-family:Consolas,monospace;font-weight:900;font-size:1.25rem;letter-spacing:1.5px;\">&#9679; DUNGEON CRAWLER WORLD</div>|g' '${LOTGD_ROOT}/templates_twig/dcc_hud_minimal/page.twig'
"

copy_to_web "${SCRIPT_DIR}/themes/dcc_hud/config.json" "${LOTGD_ROOT}/templates_twig/dcc_hud/config.json"
copy_to_web "${SCRIPT_DIR}/themes/dcc_hud/dcc_syndicate_hud.css" "${LOTGD_ROOT}/templates_twig/dcc_hud/assets/dcc_syndicate_hud.css"
copy_to_web "${SCRIPT_DIR}/themes/dcc_hud_minimal/config.json" "${LOTGD_ROOT}/templates_twig/dcc_hud_minimal/config.json"
copy_to_web "${SCRIPT_DIR}/themes/dcc_hud_minimal/dcc_syndicate_hud_minimal.css" "${LOTGD_ROOT}/templates_twig/dcc_hud_minimal/assets/dcc_syndicate_hud_minimal.css"

run_web "
cat '${LOTGD_ROOT}/templates_twig/dcc_hud/assets/dcc_syndicate_hud.css' >> '${LOTGD_ROOT}/templates_twig/dcc_hud/assets/style.css'
cat '${LOTGD_ROOT}/templates_twig/dcc_hud_minimal/assets/dcc_syndicate_hud_minimal.css' >> '${LOTGD_ROOT}/templates_twig/dcc_hud_minimal/assets/style.css'
printf '.login-hero{display:none!important;height:0!important;min-height:0!important;padding:0!important;margin:0!important;}.page-header{min-height:0!important;height:auto!important;padding:10px 16px!important;background-image:none!important;align-items:center!important;}\n' >> '${LOTGD_ROOT}/templates_twig/dcc_hud_minimal/assets/style.css'
"

# Set dcc_hud_minimal as defaultskin
SETTINGS_TABLE="${DB_PREFIX}settings"
run_mysql <<EOF
INSERT INTO \`${SETTINGS_TABLE}\` (\`setting\`, \`value\`)
VALUES ('defaultskin', 'dcc_hud_minimal')
ON DUPLICATE KEY UPDATE \`value\` = 'dcc_hud_minimal';
EOF
ok "Themes installed and defaultskin set to 'dcc_hud_minimal'."

# 5. Clear Cache
info "Clearing LotGD cache..."
run_web "rm -rf '${LOTGD_ROOT}/cache/'* '${LOTGD_ROOT}/datacache/'* '${LOTGD_ROOT}/data/cache/'* 2>/dev/null || true"
ok "Cache cleared!"

echo -e "${GREEN}============================================================================${NC}"
echo -e "${GREEN}NEW ACHIEVEMENT! Dungeon Crawler Carl v${RELEASE_VER} Installed!${NC}"
echo -e "1. Log into Superuser Grotto -> Manage Modules -> 'Dungeon Crawler World'."
echo -e "2. Install & Activate all 13 DCC modules."
echo -e "3. Stock medieval modules (raceelf, racedwarf, racetroll, racehuman, oldman, statue, fairy) were deactivated automatically!"
echo -e "4. Hard-refresh your browser (Ctrl+F5)!"
echo -e "${GREEN}============================================================================${NC}"
