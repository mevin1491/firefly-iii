#!/usr/bin/env bash
set -euxo pipefail

cd /workspace

export PATH="${HOME}/.local/bin:/usr/local/bin:${PATH}"

echo "[firefly-install] Starting MariaDB..."
sudo service mariadb start

if [[ ! -f .env ]]; then
  echo "[firefly-install] Creating .env from .env.example"
  cp .env.example .env
  sed -i 's/^APP_ENV=production/APP_ENV=local/' .env
  sed -i 's/^APP_DEBUG=false/APP_DEBUG=true/' .env
  sed -i 's/^DB_HOST=db/DB_HOST=127.0.0.1/' .env
fi

echo "[firefly-install] Running composer install..."
composer install --no-interaction --prefer-dist

echo "[firefly-install] Running npm ci..."
npm ci

if ! grep -qE '^APP_KEY=base64:' .env; then
  echo "[firefly-install] Generating application key..."
  php artisan key:generate --force --no-interaction
fi

if ! mysql -u firefly -psecret_firefly_password firefly -e "SHOW TABLES LIKE 'migrations'" 2>/dev/null | grep -q migrations; then
  echo "[firefly-install] Initializing database..."
  php artisan firefly-iii:upgrade-database --no-interaction
  php artisan db:seed --no-interaction
  php artisan firefly-iii:correct-database --no-interaction
  php artisan firefly-iii:laravel-passport-keys --no-interaction
fi

echo "[firefly-install] Done."
