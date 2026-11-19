import { Component, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { Api } from '../../core/api';
import { Titulo, fmt } from '../../core/ui';
import { SelectBuscar } from '../../shared/select-buscar';
import { DtAbajo, DtArriba, TablaDatos, Th } from '../../shared/tabla';

const COP = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });

/** Port de ordenVerVista.php */
@Component({
  selector: 'app-orden-ver',
  imports: [RouterLink, DtArriba, DtAbajo, Th],
  template: `
    <div class="w-full bg-white shadow-xl rounded-lg p-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h2 class="text-2xl font-bold text-gray-800">📅 Servicios Agrupados por Día</h2>
          <p class="text-gray-500 text-sm">Resumen financiero y operativo por delegación</p>
        </div>
        <a routerLink="/ordenCrear" class="bg-indigo-600 hover:bg-indigo-800 text-white font-bold py-2 px-4 rounded shadow transition"><i class="fas fa-plus mr-2"></i> Nuevo Reporte</a>
      </div>
      <div class="dt-wrapper">
        <app-dt-arriba [dt]="dt" />
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm" style="width: 100%">
            <thead class="bg-gray-800 text-white">
              <tr>
                <th app-th [dt]="dt" campo="fecha_visita" class="py-3 px-4">Fecha</th>
                <th class="py-3 px-4 w-1/3">Desglose por Delegación</th>
                <th app-th [dt]="dt" campo="cantidad_total" class="py-3 px-4 text-center">Total Serv.</th>
                <th app-th [dt]="dt" campo="valor_total_dia" class="py-3 px-4 text-right">$$ Total Día</th>
                <th class="py-3 px-4 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              @for (row of dt.pagina; track row.fecha_visita; let impar = $odd) {
                <tr [class.bg-gray-50]="impar" class="hover:bg-gray-100">
                  <td class="py-2 px-4"><div class="font-bold text-lg text-gray-700 text-center border p-2 rounded bg-gray-50">{{ row.fecha_visita }}</div></td>
                  <td class="py-2 px-4">
                    <ul class="text-xs text-left space-y-1">
                      @for (det of row.detalles_delegacion; track $index) {
                        <li class="flex justify-between border-b border-gray-100 pb-1 items-center">
                          <span class="font-bold text-gray-600 w-1/3 truncate" [title]="det.nombre">{{ det.nombre }}:</span>
                          <span class="flex items-center space-x-1">
                            <span class="bg-orange-100 text-orange-800 px-1.5 py-0.5 rounded border border-orange-200" title="Técnicos"><i class="fas fa-user-wrench text-[10px] mr-1"></i>{{ det.num_tecnicos }}</span>
                            <span class="bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200" title="Servicios">{{ det.cant }} serv.</span>
                            @if (rol !== 5) { <span class="text-green-600 font-medium ml-1">{{ cop(det.valor) }}</span> }
                          </span>
                        </li>
                      }
                    </ul>
                  </td>
                  <td class="py-2 px-4 text-center align-middle"><span class="bg-blue-100 text-blue-800 text-base py-1 px-3 rounded-full font-bold">{{ row.cantidad_total }}</span></td>
                  <td class="py-2 px-4 text-right align-middle text-base">
                    @if (rol === 5) {
                      <span class="text-gray-400 text-sm font-semibold bg-gray-100 px-2 py-1 rounded"><i class="fas fa-lock mr-1"></i>No Disponible</span>
                    } @else {
                      <span class="font-bold text-green-700">{{ cop(row.valor_total_dia) }}</span>
                    }
                  </td>
                  <td class="py-2 px-4 text-center align-middle">
                    <a [routerLink]="'/ordenDetalle/' + row.fecha_visita" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-2 px-6 rounded shadow transition hover:scale-105 inline-block"><i class="fas fa-eye mr-2"></i> Ver Detalle</a>
                  </td>
                </tr>
              } @empty {
                <tr><td colspan="5" class="text-center py-6 text-gray-400">{{ cargado ? 'Ningún dato disponible en esta tabla' : 'Cargando...' }}</td></tr>
              }
            </tbody>
          </table>
        </div>
        <app-dt-abajo [dt]="dt" />
      </div>
    </div>
  `
})
export class OrdenVer implements OnInit {
  private api = inject(Api);
  dt = new TablaDatos<any>([], 10, ['fecha_visita']);
  rol = 0;
  cargado = false;

  constructor() {
    inject(Titulo).set('Historial por Días');
  }

  cop(v: any) {
    return COP.format(Number(v) || 0);
  }

  async ngOnInit() {
    const info = await this.api.get('ordenVer', 'index');
    this.rol = info.rolUsuario;
    const r = await this.api.post('ordenVer', 'ajaxListar', {});
    this.dt.set(r.data);
    this.dt.campoOrden = 'fecha_visita';
    this.dt.ordenAsc = false;
    this.cargado = true;
  }
}

