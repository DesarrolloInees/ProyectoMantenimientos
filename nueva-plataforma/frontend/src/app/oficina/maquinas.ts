import { DatePipe } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSlideToggleModule } from '@angular/material/slide-toggle';
import { Api } from '../core/api';
import { Avisos } from '../core/infra';
import { Maquina } from '../core/modelos';

/** Marca máquinas VIP (prelación sobre las normales) y ajusta su frecuencia de mantenimiento. */
@Component({
  selector: 'app-maquinas',
  imports: [FormsModule, DatePipe, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule, MatSlideToggleModule],
  template: `
    <div class="pagina">
      <div class="encabezado"><h1>Máquinas VIP y frecuencias</h1></div>
      <div class="tarjeta">
        <mat-form-field appearance="outline" class="busqueda" subscriptSizing="dynamic">
          <mat-label>Buscar por ID de máquina, punto o cliente</mat-label>
          <input matInput [(ngModel)]="texto" (keyup.enter)="buscar()" />
          <button mat-icon-button matSuffix (click)="buscar()" aria-label="Buscar"><mat-icon>search</mat-icon></button>
        </mat-form-field>
      </div>
      <div class="tarjeta tabla">
        <table>
          <thead>
            <tr><th>Máquina</th><th>Punto</th><th>Última visita</th><th>VIP</th><th>Frecuencia (días)</th><th></th></tr>
          </thead>
          <tbody>
            @for (m of maquinas(); track m.idMaquina) {
              <tr>
                <td><strong>{{ m.deviceId }}</strong><br /><span class="muted">{{ m.tipoMaquina }}</span></td>
                <td>{{ m.punto }}<br /><span class="muted">{{ m.cliente }} · {{ m.municipio }}</span></td>
                <td>{{ m.ultimaVisita ? (m.ultimaVisita | date: 'd MMM y') : 'Sin visitas' }}</td>
                <td><mat-slide-toggle [(ngModel)]="m.esVip" [attr.aria-label]="'VIP ' + m.deviceId" /></td>
                <td>
                  <input class="frecuencia" type="number" min="1" [(ngModel)]="m.frecuenciaDias"
                         [placeholder]="'Tipo: ' + (m.frecuenciaTipo ?? 30)" />
                </td>
                <td><button mat-button (click)="guardar(m)">Guardar</button></td>
              </tr>
            } @empty {
              <tr><td colspan="6" class="vacio">Busque una máquina para configurarla.</td></tr>
            }
          </tbody>
        </table>
      </div>
    </div>
  `,
  styles: `
    .busqueda { width: min(560px, 100%); }
    .tabla { margin-top: 12px; padding: 0; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th, td { text-align: left; padding: 8px 12px; border-bottom: 1px solid var(--fondo); }
    th { font-size: 12px; color: var(--inees-gris); }
    .frecuencia { width: 110px; padding: 6px 8px; border: 1px solid var(--linea-2); border-radius: 6px; font: inherit; }
  `,
})
export class Maquinas {
  private readonly api = inject(Api);
  private readonly avisos = inject(Avisos);
  protected texto = '';
  protected readonly maquinas = signal<Maquina[]>([]);

  async buscar(): Promise<void> {
    if (this.texto.trim().length < 3) return;
    try {
      this.maquinas.set(await this.api.buscarMaquinas(this.texto));
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async guardar(m: Maquina): Promise<void> {
    try {
      await this.api.configurarMaquina(m.idMaquina, m.esVip, m.frecuenciaDias || null);
      this.avisos.ok(`Guardado: ${m.deviceId}${m.esVip ? ' (VIP)' : ''}`);
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
