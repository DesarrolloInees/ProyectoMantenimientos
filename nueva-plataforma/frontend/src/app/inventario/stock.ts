import { DatePipe } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatTabsModule } from '@angular/material/tabs';
import { MatTooltipModule } from '@angular/material/tooltip';
import { Selector } from '../compartido/selector';
import { Avisos } from '../core/infra';
import { Rest } from '../core/rest';

export interface StockFila {
  idTecnico: number;
  tecnico: string;
  idRepuesto: number;
  repuesto: string;
  codigo: string | null;
  cantidad: number;
  ultimaActualizacion: string | null;
}

export interface Movimiento {
  idMovimiento: number;
  fecha: string;
  tecnico: string;
  repuesto: string;
  tipo: string;
  cantidad: number;
  idOrden: number | null;
  remision: string | null;
  usuario: string | null;
  observacion: string | null;
}

/**
 * Inventario de repuestos de cada motorizado (inventarioTecnico de PHP). Toda entrega, conteo o retiro queda en el kardex.
 */
@Component({
  selector: 'app-stock',
  imports: [FormsModule, DatePipe, Selector, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule, MatTabsModule, MatTooltipModule],
  template: `
    <div class="pagina">
      <div class="encabezado">
        <h1>Inventario de técnicos</h1>
        <button mat-stroked-button (click)="excel()"><mat-icon>download</mat-icon> Excel</button>
      </div>
      <div class="filtros">
        <app-selector lista="tecnicos" etiqueta="Técnico" [(valor)]="idTecnico" (valorChange)="cargar()" />
        <mat-form-field appearance="outline" subscriptSizing="dynamic" class="ancho"><mat-label>Buscar repuesto</mat-label>
          <input matInput [(ngModel)]="texto" (keyup.enter)="cargar()" /></mat-form-field>
        <button mat-stroked-button (click)="cargar()"><mat-icon>search</mat-icon> Consultar</button>
      </div>
      <mat-tab-group animationDuration="0">
        <mat-tab label="Stock">
          <div class="contenedor-tabla">
            <table class="tabla">
              <thead><tr><th>Técnico</th><th>Repuesto</th><th>Código</th><th class="num">Cantidad</th><th>Actualizado</th><th></th></tr></thead>
              <tbody>
                @for (s of stock(); track s.idTecnico + '-' + s.idRepuesto) {
                  <tr>
                    <td>{{ s.tecnico }}</td><td>{{ s.repuesto }}</td><td>{{ s.codigo }}</td>
                    <td class="num">
                      @if (editando() === s) {
                        <input class="cant" type="number" min="0" [(ngModel)]="nuevaCantidad" (keyup.enter)="ajustar(s)" />
                      } @else { <strong>{{ s.cantidad }}</strong> }
                    </td>
                    <td>{{ s.ultimaActualizacion | date: 'dd/MM/yy HH:mm' }}</td>
                    <td class="botones-fila">
                      @if (editando() === s) {
                        <button mat-button (click)="ajustar(s)">Guardar</button><button mat-button (click)="editando.set(null)">Cancelar</button>
                      } @else {
                        <button mat-icon-button (click)="editar(s)" matTooltip="Corregir cantidad (conteo)"><mat-icon>edit</mat-icon></button>
                        <button mat-icon-button (click)="kardex(s)" matTooltip="Ver movimientos"><mat-icon>history</mat-icon></button>
                        <button mat-icon-button (click)="retirar(s)" matTooltip="Retirar del inventario"><mat-icon>delete</mat-icon></button>
                      }
                    </td>
                  </tr>
                } @empty { <tr><td colspan="6" class="vacio">Sin stock. Elija un técnico o busque un repuesto.</td></tr> }
              </tbody>
            </table>
          </div>
        </mat-tab>
        <mat-tab label="Entregar repuestos">
          <div class="tarjeta">
            <p class="muted">Suma al stock del técnico seleccionado. Queda como «asignación» en el kardex.</p>
            <app-selector lista="tecnicos" etiqueta="Técnico que recibe" [(valor)]="entrega.idTecnico" />
            @for (i of entrega.items; track $index; let j = $index) {
              <div class="filtros">
                <app-selector lista="repuestos" etiqueta="Repuesto" [(valor)]="i.idRepuesto" />
                <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Cantidad</mat-label>
                  <input matInput type="number" min="1" [(ngModel)]="i.cantidad" /></mat-form-field>
                <button mat-icon-button (click)="entrega.items.splice(j, 1)" aria-label="Quitar"><mat-icon>close</mat-icon></button>
              </div>
            }
            <button mat-stroked-button (click)="entrega.items.push({ idRepuesto: null, cantidad: 1 })"><mat-icon>add</mat-icon> Otro repuesto</button>
            <mat-form-field appearance="outline" class="obs"><mat-label>Observación</mat-label><input matInput [(ngModel)]="entrega.observacion" /></mat-form-field>
            <div class="acciones"><button mat-flat-button (click)="entregar()" [disabled]="!entrega.idTecnico">Entregar</button></div>
          </div>
        </mat-tab>
        <mat-tab label="Kardex">
          <div class="contenedor-tabla">
            <table class="tabla">
              <thead><tr><th>Fecha</th><th>Técnico</th><th>Repuesto</th><th>Tipo</th><th class="num">Cantidad</th><th>Servicio</th><th>Usuario</th><th>Observación</th></tr></thead>
              <tbody>
                @for (m of movimientos(); track m.idMovimiento) {
                  <tr><td>{{ m.fecha | date: 'dd/MM/yy HH:mm' }}</td><td>{{ m.tecnico }}</td><td>{{ m.repuesto }}</td><td>{{ m.tipo }}</td>
                    <td class="num" [class.neg]="m.cantidad < 0">{{ m.cantidad > 0 ? '+' : '' }}{{ m.cantidad }}</td>
                    <td>{{ m.idOrden ? '#' + m.idOrden + ' (' + (m.remision ?? '—') + ')' : '' }}</td><td>{{ m.usuario }}</td><td>{{ m.observacion }}</td></tr>
                } @empty { <tr><td colspan="8" class="vacio">Elija un técnico para ver sus movimientos.</td></tr> }
              </tbody>
            </table>
          </div>
        </mat-tab>
      </mat-tab-group>
    </div>
  `,
  styles: `
    .cant { width: 70px; padding: 4px; text-align: right; }
    .neg { color: var(--inees-rojo); }
    .obs { width: 100%; margin-top: 8px; }
    .tarjeta { margin-top: 12px; }
  `,
})
export class Stock {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected idTecnico: number | null = null;
  protected texto = '';
  protected readonly stock = signal<StockFila[]>([]);
  protected readonly movimientos = signal<Movimiento[]>([]);
  protected readonly editando = signal<StockFila | null>(null);
  protected nuevaCantidad = 0;
  protected entrega: { idTecnico: number | null; items: { idRepuesto: number | null; cantidad: number }[]; observacion: string } = {
    idTecnico: null, items: [{ idRepuesto: null, cantidad: 1 }], observacion: '' };

