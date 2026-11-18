import { DatePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatSelectModule } from '@angular/material/select';
import { Avisos } from '../core/infra';
import { Rest } from '../core/rest';

interface Solicitud {
  idSolicitud: number;
  solicitante: string;
  fecha: string;
  estado: string;
  observaciones: string | null;
  actualizada: string | null;
  detalle: string | null;
}

/** Tablero de solicitudes de repuestos que hacen los técnicos (solicitarInventario de PHP). */
@Component({
  selector: 'app-solicitudes',
  imports: [FormsModule, DatePipe, MatButtonModule, MatIconModule, MatFormFieldModule, MatSelectModule],
  template: `
    <div class="pagina">
      <div class="encabezado">
        <h1>Solicitudes de repuestos</h1>
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Estado</mat-label>
          <mat-select [(ngModel)]="estado" (selectionChange)="cargar()"><mat-option value="">Todas</mat-option>
            @for (e of estados; track e) { <mat-option [value]="e">{{ e }}</mat-option> }</mat-select></mat-form-field>
      </div>
      <div class="columnas">
        @for (e of estados; track e) {
          <section class="columna">
            <h3>{{ e }} <span class="chip">{{ por(e).length }}</span></h3>
            @for (s of por(e); track s.idSolicitud) {
              <article class="tarjeta">
                <div class="cab"><strong>#{{ s.idSolicitud }} · {{ s.solicitante }}</strong><span class="muted">{{ s.fecha | date: 'dd/MM/yy HH:mm' }}</span></div>
                <pre>{{ s.detalle }}</pre>
                @if (s.observaciones) { <p class="muted">{{ s.observaciones }}</p> }
                <div class="acciones">
                  @for (otro of estados; track otro) {
                    @if (otro !== s.estado) { <button mat-stroked-button (click)="cambiar(s, otro)">{{ otro }}</button> }
                  }
                </div>
              </article>
            } @empty { <p class="vacio">Ninguna</p> }
          </section>
        }
      </div>
    </div>
  `,
  styles: `
    .columnas { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; }
    .columna h3 { margin-bottom: 8px; }
    article { margin-bottom: 10px; }
    .cab { display: flex; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
    pre { font: inherit; white-space: pre-wrap; margin: 8px 0; }
  `,
})
export class Solicitudes implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected readonly estados = ['Pendiente', 'En gestión', 'Finalizado'];
  protected estado = '';
  protected readonly lista = signal<Solicitud[]>([]);

  ngOnInit(): void {
    this.cargar();
  }

  protected por(e: string): Solicitud[] {
    return this.lista().filter((s) => s.estado === e);
  }

  async cargar(): Promise<void> {
    try {
      this.lista.set(await this.rest.get<Solicitud[]>('/api/inventario/solicitudes', { estado: this.estado }));
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async cambiar(s: Solicitud, estado: string): Promise<void> {
    try {
      await this.rest.put(`/api/inventario/solicitudes/${s.idSolicitud}/estado`, { estado });
      await this.cargar();
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
