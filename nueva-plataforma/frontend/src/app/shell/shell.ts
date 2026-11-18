import { BreakpointObserver } from '@angular/cdk/layout';
import { Component, computed, inject, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatMenuModule } from '@angular/material/menu';
import { MatSidenavModule } from '@angular/material/sidenav';
import { MatTooltipModule } from '@angular/material/tooltip';
import { NavigationEnd, Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { filter, map, startWith } from 'rxjs';
import { Sesion } from '../core/sesion';

interface Enlace {
  ruta: string;
  texto: string;
  icono: string;
  /** Basta con tener uno de estos permisos; vacío = todos los usuarios. */
  permisos: string[];
}

interface Grupo {
  titulo: string;
  enlaces: Enlace[];
}

/** Menú en el orden de uso: lo del día primero, lo de consulta y configuración al final. */
const MENU: Grupo[] = [
  { titulo: '', enlaces: [{ ruta: '/inicio', texto: 'Inicio', icono: 'home', permisos: [] }] },
  {
    titulo: 'Mi trabajo',
    enlaces: [
      { ruta: '/campo', texto: 'Mi día', icono: 'two_wheeler', permisos: ['CAMPO'] },
      { ruta: '/campo/historial', texto: 'Historial de un punto', icono: 'history', permisos: ['CAMPO'] },
      { ruta: '/campo/inventario', texto: 'Mi inventario', icono: 'inventory_2', permisos: ['CAMPO'] },
      { ruta: '/campo/horas-extra', texto: 'Horas extra', icono: 'more_time', permisos: ['CAMPO'] },
      { ruta: '/campo/parqueaderos', texto: 'Parqueaderos', icono: 'local_parking', permisos: ['CAMPO'] },
      { ruta: '/campo/tiempos', texto: 'Mis tiempos', icono: 'timer', permisos: ['CAMPO'] },
    ],
  },
  {
    titulo: 'Operación del día',
    enlaces: [
      { ruta: '/cargas', texto: 'Excel de Prosegur', icono: 'upload_file', permisos: ['PLANEACION'] },
      { ruta: '/geolocalizar', texto: 'Ubicar máquinas', icono: 'add_location_alt', permisos: ['PLANEACION'] },
      { ruta: '/planeacion', texto: 'Rutas del día', icono: 'route', permisos: ['PLANEACION'] },
      { ruta: '/seguimiento', texto: 'Seguimiento', icono: 'monitoring', permisos: ['SEGUIMIENTO'] },
      { ruta: '/rastreo', texto: 'Rastreo de técnicos', icono: 'share_location', permisos: ['SEGUIMIENTO', 'PERSONAL'] },
      { ruta: '/maquinas', texto: 'Máquinas VIP', icono: 'star', permisos: ['CONFIGURACION'] },
    ],
  },
  {
    titulo: 'Servicios',
    enlaces: [
      { ruta: '/ordenes', texto: 'Servicios por día', icono: 'calendar_month', permisos: ['ORDENES'] },
      { ruta: '/ordenes/buscar', texto: 'Buscar servicios', icono: 'manage_search', permisos: ['ORDENES'] },
      { ruta: '/ordenes/registrar', texto: 'Registrar servicios', icono: 'post_add', permisos: ['ORDENES'] },
      { ruta: '/ordenes/pdf', texto: 'PDF de servicios', icono: 'picture_as_pdf', permisos: ['ORDENES'] },
      { ruta: '/ordenes/exportar', texto: 'Exportar servicios', icono: 'table_view', permisos: ['ORDENES'] },
    ],
  },
  {
    titulo: 'Logística',
    enlaces: [
      { ruta: '/inventario', texto: 'Inventario de técnicos', icono: 'inventory', permisos: ['INVENTARIO'] },
      { ruta: '/inventario/solicitudes', texto: 'Solicitudes de repuestos', icono: 'shopping_cart', permisos: ['INVENTARIO'] },
      { ruta: '/inventario/devoluciones', texto: 'Devoluciones', icono: 'assignment_return', permisos: ['INVENTARIO'] },
      { ruta: '/admin/c/repuestos', texto: 'Repuestos', icono: 'build', permisos: ['INVENTARIO', 'ADMINISTRACION'] },
      { ruta: '/remisiones', texto: 'Remisiones', icono: 'receipt_long', permisos: ['REMISIONES'] },
      { ruta: '/remisiones/salteadas', texto: 'Remisiones salteadas', icono: 'rule', permisos: ['REMISIONES'] },
    ],
  },
  {
    titulo: 'Motorizados',
    enlaces: [
      { ruta: '/personal/horas-extra', texto: 'Horas extra', icono: 'more_time', permisos: ['PERSONAL'] },
      { ruta: '/personal/parqueaderos', texto: 'Parqueaderos', icono: 'local_parking', permisos: ['PERSONAL'] },
      { ruta: '/personal/jornadas', texto: 'Entradas de turno', icono: 'login', permisos: ['PERSONAL'] },
      { ruta: '/personal/tiempos', texto: 'Tiempos de servicio', icono: 'timer', permisos: ['PERSONAL'] },
      { ruta: '/personal/asistencia', texto: 'Asistencia', icono: 'fingerprint', permisos: ['PERSONAL'] },
      { ruta: '/personal/alertas', texto: 'Alertas logísticas', icono: 'notifications_active', permisos: ['PERSONAL'] },
    ],
  },
  {
    titulo: 'Reportes',
    enlaces: [
      { ruta: '/reportes/ejecutivo', texto: 'Tablero ejecutivo', icono: 'insights', permisos: ['REPORTES'] },
      { ruta: '/reportes/tecnico', texto: 'Reporte técnico', icono: 'engineering', permisos: ['REPORTES'] },
      { ruta: '/reportes/repuestos', texto: 'Repuestos', icono: 'construction', permisos: ['REPORTES'] },
      { ruta: '/reportes/maquinas', texto: 'Máquinas', icono: 'point_of_sale', permisos: ['REPORTES'] },
      { ruta: '/reportes/historial', texto: 'Historial y cumplimiento', icono: 'fact_check', permisos: ['REPORTES'] },
      { ruta: '/reportes/trazabilidad', texto: 'Trazabilidad de repuestos', icono: 'timeline', permisos: ['REPORTES'] },
      { ruta: '/reportes/tarifas', texto: 'Tarifas', icono: 'percent', permisos: ['REPORTES'] },
    ],
  },
  {
    titulo: 'Transporte y costos',
    enlaces: [
      { ruta: '/transporte', texto: 'Transportes', icono: 'local_shipping', permisos: ['TRANSPORTE'] },
      { ruta: '/costos', texto: 'Costos', icono: 'payments', permisos: ['COSTOS'] },
      { ruta: '/costos/cotizaciones', texto: 'Analizador de cotizaciones', icono: 'request_quote', permisos: ['COSTOS'] },
    ],
  },
  {
    titulo: 'Administración',
    enlaces: [
      { ruta: '/admin/usuarios', texto: 'Usuarios', icono: 'group', permisos: ['ADMINISTRACION'] },
      { ruta: '/admin/roles', texto: 'Qué puede hacer cada rol', icono: 'admin_panel_settings', permisos: ['ADMINISTRACION'] },
      { ruta: '/admin/catalogos', texto: 'Catálogos', icono: 'list_alt', permisos: ['ADMINISTRACION'] },
      { ruta: '/admin/tarifas', texto: 'Tarifas', icono: 'sell', permisos: ['ADMINISTRACION'] },
      { ruta: '/admin/importar', texto: 'Importaciones', icono: 'upload', permisos: ['ADMINISTRACION'] },
      { ruta: '/admin/informacion', texto: 'Información de la base', icono: 'database', permisos: ['ADMINISTRACION'] },
    ],
  },
];

const CLAVE_PLEGADOS = 'inees.menu.plegados';

/**
 * Marco de la aplicación: riel azul noche con el logo, los módulos que el usuario puede usar (en grupos plegables) y
 * su cuenta abajo. En el celular el riel se abre desde la barra superior.
 */
@Component({
  selector: 'app-shell',
  imports: [RouterOutlet, RouterLink, RouterLinkActive, MatButtonModule, MatIconModule, MatMenuModule, MatSidenavModule, MatTooltipModule],
  template: `
    <mat-sidenav-container class="contenedor">
      <mat-sidenav #lateral class="riel" [mode]="movil() ? 'over' : 'side'" [opened]="!movil()" [fixedInViewport]="movil()">
        <div class="riel-in">
          <a class="logo" [routerLink]="sesion.inicio()" (click)="movil() && lateral.close()">
            <img src="marca/inees-logo-sobre-azul.svg" alt="INEES Secure Ai Systems" width="168" height="50" />
            <span class="producto">Plataforma de mantenimientos</span>
          </a>
          <nav aria-label="Módulos">
            @for (g of grupos(); track g.titulo) {
              @if (g.titulo) {
                <button type="button" class="grupo" [attr.aria-expanded]="abierto(g)" (click)="plegar(g.titulo)">
                  <span>{{ g.titulo }}</span>
                  <mat-icon class="chevron" [class.cerrado]="!abierto(g)">expand_more</mat-icon>
                </button>
              }
              @if (abierto(g)) {
                <div class="enlaces">
                  @for (e of g.enlaces; track e.ruta) {
                    <a class="enlace" [routerLink]="e.ruta" routerLinkActive="activo" [routerLinkActiveOptions]="{ exact: true }"
                       ariaCurrentWhenActive="page" (click)="movil() && lateral.close()">
                      <mat-icon>{{ e.icono }}</mat-icon><span class="texto">{{ e.texto }}</span>
                      <span class="corte" aria-hidden="true"><i></i><i></i><i></i></span>
                    </a>
                  }
                </div>
              }
            }
          </nav>
          <button type="button" class="cuenta" [matMenuTriggerFor]="menuCuenta" aria-label="Mi cuenta">
            <span class="avatar">{{ iniciales() }}</span>
            <span class="quien"><strong>{{ sesion.perfil()?.nombre }}</strong><small>{{ sesion.perfil()?.nombreRol }}</small></span>
            <mat-icon>unfold_more</mat-icon>
          </button>
        </div>
      </mat-sidenav>

      <mat-sidenav-content class="trabajo">
        @if (movil()) {
          <header class="barra-movil">
            <button mat-icon-button (click)="lateral.toggle()" aria-label="Abrir el menú"><mat-icon>menu</mat-icon></button>
            <a class="logo-movil" [routerLink]="sesion.inicio()"><img src="marca/inees-logo-sobre-azul.svg" alt="INEES" width="104" height="31" /></a>
            <button type="button" class="avatar boton" [matMenuTriggerFor]="menuCuenta" aria-label="Mi cuenta">{{ iniciales() }}</button>
          </header>
        }
        <main><router-outlet /></main>
      </mat-sidenav-content>
    </mat-sidenav-container>

    <mat-menu #menuCuenta="matMenu" xPosition="after" yPosition="above">
      <div class="menu-perfil">
        <strong>{{ sesion.perfil()?.nombre }}</strong>
        <span>{{ sesion.perfil()?.nombreRol }}</span>
        @if (sesion.perfil()?.ultimoAcceso) { <span>Último acceso: {{ sesion.perfil()?.ultimoAcceso }}</span> }
      </div>
      <a mat-menu-item routerLink="/cuenta/clave"><mat-icon>key</mat-icon> Cambiar contraseña</a>
      <button mat-menu-item (click)="sesion.salir()"><mat-icon>logout</mat-icon> Cerrar sesión</button>
    </mat-menu>
  `,
  styles: `
    :host { display: block; height: 100vh; height: 100dvh; }
    .contenedor {
      height: 100%;
      --mat-sidenav-container-background-color: var(--marca-noche);
      --mat-sidenav-container-shape: 0;
      --mat-sidenav-container-divider-color: transparent;
      --mat-sidenav-container-width: 264px;
      --mat-sidenav-content-background-color: var(--fondo);
      --mat-sidenav-scrim-color: rgb(4 27 44 / 0.55);
    }
    .riel { width: 264px; color: rgb(255 255 255 / 0.8); }
    .riel-in { display: flex; flex-direction: column; height: 100%; }

    .logo { display: block; padding: 22px 22px 18px; text-decoration: none; border-bottom: 1px solid rgb(255 255 255 / 0.1); }
    .logo img { display: block; height: 42px; width: auto; }
    .producto { display: block; margin-top: 10px; font-size: 12.5px; font-stretch: 104%; color: rgb(255 255 255 / 0.62); }

    nav { flex: 1; overflow-y: auto; padding: 10px 0 16px; scrollbar-width: thin; scrollbar-color: rgb(255 255 255 / 0.22) transparent; }
    .grupo {
      display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 14px 22px 6px;
      background: none; border: 0; cursor: pointer; font: inherit; font-size: 11px; font-weight: 600; letter-spacing: 0.07em;
      text-transform: uppercase; color: rgb(255 255 255 / 0.5); transition: color 0.2s ease;
    }
    .grupo:hover { color: rgb(255 255 255 / 0.85); }
    .chevron { font-size: 18px; width: 18px; height: 18px; transition: transform 0.2s var(--curva); }
    .chevron.cerrado { transform: rotate(-90deg); }
    .enlaces { display: flex; flex-direction: column; gap: 1px; padding: 0 12px; }
    .enlace {
      position: relative; display: flex; align-items: center; gap: 12px; height: 38px; padding: 0 10px; border-radius: 8px;
      color: rgb(255 255 255 / 0.8); text-decoration: none; font-size: 14px; transition: background-color 0.15s ease, color 0.15s ease;
    }
    .enlace mat-icon { font-size: 20px; width: 20px; height: 20px; color: rgb(255 255 255 / 0.55); transition: color 0.15s ease; }
    .enlace .texto { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .enlace:hover { background: rgb(255 255 255 / 0.07); color: #fff; }
    .enlace:hover mat-icon { color: #fff; }
    .enlace:active { transform: scale(0.99); }
    .enlace.activo { background: var(--marca-azul); color: #fff; font-weight: 600; }
    .enlace.activo mat-icon { color: #fff; }
    .enlace:focus-visible { outline-color: var(--marca-verde-c); outline-offset: -2px; }
    /* firma: las tres franjas del corte de la nube marcan dónde está el usuario */
    .corte { display: none; gap: 3px; margin-right: 2px; }
    .corte i { width: 3px; height: 14px; background: var(--marca-verde-c); transform: skewX(-32deg); }
    .enlace.activo .corte { display: flex; }

    .cuenta {
      display: flex; align-items: center; gap: 10px; margin: 8px 12px 12px; padding: 10px; border-radius: 10px; text-align: left;
      background: rgb(255 255 255 / 0.06); border: 1px solid rgb(255 255 255 / 0.1); color: #fff; cursor: pointer; font: inherit;
      transition: background-color 0.15s ease;
    }
    .cuenta:hover { background: rgb(255 255 255 / 0.1); }
    .cuenta mat-icon { color: rgb(255 255 255 / 0.6); font-size: 20px; width: 20px; height: 20px; }
    .quien { flex: 1; min-width: 0; display: flex; flex-direction: column; }
    .quien strong { font-size: 13.5px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .quien small { font-size: 12px; color: rgb(255 255 255 / 0.62); }
    .avatar {
      display: grid; place-items: center; flex: none; width: 34px; height: 34px; border-radius: 50%;
      background: var(--marca-verde); color: #fff; font-size: 13px; font-weight: 700; letter-spacing: 0.02em;
    }
    .avatar.boton { border: 2px solid rgb(255 255 255 / 0.25); cursor: pointer; font-family: inherit; }

    .trabajo { display: flex; flex-direction: column; }
    main { flex: 1; }
    .barra-movil {
      position: sticky; top: 0; z-index: 5; display: flex; align-items: center; gap: 6px;
      height: calc(56px + env(safe-area-inset-top)); padding: env(safe-area-inset-top) 12px 0 4px;
      background: var(--marca-noche); color: #fff; box-shadow: 0 10px 24px -18px rgb(4 27 44 / 0.7);
      --mat-icon-button-icon-color: #fff;
    }
    .logo-movil { margin-right: auto; display: flex; }
    .logo-movil img { height: 30px; width: auto; }

    .menu-perfil { display: flex; flex-direction: column; gap: 2px; padding: 10px 16px 12px; border-bottom: 1px solid var(--linea); margin-bottom: 4px;
      strong { color: var(--marca-noche); } span { color: var(--mut); font-size: 12px; } }
    @media print { .riel, .barra-movil { display: none !important; } }
  `,
})
export class Shell {
  protected readonly sesion = inject(Sesion);
  private readonly bp = inject(BreakpointObserver);
  private readonly router = inject(Router);
  protected readonly movil = toSignal(this.bp.observe('(max-width: 900px)').pipe(map((r) => r.matches)), { initialValue: false });
  private readonly ruta = toSignal(
    this.router.events.pipe(filter((e) => e instanceof NavigationEnd), map(() => this.router.url), startWith(this.router.url)),
    { initialValue: this.router.url },
  );
  private readonly plegados = signal<Set<string>>(leerPlegados());

  protected readonly grupos = computed(() => {
    const permisos = this.sesion.perfil()?.permisos ?? [];
    return MENU.map((g) => ({ ...g, enlaces: g.enlaces.filter((e) => !e.permisos.length || e.permisos.some((p) => permisos.includes(p))) }))
      .filter((g) => g.enlaces.length);
  });

  protected readonly iniciales = computed(() => {
    const partes = (this.sesion.perfil()?.nombre ?? '').trim().split(/\s+/).filter(Boolean);
    return ((partes[0]?.[0] ?? '') + (partes[1]?.[0] ?? '')).toUpperCase() || '·';
  });

  /** Un grupo plegado se muestra igual si contiene la pantalla abierta. */
  protected abierto(g: Grupo): boolean {
    if (!g.titulo || !this.plegados().has(g.titulo)) return true;
    const url = (this.ruta() ?? '').split('?')[0];
    return g.enlaces.some((e) => e.ruta === url);
  }

  protected plegar(titulo: string): void {
    this.plegados.update((p) => {
      const n = new Set(p);
      n.has(titulo) ? n.delete(titulo) : n.add(titulo);
      try {
        localStorage.setItem(CLAVE_PLEGADOS, JSON.stringify([...n]));
      } catch {
        // sin almacenamiento local: el plegado dura hasta recargar
      }
      return n;
    });
  }
}

function leerPlegados(): Set<string> {
  try {
    return new Set<string>(JSON.parse(localStorage.getItem(CLAVE_PLEGADOS) ?? '[]'));
  } catch {
    return new Set();
  }
}
