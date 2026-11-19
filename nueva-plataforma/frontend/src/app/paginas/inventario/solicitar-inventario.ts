import { Component, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { Api } from '../../core/api';
import { SelectBuscar } from '../../shared/select-buscar';
import { DtAbajo, DtArriba, TablaDatos, Th } from '../../shared/tabla';

function fechaHora(f: any) {
  const s = String(f || '');
  const [d, t] = s.split(' ');
  const p = (d || '').split('-');
  return p.length === 3 ? `${p[2]}/${p[1]}/${p[0]} ${(t || '').substring(0, 5)}` : s;
}

/** Port de solicitarInventarioTableroVista.php + solicitarInventarioVista.php */
@Component({
  selector: 'app-solicitar-inventario',
  imports: [FormsModule, RouterLink, SelectBuscar, DtArriba, DtAbajo, Th],
  template: `
    @if (r?.vista === 'tablero') {
      <div class="w-full px-4 md:px-6 py-4">
        <div class="bg-white p-6 md:p-8 rounded-xl shadow-lg border border-gray-200">
          <div class="mb-6 border-b pb-4 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
              <h1 class="text-2xl font-bold text-gray-800 flex items-center"><i class="fas fa-tasks text-indigo-600 mr-3"></i> Tablero de Solicitudes de Inventario</h1>
              <p class="text-gray-500 mt-1 text-sm">Gestiona y actualiza el estado de los repuestos solicitados por el equipo técnico.</p>
            </div>
            <a routerLink="/solicitarInventario" [queryParams]="{ vista: 'crear' }" class="text-xs bg-purple-50 hover:bg-purple-100 text-purple-700 font-bold px-3 py-2 rounded-lg transition flex items-center gap-1 border border-purple-200"><i class="fas fa-plus"></i> Nueva Solicitud</a>
          </div>
          @if (r.mensajeExito) { <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm"><i class="fas fa-check-circle mr-2"></i> {{ r.mensajeExito }}</div> }
          @if (r.errores?.length) { <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm"><p class="font-bold">Error:</p><ul class="list-disc list-inside ml-4 text-sm mt-1">@for (e of r.errores; track $index) {<li>{{ e }}</li>}</ul></div> }
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 flex items-center justify-between"><div><p class="text-xs text-indigo-600 font-semibold uppercase tracking-wider">Total Solicitudes</p><h3 class="text-2xl font-black text-indigo-900 mt-1">{{ dt.datos.length }}</h3></div><div class="bg-indigo-200 text-indigo-700 p-3 rounded-lg"><i class="fas fa-boxes-stacked text-xl"></i></div></div>
            <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 flex items-center justify-between"><div><p class="text-xs text-amber-600 font-semibold uppercase tracking-wider">Pendientes</p><h3 class="text-2xl font-black text-amber-900 mt-1">{{ contar('Pendiente') }}</h3></div><div class="bg-amber-200 text-amber-700 p-3 rounded-lg"><i class="fas fa-clock text-xl"></i></div></div>
            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 flex items-center justify-between"><div><p class="text-xs text-blue-600 font-semibold uppercase tracking-wider">En Gestión</p><h3 class="text-2xl font-black text-blue-900 mt-1">{{ contar('En gestión') }}</h3></div><div class="bg-blue-200 text-blue-700 p-3 rounded-lg"><i class="fas fa-spinner text-xl"></i></div></div>
            <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 flex items-center justify-between"><div><p class="text-xs text-emerald-600 font-semibold uppercase tracking-wider">Finalizadas</p><h3 class="text-2xl font-black text-emerald-900 mt-1">{{ contar('Finalizado') }}</h3></div><div class="bg-emerald-200 text-emerald-700 p-3 rounded-lg"><i class="fas fa-check-double text-xl"></i></div></div>
          </div>
          <app-dt-arriba [dt]="dt" />
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead><tr class="bg-gray-100 text-gray-700 uppercase text-xs tracking-wider">
                <th app-th [dt]="dt" campo="id_solicitud" class="p-4 border-b">ID</th><th app-th [dt]="dt" campo="solicitante_nombre" class="p-4 border-b">Solicitante</th><th app-th [dt]="dt" campo="fecha_solicitud" class="p-4 border-b">Fecha y Hora</th>
                <th class="p-4 border-b">Repuestos Solicitados</th><th class="p-4 border-b">Observaciones</th><th app-th [dt]="dt" campo="estado" class="p-4 border-b text-center">Estado</th><th class="p-4 border-b text-center">Cambiar Estado</th>
              </tr></thead>
              <tbody class="text-sm divide-y divide-gray-100">
                @for (s of dt.pagina; track s.id_solicitud) {
                  <tr class="hover:bg-gray-50 transition">
                    <td class="p-4 font-mono font-bold text-indigo-700">#{{ s.id_solicitud }}</td>
                    <td class="p-4 font-bold text-gray-800"><div class="flex items-center"><i class="fas fa-user-circle text-gray-400 mr-2 text-lg"></i> {{ s.solicitante_nombre }}</div></td>
                    <td class="p-4 text-gray-500 text-xs whitespace-nowrap">{{ fechaHora(s.fecha_solicitud) }}</td>
                    <td class="p-4"><ul class="space-y-1">
                      @for (d of s.detalles; track $index) {
                        <li class="text-xs text-gray-800 flex items-center gap-1.5"><span class="bg-indigo-100 text-indigo-800 font-bold px-2 py-0.5 rounded-md text-[11px]">{{ +d.cantidad }}x</span><span class="font-medium">{{ d.nombre_repuesto }}</span>@if (d.codigo_referencia) {<span class="text-gray-400 text-[10px]">(Ref: {{ d.codigo_referencia }})</span>}</li>
                      }
                    </ul></td>
                    <td class="p-4 text-xs text-gray-600 max-w-xs italic">@if (s.observaciones) { {{ s.observaciones }} } @else { <span class="text-gray-300">Sin observaciones</span> }</td>
                    <td class="p-4 text-center"><span class="inline-block px-3 py-1 text-xs font-bold rounded-full border" [class]="badge(s.estado)">{{ s.estado || 'Pendiente' }}</span></td>
                    <td class="p-4 text-center">
                      <select [ngModel]="s.estado || 'Pendiente'" (ngModelChange)="cambiarEstado(s, $event)" class="text-xs border border-gray-300 rounded-lg px-2.5 py-1.5 bg-white font-semibold shadow-sm hover:border-indigo-500 cursor-pointer">
                        <option value="Pendiente">Pendiente</option><option value="En gestión">En gestión</option><option value="Finalizado">Finalizado</option>
                      </select>
                    </td>
                  </tr>
                }
              </tbody>
            </table>
          </div>
          <app-dt-abajo [dt]="dt" />
        </div>
      </div>
    } @else if (r) {
      <div class="w-full px-4 md:px-6">
        <div class="bg-white p-6 md:p-8 rounded-xl shadow-lg border border-gray-200 max-w-5xl mx-auto">
          <div class="mb-6 border-b pb-4 flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-bold text-gray-800 flex items-center"><i class="fas fa-clipboard-list text-purple-600 mr-3"></i> Solicitud de Inventario / Repuestos</h1>
              <p class="text-gray-500 mt-1 text-sm">Registra y solicita repuestos requeridos directamente a logística y almacén.</p>
            </div>
            <div class="flex items-center gap-2">
              @if (!r.esTecnico) { <a routerLink="/solicitarInventario" class="text-xs bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold px-3 py-2 rounded-lg transition flex items-center gap-1 border border-indigo-200"><i class="fas fa-list"></i> Ver Tablero</a> }
              <a routerLink="/inicio" class="text-gray-400 hover:text-gray-600 transition"><i class="fas fa-times text-xl"></i></a>
            </div>
          </div>
          @if (r.mensajeExito) {
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center justify-between">
              <div><i class="fas fa-check-circle mr-2 text-lg"></i> {{ r.mensajeExito }}</div>
              <a [routerLink]="r.esTecnico ? '/inicio' : '/solicitarInventario'" class="text-xs bg-green-200 hover:bg-green-300 text-green-800 font-bold px-3 py-1.5 rounded-lg transition">{{ r.esTecnico ? 'Volver al Inicio' : 'Ir al Tablero' }}</a>
            </div>
          }
          @if (r.errores?.length) { <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm"><p class="font-bold">Hubo errores en la solicitud:</p><ul class="list-disc list-inside ml-4 text-sm mt-1">@for (e of r.errores; track $index) {<li>{{ e }}</li>}</ul></div> }
          <form (ngSubmit)="enviar()">
            <div class="bg-purple-50 p-4 rounded-lg mb-6 border border-purple-100 shadow-sm">
              <label class="block text-sm font-bold text-purple-900 mb-1"><i class="fas fa-user-check text-purple-600 mr-1"></i> Técnico / Usuario Solicitante</label>
              <p class="text-xs text-purple-600 mb-2">Usuario activo en la sesión del sistema (No modificable).</p>
              <div class="w-full bg-white border border-purple-200 text-purple-900 font-bold text-base rounded-lg px-4 py-3 cursor-not-allowed select-none shadow-inner flex items-center justify-between">
                <span><i class="fas fa-id-badge text-purple-500 mr-2"></i> {{ r.nombreUsuarioLogueado }}</span>
                <span class="text-xs bg-purple-100 text-purple-700 px-2.5 py-1 rounded-full uppercase tracking-wider font-semibold">Sesión Activa</span>
              </div>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg mb-6 border border-gray-200 shadow-sm">
              <label class="block text-sm font-bold text-gray-700 mb-1"><i class="fas fa-envelope text-indigo-500 mr-1"></i> Correos de Notificación Corporativos</label>
              <p class="text-xs text-gray-500 mb-2">Al registrar la solicitud, se enviará una notificación automática por correo electrónico a las siguientes direcciones:</p>
              <input type="text" readonly disabled [value]="r.destinatarios.join(', ')" class="w-full bg-gray-100 border border-gray-300 text-gray-700 text-xs font-mono rounded-lg px-3 py-2.5 cursor-not-allowed select-none">
            </div>
            <div class="mb-6">
              <label class="block text-sm font-bold text-gray-700 mb-2"><i class="fas fa-boxes-stacked text-indigo-500 mr-1"></i> Repuestos o Insumos Solicitados</label>
              <table class="w-full text-left border-collapse">
                <thead><tr class="text-xs text-gray-500 uppercase border-b bg-gray-50"><th class="py-2.5 px-3 w-2/3 font-semibold">Repuesto / Insumo</th><th class="py-2.5 px-3 w-28 text-center font-semibold">Cantidad</th><th class="py-2.5 px-3 w-10 text-center"></th></tr></thead>
                <tbody>
                  @for (f of filas; track f.k; let i = $index) {
                    <tr class="border-b border-gray-100">
                      <td class="py-2 pr-2"><app-select-buscar [name]="'rep' + f.k" [opciones]="r.listaRepuestos" valor="id_repuesto" [texto]="textoRep" placeholder="- Buscar Repuesto -" [(ngModel)]="f.id" /></td>
                      <td class="py-2 px-2"><input type="number" [name]="'cant' + f.k" min="1" [(ngModel)]="f.cant" required class="w-full h-[42px] border border-gray-300 rounded-lg text-center font-bold text-purple-700"></td>
                      <td class="py-2 text-center"><button type="button" (click)="quitar(i)" class="text-red-400 hover:text-red-600 p-2"><i class="fas fa-trash-alt"></i></button></td>
                    </tr>
                  }
                </tbody>
              </table>
            </div>
            <div class="flex justify-center mb-6"><button type="button" (click)="agregar()" class="text-sm bg-purple-50 hover:bg-purple-100 text-purple-700 font-semibold py-2.5 px-5 rounded-full border border-purple-200 transition flex items-center shadow-sm"><i class="fas fa-plus-circle text-purple-600 mr-2"></i> Agregar otro repuesto</button></div>
            <div class="mb-6">
              <label class="block text-sm font-bold text-gray-700 mb-1"><i class="fas fa-comment-alt text-gray-500 mr-1"></i> Observaciones o Notas Adicionales (Opcional)</label>
              <textarea name="obs" [(ngModel)]="observaciones" rows="3" placeholder="Ingresa detalles sobre la urgencia, motivo o especificaciones del repuesto..." class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500"></textarea>
            </div>
            <div class="pt-6 border-t border-gray-100 flex justify-end space-x-3">
              <a routerLink="/inicio" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50 transition">Cancelar</a>
              <button type="submit" [disabled]="enviando" class="px-8 py-3 bg-purple-600 text-white font-bold rounded-lg shadow-md hover:bg-purple-700 transform hover:-translate-y-1 transition-all flex items-center"><i class="fas fa-paper-plane mr-2"></i> Finalizar Solicitud</button>
            </div>
          </form>
        </div>
      </div>
    }
  `
})
export class SolicitarInventario implements OnInit {
  private api = inject(Api);
  private ruta = inject(ActivatedRoute);
  r: any = null;
  dt = new TablaDatos<any>([], 10);
  filas: { k: number; id: any; cant: any }[] = [];
  observaciones = '';
  enviando = false;
  private sec = 0;
  fechaHora = fechaHora;
  textoRep = (o: any) => `${o.nombre_repuesto} ${o.codigo_referencia ? '(' + o.codigo_referencia + ')' : ''}`;

  ngOnInit() {
    this.dt.campoOrden = 'id_solicitud';
    this.dt.ordenAsc = false;
    this.ruta.queryParamMap.subscribe(async (q) => this.aplicar(await this.api.get('solicitarInventario', 'index', q.get('vista') ? { vista: q.get('vista') } : {})));
  }
  private aplicar(r: any) {
    this.r = r;
    if (r.vista === 'tablero') this.dt.set(r.solicitudes || []);
    else if (!this.filas.length || r.mensajeExito) {
      this.filas = [];
      this.observaciones = '';
      this.agregar();
    }
  }
  contar(e: string) {
    return this.dt.datos.filter((s: any) => (s.estado || 'Pendiente') === e).length;
  }
  badge(e: string) {
    if (e === 'En gestión') return 'bg-blue-100 text-blue-800 border-blue-300';
    if (e === 'Finalizado') return 'bg-emerald-100 text-emerald-800 border-emerald-300';
    return 'bg-amber-100 text-amber-800 border-amber-300';
  }
  async cambiarEstado(s: any, estado: string) {
    this.aplicar(await this.api.postForm('solicitarInventario', 'cambiarEstado', { id_solicitud: s.id_solicitud, nuevo_estado: estado }));
  }
  agregar() {
    this.filas.push({ k: this.sec++, id: null, cant: 1 });
  }
  quitar(i: number) {
    if (this.filas.length > 1) this.filas.splice(i, 1);
    else alert('Debe haber al menos un repuesto en la solicitud.');
  }
  async enviar() {
    if (this.filas.some((f) => !f.id || !f.cant)) {
      alert('Selecciona el repuesto y la cantidad en cada fila.');
      return;
    }
    this.enviando = true;
    try {
      const r: any = await this.api.postForm('solicitarInventario', 'crearSolicitud', {
        vista: 'crear',
        repuestos: this.filas.map((f) => f.id),
        cantidades: this.filas.map((f) => f.cant),
        observaciones: this.observaciones
      });
      this.aplicar(r);
    } finally {
      this.enviando = false;
    }
  }
}
