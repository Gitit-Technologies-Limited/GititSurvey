#!/bin/bash
set -e

echo "Waiting for MySQL to be ready..."
until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT}', '${DB_USER}', '${DB_PASSWORD}');" 2>/dev/null; do
    echo "MySQL is unavailable - sleeping"
    sleep 2
done

echo "MySQL is ready!"

# Check if database has tables
TABLE_COUNT=$(php -r "
try {
    \$pdo = new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_NAME}', '${DB_USER}', '${DB_PASSWORD}');
    \$stmt = \$pdo->query('SHOW TABLES');
    echo \$stmt->rowCount();
} catch (Exception \$e) {
    echo '0';
}
")

if [ "$TABLE_COUNT" -eq "0" ]; then
    echo "Database is empty!"
    echo "Please run the Survey installer at http://localhost:8080/installer"
    exit 0
else
    echo "Found $TABLE_COUNT tables in database"
fi

# Run  migrations if available
if [ -f "/var/www/html/application/commands/console.php" ]; then
    echo "Running Survey migrations..."
    cd /var/www/html
    php application/commands/console.php migrate up  --interactive=0|| {
        echo "Migration command failed or not applicable"
        exit 0
    }
    echo "Migrations completed successfully!"
else
    echo "Console script not found, skipping migrations"
fi

# Initialize Metabase views
if [ -f "/var/www/html/scripts/init_metabase_views.sh" ]; then
    echo "Initializing Metabase views..."
    bash /var/www/html/scripts/init_metabase_views.sh
fi
