#!/usr/bin/env bash
#
# Cloud Agent install script for Firefly III.
# Idempotent: safe to run repeatedly against a cached or fresh checkout.
# PHP 8.4, Composer and Node are provided by the base environment snapshot.
#
set -euo pipefail

cd "$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." &> /dev/null && pwd)"
ROOT="$(pwd)"

# The .env file and SQLite database files must exist BEFORE `composer install`,
# because Composer's post-install scripts boot Laravel (which requires APP_KEY).
# The key shipped in .env.example is a valid 32-char key; it is replaced with a
# freshly generated random key further below, once Composer has installed the
# framework and the artisan command is available.
echo "==> Preparing .env (local SQLite development configuration)"
if [ ! -f .env ]; then
    cp .env.example .env
    sed -i 's/^APP_ENV=production/APP_ENV=local/' .env
    sed -i 's/^APP_DEBUG=false/APP_DEBUG=true/' .env
    sed -i 's/^DB_CONNECTION=mysql/DB_CONNECTION=sqlite/' .env
    sed -i "s|^DB_DATABASE=firefly|DB_DATABASE=${ROOT}/database/database.sqlite|" .env
    sed -i 's|^APP_URL=http://localhost|APP_URL=http://localhost:8080|' .env
fi

echo "==> Ensuring SQLite database files (application + test suite)"
touch database/database.sqlite
mkdir -p storage/database
touch storage/database/database.sqlite

echo "==> Installing PHP dependencies (composer)"
composer install --no-interaction --no-progress

echo "==> Installing Node dependencies (applies patch-package patches)"
npm ci --no-audit --no-fund

echo "==> Building v1 frontend assets (Laravel Mix)"
npm run production --workspace=v1

echo "==> Building v2 frontend assets (Vite)"
npm run build --workspace=v2

echo "==> Ensuring a freshly generated application key"
grep -q '^APP_KEY=base64:' .env || php artisan key:generate --force

echo "==> Running database migrations and seeders"
php artisan migrate --seed --force

echo "==> Applying Firefly III database upgrades"
php artisan firefly-iii:upgrade-database

echo "==> Ensuring Laravel Passport keys"
php artisan firefly-iii:laravel-passport-keys

echo "==> Clearing caches"
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "==> Install complete."
