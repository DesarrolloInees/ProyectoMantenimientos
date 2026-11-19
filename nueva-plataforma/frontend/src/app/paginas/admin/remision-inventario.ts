import { Component, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import * as XLSX from 'xlsx';
import { Api } from '../../core/api';
import { Auth } from '../../core/auth';
import { Titulo, fmt } from '../../core/ui';
import { SelectBuscar } from '../../shared/select-buscar';
import { DtAbajo, DtArriba, TablaDatos, TablaServidor, Th } from '../../shared/tabla';

/** Port de controlRemisionVerVista.php (DataTable serverSide) */
@Component({
  selector: 'app-remision-ver',
  imports: [RouterLink, DtArriba, DtAbajo, Th],
  template: `
    <div class="w-full px-4 md:px-6">
      <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md border border-gray-100">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 border-b pb-4">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800"><i class="fas fa-boxes text-indigo-600 mr-2"></i> Inventario de Remisiones</h1>
            <p class="text-gray-500 mt-1">Gestiona el inventario de Remisiones.</p>
          </div>
          <a routerLink="/controlRemisionCrear" class="mt-4 sm:mt-0 px-5 py-2.5 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transition-all duration-300 transform hover:scale-105 flex items-center space-x-2"><i class="fas fa-plus-circle"></i><span>Nuevas Remisiones</span></a>
        </div>
        <div class="w-full px-4 md:px-6">
          <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 overflow-hidden dt-wrapper">
            <app-dt-arriba [dt]="dt" [opciones]="[10, 25, 50, 100]" />
            <div class="overflow-x-auto relative">
              @if (dt.cargando) { <div class="absolute inset-0 bg-white/60 flex items-center justify-center text-gray-500 text-sm">Procesando...</div> }
              <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                  <tr>
                    <th app-th [dt]="dt" campo="numero_remision" class="px-6 py-3"># Remisión</th>
                    <th app-th [dt]="dt" campo="nombre_tecnico" class="px-6 py-3">Técnico Responsable</th>
                    <th app-th [dt]="dt" campo="nombre_estado" class="px-6 py-3">Estado</th>
                    <th app-th [dt]="dt" campo="fecha_asignacion" class="px-6 py-3">Fecha Asignación</th>
                    <th app-th [dt]="dt" campo="fecha_uso" class="px-6 py-3">Fecha Uso</th>
                    <th class="px-6 py-3 text-center">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  @for (r of dt.pagina; track r.id_control) {
                    <tr class="border-b">
                      <td class="px-6 py-3 font-bold text-gray-900">{{ r.numero_remision }}</td>
                      <td class="px-6 py-3">{{ r.nombre_tecnico }}</td>
                      <td class="px-6 py-3">
                        @switch (r.nombre_estado) {
                          @case ('DISPONIBLE') { <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Disponible</span> }
                          @case ('USADA') { <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">Usada</span> }
                          @case ('ANULADA') { <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded">Anulada</span> }
                          @default { <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">{{ r.nombre_estado }}</span> }
                        }
                      </td>
                      <td class="px-6 py-3">{{ fechaLocal(r.fecha_asignacion) }}</td>
                      <td class="px-6 py-3">
                        @if (r.fecha_uso) {
                          <div class="flex flex-col"><span class="text-gray-900 font-medium">{{ fechaLocal(r.fecha_uso) }}</span><span class="text-xs text-gray-400">{{ horaLocal(r.fecha_uso) }}</span></div>
                        } @else { <span class="text-gray-300">---</span> }
                      </td>
                      <td class="px-6 py-3">
                        @if (r.nombre_estado != 'USADA') {
                          <div class="text-center">
                            <a [routerLink]="'/controlRemisionEditar'" [queryParams]="{ id: r.id_control }" class="text-indigo-600 hover:text-indigo-900 mx-2" title="Editar"><i class="fas fa-edit"></i></a>
                            <a href="#" (click)="eliminar($event, r.id_control)" class="text-red-600 hover:text-red-900 mx-2" title="Eliminar"><i class="fas fa-trash"></i></a>
                          </div>
                        } @else { <span class="text-gray-300 italic text-xs">Bloqueado</span> }
                      </td>
                    </tr>
                  } @empty {
                    <tr><td colspan="6" class="text-center py-6 text-gray-400">Ningún dato disponible en esta tabla</td></tr>
                  }
                </tbody>
              </table>
            </div>
            <app-dt-abajo [dt]="dt" />
          </div>
        </div>
      </div>
    </div>
  `
})
export class RemisionVer implements OnInit {
  private api = inject(Api);
  dt = new TablaServidor<any>((p) => this.api.get('controlRemisionVer', 'obtenerDatosDatatable', p), 100, [
    'numero_remision', 'nombre_tecnico', 'nombre_estado', 'fecha_asignacion', 'fecha_uso'
  ]);

  constructor() {
    inject(Titulo).set('Control de Remisiones');
  }

  ngOnInit() {
    this.dt.campoOrden = 'numero_remision';
    this.dt.ordenAsc = false;
    this.dt.recargar();
  }

  private fecha(v: string) {
    return new Date(String(v).replace(' ', 'T'));
  }
  fechaLocal(v: any) {
    return v ? this.fecha(v).toLocaleDateString('es-ES') : '';
  }
  horaLocal(v: any) {
    return v ? this.fecha(v).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }) : '';
  }

  async eliminar(e: Event, id: any) {
    e.preventDefault();
    if (!confirm('¿Eliminar esta remisión?')) return;
    await this.api.post('controlRemisionEliminar', 'index', { id });
    this.dt.recargar();
  }
}

