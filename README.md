# Lavanderia Exclusiva

Sistema de gestion de lavanderia construido con Laravel y PostgreSQL.

## Stack real del proyecto

- PHP 8.2
- Laravel 12
- PostgreSQL
- Blade + Vite + Tailwind
- Docker para entorno local y despliegue
- Supabase PostgreSQL recomendado como base online permanente
- Render Free como hosting Docker elegido

## Modulos activos

- Produccion de prendas
- PQRS
- Panel administrativo
- Modulo recolector con clientes y facturas
- Usuarios y roles
- Historial de produccion
- Seguridad por codigo empresarial y bloqueo de dispositivo

## Roles del sistema

| Rol | Acceso |
|---|---|
| `usuario` | Produccion personal |
| `recolector` | Facturas y clientes |
| `admin` | Panel completo |
| `programador` | Todo lo de admin y acciones tecnicas |

## Via recomendada

La ruta limpia del proyecto es:

1. Docker empaqueta y ejecuta Laravel/Apache.
2. Supabase mantiene PostgreSQL fuera del contenedor.
3. Render Free ejecuta la imagen Docker.

Docker no reemplaza a Supabase: Docker corre la app; Supabase guarda los datos.

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

## Archivos clave

- `Dockerfile`: imagen de produccion.
- `docker-compose.yml`: stack local con app + PostgreSQL 16.
- `docker/start-container.sh`: arranque del contenedor, migraciones, seed y Apache.
- `render.yaml`: Blueprint de Render Free para construir con Docker.
- `DEPLOYMENT.md`: guia de despliegue con Docker + Supabase.
- `WEB_ACCESS.md`: acceso local, red local y publicacion.
- `.env.supabase.example`: variables base para conectar Supabase.

## Variables importantes

La app esta preparada para leer:

- `DATABASE_URL`
- `DB_URL`
- variables `PG*`
- variables `DB_*`

La conexion por defecto cambia a PostgreSQL cuando detecta `DATABASE_URL`, `DB_URL` o `PGHOST`.

## Pruebas

```bash
php artisan test
```

La suite valida, entre otras cosas:

- healthcheck `/up`
- diagnostico `/up/database`
- compatibilidad con `DATABASE_URL` y variables `PG*`
- que Docker local siga fijado a PostgreSQL 16

## Limpieza realizada

Se eliminaron piezas sin flujo activo en Laravel:

- `legacy_python`: version anterior Flask/Python, no usada por Laravel.
- modulo `mensajes`: controlador, modelo, vistas y migracion sin rutas activas.
- `railway.json`: configuracion especifica de Railway. Se reemplazo por `render.yaml`.

GitHub Pages no sirve para esta app porque no ejecuta PHP ni protege credenciales de base de datos.
