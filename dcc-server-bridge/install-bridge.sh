#!/usr/bin/env bash
set -euo pipefail

# Installs the LotGD-DCC Native Android JSON API Bridge (v0.2.0) into a running NB-Core Docker container.
# Usage: ./install-bridge.sh [container_name]
CONTAINER="${1:-dcc-web-prod}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "Installing LotGD-DCC JSON API Bridge into container: ${CONTAINER}"
docker cp "${SCRIPT_DIR}/dcc_api_bridge.php" "${CONTAINER}:/var/www/html/dcc_api_bridge.php"

docker exec -i "${CONTAINER}" sh -c '
  CONF_DIR="$(ls -d /etc/php/*/apache2/conf.d /usr/local/etc/php/conf.d 2>/dev/null | head -n 1)"
  echo "Detected PHP conf.d: ${CONF_DIR}"
  echo "auto_prepend_file = /var/www/html/dcc_api_bridge.php" > "${CONF_DIR}/99-dcc-api.ini"
  chown www-data:www-data /var/www/html/dcc_api_bridge.php
  chmod 0644 /var/www/html/dcc_api_bridge.php "${CONF_DIR}/99-dcc-api.ini"
  php -l /var/www/html/dcc_api_bridge.php
'

docker restart "${CONTAINER}"
echo "Bridge installed! Testing JSON response..."
sleep 2
docker exec -i "${CONTAINER}" curl -sL -H "X-DCC-Client: 1" http://127.0.0.1/home.php | head -c 300
echo ""
echo "Done."
