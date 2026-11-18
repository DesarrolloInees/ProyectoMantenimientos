import { DatePipe } from '@angular/common';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { Selector } from '../compartido/selector';
import { Rest } from '../core/rest';
import { Api } from '../core/api';
import { Avisos, hora, ubicacionActual } from '../core/infra';
import { MiDia } from '../core/modelos';

/** Inicio del motorizado: su jornada y su ruta del día en orden. */
@Component({
  selector: 'app-mi-dia',
  imports: [RouterLink, DatePipe, FormsModule, Selector, MatButtonModule, MatIconModule, MatProgressBarModule],
  template: `
    <div class="pagina movil">
      @if (cargando()) { <mat-progress-bar mode="indeterminate" /> }
      @if (dia(); as d) {
        <h1>{{ d.fecha | date: "EEEE d 'de' MMMM" }}</h1>
        <ol class="guia" aria-label="Su día en tres pasos">
          @for (g of guia; track g.n) {
            <li [class.hecho]="pasoGuia(d) > g.n" [class.actual]="pasoGuia(d) === g.n" [attr.aria-current]="pasoGuia(d) === g.n ? 'step' : null">
              <span class="n">@if (pasoGuia(d) > g.n) { <mat-icon>check</mat-icon> } @else { {{ g.n }} }</span>
              <span class="t">{{ g.texto }}</span>
            </li>
          }
        </ol>
        @if (d.paradas.length) {
          <div class="avance-dia">
            <div class="franjas" role="img" [attr.aria-label]="cerradas(d) + ' de ' + d.paradas.length + ' servicios cerrados'">
              @for (p of d.paradas; track p.idParada) {
                <i [class.hecho]="p.estadoParada === 'HECHA'" [class.alerta]="p.estadoParada === 'NO_INGRESO' || p.estadoParada === 'NO_ALCANZADA'"
                   [class.actual]="p.esSiguiente"></i>
              }
            </div>
            <span><strong>{{ cerradas(d) }}</strong> de {{ d.paradas.length }} servicios</span>
          </div>
        }

        <section class="tarjeta jornada">
          @if (!d.jornada) {
            <div>
              <strong>Su jornada no ha empezado</strong>
              <p class="muted">Al iniciarla se registra la hora y su ubicación.</p>
              <textarea class="novedad" rows="2" [(ngModel)]="novedad" placeholder="Novedad de la entrada (opcional): retraso, permiso, moto en taller…"></textarea>
            </div>
            <button mat-flat-button class="grande" (click)="iniciar()" [disabled]="cargando()">
              <mat-icon>play_arrow</mat-icon> Iniciar jornada
            </button>
          } @else {
            <div class="fila">
              <mat-icon class="ok">schedule</mat-icon>
              <div class="crece">
                <strong>Jornada iniciada a las {{ hora(d.jornada.horaEntrada) }}</strong>
                @if (d.jornada.horaSalida) { <br /><span class="muted">Terminó a las {{ hora(d.jornada.horaSalida) }}</span> }
              </div>
              @if (!d.jornada.horaSalida) {
                <button mat-stroked-button (click)="finalizar()" [disabled]="cargando()">Terminar</button>
              }
            </div>
          }
        </section>

        @if (siguiente(); as s) {
          <section class="tarjeta siguiente">
            <span class="etiqueta">{{ s.estadoOrden >= 4 ? 'En curso' : 'Siguiente punto' }} · {{ s.secuencia }} de {{ d.paradas.length }}</span>
            <h2>{{ s.punto }}</h2>
            <p>{{ s.cliente }}<br /><span class="muted">{{ s.direccion }} · {{ s.municipio }}</span></p>
            <p class="muted">{{ s.tipoMantenimiento ?? 'Servicio' }} · Máquina {{ s.deviceId }} · Hora estimada {{ hora(s.horaEstimada) }}</p>
            <div class="botones">
              @if (s.mapsUrl) {
                <a mat-stroked-button class="grande" [href]="s.mapsUrl" target="_blank" rel="noopener">
                  <mat-icon>navigation</mat-icon> Google Maps
                </a>
              }
              <a mat-flat-button class="grande" [routerLink]="['/campo/servicio', s.idOrden]">
                <mat-icon>arrow_forward</mat-icon> {{ s.estadoOrden >= 4 ? 'Continuar' : 'Empezar' }}
              </a>
            </div>
          </section>
        } @else if (d.idRuta) {
          <section class="tarjeta fin"><mat-icon class="ok">task_alt</mat-icon> Terminó todos los servicios de su ruta.</section>
        } @else {
          <section class="tarjeta vacio">Todavía no tiene una ruta publicada para hoy.</section>
        }

        @if (d.paradas.length) {
          <h3>Su ruta</h3>
          <ol class="ruta">
            @for (p of d.paradas; track p.idParada) {
              <li [class.actual]="p.esSiguiente" [class.cerrada]="p.estadoParada !== 'PENDIENTE'">
                <span class="num">{{ p.secuencia }}</span>
                <div class="crece">
                  <strong>{{ p.punto }}</strong><br />
                  <span class="muted">{{ p.cliente }} · {{ hora(p.horaEstimada) }}</span>
                </div>
                @switch (p.estadoParada) {
                  @case ('HECHA') { <span class="chip ok">Hecho</span> }
                  @case ('NO_INGRESO') { <span class="chip error">Sin ingreso</span> }
                  @case ('PENDIENTE') { @if (p.etapaActual) { <span class="chip aviso">En curso</span> } }
                  @default { <span class="chip">{{ p.estadoParada }}</span> }
                }
              </li>
            }
          </ol>
        }
        @if (d.idRuta) {
          <section class="tarjeta extra">
            @if (!verExtra()) {
              <button mat-stroked-button (click)="verExtra.set(true)"><mat-icon>add</mat-icon> Agregar un servicio que no está en la ruta</button>
            } @else {
              <strong>Servicio extra</strong>
              <app-selector lista="clientes" etiqueta="Cliente" [(valor)]="extra.idCliente" />
              <app-selector lista="puntos" etiqueta="Punto" [padre]="extra.idCliente" [(valor)]="extra.idPunto" />
              <app-selector lista="maquinas" etiqueta="Máquina" [padre]="extra.idPunto" [(valor)]="extra.idMaquina" />
              <app-selector lista="tipos-mantenimiento" etiqueta="Tipo de servicio" [(valor)]="extra.idTipoMantenimiento" />
              <textarea class="novedad" rows="2" [(ngModel)]="extra.motivo" placeholder="¿Por qué se agrega? (lo ve el Supervisor)"></textarea>
              <div class="botones">
                <button mat-button (click)="verExtra.set(false)">Cancelar</button>
                <button mat-flat-button (click)="agregarExtra()" [disabled]="!extra.idMaquina || !extra.idTipoMantenimiento">Agregar al final</button>
              </div>
            }
          </section>
        }
      }
    </div>
  `,
  styles: `
    .movil { max-width: 640px; padding: 12px 12px 40px; display: flex; flex-direction: column; gap: 12px; }
    h1 { font-size: 22px; line-height: 1.2; letter-spacing: -0.02em; }
    h1::first-letter { text-transform: uppercase; }
    h3 { font-size: 16px; margin-top: 8px; }
    .guia { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
    .guia li { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 10px; background: #fff;
      border: 1px solid var(--linea); color: var(--mut); font-size: 13px; line-height: 1.25; }
    .guia .n { display: grid; place-items: center; flex: none; width: 24px; height: 24px; border-radius: 6px; background: var(--fondo);
      color: var(--tinta-2); font-weight: 600; font-size: 13px; }
    .guia .n mat-icon { font-size: 18px; width: 18px; height: 18px; }
    .guia li.actual { border-color: var(--marca-azul); color: var(--marca-noche); font-weight: 600; box-shadow: 0 0 0 1px var(--marca-azul); }
    .guia li.actual .n { background: var(--marca-azul); color: #fff; }
    .guia li.hecho .n { background: var(--marca-verde); color: #fff; }
    .avance-dia { display: flex; flex-direction: column; gap: 6px; margin: -2px 0 2px; font-size: 13px; color: var(--tinta-2); }
    .avance-dia .franjas > i { height: 10px; }
    .avance-dia strong { color: var(--marca-noche); }
    .jornada { display: flex; flex-direction: column; gap: 12px; }
    .jornada p { margin: 4px 0 0; }
    .fila { display: flex; gap: 10px; align-items: center; }
    .crece { flex: 1; min-width: 0; }
    .ok { color: var(--inees-verde); }
    .grande { --mat-button-filled-container-height: 52px; --mat-button-outlined-container-height: 52px; height: 52px; font-size: 16px; }
    .siguiente { border: 2px solid var(--marca-azul); box-shadow: var(--sombra-2); }
    .siguiente h2 { font-size: 22px; line-height: 1.2; letter-spacing: -0.02em; margin: 6px 0 4px; }
    .siguiente p { margin: 6px 0; }
    .etiqueta { align-self: flex-start; font-size: 12.5px; font-weight: 600; color: var(--marca-azul); }
    .botones { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 8px; }
    .fin { display: flex; gap: 8px; align-items: center; }
    .ruta { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 6px; }
    .ruta li { display: flex; gap: 10px; align-items: center; background: #fff; border: 1px solid var(--linea); border-radius: 10px; padding: 11px 12px; }
    .ruta li.actual { border-color: var(--marca-azul); box-shadow: 0 0 0 1px var(--marca-azul); }
    .ruta li.actual .num { background: var(--marca-azul); }
    .ruta li.cerrada { opacity: 0.6; }
    .novedad { width: 100%; margin-top: 8px; padding: 8px; border: 1px solid var(--linea-2); border-radius: 8px; font: inherit; }
    .extra { display: flex; flex-direction: column; gap: 8px; }
    .num { background: var(--marca-noche); color: #fff; border-radius: 6px; min-width: 26px; height: 26px; display: grid; place-items: center; font-size: 12px; font-weight: 700; }
  `,
})
export class MiDiaPagina implements OnInit {
  protected readonly guia = [
    { n: 1, texto: 'Inicie su jornada' },
    { n: 2, texto: 'Haga su ruta en orden' },
    { n: 3, texto: 'Termine la jornada' },
  ];

