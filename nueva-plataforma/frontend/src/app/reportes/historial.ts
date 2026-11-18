import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatTabsModule } from '@angular/material/tabs';
import { Tabla } from '../compartido/tabla';
import { Avisos, hoyIso } from '../core/infra';
import { inicioMes, Rest } from '../core/rest';

/** Historial de mantenimientos del periodo y cumplimiento de la frecuencia de cada máquina (reporteHistorial de PHP). */
@Component({
  selector: 'app-reporte-historial',
  imports: [FormsModule, Tabla, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule, MatTabsModule],
  template: `
    <div class="pagina">
      <div class="encabezado"><h1>Historial y cumplimiento</h1></div>
      <div class="filtros">
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Desde</mat-label><input matInput type="date" [(ngModel)]="desde" /></mat-form-field>
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Hasta</mat-label><input matInput type="date" [(ngModel)]="hasta" /></mat-form-field>
        <button mat-flat-button (click)="cargar()"><mat-icon>search</mat-icon> Consultar</button>
        <button mat-stroked-button (click)="excel()"><mat-icon>table_view</mat-icon> Excel</button>
      </div>
      <mat-tab-group animationDuration="0">
        <mat-tab label="Cumplimiento por máquina">
          <app-tabla [filas]="datos()['cumplimiento'] ?? []" [columnas]="[{ clave: 'cliente', titulo: 'Cliente' }, { clave: 'punto', titulo: 'Punto' },
            { clave: 'delegacion', titulo: 'Delegación' }, { clave: 'device_id', titulo: 'Device ID' }, { clave: 'tipo_maquina', titulo: 'Tipo' },
            { clave: 'frecuencia', titulo: 'Frecuencia (días)', tipo: 'num' }, { clave: 'ultima_visita', titulo: 'Última visita', tipo: 'fecha' },
            { clave: 'fecha_vencimiento', titulo: 'Vence', tipo: 'fecha' }, { clave: 'mantenimientos', titulo: 'Mantenimientos', tipo: 'num' },
            { clave: 'preventivos', titulo: 'Preventivos', tipo: 'num' }, { clave: 'preventivos_faltantes', titulo: 'Preventivos faltantes', tipo: 'num' }]" />
        </mat-tab>
        <mat-tab label="Mantenimientos realizados">
          <app-tabla [filas]="datos()['mantenimientos'] ?? []" [columnas]="[{ clave: 'fecha', titulo: 'Fecha', tipo: 'fecha' }, { clave: 'cliente', titulo: 'Cliente' },
            { clave: 'punto', titulo: 'Punto' }, { clave: 'delegacion', titulo: 'Delegación' }, { clave: 'device_id', titulo: 'Device ID' },
            { clave: 'tipo_maquina', titulo: 'Tipo de máquina' }, { clave: 'tipo_mantenimiento', titulo: 'Tipo de mantenimiento' }]" />
        </mat-tab>
      </mat-tab-group>
    </div>
  `,
})
export class ReporteHistorial {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected desde = inicioMes();
  protected hasta = hoyIso();
  protected readonly datos = signal<Record<string, Record<string, any>[]>>({});

  async cargar(): Promise<void> {
    try {
      this.datos.set(await this.rest.get('/api/reportes/historial', { desde: this.desde, hasta: this.hasta }));
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async excel(): Promise<void> {
    try {
      await this.rest.descargar('/api/reportes/historial/excel', { desde: this.desde, hasta: this.hasta });
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
