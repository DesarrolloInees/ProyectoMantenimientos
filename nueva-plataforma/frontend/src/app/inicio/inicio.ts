import { DatePipe, DecimalPipe, NgTemplateOutlet } from '@angular/common';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { RouterLink } from '@angular/router';
import { Barras, Kpi } from '../compartido/graficos';
import { Avisos, hoyIso } from '../core/infra';
import { Rest } from '../core/rest';
import { Sesion } from '../core/sesion';

interface Oficina {
  serviciosMes: number;
  clientes: number;
  tecnicos: number;
  maquinas: number;
  bolsaPendiente: number;
  serviciosHoy: number;
  horasExtraPendientes: number;
  solicitudesPendientes: number;
  devolucionesPendientes: number;
}

interface Guia {
  proximoDia: string;
  cargasConfirmadas: number;
  cargasEnRevision: number;
  solicitudesProximo: number;
  sinUbicacion: number;
  bolsaProximo: number;
  rutasBorrador: number;
  rutasPublicadas: number;
  motorizadosDisponibles: number;
  rutasHoy: number;
  paradasHoy: number;
  paradasHechasHoy: number;
  paradasSinIngresoHoy: number;
  serviciosEnCurso: number;
  cerradosHoy: number;
  conNovedadHoy: number;
}

interface InicioDatos {
  oficina: Oficina | null;
  tecnico: { jornadaHoy: string | null; novedadJornada: string | null; serviciosMes: Record<string, number>; totalMes: number; pendientesHoy: number } | null;
  guia: Guia | null;
}

type Estado = 'hecho' | 'pendiente' | 'alerta' | 'info';

/** Un paso de la guía del día: qué hacer, para qué sirve, cómo va y a dónde ir. */
interface Paso {
  titulo: string;
  ayuda: string;
  estado: Estado;
  detalle: string;
  ruta: string;
  query?: Record<string, string>;
  boton: string;
}

/**
 * Inicio guiado por rol. La oficina ve, en orden, lo que hay que preparar para el próximo día hábil y cómo va hoy, con
 * un botón a cada tarea; cada rol solo ve los pasos de lo que puede hacer. El motorizado entra directo a «Mi día».
 */
