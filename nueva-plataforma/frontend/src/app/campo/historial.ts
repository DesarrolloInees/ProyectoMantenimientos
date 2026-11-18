import { DatePipe } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Selector } from '../compartido/selector';
import { Avisos } from '../core/infra';
import { Rest } from '../core/rest';

interface Historico {
  idOrden: number;
  fecha: string;
  tecnico: string | null;
  tipoServicio: string | null;
  actividades: string | null;
  repuestos: string | null;
}

/** Qué se le ha hecho a un punto: últimos 20 servicios sin fallidos ni garantías (ordenMovil de PHP). */
@Component({
  selector: 'app-historial-punto',
  imports: [FormsModule, DatePipe, Selector],
  template: `
    <div class="pagina movil">
      <h1>Historial de un punto</h1>
      <app-selector lista="clientes" etiqueta="Cliente" [(valor)]="idCliente" />
      <app-selector lista="puntos" etiqueta="Punto" [padre]="idCliente" [(valor)]="idPunto" (valorChange)="cargar()" />
      @for (h of lista(); track h.idOrden) {
        <article class="tarjeta">
          <div class="cab"><strong>{{ h.fecha | date: 'd MMM y' }}</strong><span class="chip">{{ h.tipoServicio }}</span></div>
          <div class="muted">{{ h.tecnico }}</div>
          @if (h.actividades) { <p>{{ h.actividades }}</p> }
          @if (h.repuestos) { <p class="muted">Repuestos: {{ h.repuestos }}</p> }
        </article>
      } @empty {
        @if (idPunto) { <p class="vacio">El punto no tiene servicios registrados.</p> }
      }
    </div>
  `,
  styles: `
    .movil { max-width: 640px; }
    h1 { font-size: 20px; margin-bottom: 12px; }
    article { margin-top: 10px; }
    .cab { display: flex; justify-content: space-between; gap: 8px; align-items: center; }
    p { margin: 8px 0 0; white-space: pre-wrap; }
  `,
})
export class HistorialPunto {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected idCliente: number | null = null;
  protected idPunto: number | null = null;
  protected readonly lista = signal<Historico[]>([]);

  async cargar(): Promise<void> {
    if (!this.idPunto) return this.lista.set([]);
    try {
      this.lista.set(await this.rest.get<Historico[]>('/api/campo/historial', { idPunto: this.idPunto }));
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
