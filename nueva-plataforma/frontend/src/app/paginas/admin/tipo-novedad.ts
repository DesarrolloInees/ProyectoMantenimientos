import { Component, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { Api } from '../../core/api';
import { Titulo, fmt } from '../../core/ui';
import { DtAbajo, DtArriba, TablaDatos } from '../../shared/tabla';

/** Port de tipoNovedadVerVista.php */
@Component({
  selector: 'app-tipo-novedad-ver',
  imports: [RouterLink, DtArriba, DtAbajo],
  template: `
    <div class="w-full max-w-4xl mx-auto">
      <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-tags text-indigo-600 mr-2"></i> Tipos de Novedad</h1>
          <p class="text-gray-500 text-sm mt-1">Listado de clasificaciones activas.</p>
        </div>
        <a routerLink="/tipoNovedadCrear" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition-all flex items-center"><i class="fas fa-plus mr-2"></i> Nuevo Tipo</a>
      </div>
      <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <div class="p-6 dt-wrapper">
          <app-dt-arriba [dt]="dt" />
          <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-100">
              <tr>
                <th class="px-4 py-3 rounded-tl-lg dt-sortable" (click)="dt.ordenar('id_tipo_novedad')">ID <i class="fas ml-1 text-xs" [class]="dt.icono('id_tipo_novedad')"></i></th>
                <th class="px-4 py-3 dt-sortable" (click)="dt.ordenar('nombre_novedad')">Nombre Novedad <i class="fas ml-1 text-xs" [class]="dt.icono('nombre_novedad')"></i></th>
                <th class="px-4 py-3 text-center">Estado</th>
                <th class="px-4 py-3 text-right rounded-tr-lg">Acciones</th>
              </tr>
            </thead>
            <tbody>
              @for (item of dt.pagina; track item.id_tipo_novedad) {
                <tr class="border-b hover:bg-gray-50">
                  <td class="px-4 py-3 font-medium text-gray-900">#{{ item.id_tipo_novedad }}</td>
                  <td class="px-4 py-3 font-semibold capitalize">{{ item.nombre_novedad }}</td>
                  <td class="px-4 py-3 text-center"><span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded border border-green-200">Activo</span></td>
                  <td class="px-4 py-3 text-right space-x-2">
                    <a [routerLink]="'/tipoNovedadEditar/' + item.id_tipo_novedad" class="text-blue-600 hover:text-blue-900 p-1" title="Editar"><i class="fas fa-edit fa-lg"></i></a>
                    <button (click)="eliminar(item.id_tipo_novedad)" class="text-red-500 hover:text-red-700 p-1" title="Eliminar"><i class="fas fa-trash-alt fa-lg"></i></button>
                  </td>
                </tr>
              } @empty {
                <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">Ningún dato disponible en esta tabla</td></tr>
              }
            </tbody>
          </table>
          <app-dt-abajo [dt]="dt" />
        </div>
      </div>
    </div>
  `
})
export class TipoNovedadVer implements OnInit {
  private api = inject(Api);
  dt = new TablaDatos<any>();

  constructor() {
    inject(Titulo).set('Gestión de Tipos de Novedad');
  }

  async ngOnInit() {
    const r = await this.api.get('tipoNovedadVer', 'index');
    this.dt.set(r.novedades);
  }

  async eliminar(id: any) {
    if (confirm('¿Estás seguro de eliminar este registro? Desaparecerá de la lista.')) {
      await this.api.post('tipoNovedadEliminar', 'index', { id });
      this.ngOnInit();
    }
  }
}

/** Port de tipoNovedadCrearVista.php y tipoNovedadEditarVista.php */
@Component({
  selector: 'app-tipo-novedad-form',
  imports: [FormsModule, RouterLink],
  template: `
    @if (listo) {
      <div class="w-full max-w-4xl mx-auto">
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
          <div class="mb-6 border-b pb-4">
            @if (editar) {
              <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-edit text-indigo-600 mr-2"></i> Editar Tipo de Novedad</h1>
              <p class="text-gray-500 mt-1">Modifica la información del registro seleccionado.</p>
            } @else {
              <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-tags text-indigo-600 mr-2"></i> Nuevo Tipo de Novedad</h1>
              <p class="text-gray-500 mt-1">Registra una nueva clasificación para las novedades.</p>
            }
          </div>
          @if (errores.length) {
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
              <p class="font-bold">{{ editar ? '¡Ocurrió un problema!' : '¡Error!' }}</p>
              <ul class="list-disc list-inside ml-2 text-sm">@for (e of errores; track $index) { <li>{{ e }}</li> }</ul>
            </div>
          }
          <form (submit)="guardar($event)" class="space-y-6">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Nombre de la Novedad <span class="text-red-500">*</span></label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-clipboard-list text-gray-400"></i></div>
                <input type="text" name="nombre_novedad" required [(ngModel)]="nombre" [placeholder]="editar ? '' : 'Ej: REPUESTO PENDIENTE'"
                  class="pl-10 mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 capitalize" />
              </div>
            </div>
            @if (editar) {
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Estado</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-toggle-on text-gray-400"></i></div>
                  <select name="estado" [(ngModel)]="estado" class="pl-10 mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                  </select>
                </div>
                <p class="text-xs text-gray-400 mt-1 ml-1">Si seleccionas "Inactivo", no aparecerá en las nuevas asignaciones.</p>
              </div>
            }
            <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
              <a routerLink="/tipoNovedadVer" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50">Cancelar</a>
              <button type="submit" class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transform hover:-translate-y-1 transition-all">
                <i class="fas mr-2" [class]="editar ? 'fa-sync-alt' : 'fa-save'"></i> {{ editar ? 'Actualizar' : 'Guardar' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    }
  `
})
export class TipoNovedadForm implements OnInit {
  private api = inject(Api);
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private titulo = inject(Titulo);
  editar = false;
  id: any = null;
  nombre = '';
  estado = '1';
  errores: string[] = [];
  listo = false;

  async ngOnInit() {
    this.editar = this.route.snapshot.data['modo'] === 'editar';
    this.titulo.set(this.editar ? 'Editar Tipo de Novedad' : 'Crear Tipo de Novedad');
    if (this.editar) {
      this.id = this.route.snapshot.paramMap.get('id') ?? this.route.snapshot.queryParamMap.get('id');
      const r = await this.api.get('tipoNovedadEditar', 'index', { id: this.id ?? '' });
      if (r.redirect) return void this.router.navigateByUrl('/' + r.redirect);
      this.nombre = r.datos.nombre_novedad;
      this.estado = String(r.datos.estado);
    }
    this.listo = true;
  }

  async guardar(e: Event) {
    e.preventDefault();
    const r = this.editar
      ? await this.api.post('tipoNovedadEditar', 'index', { id_tipo_novedad: this.id, nombre_novedad: this.nombre, estado: this.estado })
      : await this.api.post('tipoNovedadCrear', 'index', { nombre_novedad: this.nombre });
    if (r.success) this.router.navigateByUrl('/' + r.redirect);
    else {
      this.errores = r.errores;
      if (r.nombrePrevio !== undefined) this.nombre = r.nombrePrevio;
      if (r.datos) this.nombre = r.datos.nombre_novedad;
    }
  }
}

/** Port de diasFestivosVerVista.php */
@Component({
  selector: 'app-festivos-ver',
  imports: [RouterLink],
  template: `
    <div class="w-full px-4 md:px-6">
      <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-calendar-alt text-blue-600 mr-2"></i> Días Festivos</h1>
            <p class="text-gray-500 mt-1">Administra los días no laborales del sistema.</p>
          </div>
          <a routerLink="/diasFestivosCrear" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-blue-700 transition shadow-md"><i class="fas fa-plus"></i> Nuevo Festivo</a>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-50 text-gray-700 uppercase text-xs tracking-wider">
                <th class="p-4 border-b">Fecha</th>
                <th class="p-4 border-b">Descripción</th>
                <th class="p-4 border-b text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
              @for (f of festivos; track f.id_festivo) {
                <tr class="hover:bg-blue-50 transition">
                  <td class="p-4 font-bold text-gray-800">{{ fmt.fecha(f.fecha) }}</td>
                  <td class="p-4 text-gray-600">{{ f.descripcion ?? 'Sin descripción' }}</td>
                  <td class="p-4 text-right space-x-2">
                    <a routerLink="/diasFestivosEditar" [queryParams]="{ id: f.id_festivo }" class="text-blue-500 hover:text-blue-700" title="Editar"><i class="fas fa-edit"></i></a>
                    <a href="#" (click)="eliminar($event, f.id_festivo)" class="text-red-500 hover:text-red-700" title="Eliminar"><i class="fas fa-trash-alt"></i></a>
                  </td>
                </tr>
              } @empty {
                <tr><td colspan="3" class="p-4 text-center text-gray-400 italic">No hay días festivos registrados.</td></tr>
              }
            </tbody>
          </table>
        </div>
      </div>
    </div>
  `
})
export class FestivosVer implements OnInit {
  private api = inject(Api);
  fmt = fmt;
  festivos: any[] = [];

  constructor() {
    inject(Titulo).set('Gestión de Días Festivos');
  }

  async ngOnInit() {
    this.festivos = (await this.api.get('diasFestivosVer', 'index')).festivos;
  }

  async eliminar(e: Event, id: any) {
    e.preventDefault();
    if (!confirm('¿Estás seguro de eliminar este festivo?')) return;
    await this.api.post('diasFestivosEliminar', 'index', { id });
    this.ngOnInit();
  }
}

/** Port de diasFestivosCrearVista.php / diasFestivosEditarVista.php */
@Component({
  selector: 'app-festivos-form',
  imports: [FormsModule, RouterLink],
  template: `
    @if (listo) {
      <div class="w-full px-4 md:px-6">
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
          @if (!editar) {
            <div class="mb-6 border-b pb-4">
              <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-plus-circle text-blue-600 mr-2"></i> Nuevo Festivo</h1>
              <p class="text-gray-500 mt-1">Registra una fecha no laboral.</p>
            </div>
            @if (errores.length) {
              <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <p class="font-bold">¡Atención!</p>
                <ul class="list-disc list-inside ml-2 text-sm">@for (e of errores; track $index) { <li>{{ e }}</li> }</ul>
              </div>
            }
            <form (submit)="guardar($event)" class="space-y-6">
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Selecciona la Fecha <span class="text-red-500">*</span></label>
                <div class="relative"><input type="date" name="fecha" required [(ngModel)]="fecha" class="block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" /></div>
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Descripción / Motivo</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-info-circle text-gray-400"></i></div>
                  <input type="text" name="descripcion" [(ngModel)]="descripcion" placeholder="Ej: Navidad, Año Nuevo, Semana Santa..." class="pl-10 mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                </div>
              </div>
              <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
                <a routerLink="/diasFestivosVer" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="px-8 py-3 bg-blue-600 text-white font-bold rounded-lg shadow-md hover:bg-blue-700 transform hover:-translate-y-1 transition-all"><i class="fas fa-save mr-2"></i> Guardar Festivo</button>
              </div>
            </form>
          } @else {
            <div class="mb-6 border-b pb-4"><h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-edit text-orange-500 mr-2"></i> Editar Festivo</h1></div>
            @if (errores.length) {
              <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded text-sm">@for (e of errores; track $index) { <p>{{ e }}</p> }</div>
            }
            <form (submit)="guardar($event)" class="space-y-6">
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Fecha</label>
                <input type="date" name="fecha" required [(ngModel)]="fecha" class="block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500" />
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Descripción</label>
                <input type="text" name="descripcion" [(ngModel)]="descripcion" class="block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500" />
              </div>
              <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
                <a routerLink="/diasFestivosVer" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="px-8 py-3 bg-orange-500 text-white font-bold rounded-lg shadow-md hover:bg-orange-600 transition-all">Actualizar</button>
              </div>
            </form>
          }
        </div>
      </div>
    }
  `
})
export class FestivosForm implements OnInit {
  private api = inject(Api);
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private titulo = inject(Titulo);
  editar = false;
  id: any = null;
  fecha = '';
  descripcion = '';
  errores: string[] = [];
  listo = false;

  async ngOnInit() {
    this.editar = this.route.snapshot.data['modo'] === 'editar';
    this.titulo.set(this.editar ? 'Editar Festivo' : 'Crear Día Festivo');
    if (this.editar) {
      this.id = this.route.snapshot.queryParamMap.get('id') ?? this.route.snapshot.paramMap.get('id');
      const r = await this.api.get('diasFestivosEditar', 'index', { id: this.id ?? '' });
      if (r.redirect) return void this.router.navigateByUrl('/' + r.redirect);
      this.fecha = r.datos.fecha;
      this.descripcion = r.datos.descripcion ?? '';
    }
    this.listo = true;
  }

  async guardar(e: Event) {
    e.preventDefault();
    const r = this.editar
      ? await this.api.post('diasFestivosEditar', 'index', { id_festivo: this.id, fecha: this.fecha, descripcion: this.descripcion })
      : await this.api.post('diasFestivosCrear', 'index', { fecha: this.fecha, descripcion: this.descripcion });
    if (r.success) this.router.navigateByUrl('/' + r.redirect);
    else this.errores = r.errores;
  }
}
