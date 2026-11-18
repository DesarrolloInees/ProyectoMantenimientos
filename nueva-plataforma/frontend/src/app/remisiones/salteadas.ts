import { DatePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { Avisos } from '../core/infra';
import { Rest } from '../core/rest';

interface Salteada {
  idControl: number;
  numeroRemision: string;
  tecnico: string;
  fechaAsignacion: string | null;
  anterior: string;
  siguiente: string;
}

/** Remisiones disponibles entre dos usadas consecutivas: probablemente el técnico se las saltó (remisionesPendientes). */
@Component({
  selector: 'app-salteadas',
  imports: [DatePipe, MatButtonModule],
  template: `
    <div class="pagina">
      <div class="encabezado"><h1>Remisiones salteadas</h1></div>
      <p class="muted">Remisiones que siguen disponibles aunque la anterior y la siguiente del mismo técnico ya se usaron.</p>
      <div class="contenedor-tabla">
        <table class="tabla">
          <thead><tr><th>Técnico</th><th>Remisión</th><th>Entre</th><th>Asignada</th><th>Marcar como</th></tr></thead>
          <tbody>
            @for (s of lista(); track s.idControl) {
              <tr><td>{{ s.tecnico }}</td><td><strong>{{ s.numeroRemision }}</strong></td><td>{{ s.anterior }} y {{ s.siguiente }}</td>
                <td>{{ s.fechaAsignacion | date: 'dd/MM/yy' }}</td>
                <td class="botones-fila">
                  @for (e of estados; track e) { <button mat-stroked-button (click)="marcar(s, e)">{{ e }}</button> }
                </td></tr>
            } @empty { <tr><td colspan="5" class="vacio">No hay remisiones salteadas.</td></tr> }
          </tbody>
        </table>
      </div>
    </div>
  `,
  styles: `.botones-fila { gap: 4px; }`,
})
export class Salteadas implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected readonly estados = ['ANULADA', 'FALTANTE', 'USADA', 'ELIMINADO'];
  protected readonly lista = signal<Salteada[]>([]);

  ngOnInit(): void {
    this.cargar();
  }

  async cargar(): Promise<void> {
    try {
      this.lista.set(await this.rest.get<Salteada[]>('/api/remisiones/salteadas'));
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async marcar(s: Salteada, estado: string): Promise<void> {
    try {
      await this.rest.post(`/api/remisiones/${s.idControl}/estado`, {}, { estado });
      this.lista.update((l) => l.filter((x) => x.idControl !== s.idControl));
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
