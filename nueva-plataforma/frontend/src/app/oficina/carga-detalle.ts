import { DatePipe, KeyValuePipe } from '@angular/common';
import { Component, computed, inject, input, OnInit, signal } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { RouterLink } from '@angular/router';
import { Api } from '../core/api';
import { Avisos } from '../core/infra';
import { DetalleCarga } from '../core/modelos';

/** Revisión de una carga: filas con error, máquinas sin ubicación y confirmación. */
@Component({
  selector: 'app-carga-detalle',
  imports: [RouterLink, DatePipe, KeyValuePipe, MatButtonModule, MatIconModule],
  template: `
    <div class="pagina">
      @if (detalle(); as d) {
        <div class="encabezado">
          <a mat-icon-button routerLink="/cargas" aria-label="Volver"><mat-icon>arrow_back</mat-icon></a>
          <h1>{{ d.resumen.nombreArchivo }}</h1>
          <span class="chip" [class.ok]="d.resumen.estado === 'CONFIRMADA'" [class.aviso]="d.resumen.estado === 'EN_REVISION'">
            {{ d.resumen.estado.replace('_', ' ') }}</span>
        </div>

        <div class="cifras">
          <div class="tarjeta"><span class="n">{{ d.resumen.totalFilas }}</span> filas</div>
          <div class="tarjeta"><span class="n ok">{{ d.resumen.filasOk }}</span> válidas</div>
          <div class="tarjeta"><span class="n err">{{ d.resumen.filasError }}</span> con error</div>
          <div class="tarjeta"><span class="n av">{{ d.resumen.sinUbicacion }}</span> máquinas sin ubicación</div>
          <div class="tarjeta"><span class="muted">Servicios para</span><br /><strong>{{ d.resumen.fechaServicio | date: 'EEEE d MMMM' }}</strong></div>
        </div>

        @if (d.columnasReconocidas) {
          <p class="muted">Columnas reconocidas:
            @for (c of d.columnasReconocidas | keyvalue; track c.key) { <span class="chip">{{ c.key }} = «{{ c.value }}»</span> }
          </p>
        }

        @if (d.resumen.estado === 'EN_REVISION') {
          <div class="tarjeta pasos">
            @if (d.resumen.sinUbicacion > 0) {
              <div class="paso">
                <mat-icon class="av">add_location_alt</mat-icon>
                <div class="crece">
                  <strong>Faltan {{ d.resumen.sinUbicacion }} máquinas por ubicar</strong>
                  <p class="muted">Ponga el pin con la ubicación exacta de cada una; sin ubicación no se pueden meter en una ruta.</p>
                </div>
                <a mat-flat-button routerLink="/geolocalizar" [queryParams]="{ carga: d.resumen.idCarga }">Ubicar máquinas</a>
              </div>
            } @else {
              <div class="paso">
                <mat-icon class="ok">task_alt</mat-icon>
                <div class="crece">
                  <strong>Todas las máquinas están ubicadas</strong>
                  <p class="muted">Al confirmar, las {{ d.resumen.filasOk }} solicitudes entran a la bolsa con prioridad 1.</p>
                </div>
                <button mat-flat-button (click)="confirmar()" [disabled]="trabajando()">Confirmar carga</button>
              </div>
            }
            <button mat-button class="anular" (click)="anular()" [disabled]="trabajando()">Anular esta carga</button>
          </div>
        } @else if (d.resumen.estado === 'CONFIRMADA') {
          <div class="tarjeta pasos">
            <div class="paso">
              <mat-icon class="ok">task_alt</mat-icon>
              <div class="crece"><strong>Carga confirmada.</strong> Los servicios ya están en la bolsa.</div>
              <a mat-flat-button routerLink="/planeacion">Armar las rutas</a>
            </div>
          </div>
        }

        <div class="tarjeta tabla">
          <table>
            <thead>
              <tr><th>Fila</th><th>Máquina</th><th>Punto</th><th>Servicio</th><th>Ticket</th><th>Estado</th></tr>
            </thead>
            <tbody>
              @for (f of d.filas; track f.idSolicitud) {
                <tr [class.error]="f.estado === 'ERROR'">
                  <td>{{ f.filaExcel }}</td>
                  <td>{{ f.deviceId ?? '—' }}</td>
                  <td>{{ f.punto ?? '—' }} <span class="muted">{{ f.municipio }}</span></td>
                  <td>{{ f.tipoMantenimiento ?? f.tipoServicioTexto ?? '—' }}</td>
                  <td>{{ f.referenciaExterna ?? '—' }}</td>
                  <td>
                    @switch (f.estado) {
                      @case ('ERROR') { <span class="chip error">{{ f.mensajeError }}</span> }
                      @case ('ORDEN_GENERADA') { <span class="chip ok">En la bolsa</span> }
                      @case ('DESCARTADA') { <span class="chip">Descartada</span> }
                      @default {
                        @if (f.ubicada) { <span class="chip ok">Lista</span> } @else { <span class="chip aviso">Sin ubicación</span> }
                      }
                    }
                  </td>
                </tr>
              }
            </tbody>
          </table>
        </div>
      }
    </div>
  `,
  styles: `
    .cifras { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 12px; }
    .n { font-size: 28px; font-weight: 700; margin-right: 6px; }
    .ok { color: var(--inees-verde); }
    .err { color: var(--inees-rojo); }
    .av { color: var(--aviso); }
    .pasos { margin: 12px 0; display: flex; flex-direction: column; gap: 8px; }
    .paso { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
    .paso p { margin: 2px 0 0; }
    .crece { flex: 1; min-width: 240px; }
    .anular { align-self: flex-start; color: var(--inees-rojo); }
    .tabla { padding: 0; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th, td { text-align: left; padding: 8px 12px; border-bottom: 1px solid var(--fondo); }
    th { font-size: 12px; color: var(--inees-gris); }
    tr.error { background: var(--error-fondo); }
    td:nth-child(2), td:nth-child(5) { white-space: nowrap; }
    .chip { white-space: normal; }
  `,
})
export class CargaDetalle implements OnInit {
  private readonly api = inject(Api);
  private readonly avisos = inject(Avisos);
  readonly id = input.required<string>();
  protected readonly detalle = signal<DetalleCarga | null>(null);
  protected readonly trabajando = signal(false);
  protected readonly idNum = computed(() => Number(this.id()));

  async ngOnInit(): Promise<void> {
    this.detalle.set(await this.api.carga(this.idNum()));
  }

  async confirmar(): Promise<void> {
    await this.accion(async () => {
      this.detalle.set(await this.api.confirmarCarga(this.idNum()));
      this.avisos.ok('Carga confirmada: las solicitudes están en la bolsa.');
    });
  }

  async anular(): Promise<void> {
    if (!confirm('¿Anular esta carga? Las solicitudes no entrarán a la bolsa.')) return;
    await this.accion(async () => this.detalle.set(await this.api.anularCarga(this.idNum())));
  }

  private async accion(fn: () => Promise<void>): Promise<void> {
    this.trabajando.set(true);
    try {
      await fn();
    } catch (e) {
      this.avisos.error(e);
    } finally {
      this.trabajando.set(false);
    }
  }
}
