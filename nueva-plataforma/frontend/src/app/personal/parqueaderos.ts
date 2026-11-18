import { DatePipe } from '@angular/common';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { Fotos } from '../compartido/fotos';
import { Selector } from '../compartido/selector';
import { Avisos, hora, hoyIso } from '../core/infra';
import { inicioMes, pesos, Rest } from '../core/rest';

export interface Parqueadero {
  id: number;
  fecha: string;
  horaInicio: string;
  horaFin: string;
  numeroFactura: string;
  valor: number;
  rutaFoto: string | null;
  idTecnico: number;
  tecnico: string;
  idPunto: number;
  punto: string;
  registrado: string;
}

/** Facturas de parqueadero de los técnicos con Excel y PDF (parqueaderoAdmin de PHP). */
@Component({
  selector: 'app-parqueaderos-admin',
  imports: [FormsModule, DatePipe, Selector, Fotos, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule],
  template: `
    <div class="pagina">
      <div class="encabezado">
        <h1>Parqueaderos</h1>
        <button mat-stroked-button (click)="descargar('excel')"><mat-icon>table_view</mat-icon> Excel</button>
        <button mat-stroked-button (click)="descargar('pdf')"><mat-icon>picture_as_pdf</mat-icon> PDF</button>
      </div>
      <div class="filtros">
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Desde</mat-label><input matInput type="date" [(ngModel)]="desde" /></mat-form-field>
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Hasta</mat-label><input matInput type="date" [(ngModel)]="hasta" /></mat-form-field>
        <app-selector lista="tecnicos" etiqueta="Técnico" [(valor)]="idTecnico" />
        <button mat-flat-button (click)="cargar()"><mat-icon>search</mat-icon> Consultar</button>
      </div>
      <p class="muted">{{ lista().length }} facturas · total {{ pesos(total()) }}</p>
      <div class="contenedor-tabla">
        <table class="tabla">
          <thead><tr><th>Fecha</th><th>Técnico</th><th>Punto</th><th>Horario</th><th>Factura</th><th class="num">Valor</th><th>Foto</th></tr></thead>
          <tbody>
            @for (p of lista(); track p.id) {
              <tr><td>{{ p.fecha | date: 'dd/MM/yy' }}</td><td>{{ p.tecnico }}</td><td>{{ p.punto }}</td>
                <td>{{ hora(p.horaInicio) }}–{{ hora(p.horaFin) }}</td><td>{{ p.numeroFactura }}</td><td class="num">{{ pesos(p.valor) }}</td>
                <td><app-fotos [rutas]="[p.rutaFoto]" /></td></tr>
            } @empty { <tr><td colspan="7" class="vacio">Sin facturas.</td></tr> }
          </tbody>
        </table>
      </div>
    </div>
  `,
})
export class ParqueaderosAdmin implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected readonly hora = hora;
  protected readonly pesos = pesos;
  protected desde = inicioMes();
  protected hasta = hoyIso();
  protected idTecnico: number | null = null;
  protected readonly lista = signal<Parqueadero[]>([]);
  protected readonly total = computed(() => this.lista().reduce((s, p) => s + Number(p.valor), 0));

  ngOnInit(): void {
    this.cargar();
  }

  private filtros() {
    return { desde: this.desde, hasta: this.hasta, idTecnico: this.idTecnico };
  }

  async cargar(): Promise<void> {
    try {
      this.lista.set(await this.rest.get<Parqueadero[]>('/api/personal/parqueaderos', this.filtros()));
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async descargar(tipo: 'excel' | 'pdf'): Promise<void> {
    try {
      await this.rest.descargar(`/api/personal/parqueaderos/${tipo}`, this.filtros());
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
