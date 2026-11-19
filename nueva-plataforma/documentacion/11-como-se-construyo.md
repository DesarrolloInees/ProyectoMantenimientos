# 11. Cómo se construyó

Esta plataforma es una **migración uno a uno** de la versión PHP (carpeta raíz `ProyectoMantenimientos/`) a Spring Boot + Angular, hecha en noviembre de 2026. La regla fue: cada pantalla, consulta y comportamiento del PHP debe existir igual en la versión nueva, sobre **la misma base de datos sin cambiar su estructura**. Este documento explica el método, las equivalencias y las decisiones, para que quien mantenga el código entienda por qué está hecho así.

## Objetivos y restricciones

| Objetivo | Cómo se cumplió |
| --- | --- |
| Todas las funcionalidades, sin excluir ninguna | Se recorrió cada controlador, modelo, vista y JavaScript de `app/` y cada fila de la tabla `rutas`; el estado por módulo está al final de este documento |
| Misma base de datos, misma interacción | Las consultas SQL se copiaron del PHP y se ejecutan con `JdbcTemplate`; los tipos de datos se devuelven igual que PDO |
| Sin cambios de esquema | No se creó, alteró ni borró ninguna tabla o columna. Solo se escriben datos, igual que antes |
| Convivencia con el PHP | Mismas carpetas de archivos, mismas rutas relativas en la base, mismos hashes de contraseña y mismas claves de sesión; las dos versiones pueden correr a la vez |

## Arquitectura elegida y por qué

| Decisión | Alternativa descartada | Motivo |
| --- | --- | --- |
| SQL a mano con `core/Db` (JdbcTemplate) | JPA/Hibernate con entidades | Permite copiar las consultas del PHP tal cual, sin modelar 42 tablas ni arriesgar diferencias en los datos que recibe la pantalla |
| URLs `/api/{pagina}?accion={accion}` | API REST rediseñada | Cada `accion` del PHP corresponde a un método Java, lo que facilita comparar y migrar; el frontend conserva la misma lógica |
| Mismas claves en las respuestas JSON (`exito`, `success`, `status`, `msj`...) | Un formato único | El JavaScript de cada vista ya esperaba esas claves; unificarlas habría multiplicado los cambios y los riesgos |
| Permisos con `rutas` y `rol_permisos` | Spring Security con roles nuevos | Los permisos ya están cargados y se administran igual; un cambio de modelo no aportaba nada a la migración |
| Sesión en el servidor (cookie `PHPSESSID_INEES`) | Tokens JWT | Mismo comportamiento que `$_SESSION`; las claves de sesión son las mismas |
| Un solo `.jar` que sirve Angular, API y archivos | Frontend y backend desplegados por separado | Instalación y operación más simples en el servidor Windows actual |
| PDF con Chrome sin ventana | Librerías Java de PDF | El PHP usaba Browsershot (Chrome); así los PDF salen iguales y las plantillas pueden usar CSS moderno y JavaScript |
| Angular *standalone* + Tailwind | Mantener vistas PHP + jQuery | Pedido del proyecto; la mayoría de las vistas PHP ya usaban clases de Tailwind, así que su HTML se trasladó con pocos cambios |

## Equivalencias PHP → nueva plataforma

