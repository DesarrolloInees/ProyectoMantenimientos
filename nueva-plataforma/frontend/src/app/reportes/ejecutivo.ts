import { DatePipe, DecimalPipe } from '@angular/common';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatMenuModule } from '@angular/material/menu';
import { Barra, Barras, Kpi, Matriz } from '../compartido/graficos';
import { Avisos, hoyIso } from '../core/infra';
import { inicioMes, pesos, Rest } from '../core/rest';

type Fila = Record<string, any>;

interface SeccionPdf {
  clave: string;
  titulo: string;
  descripcion: string;
}

interface Tablero {
  desde: string;
  hasta: string;
  kpis: {
    totalServicios: number; tecnicos: number; mediaPorTecnico: number; mediaDiaria: number; cumplimientoMeta: number;
    meta: { esperados: number; diasLaborables: number; tecnicosDias: number; esperadosNormal: number; esperadosNuevaArquitectura: number };
    fallidos: number; conNovedad: number; sinNovedad: number; ingresosServicios: number; ingresosRepuestos: number; viaticos: number;
    costosOperativos: number; gastosGenerales: number;
  };
  secciones: Record<string, Fila[]>;
}

const SECCIONES: [string, string][] = [['resumen', 'Indicadores'], ['dias', 'Servicios por día y semana'], ['delegaciones', 'Delegaciones'],
  ['tipos', 'Tipos de servicio'], ['productividad', 'Productividad por técnico'], ['matrices', 'Matrices'], ['novedades', 'Novedades y fallidos'],
  ['clientes', 'Clientes con novedades'], ['puntos', 'Puntos más visitados'], ['repuestos', 'Repuestos'], ['finanzas', 'Ingresos y costos']];

/**
 * Tablero ejecutivo (reporteEjecutivo y generarReporte de PHP). Se descarga en Excel o en el PDF ejecutivo del
 * servidor, eligiendo sus secciones (equivale a «configurar reporte»).
 */
