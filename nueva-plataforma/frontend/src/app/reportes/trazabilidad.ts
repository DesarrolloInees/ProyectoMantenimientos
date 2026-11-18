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

/** Trazabilidad de repuestos cambiados por punto y los más cambiados por tipo de máquina (reporteTrazabilidad de PHP). */
@Component({
  selector: 'app-trazabilidad',
  imports: [FormsModule, Tabla, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule, MatTabsModule],
  template: `
    <div class="pagina">
      <div class="encabezado"><h1>Trazabilidad de repuestos</h1></div>
      <div class="filtros">
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Desde</mat-label><input matInput type="date" [(ngModel)]="desde" /></mat-form-field>
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Hasta</mat-label><input matInput type="date" [(ngModel)]="hasta" /></mat-form-field>
        <button mat-flat-button (click)="cargar()"><mat-icon>search</mat-icon> Consultar</button>
        <button mat-stroked-button (click)="excel()"><mat-icon>table_view</mat-icon> Excel</button>
      </div>
      <mat-tab-group animationDuration="0">
        <mat-tab label="Por punto">
          <app-tabla [filas]="datos()['porPunto'] ?? []" [columnas]="[{ clave: 'cliente', titulo: 'Cliente' }, { clave: 'punto', titulo: 'Punto' },
            { clave: 'tipo_maquina', titulo: 'Tipo de máquina' }, { clave: 'device_id', titulo: 'Device ID' }, { clave: 'repuesto', titulo: 'Repuesto' },
            { clave: 'origen', titulo: 'Origen' }, { clave: 'cantidad', titulo: 'Cantidad', tipo: 'num' }, { clave: 'fecha_cambio', titulo: 'Fecha', tipo: 'fecha' }]" />
        </mat-tab>
        <mat-tab label="Más cambiados por tipo de máquina">
          <app-tabla [filas]="datos()['porTipoMaquina'] ?? []" [columnas]="[{ clave: 'tipo_maquina', titulo: 'Tipo de máquina' }, { clave: 'repuesto', titulo: 'Repuesto' },
            { clave: 'origen', titulo: 'Origen' }, { clave: 'total', titulo: 'Total cambiados', tipo: 'num' }]" />
        </mat-tab>
      </mat-tab-group>
    </div>
  `,
})
export class Trazabilidad {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected desde = inicioMes();
  protected hasta = hoyIso();
  protected readonly datos = signal<Record<string, Record<string, any>[]>>({});

  async cargar(): Promise<void> {
    try {
      this.datos.set(await this.rest.get('/api/reportes/trazabilidad', { desde: this.desde, hasta: this.hasta }));
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async excel(): Promise<void> {
    try {
      await this.rest.descargar('/api/reportes/trazabilidad/excel', { desde: this.desde, hasta: this.hasta });
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
