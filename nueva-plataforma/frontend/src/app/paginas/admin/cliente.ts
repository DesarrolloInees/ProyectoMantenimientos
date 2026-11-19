import { Component, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { Api } from '../../core/api';
import { Titulo } from '../../core/ui';
import { DtAbajo, DtArriba, TablaDatos, Th } from '../../shared/tabla';

/** Port de clienteVerVista.php */
@Component({
  selector: 'app-cliente-ver',
  imports: [RouterLink, DtArriba, DtAbajo, Th],
  template: `
    <div class="w-full px-4 md:px-6">
      <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md border border-gray-100">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 border-b pb-4">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800"><i class="fas fa-users text-indigo-600 mr-2"></i> Clientes</h1>
            <p class="text-gray-500 mt-1">Administra las empresas y entidades registradas.</p>
          </div>
          <a routerLink="/clienteCrear" class="mt-4 sm:mt-0 px-5 py-2.5 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transition-all duration-300 transform hover:scale-105 flex items-center space-x-2">
            <i class="fas fa-plus-circle"></i> <span>Nuevo Cliente</span>
          </a>
        </div>
        @if (cargado && dt.datos.length) {
          <div class="dt-wrapper">
            <app-dt-arriba [dt]="dt" />
            <div class="overflow-x-auto">
              <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                  <tr>
                    <th app-th [dt]="dt" campo="id_cliente" class="py-3 px-4">ID</th>
                    <th app-th [dt]="dt" campo="codigo_cliente" class="py-3 px-4">Código</th>
                    <th app-th [dt]="dt" campo="nombre_cliente" class="py-3 px-4">Nombre Cliente</th>
                    <th class="py-3 px-4 text-center">Estado</th>
                    <th class="py-3 px-4 text-center">Acciones</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  @for (c of dt.pagina; track c.id_cliente) {
                    <tr class="hover:bg-gray-50 transition">
                      <td class="py-4 px-4 font-bold text-gray-600">#{{ c.id_cliente }}</td>
                      <td class="py-4 px-4 font-mono text-indigo-600 font-bold">{{ c.codigo_cliente }}</td>
                      <td class="py-4 px-4 font-medium text-gray-900 text-base">{{ c.nombre_cliente }}</td>
                      <td class="py-4 px-4 text-center"><span class="px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full border border-green-200">Activo</span></td>
                      <td class="py-4 px-4 text-center whitespace-nowrap">
                        <div class="flex justify-center items-center space-x-2">
                          <a [routerLink]="'/clienteEditar/' + c.id_cliente" class="p-2 w-8 h-8 flex items-center justify-center bg-yellow-100 text-yellow-600 rounded-full hover:bg-yellow-200 transition-colors border border-yellow-200"><i class="fas fa-edit text-xs"></i></a>
                          <button (click)="idEliminar = c.id_cliente" class="p-2 w-8 h-8 flex items-center justify-center bg-red-100 text-red-600 rounded-full hover:bg-red-200 transition-colors border border-red-200"><i class="fas fa-trash-alt text-xs"></i></button>
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
          <div class="text-center p-10 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200"><p class="text-gray-500">No hay clientes registrados aún.</p></div>
        }
      </div>
    </div>

    @if (idEliminar !== null) {
      <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" (click)="idEliminar = null"></div>
          <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full relative">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
              <div class="sm:flex sm:items-start">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10"><i class="fas fa-exclamation-triangle text-red-600"></i></div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                  <h3 class="text-lg leading-6 font-medium text-gray-900">Eliminar Cliente</h3>
                  <div class="mt-2"><p class="text-sm text-gray-500">¿Estás seguro? Se ocultará el cliente y sus puntos asociados podrían quedar inaccesibles.</p></div>
                </div>
              </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
              <button (click)="eliminar()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">Sí, Eliminar</button>
              <button type="button" (click)="idEliminar = null" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancelar</button>
            </div>
          </div>
        </div>
      </div>
    }
  `
})
export class ClienteVer implements OnInit {
  private api = inject(Api);
  dt = new TablaDatos<any>();
  cargado = false;
  idEliminar: any = null;

  constructor() {
    inject(Titulo).set('Gestión de Clientes');
  }

  async ngOnInit() {
    this.dt.set((await this.api.get('clienteVer', 'index')).clientes);
    this.cargado = true;
  }

  async eliminar() {
    const id = this.idEliminar;
    this.idEliminar = null;
    await this.api.post('clienteEliminar', 'index', { id });
    this.ngOnInit();
  }
}

/** Port de clienteCrearVista.php / clienteEditarVista.php */
@Component({
  selector: 'app-cliente-form',
  imports: [FormsModule, RouterLink],
  template: `
    @if (listo) {
      <div class="w-full px-4 md:px-6">
        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md border border-gray-100">
          <div class="mb-6 border-b pb-4">
            @if (editar) {
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-800"><i class="fas fa-edit text-yellow-500 mr-2"></i> Editar Cliente</h1>
              <p class="text-gray-500 mt-1">Modificando datos de: <strong>{{ original }}</strong></p>
            } @else {
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-800"><i class="fas fa-handshake text-indigo-600 mr-2"></i> Nuevo Cliente</h1>
              <p class="text-gray-500 mt-1">Registra una nueva empresa o cliente para gestionar sus puntos.</p>
            }
          </div>
          @if (errores.length) {
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm" role="alert">
              <p class="font-bold">¡Error!</p>
              <ul class="list-disc list-inside ml-2 text-sm">@for (e of errores; track $index) { <li>{{ e }}</li> }</ul>
            </div>
          }
          <form (submit)="guardar($event)" class="space-y-6">
            <div>
              <label for="nombre_cliente" class="block text-sm font-bold text-gray-700 mb-1">Nombre / Razón Social <span class="text-red-500">*</span></label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-building text-gray-400"></i></div>
                <input type="text" id="nombre_cliente" name="nombre_cliente" required [(ngModel)]="d.nombre_cliente" [placeholder]="editar ? '' : 'Ej: Almacenes Éxito S.A.'"
                  class="pl-10 mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 uppercase" [class]="foco" />
              </div>
            </div>
            <div [class]="editar ? 'grid grid-cols-1 md:grid-cols-2 gap-6' : ''">
              <div>
                <label for="codigo_cliente" class="block text-sm font-bold text-gray-700 mb-1">{{ editar ? 'Código Interno' : 'Código Interno / NIT' }}</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-barcode text-gray-400"></i></div>
                  <input type="text" id="codigo_cliente" name="codigo_cliente" [(ngModel)]="d.codigo_cliente" [placeholder]="editar ? '' : 'Ej: CLI-001'"
                    class="pl-10 mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 uppercase" [class]="foco" />
                </div>
                @if (!editar) { <p class="text-xs text-gray-400 mt-1">Este código debe ser único en el sistema.</p> }
              </div>
              @if (editar) {
                <div>
                  <label for="estado" class="block text-sm font-bold text-gray-700 mb-1">Estado</label>
                  <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-toggle-on text-gray-400"></i></div>
                    <select id="estado" name="estado" [(ngModel)]="d.estado" class="pl-10 mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 bg-white cursor-pointer">
                      <option value="1">Activo (Visible)</option>
                      <option value="0">Inactivo (Oculto)</option>
                    </select>
                  </div>
                </div>
              }
            </div>
            <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
              <a routerLink="/clienteVer" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50 transition-all">Cancelar</a>
              @if (editar) {
                <button type="submit" class="px-8 py-3 bg-yellow-500 text-white font-bold rounded-lg shadow-md hover:bg-yellow-600 transition-all transform hover:-translate-y-1"><i class="fas fa-sync-alt mr-2"></i> Actualizar Cliente</button>
              } @else {
                <button type="submit" class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transition-all transform hover:-translate-y-1"><i class="fas fa-save mr-2"></i> Guardar Cliente</button>
              }
            </div>
          </form>
        </div>
      </div>
    }
  `
})
export class ClienteForm implements OnInit {
  private api = inject(Api);
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private titulo = inject(Titulo);
  editar = false;
  id: any = null;
  d: any = { nombre_cliente: '', codigo_cliente: '', estado: '1' };
  original = '';
  errores: string[] = [];
  listo = false;

  get foco() {
    return this.editar ? 'focus:ring-yellow-500 focus:border-yellow-500' : 'focus:ring-indigo-500 focus:border-indigo-500';
  }

  async ngOnInit() {
    this.editar = this.route.snapshot.data['modo'] === 'editar';
    this.titulo.set(this.editar ? 'Editar Cliente' : 'Registrar Cliente');
    if (this.editar) {
      this.id = this.route.snapshot.paramMap.get('id');
      const r = await this.api.get('clienteEditar', 'index', { id: this.id ?? '' });
      if (r.redirect) return void this.router.navigateByUrl('/' + r.redirect);
      this.d = { nombre_cliente: r.datos.nombre_cliente, codigo_cliente: r.datos.codigo_cliente ?? '', estado: String(r.datos.estado) };
      this.original = r.datos.nombre_cliente;
    }
    this.listo = true;
  }

  async guardar(e: Event) {
    e.preventDefault();
    const r = this.editar
      ? await this.api.post('clienteEditar', 'index', { id_cliente: this.id, ...this.d })
      : await this.api.post('clienteCrear', 'index', { nombre_cliente: this.d.nombre_cliente, codigo_cliente: this.d.codigo_cliente });
    if (r.success) this.router.navigateByUrl('/' + r.redirect);
    else {
      this.errores = r.errores || [];
      if (r.datos) this.original = r.datos.nombre_cliente;
    }
  }
}
