# 7. Referencia de endpoints

Lista generada desde el código de los controladores: **344 endpoints** en **152 páginas**, agrupados por módulo y controlador. Las convenciones (URL, sesión, permisos, formato de respuesta) se explican en [04-backend.md](04-backend.md#ciclo-de-una-petición).

> Para regenerar este archivo después de agregar o cambiar endpoints: `powershell -ExecutionPolicy Bypass -File documentacion\herramientas\generar-endpoints.ps1`.

Cómo leer las tablas:

- **URL**: ruta del endpoint. La primera parte después de `/api/` es la *página* y debe existir en la tabla `rutas` (ver [08-permisos-y-roles.md](08-permisos-y-roles.md)).
- **accion**: valor del parámetro `?accion=`. "—" significa que el endpoint responde sin `accion` (o con cualquier otra).
- **Método**: `GET`, `POST` o `GET/POST` (acepta los dos).
- **Método Java**: `Controlador.metodo`, para ubicar el código.

## Endpoints que no son páginas

| URL | Método | Qué hace |
| --- | --- | --- |
| `/api/sesion` | GET | Estado de la sesión: usuario, rol y lista de páginas permitidas. Lo llama Angular al cargar. |
| `/api/logout` | GET/POST | Cierra la sesión (equivale a `logout` del PHP). |
| `/api/{pagina}` con `accion` | — | Todo lo demás, listado abajo. En `pdfServicio`, "`accion`" en la columna accion significa que acepta cualquier valor. |
| `/`, `/{pagina}`, `/{pagina}/{param}` | GET | Devuelve `index.html` para que Angular resuelva la ruta (`core/SpaController`). |
| `/uploads/**`, `/app/uploads/**`, `/imagenes/**`, `/app/imagenes/**`, `/app/logos/**`, `/app/temp/**` | GET | Archivos subidos, servidos desde las mismas carpetas del PHP (`core/WebConfig`). |

## Índice de módulos

- [admin](#admin) — 97 endpoints
- [asistencia](#asistencia) — 4 endpoints
- [costos](#costos) — 15 endpoints
- [cronometro](#cronometro) — 11 endpoints
- [herramientas](#herramientas) — 9 endpoints
- [horaextra](#horaextra) — 10 endpoints
- [importar](#importar) — 11 endpoints
- [inicio](#inicio) — 1 endpoints
- [inventario](#inventario) — 3 endpoints
- [login](#login) — 9 endpoints
- [orden](#orden) — 59 endpoints
- [parqueadero](#parqueadero) — 9 endpoints
- [programacion](#programacion) — 25 endpoints
- [reportes](#reportes) — 18 endpoints
- [supervisor](#supervisor) — 11 endpoints
- [tecnico](#tecnico) — 23 endpoints
- [transporte](#transporte) — 12 endpoints
- [turno](#turno) — 5 endpoints
- [usuario](#usuario) — 6 endpoints
- [varios](#varios) — 6 endpoints

## admin

### CatalogosController

Catálogos simples de administración (Ver / Crear / Editar / Eliminar), con el mismo SQL que los modelos PHP de app/models/admin: calificacionServicio, delegacion, estadoMaquina, modalidadOperativa, tecnico, tipoMantenimiento, tipoMaquina, tipoUsuario.

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/admin/CatalogosController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/calificacionServicioCrear` | POST | — | `CatalogosController.crear` |
| `/api/calificacionServicioEditar` | GET | — | `CatalogosController.obtener` |
| `/api/calificacionServicioEditar` | POST | — | `CatalogosController.editar` |
| `/api/calificacionServicioEliminar` | POST | — | `CatalogosController.eliminar` |
| `/api/calificacionServicioVer` | GET | — | `CatalogosController.ver` |
| `/api/delegacionCrear` | POST | — | `CatalogosController.crear` |
| `/api/delegacionEditar` | POST | — | `CatalogosController.editar` |
| `/api/delegacionEditar` | GET | — | `CatalogosController.obtener` |
| `/api/delegacionEliminar` | POST | — | `CatalogosController.eliminar` |
| `/api/delegacionVer` | GET | — | `CatalogosController.ver` |
| `/api/estadoMaquinaCrear` | POST | — | `CatalogosController.crear` |
| `/api/estadoMaquinaEditar` | GET | — | `CatalogosController.obtener` |
| `/api/estadoMaquinaEditar` | POST | — | `CatalogosController.editar` |
| `/api/estadoMaquinaEliminar` | POST | — | `CatalogosController.eliminar` |
| `/api/estadoMaquinaVer` | GET | — | `CatalogosController.ver` |
| `/api/modalidadOperativaCrear` | POST | — | `CatalogosController.crear` |
| `/api/modalidadOperativaEditar` | GET | — | `CatalogosController.obtener` |
| `/api/modalidadOperativaEditar` | POST | — | `CatalogosController.editar` |
| `/api/modalidadOperativaEliminar` | POST | — | `CatalogosController.eliminar` |
| `/api/modalidadOperativaVer` | GET | — | `CatalogosController.ver` |
| `/api/modalidadOPerativaVer` | GET | — | `CatalogosController.ver` |
| `/api/tecnicoCrear` | POST | — | `CatalogosController.crear` |
| `/api/tecnicoEditar` | POST | — | `CatalogosController.editar` |
| `/api/tecnicoEditar` | GET | — | `CatalogosController.obtener` |
| `/api/tecnicoEliminar` | POST | — | `CatalogosController.eliminar` |
| `/api/tecnicoVer` | GET | — | `CatalogosController.ver` |
| `/api/tipoMantenimientoCrear` | POST | — | `CatalogosController.crear` |
| `/api/tipoMantenimientoEditar` | POST | — | `CatalogosController.editar` |
| `/api/tipoMantenimientoEditar` | GET | — | `CatalogosController.obtener` |
| `/api/tipoMantenimientoEliminar` | POST | — | `CatalogosController.eliminar` |
| `/api/tipoMantenimientoVer` | GET | — | `CatalogosController.ver` |
| `/api/tipoMaquinaCrear` | POST | — | `CatalogosController.crear` |
| `/api/tipoMaquinaEditar` | GET | — | `CatalogosController.obtener` |
| `/api/tipoMaquinaEditar` | POST | — | `CatalogosController.editar` |
| `/api/tipoMaquinaEliminar` | POST | — | `CatalogosController.eliminar` |
| `/api/tipoMaquinaVer` | GET | — | `CatalogosController.ver` |
| `/api/tipoUsuarioCrear` | POST | — | `CatalogosController.crear` |
| `/api/tipoUsuarioEditar` | GET | — | `CatalogosController.obtener` |
| `/api/tipoUsuarioEditar` | POST | — | `CatalogosController.editar` |
| `/api/tipoUsuarioEliminar` | POST | — | `CatalogosController.eliminar` |
| `/api/tipoUsuarioVer` | GET | — | `CatalogosController.ver` |

### ClienteController

Port de cliente{Ver,Crear,Editar,Eliminar}Controlador + modelos.

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/admin/ClienteController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/clienteCrear` | POST | — | `ClienteController.crear` |
| `/api/clienteEditar` | POST | — | `ClienteController.editar` |
| `/api/clienteEditar` | GET | — | `ClienteController.obtener` |
| `/api/clienteEliminar` | POST | — | `ClienteController.eliminar` |
| `/api/clienteVer` | GET | — | `ClienteController.ver` |

### MaquinaPuntoController

Port de maquina{Ver,Crear,Editar,Eliminar} y punto{Ver,Crear,Editar,Eliminar}.

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/admin/MaquinaPuntoController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/maquinaCrear` | GET | — | `MaquinaPuntoController.maquinaCrearForm` |
| `/api/maquinaCrear` | POST | — | `MaquinaPuntoController.maquinaCrear` |
| `/api/maquinaEditar` | GET | — | `MaquinaPuntoController.maquinaEditarForm` |
| `/api/maquinaEditar` | POST | — | `MaquinaPuntoController.maquinaEditar` |
| `/api/maquinaEliminar` | POST | — | `MaquinaPuntoController.maquinaEliminar` |
| `/api/maquinaVer` | GET | — | `MaquinaPuntoController.maquinaVer` |
| `/api/puntoCrear` | GET | — | `MaquinaPuntoController.puntoCrearForm` |
| `/api/puntoCrear` | POST | — | `MaquinaPuntoController.puntoCrear` |
| `/api/puntoEditar` | GET | — | `MaquinaPuntoController.puntoEditarForm` |
| `/api/puntoEditar` | POST | — | `MaquinaPuntoController.puntoEditar` |
| `/api/puntoEliminar` | POST | — | `MaquinaPuntoController.puntoEliminar` |
| `/api/puntoVer` | GET | — | `MaquinaPuntoController.puntoVer` |

### RemisionInventarioController

Port de controlRemision{Ver,Crear,Editar,Eliminar}, inventarioTecnico{Ver,Crear,Editar,Eliminar} y gestionDevolucion.

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/admin/RemisionInventarioController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/controlRemisionCrear` | GET | `index` | `RemisionInventarioController.remisionCrearForm` |
| `/api/controlRemisionCrear` | POST | — | `RemisionInventarioController.remisionCrear` |
| `/api/controlRemisionCrear` | GET | `getUltima` | `RemisionInventarioController.ultimaRemision` |
| `/api/controlRemisionEditar` | POST | — | `RemisionInventarioController.remisionEditar` |
| `/api/controlRemisionEditar` | GET | — | `RemisionInventarioController.remisionEditarForm` |
| `/api/controlRemisionEliminar` | POST | — | `RemisionInventarioController.remisionEliminar` |
| `/api/controlRemisionVer` | GET | `obtenerDatosDatatable` | `RemisionInventarioController.remisionesDatatable` |
| `/api/gestionDevolucion` | POST | `index` | `RemisionInventarioController.gestionDevolucion` |
| `/api/gestionDevolucion` | POST | `ajaxProcesarDevolucion` | `RemisionInventarioController.procesarDevolucion` |
| `/api/inventarioTecnicoCrear` | GET | — | `RemisionInventarioController.inventarioCrearForm` |
| `/api/inventarioTecnicoCrear` | POST | — | `RemisionInventarioController.inventarioCrear` |
| `/api/inventarioTecnicoEditar` | POST | — | `RemisionInventarioController.inventarioEditar` |
| `/api/inventarioTecnicoEditar` | GET | — | `RemisionInventarioController.inventarioEditarForm` |
| `/api/inventarioTecnicoEliminar` | POST | — | `RemisionInventarioController.inventarioEliminar` |
| `/api/inventarioTecnicoVer` | GET | — | `RemisionInventarioController.inventarioVer` |

### RepuestoTarifaController

Port de repuesto{Ver,Crear,Editar,Eliminar}, tarifa{Ver,Crear,Editar,Eliminar} y tarifaMasiva.

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/admin/RepuestoTarifaController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/repuestoCrear` | POST | — | `RepuestoTarifaController.repuestoCrear` |
| `/api/repuestoEditar` | POST | — | `RepuestoTarifaController.repuestoEditar` |
| `/api/repuestoEditar` | GET | — | `RepuestoTarifaController.repuestoEditarForm` |
| `/api/repuestoEliminar` | POST | — | `RepuestoTarifaController.repuestoEliminar` |
| `/api/repuestoVer` | GET | — | `RepuestoTarifaController.repuestoVer` |
| `/api/tarifaCrear` | GET | `index` | `RepuestoTarifaController.tarifaCrearForm` |
| `/api/tarifaCrear` | POST | — | `RepuestoTarifaController.tarifaCrear` |
| `/api/tarifaCrear` | GET | `verificarMaquinas` | `RepuestoTarifaController.verificarMaquinas` |
| `/api/tarifaEditar` | POST | — | `RepuestoTarifaController.tarifaEditar` |
| `/api/tarifaEditar` | GET | — | `RepuestoTarifaController.tarifaEditarForm` |
| `/api/tarifaEliminar` | POST | — | `RepuestoTarifaController.tarifaEliminar` |
| `/api/tarifaMasiva` | GET | — | `RepuestoTarifaController.tarifaMasiva` |
| `/api/tarifaMasiva` | POST | — | `RepuestoTarifaController.tarifaMasivaGuardar` |
| `/api/tarifaVer` | GET | — | `RepuestoTarifaController.tarifaVer` |

### TipoNovedadFestivosController

Port de tipoNovedad{Ver,Crear,Editar,Eliminar} y diasFestivos{Ver,Crear,Editar,Eliminar}.

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/admin/TipoNovedadFestivosController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/diasFestivosCrear` | POST | — | `TipoNovedadFestivosController.festivoCrear` |
| `/api/diasFestivosEditar` | POST | — | `TipoNovedadFestivosController.festivoEditar` |
| `/api/diasFestivosEditar` | GET | — | `TipoNovedadFestivosController.festivoObtener` |
| `/api/diasFestivosEliminar` | POST | — | `TipoNovedadFestivosController.festivoEliminar` |
| `/api/diasFestivosVer` | GET | — | `TipoNovedadFestivosController.festivosVer` |
| `/api/tipoNovedadCrear` | POST | — | `TipoNovedadFestivosController.novedadCrear` |
| `/api/tipoNovedadEditar` | POST | — | `TipoNovedadFestivosController.novedadEditar` |
| `/api/tipoNovedadEditar` | GET | — | `TipoNovedadFestivosController.novedadObtener` |
| `/api/tipoNovedadEliminar` | POST | — | `TipoNovedadFestivosController.novedadEliminar` |
| `/api/tipoNovedadVer` | GET | — | `TipoNovedadFestivosController.novedadVer` |

## asistencia

### AsistenciaController

Port de asistenciaControlador.php + asistenciaModelo.php: procesa el CSV del huellero, lo cruza con empleados y servicios de la BD, guarda la edición en vivo en sesión y genera el Excel "Consolidado Nómina Mensual (con recargos)" con las mismas fórmulas de la plantilla.

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/asistencia/AsistenciaController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/asistencia` | GET | `index` | `AsistenciaController.index` |
| `/api/asistencia` | GET | `descargarExcel` | `AsistenciaController.descargarExcel` |
| `/api/asistencia` | POST | `guardarEdicion` | `AsistenciaController.guardarEdicion` |
| `/api/asistencia` | POST | `procesarArchivo` | `AsistenciaController.procesarArchivo` |

## costos

### CostosController

Port de app/controllers/costos/* (costosVer, costosCrear, costosEditar, costosAdministrativosVer, costosAdministrativosCrear, costosAdministrativosEditar) y sus modelos.

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/costos/CostosController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/costosAdministrativosCrear` | GET/POST | `index` | `CostosController.admCrearIndex` |
| `/api/costosAdministrativosCrear` | POST | `guardar_gasto` | `CostosController.admGuardarGasto` |
| `/api/costosAdministrativosCrear` | POST | `guardar_nomina` | `CostosController.admGuardarNomina` |
| `/api/costosAdministrativosEditar` | GET/POST | `index` | `CostosController.admEditarIndex` |
| `/api/costosAdministrativosEditar` | POST | `actualizar_gastos_generales` | `CostosController.admActualizarGastos` |
| `/api/costosAdministrativosEditar` | POST | `actualizar_nomina` | `CostosController.admActualizarNomina` |
| `/api/costosAdministrativosVer` | GET | `index` | `CostosController.admVerIndex` |
| `/api/costosAdministrativosVer` | GET/POST | `eliminarMes` | `CostosController.admVerEliminarMes` |
| `/api/costosCrear` | POST | `index` | `CostosController.crearGuardar` |
| `/api/costosCrear` | GET | `index` | `CostosController.crearIndex` |
| `/api/costosEditar` | GET/POST | `index` | `CostosController.editarIndex` |
| `/api/costosVer` | GET | `index` | `CostosController.verIndex` |
| `/api/costosVer` | GET | `detalle` | `CostosController.verDetalle` |
| `/api/costosVer` | GET/POST | `eliminar` | `CostosController.verEliminar` |
| `/api/costosVer` | GET/POST | `eliminarMes` | `CostosController.verEliminarMes` |

## cronometro

### CronometroController

Port de app/controllers/cronometro/* (cronometroCrear, cronometroHistorial, cronometroAdmin, cronometroReportes, cronometroPdf, cronometroExcel) y sus modelos.

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/cronometro/CronometroController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/cronometroAdmin` | GET | `index` | `CronometroController.admin` |
| `/api/cronometroCrear` | GET | `index` | `CronometroController.crearIndex` |
| `/api/cronometroCrear` | POST | `actualizarTipoVivo` | `CronometroController.actualizarTipoVivo` |
| `/api/cronometroCrear` | POST | `finalizar` | `CronometroController.finalizar` |
| `/api/cronometroCrear` | POST | `iniciar` | `CronometroController.iniciar` |
| `/api/cronometroExcel` | GET | `generar` | `CronometroController.generarExcel` |
| `/api/cronometroHistorial` | GET | `index` | `CronometroController.historial` |
| `/api/cronometroPdf` | GET | `generar` | `CronometroController.generarPdf` |
| `/api/cronometroReportes` | GET | `index` | `CronometroController.reportes` |
| `/api/cronometroReportes` | GET | `excel` | `CronometroController.reportesExcel` |
| `/api/cronometroReportes` | GET | `pdf` | `CronometroController.reportesPdf` |

## herramientas

### HerramientasCargaController

Port de los scripts sueltos de helps/*.php (cargas iniciales por CSV): importar_clientes, importar_puntos, importar_maquinas, importar_delegaciones, importar_municipios, importar_parametros y actualizar_puntos. Mismas consultas y reglas; solo accesible para el administrador.

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/herramientas/HerramientasCargaController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/herramientasCarga` | GET | `index` | `HerramientasCargaController.index` |
| `/api/herramientasCarga` | POST | `actualizarPuntos` | `HerramientasCargaController.actualizarPuntos` |
| `/api/herramientasCarga` | POST | `clientesAnalizar` | `HerramientasCargaController.clientesAnalizar` |
| `/api/herramientasCarga` | POST | `clientesGuardar` | `HerramientasCargaController.clientesGuardar` |
| `/api/herramientasCarga` | POST | `delegaciones` | `HerramientasCargaController.delegaciones` |
| `/api/herramientasCarga` | POST | `maquinas` | `HerramientasCargaController.maquinas` |
| `/api/herramientasCarga` | POST | `municipios` | `HerramientasCargaController.municipios` |
| `/api/herramientasCarga` | POST | `parametros` | `HerramientasCargaController.parametros` |
| `/api/herramientasCarga` | POST | `puntos` | `HerramientasCargaController.puntos` |

## horaextra

### HoraExtraController

Port de app/controllers/horaExtra/* (horaExtraCrear, horaExtraHistorial, horaExtraAdmin, horaExtraGenerar = PDF, horaExtraExcel) y sus modelos.

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/horaextra/HoraExtraController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/horaExtraAdmin` | GET | `index` | `HoraExtraController.admin` |
| `/api/horaExtraAdmin` | POST | `ajaxCambiarEstado` | `HoraExtraController.cambiarEstado` |
| `/api/horaExtraAdmin` | POST | `ajaxEliminarRegistro` | `HoraExtraController.eliminarRegistro` |
| `/api/horaExtraCrear` | GET | `index` | `HoraExtraController.crearIndex` |
| `/api/horaExtraCrear` | POST | `guardar` | `HoraExtraController.guardar` |
| `/api/horaExtraExcel` | GET | `generar` | `HoraExtraController.generarExcel` |
| `/api/horaExtraGenerar` | GET | `generar` | `HoraExtraController.generarPdf` |
| `/api/horaExtraHistorial` | GET | `index` | `HoraExtraController.historial` |
| `/api/horaExtraHistorial` | POST | `ajaxEditarRegistro` | `HoraExtraController.editarRegistro` |
| `/api/horaExtraHistorial` | POST | `ajaxEliminarEvidencia` | `HoraExtraController.eliminarEvidencia` |

## importar

### ImportarController

Port de importarExcelControlador.php, importarEstadoMaquinaControlador.php e importarMunicipiosControlador.php (con sus modelos). Las filas del Excel se guardan en la sesión en lugar de releer el archivo temporal en cada lote.

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/importar/ImportarController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/importarEstadoMaquina` | GET | `index` | `ImportarController.estadoIndex` |
| `/api/importarEstadoMaquina` | POST | `finalizarImportacion` | `ImportarController.estadoFinalizar` |
| `/api/importarEstadoMaquina` | POST | `procesarLote` | `ImportarController.estadoLote` |
| `/api/importarEstadoMaquina` | POST | `subirArchivo` | `ImportarController.estadoSubir` |
| `/api/importarExcel` | GET | `index` | `ImportarController.excelIndex` |
| `/api/importarExcel` | POST | `finalizarImportacion` | `ImportarController.excelFinalizar` |
| `/api/importarExcel` | POST | `procesarLote` | `ImportarController.excelLote` |
| `/api/importarExcel` | POST | `subirArchivo` | `ImportarController.excelSubir` |
| `/api/importarMunicipios` | GET | `index` | `ImportarController.municipiosIndex` |
| `/api/importarMunicipios` | POST | `ejecutarActualizacion` | `ImportarController.municipiosEjecutar` |
| `/api/importarMunicipios` | POST | `generarSimulacion` | `ImportarController.municipiosSimular` |

## inicio

### InicioController

Port de inicioControlador.php + inicioModelo.php

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/inicio/InicioController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/inicio` | GET | `index` | `InicioController.index` |

## inventario

### SolicitarInventarioController

Port de solicitarInventarioControlador.php + solicitarInventarioModelo.php

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/inventario/SolicitarInventarioController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/solicitarInventario` | GET | `index` | `SolicitarInventarioController.index` |
| `/api/solicitarInventario` | POST | `cambiarEstado` | `SolicitarInventarioController.cambiarEstado` |
| `/api/solicitarInventario` | POST | `crearSolicitud` | `SolicitarInventarioController.crearSolicitud` |

## login

### LoginController

Port de: loginControlador, cambiarPasswordControlador, procesarCambioPasswordControlador, solicitarCodigoControlador, enviarCodigoControlador, resetPasswordControlador, y la lógica de logout / sesión de index.php.

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/login/LoginController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/cambiarPassword` | GET | — | `LoginController.cambiarPassword` |
| `/api/enviarCodigo` | POST | `enviarCodigo` | `LoginController.enviarCodigo` |
| `/api/login` | POST | — | `LoginController.procesarLogin` |
| `/api/logout` | POST | — | `LoginController.logout` |
| `/api/logout` | GET | — | `LoginController.logoutGet` |
| `/api/procesarCambioPassword` | POST | — | `LoginController.procesarCambioPassword` |
| `/api/resetPassword` | POST | — | `LoginController.procesarResetPassword` |
| `/api/sesion` | GET | — | `LoginController.sesion` |
| `/api/solicitarCodigo` | POST | `enviarCodigo` | `LoginController.enviarCodigo` |

## orden

### OrdenCrearController

Port de ordenCrearControlador.php + ordenCrearModelo.php

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/orden/OrdenCrearController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/ordenCrear` | GET | `index` | `OrdenCrearController.cargarVista` |
| `/api/ordenCrear` | POST | `ajaxCalcularPrecio` | `OrdenCrearController.ajaxCalcularPrecio` |
| `/api/ordenCrear` | POST | `ajaxGuardarJSON` | `OrdenCrearController.ajaxGuardarJSON` |
| `/api/ordenCrear` | POST | `ajaxInventarioTecnico` | `OrdenCrearController.ajaxInventarioTecnico` |
| `/api/ordenCrear` | POST | `ajaxMaquinas` | `OrdenCrearController.ajaxMaquinas` |
| `/api/ordenCrear` | POST | `ajaxProgramacion` | `OrdenCrearController.ajaxProgramacion` |
| `/api/ordenCrear` | POST | `ajaxPuntos` | `OrdenCrearController.ajaxPuntos` |
| `/api/ordenCrear` | POST | `ajaxRemisiones` | `OrdenCrearController.ajaxRemisiones` |
| `/api/ordenCrear` | POST | `ajaxValidarRemision` | `OrdenCrearController.ajaxValidarRemision` |
| `/api/ordenCrear` | POST | `guardar` | `OrdenCrearController.guardar` |

### OrdenDetalleController

Port de ordenDetalleControlador.php y ordenDetalleBuscarControlador.php (ordenDetalleBuscar reutiliza el mismo modelo y las mismas acciones AJAX).

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/orden/OrdenDetalleController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/ordenDetalle` | GET | `index` | `OrdenDetalleController.cargarVista` |
| `/api/ordenDetalle` | POST | `ajaxBuscarOrdenes` | `OrdenDetalleController.ajaxBuscarOrdenes` |
| `/api/ordenDetalle` | POST | `ajaxExportarBusqueda` | `OrdenDetalleController.ajaxExportarBusqueda` |
| `/api/ordenDetalle` | POST | `ajaxExportarDetalle` | `OrdenDetalleController.ajaxExportarDetalle` |
| `/api/ordenDetalle` | POST | `ajaxGestionarRepuestoRT` | `OrdenDetalleController.ajaxGestionarRepuestoRT` |
| `/api/ordenDetalle` | POST | `ajaxGuardarCambiosJSON` | `OrdenDetalleController.ajaxGuardarCambiosJSON` |
| `/api/ordenDetalle` | POST | `ajaxGuardarNovedad` | `OrdenDetalleController.ajaxGuardarNovedad` |
| `/api/ordenDetalle` | POST | `ajaxMejorarTextoIA` | `OrdenDetalleController.ajaxMejorarTextoIA` |
| `/api/ordenDetalle` | POST | `ajaxObtenerDelegacion` | `OrdenDetalleController.ajaxObtenerDelegacion` |
| `/api/ordenDetalle` | POST | `ajaxObtenerMaquinas` | `OrdenDetalleController.ajaxObtenerMaquinas` |
| `/api/ordenDetalle` | POST | `ajaxObtenerPrecio` | `OrdenDetalleController.ajaxObtenerPrecio` |
| `/api/ordenDetalle` | POST | `ajaxObtenerPuntos` | `OrdenDetalleController.ajaxObtenerPuntos` |
| `/api/ordenDetalle` | POST | `ajaxObtenerRemisiones` | `OrdenDetalleController.ajaxObtenerRemisiones` |
| `/api/ordenDetalle` | POST | `ajaxObtenerStockTecnico` | `OrdenDetalleController.ajaxObtenerStockTecnico` |
| `/api/ordenDetalle` | POST | `guardarCambios` | `OrdenDetalleController.guardarCambios` |
| `/api/ordenDetalleBuscar` | GET | `index` | `OrdenDetalleController.cargarVistaBusqueda` |
| `/api/ordenDetalleBuscar` | POST | `ajaxBuscarOrdenes` | `OrdenDetalleController.ajaxBuscarOrdenes` |
| `/api/ordenDetalleBuscar` | POST | `ajaxExportarBusqueda` | `OrdenDetalleController.ajaxExportarBusqueda` |
| `/api/ordenDetalleBuscar` | POST | `ajaxExportarDetalle` | `OrdenDetalleController.ajaxExportarDetalle` |
| `/api/ordenDetalleBuscar` | POST | `ajaxGestionarRepuestoRT` | `OrdenDetalleController.ajaxGestionarRepuestoRT` |
| `/api/ordenDetalleBuscar` | POST | `ajaxGuardarCambiosJSON` | `OrdenDetalleController.ajaxGuardarCambiosJSON` |
| `/api/ordenDetalleBuscar` | POST | `ajaxGuardarNovedad` | `OrdenDetalleController.ajaxGuardarNovedad` |
| `/api/ordenDetalleBuscar` | POST | `ajaxMejorarTextoIA` | `OrdenDetalleController.ajaxMejorarTextoIA` |
| `/api/ordenDetalleBuscar` | POST | `ajaxObtenerDelegacion` | `OrdenDetalleController.ajaxObtenerDelegacion` |
| `/api/ordenDetalleBuscar` | POST | `ajaxObtenerMaquinas` | `OrdenDetalleController.ajaxObtenerMaquinas` |
| `/api/ordenDetalleBuscar` | POST | `ajaxObtenerPrecio` | `OrdenDetalleController.ajaxObtenerPrecio` |
| `/api/ordenDetalleBuscar` | POST | `ajaxObtenerPuntos` | `OrdenDetalleController.ajaxObtenerPuntos` |
| `/api/ordenDetalleBuscar` | POST | `ajaxObtenerRemisiones` | `OrdenDetalleController.ajaxObtenerRemisiones` |
| `/api/ordenDetalleBuscar` | POST | `ajaxObtenerStockTecnico` | `OrdenDetalleController.ajaxObtenerStockTecnico` |
| `/api/ordenDetalleBuscar` | POST | `guardarCambios` | `OrdenDetalleController.guardarCambios` |

### OrdenReporteController

Port de ordenReporteControlador.php + ordenReporteModelo.php

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/orden/OrdenReporteController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/ordenReporte` | GET | `index` | `OrdenReporteController.cargarVista` |
| `/api/ordenReporte` | POST | `ajaxDescargarNovedades` | `OrdenReporteController.ajaxDescargarNovedades` |
| `/api/ordenReporte` | POST | `ajaxDescargarServicios` | `OrdenReporteController.ajaxDescargarServicios` |

### OrdenVerMovilController

Port de ordenVerControlador/ordenVerModelo y ordenMovilControlador/ordenMovilModelo.

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/orden/OrdenVerMovilController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/ordenMovil` | GET | `index` | `OrdenVerMovilController.ordenMovil` |
| `/api/ordenMovil` | POST | `buscarHistorial` | `OrdenVerMovilController.buscarHistorial` |
| `/api/ordenMovil` | POST | `cargarPuntos` | `OrdenVerMovilController.cargarPuntos` |
| `/api/ordenVer` | GET | `index` | `OrdenVerMovilController.ordenVer` |
| `/api/ordenVer` | POST | `ajaxListar` | `OrdenVerMovilController.ajaxListar` |

### PdfServicioController

Port de pdfServicioControlador.php (PDF individual de un servicio, en línea)

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/orden/PdfServicioController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/pdfServicio` | GET | — | `PdfServicioController.generarSinAccion` |
| `/api/pdfServicio` | GET | `accion` | `PdfServicioController.generar` |

### ServicioEditarController

Port de servicioEditarControlador.php + servicioEditarModelo.php

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/orden/ServicioEditarController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/servicioEditar` | GET | `index` | `ServicioEditarController.index` |
| `/api/servicioEditar` | POST | `guardar` | `ServicioEditarController.guardar` |

### ServiciosPdfController

Port de serviciosPdfControlador.php + serviciosPdfModelo.php (descarga masiva de PDFs en ZIP por lotes)

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/orden/ServiciosPdfController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/serviciosPdf` | GET | `index` | `ServiciosPdfController.cargarVista` |
| `/api/serviciosPdf` | GET/POST | `ajaxListar` | `ServiciosPdfController.ajaxListar` |
| `/api/serviciosPdf` | GET/POST | `ajaxPuntos` | `ServiciosPdfController.ajaxPuntos` |
| `/api/serviciosPdf` | GET/POST | `cancelarTrabajoZip` | `ServiciosPdfController.cancelarTrabajoZip` |
| `/api/serviciosPdf` | POST | `crearTrabajoZip` | `ServiciosPdfController.crearTrabajoZip` |
| `/api/serviciosPdf` | GET/POST | `descargarZipTrabajo` | `ServiciosPdfController.descargarZipTrabajo` |
| `/api/serviciosPdf` | POST | `procesarLoteZip` | `ServiciosPdfController.procesarLoteZip` |

## parqueadero

### ParqueaderoController

Port de app/controllers/parqueadero/* (parqueaderoCrear, parqueaderoHistorial, parqueaderoAdmin + PDF/Excel/rotación).

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/parqueadero/ParqueaderoController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/parqueaderoAdmin` | GET | `index` | `ParqueaderoController.admin` |
| `/api/parqueaderoAdmin` | GET | `exportarExcel` | `ParqueaderoController.exportarExcel` |
| `/api/parqueaderoAdmin` | GET | `exportarPdf` | `ParqueaderoController.exportarPdf` |
| `/api/parqueaderoAdmin` | POST | `guardarRotacion` | `ParqueaderoController.guardarRotacion` |
| `/api/parqueaderoCrear` | GET | `index` | `ParqueaderoController.crearIndex` |
| `/api/parqueaderoCrear` | POST | `guardar` | `ParqueaderoController.guardar` |
| `/api/parqueaderoHistorial` | GET | `index` | `ParqueaderoController.historial` |
| `/api/parqueaderoHistorial` | POST | `ajaxEditarFactura` | `ParqueaderoController.editarFactura` |
| `/api/parqueaderoHistorial` | POST | `ajaxEliminarFoto` | `ParqueaderoController.eliminarFoto` |

## programacion

### PlaneacionController

Port de planeacionControlador.php (clase planeacionVerControlador) + planeacionModelo.php

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/programacion/PlaneacionController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/planeacionVer` | GET | `index` | `PlaneacionController.index` |
| `/api/planeacionVer` | POST | `actualizarAvance` | `PlaneacionController.actualizarAvance` |
| `/api/planeacionVer` | POST | `actualizarEstado` | `PlaneacionController.actualizarEstado` |
| `/api/planeacionVer` | POST | `actualizarFechas` | `PlaneacionController.actualizarFechas` |
| `/api/planeacionVer` | POST | `crearEstrategia` | `PlaneacionController.crearEstrategia` |
| `/api/planeacionVer` | POST | `crearTarea` | `PlaneacionController.crearTarea` |
| `/api/planeacionVer` | GET/POST | `datosGantt` | `PlaneacionController.datosGantt` |

### ProgramacionConsolidadoController

Port de programacionConsolidadoControlador.php + programacionConsolidadoModelo.php

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/programacion/ProgramacionConsolidadoController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/programacionConsolidado` | GET | `index` | `ProgramacionConsolidadoController.index` |
| `/api/programacionConsolidado` | POST | `actualizarOrden` | `ProgramacionConsolidadoController.actualizarOrden` |
| `/api/programacionConsolidado` | POST | `eliminarOrden` | `ProgramacionConsolidadoController.eliminarOrden` |
| `/api/programacionConsolidado` | GET/POST | `exportarExcelConsolidado` | `ProgramacionConsolidadoController.exportarExcelConsolidado` |
| `/api/programacionConsolidado` | GET/POST | `listarClientesEditar` | `ProgramacionConsolidadoController.listarClientesEditar` |
| `/api/programacionConsolidado` | POST | `obtenerClientesPorPunto` | `ProgramacionConsolidadoController.obtenerClientesPorPunto` |
| `/api/programacionConsolidado` | POST | `obtenerPuntosCliente` | `ProgramacionConsolidadoController.obtenerPuntosCliente` |

### ProgramacionCrearController

Port de programacionCrearControlador.php + programacionCrearModelo.php

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/programacion/ProgramacionCrearController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/programacionCrear` | GET | `index` | `ProgramacionCrearController.index` |
| `/api/programacionCrear` | POST | `guardar_definitivo` | `ProgramacionCrearController.guardarDefinitivo` |
| `/api/programacionCrear` | GET/POST | `obtener_puntos_zona` | `ProgramacionCrearController.obtenerPuntosZona` |
| `/api/programacionCrear` | GET/POST | `obtener_puntos_zonas` | `ProgramacionCrearController.obtenerPuntosZonas` |
| `/api/programacionCrear` | POST | `previsualizar` | `ProgramacionCrearController.previsualizar` |
| `/api/programacionCrear` | POST | `previsualizar_diario` | `ProgramacionCrearController.previsualizarDiario` |
| `/api/programacionCrear` | GET/POST | `puntos_aledanios` | `ProgramacionCrearController.puntosAledanios` |
| `/api/programacionCrear` | POST | `restaurarMaquinaIndividual` | `ProgramacionCrearController.restaurarMaquinaIndividual` |
| `/api/programacionCrear` | POST | `restaurarOperativo` | `ProgramacionCrearController.restaurarOperativo` |

### ProgramacionMapaController

Port de programacionMapaControlador.php + programacionMapaModelo.php

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/programacion/ProgramacionMapaController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/programacionMapa` | GET | `index` | `ProgramacionMapaController.index` |
| `/api/programacionMapa` | POST | `ajax_obtener_puntos` | `ProgramacionMapaController.ajaxObtenerPuntos` |

## reportes

### GenerarReporteController

Port de reporteEjecutivoGenerarControlador.php (generarReporteControlador): configurar + PDF ejecutivo. La vista reporteEjecutivoGenerar.php se portó a resources/plantillas/reporte-ejecutivo.html (Chrome headless ejecuta la plantilla igual que Browsershot ejecutaba la de PHP).

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/reportes/GenerarReporteController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/generarReporte` | GET | `configurar` | `GenerarReporteController.configurar` |
| `/api/generarReporte` | GET | `generar` | `GenerarReporteController.generar` |

### ReporteEjecutivoController

Port de reporteEjecutivoVerControlador.php (Tablero Ejecutivo de Servicios + exportación Excel)

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/reportes/ReporteEjecutivoController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/reporteEjecutivo` | GET/POST | `index` | `ReporteEjecutivoController.index` |
| `/api/reporteEjecutivo` | GET | `descargarExcel` | `ReporteEjecutivoController.descargarExcel` |

### ReporteFacturacionController

Port de reporteFacturacionControlador.php: la vista procesa el Excel subido en el navegador. (reporteFacturacionPdfControlador.php no tiene ruta en la tabla `rutas` e incluye una plantilla inexistente, por lo que en PHP no es alcanzable; no se expone.)

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/reportes/ReporteFacturacionController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/reporteFacturacion` | GET | `index` | `ReporteFacturacionController.index` |

### ReportesInventarioController

reporteHistorialControlador.php, reporteRepuestoControlador.php, reporteRepuestosControlador.php (PDF de inventario) y reporteTrazabilidadControlador.php (+Excel)

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/reportes/ReportesInventarioController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/reporteHistorial` | GET/POST | `index` | `ReportesInventarioController.reporteHistorial` |
| `/api/reporteRepuesto` | GET/POST | `index` | `ReportesInventarioController.reporteRepuesto` |
| `/api/reporteRepuestos` | GET | — | `ReportesInventarioController.reporteRepuestos` |
| `/api/reporteTrazabilidad` | GET/POST | `index` | `ReportesInventarioController.reporteTrazabilidad` |
| `/api/reporteTrazabilidad` | GET | `descargarExcel` | `ReportesInventarioController.descargarExcel` |

### ReportesSimplesController

Reportes pequeños: remisionesPendientesControlador.php (+ controlRemisionVerModelo::obtenerSalteadasSandwich), reporteDevolucionControlador.php, reporteMaquinasControlador.php, reporteTarifasControlador.php

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/reportes/ReportesSimplesController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/remisionesPendientes` | GET | `index` | `ReportesSimplesController.remisionesPendientes` |
| `/api/remisionesPendientes` | GET/POST | `cambiarEstado` | `ReportesSimplesController.cambiarEstado` |
| `/api/reporteDevolucion` | GET/POST | `index` | `ReportesSimplesController.reporteDevolucion` |
| `/api/reporteMaquinas` | GET/POST | `index` | `ReportesSimplesController.reporteMaquinas` |
| `/api/reporteTarifas` | GET | `index` | `ReportesSimplesController.reporteTarifas` |
| `/api/reporteTarifas` | GET/POST | `descargarReportePorcentajes` | `ReportesSimplesController.descargarReportePorcentajes` |

### ReporteTecnicoController

Port de reporteTecnicoControlador.php + reporteTecnicoModelo.php

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/reportes/ReporteTecnicoController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/reporteTecnico` | GET/POST | `index` | `ReporteTecnicoController.index` |
| `/api/reporteTecnico` | GET | `generarPDF` | `ReporteTecnicoController.generarPDF` |

## supervisor

### SupervisorController

Port de panelSupervisorControlador.php y notificacionesLogisticaControlador.php (con sus modelos). (app/controllers/api/ajaxServiciosSupervisor.php no está en la tabla `rutas` y ninguna vista lo usa.)

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/supervisor/SupervisorController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/notificacionesLogistica` | GET | `index` | `SupervisorController.notificaciones` |
| `/api/notificacionesLogistica` | POST | `procesarNotificaciones` | `SupervisorController.procesar` |
| `/api/panelSupervisor` | GET | `index` | `SupervisorController.panel` |
| `/api/panelSupervisor` | POST | `ajaxCancelarServicio` | `SupervisorController.cancelar` |
| `/api/panelSupervisor` | POST | `ajaxGuardarServicio` | `SupervisorController.guardar` |
| `/api/panelSupervisor` | POST | `ajaxObtenerClientes` | `SupervisorController.clientes` |
| `/api/panelSupervisor` | POST | `ajaxObtenerMaquinas` | `SupervisorController.maquinas` |
| `/api/panelSupervisor` | POST | `ajaxObtenerPuntos` | `SupervisorController.puntos` |
| `/api/panelSupervisor` | GET/POST | `ajaxObtenerServicios` | `SupervisorController.servicios` |
| `/api/panelSupervisor` | POST | `ajaxObtenerTecnicos` | `SupervisorController.tecnicos` |
| `/api/panelSupervisor` | POST | `ajaxObtenerTiposMantenimiento` | `SupervisorController.tiposMantenimiento` |

## tecnico

### TecnicoProgramacionController

Port de tecnicoProgramacionControlador.php + tecnicoProgramacionModelo.php

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/tecnico/TecnicoProgramacionController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/tecnicoProgramacion` | GET | `index` | `TecnicoProgramacionController.index` |
| `/api/tecnicoProgramacion` | POST | `ajaxEliminarServicio` | `TecnicoProgramacionController.ajaxEliminarServicio` |
| `/api/tecnicoProgramacion` | POST | `ajaxGuardarExtra` | `TecnicoProgramacionController.ajaxGuardarExtra` |
| `/api/tecnicoProgramacion` | POST | `ajaxIniciarServicio` | `TecnicoProgramacionController.ajaxIniciarServicio` |
| `/api/tecnicoProgramacion` | POST | `ajaxObtenerClientes` | `TecnicoProgramacionController.ajaxObtenerClientes` |
| `/api/tecnicoProgramacion` | POST | `ajaxObtenerDetalleServicio` | `TecnicoProgramacionController.ajaxObtenerDetalleServicio` |
| `/api/tecnicoProgramacion` | POST | `ajaxObtenerMaquinas` | `TecnicoProgramacionController.ajaxObtenerMaquinas` |
| `/api/tecnicoProgramacion` | POST | `ajaxObtenerProgramacion` | `TecnicoProgramacionController.ajaxObtenerProgramacion` |
| `/api/tecnicoProgramacion` | POST | `ajaxObtenerPuntos` | `TecnicoProgramacionController.ajaxObtenerPuntos` |
| `/api/tecnicoProgramacion` | POST | `ajaxObtenerTiposMantenimiento` | `TecnicoProgramacionController.ajaxObtenerTiposMantenimiento` |

### TecnicoReporteController

Port de tecnicoReporteControlador.php (el técnico atiende y finaliza el servicio desde el celular)

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/tecnico/TecnicoReporteController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/tecnicoReporte` | GET | `index` | `TecnicoReporteController.index` |
| `/api/tecnicoReporte` | POST | `ajaxEliminarFotoUnica` | `TecnicoReporteController.ajaxEliminarFotoUnica` |
| `/api/tecnicoReporte` | POST | `ajaxGuardarNovedadTecnico` | `TecnicoReporteController.ajaxGuardarNovedadTecnico` |
| `/api/tecnicoReporte` | POST | `ajaxLimpiarEvidenciasOrden` | `TecnicoReporteController.ajaxLimpiarEvidenciasOrden` |
| `/api/tecnicoReporte` | POST | `ajaxObtenerCatalogoNovedades` | `TecnicoReporteController.ajaxObtenerCatalogoNovedades` |
| `/api/tecnicoReporte` | POST | `ajaxObtenerEvidencias` | `TecnicoReporteController.ajaxObtenerEvidencias` |
| `/api/tecnicoReporte` | POST | `ajaxObtenerNovedadesTecnico` | `TecnicoReporteController.ajaxObtenerNovedadesTecnico` |
| `/api/tecnicoReporte` | POST | `ajaxSubirFotoUnica` | `TecnicoReporteController.ajaxSubirFotoUnica` |
| `/api/tecnicoReporte` | POST | `ajaxVerificarModificacion` | `TecnicoReporteController.ajaxVerificarModificacion` |
| `/api/tecnicoReporte` | POST | `ajaxVerificarNovedad` | `TecnicoReporteController.ajaxVerificarNovedad` |
| `/api/tecnicoReporte` | POST | `guardar` | `TecnicoReporteController.guardar` |

### TecnicoReporteEditarController

Port de tecnicoReporteEditarControlador.php

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/tecnico/TecnicoReporteEditarController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/tecnicoReporteEditar` | GET | `index` | `TecnicoReporteEditarController.index` |
| `/api/tecnicoReporteEditar` | POST | `actualizar` | `TecnicoReporteEditarController.actualizar` |

## transporte

### TransporteController

Port de app/controllers/transporte/* (transporteVer, transporteCrear, transporteEditar, transportePdf) y modelos.

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/transporte/TransporteController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/transporteCrear` | GET | `index` | `TransporteController.crearIndex` |
| `/api/transporteCrear` | POST | `ajaxPuntos` | `TransporteController.ajaxPuntos` |
| `/api/transporteCrear` | POST | `ajaxRemisiones` | `TransporteController.ajaxRemisiones` |
| `/api/transporteCrear` | POST | `guardar` | `TransporteController.guardar` |
| `/api/transporteEditar` | GET | `index` | `TransporteController.editarIndex` |
| `/api/transporteEditar` | GET/POST | `actualizar` | `TransporteController.actualizar` |
| `/api/transporteEditar` | POST | `ajaxPuntos` | `TransporteController.ajaxPuntos` |
| `/api/transporteEditar` | POST | `ajaxRemisiones` | `TransporteController.ajaxRemisiones` |
| `/api/transportePdf` | GET | — | `TransporteController.generarPdf` |
| `/api/transporteVer` | GET | `index` | `TransporteController.verIndex` |
| `/api/transporteVer` | GET | `ajaxListar` | `TransporteController.listar` |
| `/api/transporteVer` | POST | `eliminar` | `TransporteController.eliminar` |

## turno

### TurnoController

Port de turnoControlador, turnoReportesControlador, turnoExcelControlador, turnoPdfControlador.

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/turno/TurnoController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/turno` | GET | `estadoHoy` | `TurnoController.estadoHoy` |
| `/api/turno` | POST | `guardar` | `TurnoController.guardar` |
| `/api/turnoExcel` | GET | `generar` | `TurnoController.excel` |
| `/api/turnoPdf` | GET | `generar` | `TurnoController.pdf` |
| `/api/turnoReportes` | GET | `index` | `TurnoController.reportes` |

## usuario

### UsuarioController

Port de usuario{Ver,Crear,Editar,Eliminar}Controlador + modelos.

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/usuario/UsuarioController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/usuarioCrear` | GET | — | `UsuarioController.crearForm` |
| `/api/usuarioCrear` | POST | — | `UsuarioController.crear` |
| `/api/usuarioEditar` | GET | — | `UsuarioController.editarForm` |
| `/api/usuarioEditar` | POST | — | `UsuarioController.editar` |
| `/api/usuarioEliminar` | POST | — | `UsuarioController.eliminar` |
| `/api/usuarioVer` | GET | — | `UsuarioController.ver` |

## varios

### VariosController

Port de informacionBaseDatosControlador.php, exportarExcelControlador.php, geolocalizarControlador.php y rastreoTecnicoControlador.php (con sus modelos).

Archivo: `backend/src/main/java/co/inees/mantenimientos/modulos/varios/VariosController.java`

| URL | Método | accion | Método Java |
| --- | --- | --- | --- |
| `/api/exportarExcel` | GET | `index` | `VariosController.exportarIndex` |
| `/api/exportarExcel` | GET | `descargarReporte` | `VariosController.exportarDatos` |
| `/api/geolocalizar` | GET | `index` | `VariosController.geolocalizar` |
| `/api/informacionBaseDatos` | GET | `index` | `VariosController.informacion` |
| `/api/rastreoTecnico` | GET | `index` | `VariosController.rastreoIndex` |
| `/api/rastreoTecnico` | POST | `ajaxObtenerRuta` | `VariosController.obtenerRuta` |

