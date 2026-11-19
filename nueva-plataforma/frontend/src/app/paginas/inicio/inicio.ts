import { Component, OnInit, inject } from '@angular/core';
import { RouterLink } from '@angular/router';
import { DecimalPipe } from '@angular/common';
import { Api } from '../../core/api';
import { Auth } from '../../core/auth';
import { Titulo } from '../../core/ui';
import { TurnoService } from '../../layout/turno-modal';

/** Port de app/views/inicio/inicioVista.php */
@Component({
  selector: 'app-inicio',
  imports: [RouterLink, DecimalPipe],
  template: `
    @if (d) {
      @if (nivel === 3) {
        <div class="min-h-[80vh] flex flex-col items-center justify-center px-4 py-8">
          <div class="w-full max-w-lg rounded-2xl shadow-xl overflow-hidden bg-white border border-gray-100">
            <div class="bg-gradient-to-br from-indigo-600 via-blue-600 to-indigo-700 p-6 text-center text-white relative">
              <div class="inline-block bg-white/20 p-3.5 rounded-full mb-3 backdrop-blur-sm border border-white/20 shadow-inner"><i class="fas fa-user-astronaut text-3xl"></i></div>
              <h1 class="text-2xl font-bold tracking-tight">¡Hola, {{ d.usuario.nombre }}!</h1>
              <p class="text-indigo-100 text-sm font-medium mt-0.5">{{ d.usuario.rol_nombre }}</p>
              <div class="mt-3 inline-flex items-center gap-1.5 px-3 py-1 bg-black/20 backdrop-blur-sm rounded-full text-xs text-indigo-100">
                <i class="far fa-clock"></i><span>{{ d.fechaHoy }} · {{ d.horaActual }}</span>
              </div>
            </div>
            <div class="p-6 space-y-3">
              <p class="text-center text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">¿Qué necesitas hacer hoy?</p>
              <div class="bg-indigo-50/60 border border-indigo-100 rounded-xl p-4">
                <div class="flex items-center justify-between mb-1">
                  <p class="text-xs font-bold text-indigo-700 uppercase tracking-wider"><i class="fas fa-wrench mr-1"></i> Mis servicios este mes</p>
                  <span class="text-[10px] text-indigo-400 font-medium">{{ d.inicioMes }} – {{ d.hoyDmy }}</span>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center">
                  <div class="bg-white rounded-lg border border-sky-100 py-2"><p class="text-[10px] font-bold text-sky-600 uppercase leading-tight">Preventivo<br />Básico</p><p class="text-lg font-extrabold text-gray-800">{{ r.basico || 0 }}</p></div>
                  <div class="bg-white rounded-lg border border-indigo-100 py-2"><p class="text-[10px] font-bold text-indigo-600 uppercase leading-tight">Preventivo<br />Profundo</p><p class="text-lg font-extrabold text-gray-800">{{ r.profundo || 0 }}</p></div>
                  <div class="bg-white rounded-lg border border-amber-100 py-2"><p class="text-[10px] font-bold text-amber-600 uppercase leading-tight">Correctivo</p><p class="text-lg font-extrabold text-gray-800 mt-3">{{ r.correctivo || 0 }}</p></div>
                  <div class="bg-white rounded-lg border border-red-100 py-2"><p class="text-[10px] font-bold text-red-600 uppercase leading-tight">Fallido</p><p class="text-lg font-extrabold text-gray-800">{{ r.fallido || 0 }}</p></div>
                  <div class="bg-white rounded-lg border border-teal-100 py-2"><p class="text-[10px] font-bold text-teal-600 uppercase leading-tight">Nueva<br />Arquitectura</p><p class="text-lg font-extrabold text-gray-800">{{ r.nueva_arquitectura || 0 }}</p></div>
                  <div class="bg-white rounded-lg border border-gray-200 py-2"><p class="text-[10px] font-bold text-gray-500 uppercase leading-tight" title="Garantía, Kisan, instalaciones y otros estados">Otros</p><p class="text-lg font-extrabold text-gray-800">{{ r.otros || 0 }}</p></div>
                  <div class="bg-indigo-600 rounded-lg border border-indigo-600 py-2"><p class="text-[10px] font-bold text-indigo-100 uppercase leading-tight">Total</p><p class="text-lg font-extrabold text-white">{{ r.total || 0 }}</p></div>
                </div>
              </div>

              <button type="button" (click)="turno.abrirModal()" class="w-full text-left group flex items-center gap-4 bg-emerald-50/80 hover:bg-emerald-100/60 p-3.5 rounded-xl border-2 border-emerald-300 hover:border-emerald-500 transition-all hover:shadow-md">
                <div class="bg-emerald-100 p-3 rounded-lg group-hover:bg-emerald-600 transition shrink-0"><i class="fas fa-user-clock text-emerald-600 group-hover:text-white text-lg"></i></div>
                <div class="flex-1 min-w-0">
                  <h3 class="font-bold text-gray-800 text-sm group-hover:text-emerald-700 transition">{{ turno.turnoDelDia() ? 'Entrada registrada' : 'Marcar entrada' }}</h3>
                  <p class="text-xs text-gray-500 truncate">
                    @if (turno.turnoDelDia(); as t) {
                      Día {{ t.fecha_texto }} · {{ t.hora_entrada }}{{ t.novedad ? ' · con novedad' : '' }}
                    } @else {
                      Registra la hora en que entraste hoy
                    }
                  </p>
                </div>
                @if (turno.turnoDelDia(); as t) {
                  <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200 whitespace-nowrap"><i class="fas fa-check mr-1"></i>{{ t.hora_entrada }}</span>
                } @else {
                  <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-200 text-gray-600 border border-gray-300 whitespace-nowrap">Sin registrar</span>
                }
              </button>

              @for (o of opcionesTecnico; track o.ruta) {
                <a [routerLink]="'/' + o.ruta" class="group flex items-center gap-4 bg-gray-50/80 hover:bg-indigo-50/50 p-3.5 rounded-xl border border-gray-200 hover:border-indigo-400 transition-all hover:shadow-md">
                  <div class="bg-indigo-100 p-3 rounded-lg group-hover:bg-indigo-600 transition shrink-0"><i class="text-indigo-600 group-hover:text-white text-lg" [class]="o.icono"></i></div>
                  <div class="flex-1">
                    <h3 class="font-bold text-gray-800 text-sm group-hover:text-indigo-700 transition">{{ o.titulo }}</h3>
                    <p class="text-xs text-gray-500">{{ o.desc }}</p>
                  </div>
                  <i class="fas fa-chevron-right text-gray-300 group-hover:text-indigo-600 group-hover:translate-x-0.5 transition"></i>
                </a>
              }
              <div class="pt-4 text-center">
                <a href="#" (click)="salir($event)" class="text-xs font-medium text-gray-400 hover:text-red-500 inline-flex items-center gap-1.5 transition"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a>
              </div>
            </div>
          </div>
        </div>
      } @else if (nivel === 4) {
        <div class="min-h-[80vh] flex flex-col items-center justify-center px-4 py-8">
          <div class="w-full max-w-md rounded-2xl shadow-2xl overflow-hidden bg-white">
            <div class="bg-gradient-to-br from-emerald-600 to-teal-500 p-6 text-center text-white">
              <div class="inline-block bg-white/20 p-3 rounded-full mb-3"><i class="fa-solid fa-person-military-pointing fa-3x"></i></div>
              <h1 class="text-2xl font-bold">Bienvenido, {{ d.usuario.nombre }}</h1>
              <p class="text-emerald-100 text-sm">{{ d.usuario.rol_nombre }}</p>
              <p class="text-emerald-100 text-xs mt-1">{{ d.fechaHoy }} · {{ d.horaActual }}</p>
            </div>
            <div class="p-6 space-y-4">
              <a routerLink="/serviciosPdf" class="group flex items-center gap-4 bg-white p-4 rounded-xl border border-emerald-100 hover:border-emerald-500 transition-all">
                <div class="bg-emerald-50 p-3 rounded-full group-hover:bg-emerald-600"><i class="fas fa-file-pdf text-emerald-600 group-hover:text-white text-xl"></i></div>
                <div class="flex-1"><h3 class="font-bold text-gray-800">Consultar Historial PDF</h3><p class="text-xs text-gray-500">Descarga de reportes</p></div>
                <i class="fas fa-chevron-right text-gray-400"></i>
              </a>
              <div class="pt-4 text-center"><a href="#" (click)="salir($event)" class="text-sm text-gray-400 hover:text-red-500 inline-flex items-center gap-1"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></div>
            </div>
          </div>
        </div>
      } @else if (nivel === 5) {
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">
          <div class="rounded-2xl bg-gradient-to-r from-amber-700 via-orange-600 to-amber-600 p-6 shadow-xl text-white">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
              <div>
                <div class="flex items-center gap-3 mb-1"><i class="fa-solid fa-truck-fast text-3xl text-amber-200"></i><h1 class="text-3xl font-bold tracking-tight">Panel Supervisor Motorizados</h1></div>
                <p class="text-orange-100 text-sm">Gestión de rutas, técnicos y operatividad móvil</p>
                <div class="mt-3 flex items-center gap-2 text-xs bg-black/20 backdrop-blur-sm px-3 py-1.5 rounded-full w-fit"><i class="fas fa-user-circle"></i><span>{{ d.usuario.nombre }} ({{ d.usuario.rol_nombre }})</span></div>
              </div>
              <div class="text-left md:text-right bg-white/10 backdrop-blur-sm p-3 rounded-xl border border-white/10">
                <p class="text-2xl font-mono font-bold tracking-wider">{{ d.horaActual }}</p>
                <p class="text-xs text-orange-100 opacity-90">{{ d.fechaHoy }}</p>
              </div>
            </div>
          </div>
          @for (s of seccionesSupervisor; track s.titulo) {
            <div>
              <div class="flex items-center gap-2 mb-4"><i class="text-lg" [class]="s.icono"></i><h2 class="text-lg font-bold text-gray-800 uppercase tracking-wide">{{ s.titulo }}</h2></div>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @for (o of s.items; track o.ruta) {
                  <a [routerLink]="'/' + o.ruta" class="group bg-white rounded-xl shadow-sm hover:shadow-md p-5 transition-all hover:-translate-y-1 border border-gray-200 flex items-start gap-4" [class]="s.borde">
                    <div class="p-3.5 rounded-xl transition shrink-0" [class]="s.fondoIcono"><i class="text-xl group-hover:text-white" [class]="o.icono + ' ' + s.colorIcono"></i></div>
                    <div>
                      <h3 class="font-bold text-gray-800 text-base transition" [class]="s.hoverTitulo">{{ o.titulo }}</h3>
                      <p class="text-xs text-gray-500 mt-1">{{ o.desc }}</p>
                    </div>
                  </a>
                }
              </div>
            </div>
          }
          <div class="text-center pt-6 border-t border-gray-200">
            <a href="#" (click)="salir($event)" class="inline-flex items-center gap-2 text-gray-500 hover:text-red-600 font-medium transition"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a>
          </div>
        </div>
      } @else {
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <div class="mb-8 rounded-2xl bg-gradient-to-r from-slate-800 to-slate-900 p-6 shadow-xl text-white">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
              <div>
                <h1 class="text-3xl font-bold tracking-tight">Dashboard</h1>
                <p class="text-blue-200 text-sm mt-1">Panel de Control General · Gestión de Servicios</p>
                <div class="mt-2 flex items-center gap-2 text-sm"><i class="fas fa-user-circle"></i><span>{{ d.usuario.nombre }} ({{ d.usuario.rol_nombre }})</span></div>
              </div>
              <div class="text-right">
                <p class="text-3xl font-mono font-bold">{{ d.horaActual }}</p>
                <p class="text-sm opacity-80">{{ d.fechaHoy }}</p>
              </div>
            </div>
          </div>

          @if (tieneEstadisticas) {
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-10">
              <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-blue-500 hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                  <div><p class="text-gray-500 text-sm">Órdenes este mes</p><p class="text-3xl font-bold text-gray-800">{{ d.estadisticas.ordenes_mes | number: '1.0-0':'en-US' }}</p></div>
                  <div class="bg-blue-100 p-3 rounded-full"><i class="fas fa-clipboard-list text-blue-600 text-xl"></i></div>
                </div>
              </div>
              <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-green-500 hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                  <div><p class="text-gray-500 text-sm">Clientes activos</p><p class="text-3xl font-bold text-gray-800">{{ d.estadisticas.clientes | number: '1.0-0':'en-US' }}</p></div>
                  <div class="bg-green-100 p-3 rounded-full"><i class="fas fa-users text-green-600 text-xl"></i></div>
                </div>
              </div>
              <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-purple-500 hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                  <div><p class="text-gray-500 text-sm">Técnicos activos</p><p class="text-3xl font-bold text-gray-800">{{ d.estadisticas.tecnicos | number: '1.0-0':'en-US' }}</p></div>
                  <div class="bg-purple-100 p-3 rounded-full"><i class="fas fa-user-cog text-purple-600 text-xl"></i></div>
                </div>
              </div>
            </div>
          } @else {
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-8 rounded"><p class="text-yellow-700"><i class="fas fa-info-circle mr-2"></i> No se pudieron cargar estadísticas. Verifica las tablas.</p></div>
          }

          <h2 class="text-xl font-bold text-gray-700 mb-4 border-b pb-2 flex items-center gap-2"><i class="fas fa-rocket text-blue-500"></i> Operaciones Rápidas</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <a routerLink="/ordenCrear" class="group relative bg-gradient-to-br from-blue-600 to-indigo-700 rounded-xl shadow-lg p-6 text-white overflow-hidden transform hover:-translate-y-1 transition duration-300">
              <div class="absolute right-0 top-0 opacity-10 text-7xl group-hover:scale-110 transition">+</div>
              <div class="relative z-10"><i class="fas fa-plus-circle text-3xl mb-2 block"></i><h3 class="text-xl font-bold">Crear Servicio</h3><p class="text-blue-100 text-sm mt-1">Nueva orden de mantenimiento</p></div>
            </a>
            <a routerLink="/ordenVer" class="group bg-white rounded-xl shadow-md p-6 border border-gray-100 hover:border-blue-300 transition">
              <div class="flex items-center gap-4">
                <div class="bg-blue-50 p-3 rounded-lg group-hover:bg-blue-600 transition"><i class="fas fa-list-alt text-blue-600 group-hover:text-white text-2xl"></i></div>
                <div><h3 class="text-lg font-bold text-gray-800">Gestionar Órdenes</h3><p class="text-gray-500 text-sm">Listado, estados y asignaciones</p></div>
              </div>
            </a>
            <a routerLink="/clienteVer" class="group bg-white rounded-xl shadow-md p-6 border border-gray-100 hover:border-purple-300 transition">
              <div class="flex items-center gap-4">
                <div class="bg-purple-50 p-3 rounded-lg group-hover:bg-purple-600 transition"><i class="fa-regular fa-user text-purple-600 group-hover:text-white text-2xl"></i></div>
                <div><h3 class="text-lg font-bold text-gray-800">Base de Clientes</h3><p class="text-gray-500 text-sm">Información y sedes</p></div>
              </div>
            </a>
          </div>

          <h2 class="text-xl font-bold text-gray-700 mb-4 border-b pb-2 flex items-center gap-2"><i class="fas fa-chart-pie text-indigo-500"></i> Centro de Reportes</h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-12">
            @for (rp of reportes; track rp.url) {
              <a [routerLink]="'/' + rp.url" class="bg-white rounded-xl shadow p-4 hover:shadow-lg transition flex items-center gap-4 border-l-4" [class]="rp.borde">
                <div class="p-3 rounded-full" [class]="rp.fondo"><i class="fas text-xl" [class]="rp.icon + ' ' + rp.texto"></i></div>
                <div><h4 class="font-bold text-gray-800">{{ rp.titulo }}</h4><p class="text-xs text-gray-500">{{ rp.desc }}</p></div>
              </a>
            }
          </div>

          <h2 class="text-xl font-bold text-gray-700 mb-4 border-b pb-2 flex items-center gap-2"><i class="fas fa-cogs text-gray-500"></i> Configuración Rápida</h2>
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            @for (m of modulos; track m.url) {
              <a [routerLink]="'/' + m.url" class="flex flex-col items-center p-4 bg-gray-50 rounded-xl hover:bg-white transition border border-gray-200 text-center group">
                <i class="fas text-2xl mb-2 group-hover:scale-110 transition" [class]="m.icon + ' ' + m.color"></i>
                <span class="text-xs font-semibold text-gray-700">{{ m.texto }}</span>
              </a>
            }
          </div>
        </div>
      }
    }
  `
})
export class Inicio implements OnInit {
  private api = inject(Api);
  private auth = inject(Auth);
  turno = inject(TurnoService);
  d: any = null;

