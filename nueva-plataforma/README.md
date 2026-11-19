# INEES Mantenimientos — Spring Boot + Angular

Plataforma de programación, ejecución, control y facturación de mantenimientos de INEES, en **Spring Boot 4 (Java 21) + Angular 22**. Reemplaza a la versión PHP y usa **la misma base de datos MariaDB/MySQL `inees_mantenimientos`** y las mismas carpetas de archivos, sin cambios de esquema.

## Arrancar en local

Requisitos: Java 21, Node.js 20+, MySQL/MariaDB con la base `inees_mantenimientos`.

```bat
iniciar-local.bat          REM base de datos + backend → http://localhost:8080
iniciar-local.bat dev      REM además ng serve con recarga automática → http://localhost:4200
iniciar-local.bat build    REM recompila el frontend antes de arrancar
detener-local.bat          REM detiene todo (con "todo" también apaga la base)
```

Configuración local opcional: copiar `config-local.ejemplo.bat` como `config-local.bat`.

## Documentación

Toda la documentación está en [documentacion/](documentacion/README.md):

- [Instalación y ejecución local](documentacion/02-instalacion-y-ejecucion-local.md)
- [Manual de cambios](documentacion/10-manual-de-cambios.md) (cómo hacer cada tipo de cambio)
- [Backend](documentacion/04-backend.md) · [Frontend](documentacion/05-frontend.md) · [Base de datos](documentacion/06-base-de-datos.md) · [Endpoints](documentacion/07-endpoints.md)
- [Cómo se construyó](documentacion/11-como-se-construyo.md) (migración desde PHP)
- [Despliegue](documentacion/12-despliegue.md) · [Solución de problemas](documentacion/13-pruebas-y-solucion-de-problemas.md)

## Estructura

```text
nueva-plataforma/
├── backend/          Spring Boot (API, PDF, Excel, correo, tareas programadas)
├── frontend/         Angular (se compila dentro de backend/src/main/resources/static)
├── documentacion/    Manuales y referencia
├── scripts/          Ventanas auxiliares de iniciar-local.bat
├── iniciar-local.bat, detener-local.bat, config-local.ejemplo.bat
```
