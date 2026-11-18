import { DatePipe, KeyValuePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatTabsModule } from '@angular/material/tabs';
import { Selector } from '../compartido/selector';
import { Avisos, hoyIso } from '../core/infra';
import { minutosAHoras, Rest } from '../core/rest';

export interface Tiempo {
  fuente: string;
  id: number;
  fecha: string;
  tecnico: string;
  cliente: string;
  punto: string;
  tipoMantenimiento: string | null;
  inicio: string | null;
  fin: string | null;
  minutos: number | null;
  estimado: number | null;
  estado: string;
  justificacion: string | null;
}

interface Rendimiento {
  tecnico: string;
  registrados: number;
  finalizados: number;
  enProgreso: number;
  porTipo: Record<string, number>;
  minutosTotales: number;
  excedidos: number;
}

/**
 * Tiempo real de cada servicio frente al estimado de su tipo (cronometroAdmin y cronometroReportes de PHP).
 * Incluye los servicios del flujo en campo y el historial del cronómetro anterior.
 */
@Component({
  selector: 'app-tiempos',
  imports: [FormsModule, DatePipe, KeyValuePipe, Selector, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule, MatTabsModule],
  template: `
    <div class="pagina">
      <div class="encabezado">
        <h1>Tiempos de servicio</h1>
        <button mat-stroked-button (click)="descargar('excel')"><mat-icon>table_view</mat-icon> Excel</button>
        <button mat-stroked-button (click)="descargar('pdf')"><mat-icon>picture_as_pdf</mat-icon> PDF</button>
      </div>
      <div class="filtros">
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Desde</mat-label><input matInput type="date" [(ngModel)]="desde" /></mat-form-field>
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Hasta</mat-label><input matInput type="date" [(ngModel)]="hasta" /></mat-form-field>
        <app-selector lista="tecnicos" etiqueta="Técnico" [(valor)]="idTecnico" />
        <button mat-flat-button (click)="cargar()"><mat-icon>search</mat-icon> Consultar</button>
      </div>
      <mat-tab-group animationDuration="0">
        <mat-tab label="Servicios ({{ lista().length }})">
          <div class="contenedor-tabla">
            <table class="tabla">
              <thead><tr><th>Fecha</th><th>Técnico</th><th>Cliente / punto</th><th>Tipo</th><th>Inicio</th><th>Fin</th><th class="num">Real</th><th class="num">Estimado</th><th>Estado</th></tr></thead>
              <tbody>
                @for (t of lista(); track t.fuente + t.id) {
                  <tr [class.excedido]="excedido(t)">
                    <td>{{ t.fecha | date: 'dd/MM/yy' }}</td><td>{{ t.tecnico }}</td><td>{{ t.punto }}<br /><span class="muted">{{ t.cliente }}</span></td>
                    <td>{{ t.tipoMantenimiento }}</td><td>{{ t.inicio | date: 'HH:mm' }}</td><td>{{ t.fin | date: 'HH:mm' }}</td>
                    <td class="num">{{ minutosAHoras(t.minutos) }}</td><td class="num">{{ minutosAHoras(t.estimado) }}</td>
                    <td><span class="chip" [class.aviso]="t.estado !== 'Finalizado'">{{ t.estado }}</span>
                      @if (t.justificacion) { <div class="muted">{{ t.justificacion }}</div> }</td>
                  </tr>
                } @empty { <tr><td colspan="9" class="vacio">Sin servicios.</td></tr> }
              </tbody>
            </table>
          </div>
        </mat-tab>
        <mat-tab label="Rendimiento por técnico">
          <div class="contenedor-tabla">
            <table class="tabla">
              <thead><tr><th>Técnico</th><th class="num">Finalizados</th><th class="num">En progreso</th><th class="num">Horas</th><th class="num">Excedidos</th><th>Por tipo</th></tr></thead>
              <tbody>
                @for (r of rendimiento(); track r.tecnico) {
                  <tr><td>{{ r.tecnico }}</td><td class="num">{{ r.finalizados }}</td><td class="num">{{ r.enProgreso }}</td>
                    <td class="num">{{ minutosAHoras(r.minutosTotales) }}</td><td class="num">{{ r.excedidos }}</td>
                    <td>@for (t of r.porTipo | keyvalue; track t.key) { <span class="chip">{{ t.key }}: {{ t.value }}</span> }</td></tr>
                }
              </tbody>
            </table>
          </div>
        </mat-tab>
      </mat-tab-group>
    </div>
  `,
  styles: `tr.excedido td { background: var(--error-fondo); } .chip { margin: 0 4px 4px 0; }`,
})
export class Tiempos implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected readonly minutosAHoras = minutosAHoras;
  protected desde = hoyIso(-7);
  protected hasta = hoyIso();
  protected idTecnico: number | null = null;
  protected readonly lista = signal<Tiempo[]>([]);
  protected readonly rendimiento = signal<Rendimiento[]>([]);

  ngOnInit(): void {
    this.cargar();
  }

  protected excedido(t: Tiempo): boolean {
    return t.minutos !== null && t.estimado !== null && t.minutos > t.estimado;
  }

  private filtros() {
    return { desde: this.desde, hasta: this.hasta, idTecnico: this.idTecnico };
  }

  async cargar(): Promise<void> {
    try {
      this.lista.set(await this.rest.get<Tiempo[]>('/api/personal/tiempos', this.filtros()));
      this.rendimiento.set(await this.rest.get<Rendimiento[]>('/api/personal/tiempos/rendimiento', { desde: this.desde, hasta: this.hasta }));
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async descargar(tipo: 'excel' | 'pdf'): Promise<void> {
    try {
      await this.rest.descargar(`/api/personal/tiempos/${tipo}`, this.filtros());
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
