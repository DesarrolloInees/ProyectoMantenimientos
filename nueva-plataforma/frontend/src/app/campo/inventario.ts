import { DatePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatTabsModule } from '@angular/material/tabs';
import { Selector } from '../compartido/selector';
import { Avisos } from '../core/infra';
import { Rest } from '../core/rest';
import { Movimiento, StockFila } from '../inventario/stock';

interface Solicitud {
  idSolicitud: number;
  fecha: string;
  estado: string;
  observaciones: string | null;
  detalle: string | null;
}

/** Inventario del técnico, sus movimientos y la solicitud de repuestos al almacén (inventarioTecnicoVer y solicitarInventario). */
@Component({
  selector: 'app-mi-inventario',
  imports: [FormsModule, DatePipe, Selector, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule, MatTabsModule],
  template: `
    <div class="pagina movil">
      <h1>Mi inventario</h1>
      <mat-tab-group animationDuration="0">
        <mat-tab label="Stock">
          @for (s of stock(); track s.idRepuesto) {
            <div class="fila"><span>{{ s.repuesto }}<br /><small class="muted">{{ s.codigo }}</small></span><strong>{{ s.cantidad }}</strong></div>
          } @empty { <p class="vacio">No tiene repuestos asignados.</p> }
        </mat-tab>
        <mat-tab label="Pedir repuestos">
          <div class="tarjeta">
            @for (i of items; track $index; let j = $index) {
              <div class="pedido">
                <app-selector lista="repuestos" etiqueta="Repuesto" [(valor)]="i.idRepuesto" />
                <mat-form-field appearance="outline" subscriptSizing="dynamic" class="cant"><mat-label>Cant.</mat-label>
                  <input matInput type="number" min="1" [(ngModel)]="i.cantidad" /></mat-form-field>
                <button mat-icon-button (click)="items.splice(j, 1)" aria-label="Quitar"><mat-icon>close</mat-icon></button>
              </div>
            }
            <button mat-stroked-button (click)="items.push({ idRepuesto: null, cantidad: 1 })"><mat-icon>add</mat-icon> Otro repuesto</button>
            <mat-form-field appearance="outline" class="obs"><mat-label>Observaciones</mat-label><textarea matInput rows="2" [(ngModel)]="observaciones"></textarea></mat-form-field>
            <button mat-flat-button class="grande" (click)="pedir()" [disabled]="enviando()">Enviar solicitud</button>
          </div>
          <h3>Mis solicitudes</h3>
          @for (s of solicitudes(); track s.idSolicitud) {
            <div class="tarjeta sol"><div class="cab"><strong>#{{ s.idSolicitud }} · {{ s.fecha | date: 'd MMM' }}</strong>
              <span class="chip" [class.ok]="s.estado === 'Finalizado'" [class.aviso]="s.estado !== 'Finalizado'">{{ s.estado }}</span></div>
              <pre>{{ s.detalle }}</pre></div>
          } @empty { <p class="vacio">Sin solicitudes.</p> }
        </mat-tab>
        <mat-tab label="Movimientos">
          @for (m of movimientos(); track m.idMovimiento) {
            <div class="fila"><span>{{ m.repuesto }}<br /><small class="muted">{{ m.fecha | date: 'd MMM HH:mm' }} · {{ m.tipo }} {{ m.observacion }}</small></span>
              <strong [class.neg]="m.cantidad < 0">{{ m.cantidad > 0 ? '+' : '' }}{{ m.cantidad }}</strong></div>
          } @empty { <p class="vacio">Sin movimientos en los últimos 90 días.</p> }
        </mat-tab>
      </mat-tab-group>
    </div>
  `,
  styles: `
    .movil { max-width: 640px; }
    h1 { font-size: 20px; margin-bottom: 8px; }
    .fila { display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 10px 4px; border-bottom: 1px solid var(--linea); }
    .pedido { display: flex; gap: 6px; align-items: center; app-selector { flex: 1; } }
    .cant { width: 80px; }
    .obs { width: 100%; margin-top: 8px; }
    .grande { width: 100%; height: 48px; }
    .tarjeta { margin-top: 12px; }
    .sol .cab { display: flex; justify-content: space-between; }
    pre { font: inherit; white-space: pre-wrap; margin: 6px 0 0; }
    .neg { color: var(--inees-rojo); }
  `,
})
export class MiInventario implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected readonly stock = signal<StockFila[]>([]);
  protected readonly movimientos = signal<Movimiento[]>([]);
  protected readonly solicitudes = signal<Solicitud[]>([]);
  protected readonly enviando = signal(false);
  protected items: { idRepuesto: number | null; cantidad: number }[] = [{ idRepuesto: null, cantidad: 1 }];
  protected observaciones = '';

  async ngOnInit(): Promise<void> {
    try {
      const [s, m, so] = await Promise.all([this.rest.get<StockFila[]>('/api/campo/inventario'),
        this.rest.get<Movimiento[]>('/api/campo/inventario/movimientos'), this.rest.get<Solicitud[]>('/api/campo/solicitudes')]);
      this.stock.set(s);
      this.movimientos.set(m);
      this.solicitudes.set(so);
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async pedir(): Promise<void> {
    const items = this.items.filter((i) => i.idRepuesto && i.cantidad > 0);
    if (!items.length) return this.avisos.ok('Elija al menos un repuesto.');
    this.enviando.set(true);
    try {
      const id = await this.rest.post<number>('/api/campo/solicitudes', { items, observaciones: this.observaciones });
      this.avisos.ok(`Solicitud #${id} enviada al almacén.`);
      this.items = [{ idRepuesto: null, cantidad: 1 }];
      this.observaciones = '';
      this.solicitudes.set(await this.rest.get<Solicitud[]>('/api/campo/solicitudes'));
    } catch (e) {
      this.avisos.error(e);
    } finally {
      this.enviando.set(false);
    }
  }
}