/** Port de controlRemisionCrearVista.php */
@Component({
  selector: 'app-remision-crear',
  imports: [FormsModule, RouterLink],
  template: `
    <div class="w-full px-4 md:px-6">
      <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md border border-gray-100">
        <div class="mb-6 border-b pb-4">
          <h1 class="text-2xl sm:text-3xl font-bold text-gray-800"><i class="fas fa-ticket-alt text-indigo-600 mr-2"></i> Asignar Remisiones</h1>
          <p class="text-gray-500 mt-1">Registra remisiones permitiendo duplicados entre técnicos diferentes.</p>
        </div>
        @if (errores.length) {
          <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm"><ul class="list-disc list-inside ml-2 text-sm">@for (e of errores; track $index) { <li>{{ e }}</li> }</ul></div>
        }
        <form (submit)="guardar($event)" class="space-y-6">
          <div>
            <label for="id_tecnico" class="block text-sm font-bold text-gray-700 mb-1">Técnico Responsable <span class="text-red-500">*</span></label>
            <div class="relative">
              <select id="id_tecnico" name="id_tecnico" required [(ngModel)]="idTecnico" (ngModelChange)="consultarUltima()" class="pl-3 mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 bg-white">
                <option value="">-- Seleccione un Técnico --</option>
                @for (t of tecnicos; track t.id_tecnico) { <option [value]="t.id_tecnico + ''">{{ t.nombre_tecnico }}</option> }
              </select>
            </div>
            <p class="text-xs text-indigo-600 mt-1 font-semibold h-4">{{ msgUltima }}</p>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Número Inicial <span class="text-red-500">*</span></label>
              <input type="number" name="remision_inicio" required [(ngModel)]="inicio" placeholder="Ej: 100" class="mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Número Final (Opcional)</label>
              <input type="number" name="remision_fin" [(ngModel)]="fin" placeholder="Ej: 150" class="mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500" />
              <p class="text-xs text-gray-400 mt-1">Déjalo vacío si solo vas a registrar una.</p>
            </div>
          </div>
          <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
            <a routerLink="/controlRemisionVer" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50">Cancelar</a>
            <button type="submit" class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transform hover:-translate-y-1 transition-all"><i class="fas fa-save mr-2"></i> Guardar Asignación</button>
          </div>
        </form>
      </div>
    </div>
  `
})
export class RemisionCrear implements OnInit {
  private api = inject(Api);
  private router = inject(Router);
  tecnicos: any[] = [];
  idTecnico = '';
  inicio: any = '';
  fin: any = '';
  msgUltima = '';
  errores: string[] = [];

  constructor() {
    inject(Titulo).set('Asignar Remisiones');
  }

  async ngOnInit() {
    this.tecnicos = (await this.api.get('controlRemisionCrear', 'index')).listaTecnicos;
  }

