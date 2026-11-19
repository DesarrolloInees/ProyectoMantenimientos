# 2. Instalación y ejecución en local

Con todo instalado, arrancar la plataforma es un doble clic en `nueva-plataforma\iniciar-local.bat`: levanta la base de datos si hace falta, el backend y abre el navegador en http://localhost:8080. Este documento explica qué instalar la primera vez, qué hace el script y cómo hacer lo mismo a mano.

## Requisitos

| Programa | Versión | Para qué | Cómo conseguirlo |
| --- | --- | --- | --- |
| Windows | 10 u 11 | Los scripts `.bat` | — |
| Java JDK | 21 o superior | Compilar y correr el backend | [Eclipse Temurin 21](https://adoptium.net), o descomprimir un JDK en `%USERPROFILE%\inees-local\jdk-21...` |
| Maven | 3.9 o superior | Compilar el backend | Si no está, `iniciar-local.bat` ofrece descargarlo a `%USERPROFILE%\inees-local` |
| Node.js | 20 o superior (incluye npm) | Compilar el frontend y el modo de desarrollo | [nodejs.org](https://nodejs.org) (versión LTS) |
| MariaDB o MySQL | MariaDB 10.6+ / MySQL 8 | Base de datos `inees_mantenimientos` | XAMPP (`C:\xampp`) o MariaDB portable en `%USERPROFILE%\inees-local` |
| Google Chrome | Cualquiera reciente | Generar los PDF (servicios, reportes) | Normalmente ya está instalado |

Node.js solo es obligatorio para compilar el frontend. Si `backend/src/main/resources/static/index.html` ya existe (frontend compilado), el modo normal no lo necesita.

### Dónde busca cada cosa el script

| Qué | Orden de búsqueda |
| --- | --- |
| Java | `JAVA_HOME` → `%USERPROFILE%\inees-local\jdk-21*` → `java` en el PATH |
| Maven | `mvn.cmd` en el PATH → `%USERPROFILE%\.m2\wrapper\dists\...` → `%USERPROFILE%\inees-local\apache-maven-*` → ofrece descargarlo |
| Servidor de BD (si no hay nada en el puerto 3306) | `DB_SERVIDOR_EXE` de `config-local.bat` → MariaDB en `%USERPROFILE%\inees-local\mariadb-*` con datos en `%USERPROFILE%\inees-local\mariadb-data` → XAMPP en `C:\xampp\mysql\bin\mysqld.exe` |
| Cliente `mysql.exe` (solo para verificar la base) | `inees-local\mariadb-*` → XAMPP → PATH |

## Primera vez, paso a paso

### 1. Base de datos

Si ya usa XAMPP con la base `inees_mantenimientos` (la misma del PHP), no hay nada que hacer: el script la detecta.

Si es un equipo nuevo, cree la base y cárguela desde un respaldo `.sql` (exportado con phpMyAdmin o `mysqldump` desde el servidor):

```bat
REM Con XAMPP (ajuste la ruta del respaldo)
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE inees_mantenimientos CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci"
C:\xampp\mysql\bin\mysql.exe -u root inees_mantenimientos < C:\respaldos\inees_mantenimientos.sql
```

Para sacar un respaldo de otro servidor:

```bat
mysqldump -h SERVIDOR -u USUARIO -p --routines --single-transaction inees_mantenimientos > inees_mantenimientos.sql
```

> La base debe tener las 146 filas de `rutas` y los permisos de `rol_permisos`; sin ellas nadie puede entrar a ninguna página. Un respaldo completo de producción ya las trae.

### 2. Usuario administrador (solo si la base no tiene usuarios)

Este SQL crea el usuario `admin` con la contraseña temporal `Inees#Temporal1`; el sistema obliga a cambiarla en el primer ingreso (`forzar_cambio_pwd = 1`):

```sql
INSERT INTO usuarios (nombre, cedula, cargo, email, usuario, password_hash, forzar_cambio_pwd, nivel_acceso, estado)
VALUES ('Administrador', '0', 'Administrador', 'admin@ejemplo.com', 'admin',
        '$2y$10$.B9wX08apOWcnKa/lCFK5u/SalV9EI6kvwqrbKUb6M2eZobE7l872', 1, 1, 'activo');
```

### 3. Configuración local (opcional)

Copie `config-local.ejemplo.bat` como `config-local.bat` en la misma carpeta y descomente lo que necesite. Lo más común:

- `DB_USER` / `DB_PASS` si su MySQL tiene contraseña para root o usa otro usuario.
- `LEGACY_ROOT` si las fotos (`uploads/`, `app/uploads/`, `imagenes/`) están en otra carpeta, por ejemplo `C:\xampp\htdocs\ProyectoMantenimientos`. Por defecto se usa la carpeta que contiene a `nueva-plataforma`.
- `SMTP_PASS` si va a probar correos (recuperar contraseña, solicitudes de inventario).

`config-local.bat` está en `.gitignore`: no se sube al repositorio. Todas las variables están en [03-configuracion.md](03-configuracion.md).

### 4. Arrancar

Doble clic en `iniciar-local.bat`, o desde una consola:

```bat
cd nueva-plataforma
iniciar-local.bat
```

La primera vez tarda varios minutos: Maven descarga las dependencias del backend, `npm ci` descarga las del frontend y se compila Angular. Las siguientes veces arranca en unos 30 a 60 segundos.

## Modos de `iniciar-local.bat`

| Comando | Qué levanta | URL | Cuándo usarlo |
| --- | --- | --- | --- |
| `iniciar-local.bat` | BD (si hace falta) + backend sirviendo el frontend ya compilado. Si nunca se compiló Angular, lo compila. | http://localhost:8080 | Usar la plataforma, probar el backend |
| `iniciar-local.bat dev` | Lo anterior + `ng serve` en otra ventana, con recarga automática al guardar cambios del frontend | http://localhost:4200 | Desarrollar pantallas |
| `iniciar-local.bat build` | Recompila Angular (`ng build`) y arranca como el modo normal | http://localhost:8080 | Después de cambiar el frontend, para verlo en el puerto 8080 |
| `iniciar-local.bat ayuda` | Muestra la ayuda | — | — |

Para detener:

| Comando | Qué detiene |
| --- | --- |
| `detener-local.bat` | Backend (puerto 8080) y `ng serve` (puerto 4200) |
| `detener-local.bat todo` | Además apaga la base de datos con `mysqladmin shutdown` |

Los procesos se buscan por el puerto en que escuchan, así que no se cierran otros programas Java o Node que tenga abiertos.

## Qué hace `iniciar-local.bat`, en orden

1. Carga `config-local.bat` si existe y pone valores por defecto (`PORT=8080`, `DB_PORT=3306`, `DB_USER=root`, …).
2. Busca Java 21 y verifica la versión.
3. Busca Maven; si no lo encuentra, pregunta si lo descarga.
4. Si no hay nada escuchando en el puerto de la BD, arranca MariaDB o MySQL en una ventana minimizada "INEES Base de datos" y espera hasta 30 segundos.
5. Verifica con `mysql.exe` que se pueda leer `inees_mantenimientos.rutas` y avisa si no.
6. Si hace falta compilar el frontend (modo `build`, modo `dev` o no hay compilación previa), ejecuta `npm ci` (solo si falta `node_modules`) y `npx ng build`.
7. Si el puerto 8080 está libre, abre la ventana "INEES Backend" (`scripts\_backend.bat`, que ejecuta `mvn spring-boot:run`). Si ya está ocupado, asume que el backend corre.
8. Espera hasta 5 minutos a que `http://localhost:8080/api/sesion` responda 200.
9. En modo `dev` abre la ventana "INEES Frontend" (`scripts\_frontend.bat`, que ejecuta `ng serve --open`); si no, abre el navegador en el puerto 8080.

Los mensajes del backend (errores de SQL, excepciones) se ven en la ventana **INEES Backend**. Cerrar esa ventana detiene el backend.

## Hacer lo mismo a mano

Útil para entender el flujo o si el script falla.

```bat
REM 1. Base de datos (ejemplo con MariaDB portable; con XAMPP use su panel)
"%USERPROFILE%\inees-local\mariadb-10.11.19-winx64\bin\mariadbd.exe" --datadir="%USERPROFILE%\inees-local\mariadb-data" --port=3306 --console

REM 2. Frontend (en otra consola), solo si cambió o es la primera vez
cd nueva-plataforma\frontend
npm ci
npx ng build              REM deja el resultado en backend\src\main\resources\static

REM 3. Backend
cd ..\backend
mvn spring-boot:run       REM o: powershell -File mvn-local.ps1 spring-boot:run

REM 4. (Opcional) desarrollo del frontend con recarga automática
cd ..\frontend
npx ng serve              REM http://localhost:4200, redirige /api al 8080 (proxy.conf.json)
```

## Flujo de trabajo al desarrollar

| Cambió… | Qué hacer para verlo |
| --- | --- |
| Un archivo de Angular (`frontend/src/...`) en modo `dev` | Nada: `ng serve` recompila y recarga el navegador en el puerto 4200 |
| Un archivo de Angular sin modo `dev` | `iniciar-local.bat build` (o `npx ng build`) y recargar el navegador |
| Código Java o `application.yml` | Cerrar la ventana "INEES Backend" (o `detener-local.bat`) y volver a ejecutar `iniciar-local.bat`. No hay recarga en caliente. |
| Una plantilla de PDF (`resources/plantillas`) | Igual que Java: reiniciar el backend |
| Datos o permisos en la BD | Nada: se leen en cada petición. Para permisos, recargar la página. |

Al reiniciar el backend se pierden las sesiones (están en memoria): hay que volver a iniciar sesión.

## Problemas frecuentes al arrancar

| Síntoma | Causa probable | Solución |
| --- | --- | --- |
| `No se encontro Java 21` | No hay JDK o es Java 8/11/17 | Instale Temurin 21 o defina `JAVA_HOME` en `config-local.bat` |
| `No hay una base de datos escuchando...` | MySQL apagado y sin MariaDB/XAMPP en las rutas conocidas | Arranque MySQL desde XAMPP o defina `DB_SERVIDOR_EXE` |
| `[AVISO] No se pudo leer inees_mantenimientos.rutas` | La base no existe o el usuario/clave no son correctos | Importe el respaldo (paso 1) o ajuste `DB_USER`/`DB_PASS` |
| La ventana del backend muestra `Access denied for user` | Credenciales de BD incorrectas | `DB_USER`/`DB_PASS` en `config-local.bat` |
| `Port 8080 was already in use` | Otro programa usa el 8080 | `detener-local.bat`, o cambie `PORT` en `config-local.bat` |
| Fotos o logos no cargan (404) | `LEGACY_ROOT` no apunta a la carpeta del PHP | Ajuste `LEGACY_ROOT` |
| Todo responde "Página no encontrada o acceso denegado" | La página no está en `rutas` o el rol no la tiene | Ver [08-permisos-y-roles.md](08-permisos-y-roles.md) |
| Los PDF salen distintos o no se generan | No se encontró Chrome | Defina `CHROME_PATH` |

Más casos en [13-pruebas-y-solucion-de-problemas.md](13-pruebas-y-solucion-de-problemas.md).
