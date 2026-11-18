import { DatePipe } from '@angular/common';
import { Component, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { Kpi } from '../compartido/graficos';
import { Avisos } from '../core/infra';
import { pesos, Rest } from '../core/rest';

interface Cotizacion {
  cotizacion: string;
  remision: string;
  fecha: string | null;
  fechaTexto: string;
  categoria: string;
  estado: string;
  items: string;
  subtotal: number;
}

interface Analisis {
  cotizaciones: Cotizacion[];
  categorias: string[];
  estados: string[];
  hojas: string[];
  facturas: number;
  cotizacionesPendientes: number;
  qPendientes: number;
}

const CATEGORIAS: [string, string][] = [['MQ', 'Máquinas'], ['RP', 'Repuestos'], ['SE', 'Servicios'], ['TRA', 'Transportadora']];
const ESTADOS = ['FACTURADO', 'PENDIENTE', 'REFACTURADA', 'ANULADA', 'PAGADA'];

/**
 * Analizador de cotizaciones (reporteFacturacion de PHP). Se sube el Excel de control de cotizaciones; se filtra en
 * pantalla y se exporta la lista o el reporte gerencial de facturas, cotizaciones y Q pendientes. No se guarda nada.
 */
@Component({
  selector: 'app-cotizaciones',
  imports: [FormsModule, DatePipe, Kpi, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule, MatSelectModule],
  template: `
    <div class="pagina">
      <div class="encabezado">
        <h1>Analizador de cotizaciones</h1>
        <input #selector type="file" accept=".xlsx,.xls" hidden (change)="subir(selector.files?.[0]); selector.value = ''" />
        <button mat-flat-button (click)="selector.click()" [disabled]="ocupado()"><mat-icon>upload_file</mat-icon> Subir Excel</button>
        <button mat-stroked-button (click)="pdfGerencial()" [disabled]="!a() || ocupado()"
                [title]="a() ? a()!.facturas + ' facturas, ' + a()!.cotizacionesPendientes + ' cotizaciones y ' + a()!.qPendientes + ' Q pendientes' : ''">
          <mat-icon>picture_as_pdf</mat-icon> PDF gerencial (IM FAC, COT, Q)
        </button>
      </div>
      @if (archivo(); as f) {
        <p class="muted">Archivo: <strong>{{ f.name }}</strong> · hojas: {{ a()?.hojas?.join(', ') }}</p>
      } @else {
        <div class="tarjeta vacio">Suba el Excel de control de cotizaciones (hojas «CONTROL COT», «IM FAC», «IM COT» e «IM Q») para filtrarlo y exportarlo.</div>
      }

      @if (a()) {
        <div class="filtros tarjeta">
          <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Desde</mat-label><input matInput type="date" [(ngModel)]="desde" (ngModelChange)="filtrar()" /></mat-form-field>
          <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Hasta</mat-label><input matInput type="date" [(ngModel)]="hasta" (ngModelChange)="filtrar()" /></mat-form-field>
          <mat-form-field appearance="outline" subscriptSizing="dynamic">
            <mat-label>Categoría</mat-label>
            <mat-select [(ngModel)]="categoria" (ngModelChange)="filtrar()">
              <mat-option value="">Todas</mat-option>
              @for (c of categorias(); track c[0]) { <mat-option [value]="c[0]">{{ c[1] }}</mat-option> }
            </mat-select>
          </mat-form-field>
          <mat-form-field appearance="outline" subscriptSizing="dynamic">
            <mat-label>Estado</mat-label>
            <mat-select [(ngModel)]="estado" (ngModelChange)="filtrar()">
              <mat-option value="">Todos</mat-option>
              @for (e of estados(); track e) { <mat-option [value]="e">{{ e }}</mat-option> }
            </mat-select>
          </mat-form-field>
          <span class="espacio"></span>
          <button mat-stroked-button (click)="excel()" [disabled]="ocupado()"><mat-icon>table_view</mat-icon> Excel</button>
          <button mat-stroked-button (click)="pdfLista()" [disabled]="ocupado()"><mat-icon>picture_as_pdf</mat-icon> PDF lista</button>
        </div>

        <section class="kpis">
          <app-kpi [valor]="lista().length" texto="Registros listados" />
          <app-kpi [valor]="pesos(total())" texto="Total subtotal" />
        </section>

        <div class="tarjeta">
          <table class="tabla">
            <thead><tr><th>N.º cotización</th><th>Remisión</th><th>Fecha</th><th>Categoría</th><th>Estado</th><th>Ítems</th><th class="num">Subtotal</th></tr></thead>
            <tbody>
              @for (c of pagina(); track $index) {
                <tr>
                  <td><strong>{{ c.cotizacion }}</strong></td><td>{{ c.remision }}</td>
                  <td>{{ c.fecha ? (c.fecha | date: 'dd/MM/yyyy') : c.fechaTexto }}</td>
                  <td>{{ c.categoria }}</td><td>{{ c.estado }}</td>
                  <td class="items" [title]="c.items">{{ c.items }}</td>
                  <td class="num"><strong>{{ pesos(c.subtotal) }}</strong></td>
                </tr>
              } @empty {
                <tr><td colspan="7" class="vacio">Ninguna cotización cumple los filtros.</td></tr>
              }
            </tbody>
          </table>
          @if (lista().length > tamano) {
            <div class="paginas">
              <button mat-button (click)="hoja.set(hoja() - 1)" [disabled]="hoja() === 0"><mat-icon>chevron_left</mat-icon></button>
              <span>{{ hoja() * tamano + 1 }}–{{ fin() }} de {{ lista().length }}</span>
              <button mat-button (click)="hoja.set(hoja() + 1)" [disabled]="fin() >= lista().length"><mat-icon>chevron_right</mat-icon></button>
            </div>
          }
        </div>
      }
    </div>
  `,
  styles: `
    .filtros { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 16px; }
    .espacio { flex: 1; }
    .kpis { margin-bottom: 16px; }
    .items { max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .paginas { display: flex; justify-content: flex-end; align-items: center; gap: 8px; padding-top: 8px; }
    .muted { color: var(--mat-sys-on-surface-variant); }
  `,
})
export class Cotizaciones {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected readonly pesos = pesos;
  protected readonly tamano = 50;

  protected readonly archivo = signal<File | null>(null);
  protected readonly a = signal<Analisis | null>(null);
  protected readonly ocupado = signal(false);
  protected readonly hoja = signal(0);
  private readonly version = signal(0);
  protected desde = '';
  protected hasta = '';
  protected categoria = '';
  protected estado = '';

  protected readonly categorias = computed<[string, string][]>(() => {
    const extra = (this.a()?.categorias ?? []).filter((c) => !CATEGORIAS.some(([k]) => k === c)).map((c): [string, string] => [c, c]);
    return [...CATEGORIAS.map(([k, n]): [string, string] => [k, `${n} (${k})`]), ...extra];
  });
  protected readonly estados = computed(() => [...new Set([...ESTADOS, ...(this.a()?.estados ?? [])])]);

  /** Mismo criterio que el servidor (Filtro de AnalizadorCotizaciones). */
  protected readonly lista = computed(() => {
    this.version();
    const cat = this.categoria.trim().toUpperCase();
    const est = this.estado.trim().toUpperCase();
    return (this.a()?.cotizaciones ?? []).filter(
      (c) =>
        (!this.desde || (c.fecha != null && c.fecha >= this.desde)) &&
        (!this.hasta || (c.fecha != null && c.fecha <= this.hasta)) &&
        (!cat || c.categoria.trim().toUpperCase() === cat) &&
        (!est || c.estado.trim().toUpperCase() === est),
    );
  });
  protected readonly total = computed(() => this.lista().reduce((s, c) => s + (c.subtotal || 0), 0));
  protected readonly pagina = computed(() => this.lista().slice(this.hoja() * this.tamano, (this.hoja() + 1) * this.tamano));
  protected readonly fin = computed(() => Math.min((this.hoja() + 1) * this.tamano, this.lista().length));

  protected filtrar(): void {
    this.hoja.set(0);
    this.version.update((v) => v + 1);
  }

  async subir(f: File | undefined): Promise<void> {
    if (!f) return;
    await this.trabajar(async () => {
      this.a.set(await this.rest.enviar<Analisis>('/api/costos/cotizaciones', { archivo: f }));
      this.archivo.set(f);
      this.filtrar();
      this.avisos.ok(`Archivo procesado: ${this.a()!.cotizaciones.length} cotizaciones.`);
    });
  }

  async excel(): Promise<void> {
    await this.descargar('/api/costos/cotizaciones/excel', {});
  }

  async pdfLista(): Promise<void> {
    await this.descargar('/api/costos/cotizaciones/pdf', { tipo: 'lista' });
  }

  async pdfGerencial(): Promise<void> {
    await this.descargar('/api/costos/cotizaciones/pdf', { tipo: 'gerencial' });
  }

  private async descargar(url: string, extra: Record<string, string>): Promise<void> {
    const f = this.archivo();
    if (!f) return;
    const datos = new FormData();
    datos.append('archivo', f);
    await this.trabajar(() =>
      this.rest.descargar(url, { ...extra, desde: this.desde, hasta: this.hasta, categoria: this.categoria, estado: this.estado }, datos),
    );
  }

  private async trabajar(accion: () => Promise<void>): Promise<void> {
    this.ocupado.set(true);
    try {
      await accion();
    } catch (e) {
      this.avisos.error(e);
    } finally {
      this.ocupado.set(false);
    }
  }
}