  /** Paso de la guía en el que va: 1 sin jornada, 2 con servicios por hacer, 3 todo hecho, 4 jornada terminada. */
  protected pasoGuia(d: { jornada: { horaSalida: string | null } | null; paradas: { estadoParada: string }[] }): number {
    if (!d.jornada) return 1;
    if (d.jornada.horaSalida) return 4;
    return d.paradas.some((p) => p.estadoParada === 'PENDIENTE') ? 2 : 3;
  }

  protected cerradas(d: { paradas: { estadoParada: string }[] }): number {
    return d.paradas.filter((p) => p.estadoParada !== 'PENDIENTE').length;
  }

  private readonly api = inject(Api);
  private readonly avisos = inject(Avisos);
  protected readonly hora = hora;
  protected readonly dia = signal<MiDia | null>(null);
  protected readonly cargando = signal(false);
  protected readonly siguiente = computed(() => this.dia()?.paradas.find((p) => p.esSiguiente) ?? null);
  protected readonly verExtra = signal(false);
  private readonly rest = inject(Rest);
  protected novedad = '';
  protected extra: { idCliente: number | null; idPunto: number | null; idMaquina: number | null; idTipoMantenimiento: number | null; motivo: string } = {
    idCliente: null, idPunto: null, idMaquina: null, idTipoMantenimiento: null, motivo: '' };

