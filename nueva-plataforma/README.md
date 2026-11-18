# Plataforma INEES Mantenimientos — nueva versión

Angular 22 + Spring Boot 4.1 (Java 21) sobre MariaDB. Reemplaza a la aplicación PHP de la carpeta superior, que
**no se modificó** y sigue funcionando con su propia base (`inees_mantenimientos`) hasta el corte (ver «Fase 2»).

```
nueva-plataforma/
├── backend/    Spring Boot: API REST, seguridad JWT, migraciones Flyway, optimizador de rutas
├── frontend/   Angular + Angular Material + Leaflet: oficina (escritorio) y motorizado (celular)
├── iniciar.bat / detener.bat
└── almacenamiento/  fotos y firmas en desarrollo (ignorada por git)
```

## Cómo correrla en local

1. Doble clic en `iniciar.bat`: arranca MariaDB (si no está), el backend en **http://localhost:8081** y el
   frontend en **http://localhost:4310**, y abre el navegador.
2. `detener.bat` apaga backend y frontend (MariaDB queda arriba porque la usa también la app PHP).

Requisitos ya instalados en este equipo: JDK 21, MariaDB 10.11 portable y Node 24 portable
(`C:\Users\PERSONAL\inees-local`). El puerto 4200 no se usa porque en este equipo lo ocupa otro proyecto.

### Base de datos de desarrollo

- Base `inees_plataforma`: copia del volcado del 06-11-2026. La app PHP local sigue usando `inees_mantenimientos`.
- Flyway la tomó como versión 1 y aplicó `V2__nueva_operacion.sql`. En una base vacía, `V1__esquema_base.sql`
  crea las 42 tablas y los catálogos.
- Usuarios de prueba **solo en esta copia**: `admin_local` / `AdminLocal2026` (SuperUsuario) y
  `EdinsonR` / `Motorizado2026` (motorizado de Barranquilla). Los demás usuarios entran con su contraseña de
  siempre: los hash `$2y$` de PHP son compatibles con Spring Security.

Variables para otros entornos: `DB_URL`, `DB_USUARIO`, `DB_CLAVE`, `JWT_SECRETO` (32+ caracteres),
`ALMACENAMIENTO_RUTA` (apuntarla a la carpeta `app/` de PHP para compartir fotos), `CORS_ORIGENES`, `PUERTO`.

## Qué hace (fase 1: la nueva operación)

| Pedido | Dónde |
|---|---|
| Prosegur deja de ser usuario | V2: rol 4 y su usuario inactivos; el login lo rechaza |
| Cargar el Excel de Prosegur del día siguiente | Menú «Excel de Prosegur». Reconoce columnas por encabezado (parámetro `excel_prosegur_columnas`), acepta IDs sin el cero inicial, marca repetidas y máquinas inexistentes |
| Pin para máquinas sin geolocalizar | «Ubicar máquinas». La carga no se confirma hasta que todas tengan ubicación; si ya la tienen, sigue normal |
| Prioridades: solicitudes > arrastrados > VIP > vencidas | Bolsa de servicios y vista `v_mantenimiento_pendiente` (frecuencia de la máquina o de su tipo) |
| Ruta óptima por motorizado | `Optimizador`: inserción más barata por prioridad + 2-opt, respeta la jornada; lo que no cabe queda en la bolsa. Distancias **por carretera** con un OSRM propio (`OSRM_URL`, ver [osrm/README.md](osrm/README.md)); sin él, línea recta × `factor_ruta_vial`. Las vencidas de relleno se eligen por cercanía con un premio por atraso (`km_por_dia_atraso`, 0.25 km por día, tope 120 días) |
| Lo no realizado pasa al día siguiente con prioridad | Tarea diaria 00:05 (y al generar rutas) |
| Supervisor y Auxiliar modifican siempre | Tablero con arrastrar y soltar, fijar, optimizar, publicar; todo queda en `orden_servicio_historial` |
| Flujo guiado sin saltarse pasos | `Etapa` + `CampoService`: el backend valida el orden; hora y GPS los pone el servidor |
| Espera máxima de 45 min | Después de 45 min solo se permite «No me dieron ingreso» → siguiente punto; antes no |
| Tipo de servicio, modalidad y horas automáticas | Definidos al programar/publicar; entrada al registrar ingreso, salida al cerrar |
| Verificar seriales | Compara con los esperados de la máquina; si no coinciden avisa al Supervisor (parámetro para bloquear) |
| Repuestos de su stock o de Prosegur | Valida y descuenta el stock con kardex (`movimiento_inventario`); devoluciones pendientes como hoy |
| Estado final y novedades | Actualiza la máquina (operativa / fuera de servicio) |
| Evidencia de todo | Foto obligatoria en llegada, seriales, estado inicial, trabajo, repuestos, estado final y sin ingreso; firma al cerrar |
| Google Maps al siguiente punto | Enlace desde la ubicación actual al cerrar o saltar un servicio |
| Seguimiento en vivo | «Seguimiento»: avance por ruta y alertas (espera, seriales, lejos del punto, sin reporte) |

