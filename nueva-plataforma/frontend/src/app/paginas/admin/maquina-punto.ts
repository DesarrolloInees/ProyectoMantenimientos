import { Component, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import * as XLSX from 'xlsx';
import { Api } from '../../core/api';
import { Titulo, fmt } from '../../core/ui';
import { SelectBuscar } from '../../shared/select-buscar';
import { DtAbajo, DtArriba, TablaDatos, Th } from '../../shared/tabla';

const MODAL = `
  @if (idEliminar !== null) {
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-sm shadow-xl">
        <h3 class="text-lg font-bold text-gray-900 mb-2">{{ modalTitulo }}</h3>
        <p class="text-sm text-gray-500 mb-4">{{ modalTexto }}</p>
        <div class="flex justify-end space-x-3">
          <button (click)="idEliminar = null" class="px-4 py-2 bg-gray-100 rounded-lg text-gray-700 hover:bg-gray-200">Cancelar</button>
          <button (click)="eliminar()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Sí, Eliminar</button>
        </div>
      </div>
    </div>
  }`;

/** Port de maquinaVerVista.php */
@Component({
  selector: 'app-maquina-ver',
  imports: [RouterLink, DtArriba, DtAbajo, Th],
  template: `
    <div class="w-full px-4 md:px-6">
      <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-server text-indigo-600 mr-2"></i> Máquinas</h1>
            <p class="text-gray-500">Gestión de dispositivos instalados.</p>
          </div>
          <a routerLink="/maquinaCrear" class="px-5 py-2.5 bg-indigo-600 text-white font-bold rounded-lg shadow hover:bg-indigo-700 transition flex items-center space-x-2"><i class="fas fa-plus-circle"></i> <span>Nueva Máquina</span></a>
        </div>
        @if (cargado && dt.datos.length) {
          <div class="dt-wrapper py-4">
            <app-dt-arriba [dt]="dt" />
            <div class="overflow-x-auto">
              <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                  <tr>
                    <th app-th [dt]="dt" campo="device_id" class="py-3 px-4">Device ID</th>
                    <th app-th [dt]="dt" campo="nombre_tipo_maquina" class="py-3 px-4">Tipo</th>
                    <th app-th [dt]="dt" campo="nombre_punto" class="py-3 px-4">Ubicación (Punto)</th>
                    <th app-th [dt]="dt" campo="ultima_visita" class="py-3 px-4">Última Visita</th>
                    <th class="py-3 px-4 text-center">Estado Operativo</th>
                    <th class="py-3 px-4 text-center">Acciones</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  @for (m of dt.pagina; track m.id_maquina) {
                    <tr class="hover:bg-gray-50">
                      <td class="py-3 px-4 font-bold text-gray-800 font-mono">{{ m.device_id }}</td>
                      <td class="py-3 px-4"><span class="bg-indigo-50 text-indigo-700 px-2 py-1 rounded-md text-xs font-bold">{{ m.nombre_tipo_maquina }}</span></td>
                      <td class="py-3 px-4">{{ m.nombre_punto }}</td>
                      <td class="py-3 px-4 text-gray-500">{{ m.ultima_visita ? f.fecha(m.ultima_visita) : 'Sin registro' }}</td>
                      <td class="py-3 px-4 text-center">
                        @if (m.activo_operativo == 0) {
                          <span class="bg-red-100 text-red-700 px-2 py-1 rounded-md text-xs font-bold" title="Fuera de Servicio"><i class="fas fa-power-off mr-1"></i>Fuera de Servicio</span>
                        } @else {
                          <span class="bg-green-100 text-green-700 px-2 py-1 rounded-md text-xs font-bold" title="Operativo"><i class="fas fa-check-circle mr-1"></i>Operativo</span>
                        }
                      </td>
                      <td class="py-3 px-4 text-center flex justify-center space-x-2">
                        <a [routerLink]="'/maquinaEditar/' + m.id_maquina" class="p-2 bg-yellow-100 text-yellow-600 rounded-full hover:bg-yellow-200"><i class="fas fa-edit"></i></a>
                        <button (click)="idEliminar = m.id_maquina" class="p-2 bg-red-100 text-red-600 rounded-full hover:bg-red-200"><i class="fas fa-trash-alt"></i></button>
                      </td>
                    </tr>
                  }
                </tbody>
              </table>
            </div>
            <app-dt-abajo [dt]="dt" />
          </div>
        } @else if (cargado) {
          <div class="text-center p-8 bg-gray-50 rounded-lg"><p class="text-gray-500">No hay máquinas registradas.</p></div>
        }
      </div>
    </div>
    ${MODAL}
  `
})
export class MaquinaVer implements OnInit {
  private api = inject(Api);
  f = fmt;
  dt = new TablaDatos<any>();
  cargado = false;
  idEliminar: any = null;
  modalTitulo = 'Eliminar Máquina';
  modalTexto = '¿Seguro? Quedará inactiva.';

  constructor() {
    inject(Titulo).set('Inventario de Máquinas');
  }

  async ngOnInit() {
    this.dt.set((await this.api.get('maquinaVer', 'index')).maquinas);
    this.cargado = true;
  }

  async eliminar() {
    const id = this.idEliminar;
    this.idEliminar = null;
    await this.api.post('maquinaEliminar', 'index', { id });
    this.ngOnInit();
  }
}

/** Port de maquinaCrearVista.php / maquinaEditarVista.php */
@Component({
  selector: 'app-maquina-form',
  imports: [FormsModule, RouterLink, SelectBuscar],
  template: `
    @if (listo) {
      <div [class]="editar ? 'w-full max-w-4xl mx-auto' : 'w-full px-4 md:px-6'">
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
          <div class="mb-6 border-b pb-4">
            @if (editar) {
              <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-edit text-yellow-500 mr-2"></i> Editar Máquina</h1>
              <p class="text-gray-500 mt-1">Modificando: <strong>{{ original }}</strong></p>
            } @else {
              <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-microchip text-indigo-600 mr-2"></i> Nueva Máquina</h1>
              <p class="text-gray-500 mt-1">Registra un dispositivo y asígnalo a un punto.</p>
            }
          </div>
          @if (errores.length) {
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded"><ul class="list-disc list-inside text-sm">@for (e of errores; track $index) { <li>{{ e }}</li> }</ul></div>
          }
          <form (submit)="guardar($event)" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">{{ editar ? 'Device ID' : 'Device ID (Serial)' }} @if (!editar) { <span class="text-red-500">*</span> }</label>
                <input type="text" name="device_id" required [(ngModel)]="d.device_id" [placeholder]="editar ? '' : 'Ej: DEV-2025-001'"
                  class="w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 font-mono uppercase" [class]="editar ? 'focus:ring-yellow-500' : 'focus:ring-indigo-500'" />
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Última Visita</label>
                <input type="date" name="ultima_visita" [(ngModel)]="d.ultima_visita" class="w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2" [class]="editar ? 'focus:ring-yellow-500' : 'focus:ring-indigo-500'" />
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1">Punto de Ubicación @if (!editar) { <span class="text-red-500">*</span> }</label>
                <app-select-buscar name="id_punto" [(ngModel)]="d.id_punto" [opciones]="puntos" valor="id_punto" [texto]="textoPunto" placeholder="-- Buscar y Seleccionar --" [permitirVacio]="!editar" />
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1">Tipo de Máquina @if (!editar) { <span class="text-red-500">*</span> }</label>
                <app-select-buscar name="id_tipo_maquina" [(ngModel)]="d.id_tipo_maquina" [opciones]="tipos" valor="id_tipo_maquina" texto="nombre_tipo_maquina" placeholder="-- Buscar y Seleccionar --" [permitirVacio]="!editar" />
              </div>
              @if (editar) {
                <div class="md:col-span-2">
                  <label class="block text-sm font-bold text-gray-700 mb-1">Estado</label>
                  <select name="estado" [(ngModel)]="d.estado" class="w-full px-3 py-3 border border-gray-300 rounded-lg bg-white">
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                  </select>
                </div>
              }
            </div>
            <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
              <a routerLink="/maquinaVer" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50">Cancelar</a>
              @if (editar) {
                <button type="submit" class="px-8 py-3 bg-yellow-500 text-white font-bold rounded-lg shadow-md hover:bg-yellow-600 transform hover:-translate-y-1 transition-all">Actualizar</button>
              } @else {
                <button type="submit" class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transform hover:-translate-y-1 transition-all">Guardar Máquina</button>
              }
            </div>
          </form>
        </div>
      </div>
    }
  `
})
export class MaquinaForm implements OnInit {
  private api = inject(Api);
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private titulo = inject(Titulo);
  editar = false;
  id: any = null;
  d: any = { device_id: '', ultima_visita: '', id_punto: '', id_tipo_maquina: '', estado: '1' };
  original = '';
  puntos: any[] = [];
  tipos: any[] = [];
  errores: string[] = [];
  listo = false;

  textoPunto = (p: any) => (this.editar ? `${p.nombre_punto} (${p.nombre_cliente})` : `${p.nombre_punto} (Cliente: ${p.nombre_cliente})`);

  async ngOnInit() {
    this.editar = this.route.snapshot.data['modo'] === 'editar';
    this.titulo.set(this.editar ? 'Editar Máquina' : 'Registrar Máquina');
    if (this.editar) {
      this.id = this.route.snapshot.paramMap.get('id');
      const r = await this.api.get('maquinaEditar', 'index', { id: this.id ?? '' });
      if (r.redirect) return void this.router.navigateByUrl('/' + r.redirect);
      this.d = { ...r.datos, ultima_visita: r.datos.ultima_visita ?? '', estado: String(r.datos.estado) };
      this.original = r.datos.device_id;
      this.puntos = r.listaPuntos;
      this.tipos = r.listaTipos;
    } else {
      const r = await this.api.get('maquinaCrear', 'index');
      this.puntos = r.listaPuntos;
      this.tipos = r.listaTipos;
    }
    this.listo = true;
  }

  async guardar(e: Event) {
    e.preventDefault();
    const datos: any = { device_id: this.d.device_id, id_punto: this.d.id_punto, id_tipo_maquina: this.d.id_tipo_maquina, ultima_visita: this.d.ultima_visita };
    if (this.editar) {
      datos.id_maquina = this.id;
      datos.estado = this.d.estado;
    }
    const r = await this.api.post(this.editar ? 'maquinaEditar' : 'maquinaCrear', 'index', datos);
    if (r.success) this.router.navigateByUrl('/' + r.redirect);
    else this.errores = r.errores || [];
  }
}

/** Port de puntoVerVista.php (con exportación Excel respetando el filtro) */
@Component({
  selector: 'app-punto-ver',
  imports: [RouterLink, DtArriba, DtAbajo, Th],
  template: `
    <div class="flex justify-between items-center mb-6 border-b pb-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-store-alt text-red-600 mr-2"></i> Puntos de Atención</h1>
        <p class="text-gray-500">Gestión de sucursales y ubicaciones.</p>
      </div>
      <div class="flex space-x-3">
        <button (click)="descargarExcelPuntos()" class="px-5 py-2.5 bg-green-600 text-white font-bold rounded-lg shadow hover:bg-green-700 transition flex items-center space-x-2"><i class="fas fa-file-excel"></i> <span>Exportar Excel</span></button>
        <a routerLink="/puntoCrear" class="px-5 py-2.5 bg-red-600 text-white font-bold rounded-lg shadow hover:bg-red-700 transition flex items-center space-x-2"><i class="fas fa-plus-circle"></i> <span>Nuevo Punto</span></a>
      </div>
    </div>
    @if (cargado && dt.datos.length) {
      <div class="dt-wrapper">
        <app-dt-arriba [dt]="dt" />
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
              <tr>
                <th app-th [dt]="dt" campo="id_punto" class="py-3 px-4">ID</th>
                <th app-th [dt]="dt" campo="nombre_punto" class="py-3 px-4">Punto</th>
                <th app-th [dt]="dt" campo="nombre_cliente" class="py-3 px-4">Cliente</th>
                <th app-th [dt]="dt" campo="nombre_municipio" class="py-3 px-4">Ubicación</th>
                <th app-th [dt]="dt" campo="nombre_modalidad" class="py-3 px-4">Modalidad</th>
                <th class="py-3 px-4 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              @for (p of dt.pagina; track p.id_punto) {
                <tr class="hover:bg-gray-50">
                  <td class="py-3 px-4 font-bold text-gray-600">#{{ p.id_punto }}</td>
                  <td class="py-3 px-4"><div class="font-bold text-gray-800">{{ p.nombre_punto }}</div><div class="text-xs text-gray-500">{{ p.direccion }}</div></td>
                  <td class="py-3 px-4">{{ p.nombre_cliente }}</td>
                  <td class="py-3 px-4">
                    <span class="block">{{ p.nombre_municipio }}</span>
                    @if (p.nombre_delegacion) { <span class="text-xs text-indigo-600 bg-indigo-50 px-1 rounded">{{ p.nombre_delegacion }}</span> }
                  </td>
                  <td class="py-3 px-4">{{ p.nombre_modalidad }}</td>
                  <td class="py-3 px-4 text-center flex justify-center space-x-2">
                    <a [routerLink]="'/puntoEditar/' + p.id_punto" class="p-2 bg-yellow-100 text-yellow-600 rounded-full hover:bg-yellow-200"><i class="fas fa-edit"></i></a>
                    <button (click)="idEliminar = p.id_punto" class="p-2 bg-red-100 text-red-600 rounded-full hover:bg-red-200"><i class="fas fa-trash-alt"></i></button>
                  </td>
                </tr>
              }
            </tbody>
          </table>
        </div>
        <app-dt-abajo [dt]="dt" />
      </div>
    } @else if (cargado) {
      <div class="text-center p-8 bg-gray-50 rounded-lg"><p class="text-gray-500">No hay puntos registrados.</p></div>
    }
    ${MODAL}
  `
})
export class PuntoVer implements OnInit {
  private api = inject(Api);
  dt = new TablaDatos<any>();
  datosExcel: any[] = [];
  cargado = false;
  idEliminar: any = null;
  modalTitulo = 'Eliminar Punto';
  modalTexto = '¿Seguro? Quedará inactivo en el sistema.';

  constructor() {
    inject(Titulo).set('Listado de Puntos');
  }

  async ngOnInit() {
    const r = await this.api.get('puntoVer', 'index');
    this.dt.set(r.puntos);
    this.datosExcel = r.datosExcel || [];
    this.cargado = true;
  }

  async eliminar() {
    const id = this.idEliminar;
    this.idEliminar = null;
    await this.api.post('puntoEliminar', 'index', { id });
    this.ngOnInit();
  }

  descargarExcelPuntos() {
    if (!this.datosExcel.length) {
      alert('No hay datos cargados en el sistema.');
      return;
    }
    const ids = new Set(this.dt.filtrados.map((p: any) => String(p.id_punto)));
    if (!ids.size) {
      alert('No hay registros para exportar con el filtro actual.');
      return;
    }
    const data = this.datosExcel
      .filter((i) => ids.has(String(i.id_punto)))
      .map((i) => ({
        Cliente: i.Cliente,
        Punto: i.Punto,
        'Tipo de Máquina': i.Tipo_Maquina || 'Sin Máquina',
        'Device ID': i.Device_ID || 'N/A',
        Dirección: i.Direccion
      }));
    const hoja = XLSX.utils.json_to_sheet(data);
    const libro = XLSX.utils.book_new();
    hoja['!cols'] = [{ wch: 25 }, { wch: 35 }, { wch: 20 }, { wch: 15 }, { wch: 40 }];
    XLSX.utils.book_append_sheet(libro, hoja, 'Puntos de Atención');
    const hoy = new Date().toISOString().slice(0, 10).replace(/-/g, '');
    XLSX.writeFile(libro, `Reporte_Puntos_${hoy}.xlsx`);
  }
}

/** Port de puntoCrearVista.php / puntoEditarVista.php */
@Component({
  selector: 'app-punto-form',
  imports: [FormsModule, RouterLink],
  template: `
    @if (listo) {
      <div class="w-full max-w-4xl mx-auto">
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
          <div class="mb-6 border-b pb-4">
            @if (editar) {
              <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-edit text-yellow-500 mr-2"></i> Editar Punto</h1>
              <p class="text-gray-500 mt-1">Modificando: <strong>{{ original }}</strong></p>
            } @else {
              <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-map-marker-alt text-red-600 mr-2"></i> Nuevo Punto de Atención</h1>
              <p class="text-gray-500 mt-1">Registra la ubicación donde se prestará el servicio.</p>
            }
          </div>
          @if (errores.length) {
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded"><ul class="list-disc list-inside text-sm">@for (e of errores; track $index) { <li>{{ e }}</li> }</ul></div>
          }
          <form (submit)="guardar($event)" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1">{{ editar ? 'Nombre' : 'Nombre del Punto' }} @if (!editar) { <span class="text-red-500">*</span> }</label>
                <input type="text" name="nombre_punto" required [(ngModel)]="d.nombre_punto" [placeholder]="editar ? '' : 'Ej: Sucursal Centro Comercial Andino'"
                  class="w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2" [class]="editar ? 'focus:ring-yellow-500' : 'focus:ring-red-500'" />
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">{{ editar ? 'Cliente' : 'Cliente / Empresa' }} @if (!editar) { <span class="text-red-500">*</span> }</label>
                <select name="id_cliente" required [(ngModel)]="d.id_cliente" class="w-full px-3 py-3 border border-gray-300 rounded-lg bg-white">
                  @if (!editar) { <option value="">-- Seleccionar --</option> }
                  @for (c of l.listaClientes; track c.id_cliente) { <option [value]="c.id_cliente + ''">{{ c.nombre_cliente }}</option> }
                </select>
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Modalidad @if (!editar) { <span class="text-red-500">*</span> }</label>
                <select name="id_modalidad" required [(ngModel)]="d.id_modalidad" class="w-full px-3 py-3 border border-gray-300 rounded-lg bg-white">
                  @if (!editar) { <option value="">-- Seleccionar --</option> }
                  @for (m of l.listaModalidades; track m.id_modalidad) { <option [value]="m.id_modalidad + ''">{{ m.nombre_modalidad }}</option> }
                </select>
              </div>
            </div>
            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200 grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Municipio @if (!editar) { <span class="text-red-500">*</span> }</label>
                <select name="id_municipio" required [(ngModel)]="d.id_municipio" class="w-full px-3 py-3 border border-gray-300 rounded-lg bg-white">
                  @if (!editar) { <option value="">-- Seleccionar --</option> }
                  @for (m of l.listaMunicipios; track m.id_municipio) { <option [value]="m.id_municipio + ''">{{ m.nombre_municipio }}</option> }
                </select>
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Delegación</label>
                <select name="id_delegacion" [(ngModel)]="d.id_delegacion" class="w-full px-3 py-3 border border-gray-300 rounded-lg bg-white">
                  <option value="">-- Ninguna --</option>
                  @for (x of l.listaDelegaciones; track x.id_delegacion) { <option [value]="x.id_delegacion + ''">{{ x.nombre_delegacion }}</option> }
                </select>
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Zona</label>
                <input type="text" name="zona" [(ngModel)]="d.zona" placeholder="Ej: Zona Norte, Centro, etc." class="w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm" />
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1">{{ editar ? 'Dirección' : 'Dirección Física' }}</label>
                <input type="text" name="direccion" [(ngModel)]="d.direccion" [placeholder]="editar ? '' : 'Ej: Calle 85 # 11-53'" class="w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm" />
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div><label class="block text-sm font-bold text-gray-700 mb-1">Código 1</label><input type="text" name="codigo_1" [(ngModel)]="d.codigo_1" class="w-full px-3 py-3 border border-gray-300 rounded-lg" /></div>
              <div><label class="block text-sm font-bold text-gray-700 mb-1">Código 2</label><input type="text" name="codigo_2" [(ngModel)]="d.codigo_2" class="w-full px-3 py-3 border border-gray-300 rounded-lg" /></div>
              @if (editar) {
                <div class="md:col-span-2">
                  <label class="block text-sm font-bold text-gray-700 mb-1">Estado del Punto</label>
                  <select name="estado" [(ngModel)]="d.estado" class="w-full px-3 py-3 border border-gray-300 rounded-lg bg-white"><option value="1">Activo</option><option value="0">Inactivo</option></select>
                </div>
              }
            </div>
            <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
              <a routerLink="/puntoVer" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50">Cancelar</a>
              @if (editar) {
                <button type="submit" class="px-8 py-3 bg-yellow-500 text-white font-bold rounded-lg shadow-md hover:bg-yellow-600 transform hover:-translate-y-1 transition-all">Actualizar</button>
              } @else {
                <button type="submit" class="px-8 py-3 bg-red-600 text-white font-bold rounded-lg shadow-md hover:bg-red-700 transform hover:-translate-y-1 transition-all">Guardar Punto</button>
              }
            </div>
          </form>
        </div>
      </div>
    }
  `
})
export class PuntoForm implements OnInit {
  private api = inject(Api);
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private titulo = inject(Titulo);
  editar = false;
  id: any = null;
  l: any = {};
  d: any = { nombre_punto: '', direccion: '', codigo_1: '', codigo_2: '', zona: '', id_municipio: '', id_delegacion: '', id_modalidad: '', id_cliente: '', estado: '1' };
  original = '';
  errores: string[] = [];
  listo = false;

  async ngOnInit() {
    this.editar = this.route.snapshot.data['modo'] === 'editar';
    this.titulo.set(this.editar ? 'Editar Punto' : 'Crear Punto');
    if (this.editar) {
      this.id = this.route.snapshot.paramMap.get('id');
      const r = await this.api.get('puntoEditar', 'index', { id: this.id ?? '' });
      if (r.redirect) return void this.router.navigateByUrl('/' + r.redirect);
      this.l = r;
      const s = (v: any) => (v === null || v === undefined ? '' : String(v));
      this.d = {
        nombre_punto: s(r.datos.nombre_punto), direccion: s(r.datos.direccion), codigo_1: s(r.datos.codigo_1), codigo_2: s(r.datos.codigo_2),
        zona: s(r.datos.zona), id_municipio: s(r.datos.id_municipio), id_delegacion: s(r.datos.id_delegacion), id_modalidad: s(r.datos.id_modalidad),
        id_cliente: s(r.datos.id_cliente), estado: s(r.datos.estado ?? 1)
      };
      this.original = r.datos.nombre_punto;
    } else {
      this.l = await this.api.get('puntoCrear', 'index');
    }
    this.listo = true;
  }

  async guardar(e: Event) {
    e.preventDefault();
    const datos: any = { ...this.d };
    if (this.editar) datos.id_punto = this.id;
    else delete datos.estado;
    const r = await this.api.post(this.editar ? 'puntoEditar' : 'puntoCrear', 'index', datos);
    if (r.success) this.router.navigateByUrl('/' + r.redirect);
    else this.errores = r.errores || [];
  }
}
