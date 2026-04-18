# Deployment Guide

## Importante

Este proyecto no puede publicarse en GitHub Pages porque Laravel necesita ejecutar PHP y conectarse a PostgreSQL. GitHub Pages solo publica sitios estaticos.

Documentacion oficial:

- GitHub Pages: https://docs.github.com/en/pages/getting-started-with-github-pages/what-is-github-pages
- Laravel deployment: https://laravel.com/docs/12.x/deployment

## Estado del repositorio

El remoto `origin` ya esta configurado en:

`https://github.com/CyWolf07/lavanderia-4.0.git`

## Archivos de despliegue incluidos

- `Dockerfile`
- `.dockerignore`
- `docker/start-container.sh`

## Variables de entorno necesarias

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=
APP_URL=

DB_CONNECTION=pgsql
DB_HOST=
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=
DB_PASSWORD=
DB_SCHEMA=public
DB_SSLMODE=require
```

## Opcion recomendada: Render

La forma mas conveniente para este proyecto es Render con despliegue Docker desde GitHub. Render soporta despliegue automatico desde commits y health checks, y el repo ya queda preparado con `render.yaml`.

Pasos:

1. Sube este proyecto a GitHub.
2. En Render, conecta tu cuenta de GitHub.
3. Crea un nuevo Blueprint o Web Service desde el repositorio.
4. Si usas Blueprint, Render leera `render.yaml`.
5. Completa los valores pendientes de `APP_KEY`, `APP_URL`, `DB_HOST`, `DB_USERNAME` y `DB_PASSWORD`.
6. Despliega el servicio.

El archivo `render.yaml` ya deja configurado:

- `runtime: docker`
- despliegue automatico desde `main`
- health check en `/up`
- migraciones con `php artisan migrate --force`

## Pasos para publicarlo

1. Haz commit y push del proyecto a GitHub.
2. Crea un servicio web en un hosting compatible con Docker.
3. Conecta el repositorio de GitHub.
4. Configura las variables de entorno.
5. Despliega usando el `Dockerfile` del repositorio.
6. Si hace falta, ejecuta `php artisan migrate --force` despues del primer despliegue.

## Nota sobre la base de datos

Como tus tablas ya estan migradas en Supabase, no necesitas volver a crear la estructura. Solo asegurate de cargar correctamente las credenciales en el entorno de produccion.
