import { NgTemplateOutlet } from '@angular/common';
import { Component, computed, inject, input, OnDestroy, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatButtonToggleModule } from '@angular/material/button-toggle';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { MatSelectModule } from '@angular/material/select';
import { RouterLink } from '@angular/router';
import { Api } from '../core/api';
import { Avisos, ubicacionActual } from '../core/infra';
import { Formularios, OrdenCampo, ParadaCampo } from '../core/modelos';
import { Firma } from './firma';

/** Pasos visibles, en el orden en que el backend los exige. */
const PASOS = [
  { clave: 'INICIO_TRAYECTO', titulo: 'Ir al punto', icono: 'two_wheeler' },
  { clave: 'LLEGADA', titulo: 'Llegada', icono: 'location_on' },
  { clave: 'INGRESO', titulo: 'Ingreso', icono: 'door_open' },
  { clave: 'SERIALES', titulo: 'Seriales', icono: 'barcode' },
  { clave: 'ADMINISTRADOR', titulo: 'Administrador', icono: 'badge' },
  { clave: 'ESTADO_INICIAL', titulo: 'Estado inicial', icono: 'search' },
  { clave: 'TRABAJO', titulo: 'Trabajo', icono: 'build' },
  { clave: 'REPUESTOS', titulo: 'Repuestos', icono: 'inventory_2' },
  { clave: 'ESTADO_FINAL', titulo: 'Estado final', icono: 'power_settings_new' },
  { clave: 'CIERRE', titulo: 'Cierre', icono: 'sports_score' },
];

/** Campos de los formularios de cada paso. */
interface Campos {
  serialMaquina?: string;
  serialRouter?: string;
  serialUps?: string;
  numeroMaquina?: string;
  administrador?: string;
  celular?: string;
  idEstadoInicial?: number;
  detalleEstadoInicial?: string;
  actividades?: string;
  idEstadoFinal?: number;
  novedades: number[];
  detalleNovedad?: string;
  numeroRemision?: string;
  firma?: string | null;
  idCalificacion?: number;
  pendientes?: string;
}

interface FilaRepuesto {
  idRepuesto: number | null;
  cantidad: number;
  origen: 'INEES' | 'PROSEGUR';
}

