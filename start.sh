#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"

if command -v php >/dev/null 2>&1; then
  echo "Odpri http://localhost:8080"
  exec php -S 0.0.0.0:8080 router.php
fi

if command -v docker >/dev/null 2>&1; then
  echo "PHP ni nameščen lokalno, zaganja Docker na http://localhost:8080"
  if command -v docker-compose >/dev/null 2>&1 || docker compose version >/dev/null 2>&1; then
    exec docker compose up --build
  fi
  docker rm -f hanko-web >/dev/null 2>&1 || true
  docker build -t hanko-apartmaji .
  exec docker run --rm --name hanko-web -p 8080:8080 -e PORT=8080 hanko-apartmaji
fi

echo "Namestite PHP 8.1+ ali Docker."
exit 1
