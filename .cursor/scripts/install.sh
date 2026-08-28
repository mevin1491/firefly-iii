#!/usr/bin/env bash
set -euo pipefail

cd /workspace

export PATH="${HOME}/.local/bin:/usr/local/bin:${PATH}"

sudo service mariadb start

if [[ ! -f .env ]]; then
  cp .env.example .env
  sed -i 's/^APP_ENV=production/APP_ENV=local/' .env
  sed -i 's/^APP_DEBUG=false/APP_DEBUG=true/' .env
  sed -i 's/^DB_HOST=db/DB_HOST=127.0.0.1/' .env
fi

composer install --no-interaction --prefer-dist
npm ci

if ! grep -qE '^APP_KEY=base64:' .env; then
  php artisan key:generate --force --no-interaction
fi

if ! mysql -u firefly -psecret_firefly_password firefly -e "SHOW TABLES LIKE 'migrations'" 2>/dev/null | grep -q migrations; then
  php artisan firefly-iii:upgrade-database --no-interaction
  php artisan db:seed --no-interaction
  php artisan firefly-iii:correct-database --no-interaction
  php artisan firefly-iii:laravel-passport-keys --no-interaction
fi
