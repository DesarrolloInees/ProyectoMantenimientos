import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { RouterLink } from '@angular/router';
import { Avisos } from '../core/infra';
import { Opcion, Rest } from '../core/rest';

/**
 * Tarifas en matriz tipo de mantenimiento × modalidad, para uno o varios tipos de máquina a la vez, y copia de un año
 * al siguiente con incremento (tarifaCrear y tarifaMasiva de PHP).
 */
@Component({
  selector: 'app-tarifas',
  imports: [FormsModule, RouterLink, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule, MatSelectModule],
  template: `
    <div class="pagina">
      <div class="encabezado"><h1>Tarifas</h1><a mat-stroked-button routerLink="/admin/c/tarifas"><mat-icon>list</mat-icon> Ver todas en lista</a></div>
      <section class="tarjeta">
        <div class="filtros">
          <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Año</mat-label><input matInput type="number" [(ngModel)]="anio" (change)="cargar()" /></mat-form-field>
          <mat-form-field appearance="outline" subscriptSizing="dynamic" class="ancho"><mat-label>Tipos de máquina</mat-label>
            <mat-select multiple [(ngModel)]="tiposMaquina" (selectionChange)="cargar()">
              @for (t of maquinas(); track t.id) { <mat-option [value]="t.id">{{ t.nombre }} @if (conTarifa().includes(t.id)) { ✓ }</mat-option> }
            </mat-select><mat-hint>Si elige varios, se guarda la misma tarifa para todos</mat-hint></mat-form-field>
        </div>
        <table class="tabla">
          <thead><tr><th>Tipo de mantenimiento</th>@for (m of modalidades(); track m.id) { <th class="num">{{ m.nombre }}</th> }</tr></thead>
          <tbody>
            @for (t of mantenimientos(); track t.id) {
              <tr><td>{{ t.nombre }}</td>
                @for (m of modalidades(); track m.id) {
                  <td class="num"><input class="precio" type="number" min="0" [(ngModel)]="precios[t.id + '-' + m.id]" /></td>
                }</tr>
            }
          </tbody>
        </table>
        <div class="acciones">
          <span class="muted">Una casilla vacía o en cero borra esa tarifa.</span>
          <button mat-flat-button (click)="guardar()" [disabled]="!tiposMaquina.length">Guardar tarifas</button>
        </div>
      </section>
      <section class="tarjeta">
        <h3>Copiar tarifas a otro año</h3>
        <div class="filtros">
          <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Desde el año</mat-label><input matInput type="number" [(ngModel)]="copia.desde" /></mat-form-field>
          <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Hacia el año</mat-label><input matInput type="number" [(ngModel)]="copia.hacia" /></mat-form-field>
          <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Incremento (%)</mat-label><input matInput type="number" [(ngModel)]="copia.incremento" /></mat-form-field>
          <button mat-stroked-button (click)="copiar()">Copiar</button>
        </div>
      </section>
    </div>
  `,
  styles: `.precio { width: 130px; padding: 5px; text-align: right; border: 1px solid var(--linea); border-radius: 4px; font: inherit; } section { margin-bottom: 16px; }`,
})
export class Tarifas implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected anio = new Date().getFullYear();
  protected tiposMaquina: number[] = [];
  protected precios: Record<string, number | null> = {};
  protected readonly maquinas = signal<Opcion[]>([]);
  protected readonly mantenimientos = signal<Opcion[]>([]);
  protected readonly modalidades = signal<Opcion[]>([]);
  protected readonly conTarifa = signal<number[]>([]);
  protected copia = { desde: new Date().getFullYear(), hacia: new Date().getFullYear() + 1, incremento: 0 };

  async ngOnInit(): Promise<void> {
    const [a, b, c] = await Promise.all([this.rest.lista('tipos-maquina'), this.rest.lista('tipos-mantenimiento'), this.rest.lista('modalidades')]);
    this.maquinas.set(a);
    this.mantenimientos.set(b);
    this.modalidades.set(c);
    this.cargar();
  }

  async cargar(): Promise<void> {
    try {
      this.conTarifa.set(await this.rest.get<number[]>('/api/admin/tarifas-matriz/con-tarifa', { anio: this.anio }));
      this.precios = {};
      if (this.tiposMaquina.length === 1) {
        const m = await this.rest.get<{ celdas: { idTipoMantenimiento: number; idModalidad: number; precio: number }[] }>('/api/admin/tarifas-matriz',
          { anio: this.anio, idTipoMaquina: this.tiposMaquina[0] });
        for (const c of m.celdas) this.precios[`${c.idTipoMantenimiento}-${c.idModalidad}`] = c.precio;
      }
      this.conTarifa.update((x) => [...x]);
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async guardar(): Promise<void> {
    if (this.tiposMaquina.length > 1 && !confirm(`Se guardará la misma tabla para ${this.tiposMaquina.length} tipos de máquina. ¿Continuar?`)) return;
    const celdas = this.mantenimientos().flatMap((t) => this.modalidades().map((m) => ({ idTipoMantenimiento: t.id, idModalidad: m.id,
      precio: this.precios[`${t.id}-${m.id}`] || null })));
    try {
      await this.rest.put('/api/admin/tarifas-matriz', { anio: this.anio, idsTiposMaquina: this.tiposMaquina, celdas });
      this.avisos.ok('Tarifas guardadas.');
      await this.cargar();
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async copiar(): Promise<void> {
    try {
      const r = await this.rest.post<{ creadas: number; existentes: number }>('/api/admin/tarifas-matriz/copiar', {}, this.copia);
      this.avisos.ok(`Se crearon ${r.creadas} tarifas en ${this.copia.hacia}${r.existentes ? `; ${r.existentes} ya existían` : ''}.`);
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
