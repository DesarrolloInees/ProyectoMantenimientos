import { HttpClient, HttpParams } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import {
  Catalogos,
  DetalleCarga,
  Evidencia,
  Formularios,
  Item,
  Maquina,
  MiDia,
  Orden,
  OrdenCampo,
  Plan,
  RespuestaPaso,
  ResultadoGeneracion,
  ResumenCarga,
  Ruta,
  Tablero,
} from './modelos';

/** Todas las llamadas al backend. Devuelven promesas para usarlas con async/await. */
@Injectable({ providedIn: 'root' })
export class Api {
  private readonly http = inject(HttpClient);

  private get<T>(url: string, params?: Record<string, string | number | null | undefined>): Promise<T> {
    let p = new HttpParams();
    for (const [k, v] of Object.entries(params ?? {})) {
      if (v !== null && v !== undefined && v !== '') p = p.set(k, String(v));
    }
    return firstValueFrom(this.http.get<T>(url, { params: p }));
  }

  private post<T>(url: string, cuerpo: unknown = {}): Promise<T> {
    return firstValueFrom(this.http.post<T>(url, cuerpo));
  }

  private put<T>(url: string, cuerpo: unknown = {}): Promise<T> {
    return firstValueFrom(this.http.put<T>(url, cuerpo));
  }

  // ------------------------------------------------------------ catálogos y máquinas
  catalogos = () => this.get<Catalogos>('/api/catalogos');
  tecnicos = (idDelegacion?: number) => this.get<(Item & { delegacion: string })[]>('/api/catalogos/tecnicos', { idDelegacion });
  buscarMaquinas = (texto: string) => this.get<Maquina[]>('/api/maquinas', { texto });
  maquinasSinUbicacion = (filtro: { idCarga?: number; idDelegacion?: number }) =>
    this.get<Maquina[]>('/api/maquinas/sin-ubicacion', filtro);
  ponerPin = (idMaquina: number, latitud: number, longitud: number) =>
    this.put<Maquina>(`/api/maquinas/${idMaquina}/ubicacion`, { latitud, longitud });
  configurarMaquina = (idMaquina: number, esVip: boolean, frecuenciaDias: number | null) =>
    this.put<Maquina>(`/api/maquinas/${idMaquina}/configuracion`, { esVip, frecuenciaDias });

  // ------------------------------------------------------------ cargas de Prosegur
  cargas = () => this.get<ResumenCarga[]>('/api/cargas');
  carga = (id: number) => this.get<DetalleCarga>(`/api/cargas/${id}`);
  subirCarga(archivo: File, fechaServicio: string): Promise<DetalleCarga> {
    const datos = new FormData();
    datos.append('archivo', archivo);
    return firstValueFrom(
      this.http.post<DetalleCarga>('/api/cargas', datos, { params: new HttpParams().set('fechaServicio', fechaServicio) }),
    );
  }
  confirmarCarga = (id: number) => this.post<DetalleCarga>(`/api/cargas/${id}/confirmar`);
  anularCarga = (id: number) => this.post<DetalleCarga>(`/api/cargas/${id}/anular`);

  // ------------------------------------------------------------ planeación
  plan = (fecha: string, idDelegacion: number) => this.get<Plan>('/api/planeacion', { fecha, idDelegacion });
  generar = (fecha: string, idDelegacion: number) =>
    this.post<ResultadoGeneracion>(`/api/planeacion/generar?fecha=${fecha}&idDelegacion=${idDelegacion}`);
  publicar = (fecha: string, idDelegacion: number) =>
    this.post<Plan>(`/api/planeacion/publicar?fecha=${fecha}&idDelegacion=${idDelegacion}`);
  publicarRuta = (idRuta: number) => this.post<Ruta>(`/api/planeacion/rutas/${idRuta}/publicar`);
  recalcularRuta = (idRuta: number) => this.post<Ruta>(`/api/planeacion/rutas/${idRuta}/recalcular`);
  reordenar = (idRuta: number, idsOrden: number[]) => this.put<Ruta>(`/api/planeacion/rutas/${idRuta}/orden`, { idsOrden });
  fijar = (idParada: number, valor: boolean) => this.put<Ruta>(`/api/planeacion/paradas/${idParada}/fijada?valor=${valor}`);
  mover = (idOrden: number, idRutaDestino: number | null, posicion: number | null, motivo?: string) =>
    this.post<void>('/api/planeacion/mover', { idOrden, idRutaDestino, posicion, motivo });
  crearOrden = (idMaquina: number, idTipoMantenimiento: number, fecha: string, motivo: string) =>
    this.post<Orden>('/api/planeacion/ordenes', { idMaquina, idTipoMantenimiento, fecha, motivo });
  cancelarOrden = (idOrden: number, motivo: string) => this.post<void>(`/api/planeacion/ordenes/${idOrden}/cancelar`, { motivo });

  // ------------------------------------------------------------ seguimiento
  tablero = (idDelegacion: number, fecha?: string) => this.get<Tablero>('/api/seguimiento', { idDelegacion, fecha });

  // ------------------------------------------------------------ campo (motorizado)
  miDia = () => this.get<MiDia>('/api/campo/hoy');
  iniciarJornada = (latitud: number, longitud: number) => this.post<MiDia>('/api/campo/jornada/iniciar', { latitud, longitud });
  finalizarJornada = (latitud: number | null, longitud: number | null) =>
    this.post<MiDia>('/api/campo/jornada/finalizar', { latitud, longitud });
  formularios = () => this.get<Formularios>('/api/campo/formularios');
  ordenCampo = (id: number) => this.get<OrdenCampo>(`/api/campo/ordenes/${id}`);
  paso = (id: number, etapa: string, datos: Record<string, unknown>) =>
    this.post<RespuestaPaso>(`/api/campo/ordenes/${id}/pasos/${etapa}`, datos);
  subirFoto(idOrden: number, tipo: string, archivo: File, gps: { latitud: number; longitud: number } | null): Promise<Evidencia> {
    const datos = new FormData();
    datos.append('archivo', archivo);
    let params = new HttpParams().set('tipo', tipo);
    if (gps) params = params.set('latitud', gps.latitud).set('longitud', gps.longitud);
    return firstValueFrom(this.http.post<Evidencia>(`/api/campo/ordenes/${idOrden}/evidencias`, datos, { params }));
  }
}
