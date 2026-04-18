# Lavanderia como pagina web

## Opcion rapida en esta PC

Desde `E:\Lavanderia_Registro`, ejecuta:

```powershell
.\start-web.ps1
```

Ese comando:

- levanta Laravel y PostgreSQL con Docker
- ejecuta migraciones y seeders
- deja la app lista en navegador
- muestra la URL para `localhost`
- muestra la URL de red para abrirla desde otro dispositivo

## Abrir desde otro dispositivo en la misma red

1. Conecta el celular, tablet o PC a la misma red Wi-Fi o LAN.
2. Ejecuta `.\start-web.ps1` en el equipo donde esta el proyecto.
3. Abre en el otro dispositivo la `URL en red` que muestra el script.

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

## Publicarlo en internet

Si quieres entrar desde cualquier red, no solo desde tu Wi-Fi, publica el proyecto en un hosting con Docker o PHP. Este repo ya incluye:

- `Dockerfile`
- `render.yaml`
- `DEPLOYMENT.md`

La opcion mas directa es subirlo a GitHub y desplegarlo en Render con PostgreSQL externo o Supabase.