@Component({
  selector: 'app-servicio',
  imports: [
    NgTemplateOutlet, FormsModule, RouterLink, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule, MatSelectModule,
    MatButtonToggleModule, MatProgressBarModule, Firma,
  ],
  template: `
    <div class="pagina movil">
      <a mat-button routerLink="/campo"><mat-icon>arrow_back</mat-icon> Mi ruta</a>
      @if (enviando()) { <mat-progress-bar mode="indeterminate" /> }

      @if (orden(); as o) {
        <section class="tarjeta cabecera">
          <h1>{{ o.punto }}</h1>
          <p>{{ o.cliente }}<br /><span class="muted">{{ o.direccion }} · {{ o.municipio }}</span></p>
          <div class="datos">
            <span class="chip">Máquina {{ o.deviceId }} · {{ o.tipoMaquina }}</span>
            <span class="chip info">{{ o.tipoMantenimiento ?? 'Servicio' }}</span>
            @if (o.modalidad) { <span class="chip">{{ o.modalidad }}</span> }
            @if (o.referenciaProsegur) { <span class="chip prosegur">Prosegur {{ o.referenciaProsegur }}</span> }
          </div>
          @if (o.descripcionSolicitud) { <p class="solicitud"><mat-icon>info</mat-icon> {{ o.descripcionSolicitud }}</p> }
        </section>

        <div class="avance-pasos">
          <div class="franjas" role="img"
               [attr.aria-label]="'Paso ' + (pasoVisible() + 1) + ' de ' + pasos.length + ': ' + pasos[pasoVisible()].titulo">
            @for (p of pasos; track p.clave; let i = $index) {
              <i [class.hecho]="i < indiceActual()" [class.actual]="i === indiceActual()"></i>
            }
          </div>
          <div class="paso-actual">
            @if (indiceActual() >= pasos.length) {
              <span>Todos los pasos completos</span>
            } @else {
              <span>Paso {{ indiceActual() + 1 }} de {{ pasos.length }}</span>
              <strong><mat-icon>{{ pasos[indiceActual()].icono }}</mat-icon>{{ pasos[indiceActual()].titulo }}</strong>
            }
          </div>
        </div>

        @for (a of avisosPaso(); track $index) {
          <div class="aviso" role="status"><mat-icon>campaign</mat-icon> {{ a }}</div>
        }

        <section class="tarjeta paso">
          @switch (paso()) {
            @case ('INICIO_TRAYECTO') {
              <h2>Salir hacia el punto</h2>
              <p class="muted">Se registra la hora de salida y se abre Google Maps desde donde está.</p>
              <button mat-flat-button class="grande" (click)="registrar('INICIO_TRAYECTO')"><mat-icon>two_wheeler</mat-icon> Salir ahora</button>
            }
            @case ('LLEGADA') {
              <h2>¿Ya llegó al punto?</h2>
              @if (o.mapsUrl) {
                <a mat-stroked-button class="grande" [href]="o.mapsUrl" target="_blank" rel="noopener"><mat-icon>navigation</mat-icon> Abrir en Google Maps</a>
              }
              <ng-container *ngTemplateOutlet="foto; context: { tipo: 'llegada', texto: 'Foto de la fachada o del punto' }" />
              <button mat-flat-button class="grande" (click)="registrar('LLEGADA')" [disabled]="!tieneFoto('llegada')">
                <mat-icon>location_on</mat-icon> Llegué al punto</button>
            }
            @case ('INGRESO') {
              <h2>Esperando ingreso</h2>
              <div class="reloj" [class.vencido]="minutosEspera() >= o.esperaMaxMinutos">
                <span class="min">{{ minutosEspera() }}</span> de {{ o.esperaMaxMinutos }} min
              </div>
              <mat-progress-bar [value]="(minutosEspera() / o.esperaMaxMinutos) * 100" />
              @if (minutosEspera() < o.esperaMaxMinutos) {
                <p class="muted">Cuando le den ingreso, regístrelo: la hora de entrada se toma sola.</p>
                <button mat-flat-button class="grande" (click)="registrar('INGRESO')"><mat-icon>door_open</mat-icon> Me dieron ingreso</button>
              } @else {
                <p><strong>Pasaron {{ o.esperaMaxMinutos }} minutos.</strong> Tome una foto como soporte y siga al siguiente punto;
                  este servicio pasa al día siguiente con prioridad.</p>
                <ng-container *ngTemplateOutlet="foto; context: { tipo: 'no_ingreso', texto: 'Foto de soporte' }" />
                <button mat-flat-button class="grande rojo" (click)="registrar('NO_INGRESO')" [disabled]="!tieneFoto('no_ingreso')">
                  <mat-icon>skip_next</mat-icon> No me dieron ingreso</button>
              }
            }
            @case ('SERIALES') {
              <h2>Verifique los seriales</h2>
              <p class="muted">Escríbalos tal como aparecen en las etiquetas.</p>
              <mat-form-field appearance="outline"><mat-label>Serial de la máquina</mat-label><input matInput [(ngModel)]="f.serialMaquina" /></mat-form-field>
              <mat-form-field appearance="outline"><mat-label>Serial del router</mat-label><input matInput [(ngModel)]="f.serialRouter" /></mat-form-field>
              <mat-form-field appearance="outline"><mat-label>Serial de la UPS</mat-label><input matInput [(ngModel)]="f.serialUps" /></mat-form-field>
              <mat-form-field appearance="outline"><mat-label>Número de la máquina (opcional)</mat-label><input matInput [(ngModel)]="f.numeroMaquina" /></mat-form-field>
              <ng-container *ngTemplateOutlet="foto; context: { tipo: 'seriales', texto: 'Foto de las placas de serial' }" />
              <button mat-flat-button class="grande" (click)="registrar('SERIALES', { serialMaquina: f.serialMaquina, serialRouter: f.serialRouter, serialUps: f.serialUps, numeroMaquina: f.numeroMaquina })"
                      [disabled]="!f.serialMaquina || !tieneFoto('seriales')">Continuar</button>
            }
            @case ('ADMINISTRADOR') {
              <h2>¿Quién lo atiende?</h2>
              <mat-form-field appearance="outline"><mat-label>Nombre del administrador</mat-label><input matInput [(ngModel)]="f.administrador" autocomplete="off" /></mat-form-field>
              <mat-form-field appearance="outline"><mat-label>Celular</mat-label><input matInput type="tel" inputmode="numeric" [(ngModel)]="f.celular" /></mat-form-field>
              <button mat-flat-button class="grande" (click)="registrar('ADMINISTRADOR', { administrador: f.administrador, celular: f.celular })"
                      [disabled]="!f.administrador || !f.celular">Continuar</button>
            }
            @case ('ESTADO_INICIAL') {
              <h2>¿Cómo encontró la máquina?</h2>
              <mat-button-toggle-group [(ngModel)]="f.idEstadoInicial" class="opciones" aria-label="Estado inicial">
                @for (e of formularios()?.estadosMaquina; track e.id) { <mat-button-toggle [value]="e.id">{{ e.nombre }}</mat-button-toggle> }
              </mat-button-toggle-group>
              <mat-form-field appearance="outline"><mat-label>Lo primero que vio (detalle)</mat-label><textarea matInput rows="3" [(ngModel)]="f.detalleEstadoInicial"></textarea></mat-form-field>
              <ng-container *ngTemplateOutlet="foto; context: { tipo: 'estado_inicial', texto: 'Fotos de cómo la encontró' }" />
              <button mat-flat-button class="grande" (click)="registrar('ESTADO_INICIAL', { idEstadoInicial: f.idEstadoInicial, detalleEstadoInicial: f.detalleEstadoInicial })"
                      [disabled]="!f.idEstadoInicial || !f.detalleEstadoInicial || !tieneFoto('estado_inicial')">Continuar</button>
            }
            @case ('TRABAJO') {
              <h2>¿Qué le hizo a la máquina?</h2>
              <mat-form-field appearance="outline"><mat-label>Actividades realizadas</mat-label><textarea matInput rows="5" [(ngModel)]="f.actividades"></textarea></mat-form-field>
              <ng-container *ngTemplateOutlet="foto; context: { tipo: 'trabajo', texto: 'Fotos del trabajo' }" />
              <button mat-flat-button class="grande" (click)="registrar('TRABAJO', { actividades: f.actividades })"
                      [disabled]="(f.actividades?.length ?? 0) < 10 || !tieneFoto('trabajo')">Continuar</button>
            }
            @case ('REPUESTOS') {
              <h2>Repuestos usados</h2>
              @for (r of repuestos(); track $index; let i = $index) {
                <div class="repuesto">
                  <mat-button-toggle-group [(ngModel)]="r.origen" (change)="r.idRepuesto = null" aria-label="Origen del repuesto">
                    <mat-button-toggle value="INEES">Mi stock</mat-button-toggle>
                    <mat-button-toggle value="PROSEGUR">Prosegur</mat-button-toggle>
                  </mat-button-toggle-group>
                  <mat-form-field appearance="outline" subscriptSizing="dynamic">
                    <mat-label>Repuesto</mat-label>
                    <mat-select [(ngModel)]="r.idRepuesto">
                      @if (r.origen === 'INEES') {
                        @for (s of formularios()?.miStock; track s.idRepuesto) {
                          <mat-option [value]="s.idRepuesto">{{ s.nombre }} (tiene {{ s.cantidad }})</mat-option>
                        }
                      } @else {
                        @for (s of formularios()?.repuestos; track s.id) { <mat-option [value]="s.id">{{ s.nombre }}</mat-option> }
                      }
                    </mat-select>
                  </mat-form-field>
                  <div class="cantidad">
                    <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Cantidad</mat-label>
                      <input matInput type="number" min="1" [(ngModel)]="r.cantidad" /></mat-form-field>
                    <button mat-icon-button (click)="quitarRepuesto(i)" aria-label="Quitar repuesto"><mat-icon>delete</mat-icon></button>
                  </div>
                </div>
              }
              <button mat-stroked-button (click)="agregarRepuesto()"><mat-icon>add</mat-icon> Agregar repuesto</button>
              @if (repuestos().length) {
                <ng-container *ngTemplateOutlet="foto; context: { tipo: 'repuestos', texto: 'Foto de los repuestos' }" />
              }
              <button mat-flat-button class="grande" (click)="registrarRepuestos()"
                      [disabled]="repuestos().length > 0 && (!tieneFoto('repuestos') || repuestosIncompletos())">
                {{ repuestos().length ? 'Continuar' : 'No usé repuestos' }}</button>
            }
            @case ('ESTADO_FINAL') {
              <h2>¿Cómo queda la máquina?</h2>
              <mat-button-toggle-group [(ngModel)]="f.idEstadoFinal" class="opciones" aria-label="Estado final">
                @for (e of formularios()?.estadosMaquina; track e.id) { <mat-button-toggle [value]="e.id">{{ e.nombre }}</mat-button-toggle> }
              </mat-button-toggle-group>
              <mat-form-field appearance="outline">
                <mat-label>Novedades (opcional)</mat-label>
                <mat-select multiple [(ngModel)]="f.novedades">
                  @for (n of formularios()?.tiposNovedad; track n.id) { <mat-option [value]="n.id">{{ n.nombre }}</mat-option> }
                </mat-select>
              </mat-form-field>
              @if (f.novedades?.length) {
                <mat-form-field appearance="outline"><mat-label>Detalle de la novedad</mat-label><textarea matInput rows="3" [(ngModel)]="f.detalleNovedad"></textarea></mat-form-field>
              }
              <ng-container *ngTemplateOutlet="foto; context: { tipo: 'estado_final', texto: 'Foto de cómo queda' }" />
              <button mat-flat-button class="grande"
                      (click)="registrar('ESTADO_FINAL', { idEstadoFinal: f.idEstadoFinal, novedades: f.novedades ?? [], detalleNovedad: f.detalleNovedad })"
                      [disabled]="!f.idEstadoFinal || !tieneFoto('estado_final') || (f.novedades?.length && !f.detalleNovedad)">Continuar</button>
            }
            @case ('CIERRE') {
              <h2>Cierre del servicio</h2>
              <mat-form-field appearance="outline">
                <mat-label>Remisión</mat-label>
                <mat-select [(ngModel)]="f.numeroRemision">
                  @for (r of formularios()?.remisiones; track r) { <mat-option [value]="r">{{ r }}</mat-option> }
                </mat-select>
              </mat-form-field>
              <mat-form-field appearance="outline">
                <mat-label>Calificación (opcional)</mat-label>
                <mat-select [(ngModel)]="f.idCalificacion">
                  @for (c of formularios()?.calificaciones; track c.id) { <mat-option [value]="c.id">{{ c.nombre }}</mat-option> }
                </mat-select>
              </mat-form-field>
              <mat-form-field appearance="outline"><mat-label>Pendientes (opcional)</mat-label><textarea matInput rows="2" [(ngModel)]="f.pendientes"></textarea></mat-form-field>
              <p><strong>Firma de quien recibe</strong></p>
              <app-firma (cambio)="f.firma = $event" />
              <button mat-flat-button class="grande verde"
                      (click)="registrar('CIERRE', { numeroRemision: f.numeroRemision, firma: f.firma, idCalificacion: f.idCalificacion, pendientes: f.pendientes })"
                      [disabled]="!f.numeroRemision || !f.firma"><mat-icon>check</mat-icon> Cerrar servicio</button>
            }
            @default {
              <div class="final">
                <mat-icon class="ok">task_alt</mat-icon>
                <h2>{{ o.estado === 1 ? 'Servicio cerrado' : 'Servicio reprogramado' }}</h2>
              </div>
              @if (siguiente(); as s) {
                <div class="tarjeta siguiente">
                  <span class="muted">Siguiente punto</span>
                  <strong>{{ s.punto }}</strong>
                  <span class="muted">{{ s.direccion }} · {{ s.municipio }}</span>
                  <div class="botones">
                    @if (s.mapsUrl) {
                      <a mat-stroked-button class="grande" [href]="s.mapsUrl" target="_blank" rel="noopener"><mat-icon>navigation</mat-icon> Google Maps</a>
                    }
                    <a mat-flat-button class="grande" [routerLink]="['/campo/servicio', s.idOrden]"><mat-icon>arrow_forward</mat-icon> Siguiente</a>
                  </div>
                </div>
              } @else {
                <a mat-flat-button class="grande" routerLink="/campo">Volver a mi ruta</a>
              }
            }
          }
        </section>
      }
    </div>

    <ng-template #foto let-tipo="tipo" let-texto="texto">
      <div class="foto">
        <input #archivo type="file" accept="image/*" capture="environment" hidden (change)="subirFoto(tipo, archivo)" />
        <button mat-stroked-button type="button" (click)="archivo.click()" [disabled]="subiendoFoto()">
          <mat-icon>photo_camera</mat-icon> {{ texto }}
        </button>
        @if (cantidadFotos(tipo); as n) { <span class="chip ok">{{ n }} {{ n === 1 ? 'foto' : 'fotos' }}</span> }
        @else { <span class="chip aviso">Obligatoria</span> }
      </div>
    </ng-template>
  `,
  styles: `
    .movil { max-width: 640px; padding: 8px 12px 48px; display: flex; flex-direction: column; gap: 12px; }
    .cabecera h1 { font-size: 22px; line-height: 1.2; letter-spacing: -0.02em; }
    .chip.prosegur { background: var(--marca-noche); color: #fff; }
    .cabecera p { margin: 6px 0; }
    .datos { display: flex; gap: 6px; flex-wrap: wrap; }
    .solicitud { display: flex; gap: 6px; background: var(--info-fondo); color: var(--marca-noche); padding: 10px; border-radius: 8px; }
    .avance-pasos { display: flex; flex-direction: column; gap: 8px; padding: 2px 2px 0; }
    .avance-pasos .franjas > i { height: 10px; }
    .paso-actual { display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 13px; color: var(--tinta-2); }
    .paso-actual strong { display: inline-flex; align-items: center; gap: 6px; color: var(--marca-noche); font-size: 15px; }
    .paso-actual mat-icon { color: var(--marca-azul); font-size: 20px; width: 20px; height: 20px; }
    .aviso { display: flex; gap: 8px; background: var(--aviso-fondo); color: var(--aviso); padding: 10px; border-radius: 10px; }
    .paso { display: flex; flex-direction: column; gap: 12px; }
    .paso h2 { font-size: 20px; line-height: 1.25; letter-spacing: -0.015em; }
    .paso p { margin: 0; }
    .grande { --mat-button-filled-container-height: 52px; --mat-button-outlined-container-height: 52px; height: 52px; font-size: 16px; }
    .rojo { --mat-button-filled-container-color: var(--inees-rojo); }
    .verde { --mat-button-filled-container-color: var(--inees-verde); }
    .reloj { font-size: 18px; text-align: center; }
    .reloj .min { font-size: 48px; font-weight: 600; font-stretch: 110%; color: var(--marca-noche); font-variant-numeric: tabular-nums; }
    .reloj.vencido, .reloj.vencido .min { color: var(--error); }
    .opciones { flex-wrap: wrap; }
    .foto { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
    .repuesto { display: flex; flex-direction: column; gap: 8px; padding: 10px; border: 1px solid var(--linea); border-radius: 10px; }
    .cantidad { display: flex; gap: 8px; align-items: center; }
    .final { text-align: center; }
    .final .ok { color: var(--inees-verde); font-size: 56px; width: 56px; height: 56px; }
    .siguiente { display: flex; flex-direction: column; gap: 4px; border: 2px solid var(--inees-ambar); }
    .botones { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 8px; }
  `,
})
export class ServicioPagina implements OnInit, OnDestroy {
  private readonly api = inject(Api);
  private readonly avisos = inject(Avisos);
  readonly id = input.required<string>();

