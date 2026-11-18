import { CdkDrag, CdkDragDrop, CdkDropList, CdkDropListGroup } from '@angular/cdk/drag-drop';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatMenuModule } from '@angular/material/menu';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { MatSelectModule } from '@angular/material/select';
import { MatTooltipModule } from '@angular/material/tooltip';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { CapaMapa, COLORES_RUTA, Mapa } from '../compartido/mapa';
import { Api } from '../core/api';
import { Avisos, hora, hoyIso } from '../core/infra';
import { Item, Maquina, NOMBRE_PRIORIDAD, Orden, Plan, Ruta } from '../core/modelos';
import { Rest } from '../core/rest';

/**
 * Rutas del día: la bolsa de servicios y una columna por motorizado. Se arrastran servicios entre columnas o dentro de
 * una ruta para reordenarlos; cada cambio queda en el historial del servicio.
 */
@Component({
  selector: 'app-planeacion',
  imports: [
    FormsModule, RouterLink, CdkDropListGroup, CdkDropList, CdkDrag, MatButtonModule, MatIconModule, MatFormFieldModule,
    MatInputModule, MatSelectModule, MatProgressBarModule, MatTooltipModule, MatMenuModule, MatCheckboxModule, Mapa,
  ],
  template: `
    <div class="pagina">
      <div class="encabezado">
        <h1>Rutas del día</h1>
        <mat-form-field appearance="outline" subscriptSizing="dynamic">
          <mat-label>Fecha</mat-label>
          <input matInput type="date" [ngModel]="fecha()" (ngModelChange)="fecha.set($event); cargar()" />
        </mat-form-field>
        <mat-form-field appearance="outline" subscriptSizing="dynamic">
          <mat-label>Delegación</mat-label>
          <mat-select [ngModel]="idDelegacion()" (ngModelChange)="idDelegacion.set($event); cargar()">
            @for (d of delegaciones(); track d.id) {
              <mat-option [value]="d.id">{{ d.nombre }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <button mat-stroked-button (click)="agregando.set(!agregando())">
          <mat-icon>add</mat-icon> Agregar servicio
        </button>
        <button mat-flat-button (click)="generar()" [disabled]="ocupado()"
                matTooltip="Arma una ruta óptima por motorizado con la bolsa y los mantenimientos vencidos">
          <mat-icon>auto_mode</mat-icon> {{ hayBorradores() ? 'Volver a generar' : 'Generar rutas' }}
        </button>
        <button mat-flat-button class="publicar" (click)="publicar()" [disabled]="ocupado() || !hayBorradores()">
          <mat-icon>send</mat-icon> Publicar
        </button>
        <button mat-stroked-button [matMenuTriggerFor]="menuMaestro" (menuOpened)="prepararMaestro()"
                matTooltip="Programación de varios días en Excel (el «Excel maestro» del consolidado)">
          <mat-icon>table_view</mat-icon> Excel maestro
        </button>
      </div>
      <mat-menu #menuMaestro="matMenu">
        <div class="maestro" (click)="$event.stopPropagation()" (keydown)="$event.stopPropagation()">
          <mat-form-field appearance="outline" subscriptSizing="dynamic">
            <mat-label>Desde</mat-label><input matInput type="date" [(ngModel)]="maestroDesde" />
          </mat-form-field>
          <mat-form-field appearance="outline" subscriptSizing="dynamic">
            <mat-label>Hasta</mat-label><input matInput type="date" [(ngModel)]="maestroHasta" />
          </mat-form-field>
          <mat-checkbox [(ngModel)]="maestroSoloDelegacion">Solo la delegación elegida</mat-checkbox>
          <button mat-flat-button (click)="descargarMaestro()"><mat-icon>download</mat-icon> Descargar</button>
        </div>
      </mat-menu>

      @if (ocupado()) {
        <mat-progress-bar mode="indeterminate" />
      }

      @if (agregando()) {
        <div class="tarjeta agregar">
          <mat-form-field appearance="outline" subscriptSizing="dynamic" class="crece">
            <mat-label>Buscar máquina por ID, punto o cliente</mat-label>
            <input matInput [(ngModel)]="textoBusqueda" (keyup.enter)="buscar()" />
            <button mat-icon-button matSuffix (click)="buscar()" aria-label="Buscar"><mat-icon>search</mat-icon></button>
          </mat-form-field>
          <mat-form-field appearance="outline" subscriptSizing="dynamic">
            <mat-label>Tipo de servicio</mat-label>
            <mat-select [(ngModel)]="tipoNuevo">
              @for (t of tiposMantenimiento(); track t.id) {
                <mat-option [value]="t.id">{{ t.nombre }}</mat-option>
              }
            </mat-select>
          </mat-form-field>
          <div class="resultados">
            @for (m of encontradas(); track m.idMaquina) {
              <button mat-stroked-button (click)="crear(m)" [disabled]="!tipoNuevo">
                {{ m.deviceId }} · {{ m.punto }} <span class="muted">({{ m.municipio }})</span>
              </button>
            } @empty {
              <span class="muted">Busque la máquina y elija el tipo de servicio; entra a la bolsa con prioridad 1.</span>
            }
          </div>
        </div>
      }

      @if (plan(); as p) {
        <div class="resumen">
          @for (n of [1, 2, 3, 4]; track n) {
            <span class="chip"><span class="prio p{{ n }}">{{ p.bolsaPorPrioridad[n] ?? 0 }}</span> {{ nombrePrioridad[n] }}</span>
          }
          @if (p.sinUbicacion.length) {
            <a class="chip error" routerLink="/geolocalizar" [queryParams]="{ delegacion: idDelegacion() }">
              {{ p.sinUbicacion.length }} en la bolsa sin ubicación: ponerles el pin
            </a>
          }
        </div>

        <div class="cuerpo">
          <div class="tablero" cdkDropListGroup>
            <!-- Bolsa -->
            <section class="columna bolsa">
              <header>
                <strong>Bolsa</strong>
                <span class="muted">{{ p.bolsa.length }} sin ruta</span>
              </header>
              <div class="lista" cdkDropList [cdkDropListData]="null" (cdkDropListDropped)="soltar($event)">
                @for (o of p.bolsa; track o.idOrden) {
                  <div class="item" cdkDrag [cdkDragData]="o" [cdkDragDisabled]="!o.latitud">
                    <span class="prio p{{ o.prioridad }}" [matTooltip]="nombrePrioridad[o.prioridad]">{{ o.prioridad }}</span>
                    <div class="texto">
                      <strong>{{ o.punto }}</strong>
                      <span class="muted">{{ o.cliente }} · {{ o.municipio }}</span>
                      <span class="muted">{{ o.tipoMantenimiento ?? 'Sin tipo' }}
                        @if (o.vecesReprogramada) { · reprogramado {{ o.vecesReprogramada }} }
                        @if (o.referenciaProsegur) { · {{ o.referenciaProsegur }} }</span>
                      @if (!o.latitud) { <span class="chip error">Sin ubicación</span> }
                    </div>
                  </div>
                } @empty {
                  <div class="vacio">La bolsa está vacía.</div>
                }
              </div>
            </section>

            <!-- Una columna por motorizado -->
            @for (r of p.rutas; track r.idRuta; let i = $index) {
              <section class="columna">
                <header>
                  <span class="color" [style.background]="color(i)"></span>
                  <strong>{{ r.tecnico }}</strong>
                  <span class="chip" [class.aviso]="r.estado === 'BORRADOR'" [class.ok]="r.estado !== 'BORRADOR'">{{ r.estado }}</span>
                </header>
                <div class="metricas">
                  <span>{{ r.paradas.length }} servicios · {{ r.kmEstimados ?? 0 }} km</span>
                  <span [class.excedido]="(r.minutosEstimados ?? 0) > r.capacidadMinutos"
                        [matTooltip]="(r.minutosEstimados ?? 0) > r.capacidadMinutos ? 'Supera la jornada: considere mover servicios' : ''">
                    {{ r.minutosEstimados ?? 0 }} / {{ r.capacidadMinutos }} min</span>
                </div>
                <div class="avance" [class.alerta]="(r.minutosEstimados ?? 0) > r.capacidadMinutos" role="img"
                     [attr.aria-label]="(r.minutosEstimados ?? 0) + ' de ' + r.capacidadMinutos + ' minutos de la jornada'">
                  <span [style.width.%]="minimo(100, ((r.minutosEstimados ?? 0) / r.capacidadMinutos) * 100)"></span>
                </div>
                <div class="acciones">
                  <button mat-button (click)="recalcular(r)" matTooltip="Reordena los pendientes por la menor distancia">
                    <mat-icon>alt_route</mat-icon> Optimizar
                  </button>
                  @if (r.estado === 'BORRADOR') {
                    <button mat-button (click)="publicarRuta(r)"><mat-icon>send</mat-icon> Publicar</button>
                  }
                </div>
                <div class="lista" cdkDropList [cdkDropListData]="r" (cdkDropListDropped)="soltar($event)">
                  @for (pa of r.paradas; track pa.idParada) {
                    <div class="item" cdkDrag [cdkDragData]="pa.orden" [cdkDragDisabled]="pa.estadoParada !== 'PENDIENTE'"
                         [class.hecha]="pa.estadoParada !== 'PENDIENTE'">
                      <span class="sec" [style.background]="color(i)">{{ pa.secuencia }}</span>
                      <div class="texto">
                        <strong><span class="prio p{{ pa.orden.prioridad }}">{{ pa.orden.prioridad }}</span> {{ pa.orden.punto }}</strong>
                        <span class="muted">{{ pa.orden.cliente }} · {{ hora(pa.horaEstimada) }} · {{ pa.kmTramo ?? 0 }} km</span>
                        @if (pa.estadoParada !== 'PENDIENTE') {
                          <span class="chip" [class.ok]="pa.estadoParada === 'HECHA'" [class.error]="pa.estadoParada !== 'HECHA'">
                            {{ pa.estadoParada }}</span>
                        } @else if (pa.orden.etapaActual) {
                          <span class="chip aviso">{{ pa.orden.etapaActual }}</span>
                        }
                      </div>
                      @if (pa.estadoParada === 'PENDIENTE') {
                        <button mat-icon-button (click)="fijar(pa.idParada, !pa.fijada)"
                                [matTooltip]="pa.fijada ? 'Fijado a este motorizado' : 'Fijar a este motorizado'">
                          <mat-icon [class.fijada]="pa.fijada">{{ pa.fijada ? 'push_pin' : 'keep' }}</mat-icon>
                        </button>
                      }
                    </div>
                  } @empty {
                    <div class="vacio">Arrastre servicios aquí.</div>
                  }
                </div>
              </section>
            } @empty {
              <section class="columna vacia">
                <div class="vacio">
                  Aún no hay rutas para este día. Cargue el Excel de Prosegur y luego use «Generar rutas».
                </div>
              </section>
            }
          </div>
          <app-mapa class="mapa" [capas]="capas()" />
        </div>
      }
    </div>
  `,
  styles: `
    .publicar { --mat-button-filled-container-color: var(--inees-verde); }
    .maestro { display: flex; flex-direction: column; gap: 10px; padding: 12px 16px; min-width: 240px; }
    .resumen { display: flex; gap: 8px; flex-wrap: wrap; margin: 8px 0 12px; }
    .resumen .chip { display: inline-flex; gap: 6px; align-items: center; background: #fff; padding: 4px 10px; }
    .agregar { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 12px; }
    .crece { flex: 1; min-width: 260px; }
    .resultados { display: flex; gap: 8px; flex-wrap: wrap; width: 100%; }
    .cuerpo { display: grid; grid-template-columns: minmax(0, 1fr) 420px; gap: 16px; align-items: start; }
    .mapa { position: sticky; top: 80px; height: calc(100vh - 220px); }
    .tablero { display: flex; gap: 12px; overflow-x: auto; padding-bottom: 8px; }
    .columna {
      flex: 0 0 300px; background: #fff; border-radius: 12px; padding: 12px; display: flex; flex-direction: column; gap: 8px;
      max-height: calc(100vh - 220px);
    }
    .columna.bolsa { background: var(--fondo-2); border: 1px dashed var(--linea-2); }
    .columna.vacia { flex: 1; }
    .columna header { display: flex; align-items: center; gap: 8px; }
    .columna header strong { flex: 1; }
    .color { width: 12px; height: 12px; border-radius: 50%; }
    .metricas { display: flex; justify-content: space-between; font-size: 12px; color: var(--inees-gris); }
    .excedido { color: var(--inees-rojo); font-weight: 700; }
    .acciones { display: flex; gap: 4px; }
    .lista { overflow-y: auto; display: flex; flex-direction: column; gap: 6px; min-height: 60px; flex: 1; }
    .item {
      display: flex; gap: 8px; align-items: flex-start; padding: 8px; border-radius: 8px; background: #fff;
      border: 1px solid var(--linea); cursor: grab;
    }
    .item.hecha { opacity: 0.55; cursor: default; }
    .texto { display: flex; flex-direction: column; gap: 2px; flex: 1; min-width: 0; font-size: 13px; }
    .texto strong { font-weight: 600; }
    .sec { color: #fff; font-weight: 700; border-radius: 50%; min-width: 24px; height: 24px; display: grid; place-items: center; font-size: 12px; }
    .fijada { color: var(--inees-rojo); }
    .cdk-drag-preview { box-shadow: 0 6px 16px rgb(0 0 0 / 0.2); border-radius: 8px; background: #fff; padding: 8px; }
    .cdk-drag-placeholder { opacity: 0.3; }
    @media (max-width: 1100px) {
      .cuerpo { grid-template-columns: 1fr; }
      .mapa { position: static; height: 360px; }
    }
  `,
})
export class Planeacion implements OnInit {
  private readonly api = inject(Api);
  private readonly avisos = inject(Avisos);
  private readonly ruta = inject(ActivatedRoute);

