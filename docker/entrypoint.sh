#!/bin/bash
set -euo pipefail

PORT="${PORT:-8080}"
DATA_DIR="${HANKO_DATA_PATH:-/var/www/html/data}"

mkdir -p "$DATA_DIR/mail"

exec php -S "0.0.0.0:${PORT}" -t /var/www/html /var/www/html/router.php
