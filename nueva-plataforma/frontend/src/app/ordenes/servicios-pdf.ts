import { DatePipe } from '@angular/common';
import { Component, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { Selector } from '../compartido/selector';
import { Avisos, hoyIso } from '../core/infra';
import { Rest } from '../core/rest';
import { Filtros } from './modelo';

interface ServicioPdf {
  idOrden: number;
  numeroRemision: string | null;
  fechaVisita: string;
  tecnico: string | null;
  cliente: string;
  punto: string;
  delegacion: string | null;
  tipoMantenimiento: string | null;
}

/** PDF de los servicios: uno a uno o varios en un ZIP, opcionalmente en carpetas por delegación y técnico. */
@Component({
  selector: 'app-servicios-pdf',
  imports: [FormsModule, DatePipe, Selector, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule, MatCheckboxModule, MatProgressBarModule],
  template: `
    <div class="pagina">
      <div class="encabezado"><h1>PDF de servicios</h1></div>
      <div class="filtros">
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Desde</mat-label><input matInput type="date" [(ngModel)]="f.desde" /></mat-form-field>
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Hasta</mat-label><input matInput type="date" [(ngModel)]="f.hasta" /></mat-form-field>
        <app-selector lista="clientes" etiqueta="Cliente" [(valor)]="f.idCliente" />
        <app-selector lista="puntos" etiqueta="Punto" [padre]="f.idCliente" [(valor)]="f.idPunto" />
        <app-selector lista="tecnicos" etiqueta="Técnico" [(valor)]="f.idTecnico" />
        <app-selector lista="delegaciones" etiqueta="Delegación" [(valor)]="f.idDelegacion" />
        <app-selector lista="tipos-mantenimiento" etiqueta="Tipo" [(valor)]="f.idTipoMantenimiento" />
        <button mat-flat-button (click)="buscar()"><mat-icon>search</mat-icon> Buscar</button>
      </div>
      @if (lista().length) {
        <div class="tarjeta barra-zip">
          <mat-checkbox [checked]="todos()" (change)="marcarTodos($event.checked)">{{ seleccion().size }} de {{ lista().length }} seleccionados</mat-checkbox>
          <mat-checkbox [(ngModel)]="porDelegacion">Carpeta por delegación</mat-checkbox>
          <mat-checkbox [(ngModel)]="porTecnico">Carpeta por técnico</mat-checkbox>
          <button mat-flat-button (click)="zip()" [disabled]="!seleccion().size || generando()"><mat-icon>folder_zip</mat-icon> Descargar ZIP</button>
        </div>
        @if (generando()) { <mat-progress-bar mode="indeterminate" /> <p class="muted">Generando {{ seleccion().size }} PDF…</p> }
      }
      <div class="contenedor-tabla">
        <table class="tabla">
          <thead><tr><th></th><th>Fecha</th><th>Remisión</th><th>Técnico</th><th>Cliente / punto</th><th>Delegación</th><th>Tipo</th><th></th></tr></thead>
          <tbody>
            @for (s of lista(); track s.idOrden) {
              <tr [class.seleccionada]="seleccion().has(s.idOrden)">
                <td><mat-checkbox [checked]="seleccion().has(s.idOrden)" (change)="alternar(s.idOrden)" /></td>
                <td>{{ s.fechaVisita | date: 'dd/MM/yy' }}</td><td>{{ s.numeroRemision }}</td><td>{{ s.tecnico }}</td>
                <td>{{ s.punto }}<br /><span class="muted">{{ s.cliente }}</span></td><td>{{ s.delegacion }}</td><td>{{ s.tipoMantenimiento }}</td>
                <td><button mat-icon-button (click)="uno(s)" aria-label="PDF"><mat-icon>picture_as_pdf</mat-icon></button></td>
              </tr>
            } @empty { <tr><td colspan="8" class="vacio">Busque los servicios.</td></tr> }
          </tbody>
        </table>
      </div>
    </div>
  `,
  styles: `.barra-zip { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; margin-bottom: 12px; }`,
})
export class ServiciosPdf {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected f: Filtros = { desde: hoyIso(-7), hasta: hoyIso() };
  protected porDelegacion = true;
  protected porTecnico = false;
  protected readonly lista = signal<ServicioPdf[]>([]);
  protected readonly seleccion = signal(new Set<number>());
  protected readonly generando = signal(false);
  protected readonly todos = computed(() => this.lista().length > 0 && this.seleccion().size === this.lista().length);

  async buscar(): Promise<void> {
    try {
      this.lista.set(await this.rest.get<ServicioPdf[]>('/api/servicios-pdf', this.f as never));
      this.seleccion.set(new Set());
    } catch (e) {
      this.avisos.error(e);
    }
  }

  alternar(id: number): void {
    this.seleccion.update((s) => {
      const c = new Set(s);
      c.has(id) ? c.delete(id) : c.add(id);
      return c;
    });
  }

  marcarTodos(v: boolean): void {
    this.seleccion.set(v ? new Set(this.lista().map((s) => s.idOrden)) : new Set());
  }

  async uno(s: ServicioPdf): Promise<void> {
    try {
      await this.rest.descargar(`/api/ordenes/${s.idOrden}/pdf`);
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async zip(): Promise<void> {
    this.generando.set(true);
    try {
      await this.rest.descargar('/api/servicios-pdf/zip', undefined, { ids: [...this.seleccion()], porDelegacion: this.porDelegacion, porTecnico: this.porTecnico });
    } catch (e) {
      this.avisos.error(e);
    } finally {
      this.generando.set(false);
    }
  }
}
