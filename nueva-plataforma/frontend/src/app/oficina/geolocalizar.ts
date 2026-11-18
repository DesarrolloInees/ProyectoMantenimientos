import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatSelectModule } from '@angular/material/select';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { Mapa } from '../compartido/mapa';
import { Api } from '../core/api';
import { Avisos } from '../core/infra';
import { Item, Maquina } from '../core/modelos';

/** El Supervisor o el Auxiliar pone el pin con la ubicación exacta de cada máquina que no la tiene. */
@Component({
  selector: 'app-geolocalizar',
  imports: [FormsModule, RouterLink, MatButtonModule, MatIconModule, MatFormFieldModule, MatSelectModule, Mapa],
  template: `
    <div class="pagina">
      <div class="encabezado">
        @if (idCarga) {
          <a mat-icon-button [routerLink]="['/cargas', idCarga]" aria-label="Volver a la carga"><mat-icon>arrow_back</mat-icon></a>
        }
        <h1>Ubicar máquinas</h1>
        @if (!idCarga) {
          <mat-form-field appearance="outline" subscriptSizing="dynamic">
            <mat-label>Delegación</mat-label>
            <mat-select [ngModel]="idDelegacion()" (ngModelChange)="idDelegacion.set($event); cargar()">
              @for (d of delegaciones(); track d.id) { <mat-option [value]="d.id">{{ d.nombre }}</mat-option> }
            </mat-select>
          </mat-form-field>
        }
      </div>
      <div class="cuerpo">
        <div class="tarjeta lista">
          <p class="muted">{{ maquinas().length }} sin ubicación. Elija una y haga clic en el mapa donde está la máquina.</p>
          @for (m of maquinas(); track m.idMaquina) {
            <button class="item" [class.activa]="actual()?.idMaquina === m.idMaquina" (click)="elegir(m)">
              <strong>{{ m.punto }}</strong>
              <span class="muted">{{ m.cliente }} · {{ m.deviceId }}</span>
              <span class="muted">{{ m.direccion }} · {{ m.municipio }}</span>
            </button>
          } @empty {
            <div class="vacio">
              <mat-icon class="ok">task_alt</mat-icon><br />Todas las máquinas tienen ubicación.
              @if (idCarga) { <br /><a mat-flat-button [routerLink]="['/cargas', idCarga]">Volver y confirmar la carga</a> }
            </div>
          }
        </div>
        <div class="derecha">
          @if (actual(); as m) {
            <div class="tarjeta barra">
              <div class="crece">
                <strong>{{ m.punto }}</strong> <span class="muted">· {{ m.direccion }}</span><br />
                @if (pin(); as p) {
                  <span class="muted">Pin: {{ p[0].toFixed(6) }}, {{ p[1].toFixed(6) }}</span>
                } @else {
                  <span class="muted">Haga clic en el mapa para poner el pin.</span>
                }
              </div>
              <a mat-button [href]="buscarEnMaps(m)" target="_blank" rel="noopener"><mat-icon>search</mat-icon> Buscar dirección</a>
              <button mat-flat-button (click)="guardar()" [disabled]="!pin() || guardando()"><mat-icon>save</mat-icon> Guardar ubicación</button>
            </div>
          }
          <app-mapa class="mapa" [pin]="pin()" [centro]="centro()" (clicMapa)="pin.set([$event.lat, $event.lon])" />
        </div>
      </div>
    </div>
  `,
  styles: `
    .cuerpo { display: grid; grid-template-columns: 340px minmax(0, 1fr); gap: 16px; }
    .lista { max-height: calc(100vh - 170px); overflow-y: auto; display: flex; flex-direction: column; gap: 6px; }
    .item {
      text-align: left; border: 1px solid var(--linea); background: #fff; border-radius: 8px; padding: 8px 10px; cursor: pointer;
      display: flex; flex-direction: column; gap: 2px; font: inherit;
    }
    .item.activa { border-color: var(--inees-azul); background: var(--info-fondo); }
    .derecha { display: flex; flex-direction: column; gap: 12px; }
    .barra { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
    .crece { flex: 1; min-width: 200px; }
    .mapa { height: calc(100vh - 250px); }
    .ok { color: var(--inees-verde); font-size: 40px; width: 40px; height: 40px; }
    @media (max-width: 900px) { .cuerpo { grid-template-columns: 1fr; } .lista { max-height: 260px; } .mapa { height: 420px; } }
  `,
})
export class Geolocalizar implements OnInit {
  private readonly api = inject(Api);
  private readonly avisos = inject(Avisos);
  private readonly ruta = inject(ActivatedRoute);

  protected idCarga: number | null = null;
  protected readonly idDelegacion = signal<number | null>(null);
  protected readonly delegaciones = signal<Item[]>([]);
  protected readonly maquinas = signal<Maquina[]>([]);
  protected readonly actual = signal<Maquina | null>(null);
  protected readonly pin = signal<[number, number] | null>(null);
  protected readonly guardando = signal(false);
  /** Centro sugerido: la ubicación aproximada del punto si existe. */
  protected readonly centro = computed<[number, number] | null>(() => {
    const m = this.actual();
    return m?.latitudReferencia != null ? [m.latitudReferencia, m.longitudReferencia!] : null;
  });

  async ngOnInit(): Promise<void> {
    const q = this.ruta.snapshot.queryParamMap;
    this.idCarga = q.get('carga') ? Number(q.get('carga')) : null;
    if (!this.idCarga) {
      const c = await this.api.catalogos();
      this.delegaciones.set(c.delegaciones);
      this.idDelegacion.set(q.get('delegacion') ? Number(q.get('delegacion')) : (c.delegaciones[0]?.id ?? null));
    }
    await this.cargar();
  }

  async cargar(): Promise<void> {
    const lista = await this.api.maquinasSinUbicacion(
      this.idCarga ? { idCarga: this.idCarga } : { idDelegacion: this.idDelegacion() ?? undefined },
    );
    this.maquinas.set(lista);
    this.elegir(lista[0] ?? null);
  }

  elegir(m: Maquina | null): void {
    this.actual.set(m);
    this.pin.set(null);
  }

  buscarEnMaps(m: Maquina): string {
    return 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(`${m.direccion ?? m.punto}, ${m.municipio ?? ''}, Colombia`);
  }

  async guardar(): Promise<void> {
    const m = this.actual();
    const p = this.pin();
    if (!m || !p) return;
    this.guardando.set(true);
    try {
      await this.api.ponerPin(m.idMaquina, p[0], p[1]);
      this.avisos.ok(`Ubicación guardada: ${m.punto}`);
      const resto = this.maquinas().filter((x) => x.idMaquina !== m.idMaquina);
      this.maquinas.set(resto);
      this.elegir(resto[0] ?? null);
    } catch (e) {
      this.avisos.error(e);
    } finally {
      this.guardando.set(false);
    }
  }
}
