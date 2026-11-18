// Tipos de las respuestas del backend (Spring Boot). Fechas: 'AAAA-MM-DD'; horas: 'HH:MM:SS'.

export interface Item {
  id: number;
  nombre: string;
}

export interface Perfil {
  usuarioId: number;
  nombre: string;
  cargo: string | null;
  rol: number;
  nombreRol: string;
  idTecnico: number | null;
  permisos: string[];
  debeCambiarClave: boolean;
  ultimoAcceso?: string | null;
}

export interface Catalogos {
  delegaciones: Item[];
  tiposMantenimiento: Item[];
  estadosMaquina: Item[];
  tiposNovedad: Item[];
  calificaciones: Item[];
}

export interface Maquina {
  idMaquina: number;
  deviceId: string;
  tipoMaquina: string;
  idPunto: number;
  punto: string;
  direccion: string | null;
  municipio: string | null;
  delegacion: string | null;
  cliente: string;
  latitud: number | null;
  longitud: number | null;
  geoOrigen: 'DIRECCION' | 'PIN' | 'GPS_TECNICO' | null;
  geoVerificada: boolean;
  latitudPunto: number | null;
  longitudPunto: number | null;
  esVip: boolean;
  frecuenciaDias: number | null;
  frecuenciaTipo: number | null;
  ultimaVisita: string | null;
  activoOperativo: boolean;
  /** Para centrar el mapa: el punto o el centro de su municipio. */
  latitudReferencia: number | null;
  longitudReferencia: number | null;
}

// ---------------------------------------------------------------- cargas
export interface ResumenCarga {
  idCarga: number;
  nombreArchivo: string;
  fechaServicio: string;
  usuario: string;
  fechaCarga: string;
  totalFilas: number;
  filasOk: number;
  filasError: number;
  estado: 'EN_REVISION' | 'CONFIRMADA' | 'ANULADA';
  sinUbicacion: number;
}

export interface FilaSolicitud {
  idSolicitud: number;
  filaExcel: number;
  deviceId: string | null;
  referenciaExterna: string | null;
  tipoServicioTexto: string | null;
  descripcion: string | null;
  estado: 'VALIDA' | 'ERROR' | 'ORDEN_GENERADA' | 'DESCARTADA';
  mensajeError: string | null;
  idMaquina: number | null;
  punto: string | null;
  cliente: string | null;
  municipio: string | null;
  tipoMantenimiento: string | null;
  ubicada: boolean | null;
  idOrdenServicio: number | null;
}

export interface DetalleCarga {
  resumen: ResumenCarga;
  columnasReconocidas: Record<string, string> | null;
  filas: FilaSolicitud[];
}

// ---------------------------------------------------------------- planeación
export interface Orden {
  idOrden: number;
  idMaquina: number;
  deviceId: string;
  tipoMaquina: string;
  idPunto: number;
  punto: string;
  direccion: string | null;
  cliente: string;
  municipio: string | null;
  latitud: number | null;
  longitud: number | null;
  origen: string;
  prioridad: number;
  vecesReprogramada: number;
  fechaVisita: string;
  idTipoMantenimiento: number | null;
  tipoMantenimiento: string | null;
  estado: number;
  etapaActual: string | null;
  esVip: boolean;
  referenciaProsegur: string | null;
}

export interface Parada {
  idParada: number;
  secuencia: number;
  estadoParada: 'PENDIENTE' | 'HECHA' | 'NO_INGRESO' | 'NO_ALCANZADA' | 'RETIRADA';
  fijada: boolean;
  kmTramo: number | null;
  minutosTramo: number | null;
  horaEstimada: string | null;
  orden: Orden;
}

export interface Ruta {
  idRuta: number;
  idTecnico: number;
  tecnico: string;
  fecha: string;
  estado: 'BORRADOR' | 'PUBLICADA' | 'EN_CURSO' | 'CERRADA';
  latitudPartida: number | null;
  longitudPartida: number | null;
  kmEstimados: number | null;
  minutosEstimados: number | null;
  capacidadMinutos: number;
  paradas: Parada[];
}

export interface Plan {
  fecha: string;
  idDelegacion: number;
  rutas: Ruta[];
  bolsa: Orden[];
  sinUbicacion: Orden[];
  bolsaPorPrioridad: Record<string, number>;
}

export interface ResultadoGeneracion {
  plan: Plan;
  asignados: number;
  preventivosCreados: number;
  sinCupo: number;
}

// ---------------------------------------------------------------- seguimiento
export interface AvanceRuta {
  idRuta: number;
  idTecnico: number;
  tecnico: string;
  estado: string;
  inicioJornada: string | null;
  total: number;
  hechas: number;
  noIngreso: number;
  pendientes: number;
  ordenActual: number | null;
  puntoActual: string | null;
  etapaActual: string | null;
  ultimoEvento: string | null;
  ultimaLatitud: number | null;
  ultimaLongitud: number | null;
}

export interface Alerta {
  tipo: 'ESPERA' | 'SERIALES' | 'LEJOS_DEL_PUNTO' | 'SIN_REPORTE';
  tecnico: string;
  idOrden: number | null;
  punto: string;
  detalle: string;
  desde: string;
}

export interface Tablero {
  fecha: string;
  rutas: AvanceRuta[];
  alertas: Alerta[];
}

// ---------------------------------------------------------------- campo
export interface ParadaCampo {
  idParada: number;
  secuencia: number;
  estadoParada: Parada['estadoParada'];
  horaEstimada: string | null;
  idOrden: number;
  estadoOrden: number;
  etapaActual: string | null;
  cliente: string;
  punto: string;
  direccion: string | null;
  municipio: string | null;
  deviceId: string;
  tipoMaquina: string;
  tipoMantenimiento: string | null;
  latitud: number | null;
  longitud: number | null;
  esSiguiente: boolean;
  mapsUrl: string | null;
}

export interface MiDia {
  fecha: string;
  jornada: { horaEntrada: string; horaSalida: string | null } | null;
  idRuta: number | null;
  estadoRuta: string | null;
  paradas: ParadaCampo[];
  esperaMaxMinutos: number;
}

export interface Evidencia {
  idEvidencia: number;
  tipo: string;
  ruta: string;
  fecha: string;
}

export interface OrdenCampo {
  idOrden: number;
  estado: number;
  etapaActual: string | null;
  siguientes: string[];
  cliente: string;
  punto: string;
  direccion: string | null;
  municipio: string | null;
  deviceId: string;
  tipoMaquina: string;
  tipoMantenimiento: string | null;
  modalidad: string | null;
  referenciaProsegur: string | null;
  descripcionSolicitud: string | null;
  latitud: number | null;
  longitud: number | null;
  horaLlegada: string | null;
  horaIngreso: string | null;
  minutosEsperando: number | null;
  esperaMaxMinutos: number;
  actividades: string | null;
  complemento: Record<string, unknown> | null;
  evidencias: Evidencia[];
  fotoPorPaso: Record<string, string>;
  mapsUrl: string | null;
}

export interface Formularios {
  estadosMaquina: Item[];
  tiposNovedad: Item[];
  calificaciones: Item[];
  miStock: { idRepuesto: number; nombre: string; codigo: string | null; cantidad: number }[];
  repuestos: Item[];
  remisiones: string[];
}

export interface RespuestaPaso {
  orden: OrdenCampo;
  siguiente: ParadaCampo | null;
  avisos: string[];
}

export const NOMBRE_PRIORIDAD: Record<number, string> = {
  1: 'Solicitud Prosegur',
  2: 'Arrastrado',
  3: 'VIP vencida',
  4: 'Mantenimiento vencido',
};
