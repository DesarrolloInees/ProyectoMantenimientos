import { DatePipe } from '@angular/common';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatTooltipModule } from '@angular/material/tooltip';
import { Fotos } from '../compartido/fotos';
import { Selector } from '../compartido/selector';
import { Avisos, hora, hoyIso } from '../core/infra';
import { inicioMes, Rest } from '../core/rest';

export interface HoraExtra {
  id: number;
  fecha: string;
  horaInicio: string;
  horaFin: string;
  totalHoras: number;
  justificacion: string;
  observacionSupervisor: string | null;
  idEstado: number;
  estado: string;
  idTecnico: number;
  tecnico: string;
  idCliente: number | null;
  cliente: string | null;
  idPunto: number | null;
  punto: string | null;
  registrado: string;
  fotos: { id: number; ruta: string }[];
}

/** Aprobación de horas extra de los técnicos, con Excel y PDF (horaExtraAdmin de PHP). */
@Component({
  selector: 'app-horas-extra-admin',
  imports: [FormsModule, DatePipe, Selector, Fotos, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule, MatSelectModule, MatTooltipModule],
  template: `
    <div class="pagina">
      <div class="encabezado">
        <h1>Horas extra</h1>
        <button mat-stroked-button (click)="descargar('excel')"><mat-icon>table_view</mat-icon> Excel</button>
        <button mat-stroked-button (click)="descargar('pdf')"><mat-icon>picture_as_pdf</mat-icon> PDF</button>
      </div>
      <div class="filtros">
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Desde</mat-label><input matInput type="date" [(ngModel)]="desde" /></mat-form-field>
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Hasta</mat-label><input matInput type="date" [(ngModel)]="hasta" /></mat-form-field>
        <app-selector lista="tecnicos" etiqueta="Técnico" [(valor)]="idTecnico" />
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Estado</mat-label>
          <mat-select [(ngModel)]="idEstado"><mat-option [value]="null">Todos</mat-option><mat-option [value]="1">Pendientes</mat-option>
            <mat-option [value]="2">Aprobadas</mat-option><mat-option [value]="3">Rechazadas</mat-option></mat-select></mat-form-field>
        <button mat-flat-button (click)="cargar()"><mat-icon>search</mat-icon> Consultar</button>
      </div>
      <p class="muted">{{ lista().length }} registros · {{ totalHoras() }} horas</p>
      <div class="contenedor-tabla">
        <table class="tabla">
          <thead><tr><th>Fecha</th><th>Técnico</th><th>Cliente / punto</th><th>Horario</th><th class="num">Horas</th><th>Justificación</th><th>Fotos</th><th>Estado</th><th></th></tr></thead>
          <tbody>
            @for (h of lista(); track h.id) {
              <tr>
                <td>{{ h.fecha | date: 'dd/MM/yy' }}</td><td>{{ h.tecnico }}</td>
                <td>{{ h.cliente }}<br /><span class="muted">{{ h.punto }}</span></td>
                <td>{{ hora(h.horaInicio) }}–{{ hora(h.horaFin) }}</td><td class="num">{{ h.totalHoras }}</td>
                <td class="just">{{ h.justificacion }}</td>
                <td><app-fotos [rutas]="rutas(h)" /></td>
                <td><span class="chip" [class.ok]="h.idEstado === 2" [class.error]="h.idEstado === 3" [class.aviso]="h.idEstado === 1">{{ h.estado }}</span>
                  @if (h.observacionSupervisor) { <div class="muted">{{ h.observacionSupervisor }}</div> }</td>
                <td class="botones-fila">
                  @if (h.idEstado !== 2) { <button mat-icon-button (click)="decidir(h, 2)" matTooltip="Aprobar"><mat-icon>check_circle</mat-icon></button> }
                  @if (h.idEstado !== 3) { <button mat-icon-button (click)="decidir(h, 3)" matTooltip="Rechazar"><mat-icon>cancel</mat-icon></button> }
                  @if (h.idEstado !== 2) { <button mat-icon-button (click)="eliminar(h)" matTooltip="Eliminar"><mat-icon>delete</mat-icon></button> }
                </td>
              </tr>
            } @empty { <tr><td colspan="9" class="vacio">Sin registros.</td></tr> }
          </tbody>
        </table>
      </div>
    </div>
  `,
  styles: `.just { max-width: 280px; white-space: pre-wrap; }`,
})
export class HorasExtraAdmin implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected readonly hora = hora;
  protected desde = inicioMes();
  protected hasta = hoyIso();
  protected idTecnico: number | null = null;
  protected idEstado: number | null = 1;
  protected readonly lista = signal<HoraExtra[]>([]);
  protected readonly totalHoras = computed(() => Math.round(this.lista().reduce((s, h) => s + Number(h.totalHoras), 0) * 100) / 100);

  ngOnInit(): void {
    this.cargar();
  }

  protected rutas(h: HoraExtra): string[] {
    return h.fotos.map((f) => f.ruta);
  }

  private filtros() {
    return { desde: this.desde, hasta: this.hasta, idTecnico: this.idTecnico, idEstado: this.idEstado };
  }

  async cargar(): Promise<void> {
    try {
      this.lista.set(await this.rest.get<HoraExtra[]>('/api/personal/horas-extra', this.filtros()));
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async decidir(h: HoraExtra, idEstado: number): Promise<void> {
    const observacion = prompt(idEstado === 3 ? 'Motivo del rechazo:' : 'Observación (opcional):', h.observacionSupervisor ?? '');
    if (observacion === null) return;
    try {
      await this.rest.put(`/api/personal/horas-extra/${h.id}/estado`, { idEstado, observacion });
      await this.cargar();
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async eliminar(h: HoraExtra): Promise<void> {
    if (!confirm(`¿Eliminar el registro de ${h.tecnico} del ${h.fecha} y sus fotos?`)) return;
    try {
      await this.rest.delete(`/api/personal/horas-extra/${h.id}`);
      await this.cargar();
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async descargar(tipo: 'excel' | 'pdf'): Promise<void> {
    try {
      await this.rest.descargar(`/api/personal/horas-extra/${tipo}`, this.filtros());
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