  async consultarUltima() {
    if (!this.idTecnico) {
      this.msgUltima = '';
      this.inicio = '';
      return;
    }
    try {
      const data = await this.api.get('controlRemisionCrear', 'getUltima', { id_tecnico: this.idTecnico });
      if (data.ultima != 0) {
        this.msgUltima = 'Última remisión registrada: ' + data.ultima;
        if (data.siguiente !== '') this.inicio = data.siguiente;
      } else {
        this.msgUltima = 'Este técnico no tiene remisiones asignadas aún.';
        this.inicio = '';
      }
    } catch {
      this.msgUltima = 'Error al consultar última remisión.';
    }
  }

  async guardar(e: Event) {
    e.preventDefault();
    const r = await this.api.post('controlRemisionCrear', 'index', { id_tecnico: this.idTecnico, remision_inicio: this.inicio ?? '', remision_fin: this.fin ?? '' });
    if (r.success) this.router.navigateByUrl('/' + r.redirect);
    else this.errores = r.errores || [];
  }
}

/** Port de controlRemisionEditarVista.php */
@Component({
  selector: 'app-remision-editar',
  imports: [FormsModule, RouterLink, SelectBuscar],
  template: `
    @if (d) {
      <div class="w-full px-4 md:px-6">
        <div class="bg-white p-8 rounded-xl shadow-md border border-gray-100">
          <div class="mb-6 border-b pb-4"><h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-edit text-indigo-600 mr-2"></i> Editar Remisión</h1></div>
          @if (errores.length) {
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded"><ul class="list-disc list-inside text-sm">@for (e of errores; track $index) { <li>{{ e }}</li> }</ul></div>
          }
          <form (submit)="guardar($event)" class="space-y-6">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Número de Remisión</label>
              <input type="text" name="numero_remision" [(ngModel)]="d.numero_remision" required class="block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 bg-gray-50" />
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Técnico Responsable</label>
              <app-select-buscar name="id_tecnico" [(ngModel)]="d.id_tecnico" [opciones]="tecnicos" valor="id_tecnico" texto="nombre_tecnico" placeholder="Seleccione un técnico" />
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Estado</label>
              <select name="id_estado" [(ngModel)]="d.id_estado" class="block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500">
                @if (estadoOriginal === 'USADA') {
                  <option [value]="d.id_estado">USADA (Asignada a Orden)</option>
                } @else {
                  @for (e of estados; track e.id_estado) {
                    @if (e.nombre_estado !== 'USADA') { <option [value]="e.id_estado + ''">{{ e.nombre_estado }}</option> }
                  }
                }
              </select>
              <p class="text-xs text-gray-500 mt-1">Seleccione el estado actual de la remisión física.</p>
            </div>
            <div class="pt-6 border-t flex justify-end space-x-4">
              <a routerLink="/controlRemisionVer" class="px-6 py-3 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</a>
              <button type="submit" class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-lg shadow hover:bg-indigo-700 transition"><i class="fas fa-save mr-2"></i> Actualizar</button>
            </div>
          </form>
        </div>
      </div>
    }
  `
})
export class RemisionEditar implements OnInit {
  private api = inject(Api);
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  id: any;
  d: any = null;
  estadoOriginal = '';
  tecnicos: any[] = [];
  estados: any[] = [];
  errores: string[] = [];

  constructor() {
    inject(Titulo).set('Editar Remisión');
  }

  async ngOnInit() {
    this.id = this.route.snapshot.queryParamMap.get('id') ?? this.route.snapshot.paramMap.get('id');
    const r = await this.api.get('controlRemisionEditar', 'index', { id: this.id ?? '' });
    if (r.redirect) return void this.router.navigateByUrl('/' + r.redirect);
    this.tecnicos = r.listaTecnicos;
    this.estados = r.listaEstados;
    this.estadoOriginal = r.datos.nombre_estado;
    this.d = { numero_remision: r.datos.numero_remision, id_tecnico: r.datos.id_tecnico, id_estado: String(r.datos.id_estado) };
  }

  async guardar(e: Event) {
    e.preventDefault();
    const r = await this.api.post('controlRemisionEditar', 'index', { id: this.id, ...this.d });
    if (r.success) this.router.navigateByUrl('/' + r.redirect);
    else this.errores = r.errores || [];
  }
}

