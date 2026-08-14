# Informe ejecutivo - Lavanderia Exclusiva

## 1. Resumen general

Lavanderia Exclusiva es una aplicacion web para administrar la operacion diaria de una lavanderia. El sistema organiza usuarios, clientes, recolecciones, prendas, produccion de lavado, gastos, pagos por quincena, reportes e incidencias.

En palabras sencillas: el programa permite saber que ropa entro, quien la recolecto, quien la lavo, cuanto vale, cuanto se debe pagar, que gastos hubo y que informacion debe revisar el administrador.

La aplicacion funciona en linea mediante Render y guarda la informacion en una base de datos PostgreSQL, recomendada y preparada para Supabase. El codigo fuente esta en GitHub.

## 2. Para que sirve el programa

El objetivo principal es controlar el flujo completo de la lavanderia:

- Registrar clientes y sus datos de contacto.
- Registrar ordenes o facturas creadas por recolectores.
- Registrar prendas, cantidades, colores y valores.
- Registrar produccion diaria de lavanderos.
- Validar que la produccion lavada coincida con lo recibido.
- Detectar diferencias o incongruencias.
- Manejar gastos de la quincena.
- Calcular pagos y comisiones.
- Generar reportes administrativos.
- Controlar usuarios, roles y accesos.

## 3. Usuarios del sistema

El sistema trabaja con cuatro roles principales.

| Rol | Que puede hacer |
|---|---|
| Usuario o lavandero | Registra su produccion diaria de prendas lavadas. |
| Recolector | Registra clientes, ordenes, prendas recibidas, colores, valores y gastos propios. |
| Admin | Administra usuarios, clientes, prendas, facturas, produccion, pagos, reportes e incongruencias. |
| Programador | Tiene acceso administrativo y funciones tecnicas especiales, como ver y regenerar el codigo empresarial. |

## 4. Modulos principales

### Panel administrativo

Es el centro de control. Desde aqui se revisan totales, usuarios, clientes, produccion, facturas de recolectores, gastos, pagos, incongruencias y reportes por quincena.

El administrador puede:

- Crear, editar, activar o desactivar usuarios.
- Definir si un recolector puede editar precios.
- Crear y administrar prendas.
- Administrar clientes.
- Revisar produccion activa.
- Editar o eliminar registros cuando sea necesario.
- Ver ingresos, pagos, gastos y ganancia estimada.
- Revisar incongruencias de recoleccion o produccion.
- Cerrar periodos de produccion.

### Modulo recolector

El recolector registra la ropa que recibe del cliente. Cada orden incluye:

- Cliente.
- Numero de orden.
- Fecha de ingreso.
- Fecha de entrega.
- Prendas recibidas.
- Cantidad.
- Color de cada prenda.
- Valor unitario.
- Total de la orden.
- Estado de la factura: pendiente, pagada o cancelada.

El sistema calcula automaticamente el total y permite llevar el control de la comision del recolector, que esta configurada en el 30% de las facturas pagadas en la quincena.

### Modulo produccion

Lo usa el lavandero para registrar lo que realmente lavo. Hay dos modos de trabajo:

- Modo basico: el lavandero registra manualmente las prendas lavadas por dia.
- Modo avanzado: el lavandero trabaja desde ordenes recibidas por recolectores.

El administrador puede cambiar el modo desde el panel.

### Validacion de produccion

Una mejora importante del sistema es que no solo guarda lo que el lavandero reporta, sino que tambien valida si coincide con la ropa recibida.

Ejemplo:

- Si ingresaron 10 camisas y el lavandero reporta 10, queda validado.
- Si reporta 12, el sistema detecta sobrante.
- Si reporta 7, el sistema detecta faltante.

Las diferencias se guardan como incongruencias para que el administrador las revise. Si el administrador aprueba una diferencia, el sistema permite pagar esa produccion.

### Clientes

El sistema guarda clientes con:

- Nombre.
- Numero interno de cliente.
- Celular.
- Direccion.
- Barrio.
- Recolector asignado.
- Estado activo/inactivo.
- Coordenadas para mapa de clientes.

