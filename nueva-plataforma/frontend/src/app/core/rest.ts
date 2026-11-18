import { HttpClient, HttpParams, HttpResponse } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { firstValueFrom } from 'rxjs';

export type Parametros = Record<string, string | number | boolean | null | undefined>;

/** Opción de los selectores (/api/listas/...). */
export interface Opcion {
  id: number;
  nombre: string;
  detalle: string | null;
}

/** Cliente HTTP genérico para los módulos de oficina: promesas, parámetros opcionales y descargas. */
@Injectable({ providedIn: 'root' })
export class Rest {
  private readonly http = inject(HttpClient);

  get<T>(url: string, params?: Parametros): Promise<T> {
    return firstValueFrom(this.http.get<T>(url, { params: parametros(params) }));
  }

  post<T>(url: string, cuerpo: unknown = {}, params?: Parametros): Promise<T> {
    return firstValueFrom(this.http.post<T>(url, cuerpo, { params: parametros(params) }));
  }

  put<T>(url: string, cuerpo: unknown = {}, params?: Parametros): Promise<T> {
    return firstValueFrom(this.http.put<T>(url, cuerpo, { params: parametros(params) }));
  }

  delete<T = void>(url: string, params?: Parametros): Promise<T> {
    return firstValueFrom(this.http.delete<T>(url, { params: parametros(params) }));
  }

  /** Envía un formulario multipart (fotos y archivos). */
  enviar<T>(url: string, campos: Record<string, unknown>, params?: Parametros): Promise<T> {
    const datos = new FormData();
    for (const [k, v] of Object.entries(campos)) {
      if (v === null || v === undefined || v === '') continue;
      if (Array.isArray(v)) v.forEach((x) => datos.append(k, x as Blob));
      else if (v instanceof Blob) datos.append(k, v);
      else datos.append(k, String(v));
    }
    return firstValueFrom(this.http.post<T>(url, datos, { params: parametros(params) }));
  }

  lista(nombre: string, q?: string | null, padre?: number | null, limite = 500): Promise<Opcion[]> {
    return this.get<Opcion[]>(`/api/listas/${nombre}`, { q, padre, limite });
  }

  /** Descarga un archivo (Excel, PDF, ZIP) con el nombre que manda el servidor. */
  async descargar(url: string, params?: Parametros, cuerpo?: unknown): Promise<void> {
    const r: HttpResponse<Blob> = await firstValueFrom(
      cuerpo === undefined
        ? this.http.get(url, { params: parametros(params), responseType: 'blob', observe: 'response' })
        : this.http.post(url, cuerpo, { params: parametros(params), responseType: 'blob', observe: 'response' }),
    );
    guardarBlob(r.body!, nombreArchivo(r.headers.get('Content-Disposition')) ?? 'descarga');
  }

  /** URL de una foto o archivo guardado, con el token para poder mostrarla en <img>. */
  async imagen(ruta: string): Promise<string> {
    const blob = await firstValueFrom(this.http.get('/api/archivos', { params: parametros({ ruta }), responseType: 'blob' }));
    return URL.createObjectURL(blob);
  }
}

function parametros(p?: Parametros): HttpParams {
  let r = new HttpParams();
  for (const [k, v] of Object.entries(p ?? {})) {
    if (v !== null && v !== undefined && v !== '') r = r.set(k, String(v));
  }
  return r;
}

export function nombreArchivo(cabecera: string | null): string | null {
  if (!cabecera) return null;
  const utf = /filename\*=UTF-8''([^;]+)/i.exec(cabecera);
  if (utf) return decodeURIComponent(utf[1]);
  const simple = /filename="?([^";]+)"?/i.exec(cabecera);
  return simple ? simple[1] : null;
}

export function guardarBlob(blob: Blob, nombre: string): void {
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = nombre;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 2000);
}

/** Primer día del mes actual en formato AAAA-MM-DD. */
export function inicioMes(): string {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-01`;
}

export function pesos(v: number | null | undefined): string {
  return '$' + Math.round(v ?? 0).toLocaleString('es-CO');
}

export function minutosAHoras(m: number | null | undefined): string {
  if (m === null || m === undefined) return '—';
  return `${Math.floor(m / 60)}:${String(Math.abs(m % 60)).padStart(2, '0')}`;
}
