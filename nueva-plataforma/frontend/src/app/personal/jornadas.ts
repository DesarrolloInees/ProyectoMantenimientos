import { DatePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { Selector } from '../compartido/selector';
import { Avisos, hora, hoyIso } from '../core/infra';
import { Rest } from '../core/rest';

interface Jornada {
  id: number;
  fecha: string;
  horaEntrada: string;
  horaSalida: string | null;
  novedad: string | null;
  latitud: number | null;
  longitud: number | null;
  registrado: string;
  tecnico: string;
  codigoRuta: string | null;
  servicios: number;
}

/** Entradas (y salidas) de turno de los técnicos con su ubicación, Excel y PDF (turnoReportes de PHP). */
@Component({
  selector: 'app-jornadas',
  imports: [FormsModule, DatePipe, Selector, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule],
  template: `
    <div class="pagina">
      <div class="encabezado">
        <h1>Entradas de turno</h1>
        <button mat-stroked-button (click)="descargar('excel')"><mat-icon>table_view</mat-icon> Excel</button>
        <button mat-stroked-button (click)="descargar('pdf')"><mat-icon>picture_as_pdf</mat-icon> PDF</button>
      </div>
      <div class="filtros">
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Desde</mat-label><input matInput type="date" [(ngModel)]="desde" /></mat-form-field>
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Hasta</mat-label><input matInput type="date" [(ngModel)]="hasta" /></mat-form-field>
        <app-selector lista="tecnicos" etiqueta="Técnico" [(valor)]="idTecnico" />
        <button mat-flat-button (click)="cargar()"><mat-icon>search</mat-icon> Consultar</button>
      </div>
      <div class="contenedor-tabla">
        <table class="tabla">
          <thead><tr><th>Técnico</th><th>Fecha</th><th>Entrada</th><th>Salida</th><th class="num">Servicios</th><th>Novedad</th><th>Ubicación</th></tr></thead>
          <tbody>
            @for (j of lista(); track j.id) {
              <tr><td>{{ j.tecnico }}<br /><span class="muted">{{ j.codigoRuta }}</span></td><td>{{ j.fecha | date: 'EEE dd/MM/yy' }}</td>
                <td>{{ hora(j.horaEntrada) }}</td><td>{{ hora(j.horaSalida) }}</td><td class="num">{{ j.servicios }}</td><td>{{ j.novedad }}</td>
                <td>@if (j.latitud) { <a [href]="'https://www.google.com/maps?q=' + j.latitud + ',' + j.longitud" target="_blank" rel="noopener">Ver mapa</a> }</td></tr>
            } @empty { <tr><td colspan="7" class="vacio">Sin registros.</td></tr> }
          </tbody>
        </table>
      </div>
    </div>
  `,
})
export class Jornadas implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected readonly hora = hora;
  protected desde = hoyIso(-7);
  protected hasta = hoyIso();
  protected idTecnico: number | null = null;
  protected readonly lista = signal<Jornada[]>([]);

  ngOnInit(): void {
    this.cargar();
  }

  private filtros() {
    return { desde: this.desde, hasta: this.hasta, idTecnico: this.idTecnico };
  }

  async cargar(): Promise<void> {
    try {
      this.lista.set(await this.rest.get<Jornada[]>('/api/personal/jornadas', this.filtros()));
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async descargar(tipo: 'excel' | 'pdf'): Promise<void> {
    try {
      await this.rest.descargar(`/api/personal/jornadas/${tipo}`, this.filtros());
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
