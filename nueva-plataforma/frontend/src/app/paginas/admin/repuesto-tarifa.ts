import { Component, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { DecimalPipe } from '@angular/common';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import * as XLSX from 'xlsx';
import { Api } from '../../core/api';
import { Titulo } from '../../core/ui';
import { DtAbajo, DtArriba, TablaDatos, Th } from '../../shared/tabla';

/** Port de repuestoVerVista.php */
@Component({
  selector: 'app-repuesto-ver',
  imports: [RouterLink, DtArriba, DtAbajo, Th],
  template: `
    <div class="w-full px-4 md:px-6">
      <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md border border-gray-100">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 border-b pb-4">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800"><i class="fas fa-boxes text-indigo-600 mr-2"></i> Inventario de Repuestos</h1>
            <p class="text-gray-500 mt-1">Gestiona el catálogo de componentes y sus referencias.</p>
          </div>
          <div class="mt-4 sm:mt-0 flex flex-wrap gap-2">
            @if (dt.datos.length) {
              <button (click)="exportarExcel()" class="px-5 py-2.5 bg-green-600 text-white font-bold rounded-lg shadow-md hover:bg-green-700 transition-all duration-300 transform hover:scale-105 flex items-center space-x-2"><i class="fas fa-file-excel"></i><span>Excel</span></button>
            }
            <a routerLink="/repuestoCrear" class="px-5 py-2.5 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transition-all duration-300 transform hover:scale-105 flex items-center space-x-2"><i class="fas fa-plus-circle"></i><span>Nuevo Repuesto</span></a>
          </div>
        </div>
        @if (cargado && dt.datos.length) {
          <div class="dt-wrapper">
            <app-dt-arriba [dt]="dt" />
            <div class="overflow-x-auto">
              <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                  <tr>
                    <th app-th [dt]="dt" campo="id_repuesto" class="py-3 px-4">ID</th>
                    <th app-th [dt]="dt" campo="nombre_repuesto" class="py-3 px-4">Nombre Repuesto</th>
                    <th app-th [dt]="dt" campo="codigo_referencia" class="py-3 px-4">Cód. Referencia</th>
                    <th class="py-3 px-4 text-center">Estado</th>
                    <th class="py-3 px-4 text-center">Acciones</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  @for (r of dt.pagina; track r.id_repuesto) {
                    <tr class="hover:bg-gray-50 transition">
                      <td class="py-4 px-4 font-bold text-gray-600">#{{ r.id_repuesto }}</td>
                      <td class="py-4 px-4 font-medium text-gray-900">{{ r.nombre_repuesto }}</td>
                      <td class="py-4 px-4 font-mono text-gray-500 text-xs">@if (r.codigo_referencia) { {{ r.codigo_referencia }} } @else { <span class="text-gray-300 italic">N/A</span> }</td>
                      <td class="py-4 px-4 text-center">
                        @if (r.estado == 1) {
                          <span class="px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full border border-green-200">Activo</span>
                        } @else {
                          <span class="px-2 py-1 text-xs font-semibold text-red-700 bg-red-100 rounded-full border border-red-200">Inactivo</span>
                        }
                      </td>
                      <td class="py-4 px-4 text-center whitespace-nowrap">
                        <div class="flex justify-center items-center space-x-2">
                          <a [routerLink]="'/repuestoEditar/' + r.id_repuesto" class="p-2 w-8 h-8 flex items-center justify-center bg-yellow-100 text-yellow-600 rounded-full hover:bg-yellow-200 transition-colors border border-yellow-200" title="Editar"><i class="fas fa-edit text-xs"></i></a>
                          <button (click)="idEliminar = r.id_repuesto" class="p-2 w-8 h-8 flex items-center justify-center bg-red-100 text-red-600 rounded-full hover:bg-red-200 transition-colors border border-red-200" title="Eliminar"><i class="fas fa-trash-alt text-xs"></i></button>
                        </div>
                      </td>
                    </tr>
                  }
                </tbody>
              </table>
            </div>
            <app-dt-abajo [dt]="dt" />
          </div>
        } @else if (cargado) {
          <div class="text-center p-10 border-2 border-dashed border-gray-200 rounded-lg bg-gray-50">
            <i class="fas fa-box-open text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-500 font-medium">No hay repuestos registrados.</p>
            <p class="text-gray-400 text-sm mt-1">¡Registra el primero usando el botón azul!</p>
          </div>
        }
      </div>
    </div>
    @if (idEliminar !== null) {
      <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" (click)="idEliminar = null"></div>
          <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
          <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full relative">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
              <div class="sm:flex sm:items-start">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10"><i class="fas fa-exclamation-triangle text-red-600"></i></div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                  <h3 class="text-lg leading-6 font-medium text-gray-900">Eliminar Repuesto</h3>
                  <div class="mt-2"><p class="text-sm text-gray-500">¿Estás seguro de que deseas eliminar este repuesto? Esta acción ocultará el ítem del inventario activo.</p></div>
                </div>
              </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
              <button (click)="eliminar()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Sí, Eliminar</button>
              <button type="button" (click)="idEliminar = null" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancelar</button>
            </div>
          </div>
        </div>
      </div>
    }
  `
})
export class RepuestoVer implements OnInit {
  private api = inject(Api);
  dt = new TablaDatos<any>();
  cargado = false;
  idEliminar: any = null;

  constructor() {
    inject(Titulo).set('Inventario de Repuestos');
  }

  async ngOnInit() {
    this.dt.set((await this.api.get('repuestoVer', 'index')).repuestos);
    this.cargado = true;
  }

  async eliminar() {
    const id = this.idEliminar;
    this.idEliminar = null;
    await this.api.post('repuestoEliminar', 'index', { id });
    this.ngOnInit();
  }

  exportarExcel() {
    const lista = this.dt.datos;
    if (!lista.length) return alert('No hay datos para exportar');
    const datos = lista.map((r: any) => ({
      ID: r.id_repuesto,
      'Nombre Repuesto': r.nombre_repuesto,
      'Código Referencia': r.codigo_referencia || 'N/A',
      Estado: r.estado == 1 ? 'Activo' : 'Inactivo'
    }));
    const ws = XLSX.utils.json_to_sheet(datos);
    const wb = XLSX.utils.book_new();
    ws['!cols'] = [{ wch: 10 }, { wch: 40 }, { wch: 20 }, { wch: 15 }];
    XLSX.utils.book_append_sheet(wb, ws, 'Repuestos');
    XLSX.writeFile(wb, `Repuestos_${new Date().toISOString().slice(0, 10)}.xlsx`);
  }
}

/** Port de repuestoCrearVista.php / repuestoEditarVista.php */
@Component({
  selector: 'app-repuesto-form',
  imports: [FormsModule, RouterLink],
  template: `
    @if (listo) {
      <div [class]="editar ? 'w-full px-4 md:px-6' : 'w-full max-w-4xl mx-auto'">
        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md border border-gray-100">
          <div class="mb-6 border-b pb-4">
            @if (editar) {
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-800"><i class="fas fa-edit text-yellow-500 mr-2"></i> Editar Repuesto</h1>
              <p class="text-gray-500 mt-1 ml-1">Modifica los detalles del ítem #{{ id }}</p>
            } @else {
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-800"><i class="fas fa-box-open text-indigo-600 mr-2"></i> Crear Nuevo Repuesto</h1>
              <p class="text-gray-500 mt-1 ml-1">Registra los items para el inventario de mantenimiento.</p>
            }
          </div>
          @if (errores.length) {
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm" [class.animate-pulse]="!editar" role="alert">
              @if (editar) { <p class="font-bold">¡Error al guardar!</p> } @else { <div class="flex items-center"><i class="fas fa-exclamation-circle mr-2 text-lg"></i><p class="font-bold">¡Atención!</p></div> }
              <ul class="list-disc list-inside ml-6 mt-1 text-sm">@for (e of errores; track $index) { <li>{{ e }}</li> }</ul>
            </div>
          }
          <form (submit)="guardar($event)" class="space-y-6">
            <div>
              <label for="nombre_repuesto" class="block text-sm font-bold text-gray-700 mb-1">Nombre del Repuesto <span class="text-red-500">*</span></label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-tag text-gray-400"></i></div>
                <input type="text" id="nombre_repuesto" name="nombre_repuesto" required [(ngModel)]="d.nombre_repuesto" [placeholder]="editar ? '' : 'Ej: Batería 12V 7Ah'"
                  class="pl-10 mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 transition-all" [class]="ring" />
              </div>
              @if (!editar) { <p class="text-xs text-gray-400 mt-1">Nombre descriptivo del componente.</p> }
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label for="codigo_referencia" class="block text-sm font-bold text-gray-700 mb-1">Código / Referencia</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-barcode text-gray-400"></i></div>
                  <input type="text" id="codigo_referencia" name="codigo_referencia" [(ngModel)]="d.codigo_referencia" [placeholder]="editar ? '' : 'Ej: REF-2025-X'"
                    class="pl-10 mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 transition-all uppercase" [class]="ring" />
                </div>
              </div>
              <div>
                <label for="estado" class="block text-sm font-bold text-gray-700 mb-1">Estado</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-toggle-on text-gray-400"></i></div>
                  <select id="estado" name="estado" [(ngModel)]="d.estado" class="pl-10 mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 bg-white transition-all appearance-none cursor-pointer" [class]="ring">
                    <option value="1">Activo (Disponible)</option>
                    <option value="0">Inactivo (No Disponible)</option>
                  </select>
                  <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none"><i class="fas fa-chevron-down text-gray-400 text-xs"></i></div>
                </div>
              </div>
              <div>
                <label for="requiere_devolucion" class="block text-sm font-bold text-gray-700 mb-1">¿Control de Devolución?</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-undo-alt text-gray-400"></i></div>
                  <select id="requiere_devolucion" name="requiere_devolucion" [(ngModel)]="d.requiere_devolucion" class="pl-10 mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 bg-white transition-all appearance-none cursor-pointer" [class]="ring">
                    <option value="0">No requiere</option>
                    <option value="1">Sí (Técnico debe devolver)</option>
                  </select>
                  <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none"><i class="fas fa-chevron-down text-gray-400 text-xs"></i></div>
                </div>
              </div>
            </div>
            <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
              @if (editar) {
                <a routerLink="/repuestoVer" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50 transition-all">Cancelar</a>
                <button type="submit" class="px-8 py-3 bg-yellow-500 text-white font-bold rounded-lg shadow-md hover:bg-yellow-600 transition-all transform hover:-translate-y-1"><i class="fas fa-save mr-2"></i> Guardar Cambios</button>
              } @else {
                <a routerLink="/repuestoVer" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50 hover:text-gray-900 transition-all duration-200"><i class="fas fa-times mr-2"></i> Cancelar</a>
                <button type="submit" class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-lg shadow-lg hover:bg-indigo-700 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1"><i class="fas fa-save mr-2"></i> Guardar Repuesto</button>
              }
            </div>
          </form>
        </div>
      </div>
    }
  `
})
export class RepuestoForm implements OnInit {
  private api = inject(Api);
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private titulo = inject(Titulo);
  editar = false;
  id: any = null;
  d: any = { nombre_repuesto: '', codigo_referencia: '', estado: '1', requiere_devolucion: '0' };
  errores: string[] = [];
  listo = false;

  get ring() {
    return this.editar ? 'focus:ring-yellow-500 focus:border-yellow-500' : 'focus:ring-indigo-500 focus:border-indigo-500';
  }

  async ngOnInit() {
    this.editar = this.route.snapshot.data['modo'] === 'editar';
    if (this.editar) {
      this.id = this.route.snapshot.paramMap.get('id');
      this.titulo.set('Editar Repuesto #' + this.id);
      const r = await this.api.get('repuestoEditar', 'index', { id: this.id ?? '' });
      if (r.redirect) return void this.router.navigateByUrl('/' + r.redirect);
      this.d = { nombre_repuesto: r.datos.nombre_repuesto, codigo_referencia: r.datos.codigo_referencia ?? '', estado: String(r.datos.estado), requiere_devolucion: String(r.datos.requiere_devolucion) };
    } else {
      this.titulo.set('Crear Nuevo Repuesto');
    }
    this.listo = true;
  }

  async guardar(e: Event) {
    e.preventDefault();
    const r = await this.api.post(this.editar ? 'repuestoEditar' : 'repuestoCrear', 'index', { ...this.d, id_repuesto: this.id ?? undefined });
    if (r.success) this.router.navigateByUrl('/' + r.redirect);
    else this.errores = r.errores || [];
  }
}

/** Port de tarifaVerVista.php */
@Component({
  selector: 'app-tarifa-ver',
  imports: [RouterLink, DtArriba, DtAbajo, Th, DecimalPipe],
  template: `
    <div class="w-full px-4 md:px-6">
      <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-tags text-green-600 mr-2"></i> Tarifas</h1>
            <p class="text-gray-500">Precios configurados por servicio y máquina.</p>
          </div>
          <div class="flex gap-2">
            <a routerLink="/tarifaCrear" class="px-5 py-2.5 bg-green-600 text-white font-bold rounded-lg shadow hover:bg-green-700 transition flex items-center space-x-2"><i class="fas fa-plus-circle"></i> <span>Nueva Tarifa</span></a>
            <a routerLink="/tarifaMasiva" class="px-5 py-2.5 bg-blue-600 text-white font-bold rounded-lg shadow hover:bg-blue-700 transition flex items-center space-x-2"><i class="fas fa-th-list"></i> <span>Edición Masiva</span></a>
          </div>
        </div>
        @if (cargado && dt.datos.length) {
          <div class="dt-wrapper">
            <app-dt-arriba [dt]="dt" />
            <div class="overflow-x-auto">
              <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                  <tr>
                    <th app-th [dt]="dt" campo="nombre_tipo_maquina" class="py-3 px-4">Máquina</th>
                    <th app-th [dt]="dt" campo="nombre_mantenimiento" class="py-3 px-4">Mantenimiento</th>
                    <th app-th [dt]="dt" campo="nombre_modalidad" class="py-3 px-4">Modalidad</th>
                    <th app-th [dt]="dt" campo="año_vigencia" class="py-3 px-4">Año</th>
                    <th app-th [dt]="dt" campo="precio" class="py-3 px-4 text-right">Precio</th>
                    <th class="py-3 px-4 text-center">Acciones</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  @for (t of dt.pagina; track t.id_tarifa) {
                    <tr class="hover:bg-gray-50">
                      <td class="py-3 px-4 font-bold text-gray-700">{{ t.nombre_tipo_maquina }}</td>
                      <td class="py-3 px-4">{{ t.nombre_mantenimiento }}</td>
                      <td class="py-3 px-4">{{ t.nombre_modalidad }}</td>
                      <td class="py-3 px-4 font-mono">{{ t['año_vigencia'] }}</td>
                      <td class="py-3 px-4 text-right font-bold text-green-600">\${{ +t.precio | number: '1.2-2':'en-US' }}</td>
                      <td class="py-3 px-4 text-center flex justify-center space-x-2">
                        <a [routerLink]="'/tarifaEditar/' + t.id_tarifa" class="p-2 bg-yellow-100 text-yellow-600 rounded-full hover:bg-yellow-200"><i class="fas fa-edit"></i></a>
                        <button (click)="idEliminar = t.id_tarifa" class="p-2 bg-red-100 text-red-600 rounded-full hover:bg-red-200"><i class="fas fa-trash-alt"></i></button>
                      </td>
                    </tr>
                  }
                </tbody>
              </table>
            </div>
            <app-dt-abajo [dt]="dt" />
          </div>
        } @else if (cargado) {
          <div class="text-center p-8 bg-gray-50 rounded-lg"><p class="text-gray-500">No hay tarifas registradas.</p></div>
        }
      </div>
    </div>
    @if (idEliminar !== null) {
      <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-sm shadow-xl">
          <h3 class="text-lg font-bold text-gray-900 mb-2">Eliminar Tarifa</h3>
          <p class="text-sm text-gray-500 mb-4">¿Seguro? Esta acción es irreversible.</p>
          <div class="flex justify-end space-x-3">
            <button (click)="idEliminar = null" class="px-4 py-2 bg-gray-100 rounded-lg text-gray-700 hover:bg-gray-200">Cancelar</button>
            <button (click)="eliminar()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Sí, Borrar</button>
          </div>
        </div>
      </div>
    }
  `
})
export class TarifaVer implements OnInit {
  private api = inject(Api);
  dt = new TablaDatos<any>([], 50);
  cargado = false;
  idEliminar: any = null;

  constructor() {
    inject(Titulo).set('Listado de Tarifas');
  }

  async ngOnInit() {
    this.dt.set((await this.api.get('tarifaVer', 'index')).tarifas);
    this.cargado = true;
  }

  async eliminar() {
    const id = this.idEliminar;
    this.idEliminar = null;
    await this.api.post('tarifaEliminar', 'index', { id });
    this.ngOnInit();
  }
}

/** Port de tarifaCrearVista.php (tarifario multi-máquina) */
@Component({
  selector: 'app-tarifa-crear',
  imports: [FormsModule, RouterLink],
  template: `
    @if (listo) {
      <div class="w-full max-w-4xl mx-auto">
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
          <div class="mb-6 border-b pb-4">
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-layer-group text-blue-600 mr-2"></i> Tarifario Multi-Máquina</h1>
            <p class="text-gray-500 mt-1">Selecciona varias máquinas y aplícales los mismos precios simultáneamente.</p>
          </div>
          @if (errores.length) {
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded"><ul class="list-disc list-inside text-sm">@for (e of errores; track $index) { <li>{{ e }}</li> }</ul></div>
          }
          <form (submit)="guardar($event)" class="space-y-6">
            <div class="bg-gray-50 p-5 rounded-lg border border-gray-200">
              <div class="mb-4 w-full md:w-1/4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Año Vigencia</label>
                <input type="number" name="anio" required min="2020" max="2030" [(ngModel)]="anio" (ngModelChange)="verificarDisponibilidad()" class="w-full px-3 py-2 border rounded shadow-sm focus:ring-2 focus:ring-blue-500 bg-white" />
              </div>
              <div class="block mb-2">
                <label class="text-sm font-bold text-gray-700">Selecciona las Máquinas a aplicar:</label>
                <button type="button" (click)="toggleCheckboxes()" class="text-xs text-blue-600 hover:underline ml-2 cursor-pointer select-none">{{ textoToggle }}</button>
              </div>
              <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 bg-white p-3 rounded border h-48 overflow-y-auto">
                @for (m of maquinas; track m.id_tipo_maquina) {
                  <label class="flex items-center space-x-2 p-2 rounded border border-transparent transition"
                    [class]="ocupada(m) ? 'opacity-50 bg-gray-100 cursor-not-allowed' : 'hover:bg-blue-50 cursor-pointer hover:border-blue-100'">
                    <input type="checkbox" class="form-checkbox h-5 w-5 text-blue-600" [disabled]="ocupada(m)" [checked]="sel.has(m.id_tipo_maquina)" (change)="alternar(m)" />
                    <span class="text-sm text-gray-700">{{ m.nombre_tipo_maquina }}{{ ocupada(m) ? ' (Ya existe)' : '' }}</span>
                  </label>
                }
              </div>
              <p class="text-xs text-gray-500 mt-1"><i class="fas fa-info-circle"></i> Las máquinas en gris ya tienen precios configurados para ese año.</p>
            </div>
            <div class="overflow-x-auto">
              <table class="min-w-full border-collapse border border-gray-200">
                <thead>
                  <tr class="bg-gray-800 text-white">
                    <th class="p-3 text-left border border-gray-600">Tipo de Mantenimiento</th>
                    @for (mod of modalidades; track mod.id_modalidad) { <th class="p-3 text-center border border-gray-600">{{ mod.nombre_modalidad }}</th> }
                  </tr>
                </thead>
                <tbody>
                  @for (manto of mantenimientos; track manto.id_tipo_mantenimiento) {
                    <tr class="hover:bg-gray-50 transition">
                      <td class="p-3 border font-bold text-gray-700 bg-gray-100">{{ manto.nombre_completo }}</td>
                      @for (mod of modalidades; track mod.id_modalidad) {
                        <td class="p-2 border text-center">
                          <div class="relative">
                            <span class="absolute left-2 top-2 text-gray-400 text-xs">$</span>
                            <input type="number" [name]="'p_' + manto.id_tipo_mantenimiento + '_' + mod.id_modalidad" step="0.01" min="0" placeholder="0.00"
                              [(ngModel)]="precios[manto.id_tipo_mantenimiento][mod.id_modalidad]"
                              class="w-full pl-6 pr-2 py-1 border rounded text-right focus:bg-green-50 focus:border-green-500 font-mono text-sm" />
                          </div>
                        </td>
                      }
                    </tr>
                  }
                </tbody>
              </table>
            </div>
            <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
              <a routerLink="/tarifaVer" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50">Cancelar</a>
              <button type="submit" class="px-8 py-3 bg-blue-600 text-white font-bold rounded-lg shadow-md hover:bg-blue-700 transform hover:-translate-y-1 transition-all"><i class="fas fa-save mr-2"></i> Guardar Tarifas Masivas</button>
            </div>
          </form>
        </div>
      </div>
    }
  `
})
export class TarifaCrear implements OnInit {
  private api = inject(Api);
  private router = inject(Router);
  maquinas: any[] = [];
  mantenimientos: any[] = [];
  modalidades: any[] = [];
  precios: Record<string, Record<string, any>> = {};
  ocupadas = new Set<string>();
  sel = new Set<any>();
  anio: any = new Date().getFullYear();
  textoToggle = '(Seleccionar Disponibles)';
  errores: string[] = [];
  listo = false;

  constructor() {
    inject(Titulo).set('Crear Tarifas Masivas');
  }

  async ngOnInit() {
    const r = await this.api.get('tarifaCrear', 'index');
    this.maquinas = r.listaMaquinas;
    this.mantenimientos = r.listaMantenimientos;
    this.modalidades = r.listaModalidades;
    this.anio = r.anio;
    this.mantenimientos.forEach((m) => (this.precios[m.id_tipo_mantenimiento] = {}));
    this.listo = true;
    this.verificarDisponibilidad();
  }

  ocupada(m: any) {
    return this.ocupadas.has(String(m.id_tipo_maquina));
  }

  alternar(m: any) {
    if (this.sel.has(m.id_tipo_maquina)) this.sel.delete(m.id_tipo_maquina);
    else this.sel.add(m.id_tipo_maquina);
  }

  async verificarDisponibilidad() {
    if (String(this.anio ?? '').length !== 4) return;
    try {
      const ids: any[] = await this.api.get('tarifaCrear', 'verificarMaquinas', { anio: this.anio });
      this.ocupadas = new Set(ids.map(String));
      this.maquinas.forEach((m) => {
        if (this.ocupada(m)) this.sel.delete(m.id_tipo_maquina);
      });
    } catch (e) {
      console.error('Error verificando máquinas:', e);
    }
  }

  toggleCheckboxes() {
    const disponibles = this.maquinas.filter((m) => !this.ocupada(m));
    if (!disponibles.length) return;
    const marcar = !this.sel.has(disponibles[0].id_tipo_maquina);
    disponibles.forEach((m) => (marcar ? this.sel.add(m.id_tipo_maquina) : this.sel.delete(m.id_tipo_maquina)));
    this.textoToggle = marcar ? '(Deseleccionar Disponibles)' : '(Seleccionar Disponibles)';
  }

  async guardar(e: Event) {
    e.preventDefault();
    const precios: Record<string, Record<string, any>> = {};
    Object.entries(this.precios).forEach(([manto, mods]) => {
      precios[manto] = {};
      this.modalidades.forEach((mod) => (precios[manto][mod.id_modalidad] = mods[mod.id_modalidad] ?? ''));
    });
    const r = await this.api.post('tarifaCrear', 'index', { ids_maquinas: [...this.sel], año_vigencia: this.anio, precios });
    if (r.success) {
      alert(r.mensaje);
      this.router.navigateByUrl('/' + r.redirect);
    } else this.errores = r.errores || [];
  }
}

/** Port de tarifaEditarVista.php */
@Component({
  selector: 'app-tarifa-editar',
  imports: [FormsModule, RouterLink],
  template: `
    @if (listo) {
      <div class="w-full max-w-4xl mx-auto">
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
          <div class="mb-6 border-b pb-4">
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-edit text-yellow-500 mr-2"></i> Editar Tarifa</h1>
            <p class="text-gray-500 mt-1">Modificando tarifa ID #{{ id }}</p>
          </div>
          @if (errores.length) {
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded"><ul class="list-disc list-inside text-sm">@for (e of errores; track $index) { <li>{{ e }}</li> }</ul></div>
          }
          <form (submit)="guardar($event)" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Tipo de Máquina</label>
                <select name="id_tipo_maquina" required [(ngModel)]="d.id_tipo_maquina" class="w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-yellow-500 bg-white">
                  @for (i of l.listaMaquinas; track i.id_tipo_maquina) { <option [value]="i.id_tipo_maquina + ''">{{ i.nombre_tipo_maquina }}</option> }
                </select>
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Tipo Mantenimiento</label>
                <select name="id_tipo_mantenimiento" required [(ngModel)]="d.id_tipo_mantenimiento" class="w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-yellow-500 bg-white">
                  @for (i of l.listaMantenimientos; track i.id_tipo_mantenimiento) { <option [value]="i.id_tipo_mantenimiento + ''">{{ i.nombre_completo }}</option> }
                </select>
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Modalidad</label>
                <select name="id_modalidad" required [(ngModel)]="d.id_modalidad" class="w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-yellow-500 bg-white">
                  @for (i of l.listaModalidades; track i.id_modalidad) { <option [value]="i.id_modalidad + ''">{{ i.nombre_modalidad }}</option> }
                </select>
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Año Vigencia</label>
                <input type="number" name="anio" required min="2020" max="2030" [(ngModel)]="d.anio" class="w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-yellow-500" />
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1">Precio ($)</label>
                <input type="number" name="precio" required step="0.01" min="0" [(ngModel)]="d.precio" class="w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-yellow-500 text-lg font-bold text-gray-700" />
              </div>
            </div>
            <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
              <a routerLink="/tarifaVer" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50">Cancelar</a>
              <button type="submit" class="px-8 py-3 bg-yellow-500 text-white font-bold rounded-lg shadow-md hover:bg-yellow-600 transform hover:-translate-y-1 transition-all">Actualizar</button>
            </div>
          </form>
        </div>
      </div>
    }
  `
})
export class TarifaEditar implements OnInit {
  private api = inject(Api);
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  id: any = null;
  l: any = {};
  d: any = {};
  errores: string[] = [];
  listo = false;

  constructor() {
    inject(Titulo).set('Editar Tarifa');
  }

  async ngOnInit() {
    this.id = this.route.snapshot.paramMap.get('id');
    const r = await this.api.get('tarifaEditar', 'index', { id: this.id ?? '' });
    if (r.redirect) return void this.router.navigateByUrl('/' + r.redirect);
    this.l = r;
    this.d = {
      id_tipo_maquina: String(r.datos.id_tipo_maquina),
      id_tipo_mantenimiento: String(r.datos.id_tipo_mantenimiento),
      id_modalidad: String(r.datos.id_modalidad),
      anio: r.datos['año_vigencia'],
      precio: r.datos.precio
    };
    this.listo = true;
  }

  async guardar(e: Event) {
    e.preventDefault();
    const r = await this.api.post('tarifaEditar', 'index', {
      id_tarifa: this.id,
      id_tipo_maquina: this.d.id_tipo_maquina,
      id_tipo_mantenimiento: this.d.id_tipo_mantenimiento,
      id_modalidad: this.d.id_modalidad,
      precio: this.d.precio,
      año_vigencia: this.d.anio
    });
    if (r.success) this.router.navigateByUrl('/' + r.redirect);
    else this.errores = r.errores || [];
  }
}

/** Port de tarifaMasivaVista.php */
@Component({
  selector: 'app-tarifa-masiva',
  imports: [FormsModule, RouterLink],
  template: `
    <div class="w-full max-w-4xl mx-auto">
      @if (mensaje) {
        <div class="border px-4 py-3 rounded relative mb-4" role="alert" [class]="tipo === 'success' ? 'bg-green-100 text-green-700 border-green-400' : 'bg-red-100 text-red-700 border-red-400'">
          <strong class="font-bold">{{ tipo === 'success' ? '¡Éxito!' : '¡Error!' }}</strong>
          <span class="block sm:inline">{{ mensaje }}</span>
        </div>
      }
      <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-6">
        <div class="flex justify-between items-center mb-4">
          <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-edit text-blue-600 mr-2"></i> Edición Masiva</h1>
          <a routerLink="/tarifaVer" class="text-gray-500 hover:text-gray-700 underline">Volver al listado</a>
        </div>
        <form (submit)="cargar($event)" class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 p-4 rounded-lg border border-gray-200">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Seleccionar Máquina</label>
            <select name="id_maquina" [(ngModel)]="idMaquina" (ngModelChange)="cargar()" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2">
              <option value="">-- Seleccione --</option>
              @for (m of maquinas; track m.id_tipo_maquina) { <option [value]="m.id_tipo_maquina + ''">{{ m.nombre_tipo_maquina }}</option> }
            </select>
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Año de Vigencia</label>
            <input type="number" name="anio" [(ngModel)]="anio" (change)="cargar()" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2" />
          </div>
          <div class="flex items-end">
            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 transition"><i class="fas fa-search mr-2"></i> Cargar Tarifas</button>
          </div>
        </form>
      </div>
      @if (tarifas.length) {
        <form (submit)="guardar($event)" class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
          <div class="p-4 bg-blue-50 border-b border-blue-100 flex justify-between items-center">
            <h2 class="font-bold text-blue-800">Editando tarifas de: Año {{ anioCargado }}</h2>
            <span class="text-sm text-blue-600 bg-white px-3 py-1 rounded-full border border-blue-200">{{ tarifas.length }} registros encontrados</span>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
              <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                <tr><th class="py-3 px-6">Tipo Mantenimiento</th><th class="py-3 px-6">Modalidad</th><th class="py-3 px-6 w-48 text-right">Precio Actual ($)</th></tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                @for (t of tarifas; track t.id_tarifa) {
                  <tr class="hover:bg-gray-50 transition">
                    <td class="py-3 px-6 font-medium text-gray-900">{{ t.nombre_mantenimiento }}</td>
                    <td class="py-3 px-6"><span class="px-2 py-1 bg-gray-100 rounded text-xs font-semibold">{{ t.nombre_modalidad }}</span></td>
                    <td class="py-3 px-6 text-right">
                      <input type="number" step="0.01" min="0" [name]="'precio_' + t.id_tarifa" [(ngModel)]="t.precio" class="w-full text-right border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500 font-bold text-gray-800 p-2 bg-yellow-50 focus:bg-white transition border" />
                    </td>
                  </tr>
                }
              </tbody>
            </table>
          </div>
          <div class="p-6 bg-gray-50 border-t border-gray-200 flex justify-end">
            <button type="submit" class="px-6 py-3 bg-green-600 text-white font-bold rounded-lg shadow hover:bg-green-700 transform hover:-translate-y-0.5 transition flex items-center"><i class="fas fa-save mr-2"></i> Guardar Todos los Cambios</button>
          </div>
        </form>
      } @else if (idMaquinaCargada) {
        <div class="bg-white p-8 rounded-xl shadow text-center text-gray-500">
          <i class="fas fa-search fa-3x mb-4 text-gray-300"></i>
          <p>No se encontraron tarifas para esta combinación de Máquina y Año.</p>
        </div>
      }
    </div>
  `
})
export class TarifaMasiva implements OnInit {
  private api = inject(Api);
  maquinas: any[] = [];
  tarifas: any[] = [];
  idMaquina: any = '';
  anio: any = new Date().getFullYear();
  idMaquinaCargada: any = '';
  anioCargado: any = '';
  mensaje: string | null = null;
  tipo = '';

  constructor() {
    inject(Titulo).set('Edición Masiva de Tarifas');
  }

  ngOnInit() {
    this.cargar();
  }

  private aplicar(r: any) {
    this.maquinas = r.listaMaquinas;
    this.tarifas = r.tarifas;
    this.idMaquina = this.idMaquinaCargada = r.id_maquina ?? '';
    this.anio = this.anioCargado = r.anio;
    this.mensaje = r.mensaje;
    this.tipo = r.tipo_mensaje;
  }

  async cargar(e?: Event) {
    e?.preventDefault();
    this.aplicar(await this.api.get('tarifaMasiva', 'index', { id_maquina: this.idMaquina, año: this.anio }));
  }

  async guardar(e: Event) {
    e.preventDefault();
    const precios: Record<string, any> = {};
    this.tarifas.forEach((t) => (precios[t.id_tarifa] = t.precio ?? ''));
    this.aplicar(await this.api.post('tarifaMasiva', 'index', { guardar_masivo: 1, filtro_maquina: this.idMaquinaCargada, filtro_anio: this.anioCargado, precios }));
  }
}
