#!/usr/bin/env bash
# Exit on error
set -e

echo "=== 1. Install Composer Dependencies ==="
composer install --no-dev --optimize-autoloader --no-interaction

echo "=== 2. Build Frontend Assets (Vite) ==="
npm install
npm run build

echo "=== 3. Prepare Storage & Cache ==="
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== 4. Run Migrations & Seeders ==="
php artisan migrate --force
php artisan db:seed --class=AkruDatabaseSeeder --force || true

echo "=== Deployment Ready ==="
