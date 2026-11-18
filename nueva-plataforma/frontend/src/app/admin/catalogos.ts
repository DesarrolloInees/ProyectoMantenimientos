import { Component, inject, OnInit, signal } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { RouterLink } from '@angular/router';
import { Avisos } from '../core/infra';
import { Rest } from '../core/rest';

const ICONOS: Record<string, string> = {
  clientes: 'storefront', puntos: 'place', maquinas: 'point_of_sale', tecnicos: 'engineering', 'tipos-maquina': 'category',
  'tipos-mantenimiento': 'handyman', calificaciones: 'grade', delegaciones: 'map', municipios: 'location_city', 'estados-maquina': 'toggle_on',
  modalidades: 'alt_route', 'tipos-novedad': 'report', festivos: 'event', repuestos: 'build', 'estados-remision': 'receipt',
  tarifas: 'sell', roles: 'badge', parametros: 'settings',
};

/** Índice de catálogos administrables. */
@Component({
  selector: 'app-catalogos',
  imports: [RouterLink, MatIconModule],
  template: `
    <div class="pagina">
      <div class="encabezado"><h1>Catálogos</h1></div>
      <div class="cuadros">
        @for (c of catalogos(); track c.id) {
          <a class="tarjeta cuadro" [routerLink]="['/admin/c', c.id]"><mat-icon>{{ iconos[c.id] ?? 'list' }}</mat-icon>{{ c.titulo }}</a>
        }
      </div>
    </div>
  `,
  styles: `
    .cuadros { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; }
    .cuadro { display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--inees-oscuro); font-weight: 500; }
    .cuadro:hover { background: var(--marca-azul-t); border-color: var(--marca-azul-c); }
    mat-icon { color: var(--inees-azul); }
  `,
})
export class Catalogos implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected readonly iconos = ICONOS;
  protected readonly catalogos = signal<{ id: string; titulo: string }[]>([]);

  async ngOnInit(): Promise<void> {
    try {
      this.catalogos.set(await this.rest.get('/api/admin/catalogos'));
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