  protected readonly pasos = PASOS;
  /** Paso que se nombra en la etiqueta accesible (el último cuando ya terminó). */
  protected pasoVisible(): number {
    return Math.min(this.indiceActual(), this.pasos.length - 1);
  }
  protected readonly orden = signal<OrdenCampo | null>(null);
  protected readonly formularios = signal<Formularios | null>(null);
  protected readonly siguiente = signal<ParadaCampo | null>(null);
  protected readonly avisosPaso = signal<string[]>([]);
  protected readonly enviando = signal(false);
  protected readonly subiendoFoto = signal(false);
  protected readonly ahora = signal(Date.now());
  protected readonly repuestos = signal<FilaRepuesto[]>([]);
  protected f: Campos = { novedades: [] };
  private reloj?: ReturnType<typeof setInterval>;

  /** El paso que toca: el primero de los siguientes permitidos (INGRESO cubre también NO_INGRESO). */
  protected readonly paso = computed(() => this.orden()?.siguientes[0] ?? null);
  protected readonly indiceActual = computed(() => {
    const p = this.paso();
    return p ? PASOS.findIndex((x) => x.clave === p) : PASOS.length;
  });
  protected readonly minutosEspera = computed(() => {
    const llegada = this.orden()?.horaLlegada;
    return llegada ? Math.max(0, Math.floor((this.ahora() - new Date(llegada).getTime()) / 60000)) : 0;
  });
  protected readonly repuestosIncompletos = computed(() => this.repuestos().some((r) => !r.idRepuesto || r.cantidad < 1));

