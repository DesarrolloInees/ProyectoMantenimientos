import { Component, computed, inject } from '@angular/core';
import { NavigationEnd, Router, RouterLink, RouterOutlet } from '@angular/router';
import { filter } from 'rxjs';
import { Auth } from '../core/auth';
import { Titulo } from '../core/ui';
import { menuPorRol } from './menu';
import { TurnoModal, TurnoService } from './turno-modal';

/** Port de app/views/plantillaVista.php (navbar + contenido + footer). */
@Component({
  selector: 'app-layout',
  imports: [RouterOutlet, RouterLink, TurnoModal],
  template: `
    <div class="min-h-screen flex flex-col">
      <nav class="bg-gray-900 text-white shadow-lg fixed w-full z-[9000] top-0">
        <div class="w-full px-4">
          <div class="flex justify-between h-16">
            <div class="flex items-center">
              <a routerLink="/inicio" class="flex-shrink-0 flex items-center mr-6 hover:opacity-80 transition">
                <img src="app/logos/logoIneesFondoBlanco.png" alt="Logo-Inees" class="h-10 w-auto object-contain" />
              </a>
              <div class="hidden xl:flex space-x-1 items-center h-full">
                <div class="flex items-center space-x-1 flex-wrap py-1 relative">
                  @for (g of menu(); track g.texto) {
                    @if (g.accion === 'turno') {
                      <button type="button" (click)="turno.abrirModal()" class="px-3 py-2 rounded-md text-sm transition flex items-center whitespace-nowrap" [class]="g.claseBoton">
                        <i class="mr-1.5" [class]="g.icono"></i> {{ g.texto }}
                      </button>
                    } @else if (g.ruta) {
                      <a [routerLink]="'/' + g.ruta" class="px-3 py-2 rounded-md text-sm font-medium transition flex items-center whitespace-nowrap" [class]="g.claseBoton || 'text-gray-300 hover:bg-gray-700 hover:text-white'">
                        <i class="mr-1.5" [class]="g.icono"></i> {{ g.texto }}
                      </a>
                    } @else {
                      <div class="relative group h-full flex items-center" [class.ml-2]="!g.oscuro">
                        <button type="button" class="text-gray-300 group-hover:bg-gray-700 group-hover:text-white px-3 py-2 rounded-md text-sm font-medium inline-flex items-center transition whitespace-nowrap">
                          @if (g.icono) { <i class="mr-1.5" [class]="g.icono"></i> }
                          <span>{{ g.texto }}</span>
                          <i class="fas fa-chevron-down ml-2 text-xs opacity-75"></i>
                        </button>
                        <div class="absolute top-full rounded-md shadow-lg py-1 hidden group-hover:block z-[9999] max-h-[80vh] overflow-y-auto"
                          [class]="(g.ancho || 'w-56') + ' ' + (g.oscuro ? 'bg-gray-800 border border-gray-700' : 'bg-white border border-gray-200') + ' ' + (g.alinearDerecha ? 'right-0' : 'left-0')">
                          @for (it of g.items; track $index) {
                            @if (it.seccion) {
                              <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50" [class.mt-2]="$index > 0">{{ it.texto }}</div>
                            } @else if (it.separador) {
                              <div class="border-t border-gray-100 my-1"></div>
                            } @else if (g.oscuro) {
                              <a [routerLink]="'/' + it.ruta" class="block px-4 py-2 text-sm text-gray-300 hover:bg-blue-600 hover:text-white">
                                @if (it.icono) { <i class="w-5" [class]="it.icono"></i> } {{ it.texto }}
                              </a>
                            } @else {
                              <a [routerLink]="'/' + it.ruta" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 border-l-4 border-transparent hover:border-blue-500" [class]="it.clase || ''">
                                @if (it.icono) { <i class="w-5 text-center mr-1" [class]="it.icono"></i> } {{ it.texto }}
                              </a>
                            }
                          }
                        </div>
                      </div>
                    }
                  }
                </div>
              </div>
            </div>

            <div class="flex items-center space-x-4">
              <div class="hidden md:block text-right leading-tight">
                <div class="text-sm font-bold text-gray-200">{{ nombre() }}</div>
                <div class="text-xs text-gray-400">{{ cargo() }}</div>
              </div>
              <div class="relative group h-full flex items-center">
                <button class="flex items-center focus:outline-none">
                  <div class="h-9 w-9 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white shadow border-2 border-gray-700 hover:border-blue-400 transition">
                    <i class="fas fa-user text-sm"></i>
                  </div>
                </button>
                <div class="absolute right-0 top-12 w-48 bg-white rounded-md shadow-xl py-1 hidden group-hover:block border border-gray-200 z-[9100]">
                  <div class="px-4 py-2 border-b border-gray-100 md:hidden">
                    <p class="text-sm font-bold text-gray-800">{{ nombre() }}</p>
                    <p class="text-xs text-gray-500">{{ auth.sesion()?.usuario_cargo }}</p>
                  </div>
                  <a href="#" (click)="salir($event)" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50"><i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión</a>
                </div>
              </div>
              <div class="xl:hidden flex items-center ml-2">
                <button (click)="movil = !movil" class="text-gray-300 hover:text-white focus:outline-none p-2 border border-gray-700 rounded active:bg-gray-800">
                  <i class="fas fa-bars fa-lg"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        @if (movil) {
          <div class="xl:hidden bg-gray-900 border-t border-gray-700 pb-4 px-2 overflow-y-auto max-h-[85vh] shadow-inner">
            @for (g of menu(); track g.texto) {
              @if (g.accion === 'turno') {
                <button type="button" (click)="turno.abrirModal(); movil = false" class="w-full text-left block px-3 py-3 rounded-md text-base font-bold text-white bg-emerald-600 hover:bg-emerald-700 mt-2">
                  <i class="mr-2" [class]="g.icono"></i> {{ g.texto }}
                </button>
              } @else if (g.ruta) {
                <a [routerLink]="'/' + g.ruta" (click)="movil = false" class="block px-3 py-3 rounded-md text-base font-medium text-gray-300 hover:bg-gray-700 hover:text-white mt-1">
                  <i class="mr-2" [class]="g.icono"></i> {{ g.texto }}
                </a>
              } @else {
                <details class="group mt-1">
                  <summary class="flex justify-between items-center px-3 py-3 rounded-md text-base font-medium text-gray-300 hover:bg-gray-700 hover:text-white cursor-pointer">
                    <span>@if (g.icono) { <i class="mr-2" [class]="g.icono"></i> } {{ g.texto }}</span>
                    <i class="fas fa-chevron-down text-xs"></i>
                  </summary>
                  <div class="pl-4 border-l border-gray-700 ml-3">
                    @for (it of g.items; track $index) {
                      @if (it.seccion) {
                        <div class="px-3 pt-3 pb-1 text-xs font-semibold text-gray-500 uppercase">{{ it.texto }}</div>
                      } @else if (!it.separador) {
                        <a [routerLink]="'/' + it.ruta" (click)="movil = false" class="block px-3 py-2 rounded-md text-sm text-gray-400 hover:bg-gray-700 hover:text-white">
                          @if (it.icono) { <i class="w-5 mr-1" [class]="it.icono"></i> } {{ it.texto }}
                        </a>
                      }
                    }
                  </div>
                </details>
              }
            }
            <a href="#" (click)="salir($event)" class="block px-3 py-3 rounded-md text-base font-medium text-red-400 hover:bg-gray-700 hover:text-red-300 mt-2 border-t border-gray-700">
              <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
            </a>
          </div>
        }
      </nav>

      <main class="flex-1 pt-20 pb-8 px-2 md:px-4 w-full z-0 relative">
        <div class="mb-6 border-b border-gray-300 pb-2 flex flex-col md:flex-row justify-between md:items-end gap-2">
          <h2 class="text-2xl font-bold text-gray-800">{{ titulo.valor() }}</h2>
          <div class="text-xs text-gray-500">INEES v5.0</div>
        </div>
        <router-outlet />
      </main>

      <footer class="bg-white border-t border-gray-200 mt-auto py-4 text-center text-xs text-gray-500">&copy; 2026 INEES Mantenimientos - Todos los derechos reservados.</footer>
    </div>

    @if (auth.nivel === 3) {
      <app-turno-modal />
    }
  `
})
export class Layout {
  auth = inject(Auth);
  titulo = inject(Titulo);
  turno = inject(TurnoService);
  private router = inject(Router);
  movil = false;

