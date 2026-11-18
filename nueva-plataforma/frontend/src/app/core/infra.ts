import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { MatSnackBar } from '@angular/material/snack-bar';
import { catchError, throwError } from 'rxjs';
import { Sesion } from './sesion';

/** Agrega el token a cada petición y cierra la sesión si el servidor la rechaza. */
export const tokenInterceptor: HttpInterceptorFn = (req, next) => {
  const sesion = inject(Sesion);
  const token = sesion.token();
  const conToken = token ? req.clone({ setHeaders: { Authorization: `Bearer ${token}` } }) : req;
  return next(conToken).pipe(
    catchError((e: HttpErrorResponse) => {
      if (e.status === 401 && !req.url.endsWith('/auth/login')) {
        sesion.salir();
      }
      return throwError(() => e);
    }),
  );
};

/** Solo deja pasar si el usuario tiene el permiso indicado en data.permiso. */
export const permisoGuard: CanActivateFn = (ruta) => {
  const sesion = inject(Sesion);
  const router = inject(Router);
  if (!sesion.autenticado()) return router.parseUrl('/login');
  if (sesion.perfil()?.debeCambiarClave && ruta.routeConfig?.path !== 'cuenta/clave' && ruta.routeConfig?.path !== '') {
    return router.parseUrl('/cuenta/clave');
  }
  const permiso = ruta.data['permiso'] as string | string[] | undefined;
  const lista = permiso === undefined ? [] : Array.isArray(permiso) ? permiso : [permiso];
  if (lista.length && !lista.some((p) => sesion.tiene(p))) return router.parseUrl(sesion.inicio());
  return true;
};

/** Mensajes al usuario. Los errores muestran el texto que manda el backend ({"mensaje": ...}). */
@Injectable({ providedIn: 'root' })
export class Avisos {
  private readonly snack = inject(MatSnackBar);

  ok(texto: string): void {
    this.snack.open(texto, 'Cerrar', { duration: 3500 });
  }

  error(e: unknown): void {
    this.snack.open(mensajeDe(e), 'Cerrar', { duration: 7000, panelClass: 'snack-error' });
  }
}

export function mensajeDe(e: unknown): string {
  if (e instanceof HttpErrorResponse) {
    if (e.status === 0) return 'No hay conexión con el servidor.';
    if (e.status === 403) return e.error?.mensaje ?? 'No tiene permiso para esta acción.';
    return e.error?.mensaje ?? `Error ${e.status}`;
  }
  return e instanceof Error ? e.message : 'Ocurrió un error.';
}

/** Ubicación actual del celular. Pide permiso de GPS la primera vez. */
export function ubicacionActual(): Promise<{ latitud: number; longitud: number }> {
  return new Promise((resolve, reject) => {
    if (!('geolocation' in navigator)) {
      reject(new Error('Este dispositivo no tiene GPS.'));
      return;
    }
    navigator.geolocation.getCurrentPosition(
      (p) => resolve({ latitud: p.coords.latitude, longitud: p.coords.longitude }),
      () => reject(new Error('Active el GPS y permita la ubicación para continuar.')),
      { enableHighAccuracy: true, timeout: 20000, maximumAge: 30000 },
    );
  });
}

export function hoyIso(desplazamientoDias = 0): string {
  const d = new Date();
  d.setDate(d.getDate() + desplazamientoDias);
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const dia = String(d.getDate()).padStart(2, '0');
  return `${d.getFullYear()}-${m}-${dia}`;
}

export function hora(h: string | null | undefined): string {
  return h ? h.substring(0, 5) : '—';
}
