import { DatePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { Router, RouterLink } from '@angular/router';
import { Api } from '../core/api';
import { Avisos, hoyIso } from '../core/infra';
import { ResumenCarga } from '../core/modelos';

/** Carga del Excel diario de Prosegur con los servicios del día siguiente. */
@Component({
  selector: 'app-cargas',
  imports: [FormsModule, RouterLink, DatePipe, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule, MatProgressBarModule],
  template: `
    <div class="pagina">
      <div class="encabezado"><h1>Excel de Prosegur</h1></div>

      <div class="tarjeta subir">
        <div class="paso">
          <span class="num">1</span>
          <div>
            <strong>Elija el archivo que envió Prosegur</strong>
            <p class="muted">Se reconocen las columnas por su encabezado: ID de la máquina (Device ID, serial…), ticket,
              tipo de servicio y descripción.</p>
          </div>
        </div>
        <div class="fila">
          <input #archivo type="file" accept=".xlsx,.xls" (change)="elegir(archivo.files)" hidden />
          <button mat-stroked-button (click)="archivo.click()"><mat-icon>attach_file</mat-icon>
            {{ seleccionado()?.name ?? 'Elegir archivo' }}</button>
          <mat-form-field appearance="outline" subscriptSizing="dynamic">
            <mat-label>Servicios para el día</mat-label>
            <input matInput type="date" [(ngModel)]="fecha" [min]="hoy" />
          </mat-form-field>
          <button mat-flat-button (click)="subir()" [disabled]="!seleccionado() || subiendo()">
            <mat-icon>upload</mat-icon> Cargar y revisar
          </button>
        </div>
        @if (subiendo()) { <mat-progress-bar mode="indeterminate" /> }
      </div>

      <h2 class="titulo">Cargas recientes</h2>
      <div class="tarjeta tabla">
        <table>
          <thead>
            <tr><th>Archivo</th><th>Servicios para</th><th>Filas</th><th>Sin ubicar</th><th>Estado</th><th>Cargó</th></tr>
          </thead>
          <tbody>
            @for (c of cargas(); track c.idCarga) {
              <tr [routerLink]="['/cargas', c.idCarga]">
                <td><strong>{{ c.nombreArchivo }}</strong></td>
                <td>{{ c.fechaServicio | date: 'EEE d MMM' }}</td>
                <td>{{ c.filasOk }} ok @if (c.filasError) { · <span class="chip error">{{ c.filasError }} con error</span> }</td>
                <td>@if (c.sinUbicacion && c.estado === 'EN_REVISION') { <span class="chip aviso">{{ c.sinUbicacion }}</span> } @else { — }</td>
                <td><span class="chip" [class.ok]="c.estado === 'CONFIRMADA'" [class.aviso]="c.estado === 'EN_REVISION'">{{ c.estado.replace('_', ' ') }}</span></td>
                <td class="muted">{{ c.usuario }} · {{ c.fechaCarga | date: 'd MMM HH:mm' }}</td>
              </tr>
            } @empty {
              <tr><td colspan="6" class="vacio">Todavía no se ha cargado ningún Excel.</td></tr>
            }
          </tbody>
        </table>
      </div>
    </div>
  `,
  styles: `
    .subir { display: flex; flex-direction: column; gap: 12px; max-width: 900px; }
    .paso { display: flex; gap: 12px; }
    .paso p { margin: 4px 0 0; }
    .num { background: var(--inees-ambar); border-radius: 50%; min-width: 28px; height: 28px; display: grid; place-items: center; font-weight: 700; }
    .fila { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
    .titulo { margin: 24px 0 8px; font: var(--mat-sys-title-medium); }
    .tabla { padding: 0; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th, td { text-align: left; padding: 10px 14px; border-bottom: 1px solid var(--fondo); white-space: nowrap; }
    th { font-size: 12px; color: var(--inees-gris); font-weight: 600; }
    tbody tr { cursor: pointer; }
    tbody tr:hover { background: var(--fondo-2); }
  `,
})
export class Cargas implements OnInit {
  private readonly api = inject(Api);
  private readonly avisos = inject(Avisos);
  private readonly router = inject(Router);

  protected readonly hoy = hoyIso();
  protected fecha = hoyIso(1);
  protected readonly cargas = signal<ResumenCarga[]>([]);
  protected readonly seleccionado = signal<File | null>(null);
  protected readonly subiendo = signal(false);

  async ngOnInit(): Promise<void> {
    this.cargas.set(await this.api.cargas());
  }

  elegir(archivos: FileList | null): void {
    this.seleccionado.set(archivos?.[0] ?? null);
  }

  async subir(): Promise<void> {
    this.subiendo.set(true);
    try {
      const d = await this.api.subirCarga(this.seleccionado()!, this.fecha);
      await this.router.navigate(['/cargas', d.resumen.idCarga]);
    } catch (e) {
      this.avisos.error(e);
    } finally {
      this.subiendo.set(false);
    }
  }
}
