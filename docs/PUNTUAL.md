# Puntual y Android

## Sincronizacion del repositorio

El 14/09/2026 se ejecuto `git fetch origin --prune` y se compararon los 255
archivos versionados con `origin/main` (af0cd63). No habia diferencias ni
submodulos pendientes. Las otras ramas remotas contienen variantes anteriores
de impresion y no sustituyen a main. Se reinstalaron las dependencias del lock
de npm y se verificaron los requisitos de PHP/Composer. No se reemplazaron
`.env`, bases de datos, archivos del usuario ni las carpetas locales no versionadas.

## Comportamiento

- Ruta `/puntual`, disponible para usuarios activos de todos los roles.
- Solo `admin` consulta todas las ordenes. Cada otro rol, incluido `programador`,
  queda limitado a las ordenes cuyo `recolector_id` coincide con su usuario.
- Se utiliza la fecha de entrega guardada en cada orden existente o nueva.
- Vistas: pendientes, hoy, manana, vencidas, entregadas y todas, con paginacion.
- Detalle: cliente, direccion, recoleccion, entrega, prendas, colores y lavado.
- La confirmacion de entrega guarda fecha y responsable. No modifica pagos,
  quincenas ni registros de lavanderos.
- Las ordenes antiguas no se marcan entregadas por su estado de pago: si no hay
  constancia de entrega, aparecen pendientes/vencidas hasta confirmarlas.
- Los recordatorios se generan el dia anterior y el dia de entrega, en
  `America/Bogota`. Una orden pagada sigue teniendo avisos hasta entregarse.
- Cancelar, entregar, cambiar fecha o cambiar recolector invalida el aviso antiguo.
- Las claves y permisos push se guardan en base de datos. La clave privada VAPID
  queda cifrada con APP_KEY, que debe mantenerse estable y respaldarse.
- Cerrar sesion elimina la suscripcion del dispositivo vinculada a esa sesion.

## Despliegue

El contenedor instala las extensiones PHP necesarias, ejecuta las migraciones,
prepara las claves push una sola vez e inicia `schedule:work` junto a Apache.
El envio corre cada cinco minutos entre las 08:00 y 20:00 de Colombia.
No requiere un servicio externo de notificaciones contratado: utiliza Web Push.

Fuera de Docker:

```sh
php artisan migrate --force
php artisan puntual:preparar
php artisan schedule:work
```

Tambien se puede ejecutar `php artisan schedule:run` cada minuto mediante cron,
en lugar de schedule:work. Para una comprobacion manual: `php artisan puntual:avisar`.

`APP_URL` debe ser `https://lavanderia-exclusiva-dzev.onrender.com`.
Las variables opcionales `PUNTUAL_VAPID_PUBLIC_KEY`, `PUNTUAL_VAPID_PRIVATE_KEY`
y `PUNTUAL_VAPID_SUBJECT` permiten aportar claves externas en lugar de las
generadas. No cambiar las claves con suscripciones activas.

El usuario debe pulsar Activar avisos y conceder permiso en cada dispositivo.
La entrega push depende de conectividad, permisos y restricciones del sistema.
La bandeja de Puntual sigue disponible aunque el navegador no admita Web Push.

Render Free suspende el servidor despues de 15 minutos sin trafico y el proceso
programado queda detenido. Para avisos continuos se necesita una instancia
siempre activa o ejecutar el programador en un servidor que no se suspenda.
Este cambio no contrata servicios ni modifica el plan de Render.
Fuente: https://render.com/docs/free

## APK

Se incluye `public/downloads/lavanderia-exclusiva.apk`, version 1.0.0, firmado
para instalacion directa en Android 6 o superior. Puntual tiene su enlace de descarga.
`PUNTUAL_APK_URL` permite cambiar la ubicacion de descarga.

El proyecto Android usa Trusted Web Activity y Android Browser Helper de Google.
Reutiliza la web y su autenticacion en un navegador compatible; no incorpora otra
base de datos ni un motor web duplicado. Requiere conexion. Las actualizaciones
de la web se reflejan sin reconstruir el APK.
El archivo publico `.well-known/assetlinks.json` asocia el dominio con la firma.
Hasta desplegar ese archivo, puede mostrarse la barra del navegador.
Fuente: https://developer.chrome.com/docs/android/trusted-web-activity/quick-start

Para reconstruir: JDK 17, Android SDK 35, Build Tools 35, Gradle 8.11.1.

```powershell
$env:JAVA_HOME = 'C:/Program Files/Java/jdk-17'
$env:ANDROID_HOME = 'C:/Users/Josue/AppData/Local/Android/Sdk'
./android/build-apk.ps1 -Gradle 'RUTA/gradle.bat'
```

La firma se conserva en `android/signing/puntual.jks`. Su contrasena local esta
protegida con DPAPI para el usuario Windows actual. Ambos archivos se excluyen de
Git y Docker. Respaldar esa carpeta de forma privada; nunca publicar la firma ni
su contrasena. Para trasladar la firma a otra maquina, exportar la contrasena de
forma segura desde la cuenta que la creo. Perder la firma impide actualizar el
APK instalado con la misma identidad. Al crear una firma nueva, actualizar
assetlinks y reinstalar. Incrementar versionCode y versionName en cada version.

La APK tiene compilacion release y firma verificadas; la interfaz web fue revisada
en navegador con tamanos de escritorio y movil. La recepcion real de push en un
telefono y la asociacion del dominio requieren comprobarse despues del despliegue.

## Pruebas

`tests/Feature/PuntualTest.php` cubre acceso por rol, aislamiento de ordenes y
acciones, dias de aviso, medianoche UTC/Colombia, deduplicacion, cancelacion,
reasignacion, cambio de fecha, entrega sin alterar pago, suscripciones, cierre
de sesion, envios y reintentos. La bateria anterior comprueba pagos, produccion,
impresion y recolectores.