| PHP | Spring Boot / Angular |
| --- | --- |
| `index.php` (router, páginas públicas, RBAC) | `core/RbacInterceptor` + `core/Rbac` (backend) y `paginaGuard` (Angular) |
| `app/config/conexion.php` (PDO) | `application.yml` (`spring.datasource`) + `core/Db` |
| `$_GET`, `$_POST`, `$_FILES`, `php://input` | `core/Req` (incluye arreglos anidados `a[b][]`) |
| `$_SESSION` | `core/Sesion` (mismas claves) |
| `app/controllers/<m>/<x>Controlador.php` | `modulos/<m>/<X>Controller.java` (una `accion` = un método) |
| `app/models/<m>/<x>Modelo.php` | SQL dentro del controlador o `<X>Modelo.java` si era grande |
| `app/views/<m>/<x>Vista.php` + `js/<m>/*.js` | `frontend/src/app/paginas/<m>/<x>.ts` (plantilla + lógica) |
| `app/views/plantillaVista.php`, `app/partials/navbar_menu*.php` | `layout/layout.ts`, `layout/menu.ts` |
| DataTables | `shared/tabla.ts` (`TablaDatos`, `TablaServidor`) |
| Select2 | `shared/select-buscar.ts` |
| SweetAlert2 (CDN) | SweetAlert2 (npm) vía `core/ui.ts` |
| PhpSpreadsheet | Apache POI vía `core/Excel` y `core/ExcelLector` |
| Browsershot / Dompdf / mPDF | `core/Pdf` (Chrome headless; respaldo OpenHTMLtoPDF) |
| PHPMailer + `getConfiguracionSmtp()` | Spring Mail vía `core/Correo` |
| `password_hash` / `password_verify` | `core/Hash` (bcrypt `$2y$`, compatible en los dos sentidos) |
| GD (`imagerotate`, redimensionar) | `core/Archivos.guardarImagenOptimizada*` |
| `cron_alertas.php`, `cron_alertas_telegram.php` (Programador de tareas) | `modulos/tareas/TareasProgramadas` (`@Scheduled`) |
| `helps/*.php` (scripts sueltos) | Página `herramientasCarga` |
| `.env` / constantes en el código | Variables de entorno leídas por `application.yml` |

## Método de trabajo

Cada módulo se migró con el mismo ciclo:

1. **Leer** el controlador, el modelo, la vista y el JavaScript del PHP completos.
2. **Portar el backend:** un método Java por cada `accion`, con el SQL idéntico y las mismas validaciones, mensajes y claves de respuesta.
3. **Portar la pantalla:** el HTML de la vista a la plantilla del componente y el JavaScript a TypeScript, reemplazando jQuery, DataTables y Select2 por sus equivalentes.
4. **Registrar** la ruta en `app.routes.ts` (y en el menú si aplica).
5. **Compilar** backend y frontend.
6. **Probar contra la base real** con un usuario de prueba: consultas, guardados, ediciones, borrados, descargas de Excel y PDF (revisando el contenido), usando datos marcados (fechas 2099, nombres "ZZ QA") que se borraban al terminar. Nunca se enviaron correos a direcciones reales de la empresa durante las pruebas.
7. **Comparar** con el comportamiento del PHP; cuando el PHP tenía un error evidente, se corrigió y se dejó registrado (sección siguiente).

## Diferencias deliberadas con el PHP

**Errores del PHP que se corrigieron** (en todo lo demás la plataforma se comporta igual):

| Dónde | Error en PHP | Ahora |
| --- | --- | --- |
| `cronometroPdf`, `cronometroExcel` | No estaban en `rutas`: los botones respondían 404 | También se sirven como `cronometroReportes?accion=pdf` y `?accion=excel` |
| `cronometroCrear` | El JavaScript leía `#id_tipo_mantenimiento_final`, que no existe: la meta siempre era 60 minutos | Usa la meta del tipo de mantenimiento elegido |
| `horaExtraHistorial` | Usaba `rutas_fotos`, que la consulta nunca devolvía: "Ver Evidencias" no aparecía | Se arma desde `fotos_detalle` |
| `horaExtraGenerar` (PDF) | Buscaba las fotos en `app/app/...`: el PDF salía sin fotos | Ruta corregida |
| `rastreoTecnico` | Exportaba a Excel con SheetJS sin cargar la librería | Funciona |
| `asistencia` | La clase `d-none` no existía en la plantilla: los paneles nunca se ocultaban | Definida en `public/css/asistencia.css` |
| `helps/*.php` | Se abrían por URL sin iniciar sesión, con la clave de la BD escrita en el código | Página `herramientasCarga`, solo para el administrador, con la conexión de la aplicación |
| `helps/importar_parametros.php` | Escrito para un esquema anterior (`id_tipo_zona`): vaciaba `tarifa` y luego fallaba, dejando el sistema sin tarifas | Revisa el esquema y, si no coincide, se cancela sin modificar nada |
| `helps/actualizar_puntos.php` | Leía un CSV fijo dentro de la carpeta `helps/` | Se sube el archivo |
| Llaves de Telegram, Google Maps y SMTP | Escritas en el código PHP | Variables de entorno |

**Se mantuvo igual a propósito**, aunque parezca un error:

