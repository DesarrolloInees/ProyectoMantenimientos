/**
 * Menús de navegación por rol (app/partials/navbar_menu.php y navbar_menu_mobile.php).
 */
export interface ItemMenu {
  ruta?: string;
  texto: string;
  icono?: string;
  clase?: string;          // clases extra del enlace
  seccion?: boolean;       // encabezado de sección dentro del desplegable
  separador?: boolean;
  accion?: 'turno';        // botón especial "Marcar Entrada"
}

export interface GrupoMenu {
  texto: string;
  icono?: string;
  ruta?: string;           // enlace directo (sin desplegable)
  accion?: 'turno';
  items?: ItemMenu[];
  ancho?: string;
  oscuro?: boolean;        // desplegable oscuro (roles 3 y 5)
  claseBoton?: string;
  alinearDerecha?: boolean;
}

const TECNICO: GrupoMenu[] = [
  { texto: 'Inicio', icono: 'fas fa-home', ruta: 'inicio' },
  { texto: 'Marcar Entrada', icono: 'fas fa-user-clock', accion: 'turno', claseBoton: 'text-white bg-emerald-600 hover:bg-emerald-700 font-bold shadow' },
  { texto: 'Programación Servicios', icono: 'fa-solid fa-envelope', ruta: 'tecnicoProgramacion' },
  { texto: 'Cronómetro Servicios Historial', icono: 'fa-solid fa-envelope', ruta: 'cronometroHistorial' },
  { texto: 'Inventario Técnico', icono: 'fa-solid fa-boxes-stacked', ruta: 'inventarioTecnicoVer' },
  { texto: 'Solicitar Inventario', icono: 'fa-solid fa-clipboard-list', ruta: 'solicitarInventario', claseBoton: 'text-gray-300 hover:bg-purple-600 hover:text-white' },
  {
    texto: 'Historiales',
    icono: 'fas fa-history',
    oscuro: true,
    ancho: 'w-56',
    items: [
      { ruta: 'ordenMovil', texto: 'Consultar Historial', icono: 'fas fa-search' },
      { ruta: 'parqueaderoHistorial', texto: 'Parqueaderos', icono: 'fa-solid fa-square-parking' },
      { ruta: 'horaExtraHistorial', texto: 'Horas Extra', icono: 'fa-solid fa-clock' }
    ]
  }
];

const PROSEGUR: GrupoMenu[] = [
  { texto: 'Inicio', icono: 'fas fa-home', ruta: 'inicio' },
  { texto: 'Servicios Pdf', icono: 'fas fa-search', ruta: 'serviciosPdf', claseBoton: 'text-gray-300 hover:bg-blue-600 hover:text-white ml-2' }
];

const SUPERVISOR: GrupoMenu[] = [
  { texto: 'Inicio', icono: 'fas fa-home', ruta: 'inicio' },
  {
    texto: 'Gestión',
    icono: 'fas fa-tasks',
    oscuro: true,
    ancho: 'w-48',
    items: [
      { ruta: 'ordenVer', texto: 'Servicios', icono: 'fas fa-clipboard-list' },
      { ruta: 'cronometroAdmin', texto: 'Cronómetro Supervisar', icono: 'fas fa-stopwatch' },
      { ruta: 'turnoReportes', texto: 'Reporte de Turnos', icono: 'fas fa-user-clock' },
      { ruta: 'repuestoVer', texto: 'Repuestos', icono: 'fas fa-puzzle-piece' },
      { ruta: 'inventarioTecnicoVer', texto: 'Adm. Repuestos', icono: 'fas fa-gears' },
      { ruta: 'controlRemisionVer', texto: 'Remisiones', icono: 'fas fa-ticket-alt' }
    ]
  },
  {
    texto: 'Reportes',
    icono: 'fas fa-chart-bar',
    oscuro: true,
    ancho: 'w-48',
    items: [
      { ruta: 'reporteTecnico', texto: 'R. Técnico', icono: 'fas fa-chart-line' },
      { ruta: 'reporteEjecutivo', texto: 'R. Ejecutivo', icono: 'fas fa-chart-pie' },
      { ruta: 'horaExtraAdmin', texto: 'Administrar Horas Extra', icono: 'fas fa-clock' },
      { ruta: 'parqueaderoAdmin', texto: 'Administrar Parqueaderos', icono: 'fas fa-motorcycle' }
    ]
  },
  {
    texto: 'Herramientas',
    icono: 'fas fa-tools',
    oscuro: true,
    ancho: 'w-52',
    items: [
      { ruta: 'ordenDetalleBuscar', texto: 'Buscar Servicio', icono: 'fas fa-search' },
      { ruta: 'exportarExcel', texto: 'Exportar General', icono: 'fas fa-file-excel' },
      { ruta: 'ordenReporte', texto: 'Excel Servicios', icono: 'fas fa-file-csv' }
    ]
  }
];

