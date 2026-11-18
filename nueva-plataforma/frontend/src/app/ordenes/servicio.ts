import { DatePipe } from '@angular/common';
import { Component, inject, input, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatTabsModule } from '@angular/material/tabs';
import { MatTooltipModule } from '@angular/material/tooltip';
import { Router } from '@angular/router';
import { Selector } from '../compartido/selector';
import { Avisos } from '../core/infra';
import { Opcion, pesos, Rest } from '../core/rest';
import { ESTADOS_ORDEN, RepuestoServicio, Servicio as ServicioModelo } from './modelo';

interface Complemento {
  soporteRemoto: string | null;
  numeroMaquina: string | null;
  serialMaquina: string | null;
  serialRouter: string | null;
  serialUps: string | null;
  pendientes: string | null;
  administrador: string | null;
  celular: string | null;
  idEstadoInicial: number | null;
  detalleEstadoInicial: string | null;
}

interface Evidencia {
  idEvidencia: number;
  tipo: string;
  ruta: string;
  fecha: string;
  url?: string;
}

interface HistorialFila {
  campo: string;
  valorAnterior: string | null;
  valorNuevo: string | null;
  motivo: string | null;
  usuario: string;
  fecha: string;
}

const TIPOS_FOTO = ['antes', 'componentes', 'despues', 'remision', 'firma', 'llegada', 'seriales', 'estado_inicial', 'trabajo',
  'repuestos', 'estado_final', 'novedad'];

/**
 * Ficha de un servicio: datos, valor y viáticos (los recalcula el servidor), repuestos con su efecto en el stock,
 * novedades, datos complementarios, fotos, historial de cambios, PDF y anulación.
 */
