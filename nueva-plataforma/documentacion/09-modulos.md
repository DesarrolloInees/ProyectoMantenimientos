# 9. Módulos funcionales

Qué hace cada módulo, qué páginas tiene, dónde está su código y qué tablas toca. Las rutas de backend son relativas a `backend/src/main/java/co/inees/mantenimientos/modulos/` y las de frontend a `frontend/src/app/paginas/`. La lista completa de endpoints está en [07-endpoints.md](07-endpoints.md) y los roles que ven cada página en [08-permisos-y-roles.md](08-permisos-y-roles.md).

## Índice

| Módulo | Páginas principales | Backend | Frontend |
| --- | --- | --- | --- |
| [Inicio](#inicio) | `inicio` | `inicio/` | `inicio/` |
| [Login y usuarios](#login-y-usuarios) | `login`, `usuarioVer` | `login/`, `usuario/` | `login/`, `usuario/` |
| [Catálogos y maestros](#catálogos-y-maestros) | `clienteVer`, `puntoVer`, `maquinaVer`, `tarifaVer`... | `admin/` | `admin/` |
| [Remisiones e inventario](#remisiones-e-inventario) | `controlRemisionVer`, `inventarioTecnicoVer`, `solicitarInventario` | `admin/RemisionInventarioController`, `inventario/` | `admin/remision-inventario.ts`, `inventario/` |
| [Programación](#programación) | `programacionCrear`, `programacionConsolidado`, `programacionMapa`, `planeacionVer` | `programacion/` | `programacion/` |
| [Técnico en campo](#técnico-en-campo) | `tecnicoProgramacion`, `tecnicoReporte`, turno | `tecnico/`, `turno/` | `tecnico/`, `layout/turno-modal.ts` |
| [Órdenes de servicio](#órdenes-de-servicio) | `ordenCrear`, `ordenDetalle`, `ordenVer`, `ordenMovil`, `serviciosPdf` | `orden/` | `orden/` |
| [Cronómetro](#cronómetro) | `cronometroCrear`, `cronometroAdmin`, `cronometroReportes` | `cronometro/` | `cronometro/` |
| [Supervisor y alertas](#supervisor-y-alertas) | `panelSupervisor`, `notificacionesLogistica` | `supervisor/`, `tareas/` | `supervisor/` |
| [Reportes](#reportes) | `reporteEjecutivo`, `generarReporte`, `reporteFacturacion`... | `reportes/` | `reportes/` |
| [Horas extra](#horas-extra) | `horaExtraCrear`, `horaExtraAdmin` | `horaextra/` | `hora-extra/` |
| [Parqueaderos](#parqueaderos) | `parqueaderoCrear`, `parqueaderoAdmin` | `parqueadero/` | `parqueadero/` |
| [Transportes](#transportes) | `transporteVer`, `transporteCrear` | `transporte/` | `transporte/` |
| [Costos](#costos) | `costosVer`, `costosAdministrativosVer` | `costos/` | `costos/` |
| [Asistencia](#asistencia) | `asistencia` | `asistencia/` | `asistencia/` + `public/js/asistencia/` |
| [Importaciones](#importaciones) | `importarExcel`, `importarEstadoMaquina`, `importarMunicipios` | `importar/` | `importar/` |
| [Herramientas de carga](#herramientas-de-carga) | `herramientasCarga` | `herramientas/` | `herramientas/` |
| [Varios](#varios) | `informacionBaseDatos`, `exportarExcel`, `geolocalizar`, `rastreoTecnico` | `varios/` | `varios/` |

---

## Inicio

Tablero de bienvenida con indicadores según el rol. Página `inicio` (Panel de Control). Backend `inicio/InicioController`, frontend `inicio/inicio.ts`.

## Login y usuarios

- **Login** (`login/LoginController`, `LoginModelo`; frontend `login/login.ts`, `login/recuperacion.ts`): inicio de sesión con bcrypt, cambio obligatorio de contraseña (`forzar_cambio_pwd`), recuperación por código enviado al correo (`solicitarCodigo` → `mensajeEnviado` → `resetPassword`), `/api/sesion` y `/api/logout`. Tablas: `usuarios`, `login`, `password_reset`.
- **Usuarios** (`usuario/UsuarioController`; `usuario/usuario.ts`): `usuarioVer`, `usuarioCrear`, `usuarioEditar/:id`. Crea usuarios con contraseña inicial y rol; eliminar es lógico (`estado = 'inactivo'`). Tablas: `usuarios`, `tipousuario`.

Detalle del flujo de sesión en [08-permisos-y-roles.md](08-permisos-y-roles.md#inicio-de-sesión).

## Catálogos y maestros

Todo en `admin/`.

| Página(s) | Qué administra | Controlador / componente | Tablas |
| --- | --- | --- | --- |
| `calificacionServicio*`, `delegacion*`, `estadoMaquina*`, `modalidadOperativa*`, `tecnico*`, `tipoMantenimiento*`, `tipoMaquina*`, `tipoUsuario*` (Ver, Crear, Editar, Eliminar) | Catálogos simples de un nombre | `CatalogosController` (definición declarativa por catálogo) / `admin/catalogos.ts` | La de cada catálogo |
| `clienteVer`, `clienteCrear`, `clienteEditar/:id` | Clientes | `ClienteController` / `admin/cliente.ts` | `cliente` |
| `puntoVer`, `puntoCrear`, `puntoEditar/:id` | Puntos: dirección, coordenadas, delegación, municipio, modalidad, frecuencia de mantenimiento | `MaquinaPuntoController` / `admin/maquina-punto.ts` | `punto`, `municipio`, `delegacion` |
| `maquinaVer`, `maquinaCrear`, `maquinaEditar/:id` | Máquinas por punto (device id, tipo, estado) | `MaquinaPuntoController` / `admin/maquina-punto.ts` | `maquina`, `tipo_maquina` |
| `repuestoVer`, `repuestoCrear`, `repuestoEditar/:id` | Catálogo de repuestos | `RepuestoTarifaController` / `admin/repuesto-tarifa.ts` | `repuesto` |
| `tarifaVer`, `tarifaCrear`, `tarifaEditar/:id`, `tarifaMasiva` | Precio por tipo de máquina × tipo de mantenimiento × modalidad × año; edición masiva | `RepuestoTarifaController` / `admin/repuesto-tarifa.ts` | `tarifa` |
| `tipoNovedadVer/Crear/Editar` | Tipos de novedad que reporta el técnico | `TipoNovedadFestivosController` / `admin/tipo-novedad.ts` | `tipo_novedad` |
| `diasFestivosVer/Crear/Editar` | Festivos (recargos y asistencia) | `TipoNovedadFestivosController` / `admin/tipo-novedad.ts` | `dias_festivos` |

Para agregar un catálogo simple nuevo ver [10-manual-de-cambios.md → Catálogo simple](10-manual-de-cambios.md#4-agregar-un-catálogo-simple).

## Remisiones e inventario

| Página | Qué hace | Tablas |
| --- | --- | --- |
| `controlRemisionVer` | Lista paginada en el servidor de todas las remisiones con su estado | `control_remisiones`, `estados_remision` |
| `controlRemisionCrear` | Asigna rangos de remisiones a un técnico | `control_remisiones` |
| `controlRemisionEditar` | Cambia estado o datos de una remisión | `control_remisiones` |
| `gestionDevolucion` | Recepción de devoluciones de repuestos | `control_devolucion_repuestos`, `inventario_tecnico` |
| `inventarioTecnicoVer`, `inventarioTecnicoCrear`, `inventarioTecnicoEditar` | Stock de repuestos en manos de cada técnico: asignación masiva y ajustes | `inventario_tecnico`, `repuesto` |
| `solicitarInventario` | El técnico pide repuestos; administración ve el tablero y cambia estados. Envía correo a los responsables | `solicitudes_inventario`, `solicitud_inventario_detalle` |

Backend: `admin/RemisionInventarioController`, `inventario/SolicitarInventarioController`. Frontend: `admin/remision-inventario.ts`, `inventario/solicitar-inventario.ts`.

## Programación

| Página | Qué hace |
| --- | --- |
| `programacionCrear` (Programación de Rutas Diarias) | Arma la programación por técnico y día: elige zonas, puntos y puntos aledaños; previsualiza y guarda órdenes con `estado = 2`. También restaura máquinas o puntos marcados fuera de operación. |
| `programacionConsolidado` | Lista todo lo programado: editar técnico, punto o fecha, eliminar órdenes programadas y exportar el Excel maestro |
| `programacionMapa` | Puntos en un mapa (Leaflet) para programar visualmente (beta) |
| `planeacionVer` | Planeación estratégica: estrategias y tareas en un diagrama de Gantt con avance y estado |

Backend: `programacion/ProgramacionCrearController`, `ProgramacionConsolidadoController`, `ProgramacionMapaController`, `PlaneacionController`. Tablas: `ordenes_servicio` (estado 2), `punto`, `maquina`, `tecnico`; planeación usa `estrategias`, `tareas_estrategia`, `estados_planeacion` y `arbol`.

> **Atención:** las cuatro tablas de planeación no existen en la copia local de la base revisada el 19/11/2026 (tampoco las crea ningún script del proyecto). Sin ellas `planeacionVer` abre vacía y no puede guardar. El PHP usa las mismas tablas, así que deben existir en el servidor donde se usaba esta página; hay que copiarlas desde ese respaldo.

## Técnico en campo

| Página / función | Qué hace el técnico | Tablas |
| --- | --- | --- |
| Botón "Marcar Entrada" (`turno`) | Registra la hora de llegada del día con GPS y novedad | `turno_tecnico` |
| `tecnicoProgramacion` (Mi Programación Diaria) | Ve sus servicios del día, inicia uno (guarda GPS de inicio), agrega servicios extra, elimina los que no corresponden | `ordenes_servicio`, `ordenes_servicio_complemento` |
| `tecnicoReporte` (Atender Servicio) | Reporta el servicio: estado de la máquina, actividades, fotos (se optimizan y giran a vertical), firma, repuestos usados, remisión, novedades, GPS de fin. Guarda un borrador mientras trabaja | `ordenes_servicio`, `ordenes_servicio_complemento`, `evidencia_servicio`, `orden_servicio_repuesto`, `orden_servicio_novedad`, `control_remisiones`, `inventario_tecnico` |
| `tecnicoReporteEditar` | Corrige un reporte ya enviado | Las mismas |
| `turnoReportes` (administración) | Reporte de turnos por técnico y fecha, con Excel y PDF | `turno_tecnico` |

Backend: `tecnico/TecnicoProgramacionController`, `TecnicoReporteController` (+ `TecnicoReporteModelo`), `TecnicoReporteEditarController`, `turno/TurnoController` (+ `TurnoModelo`). Frontend: `tecnico/tecnico-programacion.ts`, `tecnico-reporte.ts`, `tecnico-compartido.ts`, `turno/turno-reportes.ts`, `layout/turno-modal.ts`.

Las fotos se guardan en `uploads/imagenes_servicios/<año>/...` y la firma del técnico en `imagenes/firmas/`.

## Órdenes de servicio

| Página | Qué hace |
| --- | --- |
| `ordenCrear` (Reporte de Servicios) | Registro manual de servicios desde oficina: cliente, punto, máquina, tipo, precio calculado desde `tarifa` (con viáticos si es fuera de la delegación), repuestos del inventario del técnico, remisión; guarda un borrador |
| `ordenDetalle`, `ordenDetalle/:fecha` (Edición Total) | Edita en bloque los servicios de un día: horas, valores, repuestos, novedades; exporta a Excel; "mejorar texto con IA" (Groq) para las actividades |
| `ordenDetalleBuscar` | Buscador individual de servicios con la misma edición |
| `servicioEditar/:id` | Edita los complementos de una orden (seriales, administrador del punto, estado inicial) |
| `ordenVer` (Historial por Días) | Lista de servicios por fecha |
| `ordenMovil` (Consulta Técnica Móvil) | Historial de un punto desde el celular |
| `ordenReporte` (Generador de Reportes) | Descarga de servicios y novedades por rango |
| `serviciosPdf` (Generador de PDFs) | Genera PDFs de muchos servicios y los entrega en un ZIP, procesando por lotes con avance |
| `pdfServicio` | PDF individual de un servicio (Chrome). Para Prosegur oculta los meses recientes (`meses_restriccion_prosegur`) |

Backend: `orden/` (`OrdenCrearController`, `OrdenDetalleController` + `OrdenDetalleModelo`, `OrdenReporteController`, `OrdenVerMovilController`, `ServicioEditarController`, `ServiciosPdfController`, `PdfServicioController`, `ServicioPdfGenerator`). Plantilla CSS: `resources/plantillas/servicio-pdf.css`.

## Cronómetro

Mide en vivo la duración de cada servicio frente a su tiempo meta.

| Página | Qué hace |
| --- | --- |
| `cronometroCrear` (técnico) | Inicia y finaliza el cronómetro de un servicio; si cambia el tipo de mantenimiento en vivo, se registra; si se pasa de la meta, exige justificación |
| `cronometroHistorial` (técnico) | Sus tiempos anteriores |
| `cronometroAdmin` (supervisor) | Radar de servicios en curso y finalizados con filtros; alerta por Telegram |
| `cronometroReportes` | Rendimiento de la flota: PDF con gráfica (`accion=pdf`) y Excel (`accion=excel`) |

Backend: `cronometro/CronometroController`. Tabla: `monitoreo_motorizados_live`. Plantilla: `resources/plantillas/cronometro-pdf.css`.

## Supervisor y alertas

| Página / tarea | Qué hace |
| --- | --- |
| `panelSupervisor` | Panel en tiempo real: servicios del día por técnico; programar o cancelar servicios sobre la marcha |
| `notificacionesLogistica` | Detecta visitas frecuentes a un mismo punto y desplazamientos largos en los últimos 7 días y envía el correo de alerta a supervisores |
| Tarea `alertasLogisticas` | Ejecuta automáticamente lo anterior (por defecto a las 7:00) si `CRON_ALERTAS=true` |
| Tarea `alertasTelegram` | Cada 5 minutos avisa por Telegram los servicios en curso que superan su meta, si `CRON_TELEGRAM=true` |

Backend: `supervisor/SupervisorController`, `tareas/TareasProgramadas`.

## Reportes

| Página | Qué muestra | Salida |
| --- | --- | --- |
| `reporteEjecutivo` | Tablero con gráficas: servicios, novedades, puntos, clientes, top de fallas | Pantalla + 4 Excel |
| `generarReporte` | Elige secciones y genera el PDF ejecutivo (plantilla `reporte-ejecutivo.html`) | PDF |
| `reporteFacturacion` | Analizador de facturación por periodo, cliente y tipo | Pantalla, Excel, PDF (pdfmake) |
| `reporteTecnico` | Servicios por técnico | Pantalla, Excel, PDF |
| `reporteHistorial` | Historial de mantenimientos por punto | Pantalla, Excel |
| `reporteRepuesto`, `reporteRepuestos`, `reporteTrazabilidad` | Uso de repuestos, inventario y trazabilidad de lo suministrado | Pantalla, Excel, PDF |
| `reporteMaquinas`, `reporteTarifas`, `reporteDevolucion`, `remisionesPendientes` | Informes simples (por tipo de máquina, tarifas del mes, devoluciones, remisiones salteadas) | Pantalla, Excel |

Backend: `reportes/` (`ReporteEjecutivoController` + `ReporteEjecutivoModelo`, `GenerarReporteController`, `ReporteFacturacionController`, `ReporteTecnicoController`, `ReportesInventarioController`, `ReportesSimplesController`, `PlantillaTecnicoPdf`, `PlantillaRepuestosPdf`).

## Horas extra

| Página | Qué hace |
| --- | --- |
| `horaExtraCrear` (técnico) | Reporta horas extra con fotos de evidencia |
| `horaExtraHistorial` (técnico) | Ve, edita y borra evidencias de sus registros |
| `horaExtraAdmin` | Aprueba o rechaza, elimina, genera PDF (con fotos) y Excel con una hoja por técnico |

Backend: `horaextra/HoraExtraController`. Tablas: `registro_horas_extra`, `evidencia_horas_extra`, `estados_aprobacion_he`. Fotos en `app/uploads/` en una carpeta por técnico.

## Parqueaderos

`parqueaderoCrear` (el técnico sube la factura con foto), `parqueaderoHistorial` (edita o borra la foto), `parqueaderoAdmin` (consolida, rota fotos, exporta PDF y Excel con fotos). Backend `parqueadero/ParqueaderoController`, tabla `facturas_parqueadero`.

## Transportes

`transporteVer`, `transporteCrear` (con fotos y remisión usada), `transporteEditar` (incluida la tarifa) y el PDF `transportePdf`. Backend `transporte/TransporteController`, plantilla `transporte-pdf.css`. El componente `TransporteForm` sirve para crear y editar.

## Costos

`costosVer`, `costosCrear`, `costosEditar` (costos de los motorizados por mes y técnico) y `costosAdministrativosVer`, `costosAdministrativosCrear`, `costosAdministrativosEditar` (nómina y gastos administrativos). Backend `costos/CostosController`. Tablas `costos_operativos`, `gastos_administrativos`.

## Asistencia

Página `asistencia` (Procesador de Asistencias). Se sube el CSV del reloj biométrico (huellero); el sistema:

1. Agrupa las marcaciones por persona y día (primera entrada y última salida) y detecta el rango de fechas.
2. Cruza los nombres con técnicos y usuarios activos (coincidencia exacta, por partes del nombre o por similitud de texto ≥ 85 %).
3. Completa entradas y salidas con las horas de los servicios realizados ese día.
4. Marca domingos y festivos (`dias_festivos`).
5. Permite editar todo en pantalla (salario, horarios, tasas de recargo, horas) y descarga el Excel "Consolidado Nómina Mensual" con fórmulas: una hoja por empleado con bloques semanales y una hoja "Resumen Extras".

Backend `asistencia/AsistenciaController` (los datos procesados se guardan en la sesión entre pasos). Frontend `asistencia/asistencia.ts` + `public/js/asistencia/*.js` + `public/css/asistencia.css`. No escribe en la base de datos.

## Importaciones

| Página | Qué importa |
| --- | --- |
| `importarExcel` | Excel de instalaciones (C2D): crea o actualiza clientes, puntos y máquinas; primero simula y muestra diferencias; procesa por lotes de 200 filas; al finalizar puede dar de baja lo que ya no viene en el archivo |
| `importarEstadoMaquina` | Estado operativo de máquinas por device id (completa el id a 12 dígitos) |
| `importarMunicipios` | Actualización masiva de municipios de los puntos, con simulación previa |

Backend `importar/ImportarController` (usa `core/ExcelLector`). Frontend `importar/importar.ts` con una clase base común `ImportacionPorLotes`.

## Herramientas de carga

Página `herramientasCarga`, solo para el administrador. Son los antiguos scripts `helps/*.php` para cargas iniciales por CSV: clientes (con resolución de códigos repetidos), puntos, máquinas, delegaciones, municipios, actualizar la última visita de los puntos y el importador de tarifas y zonas.

> El importador de tarifas y zonas corresponde a un esquema anterior de la base (columna `id_tipo_zona`). Con la base actual se cancela sin modificar nada, porque el original vaciaba la tabla `tarifa` y luego fallaba.

Backend `herramientas/HerramientasCargaController`, frontend `herramientas/herramientas-carga.ts`.

## Varios

| Página | Qué hace |
| --- | --- |
| `informacionBaseDatos` | Estado de la base instalada: totales de clientes, puntos y máquinas |
| `exportarExcel` | Exportación general de datos a Excel |
| `geolocalizar` | Obtiene coordenadas de los puntos sin ubicación con Google Maps, de 5 en 5 (requiere `GOOGLE_MAPS_KEY`) |
| `rastreoTecnico` | Recorrido del día de un técnico en el mapa a partir del GPS de sus servicios, con exportación a Excel |

Backend `varios/VariosController`, frontend `varios/varios.ts`.
