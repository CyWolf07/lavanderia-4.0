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

DB_CONNECTION=pgsql
DB_URL=
DB_SCHEMA=public
DB_SSLMODE=prefer
```

## Opcion recomendada: Render

La forma mas conveniente para este proyecto es Render con despliegue Docker desde GitHub. Render soporta despliegue automatico desde commits y health checks, y el repo ya queda preparado con `render.yaml`.

Pasos:

1. Sube este proyecto a GitHub.
2. En Render, conecta tu cuenta de GitHub.
3. Crea un nuevo Blueprint o Web Service desde el repositorio.
4. Si usas Blueprint, Render leera `render.yaml`.
5. Si usas el `render.yaml` actualizado, Render crea la base de datos y conecta `DB_URL` automaticamente.
6. Despliega el servicio.

El archivo `render.yaml` ya deja configurado:

- `runtime: docker`
- despliegue automatico desde `main`
- health check en `/up`
- base de datos Render Postgres administrada
- `APP_KEY` generado automaticamente
- conexion privada interna entre app y base de datos

## Pasos para publicarlo

1. Haz commit y push del proyecto a GitHub.
2. En Render, crea un nuevo Blueprint desde el repositorio.
3. Revisa que el plan del servicio web y la base de datos te parezcan correctos.
4. Confirma la creacion del servicio `lavanderia-web` y la base `lavanderia-db`.
5. Espera el primer despliegue. El contenedor ejecuta migraciones y seeders automaticamente al iniciar.
6. Abre la URL publica `onrender.com` que Render asigne al servicio.

## Nota sobre la base de datos

La configuracion actual esta preparada para usar Render Postgres como base principal en produccion. Si prefieres mantener una base externa como Supabase, puedes reemplazar `DB_URL` en Render por la URL externa de esa base.
