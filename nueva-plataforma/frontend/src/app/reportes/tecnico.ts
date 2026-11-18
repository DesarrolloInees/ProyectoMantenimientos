import { DatePipe } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { Selector } from '../compartido/selector';
import { Avisos, hora, hoyIso } from '../core/infra';
import { minutosAHoras, Rest } from '../core/rest';

interface FilaTecnico {
  tecnico: string;
  porTipo: Record<string, number>;
  servicios: number;
  fallidos: number;
  meta: number;
  nuevaArquitectura: boolean;
  semaforo: 'CUMPLE' | 'POR_DEBAJO' | 'BAJO' | 'SIN_META';
  porcentaje: number;
  entrada: string | null;
  salida: string | null;
  minutosJornada: number;
  minutosDesplazamiento: number;
  tramos: number[];
}

interface Dia {
  fecha: string;
  noLaboral: boolean;
  tipos: string[];
  tecnicos: FilaTecnico[];
  totales: Record<string, number>;
  total: number;
  fallidos: { delegacion: string; cliente: string; puntos: string; total: number; novedades: string }[];
  servicios: { id: number; remision: string | null; horaEntrada: string | null; horaSalida: string | null; tecnico: string; cliente: string; punto: string;
    delegacion: string | null; deviceId: string; tipo: string | null; novedades: string | null }[];
}

/** Reporte técnico diario: cumplimiento de la meta, jornada, desplazamientos y fallidos (reporteTecnico de PHP). */
@Component({
  selector: 'app-reporte-tecnico',
  imports: [FormsModule, DatePipe, Selector, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule],
  template: `
    <div class="pagina">
      <div class="encabezado">
        <h1>Reporte técnico</h1>
        <button mat-stroked-button (click)="pdf()" [disabled]="!dias().length"><mat-icon>picture_as_pdf</mat-icon> PDF</button>
      </div>
      <div class="filtros">
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Desde</mat-label><input matInput type="date" [(ngModel)]="desde" /></mat-form-field>
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Hasta</mat-label><input matInput type="date" [(ngModel)]="hasta" /></mat-form-field>
        <app-selector lista="tecnicos" etiqueta="Técnico (vacío = todos)" [(valor)]="idTecnico" />
        <button mat-flat-button (click)="cargar()"><mat-icon>search</mat-icon> Generar</button>
      </div>
      @for (d of dias(); track d.fecha) {
        <section class="tarjeta dia">
          <h2>{{ d.fecha | date: "EEEE d 'de' MMMM" }} · {{ d.total }} servicios @if (d.noLaboral) { <span class="chip">No laboral</span> }</h2>
          <div class="desliza">
            <table class="tabla compacta">
              <thead><tr><th>Técnico</th>@for (t of d.tipos; track t) { <th class="num">{{ t }}</th> }<th class="num">Total</th><th>Meta</th><th>Jornada</th><th>Desplazamiento</th></tr></thead>
              <tbody>
                @for (f of d.tecnicos; track f.tecnico) {
                  <tr><td>{{ f.tecnico }} @if (f.nuevaArquitectura) { <span class="chip">NA</span> }</td>
                    @for (t of d.tipos; track t) { <td class="num">{{ f.porTipo[t] || '' }}</td> }
                    <td class="num"><strong>{{ f.servicios }}</strong></td>
                    <td><span class="semaforo" [class]="f.semaforo">{{ f.meta ? f.porcentaje + ' % de ' + f.meta : 'Sin meta' }}</span>
                      @if (f.fallidos) { <span class="muted"> · {{ f.fallidos }} fallidos</span> }</td>
                    <td>{{ hora(f.entrada) }}–{{ hora(f.salida) }} <span class="muted">({{ minutosAHoras(f.minutosJornada) }} h)</span></td>
                    <td [title]="tramos(f)">{{ minutosAHoras(f.minutosDesplazamiento) }} h</td></tr>
                }
                <tr class="total"><td>Total</td>@for (t of d.tipos; track t) { <td class="num">{{ d.totales[t] }}</td> }<td class="num">{{ d.total }}</td><td colspan="3"></td></tr>
              </tbody>
            </table>
          </div>
          @if (d.fallidos.length) {
            <h3>Fallidos</h3>
            <table class="tabla compacta"><tbody>@for (x of d.fallidos; track $index) {
              <tr><td>{{ x.delegacion }}</td><td>{{ x.cliente }}</td><td>{{ x.puntos }}</td><td class="num">{{ x.total }}</td><td class="muted">{{ x.novedades }}</td></tr> }</tbody></table>
          }
          <details><summary>Detalle de los {{ d.servicios.length }} servicios</summary>
            <table class="tabla compacta"><tbody>@for (s of d.servicios; track s.id) {
              <tr><td>{{ s.tecnico }}</td><td>{{ s.remision }}</td><td>{{ hora(s.horaEntrada) }}–{{ hora(s.horaSalida) }}</td><td>{{ s.punto }}<br /><span class="muted">{{ s.cliente }}</span></td>
                <td>{{ s.delegacion }}</td><td>{{ s.deviceId }}</td><td>{{ s.tipo }}</td><td class="muted">{{ s.novedades }}</td></tr> }</tbody></table>
          </details>
        </section>
      } @empty { <div class="tarjeta vacio">Elija el rango y pulse Generar.</div> }
    </div>
  `,
  styles: `
    .dia { margin-bottom: 16px; }
    h2 { font-size: 16px; margin-bottom: 8px; text-transform: capitalize; }
    .desliza { overflow-x: auto; }
    .semaforo { padding: 2px 8px; border-radius: 10px; font-weight: 600; font-size: 12px; }
    .CUMPLE { background: var(--ok-fondo); color: var(--inees-verde); }
    .POR_DEBAJO { background: var(--aviso-fondo); color: var(--aviso); }
    .BAJO { background: var(--error-fondo); color: var(--inees-rojo); }
    .SIN_META { background: var(--inees-fondo); color: var(--inees-gris); }
    tr.total td { background: var(--fondo); font-weight: 600; }
    details { margin-top: 8px; }
  `,
})
export class ReporteTecnico {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected readonly hora = hora;
  protected readonly minutosAHoras = minutosAHoras;
  protected desde = hoyIso(-1);
  protected hasta = hoyIso(-1);
  protected idTecnico: number | null = null;
  protected readonly dias = signal<Dia[]>([]);

  protected tramos(f: FilaTecnico): string {
    return 'Tramos: ' + f.tramos.map((m) => minutosAHoras(m)).join(', ');
  }

  async cargar(): Promise<void> {
    try {
      this.dias.set(await this.rest.get<Dia[]>('/api/reportes/tecnico', { desde: this.desde, hasta: this.hasta, idTecnico: this.idTecnico }));
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async pdf(): Promise<void> {
    try {
      await this.rest.descargar('/api/reportes/tecnico/pdf', { desde: this.desde, hasta: this.hasta, idTecnico: this.idTecnico });
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
