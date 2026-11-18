import { DatePipe } from '@angular/common';
import { Component, computed, inject, OnDestroy, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatSelectModule } from '@angular/material/select';
import { ActivatedRoute } from '@angular/router';
import { CapaMapa, COLORES_RUTA, Mapa } from '../compartido/mapa';
import { Api } from '../core/api';
import { Avisos, hora } from '../core/infra';
import { Item, Tablero } from '../core/modelos';

const ICONO_ALERTA: Record<string, string> = {
  ESPERA: 'hourglass_top',
  SERIALES: 'barcode',
  LEJOS_DEL_PUNTO: 'wrong_location',
  SIN_REPORTE: 'schedule',
};

/** Avance de las rutas del día y alertas. Se actualiza cada 30 segundos. */
@Component({
  selector: 'app-seguimiento',
  imports: [FormsModule, DatePipe, MatFormFieldModule, MatSelectModule, MatIconModule, Mapa],
  template: `
    <div class="pagina">
      <div class="encabezado">
        <h1>Seguimiento</h1>
        <span class="muted">Actualizado {{ actualizado() | date: 'HH:mm:ss' }}</span>
        <mat-form-field appearance="outline" subscriptSizing="dynamic">
          <mat-label>Delegación</mat-label>
          <mat-select [ngModel]="idDelegacion()" (ngModelChange)="idDelegacion.set($event); cargar()">
            @for (d of delegaciones(); track d.id) { <mat-option [value]="d.id">{{ d.nombre }}</mat-option> }
          </mat-select>
        </mat-form-field>
      </div>
      @if (tablero(); as t) {
        <div class="cuerpo">
          <div class="izquierda">
            <section class="tarjeta alertas">
              <h2>Alertas</h2>
              @for (a of t.alertas; track $index) {
                <div class="alerta" [class.roja]="a.tipo === 'ESPERA' || a.tipo === 'SIN_REPORTE'">
                  <mat-icon>{{ iconoAlerta[a.tipo] }}</mat-icon>
                  <div>
                    <strong>{{ a.tecnico }}</strong> · {{ a.punto }}<br />
                    <span class="muted">{{ a.detalle }}</span>
                  </div>
                </div>
              } @empty {
                <p class="muted">Sin alertas.</p>
              }
            </section>
            @for (r of t.rutas; track r.idRuta; let i = $index) {
              <section class="tarjeta ruta">
                <div class="fila">
                  <span class="color" [style.background]="color(i)"></span>
                  <strong class="crece">{{ r.tecnico }}</strong>
                  <span class="muted">Inició {{ hora(r.inicioJornada) }}</span>
                </div>
                <div class="franjas" role="img"
                     [attr.aria-label]="r.hechas + ' hechos, ' + r.noIngreso + ' sin ingreso y ' + r.pendientes + ' pendientes'">
                  @for (s of segmentos(r); track $index) { <i [class]="s"></i> }
                </div>
                <div class="fila muted">
                  <span>{{ r.hechas }} hechos</span><span>{{ r.noIngreso }} sin ingreso</span><span>{{ r.pendientes }} pendientes</span>
                </div>
                @if (r.puntoActual) {
                  <div class="actual"><mat-icon>location_on</mat-icon> {{ r.puntoActual }} · <strong>{{ r.etapaActual }}</strong></div>
                } @else if (r.ultimoEvento) {
                  <div class="muted">Último reporte {{ r.ultimoEvento | date: 'HH:mm' }}</div>
                }
              </section>
            } @empty {
              <div class="tarjeta vacio">No hay rutas publicadas para hoy en esta delegación.</div>
            }
          </div>
          <app-mapa class="mapa" [capas]="capas()" />
        </div>
      }
    </div>
  `,
  styles: `
    .cuerpo { display: grid; grid-template-columns: 420px minmax(0, 1fr); gap: 16px; }
    .izquierda { display: flex; flex-direction: column; gap: 12px; max-height: calc(100vh - 160px); overflow-y: auto; }
    .alertas h2 { font: var(--mat-sys-title-medium); margin-bottom: 8px; }
    .alerta { display: flex; gap: 10px; padding: 8px; border-radius: 8px; background: var(--aviso-fondo); margin-bottom: 6px; }
    .alerta.roja { background: var(--error-fondo); }
    .ruta { display: flex; flex-direction: column; gap: 8px; }
    .fila { display: flex; gap: 12px; align-items: center; }
    .crece { flex: 1; }
    .color { width: 12px; height: 12px; border-radius: 50%; }
    .actual { display: flex; align-items: center; gap: 4px; color: var(--inees-azul); }
    .mapa { height: calc(100vh - 160px); }
    @media (max-width: 1000px) { .cuerpo { grid-template-columns: 1fr; } .mapa { height: 400px; } }
  `,
})
export class Seguimiento implements OnInit, OnDestroy {
  /** Un segmento por servicio de la ruta: hechos, sin ingreso, el actual y los pendientes. */
  protected segmentos(r: { hechas: number; noIngreso: number; pendientes: number; puntoActual?: string | null }): string[] {
    const l: string[] = [];
    for (let i = 0; i < r.hechas; i++) l.push('hecho');
    for (let i = 0; i < r.noIngreso; i++) l.push('alerta');
    for (let i = 0; i < r.pendientes; i++) l.push(i === 0 && r.puntoActual ? 'actual' : '');
    return l;
  }

  private readonly api = inject(Api);
  private readonly avisos = inject(Avisos);
  private readonly ruta = inject(ActivatedRoute);
  private temporizador?: ReturnType<typeof setInterval>;

  protected readonly hora = hora;
  protected readonly iconoAlerta = ICONO_ALERTA;
  protected readonly delegaciones = signal<Item[]>([]);
  protected readonly idDelegacion = signal<number | null>(null);
  protected readonly tablero = signal<Tablero | null>(null);
  protected readonly actualizado = signal<Date>(new Date());
  protected readonly capas = computed<CapaMapa[]>(() =>
    (this.tablero()?.rutas ?? [])
      .filter((r) => r.ultimaLatitud != null)
      .map((r, i) => ({
        color: this.color(i),
        puntos: [{ lat: r.ultimaLatitud!, lon: r.ultimaLongitud!, color: this.color(i), etiqueta: '🏍', titulo: r.tecnico }],
      })),
  );

  async ngOnInit(): Promise<void> {
    const c = await this.api.catalogos();
    this.delegaciones.set(c.delegaciones);
    const q = this.ruta.snapshot.queryParamMap.get('delegacion');
    this.idDelegacion.set(q ? Number(q) : (c.delegaciones.find((d) => d.nombre === 'BOGOTA')?.id ?? c.delegaciones[0]?.id ?? null));
    await this.cargar();
    this.temporizador = setInterval(() => this.cargar(), 30000);
  }

  ngOnDestroy(): void {
    clearInterval(this.temporizador);
  }

  color(i: number): string {
    return COLORES_RUTA[i % COLORES_RUTA.length];
  }

  async cargar(): Promise<void> {
    if (!this.idDelegacion()) return;
    try {
      this.tablero.set(await this.api.tablero(this.idDelegacion()!));
      this.actualizado.set(new Date());
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
