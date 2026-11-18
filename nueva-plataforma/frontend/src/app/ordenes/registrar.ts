import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatTooltipModule } from '@angular/material/tooltip';
import { Selector } from '../compartido/selector';
import { Avisos, hoyIso } from '../core/infra';
import { Opcion, pesos, Rest } from '../core/rest';

interface FilaRepuesto {
  idRepuesto: number | null;
  origen: 'INEES' | 'PROSEGUR';
  cantidad: number;
}

interface Fila {
  idCliente: number | null;
  idPunto: number | null;
  idMaquina: number | null;
  idTecnico: number | null;
  idTipoMantenimiento: number | null;
  numeroRemision: string | null;
  horaEntrada: string | null;
  horaSalida: string | null;
  idEstadoMaquina: number | null;
  idCalificacion: number | null;
  actividades: string;
  repuestos: FilaRepuesto[];
  remisiones: string[];
  precio: string | null;
  verRepuestos: boolean;
}

const LOCAL = 'inees.registrar.borrador';

/**
 * Registro manual de servicios de un día (ordenCrear de PHP): una fila por servicio. El precio y los viáticos los
 * calcula el servidor; los repuestos de INEES se descuentan del stock del técnico. El borrador se guarda en el navegador.
 */
@Component({
  selector: 'app-registrar',
  imports: [FormsModule, Selector, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule, MatSelectModule, MatTooltipModule],
  template: `
    <div class="pagina">
      <div class="encabezado">
        <h1>Registrar servicios</h1>
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Fecha de los servicios</mat-label>
          <input matInput type="date" [(ngModel)]="fecha" (change)="guardarBorrador()" /></mat-form-field>
        <button mat-stroked-button (click)="agregar()"><mat-icon>add</mat-icon> Fila</button>
        <button mat-flat-button (click)="guardar()" [disabled]="guardando() || !filas().length">Guardar {{ filas().length }} servicios</button>
      </div>
      @if (errores().length) {
        <div class="tarjeta errores">@for (e of errores(); track e) { <div>{{ e }}</div> }</div>
      }
      @for (f of filas(); track $index; let i = $index) {
        <section class="tarjeta fila">
          <div class="cab"><strong>Servicio {{ i + 1 }}</strong>
            @if (f.precio) { <span class="chip ok">{{ f.precio }}</span> }
            <span class="crece"></span>
            <button mat-icon-button (click)="quitar(i)" aria-label="Quitar fila"><mat-icon>close</mat-icon></button></div>
          <div class="rejilla">
            <app-selector lista="clientes" etiqueta="Cliente" [(valor)]="f.idCliente" (valorChange)="cambio()" />
            <app-selector lista="puntos" etiqueta="Punto" [padre]="f.idCliente" [(valor)]="f.idPunto" (valorChange)="cambio()" />
            <app-selector lista="maquinas" etiqueta="Máquina" [padre]="f.idPunto" [(valor)]="f.idMaquina" (valorChange)="precio(f)" />
            <app-selector lista="tecnicos" etiqueta="Técnico" [(valor)]="f.idTecnico" (valorChange)="remisiones(f)" />
            <mat-form-field appearance="outline"><mat-label>Tipo de servicio</mat-label>
              <mat-select [(ngModel)]="f.idTipoMantenimiento" (selectionChange)="precio(f)">
                @for (o of tipos(); track o.id) { <mat-option [value]="o.id">{{ o.nombre }}</mat-option> }</mat-select></mat-form-field>
            <mat-form-field appearance="outline"><mat-label>Remisión</mat-label>
              <mat-select [(ngModel)]="f.numeroRemision" (selectionChange)="cambio()">
                <mat-option [value]="null">Sin remisión</mat-option>
                @for (r of f.remisiones; track r) { <mat-option [value]="r">{{ r }}</mat-option> }</mat-select>
              <mat-hint>Disponibles del técnico</mat-hint></mat-form-field>
            <mat-form-field appearance="outline"><mat-label>Entrada</mat-label><input matInput type="time" [(ngModel)]="f.horaEntrada" (change)="cambio()" /></mat-form-field>
            <mat-form-field appearance="outline"><mat-label>Salida</mat-label><input matInput type="time" [(ngModel)]="f.horaSalida" (change)="cambio()" /></mat-form-field>
            <mat-form-field appearance="outline"><mat-label>Estado de la máquina</mat-label>
              <mat-select [(ngModel)]="f.idEstadoMaquina" (selectionChange)="cambio()">@for (o of estados(); track o.id) { <mat-option [value]="o.id">{{ o.nombre }}</mat-option> }</mat-select></mat-form-field>
            <mat-form-field appearance="outline"><mat-label>Calificación</mat-label>
              <mat-select [(ngModel)]="f.idCalificacion" (selectionChange)="cambio()">@for (o of calificaciones(); track o.id) { <mat-option [value]="o.id">{{ o.nombre }}</mat-option> }</mat-select></mat-form-field>
            <mat-form-field appearance="outline" class="completo"><mat-label>Actividades realizadas</mat-label>
              <textarea matInput rows="2" [(ngModel)]="f.actividades" (change)="cambio()"></textarea></mat-form-field>
          </div>
          <button mat-button (click)="f.verRepuestos = !f.verRepuestos"><mat-icon>build</mat-icon> Repuestos ({{ f.repuestos.length }})</button>
          @if (f.verRepuestos) {
            @for (r of f.repuestos; track $index; let j = $index) {
              <div class="filtros">
                <app-selector lista="repuestos" etiqueta="Repuesto" [(valor)]="r.idRepuesto" (valorChange)="cambio()" />
                <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Origen</mat-label>
                  <mat-select [(ngModel)]="r.origen" (selectionChange)="cambio()"><mat-option value="INEES">Stock del técnico</mat-option>
                    <mat-option value="PROSEGUR">Prosegur</mat-option></mat-select></mat-form-field>
                <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Cantidad</mat-label>
                  <input matInput type="number" min="1" [(ngModel)]="r.cantidad" (change)="cambio()" /></mat-form-field>
                <button mat-icon-button (click)="f.repuestos.splice(j, 1); cambio()" aria-label="Quitar repuesto"><mat-icon>delete</mat-icon></button>
              </div>
            }
            <button mat-stroked-button (click)="f.repuestos.push({ idRepuesto: null, origen: 'INEES', cantidad: 1 })">Agregar repuesto</button>
          }
        </section>
      } @empty {
        <div class="tarjeta vacio">Agregue una fila por cada servicio realizado.</div>
      }
    </div>
  `,
  styles: `
    .fila { margin-bottom: 12px; }
    .cab { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
    .crece { flex: 1; }
    .errores { background: var(--error-fondo); color: var(--inees-rojo); margin-bottom: 12px; }
  `,
})
export class Registrar implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected fecha = hoyIso(-1);
  protected readonly filas = signal<Fila[]>([]);
  protected readonly tipos = signal<Opcion[]>([]);
  protected readonly estados = signal<Opcion[]>([]);
  protected readonly calificaciones = signal<Opcion[]>([]);
  protected readonly guardando = signal(false);
  protected readonly errores = signal<string[]>([]);

  async ngOnInit(): Promise<void> {
    const [t, e, c] = await Promise.all([this.rest.lista('tipos-mantenimiento'), this.rest.lista('estados-maquina'), this.rest.lista('calificaciones')]);
    this.tipos.set(t);
    this.estados.set(e);
    this.calificaciones.set(c);
    try {
      const b = JSON.parse(localStorage.getItem(LOCAL) ?? 'null');
      if (b?.filas?.length) {
        this.fecha = b.fecha;
        this.filas.set(b.filas);
      }
    } catch {
      /* sin borrador */
    }
    if (!this.filas().length) this.agregar();
  }

  agregar(): void {
    const anterior = this.filas().at(-1);
    this.filas.update((l) => [...l, {
      idCliente: null, idPunto: null, idMaquina: null, idTecnico: anterior?.idTecnico ?? null, idTipoMantenimiento: 1,
      numeroRemision: null, horaEntrada: null, horaSalida: null, idEstadoMaquina: 1, idCalificacion: null, actividades: '',
      repuestos: [], remisiones: anterior?.remisiones ?? [], precio: null, verRepuestos: false,
    }]);
    this.guardarBorrador();
  }

  quitar(i: number): void {
    this.filas.update((l) => l.filter((_, k) => k !== i));
    this.guardarBorrador();
  }

  protected cambio(): void {
    this.guardarBorrador();
  }

  async remisiones(f: Fila): Promise<void> {
    f.numeroRemision = null;
    f.remisiones = f.idTecnico ? await this.rest.get<string[]>('/api/ordenes/remisiones-disponibles', { idTecnico: f.idTecnico }) : [];
    this.filas.update((l) => [...l]);
    this.precio(f);
  }

  async precio(f: Fila): Promise<void> {
    this.guardarBorrador();
    if (!f.idMaquina || !f.idPunto || !f.idTipoMantenimiento) return;
    try {
      const p = await this.rest.get<{ tarifa: number | null; valorViaticos: number }>('/api/ordenes/precio', {
        idMaquina: f.idMaquina, idPunto: f.idPunto, idTipoMantenimiento: f.idTipoMantenimiento, idTecnico: f.idTecnico, fecha: this.fecha });
      f.precio = p.tarifa === null ? 'Sin tarifa' : pesos(p.tarifa) + (p.valorViaticos ? ` + viáticos ${pesos(p.valorViaticos)}` : '');
      this.filas.update((l) => [...l]);
    } catch {
      /* el precio se calcula igual al guardar */
    }
  }

  guardarBorrador(): void {
    try {
      localStorage.setItem(LOCAL, JSON.stringify({ fecha: this.fecha, filas: this.filas() }));
    } catch {
      /* sin almacenamiento */
    }
  }

  async guardar(): Promise<void> {
    this.errores.set([]);
    this.guardando.set(true);
    try {
      const servicios = this.filas().map((f) => ({
        idPunto: f.idPunto, idMaquina: f.idMaquina, idTecnico: f.idTecnico, idTipoMantenimiento: f.idTipoMantenimiento,
        numeroRemision: f.numeroRemision, horaEntrada: f.horaEntrada || null, horaSalida: f.horaSalida || null,
        idEstadoMaquina: f.idEstadoMaquina, idCalificacion: f.idCalificacion, actividades: f.actividades,
        repuestos: f.repuestos.filter((r) => r.idRepuesto),
      }));
      const r = await this.rest.post<{ guardados: number; errores: string[] }>('/api/ordenes/lote', { fecha: this.fecha, servicios });
      if (r.errores.length) {
        this.errores.set(r.errores);
        // Quedan solo las filas que fallaron para corregirlas.
        const fallidas = new Set(r.errores.map((e) => Number(/^Fila (\d+)/.exec(e)?.[1]) - 1));
        this.filas.update((l) => l.filter((_, i) => fallidas.has(i)));
      } else {
        this.filas.set([]);
        this.agregar();
      }
      this.guardarBorrador();
      this.avisos.ok(`Se guardaron ${r.guardados} servicios${r.errores.length ? `; ${r.errores.length} con error` : ''}.`);
    } catch (e) {
      this.avisos.error(e);
    } finally {
      this.guardando.set(false);
    }
  }
}
