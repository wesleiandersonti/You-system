#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/opt/you-system"
NGINX_SITE="/etc/nginx/sites-available/you-system"
PHP_VER="8.1"

echo "[1/7] Instalando pacotes..."
sudo apt-get update -y
sudo apt-get install -y nginx curl git unzip software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt-get update -y
sudo apt-get install -y php${PHP_VER}-fpm php${PHP_VER}-cli

if [ ! -d "$APP_DIR" ]; then
  echo "[2/7] Clonando repositório..."
  sudo git clone https://github.com/wesleiandersonti/You-system.git "$APP_DIR"
else
  echo "[2/7] Repositório já existe, atualizando..."
  cd "$APP_DIR"
  sudo git pull --rebase origin main
fi

echo "[3/7] Ajustando permissões..."
sudo chown -R www-data:www-data "$APP_DIR"
sudo chmod -R 755 "$APP_DIR"

echo "[4/7] Configurando Nginx..."
sudo tee "$NGINX_SITE" >/dev/null <<EOF
server {
    listen 80;
    server_name _;

    root $APP_DIR/public;
    index index.php;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php${PHP_VER}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
EOF

sudo ln -sf "$NGINX_SITE" /etc/nginx/sites-enabled/you-system
sudo rm -f /etc/nginx/sites-enabled/default

echo "[5/7] Validando serviços..."
sudo nginx -t
sudo systemctl enable php${PHP_VER}-fpm nginx
sudo systemctl restart php${PHP_VER}-fpm nginx

echo "[6/7] Testando endpoint local..."
curl -sS "http://127.0.0.1/?id=WkhCfPPgqWc&format=json" || true

echo "[7/7] Concluído ✅"
echo "Acesse: http://SEU_IP/"
echo "Exemplo JSON: http://SEU_IP/?id=WkhCfPPgqWc&format=json"
