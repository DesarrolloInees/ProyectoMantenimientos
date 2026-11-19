# 13. Pruebas y solución de problemas

## Prueba general automática

`documentacion/herramientas/probar-plataforma.ps1` inicia sesión con un usuario, recorre **todas las páginas que ese usuario tiene permitidas**, revisa que el frontend y los archivos se sirvan y que sin sesión la API responda 401. No modifica datos.

```bat
cd nueva-plataforma
powershell -ExecutionPolicy Bypass -File documentacion\herramientas\probar-plataforma.ps1 -Usuario admin -Clave "su-clave"
powershell -ExecutionPolicy Bypass -File documentacion\herramientas\probar-plataforma.ps1 -Url http://servidor:8080 -Usuario admin -Clave "su-clave"
```

Resultado esperado con un administrador (19/11/2026):

```text
Páginas OK: 104   |   Sin acción index (normal): 41   |   Con error: 0
Estático /                            200
Estático /inicio                      200
Estático /app/logos/logoInees.jpg     200
Sin sesión /api/inicio -> 401 (se espera 401)
```

"Sin acción index" es normal: son páginas de Crear, Eliminar o descargas que solo aceptan POST o una acción específica. Ejecútela con usuarios de distintos roles después de cada despliegue o cambio de permisos.

## Probar endpoints con curl

`curl` viene con Windows 10/11. La sesión se guarda en un archivo de cookies:

```bat
REM 1. Iniciar sesión y guardar la cookie
curl -c cookies.txt -b cookies.txt -X POST http://localhost:8080/api/login --data-urlencode "usuario=admin" --data-urlencode "password=su-clave"

REM 2. GET con parámetros
curl -b cookies.txt "http://localhost:8080/api/ordenVer?accion=index"

REM 3. POST de formulario
curl -b cookies.txt -X POST "http://localhost:8080/api/ordenVer?accion=ajaxListar" --data "fecha=2026-11-06"

REM 4. POST con archivo
curl -b cookies.txt -X POST "http://localhost:8080/api/importarEstadoMaquina?accion=subirArchivo" -F "archivo_excel=@C:\ruta\estado.xlsx"

REM 5. Descargar un archivo
curl -b cookies.txt -o reporte.xlsx "http://localhost:8080/api/turnoExcel?accion=generar&fecha_inicio=2026-10-01&fecha_fin=2026-11-07"

REM 6. Cerrar sesión
curl -b cookies.txt -X POST http://localhost:8080/api/logout
```

También sirve la pestaña **Red** (F12) del navegador: muestra cada llamada a `/api/...`, sus parámetros y la respuesta.

## Cómo probar un cambio

1. **Compilar:** `mvn compile` en `backend` y `npx ng build` en `frontend` sin errores.
2. **Endpoint aislado** con curl (arriba): respuesta con los datos y claves esperados.
3. **Pantalla** en el navegador con `iniciar-local.bat dev`: flujo completo, mensajes de éxito y de error, consola del navegador (F12) sin errores en rojo.
4. **Permisos:** con un rol que tenga la página y con uno que no.
5. **Datos:** verificar en la base lo que se escribió (`mysql` o phpMyAdmin) y borrar los datos de prueba. Use marcas fáciles de encontrar (fechas en 2099, nombres "ZZ QA").
6. **Descargas:** abrir el Excel o PDF y revisar el contenido, no solo que se descargue.
7. **Correos:** no use direcciones reales de la empresa al probar.
8. **Prueba general** con `probar-plataforma.ps1`.

## Dónde mirar cuando algo falla

| Dónde | Qué muestra |
| --- | --- |
| Ventana "INEES Backend" (local) o `logs\inees.log` (servidor) | Excepciones de Java con su traza, errores de SQL, avisos de correo/Telegram/Chrome |
| Pestaña Red del navegador (F12) | La petición exacta y lo que respondió el backend |
| Consola del navegador (F12) | Errores de JavaScript/Angular en la pantalla |
| Respuesta JSON con `"success": false, "message": "Error: ..."` | El mensaje de la excepción que capturó `core/ApiErrores` (el detalle completo está en el log) |

## Problemas frecuentes

### Arranque

