# 🚀 Guía de Despliegue — Lavandería Exclusiva

## Opción A: Railway.app (Recomendado — Gratuito)

Railway ofrece $5 USD de crédito mensual gratis que cubre esta app + PostgreSQL.

### Paso 1 — Subir cambios al repositorio GitHub

```bash
# Desde la carpeta del proyecto en tu PC
cd e:\Lavanderia_Registro

git add .
git commit -m "fix: correccion de encoding, rutas PQRS completas y config Railway"
git push origin main
```

### Paso 2 — Crear cuenta en Railway

1. Ve a **https://railway.app**
2. Haz clic en **"Start a New Project"**
3. Selecciona **"Login with GitHub"** (conecta tu cuenta de GitHub)

### Paso 3 — Crear el proyecto

1. Haz clic en **"New Project"**
2. Selecciona **"Deploy from GitHub repo"**
3. Elige el repositorio: `CyWolf07/lavanderia-4.0`
4. Railway detecta automáticamente el `railway.json` y el `Dockerfile`

### Paso 4 — Agregar la base de datos PostgreSQL

1. En el panel del proyecto, haz clic en **"+ New"**
2. Selecciona **"Database" → "Add PostgreSQL"**
3. Railway crea automáticamente la BD y genera las variables de conexión

### Paso 5 — Configurar variables de entorno

En el servicio web (no en la BD), ve a **"Variables"** y agrega:

| Variable | Valor |
|---|---|
| `APP_NAME` | `Lavanderia Exclusiva` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Pega una clave generada con `php artisan key:generate --show` |
| `APP_LOCALE` | `es` |
| `DB_CONNECTION` | `pgsql` |
| `DATABASE_URL` | `${{Postgres.DATABASE_URL}}` |
| `PGHOST` | `${{Postgres.PGHOST}}` |
| `PGPORT` | `${{Postgres.PGPORT}}` |
| `PGDATABASE` | `${{Postgres.PGDATABASE}}` |
| `PGUSER` | `${{Postgres.PGUSER}}` |
| `PGPASSWORD` | `${{Postgres.PGPASSWORD}}` |
| `PGSSLMODE` | `require` |
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `database` |
| `VIEW_COMPILED_PATH` | `storage/framework/views-runtime` |
| `TRUSTED_PROXIES` | `*` |

> **Nota:** Railway expone estas variables desde el servicio PostgreSQL. La aplicación ahora acepta tanto `PG*` y `DATABASE_URL` como `DB_*`, así que no hace falta duplicarlas si enlazaste la base al servicio web.

> **Puerto de Railway:** Railway inyecta una variable `PORT` dinámica para el contenedor. El script `docker/start-container.sh` ya reconfigura Apache para escuchar ese puerto antes de iniciar.
>
> **Healthcheck de Railway:** Debe apuntar a `/up`, que es el endpoint de salud configurado en `bootstrap/app.php` y `railway.json`.

### Paso 6 — Desplegar

1. Railway despliega automáticamente al detectar cambios en `main`
2. El script `docker/start-container.sh` ejecuta `php artisan migrate --seed` al arrancar
3. En 2-3 minutos la app estará en un dominio tipo: `https://lavanderia-exclusiva.up.railway.app`

### Paso 7 — Obtener dominio público

1. En el servicio, ve a **"Settings" → "Networking"**
2. Haz clic en **"Generate Domain"**
3. ¡Listo! Tu URL quedará activa permanentemente

---

## Opción B: Docker local (Para desarrollo)

```bash
# Desde la raíz del proyecto
docker compose up --build

# La app queda en: http://localhost:8080
# PostgreSQL en: localhost:5433
```

El archivo `.env.docker` ya está configurado con las credenciales locales de Docker.

---

## 🗄️ Backup de la base de datos

### En Railway (vía CLI)

```bash
# Instalar Railway CLI
npm install -g @railway/cli

# Conectar tu proyecto
railway login
railway link

# Ejecutar el comando de backup
railway run php artisan db:backup
```

El archivo SQL se guarda en `storage/app/backups/backup_YYYY-MM-DD_HHmmss.sql`

### En local con Docker

```bash
# La app debe estar corriendo con docker compose up
docker exec lavanderia-web php artisan db:backup
```

### Restaurar un backup

```bash
# Conectar al PostgreSQL de Railway directamente
railway connect Postgres

# O con psql manual:
psql -h HOST -U USER -d DATABASE < storage/app/backups/backup_FECHA.sql
```

---

## ✅ Verificación rápida post-despliegue

```bash
# Ver rutas registradas
railway run php artisan route:list

# Ver estado de migraciones
railway run php artisan migrate:status

# Verificar que el sistema responde (debe retornar 200)
curl https://TU-URL.up.railway.app/up
```

---

## 🔧 Estructura de roles del sistema

| Rol | Módulo accesible |
|---|---|
| `usuario` | Producción personal (sin precios) |
| `recolector` | Módulo de facturas + crear clientes |
| `admin` | Panel completo (usuarios, prendas, clientes, reportes) |
| `programador` | Todo lo del admin + eliminar historial |

El primer usuario creado debe ser `admin` o `programador` para poder gestionar el sistema.
