import { DecimalPipe } from '@angular/common';
import { Component, computed, effect, inject, input, signal, untracked } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSlideToggleModule } from '@angular/material/slide-toggle';
import { MatTooltipModule } from '@angular/material/tooltip';
import { RouterLink } from '@angular/router';
import { Selector } from '../compartido/selector';
import { Avisos } from '../core/infra';
import { pesos, Rest } from '../core/rest';
import { Sesion } from '../core/sesion';

interface Campo {
  nombre: string;
  etiqueta: string;
  tipo: 'TEXTO' | 'TEXTO_LARGO' | 'ENTERO' | 'DECIMAL' | 'PESOS' | 'FECHA' | 'HORA' | 'BOOLEANO' | 'SELECCION' | 'EMAIL';
  requerido: boolean;
  lista: string | null;
  mostrar: string | null;
  max: number | null;
  enLista: boolean;
  soloCrear: boolean;
}

interface Entidad {
  id: string;
  titulo: string;
  singular: string;
  campos: Campo[];
  activo: string | null;
  permiteCrear: boolean;
  permiteBorrar: boolean;
}

type Fila = Record<string, any>;

/**
 * Pantalla genérica de catálogos (reemplaza las pantallas Ver/Crear/Editar/Eliminar de cada catálogo en PHP):
 * se arma con la definición que manda el servidor (/api/admin/{entidad}/meta).
 */
