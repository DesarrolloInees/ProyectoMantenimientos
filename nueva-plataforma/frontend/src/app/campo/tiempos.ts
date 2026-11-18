import { DatePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { Avisos } from '../core/infra';
import { minutosAHoras, Rest } from '../core/rest';
import { Tiempo } from '../personal/tiempos';

/** Duración de los servicios del técnico frente al tiempo estimado de cada tipo (cronometroHistorial de PHP). */
@Component({
  selector: 'app-mis-tiempos',
  imports: [DatePipe],
  template: `
    <div class="pagina movil">
      <h1>Mis tiempos de servicio</h1>
      <p class="muted">Últimos 60 días. La hora de ingreso y de cierre las registra la plataforma en cada servicio.</p>
      @for (t of lista(); track t.fuente + t.id) {
        <div class="fila" [class.excedido]="t.minutos !== null && t.estimado !== null && t.minutos > t.estimado">
          <span><strong>{{ t.punto }}</strong><br /><small class="muted">{{ t.fecha | date: 'd MMM' }} · {{ t.tipoMantenimiento }}</small></span>
          <span class="t">{{ minutosAHoras(t.minutos) }}<br /><small class="muted">meta {{ minutosAHoras(t.estimado) }}</small></span>
        </div>
      } @empty { <p class="vacio">Sin servicios.</p> }
    </div>
  `,
  styles: `
    .movil { max-width: 640px; }
    h1 { font-size: 20px; }
    .fila { display: flex; justify-content: space-between; gap: 8px; padding: 10px 4px; border-bottom: 1px solid var(--linea); }
    .fila.excedido .t { color: var(--inees-rojo); }
    .t { text-align: right; font-weight: 600; }
  `,
})
export class MisTiempos implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected readonly minutosAHoras = minutosAHoras;
  protected readonly lista = signal<Tiempo[]>([]);

  async ngOnInit(): Promise<void> {
    try {
      this.lista.set(await this.rest.get<Tiempo[]>('/api/campo/tiempos'));
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