/** Port de inventarioTecnicoVerVista.php */
@Component({
  selector: 'app-inventario-ver',
  imports: [RouterLink, DtArriba, DtAbajo, Th],
  template: `
    <div class="w-full px-4 md:px-6">
      <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
        <div class="flex gap-2 items-center flex-wrap">
          <form (submit)="pdf($event, fd.value, fh.value)" class="flex items-center gap-2 bg-gray-50 p-1 rounded-lg border border-gray-200">
            <input type="date" name="fecha_desde" #fd class="border rounded px-2 py-1 text-sm bg-white" placeholder="Desde" />
            <input type="date" name="fecha_hasta" #fh class="border rounded px-2 py-1 text-sm bg-white" placeholder="Hasta" />
            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-red-700 transition shadow-md flex items-center gap-2 text-sm"><i class="fas fa-file-pdf"></i> PDF</button>
          </form>
          @if (dt.datos.length) {
            <button type="button" (click)="generarExcelInventario()" class="bg-green-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-green-700 transition shadow-md flex items-center gap-2 text-sm"><i class="fas fa-file-excel"></i> Exportar</button>
          }
          @if (!esTecnico) {
            <a routerLink="/inventarioTecnicoCrear" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-indigo-700 transition shadow-md flex items-center gap-2 text-sm"><i class="fas fa-plus"></i> Asignar Stock</a>
          } @else {
            <a routerLink="/solicitarInventario" class="bg-purple-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-purple-700 transition shadow-md flex items-center gap-2 text-sm"><i class="fas fa-clipboard-list"></i> Solicitar Repuestos</a>
          }
        </div>
        <div class="overflow-hidden dt-wrapper mt-4">
          <app-dt-arriba [dt]="dt" />
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-50 text-gray-700 uppercase text-xs tracking-wider">
                <th app-th [dt]="dt" campo="nombre_tecnico" class="p-4 border-b">Técnico</th>
                <th app-th [dt]="dt" campo="nombre_repuesto" class="p-4 border-b">Repuesto</th>
                <th app-th [dt]="dt" campo="cantidad_actual" class="p-4 border-b text-center">Cantidad</th>
                <th app-th [dt]="dt" campo="ultima_actualizacion" class="p-4 border-b text-center">Última Carga</th>
                <th class="p-4 border-b text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-100">
              @for (item of dt.pagina; track item.id_inventario; let impar = $odd) {
                <tr [class.bg-gray-50]="impar" class="hover:bg-gray-100">
                  <td class="p-4 font-bold text-gray-800">{{ item.nombre_tecnico }}</td>
                  <td class="p-4 text-gray-600">
                    <div class="flex flex-col">
                      <span class="font-medium">{{ item.nombre_repuesto }}</span>
                      @if (item.codigo_referencia) { <span class="text-xs text-gray-400">Ref: {{ item.codigo_referencia }}</span> }
                    </div>
                  </td>
                  <td class="p-4 text-center">
                    @if (item.cantidad_actual > 0) {
                      <span class="bg-green-100 text-green-800 py-1 px-3 rounded-full font-bold text-xs">{{ item.cantidad_actual }}</span>
                    } @else {
                      <span class="bg-red-100 text-red-800 py-1 px-3 rounded-full font-bold text-xs">Agotado</span>
                    }
                  </td>
                  <td class="p-4 text-center text-xs text-gray-500">{{ f.fecha(item.ultima_actualizacion) }} {{ (item.ultima_actualizacion || '').substring(11, 16) }}</td>
                  <td class="p-4 text-right space-x-2">
                    @if (!esTecnico) {
                      <a routerLink="/inventarioTecnicoEditar" [queryParams]="{ id: item.id_inventario }" class="text-blue-500 hover:text-blue-700 bg-blue-50 p-2 rounded-full hover:bg-blue-100 transition inline-block"><i class="fas fa-edit"></i></a>
                      <a href="#" (click)="eliminar($event, item.id_inventario)" class="text-red-500 hover:text-red-700 bg-red-50 p-2 rounded-full hover:bg-red-100 transition inline-block"><i class="fas fa-trash-alt"></i></a>
                    } @else {
                      <a routerLink="/solicitarInventario" class="text-purple-600 hover:text-purple-800 bg-purple-50 p-2 rounded-full hover:bg-purple-100 transition inline-block"><i class="fas fa-clipboard-list"></i></a>
                    }
                  </td>
                </tr>
              } @empty {
                <tr><td colspan="5" class="p-4 text-center text-gray-400">Ningún dato disponible en esta tabla</td></tr>
              }
            </tbody>
          </table>
          <app-dt-abajo [dt]="dt" />
        </div>
      </div>
    </div>
  `
})
export class InventarioVer implements OnInit {
  private api = inject(Api);
  private titulo = inject(Titulo);
  f = fmt;
  dt = new TablaDatos<any>();
  esTecnico = false;

