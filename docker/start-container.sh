#!/bin/sh
# =============================================================================
# start-container.sh — Script de arranque del contenedor Docker
#
# Secuencia:
#   1. Crear carpetas necesarias para Laravel
#   2. Ajustar permisos de escritura
#   3. Esperar que PostgreSQL esté disponible (hasta 30 intentos / ~60 segundos)
#   4. Limpiar caché de configuración compilada (puede fallar sin problema)
#   5. Ejecutar migraciones y seeders
#   6. Crear el enlace simbólico de storage (si no existe)
#   7. Iniciar Apache en primer plano (proceso principal del contenedor)
# =============================================================================
set -e

cd /var/www/html

# ── 1. Crear estructura de carpetas requeridas por Laravel ────────────────────
# Laravel necesita estas carpetas para funcionar correctamente
mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/views-runtime \
    storage/framework/testing/views-runtime \
    storage/app/backups \
    storage/logs \
    bootstrap/cache

# ── 2. Permisos para el usuario del servidor web ─────────────────────────────
# Apache corre como www-data, necesita escribir en storage y bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# ── 3. Esperar conexión a PostgreSQL ─────────────────────────────────────────
# Intenta conectarse hasta 30 veces con 2 segundos de espera entre intentos
# Soporta DB_URL (Railway/Render) o variables individuales (DB_HOST, DB_PORT...)
echo "========================================"
echo " Esperando a PostgreSQL..."
echo "========================================"

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

$host     = $parts["host"] ?? (getenv("DB_HOST") ?: "db");
$port     = (string) ($parts["port"] ?? (getenv("DB_PORT") ?: "5432"));
$database = ltrim((string) ($parts["path"] ?? (getenv("DB_DATABASE") ?: "postgres")), "/");
$username = urldecode((string) ($parts["user"] ?? (getenv("DB_USERNAME") ?: "postgres")));
$password = urldecode((string) ($parts["pass"] ?? (getenv("DB_PASSWORD") ?: "")));
$sslmode  = $query["sslmode"] ?? getenv("DB_SSLMODE") ?: null;

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
        echo "FATAL: No fue posible conectarse a PostgreSQL despues de 30 intentos."
        exit 1
    fi

    echo "Intento $attempt de 30, reintentando en 2s..."
    sleep 2
done

echo "PostgreSQL disponible. Continuando..."
echo "========================================"

# ── 4. Limpiar caché de archivos compilados ───────────────────────────────────
# Se usa '|| true' para que NO detenga el script si falla
# (puede fallar si la tabla cache no existe todavia, lo cual es normal en primer deploy)
echo "Limpiando cache de configuracion compilada..."
php artisan config:clear    > /dev/null 2>&1 || true
php artisan route:clear     > /dev/null 2>&1 || true
php artisan view:clear      > /dev/null 2>&1 || true
php artisan event:clear     > /dev/null 2>&1 || true

# ── 5. Ejecutar migraciones y seeders ─────────────────────────────────────────
# --force es obligatorio en ambiente production
# Las migraciones crean las tablas: users, sessions, cache, jobs, etc.
echo "Ejecutando migraciones..."
php artisan migrate --force

# Los seeders crean datos iniciales (roles, configuracion, admin por defecto)
echo "Ejecutando seeders..."
php artisan db:seed --force || true   # || true: si ya existe data no falla el deploy

# Regenerar cache de rutas y configuracion despues de migraciones exitosas
echo "Optimizando configuracion..."
php artisan config:cache  > /dev/null 2>&1 || true
php artisan route:cache   > /dev/null 2>&1 || true

# ── 6. Enlace de storage ──────────────────────────────────────────────────────
# Crea public/storage → storage/app/public (para archivos subidos)
if [ ! -L public/storage ]; then
    echo "Creando enlace de storage..."
    php artisan storage:link || true
fi

echo "========================================"
echo " Aplicacion lista. Iniciando Apache..."
echo "========================================"

# ── 7. Iniciar Apache en primer plano ─────────────────────────────────────────
# 'exec' reemplaza este proceso shell con Apache para que Docker lo gestione correctamente
exec apache2-foreground
