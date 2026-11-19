# 12. Despliegue en producción

En producción la plataforma es **un solo archivo** `inees-mantenimientos.jar` que contiene el backend y el frontend compilado. Se ejecuta con Java 21 y necesita acceso a la base de datos y a la carpeta de archivos del PHP.

## 1. Generar el `.jar`

En el equipo de desarrollo:

```bat
cd nueva-plataforma\frontend
npm ci
npx ng build
cd ..\backend
mvn clean package -DskipTests
```

Resultado: `backend\target\inees-mantenimientos.jar` (incluye `static/` con Angular). El orden importa: primero Angular, después Maven; si se empaqueta antes, el `.jar` lleva el frontend anterior.

## 2. Preparar el servidor

| Necesita | Detalle |
| --- | --- |
| Java 21 (JRE o JDK) | Temurin 21; comprobar con `java -version` |
| Acceso a MariaDB/MySQL | La misma base `inees_mantenimientos` que usa el PHP |
| Carpeta de archivos | La raíz del PHP (donde están `uploads/`, `app/uploads/`, `imagenes/`), accesible con permisos de lectura **y escritura** para la cuenta que ejecuta Java |
| Google Chrome | Para los PDF; o definir `CHROME_PATH` |
| Puerto | 8080 por defecto (o el que se defina en `PORT`), abierto en el firewall si se accede desde otros equipos |

Estructura sugerida:

```text
C:\inees\
├── inees-mantenimientos.jar
├── inees-mantenimientos.anterior.jar   ← versión previa, para volver atrás
├── iniciar.bat                         ← variables + java -jar
└── logs\
```

`C:\inees\iniciar.bat`:

```bat
@echo off
set "DB_HOST=localhost"
set "DB_USER=inees_app"
set "DB_PASS=********"
set "LEGACY_ROOT=C:\xampp\htdocs\ProyectoMantenimientos"
set "SMTP_PASS=********"
set "TELEGRAM_BOT_TOKEN=********"
set "TELEGRAM_CHAT_ID=********"
set "GOOGLE_MAPS_KEY=********"
set "CRON_ALERTAS=true"
set "CRON_TELEGRAM=true"
set "LOGGING_FILE_NAME=C:\inees\logs\inees.log"
cd /d C:\inees
java -Xms256m -Xmx1g -jar inees-mantenimientos.jar
```

Todas las variables están en [03-configuracion.md](03-configuracion.md). Proteja este archivo: tiene contraseñas (permisos de lectura solo para administradores).

> **Usuario de base de datos.** Conviene un usuario propio (`inees_app`) con permisos `SELECT, INSERT, UPDATE, DELETE` sobre `inees_mantenimientos`, en lugar de `root`. Solo los cambios de estructura (receta 3 y 4 del manual) se aplican con un usuario administrador.

## 3. Ejecutarlo como servicio de Windows

Para que arranque solo con el servidor y se reinicie si falla, regístrelo como servicio con una herramienta como [NSSM](https://nssm.cc) o [WinSW](https://github.com/winsw/winsw). Ejemplo con NSSM:

```bat
nssm install INEES-Mantenimientos "C:\inees\iniciar.bat"
nssm set INEES-Mantenimientos AppDirectory C:\inees
nssm set INEES-Mantenimientos AppStdout C:\inees\logs\servicio.log
nssm set INEES-Mantenimientos AppStderr C:\inees\logs\servicio.log
nssm set INEES-Mantenimientos Start SERVICE_AUTO_START
nssm start INEES-Mantenimientos
```

La cuenta del servicio debe poder escribir en `LEGACY_ROOT` y ejecutar Chrome.

Alternativa sin herramientas extra: una tarea del Programador de tareas que ejecute `C:\inees\iniciar.bat` "Al iniciar el sistema", con la opción "Ejecutar tanto si el usuario inició sesión como si no".

## 4. Convivencia con el PHP

Las dos versiones pueden correr a la vez sobre la misma base y los mismos archivos, lo que permite una transición gradual:

| Fase | PHP (Apache, puerto 80) | Java (puerto 8080) |
| --- | --- | --- |
| Pruebas | En uso normal | Disponible para un grupo de prueba en `http://servidor:8080` |
| Cambio | Se deja de anunciar | Pasa a ser la dirección oficial |
| Retiro | Se apaga | Única versión |

Al cambiar de versión:

1. **Tareas programadas:** desactive en el Programador de tareas las que ejecutaban `cron_alertas.php` y `cron_alertas_telegram.php` *antes* de poner `CRON_ALERTAS=true` y `CRON_TELEGRAM=true`, para no enviar alertas duplicadas.
2. **Dirección:** para usar el puerto 80/443 con HTTPS, ponga Apache o IIS como proxy inverso hacia `http://localhost:8080` (por ejemplo `ProxyPass / http://localhost:8080/` y `ProxyPassReverse / http://localhost:8080/` en Apache con `mod_proxy`), o cambie `PORT` si el 80 queda libre.
3. **Sesiones:** son independientes. Un usuario con sesión en PHP debe volver a iniciar sesión en Java (las contraseñas son las mismas).
4. **Tamaño de subida:** si hay proxy, permita peticiones de hasta 200 MB (`LimitRequestBody` en Apache, `maxAllowedContentLength` en IIS), o fallará la carga de fotos e importaciones grandes.

## 5. Aplicar cambios de base de datos

Las versiones nuevas pueden traer archivos `.sql` (páginas nuevas en `rutas`, permisos, columnas). Siempre:

1. **Respaldar** la base: `mysqldump -u USUARIO -p --single-transaction --routines inees_mantenimientos > respaldo_AAAA-MM-DD.sql`.
2. Aplicar los `.sql` en orden de fecha.
3. Desplegar el `.jar` nuevo (paso 6).

## 6. Actualizar a una versión nueva

```bat
REM En el servidor
nssm stop INEES-Mantenimientos
copy /y C:\inees\inees-mantenimientos.jar C:\inees\inees-mantenimientos.anterior.jar
copy /y \\equipo-dev\entregas\inees-mantenimientos.jar C:\inees\inees-mantenimientos.jar
nssm start INEES-Mantenimientos
```

- Al reiniciar se cierran todas las sesiones: hágalo fuera del horario de trabajo de los técnicos.
- Verifique con la herramienta de prueba (ver [13-pruebas-y-solucion-de-problemas.md](13-pruebas-y-solucion-de-problemas.md#prueba-general-automática)): `probar-plataforma.ps1 -Url http://servidor:8080 -Usuario ... -Clave ...`.
- **Volver atrás:** detener el servicio, copiar `inees-mantenimientos.anterior.jar` sobre `inees-mantenimientos.jar` e iniciar. Si la versión nueva incluía cambios de base, restaurar también el respaldo del paso 5.

## 7. Respaldos y operación

| Qué | Cómo | Frecuencia sugerida |
| --- | --- | --- |
| Base de datos | `mysqldump` programado (tarea de Windows) | Diario, conservar 30 días |
| Archivos subidos | Copia de `uploads/`, `app/uploads/`, `imagenes/` de `LEGACY_ROOT` | Diario o semanal |
| Configuración | Copia de `C:\inees\iniciar.bat` en un lugar seguro | Al cambiarla |
| Logs | `C:\inees\logs\inees.log` (Spring rota el archivo al llegar a 10 MB) | Revisar ante cualquier reporte de error |

Señales de salud: `http://servidor:8080/api/sesion` debe responder 200 con `{"logueado":false,...}` sin iniciar sesión.