  async ngOnInit() {
    const r = await this.api.get('inventarioTecnicoVer', 'index');
    this.titulo.set(r.titulo);
    this.esTecnico = r.esTecnico;
    this.dt.set(r.inventario);
    this.dt.campoOrden = 'nombre_tecnico';
  }

  pdf(e: Event, desde: string, hasta: string) {
    e.preventDefault();
    this.api.abrir('reporteRepuestos', 'index', { fecha_desde: desde, fecha_hasta: hasta });
  }

  async eliminar(e: Event, id: any) {
    e.preventDefault();
    if (!confirm('¿Seguro?')) return;
    await this.api.post('inventarioTecnicoEliminar', 'index', { id });
    this.ngOnInit();
  }

  generarExcelInventario() {
    const datos = this.dt.datos;
    if (!datos.length) return alert('No hay datos de inventario para exportar');
    const wb = XLSX.utils.book_new();
    const porTecnico: Record<string, any[]> = {};
    datos.forEach((d: any) => {
      const t = d.nombre_tecnico || 'Sin Asignar';
      (porTecnico[t] ||= []).push({
        Repuesto: d.nombre_repuesto,
        Referencia: d.codigo_referencia || 'S/R',
        'Cantidad Actual': d.cantidad_actual,
        'Última Actualización': d.ultima_actualizacion
      });
    });
    Object.keys(porTecnico).forEach((t) => {
      const ws = XLSX.utils.json_to_sheet(porTecnico[t]);
      ws['!cols'] = [{ wch: 35 }, { wch: 15 }, { wch: 15 }, { wch: 25 }];
      XLSX.utils.book_append_sheet(wb, ws, t.replace(/[\\/?*\[\]:]/g, '').substring(0, 31));
    });
    XLSX.writeFile(wb, `Inventario_Tecnicos_${new Date().toISOString().slice(0, 10)}.xlsx`);
  }
}

/** Port de inventarioTecnicoCrearVista.php (asignación masiva de stock) */
@Component({
  selector: 'app-inventario-crear',
  imports: [FormsModule, RouterLink, SelectBuscar],
  template: `
    <div class="w-full px-4 md:px-6">
      <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-200">
        <div class="mb-6 border-b pb-4 flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-dolly-flatbed text-indigo-600 mr-2"></i> Asignación Masiva</h1>
            <p class="text-gray-500 mt-1 text-sm">Carga múltiples repuestos a un solo técnico.</p>
          </div>
          <a routerLink="/inicio" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></a>
        </div>
        @if (mensajeExito) {
          <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm"><i class="fas fa-check-circle mr-2"></i> <span [innerHTML]="mensajeExito"></span></div>
        }
        @if (errores.length) {
          <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
            <p class="font-bold">Hubo errores en la carga:</p>
            <ul class="list-disc list-inside ml-4 text-sm">@for (e of errores; track $index) { <li>{{ e }}</li> }</ul>
          </div>
        }
        <form (submit)="guardar($event)">
          <div class="bg-indigo-50 p-4 rounded-lg mb-6 border border-indigo-100">
            <label class="block text-sm font-bold text-indigo-900 mb-1"><i class="fas fa-users mr-1"></i> Técnicos Responsables (Destino)</label>
            <p class="text-xs text-indigo-600 mb-2">Puedes seleccionar varios técnicos a la vez.</p>
            <app-select-buscar name="ids_tecnicos" [multiple]="true" [(ngModel)]="tecnicosSel" [opciones]="tecnicos" valor="id_tecnico" texto="nombre_tecnico" placeholder="Seleccione uno o varios técnicos" />
          </div>
          <div class="mb-4">
            <label class="block text-sm font-bold text-gray-700 mb-2">Detalle de Repuestos</label>
            <table class="w-full text-left border-collapse">
              <thead><tr class="text-xs text-gray-500 uppercase border-b"><th class="py-2 w-2/3">Repuesto / Insumo</th><th class="py-2 w-24 text-center">Cant.</th><th class="py-2 w-10"></th></tr></thead>
              <tbody>
                @for (f of filas; track f.uid) {
                  <tr class="border-b border-gray-100">
                    <td class="py-2 pr-2"><app-select-buscar [name]="'rep_' + f.uid" [(ngModel)]="f.id_repuesto" [opciones]="repuestos" valor="id_repuesto" [texto]="textoRepuesto" placeholder="- Buscar -" /></td>
                    <td class="py-2 px-2"><input type="number" [name]="'cant_' + f.uid" min="1" [(ngModel)]="f.cantidad" required class="w-full h-[42px] border border-gray-300 rounded text-center font-bold text-indigo-700" /></td>
                    <td class="py-2 text-center"><button type="button" (click)="eliminarFila(f)" class="text-red-400 hover:text-red-600 p-2"><i class="fas fa-trash-alt"></i></button></td>
                  </tr>
                }
              </tbody>
            </table>
          </div>
          <div class="flex justify-center mb-8">
            <button type="button" (click)="agregarFila()" class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-full border border-gray-300 transition flex items-center"><i class="fas fa-plus-circle text-indigo-500 mr-2"></i> Agregar otro ítem</button>
          </div>
          <div class="pt-6 border-t border-gray-100 flex justify-end space-x-3">
            <a routerLink="/inventarioTecnicoVer" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50 transition">Cancelar</a>
            <button type="submit" class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transform hover:-translate-y-1 transition-all"><i class="fas fa-save mr-2"></i> Guardar Todo</button>
          </div>
        </form>
      </div>
    </div>
  `
})
export class InventarioCrear implements OnInit {
  private api = inject(Api);
  tecnicos: any[] = [];
  repuestos: any[] = [];
  tecnicosSel: any[] = [];
  filas: { uid: number; id_repuesto: any; cantidad: number }[] = [];
  mensajeExito = '';
  errores: string[] = [];

