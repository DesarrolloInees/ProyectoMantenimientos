import { Routes, Route } from '@angular/router';
import { authGuard, loginGuard, paginaGuard } from './core/guards';
import { Layout, NoEncontrado } from './layout/layout';
import { Login } from './paginas/login/login';
import { CambiarPassword, MensajeEnviado, ResetPassword, SolicitarCodigo } from './paginas/login/recuperacion';

/**
 * Cada ruta corresponde a un registro de la tabla `rutas` (nombre_ruta).
 * El guard verifica rol_permisos igual que index.php.
 */
function pag(path: string, titulo: string, loadComponent: Route['loadComponent'], extra: Record<string, any> = {}): Route {
  return { path, loadComponent, canActivate: [paginaGuard], data: { titulo, pagina: path.split('/')[0], ...extra } };
}

/** Catálogos simples: xVer, xCrear, xEditar/:id */
function catalogo(cat: string): Route[] {
  const ver = () => import('./paginas/admin/catalogos').then((m) => m.CatalogoVer);
  const form = () => import('./paginas/admin/catalogos').then((m) => m.CatalogoForm);
  return [
    { path: cat + 'Ver', loadComponent: ver, canActivate: [paginaGuard], data: { cat, pagina: cat + 'Ver' } },
    { path: cat + 'Crear', loadComponent: form, canActivate: [paginaGuard], data: { cat, modo: 'crear', pagina: cat + 'Crear' } },
    { path: cat + 'Editar/:id', loadComponent: form, canActivate: [paginaGuard], data: { cat, modo: 'editar', pagina: cat + 'Editar' } },
    { path: cat + 'Editar', loadComponent: form, canActivate: [paginaGuard], data: { cat, modo: 'editar', pagina: cat + 'Editar' } }
  ];
}

const CATALOGOS = ['calificacionServicio', 'delegacion', 'estadoMaquina', 'modalidadOperativa', 'tecnico', 'tipoMantenimiento', 'tipoMaquina', 'tipoUsuario'];

