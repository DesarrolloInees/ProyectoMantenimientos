import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { Avisos, hoyIso } from '../core/infra';
import { inicioMes, Opcion, Rest } from '../core/rest';
import { Sesion } from '../core/sesion';

/** Descargas en Excel: servicios por rango y tipo, novedades y la base de puntos con sus máquinas (ordenReporte y exportarExcel). */
@Component({
  selector: 'app-exportar',
  imports: [FormsModule, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule, MatSelectModule],
  template: `
    <div class="pagina">
      <div class="encabezado"><h1>Exportar servicios</h1></div>
      <section class="tarjeta">
        <div class="filtros">
          <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Desde</mat-label><input matInput type="date" [(ngModel)]="desde" /></mat-form-field>
          <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Hasta</mat-label><input matInput type="date" [(ngModel)]="hasta" /></mat-form-field>
          <mat-form-field appearance="outline" subscriptSizing="dynamic" class="ancho"><mat-label>Tipo de servicio</mat-label>
            <mat-select [(ngModel)]="tipo"><mat-option [value]="null">Todos</mat-option>
              @for (t of tipos(); track t.id) { <mat-option [value]="t.id">{{ t.nombre }}</mat-option> }</mat-select></mat-form-field>
        </div>
        <div class="botones">
          <button mat-flat-button (click)="descargar('/api/ordenes/excel', { desde, hasta, idTipoMantenimiento: tipo })"><mat-icon>table_view</mat-icon> Servicios</button>
          <button mat-stroked-button (click)="descargar('/api/ordenes/novedades-excel', { desde, hasta })"><mat-icon>report</mat-icon> Novedades</button>
        </div>
      </section>
      @if (puedeReportes) {
        <section class="tarjeta">
          <h3>Base de puntos y máquinas</h3>
          <p class="muted">Clientes, puntos, máquinas, última visita, frecuencia y próximo vencimiento.</p>
          <button mat-stroked-button (click)="descargar('/api/reportes/puntos/excel', {})"><mat-icon>download</mat-icon> Descargar puntos</button>
        </section>
      }
    </div>
  `,
  styles: `.tarjeta { margin-bottom: 16px; } .botones { display: flex; gap: 8px; flex-wrap: wrap; }`,
})
export class Exportar {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected desde = inicioMes();
  protected hasta = hoyIso();
  protected tipo: number | null = null;
  protected readonly tipos = signal<Opcion[]>([]);
  protected readonly puedeReportes = inject(Sesion).tiene('REPORTES');

  constructor() {
    this.rest.lista('tipos-mantenimiento').then((t) => this.tipos.set(t));
  }

  async descargar(url: string, p: Record<string, string | number | null>): Promise<void> {
    try {
      await this.rest.descargar(url, p);
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
