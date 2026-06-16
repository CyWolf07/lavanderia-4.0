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

APP_PORT="${PORT:-80}"

echo "========================================"
echo " Configurando Apache en el puerto ${APP_PORT}"
echo "========================================"

sed -ri "s/^Listen [0-9]+$/Listen ${APP_PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \\*:[0-9]+>/<VirtualHost *:${APP_PORT}>/" /etc/apache2/sites-available/000-default.conf

echo "========================================"
echo " Verificando variables criticas"
echo "========================================"

if [ -f .env ]; then
    export $(grep -v '^#' .env | xargs)
fi

if [ -z "${APP_KEY:-}" ]; then
    echo "ADVERTENCIA: APP_KEY no esta configurada en el entorno."
    echo "Asegurate de que este en tu archivo .env o en las variables de entorno del hosting."
fi

echo "APP_ENV=${APP_ENV:-production}"
echo "APP_DEBUG=${APP_DEBUG:-false}"
echo "PORT=${APP_PORT}"

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
# Soporta DB_URL o DATABASE_URL, ademas de variables individuales (DB_HOST, DB_PORT...)
echo "========================================"
echo " Esperando a PostgreSQL..."
echo "========================================"

attempt=0
db_driver="${DB_CONNECTION:-}"

if [ -z "$db_driver" ]; then
    if [ -n "${DATABASE_URL:-}" ] || [ -n "${DB_URL:-}" ] || [ -n "${PGHOST:-}" ]; then
        db_driver="pgsql"
    else
        db_driver="sqlite"
    fi
fi

if [ "$db_driver" = "pgsql" ]; then
    resolved_db_url="${DB_URL:-${DATABASE_URL:-}}"

    if [ -n "$resolved_db_url" ]; then
        resolved_db_host="$(RESOLVED_DB_URL="$resolved_db_url" php -r '$parts = parse_url(getenv("RESOLVED_DB_URL")); echo $parts["host"] ?? "db";')"
        resolved_db_port="$(RESOLVED_DB_URL="$resolved_db_url" php -r '$parts = parse_url(getenv("RESOLVED_DB_URL")); echo $parts["port"] ?? "5432";')"
        resolved_db_name="$(RESOLVED_DB_URL="$resolved_db_url" php -r '$parts = parse_url(getenv("RESOLVED_DB_URL")); echo ltrim($parts["path"] ?? "/postgres", "/");')"
    else
        resolved_db_host="${DB_HOST:-${PGHOST:-db}}"
        resolved_db_port="${DB_PORT:-${PGPORT:-5432}}"
        resolved_db_name="${DB_DATABASE:-${PGDATABASE:-postgres}}"
    fi

    echo "DB_CONNECTION=pgsql"
    echo "DB_HOST=${resolved_db_host}"
    echo "DB_PORT=${resolved_db_port}"
    echo "DB_DATABASE=${resolved_db_name}"
    if [ -n "${DATABASE_URL:-}" ] || [ -n "${DB_URL:-}" ]; then
        echo "DATABASE_URL_PRESENT=yes"
    else
        echo "DATABASE_URL_PRESENT=no"
    fi

until php -r '
$url = getenv("DB_URL") ?: (getenv("DATABASE_URL") ?: null);
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

$host     = $parts["host"] ?? (getenv("DB_HOST") ?: (getenv("PGHOST") ?: "db"));
$port     = (string) ($parts["port"] ?? (getenv("DB_PORT") ?: (getenv("PGPORT") ?: "5432")));
$database = ltrim((string) ($parts["path"] ?? (getenv("DB_DATABASE") ?: (getenv("PGDATABASE") ?: "postgres"))), "/");
$username = urldecode((string) ($parts["user"] ?? (getenv("DB_USERNAME") ?: (getenv("PGUSER") ?: "postgres"))));
$password = urldecode((string) ($parts["pass"] ?? (getenv("DB_PASSWORD") ?: (getenv("PGPASSWORD") ?: ""))));
$sslmode  = $query["sslmode"] ?? getenv("DB_SSLMODE") ?: (getenv("PGSSLMODE") ?: null);

if (str_starts_with($password, "[") && str_ends_with($password, "]")) {
    fwrite(STDERR, "La contrasena parece incluir corchetes de placeholder. En Supabase reemplaza [YOUR-PASSWORD] sin dejar [ ]." . PHP_EOL);
    exit(1);
}

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

    if [ "$attempt" -ge 15 ]; then
        echo "ADVERTENCIA: No fue posible conectarse a PostgreSQL despues de 15 intentos."
        echo "Continuando de todas formas..."
        break
    fi

    echo "Intento $attempt de 15, reintentando en 2s..."
    sleep 2
done

echo "PostgreSQL disponible. Continuando..."
echo "========================================"
else
    echo "DB_CONNECTION=${db_driver}. No se requiere espera activa para PostgreSQL."
    echo "========================================"
fi

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
php artisan migrate --force || true

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
echo " Asegurando un unico Apache MPM..."
echo "========================================"

find /etc/apache2/mods-enabled/ -name 'mpm_*' -delete
ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf
ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
apache2ctl -t

echo "========================================"
echo " Aplicacion lista. Iniciando Apache..."
echo "========================================"

# ── 7. Iniciar Apache en primer plano ─────────────────────────────────────────
# 'exec' reemplaza este proceso shell con Apache para que Docker lo gestione correctamente
exec apache2-foreground