@Component({
  selector: 'app-crud',
  imports: [FormsModule, DecimalPipe, RouterLink, Selector, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule, MatCheckboxModule,
    MatSlideToggleModule, MatTooltipModule],
  template: `
    <div class="pagina">
      @if (meta(); as m) {
        <div class="encabezado">
          @if (sesion.tiene('ADMINISTRACION')) { <a mat-icon-button routerLink="/admin/catalogos" aria-label="Catálogos"><mat-icon>arrow_back</mat-icon></a> }
          <h1>{{ m.titulo }}</h1>
          @if (m.permiteCrear) { <button mat-flat-button (click)="nuevo()"><mat-icon>add</mat-icon> Nuevo {{ m.singular }}</button> }
        </div>
        @if (form(); as f) {
          <section class="tarjeta formulario">
            <h3>{{ f['id'] ? 'Editar ' + m.singular : 'Nuevo ' + m.singular }}</h3>
            <div class="rejilla">
              @for (c of m.campos; track c.nombre) {
                @switch (c.tipo) {
                  @case ('SELECCION') {
                    <app-selector [lista]="c.lista!" [etiqueta]="c.etiqueta" [(valor)]="f[c.nombre]" [nombreInicial]="c.mostrar ? f[c.mostrar] : null" [requerido]="c.requerido" />
                  }
                  @case ('BOOLEANO') { <mat-slide-toggle [(ngModel)]="f[c.nombre]">{{ c.etiqueta }}</mat-slide-toggle> }
                  @case ('TEXTO_LARGO') {
                    <mat-form-field appearance="outline" class="completo"><mat-label>{{ c.etiqueta }}</mat-label>
                      <textarea matInput rows="3" [(ngModel)]="f[c.nombre]" [required]="c.requerido"></textarea></mat-form-field>
                  }
                  @default {
                    <mat-form-field appearance="outline"><mat-label>{{ c.etiqueta }}</mat-label>
                      <input matInput [type]="tipoInput(c)" [(ngModel)]="f[c.nombre]" [required]="c.requerido" [attr.maxlength]="c.tipo === 'TEXTO' ? c.max : null"
                             [disabled]="c.soloCrear && !!f['id']" [step]="c.tipo === 'DECIMAL' ? 'any' : null" /></mat-form-field>
                  }
                }
              }
            </div>
            <div class="acciones">
              <button mat-button (click)="form.set(null)">Cancelar</button>
              <button mat-flat-button (click)="guardar()" [disabled]="guardando()">Guardar</button>
            </div>
          </section>
        }
        <div class="filtros">
          <mat-form-field appearance="outline" subscriptSizing="dynamic" class="ancho"><mat-label>Buscar</mat-label>
            <input matInput [(ngModel)]="texto" (keyup.enter)="cargar(0)" /></mat-form-field>
          @if (m.activo) { <mat-checkbox [(ngModel)]="inactivos" (change)="cargar(0)">Ver inactivos</mat-checkbox> }
          <button mat-stroked-button (click)="cargar(0)"><mat-icon>search</mat-icon> Buscar</button>
        </div>
        <div class="contenedor-tabla">
          <table class="tabla">
            <thead><tr><th>#</th>@for (c of columnas(); track c.nombre) { <th>{{ c.etiqueta }}</th> }@if (m.id === 'parametros') { <th>Valor</th> }<th></th></tr></thead>
            <tbody>
              @for (f of filas(); track f['id']) {
                <tr [class.inactivo]="m.activo && !f[m.activo]">
                  <td class="muted">{{ f['id'] }}</td>
                  @for (c of columnas(); track c.nombre) {
                    <td>
                      @switch (c.tipo) {
                        @case ('BOOLEANO') { {{ f[c.nombre] ? 'Sí' : 'No' }} }
                        @case ('SELECCION') { {{ c.mostrar ? f[c.mostrar] : f[c.nombre] }} }
                        @case ('PESOS') { {{ pesos(f[c.nombre]) }} }
                        @case ('DECIMAL') { {{ f[c.nombre] | number: '1.0-6' }} }
                        @default { {{ f[c.nombre] }} }
                      }
                    </td>
                  }
                  @if (m.id === 'parametros') { <td class="valor">{{ f['valor_lista'] }}</td> }
                  <td class="botones-fila">
                    <button mat-icon-button (click)="editar(f)" matTooltip="Editar"><mat-icon>edit</mat-icon></button>
                    @if (m.id === 'tecnicos') {
                      <label class="firma" [matTooltip]="f['ruta_firma'] ? 'Cambiar firma (va en el PDF del servicio)' : 'Cargar firma (va en el PDF del servicio)'">
                        <mat-icon [class.ok]="f['ruta_firma']">draw</mat-icon><input type="file" accept="image/*" hidden (change)="firma(f, $event)" /></label>
                    }
                    @if (m.activo && !f[m.activo]) {
                      <button mat-icon-button (click)="activar(f)" matTooltip="Reactivar"><mat-icon>restore</mat-icon></button>
                    } @else if (m.permiteBorrar) {
                      <button mat-icon-button (click)="eliminar(f)" [matTooltip]="m.activo ? 'Desactivar' : 'Eliminar'"><mat-icon>delete</mat-icon></button>
                    }
                  </td>
                </tr>
              } @empty { <tr><td [attr.colspan]="columnas().length + 2" class="vacio">Sin registros</td></tr> }
            </tbody>
          </table>
          <div class="paginador">
            {{ total() ? pagina * 50 + 1 : 0 }}–{{ pagina * 50 + filas().length }} de {{ total() }}
            <button mat-icon-button [disabled]="pagina === 0" (click)="cargar(pagina - 1)" aria-label="Anterior"><mat-icon>chevron_left</mat-icon></button>
            <button mat-icon-button [disabled]="(pagina + 1) * 50 >= total()" (click)="cargar(pagina + 1)" aria-label="Siguiente"><mat-icon>chevron_right</mat-icon></button>
          </div>
        </div>
      }
    </div>
  `,
  styles: `
    .formulario { margin-bottom: 16px; }
    .valor { max-width: 360px; word-break: break-word; font-size: 12px; }
    .firma { cursor: pointer; display: inline-flex; padding: 8px; color: var(--inees-gris); }
    .firma .ok { color: var(--inees-verde); }
  `,
})
export class Crud {
  protected readonly sesion = inject(Sesion);
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  readonly entidad = input.required<string>();
  protected readonly pesos = pesos;
  protected readonly meta = signal<Entidad | null>(null);
  protected readonly filas = signal<Fila[]>([]);
  protected readonly total = signal(0);
  protected readonly form = signal<Fila | null>(null);
  protected readonly guardando = signal(false);
  protected readonly columnas = computed(() => (this.meta()?.campos ?? []).filter((c) => c.enLista));
  protected texto = '';
  protected inactivos = false;
  protected pagina = 0;