  opcionesTecnico = [
    { ruta: 'tecnicoProgramacion', icono: 'fa-solid fa-envelope', titulo: 'Programación Servicios', desc: 'Ver y gestionar servicios agendados' },
    { ruta: 'cronometroHistorial', icono: 'fa-solid fa-envelope', titulo: 'Cronómetro Servicios Historial', desc: 'Ver y gestionar los tiempos de los servicios realizados' },
    { ruta: 'ordenMovil', icono: 'fas fa-search', titulo: 'Consultar Historial', desc: 'Buscar por cliente, punto o máquina' },
    { ruta: 'parqueaderoHistorial', icono: 'fa-solid fa-square-parking', titulo: 'Historial de Parqueaderos', desc: 'Consultar registros y recibos de parqueo' },
    { ruta: 'horaExtraHistorial', icono: 'fa-solid fa-clock', titulo: 'Historial Horas Extra', desc: 'Revisar reporte y registros de asistencia' }
  ];

  seccionesSupervisor = [
    {
      titulo: 'Gestión Operativa & Servicios', icono: 'fas fa-tasks text-amber-600', borde: 'hover:border-amber-400', fondoIcono: 'bg-amber-100 group-hover:bg-amber-600', colorIcono: 'text-amber-700', hoverTitulo: 'group-hover:text-amber-600',
      items: [
        { ruta: 'ordenVer', icono: 'fa-solid fa-clipboard-list', titulo: 'Revisar Servicios', desc: 'Monitorear órdenes y estado de servicios asignados.' },
        { ruta: 'cronometroAdmin', icono: 'fa-solid fa-stopwatch', titulo: 'Cronómetrar Servicios Técnicos', desc: 'Monitorear los tiempos de los servicios realizados.' },
        { ruta: 'ordenDetalleBuscar', icono: 'fa-solid fa-magnifying-glass', titulo: 'Buscar Servicio', desc: 'Búsqueda rápida y detallada de órdenes específicas.' },
        { ruta: 'controlRemisionVer', icono: 'fa-solid fa-ticket', titulo: 'Administrar Remisiones', desc: 'Control y asignación de remisiones operativas.' }
      ]
    },
    {
      titulo: 'Repuestos e Inventario', icono: 'fas fa-boxes-stacked text-orange-600', borde: 'hover:border-orange-400', fondoIcono: 'bg-orange-100 group-hover:bg-orange-600', colorIcono: 'text-orange-700', hoverTitulo: 'group-hover:text-orange-600',
      items: [
        { ruta: 'repuestoVer', icono: 'fa-solid fa-puzzle-piece', titulo: 'Ver Repuestos', desc: 'Consulta del catálogo y stock general de repuestos.' },
        { ruta: 'inventarioTecnicoVer', icono: 'fa-solid fa-gears', titulo: 'Inventario Técnicos', desc: 'Gestión de repuestos asignados a la flota motorizada.' }
      ]
    },
    {
      titulo: 'Reportes & Descargas Excel', icono: 'fas fa-chart-pie text-emerald-600', borde: 'hover:border-emerald-400', fondoIcono: 'bg-emerald-100 group-hover:bg-emerald-600', colorIcono: 'text-emerald-700', hoverTitulo: 'group-hover:text-emerald-600',
      items: [
        { ruta: 'reporteTecnico', icono: 'fa-solid fa-chart-line', titulo: 'Reporte de Técnicos', desc: 'Métricas de rendimiento y productividad de la flota.' },
        { ruta: 'reporteEjecutivo', icono: 'fa-solid fa-chart-pie', titulo: 'Reporte Ejecutivo', desc: 'Resumen general y consolidado de operaciones.' },
        { ruta: 'exportarExcel', icono: 'fa-solid fa-file-excel', titulo: 'Excel Programación', desc: 'Descargar programación de rutas motorizadas.' },
        { ruta: 'ordenReporte', icono: 'fa-solid fa-file-csv', titulo: 'Excel Servicios', desc: 'Reporte detallado por tipo de mantenimiento.' },
        { ruta: 'horaExtraAdmin', icono: 'fa-solid fa-clock', titulo: 'Reporte Horas Extra', desc: 'Administrar Horas Extra.' },
        { ruta: 'parqueaderoAdmin', icono: 'fa-solid fa-clock', titulo: 'Reporte Parqueaderos', desc: 'Administrar Parqueaderos.' }
      ]
    }
  ];

