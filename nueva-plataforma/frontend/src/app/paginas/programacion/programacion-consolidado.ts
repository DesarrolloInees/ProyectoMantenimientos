import { Component, HostListener, OnDestroy, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import * as XLSX from 'xlsx';
import { Api } from '../../core/api';
import { SelectBuscar } from '../../shared/select-buscar';

function fechaLocal(f: string | null | undefined): Date | null {
  if (!f || String(f).startsWith('0000')) return null;
  const p = String(f).split(' ')[0].split('-');
  return p.length === 3 ? new Date(+p[0], +p[1] - 1, +p[2]) : null;
}

/** Port de programacionConsolidadoVista.php + js/programacion/{consolidado-export,consolidado-editar}.js */
@Component({
  selector: 'app-programacion-consolidado',
  imports: [FormsModule, SelectBuscar],
  template: `
    <div class="w-full max-w-7xl mx-auto">
      <div class="bg-white p-6 rounded-xl shadow-md border-t-4 border-indigo-600 mb-6">
        <div class="flex flex-col md:flex-row items-end gap-4">
          <div class="w-full md:w-1/5"><label class="block text-sm font-semibold text-gray-700 mb-1">Fecha Inicio</label><input type="date" [(ngModel)]="f.fecha_inicio" class="w-full border border-gray-300 rounded-lg shadow-sm p-2" /></div>
          <div class="w-full md:w-1/5"><label class="block text-sm font-semibold text-gray-700 mb-1">Fecha Fin</label><input type="date" [(ngModel)]="f.fecha_fin" class="w-full border border-gray-300 rounded-lg shadow-sm p-2" /></div>
          <div class="w-full md:w-1/5">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Delegación (Opcional)</label>
            <select [(ngModel)]="f.delegacion" class="w-full border border-gray-300 rounded-lg shadow-sm bg-white p-2">
              <option value="">-- Todas --</option>
              @for (d of delegaciones; track d.id_delegacion) { <option [value]="'' + d.id_delegacion">{{ d.nombre_delegacion }}</option> }
            </select>
          </div>
          <div class="w-full md:w-1/5">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Técnico (Opcional)</label>
            <select [(ngModel)]="f.tecnico" class="w-full border border-gray-300 rounded-lg shadow-sm bg-white p-2">
              <option value="">-- Todos --</option>
              @for (t of tecnicos; track t.id_tecnico) { <option [value]="'' + t.id_tecnico">{{ t.nombre_tecnico }}</option> }
            </select>
          </div>
          <div class="w-full md:w-1/5 flex gap-2"><button type="button" (click)="buscar()" class="w-full bg-indigo-600 text-white font-bold py-2 px-4 rounded-lg shadow hover:bg-indigo-700 transition"><i class="fas fa-search mr-1"></i> Buscar</button></div>
        </div>
      </div>

      @if (datos.length) {
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4"><p class="text-xs text-gray-500 font-semibold uppercase">Servicios</p><p class="text-2xl font-bold text-indigo-700">{{ datos.length }}</p></div>
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4"><p class="text-xs text-gray-500 font-semibold uppercase">Técnicos Activos</p><p class="text-2xl font-bold text-indigo-700">{{ resumen.tecnicos }}</p></div>
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4"><p class="text-xs text-gray-500 font-semibold uppercase">Zonas Cubiertas</p><p class="text-2xl font-bold text-indigo-700">{{ resumen.zonas }}</p></div>
          <div class="bg-white rounded-xl shadow-sm border p-4" [class]="resumen.fuera > 0 ? 'border-red-300 bg-red-50' : 'border-gray-200'">
            <p class="text-xs font-semibold uppercase" [class]="resumen.fuera > 0 ? 'text-red-600' : 'text-gray-500'">Aún Fuera de Servicio</p>
            <p class="text-2xl font-bold" [class]="resumen.fuera > 0 ? 'text-red-700' : 'text-indigo-700'">{{ resumen.fuera }}</p>
          </div>
        </div>
      }

      <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="bg-gray-50 p-4 border-b flex justify-between items-center">
          <h2 class="text-lg font-bold text-gray-800"><i class="fas fa-list-alt mr-2 text-indigo-600"></i> Rutas Programadas ({{ datos.length }})</h2>
          @if (datos.length) {
            <button type="button" (click)="exportarExcelMaestro()" class="bg-green-600 text-white font-bold py-2 px-6 rounded-lg shadow hover:bg-green-700 transition flex items-center"><i class="fas fa-file-excel mr-2"></i> Descargar Excel Maestro</button>
          }
        </div>
        <div class="overflow-x-auto max-h-[600px]">
          <table class="w-full text-sm text-left text-gray-600 whitespace-nowrap">
            <thead class="text-xs text-gray-700 uppercase bg-gray-100 sticky top-0 z-10 shadow-sm">
              <tr>
                <th class="px-4 py-3">Fecha</th><th class="px-4 py-3">Ruta</th><th class="px-4 py-3">Técnico</th><th class="px-4 py-3">Zona</th><th class="px-4 py-3">Cliente</th>
                <th class="px-4 py-3">Punto</th><th class="px-4 py-3">Device ID</th><th class="px-4 py-3">Estado Máquina</th><th class="px-4 py-3 text-center">Acción</th>
              </tr>
            </thead>
            <tbody>
              @for (r of datos; track r.id_orden) {
                <tr class="border-b hover:bg-blue-50 transition" [class.bg-red-50]="+(r.activo_operativo ?? 1) === 0">
                  <td class="px-4 py-2 font-semibold text-gray-800">{{ r.fecha_visita }}</td>
                  <td class="px-4 py-2"><span class="bg-indigo-100 text-indigo-800 text-xs font-bold px-2 py-1 rounded">{{ r.codigo_ruta || 'S/R' }}</span></td>
                  <td class="px-4 py-2">{{ r.nombre_tecnico }}</td>
                  <td class="px-4 py-2">{{ r.zona }}</td>
                  <td class="px-4 py-2">{{ r.nombre_cliente }}</td>
                  <td class="px-4 py-2">{{ r.nombre_punto }}</td>
                  <td class="px-4 py-2 font-mono text-xs">{{ r.device_id ?? 'N/A' }}</td>
                  <td class="px-4 py-2">
                    @if (+(r.activo_operativo ?? 1) === 0) {
                      <span class="bg-red-100 text-red-700 text-xs font-bold px-2 py-1 rounded" title="La máquina sigue marcada como fuera de servicio; no se ha restaurado"><i class="fas fa-power-off mr-1"></i> Aún Fuera de Servicio</span>
                    } @else {
                      <span class="text-xs text-gray-400">Operativa</span>
                    }
                  </td>
                  <td class="px-4 py-2 text-center">
                    <button type="button" (click)="abrirEditar(r)" class="text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 px-2 py-1 rounded transition" title="Editar esta orden de programación"><i class="fas fa-pen"></i></button>
                    <button type="button" [disabled]="eliminando === r.id_orden" (click)="eliminar(r)" class="text-red-600 hover:text-red-800 hover:bg-red-50 px-2 py-1 rounded transition" title="Eliminar esta orden de la programación">
                      <i class="fas" [class]="eliminando === r.id_orden ? 'fa-spinner fa-spin' : 'fa-trash-alt'"></i>
                    </button>
                  </td>
                </tr>
              } @empty {
                <tr><td colspan="9" class="px-4 py-8 text-center text-gray-500 font-bold">{{ cargando ? 'Cargando...' : 'No se encontraron rutas programadas en este rango de fechas.' }}</td></tr>
              }
            </tbody>
          </table>
        </div>
      </div>
    </div>

    @if (ed) {
      <div class="fixed inset-0 z-50 bg-black bg-opacity-60 flex items-center justify-center p-4" (click)="clickFondo($event)">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col overflow-hidden border border-gray-200">
          <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 text-white px-6 py-4 flex justify-between items-center shadow-md">
            <h3 class="font-bold text-lg flex items-center"><i class="fas fa-pen mr-2 text-indigo-200"></i> Editar Servicio Programado</h3>
            <button type="button" (click)="cerrarEditar()" class="text-white hover:text-red-300 transition text-2xl font-bold"><i class="fas fa-times"></i></button>
          </div>
          <div class="p-6 overflow-y-auto flex-1 bg-gray-50 space-y-4">
            <div><label class="block text-sm font-semibold text-gray-700 mb-1"><i class="fas fa-calendar mr-1 text-indigo-600"></i> Fecha de Visita *</label><input type="date" [(ngModel)]="ed.fecha_visita" class="w-full border border-gray-300 rounded-lg shadow-sm py-2 px-3" /></div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1"><i class="fas fa-user-tie mr-1 text-indigo-600"></i> Técnico *</label>
              <select [(ngModel)]="ed.id_tecnico" class="w-full border border-gray-300 rounded-lg shadow-sm py-2 px-3 bg-white">
                <option value="">-- Seleccionar técnico --</option>
                @for (t of tecnicos; track t.id_tecnico) { <option [value]="'' + t.id_tecnico">{{ t.nombre_tecnico }}</option> }
              </select>
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1"><i class="fas fa-users mr-1 text-indigo-600"></i> Cliente *</label>
              <app-select-buscar [opciones]="clientesEdit" valor="id_cliente" texto="nombre_cliente" [placeholder]="cargandoClientes ? 'Cargando clientes...' : '-- Seleccionar cliente --'" [ngModel]="ed.id_cliente" (cambio)="ed.id_cliente = $event; cargarPuntos(0)" />
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1"><i class="fas fa-map-marker-alt mr-1 text-indigo-600"></i> Punto *</label>
              <select [(ngModel)]="ed.id_punto" (change)="cambioPunto()" [disabled]="cargandoPuntos" class="w-full border border-gray-300 rounded-lg shadow-sm py-2 px-3 bg-white">
                <option value="">{{ cargandoPuntos ? 'Cargando puntos...' : ed.id_cliente ? '-- Seleccionar punto --' : '-- Primero seleccione un cliente --' }}</option>
                @for (p of puntosEdit; track p.id_punto) {
                  <option [value]="'' + p.id_punto" [disabled]="p._bloq">{{ p.nombre_punto }}{{ p._bloq ? ' — ya programado' : '' }}</option>
                }
              </select>
            </div>
            @if (info) {
              <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-2">
                <p class="text-sm font-bold text-gray-800">{{ info.nombre_punto || '—' }}</p>
                <p class="text-xs text-gray-600 flex items-center"><span class="bg-indigo-100 text-indigo-700 text-[10px] font-bold px-2 py-1 rounded mr-2"><i class="fas fa-map-marker-alt mr-1"></i> Zona: {{ info.zona || '—' }}</span></p>
                <p class="text-xs text-gray-600 flex items-center"><span class="text-gray-600 text-[10px]"><i class="fas fa-location-dot mr-1"></i> {{ info.direccion || '—' }}</span></p>
                <p class="text-xs text-gray-600 flex items-center"><span class="text-gray-600 text-[10px]"><i class="fas fa-microchip mr-1"></i> Device: {{ info.device_id || 'S/N' }}</span></p>
              </div>
            }
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1"><i class="fas fa-microchip mr-1 text-gray-600"></i> Máquina</label>
              <select [(ngModel)]="ed.id_maquina" class="w-full border border-gray-300 rounded-lg shadow-sm py-2 px-3 bg-white">
                <option value="">-- Sin asignar --</option>
                @if (maquinaOpt) { <option [value]="'' + maquinaOpt.id">Device {{ maquinaOpt.device || 's/n' }}</option> }
              </select>
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1"><i class="fas fa-cogs mr-1 text-gray-600"></i> Modalidad</label>
              <select [(ngModel)]="ed.id_modalidad" class="w-full border border-gray-300 rounded-lg shadow-sm py-2 px-3 bg-white">
                <option value="">-- Sin asignar --</option>
                @for (m of modalidades; track m.id_modalidad) { <option [value]="'' + m.id_modalidad">{{ m.nombre_modalidad }}</option> }
              </select>
            </div>
          </div>
          <div class="bg-white px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
            <button type="button" (click)="cerrarEditar()" class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-bold text-sm transition">Cancelar</button>
            <button type="button" [disabled]="guardando" (click)="guardarEditada()" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold text-sm shadow-md transition flex items-center">
              <i class="fas mr-2" [class]="guardando ? 'fa-spinner fa-spin' : 'fa-save'"></i> {{ guardando ? 'Guardando...' : 'Guardar Cambios' }}
            </button>
          </div>
        </div>
      </div>
    }
  `
})
export class ProgramacionConsolidado implements OnInit, OnDestroy {
  private api = inject(Api);
  private route = inject(ActivatedRoute);
  private router = inject(Router);

  f: any = { fecha_inicio: '', fecha_fin: '', delegacion: '', tecnico: '' };
  delegaciones: any[] = [];
  tecnicos: any[] = [];
  modalidades: any[] = [];
  datos: any[] = [];
  cargando = false;
  eliminando: any = null;

  // modal edición
  ed: any = null;
  clientesEdit: any[] = [];
  cargandoClientes = false;
  puntosEdit: any[] = [];
  cargandoPuntos = false;
  info: any = null;
  maquinaOpt: { id: any; device: any } | null = null;
  guardando = false;
  private puntoOriginal = 0;
  private sub: any;

  get resumen() {
    return {
      tecnicos: new Set(this.datos.map((r) => r.id_tecnico)).size,
      zonas: new Set(this.datos.map((r) => r.zona).filter(Boolean)).size,
      fuera: this.datos.filter((r) => +(r.activo_operativo ?? 1) === 0).length
    };
  }

  ngOnInit() {
    this.sub = this.route.queryParamMap.subscribe((q) => this.cargar(q));
  }
  ngOnDestroy() {
    this.sub?.unsubscribe();
  }

  @HostListener('document:keydown.escape')
  esc() {
    if (this.ed) this.cerrarEditar();
  }

  private async cargar(q: any) {
    const params: any = {};
    ['fecha_inicio', 'fecha_fin', 'delegacion', 'tecnico'].forEach((k) => q.has(k) && (params[k] = q.get(k)));
    this.cargando = true;
    try {
      const r: any = await this.api.get('programacionConsolidado', 'index', params);
      this.f = { fecha_inicio: r.fecha_inicio, fecha_fin: r.fecha_fin, delegacion: r.delegacion || '', tecnico: r.tecnico || '' };
      this.delegaciones = r.listaDelegaciones || [];
      this.tecnicos = r.listaTecnicos || [];
      this.modalidades = r.listaModalidades || [];
      this.datos = r.consolidado || [];
    } finally {
      this.cargando = false;
    }
  }

  buscar() {
    this.router.navigate([], { queryParams: { ...this.f } });
  }

  // ================================================================ EXCEL (consolidado-export.js)
  exportarExcelMaestro() {
    if (!this.datos.length) return;
    try {
      const filas = this.datos.map((r) => ({
        'Fecha Visita': fechaLocal(r.fecha_visita),
        Ruta: r.codigo_ruta || 'Sin Ruta',
        'Técnico Asignado': r.nombre_tecnico || '',
        'Código Cliente': r.codigo_cliente || '',
        Cliente: r.nombre_cliente || '',
        'Nombre del Punto': r.nombre_punto || '',
        Dirección: r.direccion || '',
        COD_1: r.codigo_1 || '',
        Municipio: r.nombre_municipio || '',
        Zona: r.zona || '',
        Delegación: r.nombre_delegacion || '',
        'ID Dispositivo (Device)': r.device_id || '',
        'Aún Fuera de Servicio': r.activo_operativo == 0 ? 'SI' : '',
        'Fecha Última Visita': fechaLocal(r.fecha_ultima_visita)
      }));
      const wb = XLSX.utils.book_new();
      const ws = XLSX.utils.json_to_sheet(filas, { cellDates: true });
      const rg = XLSX.utils.decode_range(ws['!ref'] as string);
      for (let R = rg.s.r + 1; R <= rg.e.r; ++R) {
        for (const c of [0, 13]) {
          const cell = ws[XLSX.utils.encode_cell({ r: R, c })];
          if (cell && cell.t === 'd') cell.z = 'dd/mm/yyyy';
        }
      }
      ws['!cols'] = [15, 12, 30, 15, 35, 30, 40, 15, 20, 20, 20, 25, 18, 18].map((wch) => ({ wch }));
      XLSX.utils.book_append_sheet(wb, ws, 'Maestro Rutas');
      XLSX.writeFile(wb, 'Maestro_Programacion_' + new Date().toISOString().slice(0, 10) + '.xlsx');
    } catch (e) {
      console.error('Error al generar Excel:', e);
      alert('Hubo un error al generar el Excel.');
    }
  }

  async eliminar(r: any) {
    const id = parseInt(r.id_orden, 10);
    if (!id) return;
    const etiqueta = r.nombre_punto ? `"${r.nombre_punto}"` : `orden #${id}`;
    if (!confirm(`¿Eliminar ${etiqueta} de la programación?\n\nSe borrará de ordenes_servicio y no saldrá en el Excel.`)) return;
    this.eliminando = r.id_orden;
    try {
      const d: any = await this.api.postForm('programacionConsolidado', 'eliminarOrden', { id_orden: id });
      if (!d || !d.status) alert(d?.msg || 'No se pudo eliminar la orden.');
      else this.datos = this.datos.filter((x) => String(x.id_orden) !== String(id));
    } catch {
      alert('Error de conexión al eliminar la orden.');
    } finally {
      this.eliminando = null;
    }
  }

  // ================================================================ EDICIÓN (consolidado-editar.js)
  private fila() {
    return this.datos.find((x) => String(x.id_orden) === String(this.ed?.id_orden));
  }

  async abrirEditar(r: any) {
    this.puntoOriginal = parseInt(r.id_punto, 10) || 0;
    this.ed = {
      id_orden: r.id_orden,
      fecha_visita: String(r.fecha_visita || '').slice(0, 10),
      id_tecnico: r.id_tecnico != null ? String(r.id_tecnico) : '',
      id_cliente: r.id_cliente != null ? String(r.id_cliente) : '',
      id_punto: '',
      id_maquina: r.id_maquina ? String(r.id_maquina) : '',
      id_modalidad: r.id_modalidad ? String(r.id_modalidad) : ''
    };
    this.maquinaOpt = r.id_maquina ? { id: r.id_maquina, device: r.device_id } : null;
    this.info = { nombre_punto: r.nombre_punto, zona: r.zona, direccion: r.direccion, device_id: r.device_id };
    this.puntosEdit = [];
    document.body.style.overflow = 'hidden';
    await this.cargarClientes(r);
    await this.cargarPuntos(this.ed.id_cliente ? this.puntoOriginal : 0);
  }

  private async cargarClientes(r: any) {
    if (!this.clientesEdit.length) {
      this.cargandoClientes = true;
      try {
        const d: any = await this.api.postForm('programacionConsolidado', 'listarClientesEditar', {});
        if (!d || !d.status) throw new Error(d?.msg || 'No se pudo cargar la lista de clientes.');
        this.clientesEdit = d.clientes || [];
      } catch (e: any) {
        alert('No se pudo cargar la lista de clientes: ' + e.message);
      } finally {
        this.cargandoClientes = false;
      }
    }
    if (r.id_cliente && !this.clientesEdit.some((c) => String(c.id_cliente) === String(r.id_cliente))) {
      this.clientesEdit = [...this.clientesEdit, { id_cliente: r.id_cliente, nombre_cliente: r.nombre_cliente || 'Cliente #' + r.id_cliente }];
    }
  }

  async cargarPuntos(idSeleccionar: any) {
    const idCliente = parseInt(this.ed?.id_cliente, 10) || 0;
    if (idCliente <= 0) {
      this.puntosEdit = [];
      this.ed.id_punto = '';
      this.info = null;
      return;
    }
    this.cargandoPuntos = true;
    try {
      const d: any = await this.api.postForm('programacionConsolidado', 'obtenerPuntosCliente', { id_cliente: idCliente, id_punto_actual: this.puntoOriginal, id_orden: this.ed.id_orden });
      if (!d || !d.status) throw new Error(d?.msg || 'No se pudieron cargar los puntos.');
      if (Array.isArray(d.modalidades) && d.modalidades.length) this.modalidades = d.modalidades;
      const fila = this.fila();
      const original = fila && String(fila.id_cliente) === String(idCliente) ? this.puntoOriginal : 0;
      this.pintarPuntos(d.puntos || [], idSeleccionar, original);
    } catch (e: any) {
      this.puntosEdit = [];
      alert('No se pudieron cargar los puntos del cliente: ' + e.message);
    } finally {
      this.cargandoPuntos = false;
    }
  }

  private pintarPuntos(puntos: any[], idSel: any, idOriginal: number) {
    const lista = [...puntos];
    const fila = this.fila();
    if (idOriginal && !lista.some((p) => String(p.id_punto) === String(idOriginal)) && fila) {
      lista.unshift({
        id_punto: fila.id_punto,
        nombre_punto: (fila.nombre_punto || 'Punto actual') + ' (actual)',
        zona: fila.zona,
        direccion: fila.direccion,
        id_maquina: fila.id_maquina,
        device_id: fila.device_id,
        activo_operativo: fila.activo_operativo,
        id_modalidad: fila.id_modalidad,
        nombre_modalidad: '',
        ya_programado: 0
      });
    }
    let primera = '';
    let existe = false;
    this.puntosEdit = lista.map((p) => {
      const esOriginal = idOriginal && String(p.id_punto) === String(idOriginal);
      const bloq = parseInt(p.ya_programado, 10) === 1 && !esOriginal;
      if (!bloq && !primera) primera = String(p.id_punto);
      if (idSel && String(p.id_punto) === String(idSel) && !bloq) existe = true;
      return { ...p, _bloq: bloq };
    });
    let destino = primera;
    if (idSel && existe) destino = String(idSel);
    else if (idOriginal) {
      const o = this.puntosEdit.find((p) => String(p.id_punto) === String(idOriginal));
      if (o && !o._bloq) destino = String(idOriginal);
    }
    this.ed.id_punto = destino || '';
    this.cambioPunto();
  }

  cambioPunto() {
    const op = this.puntosEdit.find((p) => String(p.id_punto) === String(this.ed.id_punto));
    if (!this.ed.id_punto || !op) {
      this.info = null;
      return;
    }
    this.info = { nombre_punto: op.nombre_punto, zona: op.zona, direccion: op.direccion, device_id: op.device_id || 'S/N' };
    const esOriginal = this.puntoOriginal > 0 && parseInt(this.ed.id_punto, 10) === this.puntoOriginal;
    const fila = this.fila();
    if (esOriginal) {
      const idM = fila ? fila.id_maquina : op.id_maquina;
      this.maquinaOpt = idM ? { id: idM, device: fila ? fila.device_id : op.device_id } : null;
      this.ed.id_maquina = idM ? String(idM) : '';
      const mod = fila ? fila.id_modalidad : op.id_modalidad;
      this.ed.id_modalidad = mod ? String(mod) : '';
    } else {
      this.maquinaOpt = op.id_maquina ? { id: op.id_maquina, device: op.device_id } : null;
      this.ed.id_maquina = op.id_maquina ? String(op.id_maquina) : '';
      if (op.id_modalidad && !this.modalidades.some((m) => String(m.id_modalidad) === String(op.id_modalidad))) {
        this.modalidades = [...this.modalidades, { id_modalidad: op.id_modalidad, nombre_modalidad: op.nombre_modalidad || 'Modalidad del punto' }];
      }
      this.ed.id_modalidad = op.id_modalidad ? String(op.id_modalidad) : '';
    }
  }

  clickFondo(e: MouseEvent) {
    if (e.target === e.currentTarget) this.cerrarEditar();
  }

  cerrarEditar() {
    this.ed = null;
    this.info = null;
    this.puntosEdit = [];
    document.body.style.overflow = '';
  }

  async guardarEditada() {
    if (this.guardando) return;
    const e = this.ed;
    if (!e.id_orden) return alert('No se identificó la orden que se va a editar.');
    if (!e.fecha_visita) return alert('Seleccione la fecha de visita.');
    if (!e.id_tecnico) return alert('Seleccione un técnico.');
    if (!e.id_cliente) return alert('Seleccione un cliente.');
    if (!e.id_punto) return alert('Seleccione un punto.');
    if (this.cargandoPuntos) return alert('Espere mientras se cargan los puntos del cliente.');
    this.guardando = true;
    try {
      const d: any = await this.api.postForm('programacionConsolidado', 'actualizarOrden', {
        id_orden: e.id_orden,
        id_punto: e.id_punto,
        id_tecnico: e.id_tecnico,
        id_cliente: e.id_cliente,
        id_maquina: e.id_maquina,
        id_modalidad: e.id_modalidad,
        fecha_visita: e.fecha_visita
      });
      if (!d || !d.status) {
        alert(d?.msg || 'No se pudo actualizar la orden.');
        return;
      }
      if (d.datos) {
        const i = this.datos.findIndex((x) => String(x.id_orden) === String(d.datos.id_orden));
        if (i >= 0) this.datos[i] = { ...this.datos[i], ...d.datos };
        else this.datos.push(d.datos);
        this.datos = [...this.datos];
      } else {
        location.reload();
        return;
      }
      this.cerrarEditar();
      alert(d.msg || 'Orden actualizada correctamente.');
    } catch (err: any) {
      alert('Error al guardar la orden: ' + err.message);
    } finally {
      this.guardando = false;
    }
  }
}