## Roles (fijos desde V7)

Para dar acceso a alguien basta con crearlo en **Usuarios** y elegir su rol; los permisos no se editan. La pantalla
«Qué puede hacer cada rol» lo explica, con lo que incluye y lo que no.

| Rol | Puede |
|---|---|
| Superusuario | Todo: operación, servicios, personal, costos y administración (usuarios, catálogos, tarifas, parámetros) |
| Supervisor de motorizados (revisor) | Excel de Prosegur, pines, rutas y seguimiento; servicios; inventario y remisiones; transportes; reportes; además aprueba horas extra, asistencia y parqueaderos, y marca máquinas VIP |
| Auxiliar de oficina | Lo mismo que el Supervisor, sin la parte de personal ni las máquinas VIP |
| Motorizado | Su ruta del día y el servicio paso a paso en el celular |

Al crear un motorizado se elige su delegación y su ficha de técnico se crea sola (sin delegación no recibe rutas).
El rol Prosegur quedó retirado. Cada rol entra a un **inicio guiado**: la oficina ve los pasos para dejar listo el
próximo día hábil (Excel → pines → rutas → publicar) y cómo va hoy (seguimiento, servicios por revisar) con un botón a
cada tarea; el motorizado ve en «Mi día» sus tres pasos (iniciar jornada → ruta en orden → terminar).

## Decisiones tomadas por defecto (cambiables en la tabla `parametros`)

| Decisión pendiente | Valor actual |
|---|---|
| Arrastrados vs. solicitudes nuevas | Solicitudes de Prosegur siempre primero |
| VIP | Por máquina (pantalla «Máquinas VIP»), con su propia frecuencia opcional |
| Tipo de los vencidos | `tipo_mantenimiento_preventivo` = 1 (Preventivo básico) |
| Tipo si el Excel no lo dice | `tipo_mantenimiento_solicitud_default` = 3 (Correctivo) |
| Seriales distintos | `seriales_distintos_bloquean` = 0 (sigue con alerta) |
| Jornada y partida | 480 min desde las 07:00; sin punto de partida, cada motorizado sale del centro de una zona distinta de la ciudad base de su delegación. **Conviene registrar el punto de partida real de cada uno** (Catálogos → Técnicos) |
| Urbano / interurbano | Interurbano si el motorizado sale de la ciudad base de su delegación a otro municipio (p. ej. de Bogotá a Soacha), si el punto es de otra delegación o si el punto ya está marcado así. Cada municipio trae la marca `interurbano` (V5, calculada por el nombre: «CHAPINERO (BOGOTA)» urbano, «SOACHA (CUNDINAMARCA)» interurbano) y se corrige en Catálogos → Municipios. Ojo: las áreas metropolitanas (Soledad, Envigado, Floridablanca…) quedan interurbanas |
| Ubicación por dirección | Cuenta como ubicada; el pin la reemplaza |

## Fase 2: el resto de la aplicación PHP