  async ngOnInit(): Promise<void> {
    this.reloj = setInterval(() => this.ahora.set(Date.now()), 15000);
    try {
      const [o, form] = await Promise.all([this.api.ordenCampo(Number(this.id())), this.api.formularios()]);
      this.orden.set(o);
      this.formularios.set(form);
    } catch (e) {
      this.avisos.error(e);
    }
  }

  ngOnDestroy(): void {
    clearInterval(this.reloj);
  }

  cantidadFotos(tipo: string): number {
    return this.orden()?.evidencias.filter((e) => e.tipo === tipo).length ?? 0;
  }

  tieneFoto(tipo: string): boolean {
    return this.cantidadFotos(tipo) > 0;
  }

  async subirFoto(tipo: string, input: HTMLInputElement): Promise<void> {
    const archivo = input.files?.[0];
    input.value = '';
    if (!archivo) return;
    this.subiendoFoto.set(true);
    try {
      const gps = await ubicacionActual().catch(() => null);
      const ev = await this.api.subirFoto(this.orden()!.idOrden, tipo, archivo, gps);
      this.orden.update((o) => (o ? { ...o, evidencias: [...o.evidencias, ev] } : o));
    } catch (e) {
      this.avisos.error(e);
    } finally {
      this.subiendoFoto.set(false);
    }
  }