const ADMIN: GrupoMenu[] = [
  { texto: 'Inicio', icono: 'fas fa-home', ruta: 'inicio' },
  {
    texto: 'Operaciones',
    ancho: 'w-56',
    items: [
      { seccion: true, texto: 'Gestión de Servicios' },
      { ruta: 'ordenCrear', texto: 'Nuevo Servicio', icono: 'fas fa-plus-circle text-green-500' },
      { ruta: 'ordenVer', texto: 'Gestionar Servicios', icono: 'fas fa-list-alt text-blue-500' },
      { ruta: 'ordenDetalleBuscar', texto: 'Buscar Servicio', icono: 'fa-solid fa-diamond' },
      { ruta: 'remisionesPendientes', texto: 'Remisiones Salteadas', icono: 'fa-solid fa-traffic-light text-orange-500' }
    ]
  },
  {
    texto: 'Logística',
    ancho: 'w-60',
    items: [
      { ruta: 'inventarioTecnicoVer', texto: 'Inventario Técnicos', icono: 'fa-brands fa-centos text-yellow-600' },
      { ruta: 'repuestoVer', texto: 'Gestión Repuestos', icono: 'fa-solid fa-puzzle-piece' },
      { ruta: 'controlRemisionVer', texto: 'Admin. Remisiones', icono: 'fa-solid fa-book' }
    ]
  },
  {
    texto: 'Motorizados',
    ancho: 'w-60',
    items: [
      { ruta: 'serviciosPdf', texto: 'Servicios Pdf', icono: 'fa-solid fa-file-pdf text-yellow-600' },
      { ruta: 'rastreoTecnico', texto: 'Rastreó Técnico', icono: 'fa-solid fa-street-view' },
      { ruta: 'parqueaderoAdmin', texto: 'Administrar Facturas Parqueadero', icono: 'fa-solid fa-receipt' },
      { ruta: 'horaExtraAdmin', texto: 'Administrar Horas Extra', icono: 'fa-solid fa-clock' },
      { ruta: 'turnoReportes', texto: 'Reporte de Turnos', icono: 'fas fa-user-clock text-emerald-600' },
      { ruta: 'cronometroAdmin', texto: 'Cronómetrar Servicios Técnicos', icono: 'fa-solid fa-stopwatch' }
    ]
  },
  {
    texto: 'Programación',
    ancho: 'w-60',
    items: [
      { ruta: 'programacionCrear', texto: 'Crear Programación', icono: 'fa-solid fa-person-biking text-yellow-600' },
      { ruta: 'programacionConsolidado', texto: 'Programación Consolidado', icono: 'fa-solid fa-motorcycle' }
    ]
  },
  {
    texto: 'Transporte',
    ancho: 'w-60',
    items: [
      { ruta: 'transporteCrear', texto: 'Transportes Crear', icono: 'fa-solid fa-truck text-yellow-600' },
      { ruta: 'transporteVer', texto: 'Transportes Ver', icono: 'fa-solid fa-truck-front' }
    ]
  },
  {
    texto: 'Reportes',
    ancho: 'w-64',
    items: [
      { ruta: 'reporteEjecutivo', texto: 'Reporte Ejecutivo', icono: 'fa-solid fa-chart-line text-purple-600' },
      { separador: true, texto: '' },
      { ruta: 'ordenReporte', texto: 'Excel por Fechas', icono: 'fa-solid fa-file-excel text-green-600' },
      { ruta: 'exportarExcel', texto: 'Excel Programación', icono: 'fa-solid fa-building-user' },
      { ruta: 'reporteTecnico', texto: 'R. Técnicos', icono: 'fa-solid fa-book-open-reader' },
      { ruta: 'reporteRepuesto', texto: 'R. Repuestos', icono: 'fa-solid fa-screwdriver-wrench' },
      { ruta: 'reporteMaquinas', texto: 'R. Máquinas', icono: 'fa-solid fa-cash-register' },
      { ruta: 'reporteHistorial', texto: 'R. Historial', icono: 'fa-solid fa-timeline' },
      { ruta: 'reporteDevolucion', texto: 'R. Devoluciones', icono: 'fa-solid fa-angles-left' },
      { ruta: 'reporteTrazabilidad', texto: 'R. Trazabilidad Repuestos', icono: 'fa-solid fa-route text-teal-600' },
      { ruta: 'reporteTarifas', texto: 'R. Tarifas', icono: 'fa-brands fa-bitcoin' },
      { ruta: 'asistencia', texto: 'R. Horas Trabajadores', icono: 'fa-solid fa-stopwatch' }
    ]
  },
  {
    texto: 'Administración',
    ancho: 'w-80',
    alinearDerecha: true,
    items: [
      { seccion: true, texto: 'Personas & Accesos' },
      { ruta: 'usuarioVer', texto: 'Usuarios y Accesos' },
      { ruta: 'tecnicoVer', texto: 'Técnicos' },
      { ruta: 'clienteVer', texto: 'Clientes' },
      { seccion: true, texto: 'Maestros del Sistema' },
      { ruta: 'maquinaVer', texto: 'Máquinas' },
      { ruta: 'puntoVer', texto: 'Puntos' },
      { ruta: 'tarifaVer', texto: 'Tarifas' },
      { seccion: true, texto: 'Configuraciones Varias' },
      { ruta: 'tecnicoVer', texto: 'Administrar Técnicos' },
      { ruta: 'gestionDevolucion', texto: 'Devoluciones de Repuestos' },
      { ruta: 'diasFestivosVer', texto: 'Días Festivos' },
      { ruta: 'informacionBaseDatos', texto: 'Información Base de Datos' },
      { ruta: 'calificacionServicioVer', texto: 'Tipos de Calificación' },
      { ruta: 'delegacionVer', texto: 'Tipos de Delegación' },
      { ruta: 'estadoMaquinaVer', texto: 'Tipos de Estado de Máquina' },
      { ruta: 'tipoMantenimientoVer', texto: 'Tipos Mantenimiento' },
      { ruta: 'tipoMaquinaVer', texto: 'Tipos de Máquina' },
      { ruta: 'modalidadOperativaVer', texto: 'Tipos de Modalidad Operativa' },
      { ruta: 'tipoNovedadVer', texto: 'Tipos de Novedades' },
      { ruta: 'tipoUsuarioVer', texto: 'Tipos Usuario' },
      { ruta: 'costosVer', texto: 'Costos Motorizados' },
      { ruta: 'costosAdministrativosVer', texto: 'Costos Administrativos' },
      { ruta: 'importarExcel', texto: 'Importar Excel Prosegur', clase: 'text-green-600 font-bold' },
      { ruta: 'importarEstadoMaquina', texto: 'Importar Estado Maquinas', clase: 'text-red-600 font-bold' },
      { ruta: 'importarMunicipios', texto: 'Importar Zonas Geográficas', clase: 'text-green-600 font-bold' },
      { ruta: 'herramientasCarga', texto: 'Herramientas de Carga (CSV)', clase: 'text-gray-700 font-bold' }
    ]
  }
];

export function menuPorRol(rol: number): GrupoMenu[] {
  if (rol === 3) return TECNICO;
  if (rol === 4) return PROSEGUR;
  if (rol === 5) return SUPERVISOR;
  return ADMIN;
}