  protected readonly nombrePrioridad = NOMBRE_PRIORIDAD;
  protected readonly hora = hora;
  protected readonly fecha = signal(hoyIso(1));
  protected readonly idDelegacion = signal<number | null>(null);
  protected readonly delegaciones = signal<Item[]>([]);
  protected readonly tiposMantenimiento = signal<Item[]>([]);
  protected readonly plan = signal<Plan | null>(null);
  protected readonly ocupado = signal(false);
  protected readonly agregando = signal(false);
  protected readonly encontradas = signal<Maquina[]>([]);
  protected textoBusqueda = '';
  protected tipoNuevo: number | null = null;
  protected maestroDesde = '';
  protected maestroHasta = '';
  protected maestroSoloDelegacion = true;
  private readonly rest = inject(Rest);

  /** Por defecto, la semana (lunes a domingo) del día que se está planeando, como el consolidado de PHP. */
  protected prepararMaestro(): void {
    if (this.maestroDesde) return;
    const [a, m, d] = this.fecha().split('-').map(Number);
    const lunes = new Date(a, m - 1, d - ((new Date(a, m - 1, d).getDay() + 6) % 7));
    const iso = (x: Date) => `${x.getFullYear()}-${String(x.getMonth() + 1).padStart(2, '0')}-${String(x.getDate()).padStart(2, '0')}`;
    this.maestroDesde = iso(lunes);
    this.maestroHasta = iso(new Date(lunes.getFullYear(), lunes.getMonth(), lunes.getDate() + 6));
  }

