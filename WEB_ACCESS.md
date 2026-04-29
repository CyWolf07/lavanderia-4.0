# Acceso web del proyecto

## Opcion rapida en esta PC

Desde `E:\Lavanderia_Registro`, ejecuta:

```powershell
.\start-web.ps1
```

Ese script:

- levanta Laravel y PostgreSQL 16 con Docker
- espera el healthcheck `/up`
- deja la app lista en navegador
- muestra la URL local
- muestra la URL de red para abrirla desde otro dispositivo

## Abrir desde otro dispositivo en la misma red

1. Conecta el otro equipo a la misma red.
2. Ejecuta `.\start-web.ps1` en esta PC.
3. Abre la `URL en red` que muestra el script.

Ejemplo:

```text
http://192.168.1.25:8080
```

## Credenciales iniciales

- Admin: `admin@lavanderia.com` / `admin123`
- Programador: `programador@lavanderia.com` / `programador123`
- Usuario: `usuario@lavanderia.com` / `usuario123`
- Recolector: `recolector@lavanderia.com` / `recolector123`

## Base de datos local

- Host: `localhost`
- Puerto: `5433`
- Base de datos: `lavanderia`
- Usuario: `lavanderia`
- Contrasena: `lavanderia`

## Comandos utiles

```powershell
docker compose ps
docker compose logs -f web
docker compose logs -f db
docker compose down
```

## Publicacion en internet

La opcion recomendada para este repo es Railway + Supabase.

GitHub por si solo no hospeda esta app como plataforma web, porque Laravel necesita PHP, Apache y variables privadas. El flujo correcto es subir el repo a GitHub y conectar Railway al repo para que Railway ejecute el contenedor.

Archivos usados:

- `Dockerfile`
- `railway.json`
- `DEPLOYMENT.md`
- `.env.supabase.example`

Supabase queda como base PostgreSQL permanente. Docker se usa para empaquetar y correr la app; no reemplaza a Supabase.

Si vas a publicar, sigue `DEPLOYMENT.md`, configura las variables de Railway y luego revisa:

- `https://TU-DOMINIO.up.railway.app/up`
- `https://TU-DOMINIO.up.railway.app/up/database`
