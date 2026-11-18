import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { Tabla } from '../compartido/tabla';
import { Avisos, hoyIso } from '../core/infra';
import { inicioMes, Rest } from '../core/rest';

/**
 * Participación de cada tipo de mantenimiento en el valor facturado, por tipo de máquina y por tamaño
 * (reporteTarifas de PHP; el tamaño ahora se marca en el catálogo de tipos de máquina).
 */
@Component({
  selector: 'app-reporte-tarifas',
  imports: [FormsModule, Tabla, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule],
  template: `
    <div class="pagina">
      <div class="encabezado"><h1>Tarifas: participación por tipo de servicio</h1></div>
      <div class="filtros">
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Desde</mat-label><input matInput type="date" [(ngModel)]="desde" /></mat-form-field>
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Hasta</mat-label><input matInput type="date" [(ngModel)]="hasta" /></mat-form-field>
        <button mat-flat-button (click)="cargar()"><mat-icon>search</mat-icon> Consultar</button>
        <button mat-stroked-button (click)="excel()"><mat-icon>table_view</mat-icon> Excel</button>
      </div>
      <app-tabla [filas]="filas()" [columnas]="[{ clave: 'categoria', titulo: 'Tamaño' }, { clave: 'tipo_maquina', titulo: 'Tipo de máquina' },
        { clave: 'tipo_mantenimiento', titulo: 'Tipo de mantenimiento' }, { clave: 'servicios', titulo: 'Servicios', tipo: 'num' },
        { clave: 'valor', titulo: 'Valor', tipo: 'pesos' }, { clave: 'pct_maquina', titulo: '% en la máquina', tipo: 'pct' },
        { clave: 'pct_categoria', titulo: '% en el tamaño', tipo: 'pct' }]" />
    </div>
  `,
})
export class ReporteTarifas {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected desde = inicioMes();
  protected hasta = hoyIso();
  protected readonly filas = signal<Record<string, any>[]>([]);

  async cargar(): Promise<void> {
    try {
      this.filas.set((await this.rest.get<{ filas: Record<string, any>[] }>('/api/reportes/tarifas', { desde: this.desde, hasta: this.hasta })).filas);
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async excel(): Promise<void> {
    try {
      await this.rest.descargar('/api/reportes/tarifas/excel', { desde: this.desde, hasta: this.hasta });
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