@Component({
  selector: 'app-inicio',
  imports: [Kpi, Barras, RouterLink, MatButtonModule, MatIconModule, DatePipe, DecimalPipe, NgTemplateOutlet],
  template: `
    <div class="pagina">
      <div class="encabezado">
        <h1>Hola, {{ primerNombre() }}</h1>
        <span class="fecha">{{ hoy | date: "EEEE d 'de' MMMM" }} · {{ sesion.perfil()?.nombreRol }}</span>
      </div>

      @if (datos()?.oficina; as o) {
        @if (preparar().length) {
          <section class="tarjeta guia">
            <div class="guia-cab">
              <h2>Prepare el {{ datos()!.guia!.proximoDia | date: "EEEE d 'de' MMMM" }}</h2>
              <span class="avance-texto"><strong>{{ listos(preparar()) }}</strong> de {{ preparar().length }} pasos listos</span>
            </div>
            <div class="franjas" aria-hidden="true">
              @for (p of preparar(); track p.titulo) { <i [class.hecho]="p.estado === 'hecho'" [class.alerta]="p.estado === 'alerta'"></i> }
            </div>
            <ol class="pasos">
              @for (p of preparar(); track p.titulo; let i = $index) {
                <ng-container *ngTemplateOutlet="paso; context: { $implicit: p, n: i + 1 }" />
              }
            </ol>
          </section>
        }

        @if (hoyPasos().length) {
          <section class="tarjeta guia">
            <div class="guia-cab"><h2>Hoy en la calle</h2></div>
            @if (datos()!.guia!.paradasHoy) {
              <div class="avance" [class.alerta]="datos()!.guia!.paradasSinIngresoHoy > 0" role="img"
                   [attr.aria-label]="datos()!.guia!.paradasHechasHoy + ' de ' + datos()!.guia!.paradasHoy + ' servicios hechos'">
                <span [style.width.%]="(datos()!.guia!.paradasHechasHoy / datos()!.guia!.paradasHoy) * 100"></span>
              </div>
            }
            <ol class="pasos">
              @for (p of hoyPasos(); track p.titulo; let i = $index) {
                <ng-container *ngTemplateOutlet="paso; context: { $implicit: p, n: preparar().length + i + 1 }" />
              }
            </ol>
          </section>
        }

        @if (pendientes(o).length) {
          <section class="tarjeta pendientes">
            <h2>Por aprobar o atender</h2>
            <ul>
              @for (p of pendientes(o); track p.ruta) {
                <li>
                  <a [routerLink]="p.ruta">
                    <mat-icon class="ico">{{ p.icono }}</mat-icon>
                    <span class="txt"><strong>{{ p.texto }}</strong><small>{{ p.ayuda }}</small></span>
                    <span class="cuenta" [class.cero]="!p.n">{{ p.n | number }}</span>
                    <mat-icon class="flecha">chevron_right</mat-icon>
                  </a>
                </li>
              }
            </ul>
          </section>
        }

        <dl class="base">
          <div><dt>Servicios este mes</dt><dd>{{ o.serviciosMes | number }}</dd></div>
          <div><dt>Motorizados activos</dt><dd>{{ o.tecnicos | number }}</dd></div>
          <div><dt>Clientes activos</dt><dd>{{ o.clientes | number }}</dd></div>
          <div><dt>Máquinas activas</dt><dd>{{ o.maquinas | number }}</dd></div>
        </dl>
      }

      @if (datos()?.tecnico; as t) {
        <div class="kpis">
          <app-kpi [valor]="t.jornadaHoy ? t.jornadaHoy.substring(0, 5) : 'Sin iniciar'" texto="Jornada de hoy" [alerta]="!t.jornadaHoy" />
          <app-kpi [valor]="t.pendientesHoy" texto="Servicios pendientes hoy" />
          <app-kpi [valor]="t.totalMes" texto="Servicios este mes" />
        </div>
        <section class="tarjeta">
          <app-barras titulo="Mis servicios del mes por tipo" [barras]="barrasTecnico()" color="#2d7f3e" />
        </section>
        <a mat-flat-button routerLink="/campo" class="ir boton-campo"><mat-icon>two_wheeler</mat-icon> Ir a mi día</a>
      }
    </div>

    <ng-template #paso let-p let-n="n">
      <li class="paso" [class]="p.estado">
        <span class="num" aria-hidden="true">
          @if (p.estado === 'hecho') { <mat-icon>check</mat-icon> } @else { {{ n }} }
        </span>
        <div class="txt">
          <strong>{{ p.titulo }}</strong>
          <p>{{ p.ayuda }}</p>
          <span class="estado">{{ p.detalle }}</span>
        </div>
        @if (p.estado === 'hecho') {
          <a mat-stroked-button [routerLink]="p.ruta" [queryParams]="p.query">{{ p.boton }}</a>
        } @else {
          <a mat-flat-button [routerLink]="p.ruta" [queryParams]="p.query">{{ p.boton }}</a>
        }
      </li>
    </ng-template>
  `,
  styles: `
    .encabezado { align-items: baseline; }
    .encabezado h1 { margin-right: 0; }
    .fecha { color: var(--mut); font-size: 15px; }
    .fecha::first-letter { text-transform: uppercase; }

    .guia { padding: 22px 24px 10px; margin-bottom: 16px; max-width: 980px; }
    .guia-cab { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 12px; }
    .guia-cab h2 { font-size: 19px; letter-spacing: -0.015em; }
    .guia-cab h2::first-letter { text-transform: uppercase; }
    .avance-texto { color: var(--tinta-2); font-size: 14px; }
    .avance-texto strong { color: var(--marca-noche); }
    .guia > .franjas, .guia > .avance { margin-bottom: 6px; }
    .guia > .franjas > i { height: 8px; }

    .pasos { list-style: none; margin: 0; padding: 0; }
    .paso { display: grid; grid-template-columns: 36px 1fr auto; gap: 14px; align-items: center; padding: 16px 0; border-bottom: 1px solid var(--linea); }
    .paso:last-child { border-bottom: none; }
    .num { display: grid; place-items: center; width: 32px; height: 32px; border-radius: 8px; background: var(--marca-noche); color: #fff;
      font-weight: 600; font-variant-numeric: tabular-nums; }
    .num mat-icon { font-size: 20px; width: 20px; height: 20px; }
    .paso.hecho .num { background: var(--marca-verde); }
    .paso.alerta .num { background: var(--aviso-solido); color: var(--tinta); }
    .paso.info .num { background: var(--linea-2); color: var(--tinta-2); }
    .txt strong { display: block; font-size: 15.5px; color: var(--marca-noche); }
    .txt p { margin: 3px 0 6px; color: var(--tinta-2); font-size: 13.5px; line-height: 1.45; max-width: 60ch; }
    .estado { display: inline-block; font-size: 12.5px; font-weight: 600; padding: 2px 8px; border-radius: 6px; background: var(--fondo); color: var(--tinta-2); }
    .paso.hecho .estado { background: var(--ok-fondo); color: var(--ok); }
    .paso.alerta .estado { background: var(--aviso-fondo); color: var(--aviso); }
    .paso.hecho .txt strong { color: var(--tinta-2); }

    .pendientes { padding: 20px 8px 8px; margin-bottom: 16px; max-width: 980px; }
    .pendientes h2 { font-size: 17px; padding: 0 14px 8px; }
    .pendientes ul { list-style: none; margin: 0; padding: 0; }
    .pendientes li + li { border-top: 1px solid var(--linea); }
    .pendientes a { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 8px; color: var(--tinta); text-decoration: none;
      transition: background-color 0.15s ease; }
    .pendientes a:hover { background: var(--fondo-2); }
    .pendientes a:hover .flecha { transform: translateX(3px); color: var(--marca-verde); }
    .ico { color: var(--marca-azul); font-size: 22px; width: 22px; height: 22px; }
    .pendientes .txt { flex: 1; min-width: 0; }
    .pendientes .txt strong { font-size: 14.5px; font-weight: 500; color: var(--tinta); }
    .pendientes .txt small { display: block; color: var(--mut); font-size: 12.5px; }
    .cuenta { min-width: 32px; padding: 2px 8px; border-radius: 6px; text-align: center; font-weight: 600; font-variant-numeric: tabular-nums;
      background: var(--marca-azul-t); color: var(--marca-azul); }
    .cuenta.cero { background: var(--fondo); color: var(--mut); font-weight: 500; }
    .flecha { color: var(--linea-2); transition: transform 0.2s var(--curva), color 0.2s ease; }

    .base { display: flex; flex-wrap: wrap; gap: 8px 40px; margin: 4px 0 0; padding: 14px 4px; border-top: 1px solid var(--linea-2); max-width: 980px; }
    .base div { display: flex; align-items: baseline; gap: 8px; }
    .base dt { color: var(--mut); font-size: 13px; }
    .base dd { margin: 0; font-weight: 600; color: var(--marca-noche); font-variant-numeric: tabular-nums; }

    .ir { margin-top: 16px; width: 100%; }
    @media (max-width: 640px) {
      .guia { padding: 18px 16px 6px; }
      .paso { grid-template-columns: 32px 1fr; }
      .paso > a { grid-column: 1 / -1; }
      .base { gap: 8px 24px; }
    }
  `,
})
export class Inicio implements OnInit {
  protected readonly sesion = inject(Sesion);
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected readonly datos = signal<InicioDatos | null>(null);
  protected readonly hoy = new Date();

