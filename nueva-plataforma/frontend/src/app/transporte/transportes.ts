import { DatePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatTooltipModule } from '@angular/material/tooltip';
import { Selector } from '../compartido/selector';
import { Avisos, hoyIso } from '../core/infra';
import { Opcion, pesos, Rest } from '../core/rest';

interface Transporte {
  id: number;
  fecha: string;
  categoria: string;
  tipoServicio: string | null;
  notas: string | null;
  descripcionInees: string | null;
  lugarRecogida: string | null;
  fechaRecogida: string | null;
  esMaquina: boolean;
  idTipoMaquina: number | null;
  tipoMaquina: string | null;
  serialMaquina: string | null;
  productoOtro: string | null;
  idClienteOrigen: number | null;
  clienteOrigen: string | null;
  idPuntoOrigen: number | null;
  puntoOrigen: string | null;
  idClienteDestino: number | null;
  clienteDestino: string | null;
  idPuntoDestino: number | null;
  puntoDestino: string | null;
  idTecnico: number;
  tecnico: string;
  valor: number;
  idControlRemision: number | null;
  remision: string | null;
}

const CATEGORIAS: Record<string, string> = { Prosegur_Cobro: 'Prosegur - con cobro', Prosegur_NoCobro: 'Prosegur - sin cobro', Inees: 'Transportes INEES (internos)' };

