# Documentación de INEES Mantenimientos

Documentación técnica de la plataforma en Spring Boot + Angular: cómo está construida, cómo correrla, cómo hacer cada tipo de cambio y cómo desplegarla. Está escrita para los desarrolladores que mantienen y amplían el sistema.

## Por dónde empezar

| Si usted quiere… | Lea |
| --- | --- |
| Entender qué es la plataforma y cómo está armada | [01 · Visión general](01-vision-general.md) |
| Correrla en su equipo por primera vez | [02 · Instalación y ejecución local](02-instalacion-y-ejecucion-local.md) |
| Hacer un cambio (página, campo, reporte, permiso...) | [10 · Manual de cambios](10-manual-de-cambios.md) |
| Encontrar dónde está una pantalla o un endpoint | [09 · Módulos](09-modulos.md) y [07 · Endpoints](07-endpoints.md) |
| Ponerla en el servidor | [12 · Despliegue](12-despliegue.md) |
| Resolver un error | [13 · Pruebas y solución de problemas](13-pruebas-y-solucion-de-problemas.md) |

**Inicio rápido:** con Java 21, Node.js 20 y MySQL/MariaDB instalados, doble clic en `nueva-plataforma\iniciar-local.bat` y se abre http://localhost:8080. Para desarrollar pantallas con recarga automática: `iniciar-local.bat dev`. Para detener: `detener-local.bat`.

## Contenido

| # | Documento | Contenido |
| --- | --- | --- |
| 01 | [Visión general](01-vision-general.md) | Qué hace la plataforma, arquitectura, tecnologías, estructura de carpetas, glosario |
| 02 | [Instalación y ejecución local](02-instalacion-y-ejecucion-local.md) | Requisitos, base de datos, primer usuario, `iniciar-local.bat` y sus modos, flujo de desarrollo |
| 03 | [Configuración](03-configuracion.md) | Todas las variables de entorno (BD, archivos, correo, servicios externos, tareas programadas) |
| 04 | [Backend](04-backend.md) | Paquetes, ciclo de una petición, convenciones, referencia de `core/` con ejemplos (BD, parámetros, archivos, Excel, PDF, correo) |
| 05 | [Frontend](05-frontend.md) | Estructura Angular, rutas, servicio `Api`, sesión, componentes compartidos, estilos, librerías |
| 06 | [Base de datos](06-base-de-datos.md) | Diagramas entidad-relación, diccionario de las 42 tablas, estados y convenciones *(generado)* |
| 07 | [Endpoints](07-endpoints.md) | Los 344 endpoints por módulo y controlador *(generado)* |
| 08 | [Permisos, roles y sesión](08-permisos-y-roles.md) | Cómo funciona el control de acceso, inicio de sesión, matriz página × rol *(matriz generada)* |
| 09 | [Módulos](09-modulos.md) | Qué hace cada módulo, sus páginas, archivos y tablas |
| 10 | [Manual de cambios](10-manual-de-cambios.md) | 17 recetas paso a paso con código: página nueva, acción, columna, catálogo, Excel, PDF, fotos, correo, importación, tarea programada, menú, permisos, configuración, librerías |
| 11 | [Cómo se construyó](11-como-se-construyo.md) | La migración desde PHP: decisiones, equivalencias, método, errores corregidos, verificación y estado por módulo |
| 12 | [Despliegue](12-despliegue.md) | Generar el `.jar`, servidor, servicio de Windows, convivencia con PHP, actualizaciones y respaldos |
| 13 | [Pruebas y solución de problemas](13-pruebas-y-solucion-de-problemas.md) | Prueba automática, curl, cómo probar un cambio, tabla de errores frecuentes |

## Herramientas (`herramientas/`)

| Script | Qué hace |
| --- | --- |
| `generar-endpoints.ps1` | Regenera [07-endpoints.md](07-endpoints.md) desde los controladores |
| `generar-diccionario-bd.ps1` | Regenera [06-base-de-datos.md](06-base-de-datos.md) desde la base (texto fijo en `bd-convenciones.md`) |
| `generar-permisos.ps1` | Regenera [08-permisos-y-roles.md](08-permisos-y-roles.md) desde `rutas` y `rol_permisos` (texto fijo en `permisos-intro.md`) |
| `probar-plataforma.ps1` | Prueba automática de todas las páginas de un usuario |

Se ejecutan desde `nueva-plataforma` con `powershell -ExecutionPolicy Bypass -File documentacion\herramientas\<script>.ps1`.

## Archivos de la raíz de `nueva-plataforma`

| Archivo | Para qué |
| --- | --- |
| `iniciar-local.bat` | Arranca base de datos, backend y (opcional) frontend en modo desarrollo |
| `detener-local.bat` | Detiene backend y `ng serve` (con `todo`, también la base de datos) |
| `config-local.ejemplo.bat` | Plantilla de configuración local; copiar como `config-local.bat` |
| `scripts/_backend.bat`, `scripts/_frontend.bat` | Ventanas que abre `iniciar-local.bat` |
| `README.md` | Presentación corta del proyecto |