  protected readonly primerNombre = computed(() => {
    const n = (this.sesion.perfil()?.nombre ?? '').trim().split(/\s+/)[0] ?? '';
    return n ? n.charAt(0).toUpperCase() + n.slice(1).toLowerCase() : '';
  });

  /** Pasos para dejar listo el próximo día hábil (quien arma las rutas). */
  protected readonly preparar = computed<Paso[]>(() => {
    const g = this.datos()?.guia;
    if (!g || !this.sesion.tiene('PLANEACION')) return [];
    const dia = new Date(g.proximoDia + 'T12:00:00').toLocaleDateString('es-CO', { weekday: 'long', day: 'numeric' });
    const rutas = g.rutasBorrador + g.rutasPublicadas;
    return [
      {
        titulo: 'Cargue el Excel de Prosegur',
        ayuda: `Suba el archivo con los servicios que Prosegur pide para el ${dia}. Si no llega, igual puede armar las rutas con lo pendiente.`,
        estado: g.cargasConfirmadas ? 'hecho' : g.cargasEnRevision ? 'alerta' : 'pendiente',
        detalle: g.cargasConfirmadas
          ? `Cargado y confirmado · ${g.solicitudesProximo} solicitudes`
          : g.cargasEnRevision ? 'Hay un Excel cargado sin confirmar' : 'Todavía no se ha cargado',
        ruta: '/cargas',
        boton: g.cargasEnRevision && !g.cargasConfirmadas ? 'Revisar y confirmar' : g.cargasConfirmadas ? 'Ver cargas' : 'Cargar el Excel',
      },
      {
        titulo: 'Ubique las máquinas que no tienen pin',
        ayuda: 'Una máquina sin ubicación no puede entrar a ninguna ruta. Ponga el pin en el mapa donde está la máquina.',
        estado: g.sinUbicacion ? 'alerta' : 'hecho',
        detalle: g.sinUbicacion ? `${g.sinUbicacion} ${g.sinUbicacion === 1 ? 'máquina sin ubicar' : 'máquinas sin ubicar'}` : 'Todas tienen ubicación',
        ruta: '/geolocalizar',
        boton: g.sinUbicacion ? 'Poner los pines' : 'Ver el mapa',
      },
      {
        titulo: 'Arme las rutas',
        ayuda: 'El sistema arma una ruta por motorizado: primero las solicitudes de Prosegur, luego lo atrasado, las VIP y lo cercano. Puede mover servicios a mano.',
        estado: rutas ? 'hecho' : 'pendiente',
        detalle: rutas ? `${rutas} ${rutas === 1 ? 'ruta armada' : 'rutas armadas'}` : `${g.bolsaProximo} servicios esperan ruta`,
        ruta: '/planeacion',
        query: { fecha: g.proximoDia },
        boton: rutas ? 'Revisar las rutas' : 'Armar las rutas',
      },
      {
        titulo: 'Publique las rutas',
        ayuda: 'Al publicarlas, cada motorizado ve su ruta en el celular. Después también puede cambiarlas.',
        estado: g.rutasPublicadas && !g.rutasBorrador ? 'hecho' : g.rutasBorrador ? 'alerta' : 'pendiente',
        detalle: g.rutasBorrador
          ? `${g.rutasBorrador} ${g.rutasBorrador === 1 ? 'ruta sin publicar' : 'rutas sin publicar'}`
          : g.rutasPublicadas ? 'Publicadas: los motorizados ya las ven' : 'Primero arme las rutas',
        ruta: '/planeacion',
        query: { fecha: g.proximoDia },
        boton: 'Publicar',
      },
    ];
  });

