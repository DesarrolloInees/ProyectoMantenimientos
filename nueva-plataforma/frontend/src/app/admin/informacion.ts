import { Component, inject, OnInit, signal } from '@angular/core';
import { Barras, Kpi, Matriz } from '../compartido/graficos';
import { Avisos } from '../core/infra';
import { Rest } from '../core/rest';

type Fila = Record<string, any>;

/** Resumen de la base de datos: totales, máquinas por delegación y tipo, principales clientes y antigüedad de visitas. */
@Component({
  selector: 'app-informacion',
  imports: [Kpi, Barras, Matriz],
  template: `
    <div class="pagina">
      <div class="encabezado"><h1>Información de la base de datos</h1></div>
      @if (d(); as d) {
        <div class="kpis">
          <app-kpi [valor]="d['resumen']['maquinas_activas']" texto="Máquinas activas" [detalle]="d['resumen']['total_maquinas'] + ' en total'" />
          <app-kpi [valor]="d['resumen']['fuera_de_servicio']" texto="Fuera de servicio" />
          <app-kpi [valor]="d['resumen']['sin_ubicacion']" texto="Máquinas sin ubicación" [alerta]="d['resumen']['sin_ubicacion'] > 0" />
          <app-kpi [valor]="d['resumen']['puntos_activos']" texto="Puntos activos" [detalle]="d['resumen']['total_puntos'] + ' en total'" />
          <app-kpi [valor]="d['resumen']['clientes']" texto="Clientes activos" />
          <app-kpi [valor]="d['resumen']['delegaciones']" texto="Delegaciones" />
          <app-kpi [valor]="d['vencidas']['total']" texto="Máquinas con mantenimiento vencido" [detalle]="d['vencidas']['vip'] + ' VIP'" />
          <app-kpi [valor]="d['resumen']['servicios_realizados']" texto="Servicios realizados" />
        </div>
        <section class="paneles">
          <div class="tarjeta"><app-barras titulo="Clientes con más máquinas" [barras]="barras(d['topClientes'], 'cliente', 'maquinas')" /></div>
          <div class="tarjeta"><app-barras titulo="Antigüedad de la última visita" [barras]="barras(d['antiguedadVisitas'], 'rango', 'maquinas')" color="#368aba" /></div>
        </section>
        <section class="tarjeta"><app-matriz titulo="Máquinas por delegación y tipo" nombreFila="Delegación" [datos]="d['porDelegacionYTipo']" /></section>
      }
    </div>
  `,
})
export class Informacion implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected readonly d = signal<Record<string, any> | null>(null);

  async ngOnInit(): Promise<void> {
    try {
      this.d.set(await this.rest.get('/api/admin/informacion-base'));
    } catch (e) {
      this.avisos.error(e);
    }
  }

  protected barras(filas: Fila[], etiqueta: string, valor: string) {
    return (filas ?? []).map((f) => ({ etiqueta: String(f[etiqueta]), valor: Number(f[valor]) }));
  }
}