### PQRS

PQRS significa Peticiones, Quejas, Reclamos y Sugerencias. Es un modulo para registrar solicitudes o novedades que deban ser atendidas.

### Reportes y quincenas

La aplicacion trabaja por quincenas:

- Primera quincena: dia 1 al 15.
- Segunda quincena: dia 16 al final del mes.

Los reportes agrupan produccion, facturas, gastos, pagos y comisiones segun la quincena correspondiente.

Una regla importante es que una factura puede haberse creado en una quincena, pero pagarse en otra. El sistema conserva:

- Quincena de origen: cuando se creo la factura.
- Quincena de pago: cuando realmente se marco como pagada.

Esto ayuda a que el dinero quede registrado en la quincena correcta.

## 5. Seguridad y acceso

El programa tiene varias capas de seguridad:

- Inicio de sesion con correo, cedula o credenciales registradas.
- Usuarios activos o inactivos.
- Roles con permisos diferentes.
- Codigo empresarial antes del login.
- Bloqueo de dispositivo tras varios intentos fallidos.
- Registro publico cerrado cuando ya existe el primer usuario.
- Variables privadas fuera de GitHub.
- Conexion segura a PostgreSQL/Supabase con SSL en produccion.

## 6. Como funciona en linea

La aplicacion se publica asi:

| Componente | Funcion |
|---|---|
| GitHub | Guarda el codigo fuente. |
| Render | Ejecuta la aplicacion web en internet. |
| Docker | Empaqueta PHP, Apache, Laravel y los archivos necesarios. |
| Supabase/PostgreSQL | Guarda los datos permanentes. |

Cuando se suben cambios a GitHub, Render los detecta y despliega automaticamente porque el proyecto tiene `autoDeploy: true`.

Al arrancar, el contenedor ejecuta migraciones de base de datos. Eso permite crear o actualizar tablas necesarias para que el sistema funcione.

## 7. Base de datos, explicado sin tecnicismos

La base de datos es donde vive la informacion del negocio. Entre sus datos principales estan:

- Usuarios y roles.
- Clientes.
- Prendas de lavanderia.
- Prendas del catalogo de recolector.
- Facturas de recolector.
- Detalles de cada factura.
- Produccion de lavanderos.
- Historial de produccion cerrada.
- Gastos.
- Pagos de recolectores.
- Incongruencias.
- Configuraciones internas.
- Sesiones y seguridad.

PostgreSQL es el motor de base de datos. Supabase es una plataforma que ofrece PostgreSQL en la nube, con acceso permanente y seguro.

## 8. Lenguajes y tecnologias usadas

| Tecnologia | Uso |
|---|---|
| PHP 8.2 | Lenguaje principal del servidor. |
| Laravel 12 | Framework que organiza rutas, controladores, modelos, seguridad y base de datos. |
| Blade | Motor de vistas para las pantallas. |
| Tailwind CSS | Estilos visuales de la interfaz. |
| JavaScript / Vite | Compilacion de recursos frontend. |
| PostgreSQL | Base de datos principal. |
| Docker | Contenedor para ejecutar la aplicacion de forma estable. |
| Render | Hosting de la app en linea. |
| GitHub | Control de versiones y fuente de despliegue. |

## 9. Estado de validacion

Antes del ultimo despliegue se verifico:

- Pruebas automatizadas de Laravel: 85 pruebas pasadas.
- Build de frontend con Vite: correcto.
- Cache de configuracion, rutas y vistas: correcto.
- Healthcheck publico `/up`: correcto.
- Diagnostico de base de datos `/up/database`: correcto con conexion `pgsql`.

## 10. Idea central para presentar

Lavanderia Exclusiva no es solo una pagina web. Es un sistema operativo interno para la lavanderia. Controla la entrada de ropa, el trabajo de recolectores, el trabajo de lavanderos, los pagos, los gastos, los reportes y las diferencias que deben revisarse. Esta construido con Laravel, publicado en Render y conectado a PostgreSQL/Supabase para conservar la informacion del negocio.

