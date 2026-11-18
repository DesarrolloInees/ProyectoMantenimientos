import { DatePipe } from '@angular/common';
import { Component, computed, inject, input, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatTooltipModule } from '@angular/material/tooltip';
import { RouterLink } from '@angular/router';
import { Selector } from '../compartido/selector';
import { Avisos, hora, hoyIso } from '../core/infra';
import { minutosAHoras, pesos, Rest } from '../core/rest';
import { Filtros, Servicio } from './modelo';

/**
 * Búsqueda de servicios con filtros (ordenDetalleBuscar) y detalle de un día (ordenDetalle).
 * Desde aquí se abre la ficha de cada servicio para corregirlo, y se exporta a Excel.
 */
@Component({
  selector: 'app-buscar',
  imports: [FormsModule, DatePipe, RouterLink, Selector, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule,
    MatCheckboxModule, MatTooltipModule],
  template: `
    <div class="pagina">
      <div class="encabezado">
        <h1>{{ fecha() ? 'Servicios del ' + (fecha() | date: 'EEEE d MMMM y') : 'Buscar servicios' }}</h1>
        <button mat-stroked-button (click)="excel()" [disabled]="!servicios().length"><mat-icon>download</mat-icon> Excel</button>
      </div>
      <div class="filtros">
        @if (!fecha()) {
          <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Desde</mat-label>
            <input matInput type="date" [(ngModel)]="f.desde" /></mat-form-field>
          <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Hasta</mat-label>
            <input matInput type="date" [(ngModel)]="f.hasta" /></mat-form-field>
        }
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Remisión</mat-label>
          <input matInput [(ngModel)]="f.remision" (keyup.enter)="buscar()" /></mat-form-field>
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Device ID</mat-label>
          <input matInput [(ngModel)]="f.deviceId" (keyup.enter)="buscar()" /></mat-form-field>
        <app-selector lista="clientes" etiqueta="Cliente" [(valor)]="f.idCliente" />
        <app-selector lista="puntos" etiqueta="Punto" [padre]="f.idCliente" [(valor)]="f.idPunto" />
        <app-selector lista="tecnicos" etiqueta="Técnico" [(valor)]="f.idTecnico" />
        <app-selector lista="delegaciones" etiqueta="Delegación" [(valor)]="f.idDelegacion" />
        <app-selector lista="tipos-mantenimiento" etiqueta="Tipo de servicio" [(valor)]="f.idTipoMantenimiento" />
        <mat-checkbox [(ngModel)]="f.conNovedad">Solo con novedad</mat-checkbox>
        <button mat-flat-button (click)="buscar()"><mat-icon>search</mat-icon> Buscar</button>
      </div>
      @if (servicios().length) {
        <p class="muted">{{ servicios().length }} servicios · {{ pesos(total()) }} · viáticos {{ pesos(viaticos()) }}</p>
      }
      <div class="contenedor-tabla">
        <table class="tabla">
          <thead>
            <tr><th>Fecha</th><th>Remisión</th><th>Técnico</th><th>Cliente / punto</th><th>Máquina</th><th>Tipo</th>
              <th>Horario</th><th class="num">Valor</th><th>Estado / calificación</th><th>Novedades y repuestos</th><th></th></tr>
          </thead>
          <tbody>
            @for (s of servicios(); track s.idOrden) {
              <tr>
                <td>{{ s.fechaVisita | date: 'dd/MM/yy' }}</td>
                <td><strong>{{ s.numeroRemision ?? '—' }}</strong></td>
                <td>{{ s.tecnico }}</td>
                <td>{{ s.punto }}<br /><span class="muted">{{ s.cliente }} · {{ s.delegacion }} · {{ s.modalidad }}</span></td>
                <td>{{ s.deviceId }}<br /><span class="muted">{{ s.tipoMaquina }}</span></td>
                <td>{{ s.tipoMantenimiento }}</td>
                <td>{{ hora(s.horaEntrada) }}–{{ hora(s.horaSalida) }}<br /><span class="muted">{{ minutosAHoras(s.minutosServicio) }} h</span></td>
                <td class="num">{{ pesos(s.valorServicio) }}
                  @if (s.valorViaticos) { <br /><span class="muted" matTooltip="Viáticos">+{{ pesos(s.valorViaticos) }}</span> }</td>
                <td>{{ s.estadoMaquina }}<br /><span class="muted">{{ s.calificacion }}</span></td>
                <td class="nov">
                  @if (s.novedades) { <span class="chip aviso">{{ s.novedades }}</span> }
                  @if (s.repuestosTexto) { <div class="muted">{{ s.repuestosTexto }}</div> }
                </td>
                <td class="botones-fila">
                  <a mat-icon-button [routerLink]="['/ordenes', s.idOrden]" matTooltip="Ver y corregir"><mat-icon>edit</mat-icon></a>
                  <button mat-icon-button (click)="pdf(s)" matTooltip="PDF"><mat-icon>picture_as_pdf</mat-icon></button>
                </td>
              </tr>
            } @empty {
              <tr><td colspan="11" class="vacio">{{ buscado() ? 'No hay servicios con esos filtros.' : 'Use los filtros para buscar.' }}</td></tr>
            }
          </tbody>
        </table>
      </div>
    </div>
  `,
  styles: `.nov { max-width: 260px; font-size: 12px; } .chip { white-space: normal; }`,
})
export class Buscar implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  /** Desde la ruta /ordenes/dia/:fecha */
  readonly fecha = input<string | undefined>();
  protected readonly hora = hora;
  protected readonly pesos = pesos;
  protected readonly minutosAHoras = minutosAHoras;
  protected f: Filtros = { desde: hoyIso(-7), hasta: hoyIso() };
  protected readonly servicios = signal<Servicio[]>([]);
  protected readonly buscado = signal(false);
  protected readonly total = computed(() => this.servicios().reduce((s, x) => s + (x.valorServicio ?? 0), 0));
  protected readonly viaticos = computed(() => this.servicios().reduce((s, x) => s + (x.valorViaticos ?? 0), 0));

  ngOnInit(): void {
    if (this.fecha()) {
      this.f = { desde: this.fecha(), hasta: this.fecha() };
      this.buscar();
    }
  }

  async buscar(): Promise<void> {
    try {
      this.servicios.set(await this.rest.get<Servicio[]>('/api/ordenes', { ...this.f, limite: 3000 } as never));
      this.buscado.set(true);
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async excel(): Promise<void> {
    try {
      await this.rest.descargar('/api/ordenes/excel', this.f as never);
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async pdf(s: Servicio): Promise<void> {
    try {
      await this.rest.descargar(`/api/ordenes/${s.idOrden}/pdf`);
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
