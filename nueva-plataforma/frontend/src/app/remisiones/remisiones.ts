import { DatePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { RouterLink } from '@angular/router';
import { Selector } from '../compartido/selector';
import { Avisos } from '../core/infra';
import { Opcion, Rest } from '../core/rest';

interface Remision {
  idControl: number;
  numeroRemision: string;
  idTecnico: number;
  tecnico: string;
  idEstado: number;
  estado: string;
  fechaAsignacion: string | null;
  fechaUso: string | null;
  idOrden: number | null;
  idInstalacion: number | null;
}

/** Talonarios de remisiones de cada técnico: asignar rangos, consultar y corregir estados (controlRemision de PHP). */
@Component({
  selector: 'app-remisiones',
  imports: [FormsModule, DatePipe, RouterLink, Selector, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule, MatSelectModule],
  template: `
    <div class="pagina">
      <div class="encabezado"><h1>Remisiones</h1><a mat-stroked-button routerLink="/remisiones/salteadas"><mat-icon>rule</mat-icon> Salteadas</a></div>
      <section class="tarjeta asignar">
        <h3>Asignar talonario</h3>
        <div class="filtros">
          <app-selector lista="tecnicos" etiqueta="Técnico" [(valor)]="rango.idTecnico" (valorChange)="siguiente()" />
          <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Desde el número</mat-label>
            <input matInput type="number" [(ngModel)]="rango.desde" /></mat-form-field>
          <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Hasta el número</mat-label>
            <input matInput type="number" [(ngModel)]="rango.hasta" /></mat-form-field>
          <button mat-flat-button (click)="asignar()" [disabled]="!rango.idTecnico || !rango.desde">Asignar</button>
          @if (ultima()) { <span class="muted">Última asignada: {{ ultima() }}</span> }
        </div>
      </section>
      <div class="filtros">
        <mat-form-field appearance="outline" subscriptSizing="dynamic" class="ancho"><mat-label>Número o técnico</mat-label>
          <input matInput [(ngModel)]="texto" (keyup.enter)="cargar(0)" /></mat-form-field>
        <app-selector lista="tecnicos" etiqueta="Técnico" [(valor)]="idTecnico" (valorChange)="cargar(0)" />
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Estado</mat-label>
          <mat-select [(ngModel)]="idEstado" (selectionChange)="cargar(0)"><mat-option [value]="null">Todos</mat-option>
            @for (e of estados(); track e.id) { <mat-option [value]="e.id">{{ e.nombre }}</mat-option> }</mat-select></mat-form-field>
        <button mat-stroked-button (click)="cargar(0)"><mat-icon>search</mat-icon> Buscar</button>
      </div>
      <div class="contenedor-tabla">
        <table class="tabla">
          <thead><tr><th>Remisión</th><th>Técnico</th><th>Estado</th><th>Asignada</th><th>Usada</th><th>Usada en</th><th></th></tr></thead>
          <tbody>
            @for (r of lista(); track r.idControl) {
              <tr>
                <td><strong>{{ r.numeroRemision }}</strong></td><td>{{ r.tecnico }}</td>
                <td>
                  @if (editando() === r.idControl) {
                    <mat-select class="sel" [(ngModel)]="nuevoEstado">@for (e of estados(); track e.id) { <mat-option [value]="e.id">{{ e.nombre }}</mat-option> }</mat-select>
                  } @else { <span class="chip">{{ r.estado }}</span> }
                </td>
                <td>{{ r.fechaAsignacion | date: 'dd/MM/yy' }}</td><td>{{ r.fechaUso | date: 'dd/MM/yy HH:mm' }}</td>
                <td>
                  @if (r.idOrden) { <a [routerLink]="['/ordenes', r.idOrden]">Servicio #{{ r.idOrden }}</a> }
                  @if (r.idInstalacion) { Transporte #{{ r.idInstalacion }} }
                </td>
                <td>
                  @if (editando() === r.idControl) {
                    <button mat-button (click)="guardar(r)">Guardar</button><button mat-button (click)="editando.set(null)">Cancelar</button>
                  } @else { <button mat-icon-button (click)="editar(r)" aria-label="Cambiar estado"><mat-icon>edit</mat-icon></button> }
                </td>
              </tr>
            } @empty { <tr><td colspan="7" class="vacio">Sin remisiones.</td></tr> }
          </tbody>
        </table>
        <div class="paginador">
          {{ pagina * 50 + 1 }}–{{ pagina * 50 + lista().length }} de {{ total() }}
          <button mat-icon-button [disabled]="pagina === 0" (click)="cargar(pagina - 1)" aria-label="Anterior"><mat-icon>chevron_left</mat-icon></button>
          <button mat-icon-button [disabled]="(pagina + 1) * 50 >= total()" (click)="cargar(pagina + 1)" aria-label="Siguiente"><mat-icon>chevron_right</mat-icon></button>
        </div>
      </div>
    </div>
  `,
  styles: `.asignar { margin-bottom: 16px; } .sel { width: 180px; }`,
})
export class Remisiones implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected texto = '';
  protected idTecnico: number | null = null;
  protected idEstado: number | null = null;
  protected pagina = 0;
  protected readonly lista = signal<Remision[]>([]);
  protected readonly total = signal(0);
  protected readonly estados = signal<Opcion[]>([]);
  protected readonly editando = signal<number | null>(null);
  protected readonly ultima = signal<string | null>(null);
  protected nuevoEstado: number | null = null;
  protected rango: { idTecnico: number | null; desde: number | null; hasta: number | null } = { idTecnico: null, desde: null, hasta: null };

  async ngOnInit(): Promise<void> {
    this.estados.set(await this.rest.lista('estados-remision'));
    this.cargar(0);
  }

  async cargar(pagina: number): Promise<void> {
    this.pagina = pagina;
    try {
      const r = await this.rest.get<{ items: Remision[]; total: number }>('/api/remisiones', {
        texto: this.texto, idTecnico: this.idTecnico, idEstado: this.idEstado, pagina, tamano: 50 });
      this.lista.set(r.items);
      this.total.set(r.total);
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async siguiente(): Promise<void> {
    if (!this.rango.idTecnico) return;
    const s = await this.rest.get<{ ultima: string | null; siguiente: number | null }>('/api/remisiones/siguiente', { idTecnico: this.rango.idTecnico });
    this.ultima.set(s.ultima);
    this.rango.desde = s.siguiente;
  }

  async asignar(): Promise<void> {
    try {
      const r = await this.rest.post<{ creadas: number; repetidas: number }>('/api/remisiones/rango', this.rango);
      this.avisos.ok(`Se asignaron ${r.creadas} remisiones${r.repetidas ? `; ${r.repetidas} ya las tenía y se omitieron` : ''}.`);
      this.rango = { idTecnico: this.rango.idTecnico, desde: null, hasta: null };
      await this.siguiente();
      await this.cargar(0);
    } catch (e) {
      this.avisos.error(e);
    }
  }

  editar(r: Remision): void {
    this.nuevoEstado = r.idEstado;
    this.editando.set(r.idControl);
  }

  async guardar(r: Remision): Promise<void> {
    try {
      await this.rest.put(`/api/remisiones/${r.idControl}`, { numeroRemision: r.numeroRemision, idTecnico: r.idTecnico, idEstado: this.nuevoEstado });
      this.editando.set(null);
      await this.cargar(this.pagina);
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
