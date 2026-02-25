#!/usr/bin/env bash
set -euo pipefail

DOMAIN="${1:-}"
EMAIL="${2:-admin@localhost}"
ENABLE_CERTBOT="${3:-no}"  # yes|no

echo "[1/6] Instalando UFW + Fail2ban..."
sudo apt-get update -y
sudo apt-get install -y ufw fail2ban

echo "[2/6] Configurando UFW..."
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable

echo "[3/6] Configurando Fail2ban..."
sudo tee /etc/fail2ban/jail.local >/dev/null <<'EOF'
[DEFAULT]
bantime = 1h
findtime = 10m
maxretry = 5

[sshd]
enabled = true
port = ssh
logpath = %(sshd_log)s
backend = systemd
EOF

sudo systemctl enable fail2ban
sudo systemctl restart fail2ban

echo "[4/6] Aplicando headers de segurança no Nginx (you-system)..."
NGINX_SITE="/etc/nginx/sites-available/you-system"
if [ -f "$NGINX_SITE" ]; then
  if ! grep -q "X-Frame-Options" "$NGINX_SITE"; then
    sudo sed -i '/server_name _;/a\
    server_tokens off;\
    add_header X-Frame-Options "SAMEORIGIN" always;\
    add_header X-Content-Type-Options "nosniff" always;\
    add_header Referrer-Policy "no-referrer-when-downgrade" always;\
    add_header X-XSS-Protection "1; mode=block" always;\
    client_max_body_size 1m;' "$NGINX_SITE"
  fi
  sudo nginx -t
  sudo systemctl reload nginx
else
  echo "[WARN] Site $NGINX_SITE não encontrado. Pulei ajuste Nginx."
fi

if [ "$ENABLE_CERTBOT" = "yes" ]; then
  if [ -z "$DOMAIN" ]; then
    echo "[ERRO] Para certbot, informe domínio no 1º parâmetro."
    exit 1
  fi
  echo "[5/6] Instalando Certbot e emitindo certificado para $DOMAIN..."
  sudo apt-get install -y certbot python3-certbot-nginx
  sudo certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos -m "$EMAIL" --redirect
  sudo certbot renew --dry-run
else
  echo "[5/6] Certbot desativado (ENABLE_CERTBOT=no)."
fi

echo "[6/6] Status final"
sudo ufw status verbose || true
sudo fail2ban-client status || true

echo "Hardening concluído ✅"
echo "Uso com certbot: ./deploy/hardening-ubuntu.sh seu-dominio.com seu-email@dominio.com yes"