  protected async descargarMaestro(): Promise<void> {
    try {
      await this.rest.descargar('/api/planeacion/maestro/excel', {
        desde: this.maestroDesde, hasta: this.maestroHasta, idDelegacion: this.maestroSoloDelegacion ? this.idDelegacion() : null,
      });
    } catch (e) {
      this.avisos.error(e);
    }
  }

  protected readonly minimo = Math.min;
  protected readonly hayBorradores = computed(() => this.plan()?.rutas.some((r) => r.estado === 'BORRADOR') ?? false);
  protected readonly capas = computed<CapaMapa[]>(() => {
    const p = this.plan();
    if (!p) return [];
    const capas: CapaMapa[] = p.rutas.map((r, i) => ({
      color: this.color(i),
      unir: true,
      partida: r.latitudPartida != null ? [r.latitudPartida, r.longitudPartida!] : null,
      puntos: r.paradas
        .filter((pa) => pa.orden.latitud != null)
        .map((pa) => ({
          lat: pa.orden.latitud!,
          lon: pa.orden.longitud!,
          color: pa.estadoParada === 'PENDIENTE' ? this.color(i) : '#a9b6c3',
          etiqueta: String(pa.secuencia),
          titulo: `${r.tecnico} · ${pa.secuencia}. ${pa.orden.punto}`,
        })),
    }));
    capas.push({
      color: '#5c6976',
      puntos: p.bolsa
        .filter((o) => o.latitud != null)
        .map((o) => ({ lat: o.latitud!, lon: o.longitud!, color: '#5c6976', etiqueta: '·', titulo: `Bolsa: ${o.punto}` })),
    });
    return capas;
  });