  textoRepuesto = (r: any) => `${r.nombre_repuesto} ${r.codigo_referencia ? '(' + r.codigo_referencia + ')' : ''}`;

  constructor() {
    inject(Titulo).set('Asignación Masiva de Stock');
  }

  async ngOnInit() {
    const r = await this.api.get('inventarioTecnicoCrear', 'index');
    this.tecnicos = r.listaTecnicos;
    this.repuestos = r.listaRepuestos;
    this.filas = [];
    this.agregarFila();
  }

  agregarFila() {
    this.filas.push({ uid: Date.now() + Math.random(), id_repuesto: '', cantidad: 1 });
  }

  eliminarFila(f: any) {
    if (this.filas.length > 1) this.filas = this.filas.filter((x) => x !== f);
    else alert('Debe haber al menos un repuesto.');
  }

  async guardar(e: Event) {
    e.preventDefault();
    if (!this.tecnicosSel.length) return alert('Seleccione uno o varios técnicos');
    if (this.filas.some((f) => !f.id_repuesto)) return alert('Seleccione el repuesto en todas las filas');
    this.mensajeExito = '';
    this.errores = [];
    const r = await this.api.post('inventarioTecnicoCrear', 'index', {
      ids_tecnicos: this.tecnicosSel,
      repuestos: this.filas.map((f) => f.id_repuesto),
      cantidades: this.filas.map((f) => f.cantidad)
    });
    if (r.success) {
      this.mensajeExito = r.mensajeExito;
      this.tecnicosSel = [];
      this.filas = [];
      this.agregarFila();
    } else {
      this.errores = r.errores || [];
    }
  }
}