  constructor() {
    effect(() => {
      const e = this.entidad();
      untracked(() => this.iniciar(e));
    });
  }

  private async iniciar(e: string): Promise<void> {
    this.form.set(null);
    this.texto = '';
    try {
      this.meta.set(await this.rest.get<Entidad>(`/api/admin/${e}/meta`));
      await this.cargar(0);
    } catch (err) {
      this.avisos.error(err);
    }
  }

  async cargar(pagina: number): Promise<void> {
    this.pagina = pagina;
    try {
      const r = await this.rest.get<{ items: Fila[]; total: number }>(`/api/admin/${this.entidad()}`, { texto: this.texto, inactivos: this.inactivos, pagina, tamano: 50 });
      this.filas.set(r.items);
      this.total.set(r.total);
    } catch (e) {
      this.avisos.error(e);
    }
  }

  protected tipoInput(c: Campo): string {
    switch (c.tipo) {
      case 'ENTERO': case 'DECIMAL': case 'PESOS': return 'number';
      case 'FECHA': return 'date';
      case 'HORA': return 'time';
      case 'EMAIL': return 'email';
      default: return 'text';
    }
  }

  nuevo(): void {
    const f: Fila = {};
    for (const c of this.meta()!.campos) if (c.tipo === 'BOOLEANO') f[c.nombre] = c.nombre === this.meta()!.activo || c.nombre === 'activo_operativo';
    this.form.set(f);
  }

  async editar(f: Fila): Promise<void> {
    try {
      const completo = await this.rest.get<Fila>(`/api/admin/${this.entidad()}/${f['id']}`);
      for (const c of this.meta()!.campos) if (c.tipo === 'HORA' && completo[c.nombre]) completo[c.nombre] = String(completo[c.nombre]).substring(0, 5);
      this.form.set(completo);
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async guardar(): Promise<void> {
    const f = this.form()!;
    const datos: Fila = {};
    for (const c of this.meta()!.campos) datos[c.nombre] = f[c.nombre] ?? null;
    this.guardando.set(true);
    try {
      if (f['id']) await this.rest.put(`/api/admin/${this.entidad()}/${f['id']}`, datos);
      else await this.rest.post(`/api/admin/${this.entidad()}`, datos);
      this.avisos.ok('Guardado.');
      this.form.set(null);
      await this.cargar(this.pagina);
    } catch (e) {
      this.avisos.error(e);
    } finally {
      this.guardando.set(false);
    }
  }

  async eliminar(f: Fila): Promise<void> {
    if (!confirm(this.meta()!.activo ? '¿Desactivar este registro?' : '¿Eliminar este registro?')) return;
    try {
      await this.rest.delete(`/api/admin/${this.entidad()}/${f['id']}`);
      await this.cargar(this.pagina);
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async firma(f: Fila, ev: Event): Promise<void> {
    const archivo = (ev.target as HTMLInputElement).files?.[0];
    if (!archivo) return;
    try {
      await this.rest.enviar(`/api/admin/tecnicos/${f['id']}/firma`, { archivo });
      this.avisos.ok('Firma cargada.');
      await this.cargar(this.pagina);
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async activar(f: Fila): Promise<void> {
    try {
      await this.rest.post(`/api/admin/${this.entidad()}/${f['id']}/activar`);
      await this.cargar(this.pagina);
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