| Síntoma | Causa | Solución |
| --- | --- | --- |
| La ventana del backend se cierra o muestra `APPLICATION FAILED TO START` | Error de configuración, casi siempre de base de datos | Leer el mensaje; revisar `DB_*` |
| `Communications link failure` / `Connection refused` | MariaDB/MySQL apagado o en otro puerto | Arrancar la base; revisar `DB_HOST`/`DB_PORT` |
| `Access denied for user` | Usuario o clave de BD | `DB_USER` / `DB_PASS` |
| `Unknown database 'inees_mantenimientos'` | La base no existe en ese servidor | Importar el respaldo ([02](02-instalacion-y-ejecucion-local.md#1-base-de-datos)) |
| `Port 8080 was already in use` | Otro proceso usa el puerto (a veces un backend anterior) | `detener-local.bat` o cambiar `PORT` |
| `release version 21 not supported` al compilar | Maven usa un Java viejo | `JAVA_HOME` apuntando al JDK 21 |
| `ng` no se reconoce / errores de npm | Falta Node.js o `node_modules` | Instalar Node 20+ y ejecutar `npm ci` en `frontend` |

### Acceso y permisos

| Síntoma | Causa | Solución |
| --- | --- | --- |
| Siempre vuelve al login | La sesión expiró, el backend se reinició o el navegador bloquea cookies | Volver a entrar; si pasa en cada clic, revisar que el proxy conserve la cookie `PHPSESSID_INEES` |
| Página 404 / "Página no encontrada o acceso denegado" | La página no está en `rutas`, está `inactivo`, o el rol no la tiene en `rol_permisos` | [08-permisos-y-roles.md](08-permisos-y-roles.md#dar-o-quitar-acceso-a-una-página) |
| Un ítem del menú lleva a 404 | El menú es fijo por rol y ese rol no tiene la página | Dar el permiso o quitar el ítem de su menú (`menu.ts`) |
| No puede iniciar sesión con una clave correcta | Usuario `inactivo`, o hash de contraseña vacío | Revisar `usuarios.estado` y `password_hash` |
| Pide cambiar la contraseña en cada ingreso | `forzar_cambio_pwd = 1` y el cambio falla | Ver el mensaje del formulario; la nueva clave debe cumplir las reglas de seguridad |

### Funcionalidades

| Síntoma | Causa | Solución |
| --- | --- | --- |
| Fotos, firmas o logos no se ven (404) | `LEGACY_ROOT` no apunta a la carpeta correcta, o el archivo no existe | Ajustar `LEGACY_ROOT`; comprobar que el archivo exista en esa ruta relativa |
| Error al subir fotos o archivos grandes | Supera 50 MB por archivo / 200 MB por envío, o el proxy lo limita | Reducir el archivo o ajustar límites ([12](12-despliegue.md#4-convivencia-con-el-php)) |
| "No se pudo guardar el archivo" | La cuenta que ejecuta Java no puede escribir en `LEGACY_ROOT` | Dar permisos de escritura a esas carpetas |
| PDF en blanco, sin estilos o muy lento | Chrome no encontrado (se usa el motor de respaldo) o se colgó | Definir `CHROME_PATH`; el log muestra "Chrome headless falló" |
| PDF con fotos rotas | La foto no existe en disco | Revisar la ruta guardada en la base y `LEGACY_ROOT` |
| No llegan correos | `SMTP_PASS` vacío o inválido; Gmail exige contraseña de aplicación | Configurar SMTP ([03](03-configuracion.md#correo-smtp)); el log muestra el error de autenticación |
| No llegan alertas de Telegram | `CRON_TELEGRAM` apagado, o falta token/chat | Revisar las variables; el log dice "Telegram no configurado" |
| Alertas duplicadas | Siguen activas las tareas de Windows del PHP | Desactivarlas |
| Geolocalizar no hace nada | Falta `GOOGLE_MAPS_KEY` o la llave no tiene habilitada Geocoding API | Configurar la llave |
| "Mejorar texto con IA" falla | Falta `GROQ_API_KEY` | Configurarla |
| `planeacionVer` vacía o sin guardar | Faltan las tablas `estrategias`, `tareas_estrategia`, `estados_planeacion`, `arbol` | Traerlas del respaldo del servidor ([09](09-modulos.md#programación)) |
| Excel con caracteres raros al importar CSV | El archivo no está en UTF-8 ni en Windows-1252 | Guardarlo como "CSV UTF-8" desde Excel |
| Importación se queda a medias | Se cerró la pestaña o se reinició el backend (las filas viven en la sesión) | Volver a subir el archivo |
| Datos viejos en pantalla tras desplegar | El navegador guardó la versión anterior | Recargar con Ctrl+F5 |
