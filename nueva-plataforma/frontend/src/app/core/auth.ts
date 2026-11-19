import { Injectable, inject, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { firstValueFrom } from 'rxjs';

export interface SesionInfo {
  logueado: boolean;
  temporal: boolean;
  usuario_id: number | null;
  usuario_name: string;
  nivel_acceso: number;
  usuario_cargo: string;
  permisos: string[];
}

/** Estado de $_SESSION expuesto al frontend. */
@Injectable({ providedIn: 'root' })
export class Auth {
  private http = inject(HttpClient);
  readonly sesion = signal<SesionInfo | null>(null);
  private cargando: Promise<SesionInfo> | null = null;

  cargar(forzar = false): Promise<SesionInfo> {
    if (!forzar && this.sesion()) return Promise.resolve(this.sesion()!);
    if (!forzar && this.cargando) return this.cargando;
    this.cargando = firstValueFrom(this.http.get<SesionInfo>('/api/sesion')).then((s) => {
      this.sesion.set(s);
      this.cargando = null;
      return s;
    });
    return this.cargando;
  }

  get nivel(): number {
    return Number(this.sesion()?.nivel_acceso ?? 0);
  }

  get usuarioId(): number {
    return Number(this.sesion()?.usuario_id ?? 0);
  }

  puede(pagina: string): boolean {
    const s = this.sesion();
    if (!s?.logueado) return false;
    const p = pagina.toLowerCase();
    return s.permisos.some((x) => x.toLowerCase() === p);
  }

  async logout(): Promise<void> {
    try {
      await firstValueFrom(this.http.post('/api/logout', {}));
    } catch {}
    this.sesion.set(null);
    window.location.href = '/login';
  }
}
