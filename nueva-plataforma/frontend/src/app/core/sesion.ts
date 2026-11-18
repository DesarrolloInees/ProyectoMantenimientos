import { HttpClient } from '@angular/common/http';
import { computed, inject, Injectable, signal } from '@angular/core';
import { Router } from '@angular/router';
import { firstValueFrom } from 'rxjs';
import { Perfil } from './modelos';

const CLAVE_TOKEN = 'inees.token';
const CLAVE_PERFIL = 'inees.perfil';

function leer(clave: string): string | null {
  try {
    return localStorage.getItem(clave);
  } catch {
    return null;
  }
}

/** Sesión del usuario: token JWT y perfil con sus permisos (PLANEACION, SEGUIMIENTO, CONFIGURACION, CAMPO). */
@Injectable({ providedIn: 'root' })
export class Sesion {
  private readonly http = inject(HttpClient);
  private readonly router = inject(Router);

  readonly token = signal<string | null>(leer(CLAVE_TOKEN));
  readonly perfil = signal<Perfil | null>(JSON.parse(leer(CLAVE_PERFIL) ?? 'null'));
  readonly autenticado = computed(() => !!this.token() && !!this.perfil());
  readonly esTecnico = computed(() => this.tiene('CAMPO'));

  tiene(permiso: string): boolean {
    return this.perfil()?.permisos.includes(permiso) ?? false;
  }

  /** Página de inicio según lo que el usuario puede hacer. */
  inicio(): string {
    if (this.perfil()?.debeCambiarClave) return '/cuenta/clave';
    return this.tiene('CAMPO') ? '/campo' : '/inicio';
  }

  /** Tras cambiar la contraseña obligatoria, el perfil deja de pedirla. */
  claveCambiada(): void {
    const p = this.perfil();
    if (p) this.guardar(this.token()!, { ...p, debeCambiarClave: false });
  }

  async ingresar(usuario: string, clave: string): Promise<void> {
    const r = await firstValueFrom(
      this.http.post<{ token: string; perfil: Perfil }>('/api/auth/login', { usuario, clave }),
    );
    this.guardar(r.token, r.perfil);
    await this.router.navigateByUrl(this.inicio());
  }

  salir(): void {
    try {
      localStorage.removeItem(CLAVE_TOKEN);
      localStorage.removeItem(CLAVE_PERFIL);
    } catch {
      /* almacenamiento no disponible */
    }
    this.token.set(null);
    this.perfil.set(null);
    this.router.navigateByUrl('/login');
  }

  private guardar(token: string, perfil: Perfil): void {
    try {
      localStorage.setItem(CLAVE_TOKEN, token);
      localStorage.setItem(CLAVE_PERFIL, JSON.stringify(perfil));
    } catch {
      /* la sesión vive solo en memoria */
    }
    this.token.set(token);
    this.perfil.set(perfil);
  }
}
