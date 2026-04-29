# Lavanderia Exclusiva

Sistema de gestion de lavanderia construido con Laravel y PostgreSQL.

## Stack real del proyecto

- PHP 8.2
- Laravel 12
- PostgreSQL
- Blade + Vite + Tailwind
- Docker para entorno local
- Railway para despliegue recomendado
- Supabase PostgreSQL para base online permanente

## Modulos principales

- Produccion de prendas
- PQRS
- Panel administrativo
- Modulo recolector con clientes y facturas
- Usuarios y roles
- Historial de produccion

## Roles del sistema

| Rol | Acceso |
|---|---|
| `usuario` | Produccion personal |
| `recolector` | Facturas y clientes |
| `admin` | Panel completo |
| `programador` | Todo lo de admin y acciones tecnicas |

## Ejecucion local con Docker

```bash
docker compose up --build -d
```

Accesos:

- App: `http://localhost:8080`
- PostgreSQL 16: `localhost:5433`

Usuarios sembrados por defecto:

- `admin@lavanderia.com` / `admin123`
- `programador@lavanderia.com` / `programador123`
- `usuario@lavanderia.com` / `usuario123`
- `recolector@lavanderia.com` / `recolector123`

## Archivos clave de despliegue

- `Dockerfile`: imagen de produccion y utilidades de PostgreSQL 16
- `docker-compose.yml`: stack local con app + PostgreSQL 16
- `docker/start-container.sh`: arranque del contenedor, migraciones, seed y healthcheck
- `railway.json`: build y healthcheck para Railway
- `DEPLOYMENT.md`: guia validada de despliegue
- `WEB_ACCESS.md`: acceso local por red y publicacion
- `.env.supabase.example`: base de variables para conectar Supabase en produccion

## Variables importantes

La app esta preparada para leer:

- `DATABASE_URL`
- variables `PG*`
- variables `DB_*`

La conexion por defecto en este proyecto es PostgreSQL.

## Pruebas

```bash
php artisan test
```

El suite actual valida, entre otras cosas:

- healthcheck `/up`
- compatibilidad de `DATABASE_URL` y variables `PG*`
- que Docker local siga fijado a PostgreSQL 16

## Despliegue online

La ruta recomendada es subir el codigo a GitHub y conectar ese repo a Railway. Railway ejecuta Laravel con Docker/Apache, mientras Supabase mantiene PostgreSQL activo fuera del contenedor.

GitHub Pages no es suficiente para esta app porque no ejecuta PHP ni protege credenciales de base de datos.

Consulta:

- `DEPLOYMENT.md`
- `.env.supabase.example`