  async cargar(): Promise<void> {
    if (!this.idTecnico && !this.texto.trim()) return;
    try {
      this.stock.set(await this.rest.get<StockFila[]>('/api/inventario/stock', { idTecnico: this.idTecnico, texto: this.texto }));
      if (this.idTecnico) this.movimientos.set(await this.rest.get<Movimiento[]>('/api/inventario/kardex', { idTecnico: this.idTecnico }));
    } catch (e) {
      this.avisos.error(e);
    }
  }

  editar(s: StockFila): void {
    this.nuevaCantidad = s.cantidad;
    this.editando.set(s);
  }

  async ajustar(s: StockFila): Promise<void> {
    const obs = prompt('Motivo del ajuste (conteo, daño, pérdida…):', 'Ajuste por conteo');
    if (obs === null) return;
    try {
      await this.rest.put('/api/inventario/stock', { idTecnico: s.idTecnico, idRepuesto: s.idRepuesto, cantidad: this.nuevaCantidad, observacion: obs });
      this.editando.set(null);
      await this.cargar();
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async retirar(s: StockFila): Promise<void> {
    const motivo = prompt(`¿Retirar ${s.repuesto} del inventario de ${s.tecnico}? Escriba el motivo:`);
    if (!motivo) return;
    try {
      await this.rest.delete(`/api/inventario/stock/${s.idTecnico}/${s.idRepuesto}`, { motivo });
      await this.cargar();
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async kardex(s: StockFila): Promise<void> {
    try {
      this.movimientos.set(await this.rest.get<Movimiento[]>('/api/inventario/kardex', { idTecnico: s.idTecnico, idRepuesto: s.idRepuesto }));
      this.avisos.ok(`Movimientos de ${s.repuesto} en la pestaña Kardex.`);
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async entregar(): Promise<void> {
    const items = this.entrega.items.filter((i) => i.idRepuesto && i.cantidad > 0);
    if (!items.length) return this.avisos.ok('Agregue al menos un repuesto.');
    try {
      this.stock.set(await this.rest.post<StockFila[]>('/api/inventario/asignar', { ...this.entrega, items }));
      this.idTecnico = this.entrega.idTecnico;
      this.avisos.ok('Repuestos entregados.');
      this.entrega = { idTecnico: this.entrega.idTecnico, items: [{ idRepuesto: null, cantidad: 1 }], observacion: '' };
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async excel(): Promise<void> {
    try {
      await this.rest.descargar('/api/inventario/stock/excel', { idTecnico: this.idTecnico });
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