export const routes: Routes = [
  { path: 'login', component: Login, canActivate: [loginGuard] },
  { path: 'cambiarPassword', component: CambiarPassword },
  { path: 'solicitarCodigo', component: SolicitarCodigo },
  { path: 'mensajeEnviado', component: MensajeEnviado },
  { path: 'resetPassword', component: ResetPassword },
  {
    path: '',
    component: Layout,
    canActivate: [authGuard],
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'inicio' },
      pag('inicio', 'Panel de Control', () => import('./paginas/inicio/inicio').then((m) => m.Inicio)),
      pag('turnoReportes', 'Reporte de Turnos', () => import('./paginas/turno/turno-reportes').then((m) => m.TurnoReportes)),
      ...CATALOGOS.flatMap(catalogo),
      pag('tipoNovedadVer', 'Gestión de Tipos de Novedad', () => import('./paginas/admin/tipo-novedad').then((m) => m.TipoNovedadVer)),
      pag('tipoNovedadCrear', 'Crear Tipo de Novedad', () => import('./paginas/admin/tipo-novedad').then((m) => m.TipoNovedadForm)),
      pag('tipoNovedadEditar/:id', 'Editar Tipo de Novedad', () => import('./paginas/admin/tipo-novedad').then((m) => m.TipoNovedadForm), { modo: 'editar' }),
      pag('diasFestivosVer', 'Gestión de Días Festivos', () => import('./paginas/admin/tipo-novedad').then((m) => m.FestivosVer)),
      pag('diasFestivosCrear', 'Crear Día Festivo', () => import('./paginas/admin/tipo-novedad').then((m) => m.FestivosForm)),
      pag('usuarioVer', 'Ver Usuarios', () => import('./paginas/usuario/usuario').then((m) => m.UsuarioVer)),
      pag('usuarioCrear', 'Crear Nuevo Usuario', () => import('./paginas/usuario/usuario').then((m) => m.UsuarioForm)),
      pag('usuarioEditar/:id', 'Editar Usuario', () => import('./paginas/usuario/usuario').then((m) => m.UsuarioForm), { modo: 'editar' }),
      pag('maquinaVer', 'Inventario de Máquinas', () => import('./paginas/admin/maquina-punto').then((m) => m.MaquinaVer)),
      pag('maquinaCrear', 'Registrar Máquina', () => import('./paginas/admin/maquina-punto').then((m) => m.MaquinaForm)),
      pag('maquinaEditar/:id', 'Editar Máquina', () => import('./paginas/admin/maquina-punto').then((m) => m.MaquinaForm), { modo: 'editar' }),
      pag('puntoVer', 'Listado de Puntos', () => import('./paginas/admin/maquina-punto').then((m) => m.PuntoVer)),
      pag('puntoCrear', 'Crear Punto', () => import('./paginas/admin/maquina-punto').then((m) => m.PuntoForm)),
      pag('puntoEditar/:id', 'Editar Punto', () => import('./paginas/admin/maquina-punto').then((m) => m.PuntoForm), { modo: 'editar' }),
      pag('repuestoVer', 'Inventario de Repuestos', () => import('./paginas/admin/repuesto-tarifa').then((m) => m.RepuestoVer)),
      pag('repuestoCrear', 'Crear Nuevo Repuesto', () => import('./paginas/admin/repuesto-tarifa').then((m) => m.RepuestoForm)),
      pag('repuestoEditar/:id', 'Editar Repuesto', () => import('./paginas/admin/repuesto-tarifa').then((m) => m.RepuestoForm), { modo: 'editar' }),
      pag('tarifaVer', 'Listado de Tarifas', () => import('./paginas/admin/repuesto-tarifa').then((m) => m.TarifaVer)),
      pag('tarifaCrear', 'Crear Tarifas Masivas', () => import('./paginas/admin/repuesto-tarifa').then((m) => m.TarifaCrear)),
      pag('tarifaEditar/:id', 'Editar Tarifa', () => import('./paginas/admin/repuesto-tarifa').then((m) => m.TarifaEditar)),
      pag('tarifaMasiva', 'Edición Masiva de Tarifas', () => import('./paginas/admin/repuesto-tarifa').then((m) => m.TarifaMasiva)),
      pag('controlRemisionVer', 'Control de Remisiones', () => import('./paginas/admin/remision-inventario').then((m) => m.RemisionVer)),
      pag('controlRemisionCrear', 'Asignar Remisiones', () => import('./paginas/admin/remision-inventario').then((m) => m.RemisionCrear)),
      pag('controlRemisionEditar', 'Editar Remisión', () => import('./paginas/admin/remision-inventario').then((m) => m.RemisionEditar)),
      pag('inventarioTecnicoVer', 'Inventario por Técnico', () => import('./paginas/admin/remision-inventario').then((m) => m.InventarioVer)),
      pag('inventarioTecnicoCrear', 'Asignación Masiva de Stock', () => import('./paginas/admin/remision-inventario').then((m) => m.InventarioCrear)),
      pag('inventarioTecnicoEditar', 'Ajustar Stock de Técnico', () => import('./paginas/admin/remision-inventario').then((m) => m.InventarioEditar)),
      pag('gestionDevolucion', 'Recepción de Devoluciones', () => import('./paginas/admin/remision-inventario').then((m) => m.GestionDevolucion)),
      pag('ordenDetalle/:fecha', 'Edición Total', () => import('./paginas/orden/orden-detalle').then((m) => m.OrdenDetalle)),
      pag('ordenDetalle', 'Edición Total', () => import('./paginas/orden/orden-detalle').then((m) => m.OrdenDetalle)),
      pag('ordenDetalleBuscar', 'Buscador Individual de Servicios', () => import('./paginas/orden/orden-detalle').then((m) => m.OrdenDetalle), { modo: 'busqueda' }),
      pag('ordenCrear', 'Reporte de Servicios', () => import('./paginas/orden/orden-crear').then((m) => m.OrdenCrear)),
      pag('ordenReporte', 'Generador de Reportes', () => import('./paginas/orden/orden-reporte').then((m) => m.OrdenReporte)),
      pag('servicioEditar/:id', 'Editar Complementos de Orden', () => import('./paginas/orden/servicio-editar').then((m) => m.ServicioEditar)),
      pag('servicioEditar', 'Editar Complementos de Orden', () => import('./paginas/orden/servicio-editar').then((m) => m.ServicioEditar)),
      pag('serviciosPdf', 'Generador de PDFs', () => import('./paginas/orden/servicios-pdf').then((m) => m.ServiciosPdf)),
      pag('tecnicoProgramacion', 'Mi Programación Diaria', () => import('./paginas/tecnico/tecnico-programacion').then((m) => m.TecnicoProgramacion)),
      pag('tecnicoReporte', 'Atender Servicio', () => import('./paginas/tecnico/tecnico-reporte').then((m) => m.TecnicoReporte)),
      pag('tecnicoReporteEditar', 'Editar Reporte de Servicio', () => import('./paginas/tecnico/tecnico-reporte').then((m) => m.TecnicoReporteEditar)),
      pag('programacionCrear', 'Programación de Rutas Diarias - Contingencias', () => import('./paginas/programacion/programacion-crear').then((m) => m.ProgramacionCrear)),
      pag('remisionesPendientes', 'Remisiones Salteadas', () => import('./paginas/reportes/reportes-simples').then((m) => m.RemisionesPendientes)),
      pag('reporteDevolucion', 'Control de Devolución de Repuestos', () => import('./paginas/reportes/reportes-simples').then((m) => m.ReporteDevolucion)),
      pag('reporteMaquinas', 'Reporte por Tipo de Máquina', () => import('./paginas/reportes/reportes-simples').then((m) => m.ReporteMaquinas)),
      pag('reporteTarifas', 'Reporte de Tarifas Mensuales', () => import('./paginas/reportes/reportes-simples').then((m) => m.ReporteTarifas)),
      pag('reporteHistorial', 'Historial de Mantenimientos por Punto', () => import('./paginas/reportes/reportes-inventario').then((m) => m.ReporteHistorial)),
      pag('reporteRepuesto', 'Reporte de Uso de Repuestos', () => import('./paginas/reportes/reportes-inventario').then((m) => m.ReporteRepuesto)),
      pag('reporteRepuestos', 'Reporte de Inventario', () => import('./paginas/reportes/reportes-inventario').then((m) => m.ReporteRepuestosPdf)),
      pag('reporteTrazabilidad', 'Trazabilidad de Repuestos Suministrados', () => import('./paginas/reportes/reportes-inventario').then((m) => m.ReporteTrazabilidad)),
      pag('reporteTecnico', 'Reporte de Servicios por Técnico', () => import('./paginas/reportes/reporte-tecnico').then((m) => m.ReporteTecnico)),
      pag('reporteEjecutivo', 'Tablero Ejecutivo de Servicios', () => import('./paginas/reportes/reporte-ejecutivo').then((m) => m.ReporteEjecutivo)),
      pag('costosVer', 'Resumen de Costos', () => import('./paginas/costos/costos').then((m) => m.CostosVer)),
      pag('costosCrear', 'Costos Motorizados', () => import('./paginas/costos/costos').then((m) => m.CostosCrear)),
      pag('costosEditar', 'Editar Costos', () => import('./paginas/costos/costos').then((m) => m.CostosEditar)),
      pag('costosAdministrativosVer', 'Histórico Costos Administrativos', () => import('./paginas/costos/costos').then((m) => m.CostosAdministrativosVer)),
      pag('costosAdministrativosCrear', 'Gestión de Costos Administrativos', () => import('./paginas/costos/costos').then((m) => m.CostosAdministrativosCrear)),
      pag('costosAdministrativosEditar', 'Editar Costos Administrativos', () => import('./paginas/costos/costos').then((m) => m.CostosAdministrativosEditar)),
      pag('cronometroCrear', 'Cronómetro de Servicio', () => import('./paginas/cronometro/cronometro').then((m) => m.CronometroCrear)),
      pag('cronometroHistorial', 'Historial de Tiempos', () => import('./paginas/cronometro/cronometro').then((m) => m.CronometroHistorial)),
      pag('cronometroAdmin', 'Monitoreo de Servicios (Supervisor)', () => import('./paginas/cronometro/cronometro').then((m) => m.CronometroAdmin)),
      pag('cronometroReportes', 'Reporte de Rendimiento de Flota', () => import('./paginas/cronometro/cronometro').then((m) => m.CronometroReportes)),
      pag('horaExtraCrear', 'Reportar Horas Extra', () => import('./paginas/hora-extra/hora-extra').then((m) => m.HoraExtraCrear)),
      pag('horaExtraHistorial', 'Mis Horas Extra', () => import('./paginas/hora-extra/hora-extra').then((m) => m.HoraExtraHistorial)),
      pag('horaExtraAdmin', 'Administración de Horas Extra', () => import('./paginas/hora-extra/hora-extra').then((m) => m.HoraExtraAdmin)),
      pag('parqueaderoCrear', 'Registrar Parqueadero', () => import('./paginas/parqueadero/parqueadero').then((m) => m.ParqueaderoCrear)),
      pag('parqueaderoHistorial', 'Historial de Parqueaderos', () => import('./paginas/parqueadero/parqueadero').then((m) => m.ParqueaderoHistorial)),
      pag('parqueaderoAdmin', 'Administración de Parqueaderos', () => import('./paginas/parqueadero/parqueadero').then((m) => m.ParqueaderoAdmin)),
      pag('transporteVer', 'Gestión de Transportes', () => import('./paginas/transporte/transporte').then((m) => m.TransporteVer)),
      pag('transporteCrear', 'Registro de Transportes', () => import('./paginas/transporte/transporte').then((m) => m.TransporteForm)),
      pag('transporteEditar', 'Editar Transporte', () => import('./paginas/transporte/transporte').then((m) => m.TransporteForm), { modo: 'editar' }),
      pag('informacionBaseDatos', 'Estado de Base Instalada', () => import('./paginas/varios/varios').then((m) => m.InformacionBaseDatos)),
      pag('exportarExcel', 'Exportación de Datos', () => import('./paginas/varios/varios').then((m) => m.ExportarExcel)),
      pag('geolocalizar', 'Geolocalizar Puntos', () => import('./paginas/varios/varios').then((m) => m.Geolocalizar)),
      pag('rastreoTecnico', 'Rastreo de Técnicos', () => import('./paginas/varios/varios').then((m) => m.RastreoTecnico)),
      pag('solicitarInventario', 'Solicitar Inventario', () => import('./paginas/inventario/solicitar-inventario').then((m) => m.SolicitarInventario)),
      pag('panelSupervisor', 'Panel de Supervisor - Tiempo Real', () => import('./paginas/supervisor/supervisor').then((m) => m.PanelSupervisor)),
      pag('notificacionesLogistica', 'Alertas y Notificaciones Logísticas', () => import('./paginas/supervisor/supervisor').then((m) => m.NotificacionesLogistica)),
      pag('importarExcel', 'Importación Masiva de Instalaciones', () => import('./paginas/importar/importar').then((m) => m.ImportarExcel)),
      pag('importarEstadoMaquina', 'Importar Estado de Maquinas', () => import('./paginas/importar/importar').then((m) => m.ImportarEstadoMaquina)),
      pag('importarMunicipios', 'Actualización Masiva de Municipios', () => import('./paginas/importar/importar').then((m) => m.ImportarMunicipios)),
      pag('herramientasCarga', 'Herramientas de Carga Inicial (CSV)', () => import('./paginas/herramientas/herramientas-carga').then((m) => m.HerramientasCarga)),
      pag('asistencia','Procesador de Asistencias (Huellero + Servicios)', () => import('./paginas/asistencia/asistencia').then((m) => m.Asistencia)),
      pag('generarReporte', 'Configurar Reporte Ejecutivo', () => import('./paginas/reportes/reporte-ejecutivo').then((m) => m.GenerarReporte)),
      pag('reporteFacturacion', 'Analizador de Facturación', () => import('./paginas/reportes/reporte-facturacion').then((m) => m.ReporteFacturacion)),
      pag('programacionConsolidado', 'Consolidado General de Programación', () => import('./paginas/programacion/programacion-consolidado').then((m) => m.ProgramacionConsolidado)),
      pag('programacionMapa', 'Beta: Programación por Mapa', () => import('./paginas/programacion/programacion-mapa').then((m) => m.ProgramacionMapa)),
      pag('planeacionVer', 'Planeación Estratégica', () => import('./paginas/programacion/planeacion').then((m) => m.Planeacion)),
      pag('ordenVer', 'Historial por Días', () => import('./paginas/orden/orden-ver-movil').then((m) => m.OrdenVer)),
      pag('ordenMovil', 'Consulta Técnica Móvil', () => import('./paginas/orden/orden-ver-movil').then((m) => m.OrdenMovil)),
      pag('clienteVer', 'Gestión de Clientes', () => import('./paginas/admin/cliente').then((m) => m.ClienteVer)),
      pag('clienteCrear', 'Registrar Cliente', () => import('./paginas/admin/cliente').then((m) => m.ClienteForm)),
      pag('clienteEditar/:id', 'Editar Cliente', () => import('./paginas/admin/cliente').then((m) => m.ClienteForm), { modo: 'editar' }),
      pag('diasFestivosEditar', 'Editar Festivo', () => import('./paginas/admin/tipo-novedad').then((m) => m.FestivosForm), { modo: 'editar' }),
      { path: 'error404', component: NoEncontrado },
      { path: '**', component: NoEncontrado }
    ]
  }
];
