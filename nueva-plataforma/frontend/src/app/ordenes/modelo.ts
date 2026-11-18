export interface RepuestoServicio {
  idRepuesto: number;
  nombre: string;
  codigo: string | null;
  origen: 'INEES' | 'PROSEGUR';
  cantidad: number;
  valorUnitario: number | null;
}

export interface Servicio {
  idOrden: number;
  numeroRemision: string | null;
  fechaVisita: string;
  horaEntrada: string | null;
  horaSalida: string | null;
  minutosServicio: number | null;
  valorServicio: number | null;
  valorViaticos: number | null;
  diasViaticos: number | null;
  esFueraDelegacion: boolean;
  actividades: string | null;
  tieneNovedad: boolean;
  detalleNovedad: string | null;
  novedades: string | null;
  idsNovedades: string | null;
  idMaquina: number;
  deviceId: string;
  idTipoMaquina: number | null;
  tipoMaquina: string | null;
  idCliente: number;
  cliente: string;
  idPunto: number;
  punto: string;
  idDelegacion: number | null;
  delegacion: string | null;
  idModalidad: number | null;
  modalidad: string | null;
  idTecnico: number | null;
  tecnico: string | null;
  idTipoMantenimiento: number | null;
  tipoMantenimiento: string | null;
  idEstadoMaquina: number | null;
  estadoMaquina: string | null;
  idCalificacion: number | null;
  calificacion: string | null;
  repuestosTexto: string | null;
  estado: number;
  origen: string;
  repuestosLegado: string | null;
  creado: string | null;
  repuestos: RepuestoServicio[];
}

export interface Filtros {
  desde?: string | null;
  hasta?: string | null;
  idCliente?: number | null;
  idPunto?: number | null;
  idTecnico?: number | null;
  idDelegacion?: number | null;
  idTipoMantenimiento?: number | null;
  remision?: string | null;
  deviceId?: string | null;
  conNovedad?: boolean | null;
  estado?: number | null;
}

export const ESTADOS_ORDEN: Record<number, string> = {
  1: 'Finalizado',
  2: 'Programado',
  3: 'En la bolsa',
  4: 'En camino',
  5: 'En el punto',
  6: 'En servicio',
  9: 'Anulado',
};