  menu = computed(() => menuPorRol(Number(this.auth.sesion()?.nivel_acceso ?? 0)));
  nombre = computed(() => this.auth.sesion()?.usuario_name || 'Usuario');
  cargo = computed(() => this.auth.sesion()?.usuario_cargo || 'Colaborador');

  constructor() {
    this.router.events.pipe(filter((e) => e instanceof NavigationEnd)).subscribe(() => {
      this.movil = false;
      let r = this.router.routerState.snapshot.root;
      while (r.firstChild) r = r.firstChild;
      const t = r.data?.['titulo'];
      if (t) this.titulo.set(t);
    });
  }

  salir(e: Event) {
    e.preventDefault();
    this.auth.logout();
  }
}

/** Página 404 (index.php: "Error 404 - Página no encontrada o acceso denegado."). */
@Component({
  selector: 'app-no-encontrado',
  imports: [RouterLink],
  template: `
    <div class="flex items-center justify-center min-h-[50vh]">
      <div class="text-center">
        <h1 class="text-4xl font-bold text-gray-800 mb-2">Error 404</h1>
        <p class="text-gray-600 mb-6">Página no encontrada o acceso denegado.</p>
        <a routerLink="/inicio" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Volver al inicio</a>
      </div>
    </div>
  `
})
export class NoEncontrado {
  constructor() {
    inject(Titulo).set('Error 404');
  }
}