/** Port de ordenMovilVista.php (historial técnico móvil) */
@Component({
  selector: 'app-orden-movil',
  imports: [FormsModule, SelectBuscar],
  template: `
    <div class="w-full max-w-md mx-auto pb-24">
      <div class="bg-indigo-600 -mx-4 -mt-4 px-6 pt-8 pb-10 rounded-b-[2rem] shadow-lg mb-6 relative">
        <h2 class="text-white text-xl font-bold flex items-center"><i class="fas fa-mobile-alt mr-3 text-indigo-200"></i> Historial Técnico</h2>
        <p class="text-indigo-100 text-sm mt-1 ml-7 opacity-80">Consulta rápida de servicios anteriores</p>
      </div>
      <div class="px-2 space-y-5">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
          <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">1. Selecciona el Cliente</label>
          <app-select-buscar [(ngModel)]="idCliente" (cambio)="cambioCliente()" [opciones]="clientes" valor="id_cliente" texto="nombre_cliente" placeholder="-- Buscar Cliente... --" claseBoton="h-[52px] rounded-xl bg-gray-50" />
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
          <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">2. Selecciona el Punto</label>
          <app-select-buscar [(ngModel)]="idPunto" [opciones]="puntos" valor="id_punto" texto="nombre_punto" [placeholder]="placeholderPunto" [disabled]="!puntos.length" claseBoton="h-[52px] rounded-xl bg-gray-50" />
        </div>
        <button (click)="buscarServiciosMovil()" [disabled]="!idPunto"
          class="w-full text-white font-bold py-4 px-6 rounded-xl shadow transition-all transform flex justify-center items-center gap-2"
          [class]="idPunto ? 'bg-indigo-600 hover:bg-indigo-700 hover:scale-105 shadow-lg cursor-pointer' : 'bg-indigo-300 cursor-not-allowed'">
          <span>CONSULTAR</span> <i class="fas fa-arrow-right"></i>
        </button>
      </div>
      <div class="flex items-center gap-4 my-8 px-2">
        <div class="h-px bg-gray-300 flex-1"></div>
        <span class="text-gray-400 text-xs font-bold uppercase tracking-widest">Resultados</span>
        <div class="h-px bg-gray-300 flex-1"></div>
      </div>
      <div class="px-2 space-y-4 min-h-[200px]">
        @if (estado === 'inicial') {
          <div class="flex flex-col items-center justify-center text-gray-400 py-8 opacity-60">
            <i class="fas fa-clipboard-list text-5xl mb-4 text-gray-300"></i>
            <p class="text-sm text-center">Selecciona un cliente y un punto<br />para ver el historial.</p>
          </div>
        } @else if (estado === 'cargando') {
          <div class="flex flex-col items-center justify-center py-10"><i class="fas fa-circle-notch fa-spin text-4xl text-indigo-500 mb-3"></i><p class="text-gray-500 font-medium">Buscando historial...</p></div>
        } @else if (estado === 'error') {
          <p class="text-red-500 text-center">Error al consultar datos.</p>
        } @else if (!resultados.length) {
          <div class="p-4 bg-yellow-50 text-yellow-700 rounded-lg text-center border border-yellow-200 shadow-sm"><i class="fas fa-info-circle mb-2 text-2xl"></i><br />No se encontraron servicios anteriores en este punto.</div>
        } @else {
          @for (row of resultados; track row.id_ordenes_servicio) {
            <div class="bg-white rounded-xl shadow-md overflow-hidden mb-4 border-l-4" [class]="row.color_borde">
              <div class="bg-gray-50 px-4 py-3 border-b border-gray-100 flex justify-between items-center">
                <span class="font-bold text-gray-800 text-sm"><i class="far fa-calendar-alt text-gray-500 mr-1"></i> {{ f.fecha(row.fecha_visita) }}</span>
                <span class="text-xs font-bold px-2 py-1 rounded bg-white border border-gray-200 text-gray-600">{{ row.tipo_servicio }}</span>
              </div>
              <div class="p-4">
                <div class="flex items-center mb-3">
                  <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 mr-3"><i class="fas fa-user text-sm"></i></div>
                  <div><p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider">Técnico</p><p class="text-sm font-semibold text-gray-800 leading-tight">{{ row.nombre_tecnico }}</p></div>
                </div>
                <div class="mb-3">
                  <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider mb-1">Actividades / Observación</p>
                  <div class="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg border border-gray-100 whitespace-pre-line">{{ row.que_se_hizo || 'Sin observaciones.' }}</div>
                </div>
                @if (row.repuestos) {
                  <div class="mt-3 pt-3 border-t border-dashed border-gray-200">
                    <p class="text-xs font-bold text-gray-700 flex items-center gap-1 mb-1"><i class="fas fa-tools text-gray-400"></i> Repuestos:</p>
                    <p class="text-xs text-gray-500 pl-4">{{ row.repuestos }}</p>
                  </div>
                }
              </div>
            </div>
          }
        }
      </div>
    </div>
  `
})
export class OrdenMovil implements OnInit {
  private api = inject(Api);
  f = fmt;
  clientes: any[] = [];
  puntos: any[] = [];
  idCliente: any = '';
  idPunto: any = '';
  placeholderPunto = 'Esperando cliente...';
  resultados: any[] = [];
  estado: 'inicial' | 'cargando' | 'listo' | 'error' = 'inicial';

  constructor() {
    inject(Titulo).set('Consulta Técnica Móvil');
  }

  async ngOnInit() {
    this.clientes = (await this.api.get('ordenMovil', 'index')).listaClientes;
  }

  async cambioCliente() {
    this.idPunto = '';
    this.puntos = [];
    this.resultados = [];
    this.estado = 'inicial';
    if (!this.idCliente) {
      this.placeholderPunto = 'Esperando cliente...';
      return;
    }
    this.placeholderPunto = 'Cargando puntos...';
    try {
      const data = await this.api.postForm('ordenMovil', 'cargarPuntos', { id_cliente: this.idCliente });
      this.puntos = data;
      this.placeholderPunto = data.length ? '-- Escribe para buscar punto --' : 'Sin puntos asignados';
    } catch {
      alert('Error de conexión al cargar puntos');
    }
  }

  async buscarServiciosMovil() {
    if (!this.idCliente || !this.idPunto) return;
    this.estado = 'cargando';
    try {
      this.resultados = await this.api.postForm('ordenMovil', 'buscarHistorial', { id_cliente: this.idCliente, id_punto: this.idPunto });
      this.estado = 'listo';
    } catch {
      this.estado = 'error';
    }
  }
}
