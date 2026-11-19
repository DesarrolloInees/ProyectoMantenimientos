import { Component, OnDestroy, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { Api } from '../../core/api';
import { SelectBuscar } from '../../shared/select-buscar';
import { DtAbajo, DtArriba, TablaDatos, Th } from '../../shared/tabla';

/** Port de serviciosPdfVista.php (listado + descarga masiva en ZIP por lotes) */
@Component({
  selector: 'app-servicios-pdf',
  imports: [FormsModule, RouterLink, SelectBuscar, DtArriba, DtAbajo, Th],
  template: `
    <div class="w-full bg-white shadow-xl rounded-lg p-6">
      <div class="flex flex-col gap-2 md:flex-row md:justify-between md:items-center mb-6">
        <div>
          <h2 class="text-2xl font-bold text-gray-800">📄 Generación de PDFs por Servicio</h2>
          <p class="text-gray-500 text-sm">Filtra, previsualiza y descarga los servicios en un ZIP organizado por delegación y técnico.</p>
        </div>
        <div class="text-sm text-gray-500">Máximo por descarga: <strong>1000 servicios</strong></div>
      </div>

      <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Fecha inicio</label>
            <input type="date" [(ngModel)]="f.fecha_inicio" class="w-full p-2.5 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Fecha fin</label>
            <input type="date" [(ngModel)]="f.fecha_fin" class="w-full p-2.5 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Cliente</label>
            <app-select-buscar [opciones]="op.clientes" valor="id_cliente" texto="nombre_cliente" placeholder="Todos los clientes" [ngModel]="f.id_cliente" (cambio)="f.id_cliente = $event; cargarPuntos($event)" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Punto</label>
            <app-select-buscar [opciones]="puntos" valor="id_punto" texto="nombre_punto" [placeholder]="cargandoPuntos ? 'Cargando puntos...' : 'Todos los puntos'" [(ngModel)]="f.id_punto" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Técnico</label>
            <app-select-buscar [opciones]="op.tecnicos" valor="id_tecnico" texto="nombre_tecnico" placeholder="Todos los técnicos" [(ngModel)]="f.id_tecnico" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Delegación</label>
            <app-select-buscar [opciones]="op.delegaciones" valor="id_delegacion" texto="nombre_delegacion" placeholder="Todas las delegaciones" [(ngModel)]="f.id_delegacion" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tipo de mantenimiento</label>
            <app-select-buscar [opciones]="op.tipos" valor="id_tipo_mantenimiento" texto="nombre_completo" placeholder="Todos los tipos" [(ngModel)]="f.id_tipo_mantenimiento" />
          </div>
          <div class="flex items-end gap-2">
            <button type="button" (click)="aplicar()" class="flex-1 bg-blue-600 hover:bg-blue-800 text-white font-bold py-2.5 px-4 rounded shadow"><i class="fas fa-filter mr-1"></i> Aplicar filtros</button>
            <button type="button" (click)="limpiarFiltros()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2.5 px-4 rounded shadow">Limpiar</button>
          </div>
        </div>
        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3 mt-5 pt-4 border-t border-gray-200">
          <div class="text-sm text-gray-600 flex items-center gap-3">
            @if (sel.size > 0) {
              <span><strong>{{ sel.size }}</strong> servicios seleccionados manualmente.</span>
              <button type="button" (click)="limpiarSeleccion()" class="text-blue-600 hover:text-blue-800 underline font-bold">Limpiar selección</button>
            } @else {
              <span>Sin servicios seleccionados. Se descargará todo lo que coincida con los filtros.</span>
            }
          </div>
          <button type="button" (click)="descargarZip()" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg shadow flex items-center justify-center gap-2">
            <i class="fas fa-file-archive"></i>
            <span>{{ sel.size > 0 ? 'Descargar ZIP (' + sel.size + ' seleccionados)' : 'Descargar ZIP con filtros' }}</span>
          </button>
        </div>
        @if (mensaje) {
          <div class="mt-3 p-3 rounded text-sm font-bold text-center" [class]="claseMensaje">{{ mensaje }}</div>
        }
      </div>

      <div class="dt-wrapper">
        <app-dt-arriba [dt]="dt" />
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm" style="width: 100%">
            <thead class="bg-gray-800 text-white">
              <tr>
                <th class="py-3 px-4 text-center" style="width: 40px"><input type="checkbox" class="cursor-pointer" title="Seleccionar todos los resultados" [checked]="todos" (change)="marcarTodos($any($event.target).checked)" /></th>
                <th app-th [dt]="dt" campo="id_ordenes_servicio" class="py-3 px-4 text-center">ID</th>
                <th app-th [dt]="dt" campo="numero_remision" class="py-3 px-4 text-center">Remisión</th>
                <th app-th [dt]="dt" campo="fecha_visita" class="py-3 px-4 text-center">Fecha Visita</th>
                <th app-th [dt]="dt" campo="nombre_cliente" class="py-3 px-4">Cliente / Punto</th>
                <th app-th [dt]="dt" campo="nombre_delegacion" class="py-3 px-4">Delegación</th>
                <th app-th [dt]="dt" campo="nombre_tecnico" class="py-3 px-4">Técnico</th>
                <th class="py-3 px-4 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              @for (r of dt.pagina; track r.id_ordenes_servicio; let impar = $odd) {
                <tr [class.bg-gray-50]="impar" class="hover:bg-gray-100 border-b">
                  <td class="text-center align-middle py-2"><input type="checkbox" class="cursor-pointer" [checked]="sel.has('' + r.id_ordenes_servicio)" (change)="alternar(r, $any($event.target).checked)" /></td>
                  <td class="text-center font-bold text-gray-600">{{ r.id_ordenes_servicio }}</td>
                  <td class="text-center font-bold text-indigo-600">{{ r.numero_remision }}</td>
                  <td class="text-center">{{ r.fecha_visita }}</td>
                  <td class="px-4">
                    <div class="font-bold text-gray-800">{{ r.nombre_cliente || 'SIN CLIENTE' }}</div>
                    <div class="text-xs text-gray-500">{{ r.nombre_punto || 'SIN PUNTO' }}</div>
                  </td>
                  <td class="text-sm text-gray-700 px-4">{{ r.nombre_delegacion }}</td>
                  <td class="text-sm text-gray-700 px-4">{{ r.nombre_tecnico }}</td>
                  <td class="text-center align-middle py-2">
                    <div class="flex flex-col gap-2">
                      <a [href]="urlPdf(r)" target="_blank" class="bg-red-600 hover:bg-red-800 text-white font-bold py-1 px-3 rounded shadow text-xs text-center inline-block"><i class="fas fa-file-pdf"></i> PDF</a>
                      <a [routerLink]="['/servicioEditar', r.id_ordenes_servicio]" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-1 px-3 rounded shadow text-xs text-center inline-block"><i class="fas fa-edit"></i> Completar</a>
                    </div>
                  </td>
                </tr>
              } @empty {
                <tr><td colspan="8" class="text-center py-6 text-gray-400">{{ cargando ? 'Cargando...' : 'Ningún dato disponible en esta tabla' }}</td></tr>
              }
            </tbody>
          </table>
        </div>
        <app-dt-abajo [dt]="dt" />
      </div>
    </div>

    @if (modal) {
      <div class="fixed inset-0 bg-black bg-opacity-60 z-[99999] flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="h-10 w-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center"><i class="fas fa-file-pdf"></i></div>
            <div>
              <h3 class="text-lg font-bold text-gray-800">Generando ZIP de servicios</h3>
              <p class="text-xs text-gray-500">No cierres esta ventana mientras se generan los PDFs.</p>
            </div>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden mb-3">
            <div class="h-4 rounded-full transition-all duration-200" [class]="fallo ? 'bg-red-600' : 'bg-blue-600'" [style.width.%]="porcentaje"></div>
          </div>
          <div class="flex justify-between text-sm text-gray-600 mb-4">
            <span>{{ progresoTexto }}</span>
            <span>{{ procesados }} / {{ total }}</span>
          </div>
          <div class="text-xs text-gray-500 truncate mb-4">{{ progresoDetalle }}</div>
          <div class="flex justify-end gap-2">
            <button type="button" (click)="cancelar()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded">{{ fallo ? 'Cerrar' : 'Cancelar' }}</button>
          </div>
        </div>
      </div>
    }
  `
})
export class ServiciosPdf implements OnInit, OnDestroy {
  private api = inject(Api);

  op: any = { clientes: [], tecnicos: [], delegaciones: [], tipos: [] };
  puntos: any[] = [];
  cargandoPuntos = false;
  f: any = { fecha_inicio: '', fecha_fin: '', id_cliente: '', id_punto: '', id_tecnico: '', id_delegacion: '', id_tipo_mantenimiento: '' };
  dt = new TablaDatos<any>([], 10);
  cargando = false;
  sel = new Set<string>();
  mensaje = '';
  claseMensaje = '';

  modal = false;
  fallo = false;
  procesando = false;
  cancelado = false;
  token: string | null = null;
  procesados = 0;
  total = 0;
  progresoTexto = 'Preparando descarga...';
  progresoDetalle = '';
  private destruido = false;

  get porcentaje() {
    if (this.fallo) return 100;
    return this.total > 0 ? Math.min(100, Math.round((this.procesados / this.total) * 100)) : 0;
  }

  get todos() {
    return this.sel.size > 0 && this.dt.filtrados.length > 0 && this.dt.filtrados.every((r) => this.sel.has(String(r.id_ordenes_servicio)));
  }

  async ngOnInit() {
    this.dt.campoOrden = 'fecha_visita';
    this.dt.ordenAsc = false;
    const r: any = await this.api.get('serviciosPdf');
    this.op = r.opciones || this.op;
    this.cargarPuntos('');
    this.listar();
  }

  ngOnDestroy() {
    this.destruido = true;
    this.cancelado = true;
  }

  private filtros() {
    const o: Record<string, any> = {};
    Object.entries(this.f).forEach(([k, v]) => (o[k] = v ?? ''));
    return o;
  }

  async listar() {
    this.cargando = true;
    try {
      const r: any = await this.api.postForm('serviciosPdf', 'ajaxListar', this.filtros());
      this.dt.set(r.data || []);
    } finally {
      this.cargando = false;
    }
  }

  async cargarPuntos(idCliente: any) {
    this.f.id_punto = '';
    this.puntos = [];
    this.cargandoPuntos = true;
    try {
      const r: any = await this.api.postForm('serviciosPdf', 'ajaxPuntos', { id_cliente: idCliente || '' });
      this.puntos = r.data || [];
    } catch {
      this.puntos = [];
    } finally {
      this.cargandoPuntos = false;
    }
  }

  private mostrar(texto: string, tipo: 'ok' | 'warning' | 'error' = 'ok') {
    this.mensaje = texto;
    this.claseMensaje = tipo === 'ok' ? 'bg-green-100 text-green-700' : tipo === 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-700';
  }

  private fechasOk(): boolean {
    if (this.f.fecha_inicio && this.f.fecha_fin && this.f.fecha_inicio > this.f.fecha_fin) {
      this.mostrar('La fecha de inicio no puede ser mayor que la fecha fin.', 'error');
      return false;
    }
    return true;
  }

  aplicar() {
    if (!this.fechasOk()) return;
    this.mostrar('');
    this.limpiarSeleccion();
    this.listar();
  }

  limpiarFiltros() {
    this.f = { fecha_inicio: '', fecha_fin: '', id_cliente: '', id_punto: '', id_tecnico: '', id_delegacion: '', id_tipo_mantenimiento: '' };
    this.cargarPuntos('');
    this.mostrar('');
    this.limpiarSeleccion();
    this.listar();
  }

  limpiarSeleccion() {
    this.sel = new Set();
  }

  alternar(r: any, marcar: boolean) {
    const id = String(r.id_ordenes_servicio);
    if (marcar) this.sel.add(id);
    else this.sel.delete(id);
    this.sel = new Set(this.sel);
  }

  marcarTodos(marcar: boolean) {
    this.dt.filtrados.forEach((r) => (marcar ? this.sel.add(String(r.id_ordenes_servicio)) : this.sel.delete(String(r.id_ordenes_servicio))));
    this.sel = new Set(this.sel);
  }

  urlPdf(r: any) {
    return this.api.url('pdfServicio', 'generar', { id: r.id_ordenes_servicio });
  }

  // ================================================================ ZIP
  async descargarZip() {
    if (this.procesando) return;
    if (!this.fechasOk()) return;
    const datos: Record<string, any> = this.filtros();
    if (this.sel.size > 0) datos['ids'] = Array.from(this.sel);
    this.procesando = true;
    this.cancelado = false;
    this.token = null;
    this.mostrar('');
    this.fallo = false;
    this.procesados = 0;
    this.total = 0;
    this.progresoTexto = 'Preparando descarga...';
    this.progresoDetalle = '';
    this.modal = true;
    try {
      const r: any = await this.api.postForm('serviciosPdf', 'crearTrabajoZip', datos);
      if (!r || r.status !== 'ok') return this.fallar(r?.msg || 'No se pudo iniciar la descarga.');
      this.token = r.token;
      if (r.advertencia) this.mostrar(r.advertencia, 'warning');
      this.progreso(0, r.total, 'Iniciando generación de PDFs...');
      this.procesarLotes();
    } catch {
      this.fallar('No se pudo iniciar el trabajo de descarga.');
    }
  }

  private progreso(p: number, t: number, detalle: string) {
    this.procesados = p;
    this.total = t;
    this.progresoTexto = this.porcentaje + '% completado';
    this.progresoDetalle = detalle || '';
  }

  private async procesarLotes() {
    if (this.cancelado) return;
    try {
      const r: any = await this.api.postForm('serviciosPdf', 'procesarLoteZip', { token: this.token, lote: 3 });
      if (this.cancelado) return;
      if (!r || r.status !== 'ok') return this.fallar(r?.msg || 'Error desconocido al generar los PDFs.');
      this.progreso(r.procesados, r.total, r.actual ? 'Generando: ' + r.actual : 'Generando PDFs...');
      if (r.completado) return this.descargarTrabajo();
      setTimeout(() => this.procesarLotes(), 80);
    } catch {
      if (!this.cancelado) this.fallar('Se perdió la conexión con el servidor durante la generación.');
    }
  }

  private descargarTrabajo() {
    this.progresoTexto = 'Comprimiendo ZIP...';
    this.progresoDetalle = 'La descarga comenzará automáticamente.';
    window.location.href = this.api.url('serviciosPdf', 'descargarZipTrabajo', { token: this.token });
    setTimeout(() => {
      if (this.destruido) return;
      this.finalizar();
      this.mostrar('ZIP generado correctamente. Revisa las descargas de tu navegador.', 'ok');
    }, 4000);
  }

  private fallar(msg: string) {
    this.procesando = false;
    this.fallo = true;
    this.progresoTexto = 'No se pudo completar la descarga';
    this.progresoDetalle = msg || 'Intenta de nuevo con menos servicios.';
    if (this.token) {
      this.api.postForm('serviciosPdf', 'cancelarTrabajoZip', { token: this.token }).catch(() => {});
      this.token = null;
    }
  }

  private finalizar() {
    this.procesando = false;
    this.token = null;
    this.modal = false;
  }

  cancelar() {
    if (!this.procesando) {
      this.modal = false;
      return;
    }
    this.cancelado = true;
    if (this.token) this.api.postForm('serviciosPdf', 'cancelarTrabajoZip', { token: this.token }).catch(() => {});
    this.finalizar();
    this.mostrar('Descarga cancelada.', 'warning');
  }
}
