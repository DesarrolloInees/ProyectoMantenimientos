import { Component, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { Api } from '../../core/api';
import { Titulo } from '../../core/ui';
import { DtAbajo, DtArriba, TablaDatos, Th } from '../../shared/tabla';

/** Port de usuarioVerVista.php + js/usuario/usuarioVer.js */
@Component({
  selector: 'app-usuario-ver',
  imports: [RouterLink, DtArriba, DtAbajo, Th],
  template: `
    <div class="w-full px-4 md:px-6">
      <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 border-b pb-4">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Gestión de Usuarios</h1>
            <p class="text-gray-500 mt-1">Crea, edita o elimina los usuarios del sistema.</p>
          </div>
          <a routerLink="/usuarioCrear" class="mt-4 sm:mt-0 px-5 py-2.5 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transition-all duration-300 transform hover:scale-105 flex items-center space-x-2">
            <i class="fa-solid fa-user-plus"></i><span>Crear Nuevo Usuario</span>
          </a>
        </div>
        @if (cargado && dt.datos.length) {
          <div class="dt-wrapper">
            <app-dt-arriba [dt]="dt" />
            <div class="overflow-x-auto">
              <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-800 uppercase bg-gray-100">
                  <tr>
                    <th app-th [dt]="dt" campo="usuario_id" class="py-3 px-4">ID</th>
                    <th app-th [dt]="dt" campo="nombre" class="py-3 px-4">Nombre</th>
                    <th app-th [dt]="dt" campo="cedula" class="py-3 px-4">Cédula</th>
                    <th app-th [dt]="dt" campo="cargo" class="py-3 px-4">Cargo</th>
                    <th app-th [dt]="dt" campo="rol" class="py-3 px-4">Rol</th>
                    <th class="py-3 px-4 text-center">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  @for (u of dt.pagina; track u.usuario_id) {
                    <tr class="border-b">
                      <td class="py-4 px-4 font-medium text-gray-900">{{ u.usuario_id }}</td>
                      <td class="py-4 px-4">{{ u.nombre }}</td>
                      <td class="py-4 px-4">{{ u.cedula }}</td>
                      <td class="py-4 px-4">{{ u.cargo }}</td>
                      <td class="py-4 px-4">{{ u.rol }}</td>
                      <td class="py-4 px-4 text-center whitespace-nowrap">
                        <div class="flex justify-center items-center space-x-2">
                          <a [routerLink]="'/usuarioEditar/' + u.usuario_id" class="p-2 w-9 h-9 flex items-center justify-center bg-yellow-400 text-white rounded-full hover:bg-yellow-500 transition-colors" title="Editar"><i class="fa-solid fa-user-pen"></i></a>
                          <button (click)="idEliminar = u.usuario_id" class="p-2 w-9 h-9 flex items-center justify-center bg-red-600 text-white rounded-full hover:bg-red-700 transition-colors" title="Eliminar"><i class="fa-solid fa-user-minus"></i></button>
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
          <div class="text-center p-8 border-2 border-dashed rounded-lg">
            <i class="fa-solid fa-users-slash text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-500 font-medium">No hay usuarios activos para mostrar.</p>
            <p class="text-gray-400 text-sm mt-1">¡Crea el primer usuario para empezar!</p>
          </div>
        }
      </div>
    </div>

    @if (idEliminar !== null) {
      <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-black bg-opacity-60 transition-opacity duration-300" (click)="idEliminar = null">
        <div class="bg-white rounded-lg p-6 w-full max-w-sm mx-auto shadow-xl transform transition-all duration-300" (click)="$event.stopPropagation()">
          <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100"><i class="fa-solid fa-triangle-exclamation text-xl text-red-600"></i></div>
            <h2 class="text-xl font-bold my-4 text-gray-800">Confirmar Eliminación</h2>
            <p class="text-gray-600 mb-6">¿Estás seguro de que quieres eliminar este usuario? Esta acción no se puede deshacer.</p>
          </div>
          <div class="flex justify-center space-x-4">
            <button (click)="idEliminar = null" class="px-6 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 font-medium transition-colors">Cancelar</button>
            <button (click)="eliminar()" class="px-6 py-2 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition-colors">Sí, eliminar</button>
          </div>
        </div>
      </div>
    }
  `
})
export class UsuarioVer implements OnInit {
  private api = inject(Api);
  dt = new TablaDatos<any>();
  cargado = false;
  idEliminar: any = null;

  constructor() {
    inject(Titulo).set('Ver Usuarios');
  }

  async ngOnInit() {
    this.dt.set((await this.api.get('usuarioVer', 'index')).usuarios);
    this.cargado = true;
  }

  async eliminar() {
    const id = this.idEliminar;
    this.idEliminar = null;
    await this.api.post('usuarioEliminar', 'index', { id });
    this.ngOnInit();
  }
}

const INPUT = 'mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500';

/** Port de usuarioCrearVista.php / usuarioEditarVista.php */
@Component({
  selector: 'app-usuario-form',
  imports: [FormsModule, RouterLink],
  template: `
    @if (listo) {
      <div class="w-full max-w-4xl mx-auto">
        @if (!editar || u) {
          <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md">
            <div class="mb-6 border-b pb-4">
              @if (editar) {
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Editar Usuario: <span class="text-indigo-600">{{ usuarioOriginal }}</span></h1>
              } @else {
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Crear Nuevo Usuario</h1>
                <p class="text-gray-500 mt-1">Completa los datos para registrar un nuevo usuario en el sistema.</p>
              }
            </div>
            @if (errores.length) {
              <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                <p class="font-bold">{{ editar ? 'Error:' : '¡Error!' }}</p>
                <ul class="list-disc pl-5">@for (e of errores; track $index) { <li>{{ e }}</li> }</ul>
              </div>
            }
            <form (submit)="guardar($event)" class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div><label class="block text-sm font-medium text-gray-600">Nombre Completo</label><input type="text" name="nombre" required [(ngModel)]="d.nombre" [placeholder]="editar ? '' : 'Ej: Carlos Rodriguez'" class="${INPUT}" /></div>
                <div><label class="block text-sm font-medium text-gray-600">Cédula</label><input type="text" name="cedula" required [(ngModel)]="d.cedula" [placeholder]="editar ? '' : 'Ej: 123456789'" class="${INPUT}" /></div>
                <div><label class="block text-sm font-medium text-gray-600">Cargo</label><input type="text" name="cargo" required [(ngModel)]="d.cargo" [placeholder]="editar ? '' : 'Ej: Técnico Especialista'" class="${INPUT}" /></div>
                <div><label class="block text-sm font-medium text-gray-600">Celular</label><input type="tel" name="celular" required [(ngModel)]="d.celular" [placeholder]="editar ? '' : 'Ej: 3001234567'" class="${INPUT}" /></div>
                <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-600">Email</label><input type="email" name="email" required [(ngModel)]="d.email" [placeholder]="editar ? '' : 'ejemplo@inees.co'" class="${INPUT}" /></div>
                <div><label class="block text-sm font-medium text-gray-600">Nombre de Usuario</label><input type="text" name="usuario" required [(ngModel)]="d.usuario" [placeholder]="editar ? '' : 'Ej: crosriguez'" class="${INPUT}" /></div>
                @if (editar) {
                  <div>
                    <label class="block text-sm font-medium text-gray-600">Nueva Contraseña</label>
                    <input type="password" name="pass" [(ngModel)]="d.pass" placeholder="Dejar en blanco para mantener la actual" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 focus:ring-indigo-500" />
                    <p class="text-xs text-gray-400 mt-1">Solo llena esto si quieres cambiarla.</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-600">Rol / Nivel de Acceso</label>
                    <select name="nivel_acceso" required [(ngModel)]="d.nivel_acceso" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-white">
                      @for (t of tipos; track t.idTipoUsuario) { <option [value]="t.idTipoUsuario + ''">{{ t.nombreTipoUsuario }}</option> }
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-600">Estado</label>
                    <select name="estado" required [(ngModel)]="d.estado" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-white">
                      <option value="activo">Activo</option>
                      <option value="inactivo">Inactivo</option>
                    </select>
                  </div>
                } @else {
                  <div><label class="block text-sm font-medium text-gray-600">Contraseña</label><input type="password" name="pass" required [(ngModel)]="d.pass" placeholder="••••••••" class="${INPUT}" /></div>
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-600">Rol / Nivel de Acceso</label>
                    <select name="nivel_acceso" required [(ngModel)]="d.nivel_acceso" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                      <option value="">-- Selecciona un rol --</option>
                      @for (t of tipos; track t.idTipoUsuario) { <option [value]="t.idTipoUsuario + ''">{{ t.nombreTipoUsuario }}</option> } @empty { <option value="" disabled>No hay roles definidos en BD</option> }
                    </select>
                  </div>
                }
              </div>
              <div class="pt-6 border-t flex justify-end space-x-4">
                <a routerLink="/usuarioVer" class="px-6 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg hover:bg-gray-300 transition-colors">Cancelar</a>
                @if (editar) {
                  <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-bold rounded-lg shadow-md hover:bg-blue-700 transition-all transform hover:scale-105">Actualizar Usuario</button>
                } @else {
                  <button type="submit" class="px-6 py-3 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-300 transform hover:scale-105">Guardar Usuario</button>
                }
              </div>
            </form>
          </div>
        } @else {
          <div class="text-center p-8 border-2 border-dashed rounded-lg bg-gray-50">
            <i class="fa-solid fa-user-xmark text-4xl text-gray-400 mb-4"></i>
            <h2 class="text-xl font-bold text-gray-700">Usuario no encontrado</h2>
            <p class="text-gray-500 mb-4">No se pudieron cargar los datos del usuario.</p>
            <a routerLink="/usuarioVer" class="text-indigo-600 hover:underline">Volver a la lista</a>
          </div>
        }
      </div>
    }
  `
})
export class UsuarioForm implements OnInit {
  private api = inject(Api);
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private titulo = inject(Titulo);
  editar = false;
  u: any = null;
  usuarioOriginal = '';
  tipos: any[] = [];
  d: any = { nombre: '', cedula: '', cargo: '', celular: '', email: '', usuario: '', pass: '', nivel_acceso: '', estado: 'activo' };
  errores: string[] = [];
  listo = false;

  async ngOnInit() {
    this.editar = this.route.snapshot.data['modo'] === 'editar';
    this.titulo.set(this.editar ? 'Editar Usuario' : 'Crear Nuevo Usuario');
    if (this.editar) {
      const r = await this.api.get('usuarioEditar', 'index', { id: this.route.snapshot.paramMap.get('id') ?? '' });
      if (r.redirect) return void this.router.navigateByUrl('/' + r.redirect);
      this.u = r.usuario;
      this.tipos = r.tiposUsuario;
      this.errores = r.errores;
      if (this.u) {
        this.usuarioOriginal = this.u.usuario;
        this.d = { ...this.u, pass: '', nivel_acceso: String(this.u.nivel_acceso) };
      }
    } else {
      const r = await this.api.get('usuarioCrear', 'index');
      this.tipos = r.tiposUsuario;
      this.errores = r.errores;
    }
    this.listo = true;
  }

  async guardar(e: Event) {
    e.preventDefault();
    const campos = ['nombre', 'cedula', 'cargo', 'email', 'celular', 'usuario', 'pass', 'nivel_acceso'];
    const datos: Record<string, any> = {};
    campos.forEach((c) => (datos[c] = this.d[c] ?? ''));
    if (this.editar) {
      datos['estado'] = this.d.estado;
      datos['usuario_id'] = this.u.usuario_id;
    }
    const r = await this.api.post(this.editar ? 'usuarioEditar' : 'usuarioCrear', 'index', datos);
    if (r.success) this.router.navigateByUrl('/' + r.redirect);
    else this.errores = r.errores || [];
  }
}
