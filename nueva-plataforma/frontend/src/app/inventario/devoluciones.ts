import { DatePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { Selector } from '../compartido/selector';
import { Avisos } from '../core/infra';
import { Rest } from '../core/rest';

interface Devolucion {
  idDevolucion: number;
  idOrden: number;
  fechaVisita: string;
  remision: string | null;
  tecnico: string;
  repuesto: string;
  codigo: string | null;
  cantidad: number;
  estado: string;
  fechaDevolucion: string | null;
  cliente: string;
  punto: string;
  delegacion: string | null;
}

/** Piezas cambiadas que el técnico debe devolver (gestionDevolucion y reporteDevolucion de PHP). */
@Component({
  selector: 'app-devoluciones',
  imports: [FormsModule, DatePipe, Selector, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule, MatSelectModule],
  template: `
    <div class="pagina">
      <div class="encabezado"><h1>Devoluciones de repuestos</h1>
        <button mat-stroked-button (click)="excel()"><mat-icon>download</mat-icon> Excel</button></div>
      <div class="filtros">
        <app-selector lista="tecnicos" etiqueta="Técnico" [(valor)]="idTecnico" />
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Estado</mat-label>
          <mat-select [(ngModel)]="estado"><mat-option value="Pendiente">Pendientes</mat-option><mat-option value="Devuelto">Devueltas</mat-option>
            <mat-option value="Todas">Todas</mat-option></mat-select></mat-form-field>
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Desde</mat-label><input matInput type="date" [(ngModel)]="desde" /></mat-form-field>
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Hasta</mat-label><input matInput type="date" [(ngModel)]="hasta" /></mat-form-field>
        <button mat-flat-button (click)="cargar()"><mat-icon>search</mat-icon> Consultar</button>
      </div>
      <div class="contenedor-tabla">
        <table class="tabla">
          <thead><tr><th>Fecha</th><th>Remisión</th><th>Técnico</th><th>Repuesto</th><th class="num">Cant.</th><th>Cliente / punto</th><th>Delegación</th><th>Estado</th><th></th></tr></thead>
          <tbody>
            @for (d of lista(); track d.idDevolucion) {
              <tr><td>{{ d.fechaVisita | date: 'dd/MM/yy' }}</td><td>{{ d.remision }}</td><td>{{ d.tecnico }}</td>
                <td>{{ d.repuesto }}<br /><span class="muted">{{ d.codigo }}</span></td><td class="num">{{ d.cantidad }}</td>
                <td>{{ d.punto }}<br /><span class="muted">{{ d.cliente }}</span></td><td>{{ d.delegacion }}</td>
                <td><span class="chip" [class.ok]="d.estado === 'Devuelto'" [class.aviso]="d.estado === 'Pendiente'">{{ d.estado }}</span>
                  @if (d.fechaDevolucion) { <br /><span class="muted">{{ d.fechaDevolucion | date: 'dd/MM/yy' }}</span> }</td>
                <td>@if (d.estado === 'Pendiente') { <button mat-stroked-button (click)="devuelto(d)">Marcar devuelto</button> }</td></tr>
            } @empty { <tr><td colspan="9" class="vacio">Sin devoluciones con esos filtros.</td></tr> }
          </tbody>
        </table>
      </div>
    </div>
  `,
})
export class Devoluciones implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected idTecnico: number | null = null;
  protected estado = 'Pendiente';
  protected desde = '';
  protected hasta = '';
  protected readonly lista = signal<Devolucion[]>([]);

  ngOnInit(): void {
    this.cargar();
  }

  private filtros() {
    return { idTecnico: this.idTecnico, estado: this.estado, desde: this.desde, hasta: this.hasta };
  }

  async cargar(): Promise<void> {
    try {
      this.lista.set(await this.rest.get<Devolucion[]>('/api/inventario/devoluciones', this.filtros()));
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async devuelto(d: Devolucion): Promise<void> {
    try {
      await this.rest.post(`/api/inventario/devoluciones/${d.idDevolucion}/devuelto`);
      await this.cargar();
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async excel(): Promise<void> {
    try {
      await this.rest.descargar('/api/inventario/devoluciones/excel', this.filtros());
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