/** Port de inventarioTecnicoEditarVista.php */
@Component({
  selector: 'app-inventario-editar',
  imports: [FormsModule, RouterLink],
  template: `
    @if (d) {
      <div class="w-full px-4 md:px-6">
        <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-200">
          <div class="mb-6 border-b pb-4">
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-edit text-orange-500 mr-2"></i> Ajuste Manual de Stock</h1>
            <p class="text-gray-500 mt-1 text-sm">Corrige la cantidad actual en posesión del técnico.</p>
          </div>
          @if (errores.length) {
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded text-sm">@for (e of errores; track $index) { <p>{{ e }}</p> }</div>
          }
          <form (submit)="guardar($event)" class="space-y-6">
            <div class="grid grid-cols-2 gap-4">
              <div class="bg-gray-50 p-3 rounded-lg border"><label class="block text-xs font-bold text-gray-400 uppercase">Técnico</label><div class="text-gray-800 font-semibold">{{ d.nombre_tecnico }}</div></div>
              <div class="bg-gray-50 p-3 rounded-lg border"><label class="block text-xs font-bold text-gray-400 uppercase">Repuesto</label><div class="text-gray-800 font-semibold">{{ d.nombre_repuesto }}</div></div>
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Cantidad Real en Stock</label>
              <div class="relative">
                <input type="number" name="cantidad" required min="0" [(ngModel)]="cantidad" class="block w-full px-4 py-4 text-center text-2xl font-bold border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-orange-600" />
                <div class="absolute right-4 top-5 text-gray-400 font-bold text-xs">UNIDS</div>
              </div>
              <p class="text-xs text-gray-400 mt-2 text-center"><i class="fas fa-info-circle"></i> Use esto para correcciones (pérdidas, conteos, etc).</p>
            </div>
            <div class="pt-6 border-t border-gray-100 flex justify-between items-center">
              <a routerLink="/inventarioTecnicoVer" class="text-gray-500 hover:text-gray-700 font-semibold">Cancelar</a>
              <button type="submit" class="px-8 py-3 bg-orange-500 text-white font-bold rounded-lg shadow-md hover:bg-orange-600 transform hover:-translate-y-1 transition-all">Actualizar Cantidad</button>
            </div>
          </form>
        </div>
      </div>
    }
  `
})
export class InventarioEditar implements OnInit {
  private api = inject(Api);
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  d: any = null;
  cantidad: any = 0;
  errores: string[] = [];

  constructor() {
    inject(Titulo).set('Ajustar Stock de Técnico');
  }

  async ngOnInit() {
    const id = this.route.snapshot.queryParamMap.get('id') ?? this.route.snapshot.paramMap.get('id');
    const r = await this.api.get('inventarioTecnicoEditar', 'index', { id: id ?? '' });
    if (r.redirect) return void this.router.navigateByUrl('/' + r.redirect);
    this.d = r.datos;
    this.cantidad = r.datos.cantidad_actual;
  }

  async guardar(e: Event) {
    e.preventDefault();
    const r = await this.api.post('inventarioTecnicoEditar', 'index', { id_inventario: this.d.id_inventario, cantidad: this.cantidad });
    if (r.success) this.router.navigateByUrl('/' + r.redirect);
    else {
      this.errores = r.errores || [];
      if (r.datos) this.d = r.datos;
    }
  }
}