`V3__plataforma_completa.sql` deja la base lista para que la plataforma nueva reemplace por completo a la PHP
(juego de caracteres único, roles con permisos por módulo, llaves foráneas que faltaban, saldo inicial del kardex,
reglas que estaban escritas en el código pasan a `parametros`). **Después de V3 la aplicación PHP ya no puede usar
esa base**: la PHP sigue con `inees_mantenimientos` y el paso a producción es un corte (migrar la base y cambiar de
aplicación el mismo día). `V4` agrega los correos de las solicitudes de repuestos, `V5` la marca de municipio
interurbano, `V6` el parámetro `km_por_dia_atraso` y `V7` los cuatro roles fijos.

| Módulo PHP (tabla `rutas`) | Plataforma nueva |
|---|---|
| ordenDetalle, ordenDetalleBuscar, ordenVer, ordenCrear, servicioEditar | Servicios: días, buscar, registrar, ficha del servicio (corrección y valorización) |
| pdfServicio, serviciosPdf | PDF por servicio y descarga en ZIP |
| exportarExcel, ordenReporte | Exportar servicios; Excel de puntos en Reportes |
| programacionCrear, programacionMapa, planeacionVer | Excel de Prosegur, Ubicar máquinas y Rutas del día (fase 1) |
| programacionConsolidado (Excel maestro) | Rutas del día → «Excel maestro» (rango de fechas, una fila por parada) |
| tecnicoProgramacion, tecnicoReporte, tecnicoReporteEditar, ordenMovil | Celular del motorizado: Mi día, servicio guiado e historial del punto |
| panelSupervisor, rastreoTecnico | Seguimiento y Rastreo |
| inventarioTecnico*, solicitarInventario, gestionDevolucion, reporteDevolucion | Inventario: stock con kardex, solicitudes y devoluciones |
| controlRemision*, remisionesPendientes | Remisiones y remisiones salteadas |
| horaExtra*, parqueadero*, turno*, cronometro*, asistencia | Personal: horas extra, parqueaderos, jornadas, tiempos, asistencia (y su parte en el celular) |
| notificacionesLogistica (y cron de alertas) | Alertas logísticas y tarea programada |
| transporte* | Transportes con su PDF |
| costos*, costosAdministrativos* | Costos |
| reporteFacturacion (analizador de cotizaciones) | Costos → «Analizador de cotizaciones» (solo permiso COSTOS, como en PHP solo el SuperUsuario) |
| reporteEjecutivo + generarReporte (PDF V-11) | Tablero ejecutivo → «PDF»: las 17 secciones de «Configurar reporte», A4 horizontal |
| reporteTecnico, reporteRepuesto(s), reporteMaquinas, reporteHistorial, reporteTrazabilidad, reporteTarifas | Reportes, cada uno con Excel y/o PDF |
| usuario*, tipoUsuario*, catálogos (*Crear/Ver/Editar/Eliminar), tarifa*, tarifaMasiva | Administración: usuarios, roles y permisos, catálogos genéricos, matriz de tarifas |
| importarExcel, importarEstadoMaquina, importarMunicipios, informacionBaseDatos, geolocalizar | Administración → Importar e Información; geocodificación |
| login, cambiarPassword, solicitarCodigo, resetPassword | Login, cambio de clave y recuperación por correo |

Los PDF se generan con openhtmltopdf (sin Chrome/Puppeteer en el servidor). El PDF ejecutivo replica las secciones
de la versión PHP; las gráficas son barras hechas con tablas. Dos diferencias a propósito: el estado de resultados
suma todos los tipos de servicio (PHP dejaba fuera «Nueva Arquitectura») y los viáticos, y la tabla de técnicos
muestra a todos (PHP cortaba en 15).

## Lo que sigue

- Formato real del Excel de Prosegur y un archivo real del control de cotizaciones (hoy probados con archivos de
  ejemplo: la lectura reconoce los encabezados por nombre).
- Registrar el punto de partida de cada motorizado (hoy ninguno lo tiene).
- Despliegue (servidor, HTTPS) y modo sin conexión para el celular.

## Ensayo del corte (12-11-2026)

