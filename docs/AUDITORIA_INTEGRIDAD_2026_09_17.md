# Revision de integridad y seguridad

## Alcance

Revision estatica de modelos, relaciones, claves foraneas de migraciones,
rutas, permisos, controladores y servicios principales. Pruebas locales con
SQLite y auditoria de dependencias. No se modificaron registros de produccion
durante la revision ni se repararon datos historicos por inferencia.

## Correcciones

| Area | Fallo | Correccion |
| --- | --- | --- |
| Middleware administrativo | Capturaba errores del controlador y ejecutaba nuevamente la peticion | El controlador se ejecuta una sola vez; solo el limitador tiene fallback |
| Cliente / factura | Eliminar cliente eliminaba facturas en cascada | Se impide eliminar clientes con facturas; deben inhabilitarse |
| Usuario / produccion / historial / gastos / pagos | Borrado de cuenta eliminaba datos financieros relacionados | Proteccion en el modelo para borrado desde perfil y administracion |
| Prenda / produccion | Borrar prenda podia borrar trabajo registrado | Se rechaza el borrado mientras tenga produccion activa |
| Cliente / recolector | Se podia delegar a usuarios de otro rol o inactivos | Validacion de rol efectivo y estado activo |
| Orden / detalles | Filas seleccionadas sin prenda podian generar orden vacia | Rechazo de filas invalidas antes de guardar |
| Edicion de orden | Reemplazaba todos los detalles, perdiendo colores, IDs y lavado | Actualizacion por prenda conservando identidad y seguimiento |
| Edicion de orden lavada | Permitiria quitar o cambiar cantidades ya contabilizadas | Rechazo transaccional, sin escrituras parciales |
| Fecha de entrega | Omitir fecha al editar intentaba guardar NULL en campo obligatorio | Se conserva la fecha anterior |
| Pago / comision | Borrar factura pagada dejaba comision desactualizada | Recalculo y registro correcto del estado en auditoria |
| Concurrencia | Lecturas previas a transaccion permitian decisiones con estado viejo | Bloqueos y relectura en pago, lavado, edicion y cierre |
| Numeracion | Nuevos bloques ignoraban facturas historicas y competian entre procesos | Bloqueo global de asignacion y limite basado en bloques y facturas |
| WhatsApp | Se enviaba el ID interno en lugar del numero de orden | Mensaje usa numero_orden |
| PQRS / autor | Todos los usuarios veian todas las solicitudes | Relacion user_id; usuarios ven propias y roles administrativos todas |
| Errores de integridad | Algunos bloqueos no tenian mensaje visible | Avisos en clientes, dashboard y eliminacion de perfil |
| Dependencias | Auditorias detectaron paquetes vulnerables | Actualizaciones compatibles y lockfiles actualizados |

## Entidades revisadas

- Identidad: User, Rol, EnterpriseAccessControl, SystemSetting.
- Catalogos: Cliente, Prenda, RecolectorPrenda, PrendaEquivalencia.
- Trabajo: FacturaRecolector, FacturaRecolectorDetalle, Produccion, HistorialProduccion.
- Finanzas: Gasto, PagoRecolector, BloqueNumeroOrden.
- Control: AuditEvent, IncongruenciaProduccion, IncongruenciaRecolector.
- Comunicaciones: Pqrs, PuntualRecordatorio, suscripciones de Puntual.

No se cambiaron las reglas de pago de lavanderos, el porcentaje de recolectores,
ni la asignacion de fechas a quincenas. No se renumeraron facturas existentes.

## Migracion

`2026_09_18_042746_add_user_id_to_pqrs_table.php` agrega autor nullable e indice.
El nombre corresponde al reloj UTC del generador. Las PQRS antiguas permanecen
sin autor y solo son visibles para administracion. No se asignan por correo:
ese campo historicamente era editable y no demuestra autoria.
Debe aplicarse con el mecanismo de migraciones del despliegue antes de usar PQRS.

## Validacion y limites

- Suite PHP completa: 129 pruebas aprobadas, 609 aserciones, incluyendo
  pruebas nuevas de integridad, permisos y middleware.
- JavaScript: 7 pruebas aprobadas de contexto APK y reutilizacion de ventanas.
- Compilacion de recursos con Vite completada correctamente.
- Composer y npm: cero vulnerabilidades reportadas en las auditorias finales;
  esto no sustituye la revision del codigo ni garantiza ausencia de vulnerabilidades.
- composer.json validado y git diff --check sin errores.
- SQLite no prueba contencion real de filas PostgreSQL: los escenarios simultaneos
  requieren prueba de carga sobre una base PostgreSQL aislada antes de afirmar
  cobertura completa de concurrencia.
- Los bloqueos de borrado son del modelo Eloquent. SQL directo o borrado masivo
  que omita eventos puede seguir ejecutando cascadas del esquema original.
- No se verificaron las politicas RLS, privilegios Data API, backups restaurables,
  logs ni datos reales de Supabase. Requieren acceso y una revision operativa separada.
- No se ejecutaron pruebas fisicas Android ni una revision visual completa.
- El listado del recolector y algunas pantallas administrativas cargan historial
  completo: conviene medir volumen real antes de introducir paginacion y cambiar
  los contratos de sus modales.

Esta revision corrige fallos concretos; no certifica ausencia total de errores.
