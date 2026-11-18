import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatTabsModule } from '@angular/material/tabs';
import { Kpi } from '../compartido/graficos';
import { Tabla } from '../compartido/tabla';
import { Avisos, hoyIso } from '../core/infra';
import { inicioMes, Rest } from '../core/rest';

/** Repuestos usados en el periodo (reporteRepuesto) e inventario en poder de los técnicos (reporteRepuestos PDF). */
@Component({
  selector: 'app-reporte-repuestos',
  imports: [FormsModule, Tabla, Kpi, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule, MatSelectModule, MatTabsModule],
  template: `
    <div class="pagina">
      <div class="encabezado"><h1>Repuestos</h1></div>
      <mat-tab-group animationDuration="0">
        <mat-tab label="Usados en servicios">
          <div class="filtros">
            <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Desde</mat-label><input matInput type="date" [(ngModel)]="desde" /></mat-form-field>
            <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Hasta</mat-label><input matInput type="date" [(ngModel)]="hasta" /></mat-form-field>
            <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Origen</mat-label>
              <mat-select [(ngModel)]="origen"><mat-option value="">Todos</mat-option><mat-option value="INEES">INEES</mat-option><mat-option value="PROSEGUR">Prosegur</mat-option></mat-select></mat-form-field>
            <button mat-flat-button (click)="usados()"><mat-icon>search</mat-icon> Consultar</button>
            <button mat-stroked-button (click)="excel()"><mat-icon>table_view</mat-icon> Excel (con detalle INEES)</button>
          </div>
          <app-tabla [filas]="filasUsados()" [columnas]="[{ clave: 'codigo', titulo: 'Código' }, { clave: 'repuesto', titulo: 'Repuesto' },
            { clave: 'origen', titulo: 'Origen' }, { clave: 'cantidad', titulo: 'Cantidad', tipo: 'num' }, { clave: 'servicios', titulo: 'Servicios', tipo: 'num' },
            { clave: 'valor', titulo: 'Valor', tipo: 'pesos' }]" />
        </mat-tab>
        <mat-tab label="Inventario de los técnicos">
          <div class="acciones"><button mat-stroked-button (click)="pdfInventario()"><mat-icon>picture_as_pdf</mat-icon> PDF</button></div>
          @if (inventario(); as inv) {
            <div class="kpis">
              <app-kpi [valor]="inv.kpis['total_piezas']" texto="Piezas en poder de técnicos" />
              <app-kpi [valor]="inv.kpis['tecnicos_con_stock']" texto="Técnicos con stock" />
              <app-kpi [valor]="inv.kpis['referencias']" texto="Referencias distintas" />
              <app-kpi [valor]="inv.kpis['agotados']" texto="Referencias agotadas" />
            </div>
            <h3>Consolidado por repuesto</h3>
            <app-tabla [filas]="inv.consolidado" [columnas]="[{ clave: 'repuesto', titulo: 'Repuesto' }, { clave: 'codigo', titulo: 'Código' },
              { clave: 'tecnicos', titulo: 'Técnicos', tipo: 'num' }, { clave: 'total', titulo: 'Total', tipo: 'num' }]" />
            <h3>Por técnico</h3>
            <app-tabla [filas]="inv.porTecnico" [columnas]="[{ clave: 'tecnico', titulo: 'Técnico' }, { clave: 'repuesto', titulo: 'Repuesto' },
              { clave: 'codigo', titulo: 'Código' }, { clave: 'cantidad', titulo: 'Cantidad', tipo: 'num' }]" />
          }
        </mat-tab>
      </mat-tab-group>
    </div>
  `,
  styles: `.filtros, .acciones { margin-top: 12px; } h3 { margin: 16px 0 8px; }`,
})
export class ReporteRepuestos implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected desde = inicioMes();
  protected hasta = hoyIso();
  protected origen = '';
  protected readonly filasUsados = signal<Record<string, any>[]>([]);
  protected readonly inventario = signal<{ kpis: Record<string, number>; consolidado: Record<string, any>[]; porTecnico: Record<string, any>[] } | null>(null);

  async ngOnInit(): Promise<void> {
    this.usados();
    try {
      this.inventario.set(await this.rest.get('/api/reportes/inventario'));
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async usados(): Promise<void> {
    try {
      const r = await this.rest.get<{ filas: Record<string, any>[] }>('/api/reportes/repuestos-usados', { desde: this.desde, hasta: this.hasta, origen: this.origen });
      this.filasUsados.set(r.filas);
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async excel(): Promise<void> {
    try {
      await this.rest.descargar('/api/reportes/repuestos-usados/excel', { desde: this.desde, hasta: this.hasta, origen: this.origen });
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async pdfInventario(): Promise<void> {
    try {
      await this.rest.descargar('/api/reportes/inventario/pdf');
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
