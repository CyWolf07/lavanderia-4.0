#!/bin/sh
set -e

cd /var/www/html

mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/views-runtime \
    storage/framework/testing/views-runtime \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

echo "Esperando a PostgreSQL..."
attempt=0
until php -r '
$url = getenv("DB_URL") ?: null;
$parts = [];
$query = [];

if ($url) {
    $parts = parse_url($url);

    if ($parts === false) {
        fwrite(STDERR, "DB_URL no es valida." . PHP_EOL);
        exit(1);
    }

    parse_str($parts["query"] ?? "", $query);
}

$host = $parts["host"] ?? (getenv("DB_HOST") ?: "db");
$port = (string) ($parts["port"] ?? (getenv("DB_PORT") ?: "5432"));
$database = ltrim((string) ($parts["path"] ?? (getenv("DB_DATABASE") ?: "postgres")), "/");
$username = urldecode((string) ($parts["user"] ?? (getenv("DB_USERNAME") ?: "postgres")));
$password = urldecode((string) ($parts["pass"] ?? (getenv("DB_PASSWORD") ?: "")));
$sslmode = $query["sslmode"] ?? getenv("DB_SSLMODE") ?: null;

$dsn = "pgsql:host={$host};port={$port};dbname={$database}";

if ($sslmode) {
    $dsn .= ";sslmode={$sslmode}";
}

fwrite(STDOUT, "Intentando conexion a {$host}:{$port}/{$database}" . PHP_EOL);

try {
    new PDO($dsn, $username, $password, [
        PDO::ATTR_TIMEOUT => 5,
    ]);
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
'; do
    attempt=$((attempt + 1))

    if [ "$attempt" -ge 30 ]; then
        echo "No fue posible conectarse a PostgreSQL."
        exit 1
    fi

    sleep 2
done

echo "Base de datos disponible. Ejecutando migraciones y seeders..."
php artisan optimize:clear >/dev/null 2>&1 || true
php artisan migrate --force --seed

if [ ! -L public/storage ]; then
    php artisan storage:link || true
fi

exec apache2-foreground
