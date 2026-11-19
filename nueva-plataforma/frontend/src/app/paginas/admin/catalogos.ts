import { Component, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { Api } from '../../core/api';
import { Titulo } from '../../core/ui';
import { DtAbajo, DtArriba, TablaDatos } from '../../shared/tabla';

/** Textos/íconos de cada catálogo, tomados de las vistas app/views/admin/*Vista.php */
interface CatUI {
  id: string;
  campo: string;
  tituloVer: string;
  h1Ver: string;
  iconoVer: string;
  subVer: string;
  btnNuevo: string;
  iconoNuevo?: string;
  thNombre: string;
  vacio: string;
  modalTitulo: string;
  modalTexto: string;
  modalBoton: string;
  tituloCrear: string;
  h1Crear: string;
  iconoCrear: string;
  subCrear: string;
  labelCrear: string;
  placeholder: string;
  iconoInput: string;
  iconoInputEditar?: string;
  mayusculas?: boolean;
  colorCrear?: 'indigo' | 'yellow';
  tituloEditar: string;
  h1Editar: string;
  iconoEditar?: string;
  labelEditar: string;
  estado?: [string, string];
}

export const CATALOGOS: Record<string, CatUI> = {
  calificacionServicio: {
    id: 'id_calificacion', campo: 'nombre_calificacion',
    tituloVer: 'Calificaciones de Servicio', h1Ver: 'Calificaciones', iconoVer: 'fas fa-star-half-alt text-yellow-500', subVer: 'Gestión de niveles de satisfacción.',
    btnNuevo: 'Nueva', thNombre: 'Nombre Calificación', vacio: 'No hay calificaciones registradas.',
    modalTitulo: 'Eliminar Calificación', modalTexto: '¿Seguro? Se borrará permanentemente.', modalBoton: 'Sí, Borrar',
    tituloCrear: 'Crear Calificación', h1Crear: 'Nueva Calificación', iconoCrear: 'fas fa-star text-yellow-500', subCrear: 'Define un nivel de servicio (Ej: Excelente, Bueno, Malo).',
    labelCrear: 'Nombre de la Calificación', placeholder: 'Ej: Excelente', iconoInput: 'fas fa-tag', colorCrear: 'yellow',
    tituloEditar: 'Editar Calificación', h1Editar: 'Editar Calificación', labelEditar: 'Nombre'
  },
  delegacion: {
    id: 'id_delegacion', campo: 'nombre_delegacion',
    tituloVer: 'Delegaciones', h1Ver: 'Delegaciones', iconoVer: 'fas fa-map text-indigo-600', subVer: 'Gestión de zonas geográficas.',
    btnNuevo: 'Nueva Delegación', thNombre: 'Nombre Delegación', vacio: 'No hay delegaciones registradas.',
    modalTitulo: 'Eliminar Delegación', modalTexto: '¿Seguro? Se ocultará de la lista.', modalBoton: 'Sí, Eliminar',
    tituloCrear: 'Crear Delegación', h1Crear: 'Nueva Delegación', iconoCrear: 'fas fa-map-marker-alt text-indigo-600', subCrear: 'Registra una zona geográfica o administrativa.',
    labelCrear: 'Nombre de la Delegación', placeholder: 'Ej: Zona Norte', iconoInput: 'fas fa-building', mayusculas: true,
    tituloEditar: 'Editar Delegación', h1Editar: 'Editar Delegación', labelEditar: 'Nombre Delegación', estado: ['Activo', 'Inactivo']
  },
  estadoMaquina: {
    id: 'id_estado', campo: 'nombre_estado',
    tituloVer: 'Estados de Máquina', h1Ver: 'Estados de Máquina', iconoVer: 'fas fa-server text-indigo-600', subVer: 'Catálogo de condiciones operativas.',
    btnNuevo: 'Nuevo Estado', thNombre: 'Nombre del Estado', vacio: 'No hay estados registrados.',
    modalTitulo: 'Eliminar Estado', modalTexto: '¿Seguro? Se borrará permanentemente.', modalBoton: 'Sí, Borrar',
    tituloCrear: 'Crear Estado de Máquina', h1Crear: 'Nuevo Estado', iconoCrear: 'fas fa-traffic-light text-indigo-600', subCrear: 'Define una condición para las máquinas (Ej: Operativa, Dañada).',
    labelCrear: 'Nombre del Estado', placeholder: 'Ej: En Mantenimiento', iconoInput: 'fas fa-info-circle', iconoInputEditar: 'fas fa-tag',
    tituloEditar: 'Editar Estado', h1Editar: 'Editar Estado', labelEditar: 'Nombre'
  },
  modalidadOperativa: {
    id: 'id_modalidad', campo: 'nombre_modalidad',
    tituloVer: 'Modalidades Operativas', h1Ver: 'Modalidades', iconoVer: 'fas fa-handshake text-indigo-600', subVer: 'Gestión de tipos de contrato/operación.',
    btnNuevo: 'Nueva', thNombre: 'Nombre Modalidad', vacio: 'No hay modalidades registradas.',
    modalTitulo: 'Eliminar Modalidad', modalTexto: '¿Seguro? Se borrará permanentemente.', modalBoton: 'Sí, Borrar',
    tituloCrear: 'Crear Modalidad Operativa', h1Crear: 'Nueva Modalidad', iconoCrear: 'fas fa-briefcase text-indigo-600', subCrear: 'Define un tipo de operación (Ej: Venta, Comodato, Renta).',
    labelCrear: 'Nombre de la Modalidad', placeholder: 'Ej: Comodato', iconoInput: 'fas fa-file-contract',
    tituloEditar: 'Editar Modalidad', h1Editar: 'Editar Modalidad', labelEditar: 'Nombre'
  },
  tecnico: {
    id: 'id_tecnico', campo: 'nombre_tecnico',
    tituloVer: 'Gestión de Técnicos', h1Ver: 'Técnicos', iconoVer: 'fas fa-users-cog text-indigo-600', subVer: 'Administra el personal de servicio.',
    btnNuevo: 'Nuevo Técnico', iconoNuevo: 'fas fa-user-plus', thNombre: 'Nombre del Técnico', vacio: 'No hay técnicos registrados.',
    modalTitulo: 'Eliminar Técnico', modalTexto: '¿Seguro? El técnico quedará inactivo.', modalBoton: 'Sí, Eliminar',
    tituloCrear: 'Crear Técnico', h1Crear: 'Nuevo Técnico', iconoCrear: 'fas fa-user-cog text-indigo-600', subCrear: 'Registra al personal encargado del mantenimiento.',
    labelCrear: 'Nombre Completo', placeholder: 'Ej: Juan Pérez', iconoInput: 'fas fa-user',
    tituloEditar: 'Editar Técnico', h1Editar: 'Editar Técnico', iconoEditar: 'fas fa-user-edit text-yellow-500', labelEditar: 'Nombre Completo',
    estado: ['Activo (Disponible)', 'Inactivo (No Disponible)']
  },
  tipoMantenimiento: {
    id: 'id_tipo_mantenimiento', campo: 'nombre_completo',
    tituloVer: 'Tipos de Mantenimiento', h1Ver: 'Tipos de Mantenimiento', iconoVer: 'fas fa-clipboard-list text-indigo-600', subVer: 'Gestión de categorías de servicio.',
    btnNuevo: 'Nuevo Tipo', thNombre: 'Nombre del Mantenimiento', vacio: 'No hay tipos de mantenimiento registrados.',
    modalTitulo: 'Eliminar Tipo', modalTexto: '¿Seguro? Se ocultará del sistema.', modalBoton: 'Sí, Eliminar',
    tituloCrear: 'Crear Tipo Mantenimiento', h1Crear: 'Nuevo Tipo de Mantenimiento', iconoCrear: 'fas fa-tools text-indigo-600', subCrear: 'Define una categoría (Ej: Preventivo, Correctivo, Instalación).',
    labelCrear: 'Nombre del Mantenimiento', placeholder: 'Ej: Mantenimiento Preventivo Anual', iconoInput: 'fas fa-hammer',
    tituloEditar: 'Editar Tipo Mantenimiento', h1Editar: 'Editar Tipo', labelEditar: 'Nombre del Mantenimiento', estado: ['Activo', 'Inactivo']
  },
  tipoMaquina: {
    id: 'id_tipo_maquina', campo: 'nombre_tipo_maquina',
    tituloVer: 'Tipos de Máquina', h1Ver: 'Tipos de Máquina', iconoVer: 'fas fa-server text-indigo-600', subVer: 'Catálogo de equipos soportados.',
    btnNuevo: 'Nuevo Tipo', thNombre: 'Nombre del Tipo', vacio: 'No hay tipos registrados.',
    modalTitulo: 'Eliminar Tipo', modalTexto: '¿Seguro? Se ocultará del sistema.', modalBoton: 'Sí, Eliminar',
    tituloCrear: 'Crear Tipo de Máquina', h1Crear: 'Nuevo Tipo de Máquina', iconoCrear: 'fas fa-cogs text-indigo-600', subCrear: 'Define una nueva categoría (ej: Impresora, Cajero, Datáfono).',
    labelCrear: 'Nombre del Tipo', placeholder: 'Ej: Cajero Automático Multifuncional', iconoInput: 'fas fa-robot', mayusculas: true,
    tituloEditar: 'Editar Tipo de Máquina', h1Editar: 'Editar Tipo', labelEditar: 'Nombre del Tipo', estado: ['Activo', 'Inactivo']
  },
  tipoUsuario: {
    id: 'idTipoUsuario', campo: 'nombreTipoUsuario',
    tituloVer: 'Roles de Usuario', h1Ver: 'Roles de Usuario', iconoVer: 'fas fa-users-cog text-indigo-600', subVer: 'Gestión de niveles de acceso.',
    btnNuevo: 'Nuevo Rol', thNombre: 'Nombre del Rol', vacio: 'No hay roles registrados.',
    modalTitulo: 'Eliminar Rol', modalTexto: '¿Seguro? Si hay usuarios con este rol, no se podrá borrar.', modalBoton: 'Sí, Borrar',
    tituloCrear: 'Crear Rol de Usuario', h1Crear: 'Nuevo Rol', iconoCrear: 'fas fa-id-card text-indigo-600', subCrear: 'Define un perfil de acceso (Ej: Administrador, Técnico).',
    labelCrear: 'Nombre del Rol', placeholder: 'Ej: Supervisor', iconoInput: 'fas fa-user-tag',
    tituloEditar: 'Editar Rol', h1Editar: 'Editar Rol', labelEditar: 'Nombre'
  }
};

/** Listado (…VerVista.php) */
@Component({
  selector: 'app-catalogo-ver',
  imports: [RouterLink, DtArriba, DtAbajo],
  template: `
    <div class="w-full px-4 md:px-6">
      <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-800"><i class="mr-2" [class]="ui.iconoVer"></i> {{ ui.h1Ver }}</h1>
            <p class="text-gray-500">{{ ui.subVer }}</p>
          </div>
          <a [routerLink]="'/' + clave + 'Crear'" class="px-5 py-2.5 bg-indigo-600 text-white font-bold rounded-lg shadow hover:bg-indigo-700 transition flex items-center space-x-2">
            <i [class]="ui.iconoNuevo || 'fas fa-plus-circle'"></i> <span>{{ ui.btnNuevo }}</span>
          </a>
        </div>
        @if (cargado && dt.datos.length) {
          <div class="dt-wrapper py-4">
            <app-dt-arriba [dt]="dt" />
            <div class="overflow-x-auto">
              <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                  <tr>
                    <th class="py-3 px-4 dt-sortable" (click)="dt.ordenar(ui.id)">ID <i class="fas ml-1 text-xs" [class]="dt.icono(ui.id)"></i></th>
                    <th class="py-3 px-4 dt-sortable" (click)="dt.ordenar(ui.campo)">{{ ui.thNombre }} <i class="fas ml-1 text-xs" [class]="dt.icono(ui.campo)"></i></th>
                    <th class="py-3 px-4 text-center">Acciones</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  @for (f of dt.pagina; track f[ui.id]) {
                    <tr class="hover:bg-gray-50 bg-white">
                      <td class="py-3 px-4 font-bold text-gray-600">#{{ f[ui.id] }}</td>
                      <td class="py-3 px-4 font-medium text-gray-900">{{ f[ui.campo] }}</td>
                      <td class="py-3 px-4 text-center flex justify-center space-x-2">
                        <a [routerLink]="'/' + clave + 'Editar/' + f[ui.id]" class="p-2 bg-yellow-100 text-yellow-600 rounded-full hover:bg-yellow-200"><i class="fas fa-edit"></i></a>
                        <button (click)="idEliminar = f[ui.id]" class="p-2 bg-red-100 text-red-600 rounded-full hover:bg-red-200"><i class="fas fa-trash-alt"></i></button>
                      </td>
                    </tr>
                  }
                </tbody>
              </table>
            </div>
            <app-dt-abajo [dt]="dt" />
          </div>
        } @else if (cargado) {
          <div class="text-center p-8 bg-gray-50 rounded-lg"><p class="text-gray-500">{{ ui.vacio }}</p></div>
        }
      </div>
    </div>

    @if (idEliminar !== null) {
      <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-sm shadow-xl">
          <h3 class="text-lg font-bold text-gray-900 mb-2">{{ ui.modalTitulo }}</h3>
          <p class="text-sm text-gray-500 mb-4">{{ ui.modalTexto }}</p>
          <div class="flex justify-end space-x-3">
            <button (click)="idEliminar = null" class="px-4 py-2 bg-gray-100 rounded-lg text-gray-700 hover:bg-gray-200">Cancelar</button>
            <button (click)="eliminar()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">{{ ui.modalBoton }}</button>
          </div>
        </div>
      </div>
    }
  `
})
export class CatalogoVer implements OnInit {
  private api = inject(Api);
  private route = inject(ActivatedRoute);
  private titulo = inject(Titulo);
  clave = '';
  ui!: CatUI;
  dt = new TablaDatos<any>();
  cargado = false;
  idEliminar: any = null;

  ngOnInit() {
    this.clave = this.route.snapshot.data['cat'];
    this.ui = CATALOGOS[this.clave];
    this.titulo.set(this.ui.tituloVer);
    this.cargar();
  }

  async cargar() {
    const r = await this.api.get(this.clave + 'Ver', 'index');
    this.dt.set(r.lista || []);
    this.cargado = true;
  }

  async eliminar() {
    const id = this.idEliminar;
    this.idEliminar = null;
    await this.api.post(this.clave + 'Eliminar', 'index', { id });
    this.cargar();
  }
}

/** Formulario Crear / Editar (…CrearVista.php / …EditarVista.php) */
@Component({
  selector: 'app-catalogo-form',
  imports: [FormsModule, RouterLink],
  template: `
    @if (listo) {
      <div class="w-full px-4 md:px-6">
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
          <div class="mb-6 border-b pb-4">
            @if (editar) {
              <h1 class="text-2xl font-bold text-gray-800"><i class="mr-2" [class]="ui.iconoEditar || 'fas fa-edit text-yellow-500'"></i> {{ ui.h1Editar }}</h1>
              <p class="text-gray-500 mt-1">Modificando: <strong>{{ original }}</strong></p>
            } @else {
              <h1 class="text-2xl font-bold text-gray-800"><i class="mr-2" [class]="ui.iconoCrear"></i> {{ ui.h1Crear }}</h1>
              <p class="text-gray-500 mt-1">{{ ui.subCrear }}</p>
            }
          </div>

          @if (errores.length) {
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
              <p class="font-bold">¡Error!</p>
              <ul class="list-disc list-inside ml-2 text-sm">
                @for (e of errores; track $index) { <li>{{ e }}</li> }
              </ul>
            </div>
          }

          <form (submit)="guardar($event)" class="space-y-6">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">{{ editar ? ui.labelEditar : ui.labelCrear }} <span class="text-red-500">*</span></label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="text-gray-400" [class]="editar ? (ui.iconoInputEditar || ui.iconoInput) : ui.iconoInput"></i></div>
                <input type="text" [name]="ui.campo" required [(ngModel)]="nombre" [placeholder]="editar ? '' : ui.placeholder"
                  class="pl-10 mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2"
                  [class]="(amarillo ? 'focus:ring-yellow-500 focus:border-yellow-500' : 'focus:ring-indigo-500 focus:border-indigo-500') + (ui.mayusculas ? ' uppercase' : '')" />
              </div>
            </div>

            @if (editar && ui.estado) {
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Estado</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-toggle-on text-gray-400"></i></div>
                  <select name="estado" [(ngModel)]="estado" class="pl-10 mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-yellow-500 bg-white">
                    <option value="1">{{ ui.estado[0] }}</option>
                    <option value="0">{{ ui.estado[1] }}</option>
                  </select>
                </div>
              </div>
            }

            <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
              <a [routerLink]="'/' + clave + 'Ver'" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50">Cancelar</a>
              @if (editar) {
                <button type="submit" [disabled]="enviando" class="px-8 py-3 bg-yellow-500 text-white font-bold rounded-lg shadow-md hover:bg-yellow-600 transform hover:-translate-y-1 transition-all"><i class="fas fa-sync-alt mr-2"></i> Actualizar</button>
              } @else {
                <button type="submit" [disabled]="enviando" class="px-8 py-3 text-white font-bold rounded-lg shadow-md transform hover:-translate-y-1 transition-all"
                  [class]="amarillo ? 'bg-yellow-500 hover:bg-yellow-600' : 'bg-indigo-600 hover:bg-indigo-700'"><i class="fas fa-save mr-2"></i> Guardar</button>
              }
            </div>
          </form>
        </div>
      </div>
    }
  `
})
export class CatalogoForm implements OnInit {
  private api = inject(Api);
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private titulo = inject(Titulo);
  clave = '';
  ui!: CatUI;
  editar = false;
  id: any = null;
  nombre = '';
  original = '';
  estado = '1';
  errores: string[] = [];
  listo = false;
  enviando = false;

  get amarillo() {
    return this.editar || this.ui.colorCrear === 'yellow';
  }

  async ngOnInit() {
    this.clave = this.route.snapshot.data['cat'];
    this.ui = CATALOGOS[this.clave];
    this.id = this.route.snapshot.paramMap.get('id');
    this.editar = this.route.snapshot.data['modo'] === 'editar';
    this.titulo.set(this.editar ? this.ui.tituloEditar : this.ui.tituloCrear);
    if (this.editar) {
      const r = await this.api.get(this.clave + 'Editar', 'index', { id: this.id ?? '' });
      if (r.redirect) {
        this.router.navigateByUrl('/' + r.redirect);
        return;
      }
      this.nombre = this.original = r.datos[this.ui.campo] ?? '';
      this.estado = String(r.datos['estado'] ?? '1');
    }
    this.listo = true;
  }

  async guardar(e: Event) {
    e.preventDefault();
    this.enviando = true;
    try {
      const datos: Record<string, any> = { [this.ui.campo]: this.nombre };
      if (this.editar) {
        datos['id'] = this.id;
        datos[this.ui.id] = this.id;
        if (this.ui.estado) datos['estado'] = this.estado;
      }
      const r = await this.api.post(this.clave + (this.editar ? 'Editar' : 'Crear'), 'index', datos);
      if (r.success) {
        this.router.navigateByUrl('/' + r.redirect);
      } else if (r.redirect) {
        this.router.navigateByUrl('/' + r.redirect);
      } else {
        this.errores = r.errores || [];
      }
    } finally {
      this.enviando = false;
    }
  }
}
