# Guia de despliegue online - Lavanderia Exclusiva

Validada en este repo el 2026-04-29.

## Decision recomendada

Este proyecto es Laravel, no una pagina estatica. GitHub Pages no sirve para dejarlo funcionando como plataforma web con login, sesiones y base de datos, porque GitHub Pages no ejecuta PHP ni puede proteger credenciales PostgreSQL.

La ruta recomendada es:

- GitHub: repositorio del codigo.
- Railway: servidor online que construye el `Dockerfile` y ejecuta Laravel/Apache.
- Supabase: PostgreSQL permanente para que la base siga viva aunque se redespliegue la app.
- Docker: empaque de la aplicacion y entorno local. Docker no reemplaza a Supabase; se complementan.

## Estado de Supabase

La conexion de Supabase fue probada desde esta maquina con SSL y respondio correctamente.

Dato importante: en Supabase el texto `[YOUR-PASSWORD]` es un marcador. Los corchetes no deben quedar en la URL final.

Correcto:

```text
postgresql://postgres.TU-PROYECTO:TU_PASSWORD_URL_ENCODED@aws-1-us-west-2.pooler.supabase.com:5432/postgres?sslmode=require
```

Incorrecto:

```text
postgresql://postgres.TU-PROYECTO:[TU_PASSWORD_URL_ENCODED]@aws-1-us-west-2.pooler.supabase.com:5432/postgres?sslmode=require
```

Si la clave contiene `@`, en la URL debe escribirse como `%40`.

## 1. Subir a GitHub

```bash
git add .
git commit -m "chore: preparar despliegue laravel con supabase"
git push origin main
```

## 2. Crear el servicio en Railway

1. Entra a `https://railway.app`.
2. Inicia sesion con GitHub.
3. Crea un proyecto nuevo.
4. Elige `Deploy from GitHub repo`.
5. Selecciona este repositorio.

Railway detecta `railway.json` y construye la imagen desde `Dockerfile`.

## 3. Variables de Railway

Configura estas variables en el servicio web de Railway:

| Variable | Valor |
|---|---|
| `APP_NAME` | `Lavanderia Exclusiva` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | salida de `php artisan key:generate --show` |
| `APP_URL` | dominio publico de Railway |
| `APP_LOCALE` | `es` |
| `APP_FALLBACK_LOCALE` | `es` |
| `APP_FAKER_LOCALE` | `es_CO` |
| `DB_CONNECTION` | `pgsql` |
| `DATABASE_URL` | URL de Supabase sin corchetes y con `?sslmode=require` |
| `DB_SSLMODE` | `require` |
| `PGSSLMODE` | `require` |
| `DB_SCHEMA` | `public` |
| `SESSION_DRIVER` | `database` |
| `SESSION_SECURE_COOKIE` | `true` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `database` |
| `VIEW_COMPILED_PATH` | `storage/framework/views-runtime` |
| `TRUSTED_PROXIES` | `*` |

No subas `.env` a GitHub. Las variables reales van en Railway.

Para generar `APP_KEY` localmente:

```bash
php artisan key:generate --show
```

## 4. Primer despliegue

En cada arranque el contenedor ejecuta:

```bash
php artisan migrate --force
php artisan db:seed --force
```

Luego limpia cache, crea el enlace de `storage` si hace falta e inicia Apache.

## 5. Verificacion

Healthcheck de la app:

```bash
curl https://TU-DOMINIO.up.railway.app/up
```

Respuesta esperada:

```json
{"success":true}
```

Diagnostico de base de datos:

```bash
curl https://TU-DOMINIO.up.railway.app/up/database
```

Respuesta esperada:

```json
{"success":true,"database":"ok","connection":"pgsql"}
```

## 6. Docker local

Para probar en esta PC con PostgreSQL local:

```bash
docker compose up --build
```

Servicios:

- App: `http://localhost:8080`
- PostgreSQL 16 local: `localhost:5433`

Tambien puedes usar:

```powershell
.\start-web.ps1
```

## 7. Backups

En Railway:

```bash
php artisan db:backup
```

El archivo queda en `storage/app/backups/backup_YYYY-MM-DD_HHMMSS.sql`.

Para restaurar:

```bash
psql -h HOST -U USER -d DATABASE < backup.sql
```