Con el volcado de PHP del 06-11-2026 en una base nueva, la plataforma arrancó y Flyway aplicó V2…V6 en ~8 s.
Verificado con `corte/verificar_corte.sql`, antes y después:

- Ninguna tabla de datos cambió de número de filas. Diferencias esperadas: tablas nuevas, `tipousuario` → `rol`,
  se retiran `rutas` y `rol_permisos`, 2 estados de remisión y 21 parámetros nuevos, saldo inicial del kardex.
- 12.703 servicios, valor $1.668.186.135, viáticos $33.372.000 y 993.952 minutos de servicio: iguales.
- Stock 3.184 piezas = kardex, sin descuadres; 2.642 repuestos usados; remisiones por estado iguales.
- Textos (puntos, actividades, técnicos, usuarios) idénticos byte a byte. Dos puntos ya venían dañados desde PHP:
  «PAÃALERA» en los puntos 273 y 343 (corregir a «PAÑALERA» en Catálogos → Puntos).
- Roles y permisos con los mismos IDs de PHP; Prosegur inactivo.
- Los 100+ GET de la API respondieron sin errores sobre la base migrada; la prueba de punta a punta dio 52 de 52.
- Primer día de rutas en Bogotá con datos reales: 81 servicios en 9 rutas, 163 km en total (2 km por servicio) y
  10 paradas fuera de Bogotá. Antes del ajuste del optimizador eran 61 servicios, ~563 km y 46 paradas fuera.

Para el corte real: detener la app PHP, respaldar la base, correr la primera mitad de `verificar_corte.sql`, arrancar la
plataforma nueva contra esa base (`DB_URL`), correr la segunda mitad y comparar.

## Pruebas

- `backend`: `mvnw test` (20 pruebas: optimizador con el relleno por cercanía, distancias por carretera contra un OSRM
  simulado —bloques, respaldo en línea recta, ruta que evita un desvío—, orden de los pasos y analizador de cotizaciones).

## Revisión del 17-11-2026

- `V8`: el Excel diario de indisponibles de Prosegur (encabezado `DEVICE`, tipo en `COMENTARIOS`) se rechazaba
  completo; ahora se reconoce y «MANTENIMIENTO PROFUNDO» queda como Preventivo Profundo. Probado con el archivo
  real: 57 de 57 filas válidas.
- `verificar_corte.sql` otra vez contra el volcado del 06-11-2026: servicios, valores, viáticos, minutos, stock =
  kardex (0 descuadres), remisiones, máquinas y textos idénticos a la base PHP.
- Prueba de humo: 148 GET como Superusuario y 114 como motorizado, sin ningún error 500. Las únicas respuestas no 2xx
  fueron validaciones de parámetros y los 403 esperados por rol.
- Seguridad: `/api/listas/usuarios` (nombres de inicio de sesión y roles) quedó solo para Administración; antes la
  podía leer cualquier motorizado.
- El flujo completo de la fase 1 (carga → pines → rutas → publicar → servicio paso a paso → sin ingreso) se probó
  por la API contra `inees_plataforma`.
- Prueba de punta a punta del 12-11-2026 contra la API (50 de 50): Prosegur sin acceso, Excel con IDs sin cero inicial,
  repetidas e inexistentes, pin obligatorio antes de confirmar, prioridades (solicitud > arrastrado > VIP > vencida),
  rutas dentro de la jornada, cambios del Supervisor con historial, publicación, tipo de servicio y modalidad
  automáticos, flujo guiado sin saltos (jornada, GPS, fotos, 45 min, seriales, administrador, repuestos de stock y
  de Prosegur, estado final, novedad, cierre con horas automáticas), Google Maps al siguiente punto y lo no realizado
  al día siguiente con prioridad. Se corrió sobre un respaldo de `inees_plataforma` que luego se restauró.
- Prueba de humo del 12-11-2026: los 100+ GET de la API respondieron con datos reales (las únicas respuestas no 2xx
  fueron validaciones esperadas) y el motorizado recibe 403 en los módulos de oficina.