- `importarExcel`, en modo importación, procesa todas las filas aunque se marquen solo algunas como aprobadas. Cambiarlo podría producir bajas masivas inesperadas al finalizar la importación.
- `ajaxServiciosSupervisor.php` no tenía ruta ni lo usaba ninguna vista: no se migró.
- Las respuestas de error devuelven HTTP 200 con `success: false`, porque así las esperan las pantallas.

**Hallazgos de la verificación final (19/11/2026):**

- `planeacionVer` usa las tablas `estrategias`, `tareas_estrategia`, `estados_planeacion` y `arbol`, que no existen en la copia local de la base (el PHP usa las mismas). Sin ellas la página abre vacía. Hay que traerlas del respaldo del servidor donde se usaba.
- Los parámetros `intentos_maximos`, `password_vigencia_dias` y `session_timeout_minutos` existen en la tabla `parametros`, pero ningún código (PHP ni Java) los usa.
- El menú no se filtra por permisos (tampoco en PHP): cada rol ve una lista fija.

## Verificación realizada

Además de las pruebas por módulo, al terminar se hizo una verificación general:

| Prueba | Resultado |
| --- | --- |
| Compilación de backend (`mvn compile`) y frontend (`ng build`) | Sin errores |
| Las 145 páginas activas de `rutas` + `herramientasCarga`, consultadas con un administrador | Todas responden; las que dan aviso son endpoints solo POST o sin acción `index`, como se esperaba |
| Cruce de las 249 llamadas del frontend contra los 344 endpoints del backend | Todas tienen su endpoint |
| Consultas de lectura con datos reales: listados de órdenes, remisiones (15.872 filas paginadas), tablero ejecutivo, facturación, rastreo, panel del supervisor | Correctas |
| Descargas: PDF de un servicio real, Excel de cronómetro, turnos y horas extra | Archivos válidos |
| Ciclo crear → editar → eliminar en un catálogo | Correcto y sin rastros |
| Permisos: rol con permiso, rol sin permiso, sin sesión, página exclusiva del administrador | 200, 404, 401 y 404 respectivamente |
| Tablas usadas por el código Java vs. tablas de la base | Solo faltan las 4 de planeación (ver arriba) |
| Arranque y detención con `iniciar-local.bat` / `detener-local.bat` | Correcto |

## Estado por módulo

- [x] Núcleo: router de permisos, sesión, base de datos, archivos, PDF, Excel, correo, Telegram
- [x] Login, cambio y recuperación de contraseña, logout
- [x] Plantilla: layout, menús por rol, modal de turno
- [x] Inicio
- [x] Turnos: marcación, `turnoReportes`, `turnoExcel`, `turnoPdf`
- [x] Catálogos: calificación de servicio, delegación, estado de máquina, modalidad operativa, técnico, tipo de mantenimiento, tipo de máquina, tipo de usuario (Ver/Crear/Editar/Eliminar)
- [x] Tipos de novedad, festivos, clientes, usuarios, máquinas, puntos, repuestos, tarifas (+ edición masiva)
- [x] Remisiones (lista paginada en servidor, crear, editar, eliminar), inventario por técnico, devoluciones
- [x] Órdenes: crear (con borrador), ver, detalle, buscador, consulta móvil, reporte, editar complementos, PDF masivo en ZIP, PDF individual
- [x] Técnico: programación diaria (GPS, extras), reporte del servicio (fotos, firma, novedades, borrador, repuestos), editar reporte
- [x] Programación: crear, consolidado, mapa, planeación (Gantt)
- [x] Reportes: por técnico, repuestos, inventario, ejecutivo (+ PDF configurable), historial, máquinas, tarifas, devoluciones, trazabilidad, remisiones salteadas, facturación
- [x] Costos de motorizados y administrativos
- [x] Cronómetro: técnico, historial, supervisor, reportes con PDF y Excel
- [x] Horas extra: reporte con fotos, historial, administración, PDF y Excel por técnico
- [x] Parqueaderos: registro, historial, administración con PDF, Excel y rotación de fotos
- [x] Transportes: lista, registro, edición, PDF
- [x] Solicitudes de inventario, exportar Excel, geolocalizar, rastreo, panel del supervisor, alertas logísticas, información de la base
- [x] Importaciones: Excel C2D por lotes, estado de máquinas, municipios
- [x] Procesador de asistencia
- [x] Tareas programadas (reemplazo de los dos cron PHP)
- [x] Herramientas de carga (reemplazo de `helps/*.php`)
