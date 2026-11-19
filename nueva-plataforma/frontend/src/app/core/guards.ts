import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { HttpInterceptorFn } from '@angular/common/http';
import { tap } from 'rxjs';
import { Auth } from './auth';

/**
 * Reglas de index.php:
 *  C. Si NO estás logueado y NO es pública -> al login
 *  RBAC: si el rol no tiene la ruta en rol_permisos -> 404 "Página no encontrada o acceso denegado"
 */
export const authGuard: CanActivateFn = async () => {
  const auth = inject(Auth);
  const router = inject(Router);
  const s = await auth.cargar(true).catch(() => null);
  if (!s?.logueado) {
    return router.parseUrl('/login');
  }
  return true;
};

export const paginaGuard: CanActivateFn = async (route) => {
  const auth = inject(Auth);
  const router = inject(Router);
  const s = await auth.cargar().catch(() => null);
  if (!s?.logueado) return router.parseUrl('/login');
  const pagina = (route.data?.['pagina'] as string) || route.routeConfig?.path?.split('/')[0] || '';
  if (!auth.puede(pagina)) {
    return router.parseUrl('/error404');
  }
  return true;
};

/** Regla D: si ya estás logueado e intentas ir al login -> al inicio. */
export const loginGuard: CanActivateFn = async () => {
  const auth = inject(Auth);
  const router = inject(Router);
  const s = await auth.cargar(true).catch(() => null);
  if (s?.logueado) return router.parseUrl('/inicio');
  return true;
};

/** Si el backend responde 401 (sesión expirada) -> redirigir al login. */
export const sesionInterceptor: HttpInterceptorFn = (req, next) =>
  next(req).pipe(
    tap({
      error: (e) => {
        if (e?.status === 401 && !location.pathname.startsWith('/login')) {
          location.href = '/login';
        }
      }
    })
  );