@Component({
  selector: 'app-ejecutivo',
  imports: [FormsModule, DatePipe, DecimalPipe, Kpi, Barras, Matriz, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule, MatCheckboxModule, MatMenuModule],
  template: `
    <div class="pagina">
      <div class="encabezado">
        <h1>Tablero ejecutivo</h1>
        <div class="filtros no-imprimir">
          <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Desde</mat-label><input matInput type="date" [(ngModel)]="desde" /></mat-form-field>
          <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Hasta</mat-label><input matInput type="date" [(ngModel)]="hasta" /></mat-form-field>
          <button mat-flat-button (click)="cargar()"><mat-icon>refresh</mat-icon> Generar</button>
          <button mat-stroked-button [matMenuTriggerFor]="menuSecciones"><mat-icon>tune</mat-icon> Secciones</button>
          <button mat-stroked-button [matMenuTriggerFor]="menuPdf" (menuOpened)="cargarSeccionesPdf()"><mat-icon>picture_as_pdf</mat-icon> PDF</button>
          <button mat-stroked-button (click)="excel()"><mat-icon>table_view</mat-icon> Excel</button>
        </div>
        <mat-menu #menuSecciones="matMenu">
          @for (s of secciones; track s[0]) {
            <div mat-menu-item (click)="$event.stopPropagation()"><mat-checkbox [checked]="activa(s[0])" (change)="alternar(s[0])">{{ s[1] }}</mat-checkbox></div>
          }
        </mat-menu>
        <!-- «Configurar reporte» de PHP: secciones del PDF ejecutivo (A4 horizontal, una por página) -->
        <mat-menu #menuPdf="matMenu" class="menu-pdf">
          <div class="pdf-config" (click)="$event.stopPropagation()" (keydown)="$event.stopPropagation()">
            <div class="pdf-cab">
              <strong>Secciones del PDF</strong>
              <button mat-button (click)="todasPdf(seccionesPdfElegidas().size < seccionesPdf().length)">
                {{ seccionesPdfElegidas().size < seccionesPdf().length ? 'Todas' : 'Ninguna' }}
              </button>
            </div>
            @for (s of seccionesPdf(); track s.clave) {
              <mat-checkbox [checked]="seccionesPdfElegidas().has(s.clave)" (change)="alternarPdf(s.clave)">
                <span class="pdf-titulo">{{ s.titulo }}</span><span class="pdf-desc">{{ s.descripcion }}</span>
              </mat-checkbox>
            }
            <mat-form-field appearance="outline" subscriptSizing="dynamic" class="pdf-min">
              <mat-label>Mín. visitas (puntos más visitados)</mat-label>
              <input matInput type="number" min="1" [(ngModel)]="minVisitas" />
            </mat-form-field>
            <button mat-flat-button (click)="pdf()" [disabled]="generandoPdf() || seccionesPdfElegidas().size === 0">
              <mat-icon>download</mat-icon> {{ generandoPdf() ? 'Generando…' : 'Descargar PDF' }}
            </button>
          </div>
        </mat-menu>
      </div>
      @if (t(); as t) {
        <p class="solo-impresion">Periodo: {{ t.desde | date: 'd MMM y' }} al {{ t.hasta | date: 'd MMM y' }}</p>
        @if (activa('resumen')) {
          <section class="kpis">
            <app-kpi [valor]="t.kpis.totalServicios" texto="Servicios" />
            <app-kpi [valor]="t.kpis.tecnicos" texto="Técnicos" />
            <app-kpi [valor]="(t.kpis.mediaPorTecnico | number: '1.0-1') ?? ''" texto="Promedio por técnico" />
            <app-kpi [valor]="(t.kpis.mediaDiaria | number: '1.0-2') ?? ''" texto="Promedio diario por técnico" />
            <app-kpi [valor]="t.kpis.cumplimientoMeta + ' %'" texto="Cumplimiento de la meta" [avance]="t.kpis.cumplimientoMeta" [alerta]="t.kpis.cumplimientoMeta < 85"
              [detalle]="t.kpis.meta.esperados + ' esperados en ' + t.kpis.meta.diasLaborables + ' días'" />
            <app-kpi [valor]="t.kpis.fallidos" texto="Fallidos" [alerta]="t.kpis.fallidos > 0" />
            <app-kpi [valor]="t.kpis.conNovedad" texto="Con novedad" [detalle]="t.kpis.sinNovedad + ' sin novedad'" />
          </section>
        }
        @if (activa('dias')) {
          <section class="paneles">
            <div class="tarjeta"><app-barras titulo="Servicios por día" [barras]="barras(t.secciones['serviciosPorDia'], 'fecha', 'total')" /></div>
            <div class="tarjeta"><app-barras titulo="Servicios por semana" [barras]="barrasSemana()" color="#368aba" /></div>
          </section>
        }
        @if (activa('delegaciones')) {
          <section class="tarjeta">
            <h3>Delegaciones</h3>
            <table class="tabla"><thead><tr><th>Delegación</th><th class="num">Servicios</th><th class="num">Técnicos</th><th class="num">Días efectivos</th>
              <th class="num">Promedio por día</th><th class="num">Con novedad</th><th class="num">Ingresos</th></tr></thead>
              <tbody>@for (d of t.secciones['delegaciones']; track d['delegacion']) {
                <tr><td>{{ d['delegacion'] }}</td><td class="num">{{ d['servicios'] }}</td><td class="num">{{ d['tecnicos'] }}</td><td class="num">{{ d['dias_efectivos'] }}</td>
                  <td class="num">{{ d['servicios'] / (d['dias_efectivos'] || 1) | number: '1.0-1' }}</td><td class="num">{{ d['novedades'] }}</td>
                  <td class="num">{{ pesos(d['ingresos']) }}</td></tr> }</tbody></table>
          </section>
        }
        @if (activa('tipos')) {
          <section class="paneles">
            <div class="tarjeta"><app-barras titulo="Servicios por tipo" [barras]="barras(t.secciones['tipos'], 'tipo', 'total')" color="#2d7f3e" /></div>
            <div class="tarjeta"><app-barras titulo="Estado final de las máquinas" [barras]="barras(t.secciones['estadosMaquina'], 'estado', 'total')" color="#0b5591" /></div>
          </section>
        }
        @if (activa('productividad')) {
          <section class="tarjeta">
            <h3>Productividad por técnico</h3>
            <table class="tabla"><thead><tr><th>Técnico</th><th class="num">Servicios L-V</th><th class="num">Días L-V</th><th class="num">Prom. L-V</th>
              <th class="num">Servicios sáb.</th><th class="num">Días sáb.</th><th class="num">Total</th><th class="num">Horas en servicio</th></tr></thead>
              <tbody>@for (p of t.secciones['productividad']; track p['tecnico']) {
                <tr><td>{{ p['tecnico'] }}</td><td class="num">{{ p['servicios_lv'] }}</td><td class="num">{{ p['dias_lv'] }}</td>
                  <td class="num">{{ p['servicios_lv'] / (p['dias_lv'] || 1) | number: '1.0-1' }}</td><td class="num">{{ p['servicios_sab'] }}</td>
                  <td class="num">{{ p['dias_sab'] }}</td><td class="num"><strong>{{ p['total'] }}</strong></td><td class="num">{{ p['horas'] }}</td></tr> }</tbody></table>
          </section>
        }
        @if (activa('matrices')) {
          <section class="tarjeta"><app-matriz titulo="Técnico × tipo de servicio" nombreFila="Técnico" [datos]="$any(t.secciones['tecnicoPorTipo'])" /></section>
          <section class="tarjeta"><app-matriz titulo="Delegación × tipo de servicio" nombreFila="Delegación" [datos]="$any(t.secciones['delegacionPorTipo'])" /></section>
          <section class="tarjeta"><app-matriz titulo="Puntos atendidos por delegación y tipo de máquina" nombreFila="Delegación" [datos]="$any(t.secciones['delegacionPorTipoMaquina'])" /></section>
          <section class="tarjeta"><app-matriz titulo="Calificaciones por delegación" nombreFila="Delegación" [datos]="$any(t.secciones['calificaciones'])" /></section>
        }
        @if (activa('novedades')) {
          <section class="paneles">
            <div class="tarjeta"><app-barras titulo="Novedades más frecuentes" [barras]="barras(t.secciones['topNovedades'], 'novedad', 'total')" color="#072942" /></div>
            <div class="tarjeta">
              <h3>Principales novedades por delegación</h3>
              <table class="tabla compacta"><tbody>@for (n of t.secciones['novedadesPorDelegacion']; track $index) {
                <tr><td>{{ n['delegacion'] }}</td><td>{{ n['novedad'] }}</td><td class="num">{{ n['total'] }}</td></tr> }</tbody></table>
            </div>
            <div class="tarjeta">
              <h3>Novedades de los fallidos</h3>
              <table class="tabla compacta"><tbody>@for (n of t.secciones['fallidosPorDelegacion']; track $index) {
                <tr><td>{{ n['delegacion'] }}</td><td>{{ n['novedad'] }}</td><td class="num">{{ n['total'] }}</td></tr>
              } @empty { <tr><td class="vacio">Sin fallidos</td></tr> }</tbody></table>
            </div>
            <div class="tarjeta">
              <h3>Puntos con 2 o más fallidos</h3>
              <table class="tabla compacta"><tbody>@for (p of t.secciones['puntosConFallidos']; track $index) {
                <tr><td>{{ p['delegacion'] }}</td><td>{{ p['punto'] }}</td><td class="num">{{ p['fallidos'] }}</td></tr>
              } @empty { <tr><td class="vacio">Ninguno</td></tr> }</tbody></table>
            </div>
          </section>
        }
        @if (activa('clientes')) {
          <section class="tarjeta">
            <h3>Clientes con más novedades por delegación</h3>
            <table class="tabla"><thead><tr><th>Delegación</th><th>Cliente</th><th class="num">Servicios con novedad</th><th>Novedades</th></tr></thead>
              <tbody>@for (c of t.secciones['clientesConNovedades']; track $index) {
                <tr><td>{{ c['delegacion'] }}</td><td>{{ c['cliente'] }}</td><td class="num">{{ c['total'] }}</td><td>{{ c['novedades'] }}</td></tr> }</tbody></table>
          </section>
        }
        @if (activa('puntos')) {
          <section class="tarjeta">
            <h3>Puntos visitados dos o más veces</h3>
            <table class="tabla"><thead><tr><th>Punto</th><th>Cliente</th><th>Delegación</th><th>Máquinas</th><th class="num">Visitas</th><th>Detalle</th></tr></thead>
              <tbody>@for (p of t.secciones['puntosMasVisitados']; track p['id_punto']) {
                <tr><td>{{ p['punto'] }}</td><td>{{ p['cliente'] }}</td><td>{{ p['delegacion'] }}</td><td>{{ p['tipos'] }}</td>
                  <td class="num"><strong>{{ p['visitas'] }}</strong></td><td class="muted detalle">{{ p['detalle'] }}</td></tr> }</tbody></table>
          </section>
        }
        @if (activa('repuestos')) {
          <section class="paneles">
            <div class="tarjeta"><app-barras titulo="Repuestos por origen (unidades)" [barras]="barras(t.secciones['repuestosOrigen'], 'origen', 'total')" color="#4ba844" /></div>
            <div class="tarjeta">
              <h3>Repuestos INEES por delegación</h3>
              <table class="tabla compacta"><tbody>@for (r of t.secciones['repuestosPorDelegacion']; track $index) {
                <tr><td>{{ r['delegacion'] }}</td><td>{{ r['repuesto'] }}</td><td class="num">{{ r['cantidad'] }}</td></tr> }</tbody></table>
            </div>
          </section>
        }
        @if (activa('finanzas')) {
          <section class="kpis">
            <app-kpi [valor]="pesos(t.kpis.ingresosServicios)" texto="Ingresos por servicios" />
            <app-kpi [valor]="pesos(t.kpis.viaticos)" texto="Viáticos" />
            <app-kpi [valor]="pesos(t.kpis.ingresosRepuestos)" texto="Repuestos INEES" />
            <app-kpi [valor]="pesos(t.kpis.costosOperativos)" texto="Costos de nómina y operación" />
            <app-kpi [valor]="pesos(t.kpis.gastosGenerales)" texto="Gastos generales" />
            <app-kpi [valor]="pesos(utilidad())" texto="Resultado" [alerta]="utilidad() < 0" />
          </section>
          <section class="paneles">
            <div class="tarjeta"><app-barras titulo="Ingresos por tipo de servicio" [barras]="barras(t.secciones['tipos'], 'tipo', 'ingresos')" color="#2d7f3e" /></div>
            <div class="tarjeta"><app-barras titulo="Costos por persona" [barras]="barras(t.secciones['costosOperativos'], 'beneficiario', 'subtotal')" color="#072942" /></div>
          </section>
        }
      } @else {
        <div class="tarjeta vacio">Elija el rango y pulse Generar.</div>
      }
    </div>
  `,
  styles: `
    section { margin-bottom: 16px; } .detalle { font-size: 11px; max-width: 340px; }
    .pdf-config { display: flex; flex-direction: column; gap: 2px; padding: 8px 16px 12px; min-width: 320px; }
    .pdf-cab { display: flex; justify-content: space-between; align-items: center; }
    .pdf-titulo { display: block; font-weight: 500; }
    .pdf-desc { display: block; font-size: 11px; color: var(--mat-sys-on-surface-variant); line-height: 1.2; }
    .pdf-min { margin: 8px 0; }
  `,
})
export class Ejecutivo implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected readonly pesos = pesos;
  protected readonly secciones = SECCIONES;
  protected desde = inicioMes();
  protected hasta = hoyIso();
  protected readonly t = signal<Tablero | null>(null);
  private readonly ocultas = signal(new Set<string>());
  protected readonly utilidad = computed(() => {
    const k = this.t()?.kpis;
    return k ? k.ingresosServicios + k.viaticos + k.ingresosRepuestos - k.costosOperativos - k.gastosGenerales : 0;
  });
  protected readonly barrasSemana = computed<Barra[]>(() =>
    (this.t()?.secciones['serviciosPorSemana'] ?? []).map((s) => ({ etiqueta: `${String(s['inicio']).substring(5)} a ${String(s['fin']).substring(5)}`, valor: Number(s['total']) })));

  ngOnInit(): void {
    this.cargar();
  }

  protected activa(s: string): boolean {
    return !this.ocultas().has(s);
  }

  protected alternar(s: string): void {
    this.ocultas.update((o) => {
      const c = new Set(o);
      c.has(s) ? c.delete(s) : c.add(s);
      return c;
    });
  }

  protected barras(filas: Fila[] | undefined, etiqueta: string, valor: string): Barra[] {
    return (filas ?? []).map((f) => ({ etiqueta: String(f[etiqueta] ?? '—'), valor: Number(f[valor]) || 0 }));
  }

  async cargar(): Promise<void> {
    try {
      this.t.set(await this.rest.get<Tablero>('/api/reportes/ejecutivo', { desde: this.desde, hasta: this.hasta }));
    } catch (e) {
      this.avisos.error(e);
    }
  }

  protected readonly seccionesPdf = signal<SeccionPdf[]>([]);
  protected readonly seccionesPdfElegidas = signal(new Set<string>());
  protected readonly generandoPdf = signal(false);
  protected minVisitas = 2;

  async cargarSeccionesPdf(): Promise<void> {
    if (this.seccionesPdf().length) return;
    try {
      const s = await this.rest.get<SeccionPdf[]>('/api/reportes/ejecutivo/secciones');
      this.seccionesPdf.set(s);
      this.seccionesPdfElegidas.set(new Set(s.map((x) => x.clave)));
    } catch (e) {
      this.avisos.error(e);
    }
  }

  protected alternarPdf(clave: string): void {
    this.seccionesPdfElegidas.update((o) => {
      const c = new Set(o);
      c.has(clave) ? c.delete(clave) : c.add(clave);
      return c;
    });
  }

  protected todasPdf(todas: boolean): void {
    this.seccionesPdfElegidas.set(new Set(todas ? this.seccionesPdf().map((s) => s.clave) : []));
  }

  async pdf(): Promise<void> {
    this.generandoPdf.set(true);
    try {
      const elegidas = this.seccionesPdf().map((s) => s.clave).filter((c) => this.seccionesPdfElegidas().has(c));
      await this.rest.descargar('/api/reportes/ejecutivo/pdf', {
        desde: this.desde, hasta: this.hasta, secciones: elegidas.join(','), minVisitas: Math.max(1, Number(this.minVisitas) || 2),
      });
    } catch (e) {
      this.avisos.error(e);
    } finally {
      this.generandoPdf.set(false);
    }
  }

  async excel(): Promise<void> {
    try {
      await this.rest.descargar('/api/reportes/ejecutivo/excel', { desde: this.desde, hasta: this.hasta });
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