  async ngOnInit(): Promise<void> {
    const q = this.ruta.snapshot.queryParamMap;
    if (q.get('fecha')) this.fecha.set(q.get('fecha')!);
    const c = await this.api.catalogos();
    this.delegaciones.set(c.delegaciones);
    this.tiposMantenimiento.set(c.tiposMantenimiento);
    this.idDelegacion.set(
      q.get('delegacion') ? Number(q.get('delegacion')) : (c.delegaciones.find((d) => d.nombre === 'BOGOTA')?.id ?? c.delegaciones[0]?.id ?? null),
    );
    await this.cargar();
  }

  color(i: number): string {
    return COLORES_RUTA[i % COLORES_RUTA.length];
  }

  async cargar(): Promise<void> {
    const del = this.idDelegacion();
    if (!del) return;
    await this.trabajar(async () => this.plan.set(await this.api.plan(this.fecha(), del)));
  }

  async generar(): Promise<void> {
    await this.trabajar(async () => {
      const r = await this.api.generar(this.fecha(), this.idDelegacion()!);
      this.plan.set(r.plan);
      this.avisos.ok(
        `${r.asignados} servicios en ruta, ${r.preventivosCreados} mantenimientos vencidos agregados` +
          (r.sinCupo ? `, ${r.sinCupo} quedan en la bolsa por falta de tiempo` : ''),
      );
    });
  }