  /** Cómo va el día de hoy: seguimiento y servicios por revisar. */
  protected readonly hoyPasos = computed<Paso[]>(() => {
    const g = this.datos()?.guia;
    if (!g) return [];
    const l: Paso[] = [];
    if (this.sesion.tiene('SEGUIMIENTO')) {
      l.push({
        titulo: 'Siga las rutas en vivo',
        ayuda: 'Vea en el mapa dónde va cada motorizado y atienda las alertas: espera larga, seriales distintos o lejos del punto.',
        estado: !g.rutasHoy ? 'info' : g.paradasSinIngresoHoy ? 'alerta' : g.paradasHechasHoy === g.paradasHoy ? 'hecho' : 'pendiente',
        detalle: !g.rutasHoy
          ? 'Hoy no hay rutas publicadas'
          : `${g.paradasHechasHoy} de ${g.paradasHoy} servicios hechos · ${g.serviciosEnCurso} en curso` +
            (g.paradasSinIngresoHoy ? ` · ${g.paradasSinIngresoHoy} sin ingreso` : ''),
        ruta: '/seguimiento',
        boton: 'Ver el seguimiento',
      });
    }
    if (this.sesion.tiene('ORDENES')) {
      l.push({
        titulo: 'Revise los servicios cerrados',
        ayuda: 'Revise lo que reportaron los motorizados (repuestos, novedades, fotos y valor) y corríjalo si hace falta.',
        estado: !g.cerradosHoy ? 'info' : g.conNovedadHoy ? 'alerta' : 'pendiente',
        detalle: !g.cerradosHoy
          ? 'Todavía no hay servicios cerrados hoy'
          : `${g.cerradosHoy} cerrados hoy` + (g.conNovedadHoy ? ` · ${g.conNovedadHoy} con novedad` : ''),
        ruta: `/ordenes/dia/${hoyIso()}`,
        boton: 'Revisar servicios',
      });
    }
    return l;
  });

  async ngOnInit(): Promise<void> {
    try {
      this.datos.set(await this.rest.get<InicioDatos>('/api/inicio'));
    } catch (e) {
      this.avisos.error(e);
    }
  }

  protected listos(pasos: Paso[]): number {
    return pasos.filter((p) => p.estado === 'hecho').length;
  }

  protected pendientes(o: Oficina): { ruta: string; texto: string; ayuda: string; icono: string; n: number }[] {
    const l: { ruta: string; texto: string; ayuda: string; icono: string; n: number }[] = [];
    if (this.sesion.tiene('PERSONAL')) {
      l.push({ ruta: '/personal/horas-extra', texto: 'Horas extra por aprobar', ayuda: 'Apruebe o rechace las que reportaron los motorizados', icono: 'more_time', n: o.horasExtraPendientes });
    }
    if (this.sesion.tiene('INVENTARIO')) {
      l.push({ ruta: '/inventario/solicitudes', texto: 'Solicitudes de repuestos', ayuda: 'Repuestos que pidieron los motorizados', icono: 'shopping_cart', n: o.solicitudesPendientes });
      l.push({ ruta: '/inventario/devoluciones', texto: 'Piezas por devolver', ayuda: 'Piezas cambiadas que deben entregar a Prosegur', icono: 'assignment_return', n: o.devolucionesPendientes });
    }
    return l;
  }

  protected barrasTecnico() {
    const n: Record<string, string> = { basico: 'Preventivo básico', profundo: 'Preventivo profundo', correctivo: 'Correctivo', fallido: 'Fallido', otros: 'Otros' };
    return Object.entries(this.datos()?.tecnico?.serviciosMes ?? {}).map(([k, v]) => ({ etiqueta: n[k] ?? k, valor: v }));
  }
}
