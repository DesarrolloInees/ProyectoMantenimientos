# 3. Configuración

Toda la configuración está en `backend/src/main/resources/application.yml` y **cada valor se puede cambiar con una variable de entorno**, sin recompilar. En local las variables se ponen en `config-local.bat`; en producción, en las variables del servicio o del sistema (ver [12-despliegue.md](12-despliegue.md)).

En el YAML, `${NOMBRE:valor}` significa: usar la variable de entorno `NOMBRE` y, si no existe, `valor`.

## Variables de entorno

### Servidor

| Variable | Por defecto | Qué controla |
| --- | --- | --- |
| `PORT` | `8080` | Puerto HTTP del backend |
| `SESSION_TIMEOUT` | `8h` | Tiempo de inactividad tras el cual se cierra la sesión (formato Spring: `30m`, `8h`) |

Fijos en el YAML (cambiar requiere recompilar): cookie de sesión `PHPSESSID_INEES` con `HttpOnly`, subida máxima de 50 MB por archivo y 200 MB por petición.

### Base de datos

| Variable | Por defecto | Qué controla |
| --- | --- | --- |
| `DB_HOST` | `localhost` | Servidor MariaDB/MySQL |
| `DB_PORT` | `3306` | Puerto |
| `DB_NAME` | `inees_mantenimientos` | Nombre de la base |
| `DB_USER` | `root` | Usuario |
| `DB_PASS` | vacío | Contraseña |

La URL JDBC agrega `characterEncoding=UTF-8&tinyInt1isBit=false&yearIsDateType=false` para que los `TINYINT(1)` lleguen como números (0/1) y no como booleanos, igual que en PHP. El pool de conexiones (Hikari) permite 15 conexiones simultáneas.

### Archivos

| Variable | Por defecto | Qué controla |
| --- | --- | --- |
| `LEGACY_ROOT` | `../..` (relativo a la carpeta `backend`, o sea la raíz del proyecto PHP) | Carpeta raíz donde están y se guardan `uploads/`, `app/uploads/`, `imagenes/`, `app/logos/`, `app/temp/`. Las rutas que guarda la base son relativas a esta carpeta. |

En producción conviene una ruta absoluta, por ejemplo `C:\xampp\htdocs\ProyectoMantenimientos`, para que no dependa de la carpeta desde donde se ejecute el `.jar`.

### Correo (SMTP)

| Variable | Por defecto | Qué controla |
| --- | --- | --- |
| `SMTP_HOST` | `smtp.gmail.com` | Servidor SMTP |
| `SMTP_PORT` | `465` | Puerto |
| `SMTP_USER` | `ineesmensajesautomaticos@gmail.com` | Cuenta remitente |
| `SMTP_PASS` | vacío | Contraseña (en Gmail: una *contraseña de aplicación*) |
| `SMTP_SSL` | `true` | SSL directo (puerto 465) |
| `SMTP_STARTTLS` | `false` | STARTTLS (puerto 587: poner `SMTP_SSL=false` y `SMTP_STARTTLS=true`) |

El nombre del remitente es "Sistema I-Nexis" (`inees.mail-from-name`). Sin `SMTP_PASS` no se envían correos: fallan la recuperación de contraseña, las solicitudes de inventario y las alertas logísticas.

### Servicios externos

| Variable | Por defecto | Para qué | Sin configurar |
| --- | --- | --- | --- |
| `TELEGRAM_BOT_TOKEN` | vacío | Bot que envía alertas del cronómetro | Las alertas se registran en el log y no se envían |
| `TELEGRAM_CHAT_ID` | vacío | Grupo o chat que recibe las alertas | Igual que arriba |
| `GOOGLE_MAPS_KEY` | vacío | Geocodificación en la página `geolocalizar` | La página informa que falta la llave |
| `GROQ_API_KEY`, `GROQ_API_KEY_2` | vacío | "Mejorar texto con IA" en `ordenDetalle` (la segunda llave es de respaldo) | El botón responde con error |
| `CHROME_PATH` | `C:\Program Files\Google\Chrome\Application\chrome.exe` | Chrome sin ventana para los PDF | Se usa OpenHTMLtoPDF (Java puro): funciona, pero el diseño puede variar y no ejecuta JavaScript |

> En la versión PHP estas llaves estaban escritas dentro del código. En Java nunca deben ir en el código ni en `application.yml`: solo en variables de entorno.

### Tareas programadas

Reemplazan a `cron_alertas.php` y `cron_alertas_telegram.php`, que antes ejecutaba el Programador de tareas de Windows. Vienen **apagadas**; en local deben seguir apagadas para no enviar alertas reales.

| Variable | Por defecto | Qué controla |
| --- | --- | --- |
| `CRON_ALERTAS` | `false` | Activa el correo diario de alertas logísticas (visitas frecuentes y desplazamientos largos) |
| `CRON_ALERTAS_EXPR` | `0 0 7 * * *` | Cuándo corre (expresión cron de Spring: segundo, minuto, hora, día, mes, día de la semana). Por defecto, todos los días a las 7:00 |
| `CRON_TELEGRAM` | `false` | Activa la revisión de servicios en curso que superan su tiempo meta y avisa por Telegram |
| `CRON_TELEGRAM_EXPR` | `0 */5 * * * *` | Cada 5 minutos |

Las expresiones usan la zona horaria `America/Bogota`. Al activarlas en producción hay que **desactivar las tareas de Windows que llamaban a los `.php`**, o las alertas llegarán dos veces.

### Registro (logs)

`logging.level.root` y `logging.level.co.inees` están en `INFO`. Para ver más detalle sin recompilar:

```bat
set LOGGING_LEVEL_CO_INEES=DEBUG
```

Para enviar el log a un archivo: `set LOGGING_FILE_NAME=C:\inees\logs\inees.log`.

## Configuración guardada en la base de datos

Algunos valores de negocio están en la tabla `parametros` y se cambian con SQL, sin reiniciar. Ver [06-base-de-datos.md → Tabla parametros](06-base-de-datos.md#tabla-parametros).

## Configuración del frontend

| Archivo | Qué define |
| --- | --- |
| `frontend/proxy.conf.json` | En modo `ng serve` (puerto 4200), redirige `/api`, `/uploads`, `/app/uploads`, `/app/logos`, `/app/imagenes`, `/app/temp` e `/imagenes` al backend en el 8080 |
| `frontend/angular.json` | La compilación se escribe en `../backend/src/main/resources/static` (`outputPath`) |
| `frontend/tailwind.config.js` | Archivos que Tailwind revisa para generar las clases CSS |
| `frontend/src/app/app.config.ts` | Idioma y formato `es-CO`, router, cliente HTTP con el interceptor de sesión |

El frontend no tiene variables de entorno propias: todas las URLs son relativas (`/api/...`), así que funciona en cualquier dominio o puerto donde lo sirva el backend.
