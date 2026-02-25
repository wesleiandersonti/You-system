#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/opt/you-system"

cd "$APP_DIR"
sudo git pull --rebase origin main
sudo chown -R www-data:www-data "$APP_DIR"
sudo systemctl restart php8.1-fpm nginx

echo "Atualizado com sucesso ✅"