@Component({
  selector: 'app-servicio',
  imports: [FormsModule, DatePipe, Selector, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule, MatSelectModule,
    MatTabsModule, MatCheckboxModule, MatTooltipModule],
  template: `
    <div class="pagina">
      @if (s(); as s) {
        <div class="encabezado">
          <button mat-icon-button (click)="volver()" aria-label="Volver"><mat-icon>arrow_back</mat-icon></button>
          <h1>Servicio #{{ s.idOrden }} · Remisión {{ s.numeroRemision ?? '—' }}</h1>
          <span class="chip" [class.error]="s.estado === 9" [class.ok]="s.estado === 1">{{ estados[s.estado] ?? s.estado }}</span>
          <button mat-stroked-button (click)="pdf()"><mat-icon>picture_as_pdf</mat-icon> PDF</button>
          @if (s.estado !== 9) {
            <button mat-stroked-button class="peligro" (click)="anular()"><mat-icon>block</mat-icon> Anular</button>
          }
        </div>
        <p class="muted">{{ s.cliente }} · {{ s.punto }} · {{ s.delegacion }} — valor {{ pesos(s.valorServicio) }}
          @if (s.valorViaticos) { + viáticos {{ pesos(s.valorViaticos) }} } · origen {{ s.origen }}</p>

        <mat-tab-group animationDuration="0">
          <mat-tab label="Datos del servicio">
            <div class="tarjeta">
              <div class="rejilla">
                <mat-form-field appearance="outline"><mat-label>Fecha</mat-label><input matInput type="date" [(ngModel)]="d.fechaVisita" /></mat-form-field>
                <app-selector lista="clientes" etiqueta="Cliente" [(valor)]="idCliente" [nombreInicial]="s.cliente" />
                <app-selector lista="puntos" etiqueta="Punto" [padre]="idCliente" [(valor)]="d.idPunto" [nombreInicial]="s.punto" />
                <app-selector lista="maquinas" etiqueta="Máquina" [padre]="d.idPunto" [(valor)]="d.idMaquina" [nombreInicial]="s.deviceId" />
                <app-selector lista="tecnicos" etiqueta="Técnico" [(valor)]="d.idTecnico" [nombreInicial]="s.tecnico" />
                <mat-form-field appearance="outline"><mat-label>Tipo de servicio</mat-label>
                  <mat-select [(ngModel)]="d.idTipoMantenimiento">@for (o of tipos(); track o.id) { <mat-option [value]="o.id">{{ o.nombre }}</mat-option> }</mat-select></mat-form-field>
                <mat-form-field appearance="outline"><mat-label>Modalidad</mat-label>
                  <mat-select [(ngModel)]="d.idModalidad">@for (o of modalidades(); track o.id) { <mat-option [value]="o.id">{{ o.nombre }}</mat-option> }</mat-select></mat-form-field>
                <mat-form-field appearance="outline"><mat-label>Remisión</mat-label><input matInput [(ngModel)]="d.numeroRemision" (blur)="verificarRemision()" />
                  @if (avisoRemision()) { <mat-hint [class.error]="!remisionOk()">{{ avisoRemision() }}</mat-hint> }</mat-form-field>
                <mat-form-field appearance="outline"><mat-label>Hora de entrada</mat-label><input matInput type="time" [(ngModel)]="d.horaEntrada" /></mat-form-field>
                <mat-form-field appearance="outline"><mat-label>Hora de salida</mat-label><input matInput type="time" [(ngModel)]="d.horaSalida" /></mat-form-field>
                <mat-form-field appearance="outline"><mat-label>Estado final de la máquina</mat-label>
                  <mat-select [(ngModel)]="d.idEstadoMaquina">@for (o of estadosMaquina(); track o.id) { <mat-option [value]="o.id">{{ o.nombre }}</mat-option> }</mat-select></mat-form-field>
                <mat-form-field appearance="outline"><mat-label>Calificación</mat-label>
                  <mat-select [(ngModel)]="d.idCalificacion">@for (o of calificaciones(); track o.id) { <mat-option [value]="o.id">{{ o.nombre }}</mat-option> }</mat-select></mat-form-field>
                <mat-form-field appearance="outline"><mat-label>Valor manual (solo si no hay tarifa)</mat-label>
                  <input matInput type="number" [(ngModel)]="d.valorManual" /></mat-form-field>
                <mat-form-field appearance="outline" class="completo"><mat-label>Actividades realizadas</mat-label>
                  <textarea matInput rows="5" [(ngModel)]="d.actividades"></textarea>
                  <button mat-button matSuffix (click)="mejorarTexto()" [disabled]="trabajando()" matTooltip="Mejorar la redacción con IA">
                    <mat-icon>auto_fix_high</mat-icon> IA</button></mat-form-field>
                <mat-form-field appearance="outline" class="completo"><mat-label>Motivo de la corrección</mat-label>
                  <input matInput [(ngModel)]="motivo" placeholder="Queda en el historial del servicio" /></mat-form-field>
              </div>
              <div class="acciones"><button mat-flat-button (click)="guardar()" [disabled]="trabajando()">Guardar cambios</button></div>
            </div>
          </mat-tab>

          <mat-tab label="Repuestos ({{ s.repuestos.length }})">
            <div class="tarjeta">
              <table class="tabla">
                <thead><tr><th>Repuesto</th><th>Código</th><th>Origen</th><th class="num">Cantidad</th><th></th></tr></thead>
                <tbody>
                  @for (r of s.repuestos; track r.idRepuesto + r.origen) {
                    <tr><td>{{ r.nombre }}</td><td>{{ r.codigo }}</td><td>{{ r.origen }}</td><td class="num">{{ r.cantidad }}</td>
                      <td><button mat-icon-button (click)="quitarRepuesto(r)" matTooltip="Quitar (si es INEES vuelve al stock del técnico)"><mat-icon>delete</mat-icon></button></td></tr>
                  } @empty { <tr><td colspan="5" class="vacio">Sin repuestos</td></tr> }
                </tbody>
              </table>
              @if (s.repuestosLegado && !s.repuestos.length) { <p class="muted">Registro anterior: {{ s.repuestosLegado }}</p> }
              <h3>Agregar repuesto</h3>
              <div class="filtros">
                <app-selector lista="repuestos" etiqueta="Repuesto" [(valor)]="nuevoRepuesto.idRepuesto" />
                <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Origen</mat-label>
                  <mat-select [(ngModel)]="nuevoRepuesto.origen"><mat-option value="INEES">Stock del técnico (INEES)</mat-option>
                    <mat-option value="PROSEGUR">Prosegur</mat-option></mat-select></mat-form-field>
                <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Cantidad</mat-label>
                  <input matInput type="number" min="1" [(ngModel)]="nuevoRepuesto.cantidad" /></mat-form-field>
                <button mat-flat-button (click)="agregarRepuesto()" [disabled]="!nuevoRepuesto.idRepuesto">Agregar</button>
              </div>
            </div>
          </mat-tab>

          <mat-tab label="Novedades">
            <div class="tarjeta">
              <div class="novedades">
                @for (n of tiposNovedad(); track n.id) {
                  <mat-checkbox [checked]="novedades().has(n.id)" (change)="alternarNovedad(n.id)">{{ n.nombre }}</mat-checkbox>
                }
              </div>
              <mat-form-field appearance="outline" class="ancho-total"><mat-label>Detalle de la novedad</mat-label>
                <textarea matInput rows="3" [(ngModel)]="detalleNovedad"></textarea></mat-form-field>
              <div class="acciones"><button mat-flat-button (click)="guardarNovedades()">Guardar novedades</button></div>
            </div>
          </mat-tab>

          <mat-tab label="Datos complementarios">
            @if (comp(); as c) {
              <div class="tarjeta">
                <div class="rejilla">
                  <mat-form-field appearance="outline"><mat-label>N° de máquina</mat-label><input matInput [(ngModel)]="c.numeroMaquina" /></mat-form-field>
                  <mat-form-field appearance="outline"><mat-label>Serial máquina</mat-label><input matInput [(ngModel)]="c.serialMaquina" /></mat-form-field>
                  <mat-form-field appearance="outline"><mat-label>Serial router</mat-label><input matInput [(ngModel)]="c.serialRouter" /></mat-form-field>
                  <mat-form-field appearance="outline"><mat-label>Serial UPS</mat-label><input matInput [(ngModel)]="c.serialUps" /></mat-form-field>
                  <mat-form-field appearance="outline"><mat-label>Administrador del punto</mat-label><input matInput [(ngModel)]="c.administrador" /></mat-form-field>
                  <mat-form-field appearance="outline"><mat-label>Celular</mat-label><input matInput [(ngModel)]="c.celular" /></mat-form-field>
                  <mat-form-field appearance="outline"><mat-label>Soporte remoto</mat-label><input matInput [(ngModel)]="c.soporteRemoto" /></mat-form-field>
                  <mat-form-field appearance="outline"><mat-label>Estado inicial</mat-label>
                    <mat-select [(ngModel)]="c.idEstadoInicial">@for (o of estadosMaquina(); track o.id) { <mat-option [value]="o.id">{{ o.nombre }}</mat-option> }</mat-select></mat-form-field>
                  <mat-form-field appearance="outline" class="completo"><mat-label>Cómo encontró la máquina</mat-label>
                    <textarea matInput rows="2" [(ngModel)]="c.detalleEstadoInicial"></textarea></mat-form-field>
                  <mat-form-field appearance="outline" class="completo"><mat-label>Pendientes</mat-label>
                    <textarea matInput rows="2" [(ngModel)]="c.pendientes"></textarea></mat-form-field>
                </div>
                <div class="acciones"><button mat-flat-button (click)="guardarComplemento()">Guardar</button></div>
              </div>
            }
          </mat-tab>

          <mat-tab label="Fotos ({{ evidencias().length }})">
            <div class="tarjeta">
              <div class="galeria">
                @for (e of evidencias(); track e.idEvidencia) {
                  <figure>
                    @if (e.url) { <a [href]="e.url" target="_blank"><img [src]="e.url" [alt]="e.tipo" /></a> } @else { <div class="sin">Sin archivo</div> }
                    <figcaption>{{ e.tipo }} <button mat-icon-button (click)="eliminarFoto(e)" aria-label="Eliminar"><mat-icon>delete</mat-icon></button></figcaption>
                  </figure>
                } @empty { <p class="vacio">Sin fotos</p> }
              </div>
              <div class="filtros">
                <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Tipo de foto</mat-label>
                  <mat-select [(ngModel)]="tipoFoto">@for (t of tiposFoto; track t) { <mat-option [value]="t">{{ t }}</mat-option> }</mat-select></mat-form-field>
                <input type="file" accept="image/*" (change)="subirFoto($event)" />
              </div>
            </div>
          </mat-tab>

          <mat-tab label="Historial">
            <div class="tarjeta">
              <table class="tabla">
                <thead><tr><th>Fecha</th><th>Usuario</th><th>Campo</th><th>Antes</th><th>Después</th><th>Motivo</th></tr></thead>
                <tbody>
                  @for (h of historial(); track $index) {
                    <tr><td>{{ h.fecha | date: 'dd/MM/yy HH:mm' }}</td><td>{{ h.usuario }}</td><td>{{ h.campo }}</td><td>{{ h.valorAnterior }}</td>
                      <td>{{ h.valorNuevo }}</td><td>{{ h.motivo }}</td></tr>
                  } @empty { <tr><td colspan="6" class="vacio">Sin cambios registrados</td></tr> }
                </tbody>
              </table>
            </div>
          </mat-tab>
        </mat-tab-group>
      }
    </div>
  `,
  styles: `
    .tarjeta { margin-top: 12px; }
    .novedades { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 4px; margin-bottom: 12px; }
    .ancho-total { width: 100%; }
    .galeria { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 12px; }
    figure { margin: 0; width: 160px; }
    figure img { width: 160px; height: 120px; object-fit: cover; border-radius: 8px; border: 1px solid var(--linea); }
    figcaption { display: flex; align-items: center; justify-content: space-between; font-size: 12px; }
    .sin { width: 160px; height: 120px; display: grid; place-items: center; background: var(--inees-fondo); border-radius: 8px; color: var(--inees-gris); }
    .peligro { color: var(--inees-rojo); }
    .error { color: var(--inees-rojo); }
  `,
})
export class Servicio implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  private readonly router = inject(Router);
  readonly id = input.required<string>();
  protected readonly estados = ESTADOS_ORDEN;
  protected readonly pesos = pesos;
  protected readonly tiposFoto = TIPOS_FOTO;
  protected readonly s = signal<ServicioModelo | null>(null);
  protected readonly comp = signal<Complemento | null>(null);
  protected readonly evidencias = signal<Evidencia[]>([]);
  protected readonly historial = signal<HistorialFila[]>([]);
  protected readonly tipos = signal<Opcion[]>([]);
  protected readonly modalidades = signal<Opcion[]>([]);
  protected readonly estadosMaquina = signal<Opcion[]>([]);
  protected readonly calificaciones = signal<Opcion[]>([]);
  protected readonly tiposNovedad = signal<Opcion[]>([]);
  protected readonly novedades = signal(new Set<number>());
  protected readonly trabajando = signal(false);
  protected readonly avisoRemision = signal<string | null>(null);
  protected readonly remisionOk = signal(true);
  protected d: any = {};
  protected idCliente: number | null = null;
  protected motivo = '';
  protected detalleNovedad = '';
  protected tipoFoto = 'antes';
  protected nuevoRepuesto: { idRepuesto: number | null; origen: string; cantidad: number } = { idRepuesto: null, origen: 'INEES', cantidad: 1 };

  async ngOnInit(): Promise<void> {
    const [tipos, modalidades, estados, calif, nov] = await Promise.all([
      this.rest.lista('tipos-mantenimiento'), this.rest.lista('modalidades'), this.rest.lista('estados-maquina'),
      this.rest.lista('calificaciones'), this.rest.lista('tipos-novedad')]);
    this.tipos.set(tipos);
    this.modalidades.set(modalidades);
    this.estadosMaquina.set(estados);
    this.calificaciones.set(calif);
    this.tiposNovedad.set(nov);
    await this.cargar();
  }

  private async cargar(): Promise<void> {
    try {
      const id = Number(this.id());
      const s = await this.rest.get<ServicioModelo>(`/api/ordenes/${id}`);
      this.s.set(s);
      this.idCliente = s.idCliente;
      this.d = {
        fechaVisita: s.fechaVisita, idPunto: s.idPunto, idMaquina: s.idMaquina, idTecnico: s.idTecnico,
        idTipoMantenimiento: s.idTipoMantenimiento, idModalidad: s.idModalidad, numeroRemision: s.numeroRemision,
        horaEntrada: s.horaEntrada?.substring(0, 5), horaSalida: s.horaSalida?.substring(0, 5), idEstadoMaquina: s.idEstadoMaquina,
        idCalificacion: s.idCalificacion, actividades: s.actividades, valorManual: null,
      };
      this.novedades.set(new Set((s.idsNovedades ?? '').split(',').filter(Boolean).map(Number)));
      this.detalleNovedad = s.detalleNovedad ?? '';
      this.comp.set(await this.rest.get<Complemento>(`/api/ordenes/${id}/complemento`));
      this.historial.set(await this.rest.get<HistorialFila[]>(`/api/ordenes/${id}/historial`));
      const ev = await this.rest.get<Evidencia[]>(`/api/ordenes/${id}/evidencias`);
      this.evidencias.set(ev);
      for (const e of ev) {
        this.rest.imagen(e.ruta).then((url) => this.evidencias.update((l) => l.map((x) => (x.idEvidencia === e.idEvidencia ? { ...x, url } : x)))).catch(() => {});
      }
    } catch (e) {
      this.avisos.error(e);
    }
  }

  protected volver(): void {
    history.length > 1 ? history.back() : this.router.navigateByUrl('/ordenes');
  }

  async verificarRemision(): Promise<void> {
    const n = (this.d['numeroRemision'] ?? '').trim();
    if (!n || !this.d['idTecnico']) return this.avisoRemision.set(null);
    try {
      const r = await this.rest.get<{ disponible: boolean; mensaje: string }>('/api/ordenes/remision', { numero: n, idTecnico: this.d['idTecnico'], idOrden: this.s()?.idOrden });
      this.remisionOk.set(r.disponible);
      this.avisoRemision.set(r.mensaje);
    } catch {
      this.avisoRemision.set(null);
    }
  }

  async guardar(): Promise<void> {
    await this.hacer(async () => {
      const datos = { ...this.d, horaEntrada: this.d['horaEntrada'] || null, horaSalida: this.d['horaSalida'] || null, valorManual: this.d['valorManual'] || null };
      this.s.set(await this.rest.put<ServicioModelo>(`/api/ordenes/${this.s()!.idOrden}`, { datos, motivo: this.motivo }));
      this.avisos.ok('Servicio actualizado. Valor y viáticos recalculados.');
      this.motivo = '';
      await this.cargar();
    });
  }

  async mejorarTexto(): Promise<void> {
    if (!this.d['actividades']?.trim()) return;
    await this.hacer(async () => {
      const r = await this.rest.post<{ textoMejorado: string }>('/api/ia/mejorar-texto', { texto: this.d['actividades'] });
      this.d['actividades'] = r.textoMejorado;
      this.avisos.ok('Texto mejorado. Revíselo antes de guardar.');
    });
  }

  async agregarRepuesto(): Promise<void> {
    await this.hacer(async () => {
      const lista = await this.rest.post<RepuestoServicio[]>(`/api/ordenes/${this.s()!.idOrden}/repuestos`, this.nuevoRepuesto);
      this.s.update((s) => (s ? { ...s, repuestos: lista } : s));
      this.nuevoRepuesto = { idRepuesto: null, origen: 'INEES', cantidad: 1 };
    });
  }

  async quitarRepuesto(r: RepuestoServicio): Promise<void> {
    if (!confirm(`¿Quitar ${r.nombre} del servicio?`)) return;
    await this.hacer(async () => {
      const lista = await this.rest.delete<RepuestoServicio[]>(`/api/ordenes/${this.s()!.idOrden}/repuestos/${r.idRepuesto}`, { origen: r.origen });
      this.s.update((s) => (s ? { ...s, repuestos: lista } : s));
    });
  }

  protected alternarNovedad(id: number): void {
    this.novedades.update((n) => {
      const c = new Set(n);
      c.has(id) ? c.delete(id) : c.add(id);
      return c;
    });
  }

  async guardarNovedades(): Promise<void> {
    await this.hacer(async () => {
      await this.rest.put(`/api/ordenes/${this.s()!.idOrden}/novedades`, { ids: [...this.novedades()], detalle: this.detalleNovedad });
      this.avisos.ok('Novedades guardadas.');
    });
  }

  async guardarComplemento(): Promise<void> {
    await this.hacer(async () => {
      this.comp.set(await this.rest.put<Complemento>(`/api/ordenes/${this.s()!.idOrden}/complemento`, this.comp()));
      this.avisos.ok('Datos complementarios guardados.');
    });
  }

  async subirFoto(ev: Event): Promise<void> {
    const archivo = (ev.target as HTMLInputElement).files?.[0];
    if (!archivo) return;
    await this.hacer(async () => {
      await this.rest.enviar(`/api/ordenes/${this.s()!.idOrden}/evidencias`, { archivo }, { tipo: this.tipoFoto });
      (ev.target as HTMLInputElement).value = '';
      await this.cargar();
    });
  }

  async eliminarFoto(e: Evidencia): Promise<void> {
    if (!confirm('¿Eliminar esta foto?')) return;
    await this.hacer(async () => {
      await this.rest.delete(`/api/ordenes/${this.s()!.idOrden}/evidencias/${e.idEvidencia}`);
      this.evidencias.update((l) => l.filter((x) => x.idEvidencia !== e.idEvidencia));
    });
  }

  async pdf(): Promise<void> {
    await this.hacer(() => this.rest.descargar(`/api/ordenes/${this.s()!.idOrden}/pdf`));
  }

  async anular(): Promise<void> {
    const motivo = prompt('Motivo de la anulación (la remisión vuelve a quedar disponible y los repuestos INEES vuelven al stock):');
    if (!motivo?.trim()) return;
    await this.hacer(async () => {
      await this.rest.post(`/api/ordenes/${this.s()!.idOrden}/anular`, { motivo });
      this.avisos.ok('Servicio anulado.');
      await this.cargar();
    });
  }

  private async hacer(f: () => Promise<unknown>): Promise<void> {
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