  agregarRepuesto(): void {
    this.repuestos.update((l) => [...l, { idRepuesto: null, cantidad: 1, origen: 'INEES' }]);
  }

  quitarRepuesto(i: number): void {
    this.repuestos.update((l) => l.filter((_, j) => j !== i));
  }

  registrarRepuestos(): Promise<void> {
    return this.registrar('REPUESTOS', {
      repuestos: this.repuestos().map((r) => ({ idRepuesto: r.idRepuesto, cantidad: r.cantidad, origen: r.origen })),
    });
  }

  async registrar(etapa: string, datos: Record<string, unknown> = {}): Promise<void> {
    this.enviando.set(true);
    try {
      // La llegada exige GPS; en los demás pasos se envía si está disponible.
      const gps = etapa === 'LLEGADA' ? await ubicacionActual() : await ubicacionActual().catch(() => null);
      const r = await this.api.paso(this.orden()!.idOrden, etapa, { ...datos, ...(gps ?? {}) });
      this.orden.set(r.orden);
      this.siguiente.set(r.siguiente);
      this.avisosPaso.set(r.avisos);
      this.ahora.set(Date.now());
      if (etapa === 'INICIO_TRAYECTO' && r.orden.mapsUrl) {
        const destino = gps
          ? r.orden.mapsUrl.replace('?api=1', `?api=1&origin=${gps.latitud},${gps.longitud}`)
          : r.orden.mapsUrl;
        window.open(destino, '_blank', 'noopener');
      }
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (e) {
      this.avisos.error(e);
    } finally {
      this.enviando.set(false);
    }
  }
}