  reportes = [
    { url: 'reporteEjecutivo', icon: 'fa-chart-line', borde: 'border-indigo-500', fondo: 'bg-indigo-100', texto: 'text-indigo-600', titulo: 'Reporte Ejecutivo', desc: 'KPIs y gráficas' },
    { url: 'reporteTecnico', icon: 'fa-book-open-reader', borde: 'border-cyan-500', fondo: 'bg-cyan-100', texto: 'text-cyan-600', titulo: 'Reporte Técnico', desc: 'Productividad' },
    { url: 'reporteRepuesto', icon: 'fa-screwdriver-wrench', borde: 'border-teal-500', fondo: 'bg-teal-100', texto: 'text-teal-600', titulo: 'Reporte Repuestos', desc: 'Consumo de materiales' }
  ];

  modulos = [
    { url: 'usuarioVer', icon: 'fa-key', color: 'text-yellow-600', texto: 'Usuarios' },
    { url: 'tecnicoVer', icon: 'fa-user-gear', color: 'text-blue-600', texto: 'Técnicos' },
    { url: 'maquinaVer', icon: 'fa-cash-register', color: 'text-green-600', texto: 'Máquinas' },
    { url: 'puntoVer', icon: 'fa-city', color: 'text-purple-600', texto: 'Puntos' },
    { url: 'repuestoVer', icon: 'fa-puzzle-piece', color: 'text-orange-600', texto: 'Repuestos' },
    { url: 'tarifaVer', icon: 'fa-money-bill', color: 'text-emerald-600', texto: 'Tarifas' }
  ];

  constructor() {
    inject(Titulo).set('Inicio');
  }

  get nivel() {
    return this.auth.nivel;
  }

  get r() {
    return this.d?.resumenTecnicoMes ?? {};
  }

  get tieneEstadisticas() {
    return this.d?.estadisticas && Object.keys(this.d.estadisticas).length > 0;
  }

  async ngOnInit() {
    this.d = await this.api.get('inicio', 'index');
    if (this.nivel === 3) this.turno.turnoDelDia.set(this.d.turnoHoy);
  }

  salir(e: Event) {
    e.preventDefault();
    this.auth.logout();
  }
}
