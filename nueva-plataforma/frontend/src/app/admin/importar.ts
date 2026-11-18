import { Component, inject, OnInit, signal } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { MatTabsModule } from '@angular/material/tabs';
import { Tabla } from '../compartido/tabla';
import { Avisos } from '../core/infra';
import { Rest } from '../core/rest';

type Fila = any;

/**
 * Importaciones desde Excel (importarExcel, importarEstadoMaquina e importarMunicipios de PHP) y geocodificación de
 * puntos por dirección. Todas simulan primero y se confirman después.
 */
@Component({
  selector: 'app-importar',
  imports: [Tabla, MatButtonModule, MatIconModule, MatTabsModule, MatProgressBarModule],
  template: `
    <div class="pagina">
      <div class="encabezado"><h1>Importaciones</h1></div>
      @if (trabajando()) { <mat-progress-bar mode="indeterminate" /> }
      <mat-tab-group animationDuration="0">
        <mat-tab label="Base de máquinas de Prosegur">
          <section class="tarjeta">
            <p>Excel de la base de Prosegur: A código cliente, B cliente, C device id, D código 1, E código 2, F punto, I delegación, K tipo de máquina,
              AK dirección. Crea o actualiza clientes, puntos y máquinas, y <b>desactiva</b> las máquinas y puntos activos que no vengan en el archivo.</p>
            <input type="file" accept=".xlsx,.xls" (change)="archivoBase = $any($event.target).files[0]" />
            <button mat-stroked-button (click)="base(true)" [disabled]="!archivoBase">Simular</button>
            <button mat-flat-button (click)="base(false)" [disabled]="!archivoBase || !simuladoBase()">Importar</button>
            @if (resBase(); as r) {
              <p><b>{{ r.simulacion ? 'Simulación' : 'Importación' }}:</b> {{ r.filas }} filas · {{ r.nuevas }} nuevas · {{ r.actualizadas }} a actualizar
                @if (!r.simulacion) { · {{ r.maquinasDesactivadas }} máquinas y {{ r.puntosDesactivados }} puntos desactivados · {{ r.errores }} errores }</p>
              @for (m of r.mensajes; track $index) { <div class="error">{{ m }}</div> }
              <h3>Filas del archivo</h3>
              <app-tabla [filas]="r.detalle" [columnas]="[{ clave: 'fila', titulo: 'Fila', tipo: 'num' }, { clave: 'deviceId', titulo: 'Device ID' },
                { clave: 'accion', titulo: 'Acción' }, { clave: 'cliente', titulo: 'Cliente' }, { clave: 'punto', titulo: 'Punto' },
                { clave: 'clienteAnterior', titulo: 'Cliente actual' }, { clave: 'puntoAnterior', titulo: 'Punto actual' }, { clave: 'tipoMaquina', titulo: 'Tipo' }]" />
              @if (r.fantasmas.length) {
                <h3>Se desactivarán ({{ r.fantasmas.length }}): están activas y no vienen en el archivo</h3>
                <app-tabla [filas]="r.fantasmas" [columnas]="[{ clave: 'deviceId', titulo: 'Device ID' }, { clave: 'cliente', titulo: 'Cliente' }, { clave: 'punto', titulo: 'Punto' }]" />
              }
            }
          </section>
        </mat-tab>
        <mat-tab label="Máquinas fuera de servicio">
          <section class="tarjeta">
            <p>Excel con los device id en la columna A de las máquinas que Prosegur reporta fuera de servicio. No se programan hasta restaurarlas.</p>
            <input type="file" accept=".xlsx,.xls" (change)="archivoEstado = $any($event.target).files[0]" />
            <button mat-stroked-button (click)="estado(true)" [disabled]="!archivoEstado">Simular</button>
            <button mat-flat-button (click)="estado(false)" [disabled]="!archivoEstado || !simuladoEstado()">Marcar fuera de servicio</button>
            @if (resEstado(); as r) {
              @if (!r.simulacion) { <p>Se marcaron {{ r.marcadas }} máquinas.</p> }
              <app-tabla [filas]="r.detalle" [columnas]="[{ clave: 'deviceId', titulo: 'Device ID' }, { clave: 'accion', titulo: 'Acción' }, { clave: 'cliente', titulo: 'Cliente' },
                { clave: 'punto', titulo: 'Punto' }, { clave: 'tipoMaquina', titulo: 'Tipo' }, { clave: 'zona', titulo: 'Zona' }]" />
            }
            <h3>Fuera de servicio hoy ({{ fuera().length }})</h3>
            <button mat-stroked-button (click)="restaurarTodas()" [disabled]="!fuera().length">Restaurar todas a operativas</button>
            <table class="tabla"><tbody>@for (m of fuera(); track m['idMaquina']) {
              <tr><td>{{ m['deviceId'] }}</td><td>{{ m['cliente'] }}</td><td>{{ m['punto'] }}</td><td>{{ m['zona'] }}</td><td>{{ m['tipoMaquina'] }}</td>
                <td><button mat-button (click)="restaurar(m)">Restaurar</button></td></tr> }</tbody></table>
          </section>
        </mat-tab>
        <mat-tab label="Zonas de los puntos">
          <section class="tarjeta">
            <p>Excel de la programación: columna C device id y F zona. Actualiza la zona del punto de cada máquina.</p>
            <input type="file" accept=".xlsx,.xls" (change)="archivoZonas = $any($event.target).files[0]" />
            <button mat-stroked-button (click)="zonas(true)" [disabled]="!archivoZonas">Simular</button>
            <button mat-flat-button (click)="zonas(false)" [disabled]="!archivoZonas || !simuladoZonas()">Actualizar zonas</button>
            @if (resZonas(); as r) {
              @if (!r.simulacion) { <p>Se actualizaron {{ r.actualizados }} puntos.</p> }
              <app-tabla [filas]="r.detalle" [columnas]="[{ clave: 'deviceId', titulo: 'Device ID' }, { clave: 'punto', titulo: 'Punto' }, { clave: 'zonaActual', titulo: 'Zona actual' },
                { clave: 'zonaNueva', titulo: 'Zona nueva' }, { clave: 'accion', titulo: 'Acción' }]" />
            }
          </section>
        </mat-tab>
        <mat-tab label="Geocodificar puntos">
          <section class="tarjeta">
            <p>Busca con Google las coordenadas de los puntos que no tienen ubicación, a partir de su dirección (3 intentos por punto).
              Las máquinas sin ubicación toman la del punto. Para precisión exacta use «Ubicar máquinas» con el pin.</p>
            @if (geo(); as g) { <p>{{ g.total }} puntos sin coordenadas · {{ g.sinIntentos }} sin intentar.</p> }
            <button mat-flat-button (click)="geocodificar()">Geocodificar un lote de 25</button>
            @if (resGeo(); as r) { <p>Procesados {{ r.procesados }} · ubicados {{ r.ubicados }} · sin resultado {{ r.fallidos }}</p> }
          </section>
        </mat-tab>
      </mat-tab-group>
    </div>
  `,
  styles: `section { margin-top: 12px; } section > button { margin-left: 8px; } .error { color: var(--inees-rojo); font-size: 13px; } h3 { margin: 16px 0 8px; }`,
})
export class Importar implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected archivoBase?: File;
  protected archivoEstado?: File;
  protected archivoZonas?: File;
  protected readonly trabajando = signal(false);
  protected readonly resBase = signal<Fila | null>(null);
  protected readonly resEstado = signal<Fila | null>(null);
  protected readonly resZonas = signal<Fila | null>(null);
  protected readonly simuladoBase = signal(false);
  protected readonly simuladoEstado = signal(false);
  protected readonly simuladoZonas = signal(false);
  protected readonly fuera = signal<Fila[]>([]);
  protected readonly geo = signal<{ total: number; sinIntentos: number } | null>(null);
  protected readonly resGeo = signal<Fila | null>(null);

  ngOnInit(): void {
    this.cargarFuera();
    this.rest.get<{ total: number; sinIntentos: number }>('/api/geo/pendientes').then((g) => this.geo.set(g)).catch(() => {});
  }

  async base(simular: boolean): Promise<void> {
    if (!simular && !confirm('Se actualizará la base y se desactivarán las máquinas que no vienen en el archivo. ¿Continuar?')) return;
    await this.hacer(async () => {
      this.resBase.set(await this.rest.enviar('/api/importar/base-prosegur', { archivo: this.archivoBase }, { simular }));
      this.simuladoBase.set(simular);
    });
  }

  async estado(simular: boolean): Promise<void> {
    await this.hacer(async () => {
      this.resEstado.set(await this.rest.enviar('/api/importar/estado-maquinas', { archivo: this.archivoEstado }, { simular }));
      this.simuladoEstado.set(simular);
      if (!simular) await this.cargarFuera();
    });
  }

  async zonas(simular: boolean): Promise<void> {
    await this.hacer(async () => {
      this.resZonas.set(await this.rest.enviar('/api/importar/zonas', { archivo: this.archivoZonas }, { simular }));
      this.simuladoZonas.set(simular);
    });
  }

  async cargarFuera(): Promise<void> {
    try {
      this.fuera.set(await this.rest.get<Fila[]>('/api/importar/fuera-de-servicio'));
    } catch {
      /* sin permiso o sin datos */
    }
  }

  async restaurar(m: Fila): Promise<void> {
    await this.hacer(async () => {
      await this.rest.post(`/api/importar/fuera-de-servicio/${m['idMaquina']}/restaurar`);
      await this.cargarFuera();
    });
  }

  async restaurarTodas(): Promise<void> {
    if (!confirm(`¿Restaurar las ${this.fuera().length} máquinas a operativas?`)) return;
    await this.hacer(async () => {
      await this.rest.post('/api/importar/fuera-de-servicio/restaurar', this.fuera().map((m) => m['idMaquina']));
      await this.cargarFuera();
    });
  }

  async geocodificar(): Promise<void> {
    await this.hacer(async () => {
      this.resGeo.set(await this.rest.post('/api/geo/geocodificar', {}, { lote: 25 }));
      this.geo.set(await this.rest.get('/api/geo/pendientes'));
    });
  }

  private async hacer(f: () => Promise<void>): Promise<void> {
    this.trabajando.set(true);
    try {
      await f();
    } catch (e) {
      this.avisos.error(e);
    } finally {
      this.trabajando.set(false);
    }
  }
}
