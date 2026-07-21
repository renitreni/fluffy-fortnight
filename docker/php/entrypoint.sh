#!/bin/sh
set -e

# Wait for MySQL to be ready
echo "Waiting for database connection..."
until php -r "
    \$pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD')
    );
    echo 'Connected';
" 2>/dev/null; do
    echo "  DB not ready, retrying in 2s..."
    sleep 2
done
echo "Database is ready."

# Run Laravel bootstrap tasks
php artisan key:generate --no-interaction --force 2>/dev/null || true
php artisan storage:link --no-interaction 2>/dev/null || true
php artisan migrate --force --no-interaction
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Execute the main container command (php-fpm or queue:work)
exec "$@"
