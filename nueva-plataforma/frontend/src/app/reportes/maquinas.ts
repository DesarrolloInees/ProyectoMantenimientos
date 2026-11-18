import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { Matriz } from '../compartido/graficos';
import { Selector } from '../compartido/selector';
import { Tabla } from '../compartido/tabla';
import { Avisos } from '../core/infra';
import { Rest } from '../core/rest';

/** Máquinas activas por tipo y delegación (reporteMaquinas de PHP). */
@Component({
  selector: 'app-reporte-maquinas',
  imports: [FormsModule, Selector, Tabla, Matriz, MatButtonModule, MatIconModule],
  template: `
    <div class="pagina">
      <div class="encabezado"><h1>Máquinas</h1></div>
      <section class="tarjeta"><app-matriz titulo="Máquinas activas por delegación y tipo" nombreFila="Delegación" [datos]="$any(resumen())" /></section>
      <div class="filtros">
        <app-selector lista="tipos-maquina" etiqueta="Tipo de máquina" [(valor)]="idTipoMaquina" />
        <app-selector lista="delegaciones" etiqueta="Delegación" [(valor)]="idDelegacion" />
        <button mat-flat-button (click)="cargar()"><mat-icon>search</mat-icon> Listar</button>
        <button mat-stroked-button (click)="excel()"><mat-icon>table_view</mat-icon> Excel</button>
      </div>
      <app-tabla [filas]="filas()" [columnas]="[{ clave: 'delegacion', titulo: 'Delegación' }, { clave: 'device_id', titulo: 'Device ID' },
        { clave: 'tipo_maquina', titulo: 'Tipo' }, { clave: 'cliente', titulo: 'Cliente' }, { clave: 'punto', titulo: 'Punto' },
        { clave: 'direccion', titulo: 'Dirección' }, { clave: 'ultima_visita', titulo: 'Última visita', tipo: 'fecha' },
        { clave: 'operativa', titulo: 'Operativa', tipo: 'si' }, { clave: 'vip', titulo: 'VIP', tipo: 'si' }]" />
    </div>
  `,
  styles: `section { margin-bottom: 16px; }`,
})
export class ReporteMaquinas implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected idTipoMaquina: number | null = null;
  protected idDelegacion: number | null = null;
  protected readonly filas = signal<Record<string, any>[]>([]);
  protected readonly resumen = signal<Record<string, any>[]>([]);

  async ngOnInit(): Promise<void> {
    try {
      this.resumen.set(await this.rest.get('/api/reportes/maquinas/resumen'));
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async cargar(): Promise<void> {
    try {
      this.filas.set((await this.rest.get<{ filas: Record<string, any>[] }>('/api/reportes/maquinas', { idTipoMaquina: this.idTipoMaquina, idDelegacion: this.idDelegacion })).filas);
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async excel(): Promise<void> {
    try {
      await this.rest.descargar('/api/reportes/maquinas/excel', { idTipoMaquina: this.idTipoMaquina, idDelegacion: this.idDelegacion });
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