/** Port de gestionDevolucionVista.php */
@Component({
  selector: 'app-gestion-devolucion',
  imports: [FormsModule, SelectBuscar, DtArriba, DtAbajo, Th],
  template: `
    <div class="w-full px-4 md:px-6 pb-20">
      <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-8">
        <div class="mb-6 border-b pb-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-box-open text-blue-600 mr-2"></i> Recepción de Repuestos</h1>
            <p class="text-gray-500 text-sm">Selecciona los repuestos que el técnico acaba de entregar en sede.</p>
          </div>
        </div>
        <form (submit)="filtrar($event)" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
          <div class="md:col-span-2">
            <label class="block text-sm font-bold text-gray-700 mb-1">Filtrar por Técnico</label>
            <app-select-buscar name="id_tecnico" [(ngModel)]="filtro" [opciones]="tecnicos" valor="id_tecnico" texto="nombre_tecnico" placeholder="-- Ver Todos los Técnicos --" />
          </div>
          <div><button type="submit" class="w-full py-2 px-4 bg-gray-800 text-white font-bold rounded-lg shadow hover:bg-gray-900 transition-all"><i class="fas fa-filter mr-2"></i> Filtrar</button></div>
        </form>
      </div>
      <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 relative dt-wrapper">
        @if (dt.datos.length) { <app-dt-arriba [dt]="dt" /> }
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-700 uppercase text-xs">
              <tr>
                <th class="py-3 px-4 text-center w-10"><input type="checkbox" [checked]="todos" (change)="marcarTodos($event)" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded cursor-pointer" /></th>
                <th app-th [dt]="dt" campo="nombre_tecnico" class="py-3 px-4">Técnico</th>
                <th app-th [dt]="dt" campo="nombre_repuesto" class="py-3 px-4">Repuesto / Código</th>
                <th app-th [dt]="dt" campo="cantidad" class="py-3 px-4">Cant.</th>
                <th app-th [dt]="dt" campo="numero_remision" class="py-3 px-4">Remisión / Fecha</th>
                <th app-th [dt]="dt" campo="nombre_cliente" class="py-3 px-4">Ubicación</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              @for (d of dt.pagina; track d.id_orden_servicio + '-' + d.id_repuesto) {
                <tr class="hover:bg-blue-50 transition-colors">
                  <td class="py-3 px-4 text-center"><input type="checkbox" [checked]="sel.has(clave(d))" (change)="alternar(d)" class="w-4 h-4 text-blue-600 border-gray-300 rounded cursor-pointer" /></td>
                  <td class="py-3 px-4 font-bold text-gray-800">{{ d.nombre_tecnico }}</td>
                  <td class="py-3 px-4"><div class="font-bold text-blue-700">{{ d.nombre_repuesto }}</div><div class="text-xs text-gray-400 font-mono">{{ d.codigo_referencia ?? 'S/R' }}</div></td>
                  <td class="py-3 px-4 text-center font-black text-lg">{{ d.cantidad }}</td>
                  <td class="py-3 px-4"><div class="font-bold">{{ d.numero_remision }}</div><div class="text-xs text-gray-500">{{ f.fecha(d.fecha_visita) }}</div></td>
                  <td class="py-3 px-4"><div class="text-xs font-bold text-gray-700">{{ d.nombre_cliente }}</div><div class="text-xs text-gray-500">{{ d.nombre_punto }}</div></td>
                </tr>
              } @empty {
                <tr><td colspan="6" class="text-center py-8 text-gray-400 font-bold">🎉 ¡Excelente! No hay repuestos pendientes por devolver.</td></tr>
              }
            </tbody>
          </table>
        </div>
        @if (dt.datos.length) { <app-dt-abajo [dt]="dt" /> }
      </div>
    </div>
    @if (dt.datos.length) {
      <div class="fixed bottom-6 right-6 z-50">
        <button type="button" (click)="procesarDevoluciones()" [disabled]="!sel.size || guardando"
          class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-8 rounded-full shadow-2xl transform transition-all flex items-center gap-2"
          [class]="sel.size ? 'hover:scale-105' : 'opacity-50 cursor-not-allowed'">
          @if (guardando) { <i class="fas fa-spinner fa-spin"></i> Guardando... } @else { <i class="fas fa-check-double text-xl"></i><span>Marcar Seleccionados ({{ sel.size }})</span> }
        </button>
      </div>
    }
  `
})
export class GestionDevolucion implements OnInit {
  private api = inject(Api);
  f = fmt;
  tecnicos: any[] = [];
  filtro: any = '';
  dt = new TablaDatos<any>();
  sel = new Set<string>();
  guardando = false;

  constructor() {
    inject(Titulo).set('Recepción de Devoluciones');
  }

  get todos() {
    return this.dt.pagina.length > 0 && this.dt.pagina.every((d: any) => this.sel.has(this.clave(d)));
  }

  clave(d: any) {
    return d.id_orden_servicio + '|' + d.id_repuesto;
  }

  ngOnInit() {
    this.cargar();
  }

  async cargar() {
    const r = await this.api.post('gestionDevolucion', 'index', { id_tecnico: this.filtro ?? '' });
    this.tecnicos = r.tecnicos;
    this.dt.set(r.datosPendientes);
    this.dt.campoOrden = 'nombre_tecnico';
    this.sel.clear();
  }

  filtrar(e: Event) {
    e.preventDefault();
    this.cargar();
  }

  alternar(d: any) {
    const k = this.clave(d);
    this.sel.has(k) ? this.sel.delete(k) : this.sel.add(k);
  }

  marcarTodos(e: Event) {
    const v = (e.target as HTMLInputElement).checked;
    this.dt.pagina.forEach((d: any) => (v ? this.sel.add(this.clave(d)) : this.sel.delete(this.clave(d))));
  }

  async procesarDevoluciones() {
    const items = [...this.sel].map((k) => {
      const [o, r] = k.split('|');
      return { id_orden: +o, id_repuesto: +r };
    });
    if (!items.length) return;
    if (!confirm('¿Estás seguro de marcar estos ' + items.length + ' repuestos como recibidos en sede?')) return;
    this.guardando = true;
    try {
      const data = await this.api.post('gestionDevolucion', 'ajaxProcesarDevolucion', { items: JSON.stringify(items) });
      alert(data.msg);
      await this.cargar();
    } catch {
      alert('❌ Ocurrió un error al intentar procesar las devoluciones.');
    } finally {
      this.guardando = false;
    }
  }
}
