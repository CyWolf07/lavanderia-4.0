# Guia de despliegue online - Lavanderia Exclusiva

## Decision recomendada

Este proyecto es Laravel, no una pagina estatica. Para funcionar necesita PHP, Apache, variables privadas, sesiones y PostgreSQL.

La via clara y portable es:

- GitHub: repositorio del codigo.
- Docker: imagen de la aplicacion Laravel/Apache.
- Supabase: PostgreSQL permanente.
- Render Free: hosting Docker de la aplicacion.

GitHub Pages no sirve para esta aplicacion.

## 1. Base de datos en Supabase

Crea una base PostgreSQL en Supabase y usa la URL de conexion con SSL.

Correcto:

```text
postgresql://postgres.TU-PROYECTO:TU_PASSWORD_URL_ENCODED@aws-1-us-west-2.pooler.supabase.com:5432/postgres?sslmode=require
```

Incorrecto:

```text
postgresql://postgres.TU-PROYECTO:[TU_PASSWORD_URL_ENCODED]@aws-1-us-west-2.pooler.supabase.com:5432/postgres?sslmode=require
```

Si la clave contiene `@`, en la URL debe escribirse como `%40`.

## 2. Variables de produccion

Configura estas variables en el hosting que ejecute Docker:

| Variable | Valor |
|---|---|
| `APP_NAME` | `Lavanderia Exclusiva` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | salida de `php artisan key:generate --show` |
| `APP_URL` | dominio publico del hosting |
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

No subas `.env` a GitHub. Las variables reales van en el panel del hosting.

Para generar `APP_KEY`:

```bash
php artisan key:generate --show
```

## 3. Despliegue en Render Free

El repo incluye `render.yaml`. Render lo usa como Blueprint para crear un Web Service con:

- `runtime: docker`
- `plan: free`
- `dockerfilePath: ./Dockerfile`
- `healthCheckPath: /up`

Pasos:

1. Sube este repo a GitHub.
2. En Render, crea un nuevo Blueprint o Web Service desde el repositorio.
3. Si usas Blueprint, Render leera `render.yaml`.
4. Completa las variables marcadas como privadas:
   - `APP_KEY`
   - `APP_URL`
   - `DATABASE_URL`
5. Despliega.

El contenedor arranca con:

```bash
start-container
```

En cada arranque ejecuta:

```bash
php artisan migrate --force
php artisan db:seed --force
```

Luego limpia cache, crea el enlace de `storage` si hace falta e inicia Apache.

## 4. Verificacion

Healthcheck de la app:

```bash
curl https://TU-DOMINIO/up
```

Respuesta esperada:

```json
{"success":true}
```

Diagnostico de base de datos:

```bash
curl https://TU-DOMINIO/up/database
```

Respuesta esperada:

```json
{"success":true,"database":"ok","connection":"pgsql"}
```

## 5. Docker local

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

## 6. Proveedores gratuitos sugeridos

La decision actual es Render Free + Supabase.

Ten presente que Render Free puede dormir el servicio tras inactividad. Cuando alguien entra de nuevo, el primer acceso puede tardar mas mientras Render despierta el contenedor.

## 7. Backups

Dentro del contenedor:

```bash
php artisan db:backup
```

El archivo queda en `storage/app/backups/backup_YYYY-MM-DD_HHMMSS.sql`.

Para restaurar:

```bash
psql -h HOST -U USER -d DATABASE < backup.sql
```
