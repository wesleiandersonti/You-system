#!/usr/bin/env bash
set -euo pipefail

URL="http://127.0.0.1/?health=1"

echo "Testando: $URL"
HTTP_CODE=$(curl -s -o /tmp/you-health.json -w "%{http_code}" "$URL" || true)

echo "HTTP: $HTTP_CODE"
cat /tmp/you-health.json || true

if [ "$HTTP_CODE" != "200" ]; then
  echo "Healthcheck falhou ❌"
  exit 1
fi

echo "Healthcheck OK ✅"