  async ngOnInit(): Promise<void> {
    await this.ejecutar(async () => this.dia.set(await this.api.miDia()));
  }

  async iniciar(): Promise<void> {
    await this.ejecutar(async () => {
      const gps = await ubicacionActual();
      this.dia.set(await this.rest.post<MiDia>('/api/campo/jornada/iniciar', { latitud: gps.latitud, longitud: gps.longitud, novedad: this.novedad }));
      this.avisos.ok('Jornada iniciada. ¡Buen día!');
    });
  }

  async finalizar(): Promise<void> {
    if (!confirm('¿Terminar la jornada de hoy?')) return;
    await this.ejecutar(async () => {
      const gps = await ubicacionActual().catch(() => null);
      this.dia.set(await this.api.finalizarJornada(gps?.latitud ?? null, gps?.longitud ?? null));
    });
  }

  async agregarExtra(): Promise<void> {
    await this.ejecutar(async () => {
      await this.rest.post('/api/campo/servicios-extra', { idMaquina: this.extra.idMaquina, idTipoMantenimiento: this.extra.idTipoMantenimiento,
        motivo: this.extra.motivo });
      this.avisos.ok('Servicio agregado al final de su ruta.');
      this.verExtra.set(false);
      this.extra = { idCliente: null, idPunto: null, idMaquina: null, idTipoMantenimiento: null, motivo: '' };
      this.dia.set(await this.api.miDia());
    });
  }

  private async ejecutar(fn: () => Promise<void>): Promise<void> {
    this.cargando.set(true);
    try {
      await fn();
    } catch (e) {
      this.avisos.error(e);
    } finally {
      this.cargando.set(false);
    }
  }
}