  async publicar(): Promise<void> {
    await this.trabajar(async () => {
      this.plan.set(await this.api.publicar(this.fecha(), this.idDelegacion()!));
      this.avisos.ok('Rutas publicadas: los motorizados ya las ven en el celular.');
    });
  }

  async publicarRuta(r: Ruta): Promise<void> {
    await this.trabajar(async () => {
      await this.api.publicarRuta(r.idRuta);
      await this.recargar();
    });
  }

  async recalcular(r: Ruta): Promise<void> {
    await this.trabajar(async () => {
      await this.api.recalcularRuta(r.idRuta);
      await this.recargar();
    });
  }

  async fijar(idParada: number, valor: boolean): Promise<void> {
    await this.trabajar(async () => {
      await this.api.fijar(idParada, valor);
      await this.recargar();
    });
  }

  // eslint-disable-next-line @typescript-eslint/no-explicit-any -- la bolsa usa data null y las rutas data Ruta
  async soltar(e: CdkDragDrop<any, any, Orden>): Promise<void> {
    const orden = e.item.data;
    const destino = e.container.data as Ruta | null;
    // La posición se cuenta entre los servicios pendientes (los atendidos quedan arriba).
    const atendidos = destino ? destino.paradas.filter((p) => p.estadoParada !== 'PENDIENTE').length : 0;
    const posicion = Math.max(0, e.currentIndex - atendidos);
    await this.trabajar(async () => {
      if (e.previousContainer === e.container) {
        if (!destino || e.previousIndex === e.currentIndex) return;
        const ids = destino.paradas.filter((p) => p.estadoParada === 'PENDIENTE').map((p) => p.orden.idOrden);
        ids.splice(ids.indexOf(orden.idOrden), 1);
        ids.splice(posicion, 0, orden.idOrden);
        await this.api.reordenar(destino.idRuta, ids);
      } else {
        await this.api.mover(orden.idOrden, destino?.idRuta ?? null, destino ? posicion : null, 'Cambio desde el tablero');
      }
      await this.recargar();
    });
  }

  async buscar(): Promise<void> {
    if (this.textoBusqueda.trim().length < 3) return;
    await this.trabajar(async () => this.encontradas.set(await this.api.buscarMaquinas(this.textoBusqueda)));
  }

  async crear(m: Maquina): Promise<void> {
    await this.trabajar(async () => {
      await this.api.crearOrden(m.idMaquina, this.tipoNuevo!, this.fecha(), 'Pedido por la oficina');
      this.avisos.ok(`Servicio agregado a la bolsa: ${m.punto}`);
      this.encontradas.set([]);
      this.agregando.set(false);
      await this.recargar();
    });
  }

  private async recargar(): Promise<void> {
    this.plan.set(await this.api.plan(this.fecha(), this.idDelegacion()!));
  }

  private async trabajar(fn: () => Promise<void>): Promise<void> {
    this.ocupado.set(true);
    try {
      await fn();
    } catch (e) {
      this.avisos.error(e);
      await this.recargar().catch(() => undefined);
    } finally {
      this.ocupado.set(false);
    }
  }
}