/** Instalaciones, desinstalaciones y traslados de máquinas con su remisión y fotos (transporte de PHP). */
@Component({
  selector: 'app-transportes',
  imports: [FormsModule, DatePipe, Selector, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule, MatSelectModule, MatCheckboxModule, MatTooltipModule],
  template: `
    <div class="pagina">
      <div class="encabezado">
        <h1>Transportes</h1>
        <button mat-stroked-button (click)="excel()"><mat-icon>download</mat-icon> Excel</button>
        <button mat-flat-button (click)="nuevo()"><mat-icon>add</mat-icon> Nuevo transporte</button>
      </div>
      @if (form(); as f) {
        <section class="tarjeta formulario">
          <h3>{{ f.id ? 'Editar transporte #' + f.id : 'Nuevo transporte' }}</h3>
          <div class="rejilla">
            <mat-form-field appearance="outline"><mat-label>Categoría</mat-label>
              <mat-select [(ngModel)]="f.categoria" (selectionChange)="f.tipoServicio = null">
                @for (c of categorias; track c[0]) { <mat-option [value]="c[0]">{{ c[1] }}</mat-option> }</mat-select></mat-form-field>
            <mat-form-field appearance="outline"><mat-label>Servicio</mat-label>
              <mat-select [(ngModel)]="f.tipoServicio">@for (s of servicios()[f.categoria] ?? []; track s) { <mat-option [value]="s">{{ s }}</mat-option> }</mat-select></mat-form-field>
            <mat-form-field appearance="outline"><mat-label>Fecha</mat-label><input matInput type="date" [(ngModel)]="f.fecha" /></mat-form-field>
            <app-selector lista="tecnicos" etiqueta="Técnico" [(valor)]="f.idTecnico" [nombreInicial]="f.tecnico" (valorChange)="cargarRemisiones()" />
            <mat-form-field appearance="outline"><mat-label>Remisión</mat-label>
              <mat-select [(ngModel)]="f.idControlRemision"><mat-option [value]="null">Sin remisión</mat-option>
                @if (f.idControlRemision && f.remision) { <mat-option [value]="f.idControlRemision">{{ f.remision }} (actual)</mat-option> }
                @for (r of remisiones(); track r.id) { <mat-option [value]="r.id">{{ r.nombre }}</mat-option> }</mat-select></mat-form-field>
            <mat-form-field appearance="outline"><mat-label>Valor del servicio</mat-label><input matInput type="number" [(ngModel)]="f.valor" /></mat-form-field>
            <mat-checkbox [(ngModel)]="f.esMaquina">Es una máquina</mat-checkbox>
            @if (f.esMaquina) {
              <app-selector lista="tipos-maquina" etiqueta="Tipo de máquina" [(valor)]="f.idTipoMaquina" [nombreInicial]="f.tipoMaquina" />
              <mat-form-field appearance="outline"><mat-label>Serial de la máquina</mat-label><input matInput [(ngModel)]="f.serialMaquina" /></mat-form-field>
            } @else {
              <mat-form-field appearance="outline"><mat-label>Producto</mat-label><input matInput [(ngModel)]="f.productoOtro" /></mat-form-field>
            }
            <app-selector lista="clientes" etiqueta="Cliente de origen" [(valor)]="f.idClienteOrigen" [nombreInicial]="f.clienteOrigen" />
            @if (!f.idClienteOrigen) { <mat-form-field appearance="outline"><mat-label>…o escriba el origen</mat-label><input matInput [(ngModel)]="f.clienteOrigenTexto" /></mat-form-field> }
            <app-selector lista="puntos" etiqueta="Punto de origen" [padre]="f.idClienteOrigen" [(valor)]="f.idPuntoOrigen" [nombreInicial]="f.puntoOrigen" />
            @if (!f.idPuntoOrigen) { <mat-form-field appearance="outline"><mat-label>…o escriba el punto de origen</mat-label><input matInput [(ngModel)]="f.puntoOrigenTexto" /></mat-form-field> }
            <app-selector lista="clientes" etiqueta="Cliente de destino" [(valor)]="f.idClienteDestino" [nombreInicial]="f.clienteDestino" />
            @if (!f.idClienteDestino) { <mat-form-field appearance="outline"><mat-label>…o escriba el destino</mat-label><input matInput [(ngModel)]="f.clienteDestinoTexto" /></mat-form-field> }
            <app-selector lista="puntos" etiqueta="Punto de destino" [padre]="f.idClienteDestino" [(valor)]="f.idPuntoDestino" [nombreInicial]="f.puntoDestino" />
            @if (!f.idPuntoDestino) { <mat-form-field appearance="outline"><mat-label>…o escriba el punto de destino</mat-label><input matInput [(ngModel)]="f.puntoDestinoTexto" /></mat-form-field> }
            <mat-form-field appearance="outline"><mat-label>Lugar de recogida</mat-label>
              <mat-select [(ngModel)]="f.lugarRecogida"><mat-option [value]="null">—</mat-option><mat-option value="Innes">INEES</mat-option>
                <mat-option value="Prosegur">Prosegur</mat-option></mat-select></mat-form-field>
            <mat-form-field appearance="outline"><mat-label>Fecha de recogida</mat-label><input matInput type="date" [(ngModel)]="f.fechaRecogida" /></mat-form-field>
            <mat-form-field appearance="outline" class="completo"><mat-label>Notas</mat-label><textarea matInput rows="2" [(ngModel)]="f.notas"></textarea></mat-form-field>
            @if (f.categoria === 'Inees') {
              <mat-form-field appearance="outline" class="completo"><mat-label>Descripción INEES</mat-label><textarea matInput rows="2" [(ngModel)]="f.descripcionInees"></textarea></mat-form-field>
            }
          </div>
          <div class="fotos-form">
            <label>Foto remisión <input type="file" accept="image/*" (change)="foto('fotoRemision', $event)" /></label>
            <label>Foto máquina <input type="file" accept="image/*" (change)="foto('fotoMaquina', $event)" /></label>
            <label>Foto chazos <input type="file" accept="image/*" (change)="foto('fotoChazos', $event)" /></label>
          </div>
          <div class="acciones">
            <button mat-button (click)="form.set(null)">Cancelar</button>
            <button mat-flat-button (click)="guardar()" [disabled]="guardando()">Guardar</button>
          </div>
        </section>
      }
      <div class="filtros">
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Desde</mat-label><input matInput type="date" [(ngModel)]="desde" /></mat-form-field>
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Hasta</mat-label><input matInput type="date" [(ngModel)]="hasta" /></mat-form-field>
        <app-selector lista="tecnicos" etiqueta="Técnico" [(valor)]="idTecnico" />
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Categoría</mat-label>
          <mat-select [(ngModel)]="categoria"><mat-option [value]="null">Todas</mat-option>
            @for (c of categorias; track c[0]) { <mat-option [value]="c[0]">{{ c[1] }}</mat-option> }</mat-select></mat-form-field>
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Serial, remisión o destino</mat-label><input matInput [(ngModel)]="texto" (keyup.enter)="cargar()" /></mat-form-field>
        <button mat-stroked-button (click)="cargar()"><mat-icon>search</mat-icon> Buscar</button>
      </div>
      <div class="contenedor-tabla">
        <table class="tabla">
          <thead><tr><th>#</th><th>Fecha</th><th>Categoría / servicio</th><th>Remisión</th><th>Técnico</th><th>Máquina</th><th>Origen</th><th>Destino</th><th class="num">Valor</th><th></th></tr></thead>
          <tbody>
            @for (t of lista(); track t.id) {
              <tr><td>{{ t.id }}</td><td>{{ t.fecha | date: 'dd/MM/yy' }}</td><td>{{ nombreCategoria(t.categoria) }}<br /><span class="muted">{{ t.tipoServicio }}</span></td>
                <td>{{ t.remision }}</td><td>{{ t.tecnico }}</td><td>{{ t.esMaquina ? t.tipoMaquina : t.productoOtro }}<br /><span class="muted">{{ t.serialMaquina }}</span></td>
                <td>{{ t.clienteOrigen }}<br /><span class="muted">{{ t.puntoOrigen }}</span></td><td>{{ t.clienteDestino }}<br /><span class="muted">{{ t.puntoDestino }}</span></td>
                <td class="num">{{ pesos(t.valor) }}</td>
                <td class="botones-fila">
                  <button mat-icon-button (click)="editar(t)" matTooltip="Editar"><mat-icon>edit</mat-icon></button>
                  <button mat-icon-button (click)="pdf(t)" matTooltip="Acta en PDF"><mat-icon>picture_as_pdf</mat-icon></button>
                  <button mat-icon-button (click)="eliminar(t)" matTooltip="Eliminar"><mat-icon>delete</mat-icon></button>
                </td></tr>
            } @empty { <tr><td colspan="10" class="vacio">Sin transportes.</td></tr> }
          </tbody>
        </table>
      </div>
    </div>
  `,
  styles: `.formulario { margin-bottom: 16px; } .fotos-form { display: flex; gap: 16px; flex-wrap: wrap; margin-top: 8px; font-size: 13px; }`,
})
export class Transportes implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected readonly pesos = pesos;
  protected readonly categorias = Object.entries(CATEGORIAS);
  protected readonly lista = signal<Transporte[]>([]);
  protected readonly servicios = signal<Record<string, string[]>>({});
  protected readonly remisiones = signal<Opcion[]>([]);
  protected readonly form = signal<any>(null);
  protected readonly guardando = signal(false);
  protected desde = hoyIso(-30);
  protected hasta = hoyIso();
  protected idTecnico: number | null = null;
  protected categoria: string | null = null;
  protected texto = '';
  private fotos: Record<string, File> = {};

  async ngOnInit(): Promise<void> {
    this.servicios.set(await this.rest.get<Record<string, string[]>>('/api/transporte/servicios'));
    this.cargar();
  }

  protected nombreCategoria(c: string): string {
    return CATEGORIAS[c] ?? c;
  }

  async cargar(): Promise<void> {
    try {
      this.lista.set(await this.rest.get<Transporte[]>('/api/transporte', { desde: this.desde, hasta: this.hasta, idTecnico: this.idTecnico, categoria: this.categoria, texto: this.texto }));
    } catch (e) {
      this.avisos.error(e);
    }
  }

  nuevo(): void {
    this.fotos = {};
    this.remisiones.set([]);
    this.form.set({ categoria: 'Prosegur_Cobro', fecha: hoyIso(), esMaquina: true, valor: 0, idControlRemision: null });
  }

  editar(t: Transporte): void {
    this.fotos = {};
    this.form.set({ ...t });
    this.cargarRemisiones();
  }

  async cargarRemisiones(): Promise<void> {
    const f = this.form();
    this.remisiones.set(f?.['idTecnico'] ? await this.rest.get<Opcion[]>('/api/transporte/remisiones', { idTecnico: f['idTecnico'] }) : []);
  }

  foto(campo: string, ev: Event): void {
    const a = (ev.target as HTMLInputElement).files?.[0];
    if (a) this.fotos[campo] = a;
  }

  async guardar(): Promise<void> {
    const f = this.form()!;
    const campos: Record<string, unknown> = {};
    for (const k of ['idTecnico', 'fecha', 'categoria', 'tipoServicio', 'notas', 'descripcionInees', 'lugarRecogida', 'fechaRecogida', 'esMaquina',
      'idTipoMaquina', 'serialMaquina', 'productoOtro', 'idClienteOrigen', 'clienteOrigenTexto', 'idPuntoOrigen', 'puntoOrigenTexto',
      'idClienteDestino', 'clienteDestinoTexto', 'idPuntoDestino', 'puntoDestinoTexto', 'valor', 'idControlRemision']) {
      campos[k] = f[k];
    }
    this.guardando.set(true);
    try {
      await this.rest.enviar(f['id'] ? `/api/transporte/${f['id']}` : '/api/transporte', { ...campos, ...this.fotos });
      this.avisos.ok('Transporte guardado.');
      this.form.set(null);
      await this.cargar();
    } catch (e) {
      this.avisos.error(e);
    } finally {
      this.guardando.set(false);
    }
  }

  async pdf(t: Transporte): Promise<void> {
    try {
      await this.rest.descargar(`/api/transporte/${t.id}/pdf`);
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async eliminar(t: Transporte): Promise<void> {
    if (!confirm(`¿Eliminar el transporte #${t.id}? La remisión vuelve a quedar disponible.`)) return;
    try {
      await this.rest.delete(`/api/transporte/${t.id}`);
      await this.cargar();
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async excel(): Promise<void> {
    try {
      await this.rest.descargar('/api/transporte/excel', { desde: this.desde, hasta: this.hasta, idTecnico: this.idTecnico, categoria: this.categoria });
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
