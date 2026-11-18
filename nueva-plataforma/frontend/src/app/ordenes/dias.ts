import { DatePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { RouterLink } from '@angular/router';
import { Avisos, hoyIso } from '../core/infra';
import { pesos, Rest } from '../core/rest';

interface Dia {
  fecha: string;
  servicios: number;
  valor: number;
  delegaciones: { delegacion: string; servicios: number; valor: number; tecnicos: number }[];
}

/** Servicios realizados por día y delegación (ordenVer de PHP). Cada día abre su detalle para revisar y corregir. */
@Component({
  selector: 'app-dias',
  imports: [FormsModule, DatePipe, RouterLink, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule],
  template: `
    <div class="pagina">
      <div class="encabezado">
        <h1>Servicios por día</h1>
        <a mat-flat-button routerLink="/ordenes/registrar"><mat-icon>post_add</mat-icon> Registrar servicios</a>
      </div>
      <div class="filtros">
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Desde</mat-label>
          <input matInput type="date" [(ngModel)]="desde" /></mat-form-field>
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Hasta</mat-label>
          <input matInput type="date" [(ngModel)]="hasta" /></mat-form-field>
        <button mat-stroked-button (click)="cargar()"><mat-icon>search</mat-icon> Consultar</button>
      </div>
      <div class="contenedor-tabla">
        <table class="tabla">
          <thead><tr><th>Fecha</th><th class="num">Servicios</th><th class="num">Valor</th><th>Por delegación</th><th></th></tr></thead>
          <tbody>
            @for (d of dias(); track d.fecha) {
              <tr>
                <td><strong>{{ d.fecha | date: 'EEE d MMM y' }}</strong></td>
                <td class="num">{{ d.servicios }}</td>
                <td class="num">{{ pesos(d.valor) }}</td>
                <td>
                  @for (x of d.delegaciones; track x.delegacion) {
                    <span class="chip" [title]="x.tecnicos + ' técnicos · ' + pesos(x.valor)">{{ x.delegacion }}: {{ x.servicios }}</span>
                  }
                </td>
                <td><a mat-button [routerLink]="['/ordenes/dia', d.fecha]">Ver detalle</a></td>
              </tr>
            } @empty {
              <tr><td colspan="5" class="vacio">No hay servicios en el rango.</td></tr>
            }
          </tbody>
        </table>
      </div>
    </div>
  `,
  styles: `.chip { margin: 0 4px 4px 0; }`,
})
export class Dias implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected readonly pesos = pesos;
  protected desde = hoyIso(-30);
  protected hasta = hoyIso();
  protected readonly dias = signal<Dia[]>([]);

  ngOnInit(): void {
    this.cargar();
  }

  async cargar(): Promise<void> {
    try {
      this.dias.set(await this.rest.get<Dia[]>('/api/ordenes/dias', { desde: this.desde, hasta: this.hasta }));
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
