import { Component, OnDestroy, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { Api } from '../../core/api';

const DIAS = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
const DIAS_CONFIG: Record<string, { color: string; bg: string; border: string; icono: string }> = {
  lunes: { color: 'blue', bg: 'bg-blue-50', border: 'border-blue-300', icono: 'text-blue-600' },
  martes: { color: 'green', bg: 'bg-green-50', border: 'border-green-300', icono: 'text-green-600' },
  miercoles: { color: 'yellow', bg: 'bg-yellow-50', border: 'border-yellow-300', icono: 'text-yellow-600' },
  jueves: { color: 'orange', bg: 'bg-orange-50', border: 'border-orange-300', icono: 'text-orange-600' },
  viernes: { color: 'red', bg: 'bg-red-50', border: 'border-red-300', icono: 'text-red-600' },
  sabado: { color: 'purple', bg: 'bg-purple-50', border: 'border-purple-300', icono: 'text-purple-600' }
};
const NOMBRES_DIA = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
const MESES = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

function lunesEstaSemana(): string {
  const d = new Date();
  const dow = d.getDay() || 7;
  d.setDate(d.getDate() - (dow - 1));
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}
function hoyIso() {
  return new Date().toISOString().split('T')[0];
}
const cap = (s: string) => s.charAt(0).toUpperCase() + s.slice(1);

interface DiaCfg {
  tecnico: string;
  zonas: Set<string>;
  puntos: string[];
}

/** Port de programacionCrearVista.php + js/programacion/{core,clientes-delegacion,calendario-semanal,maquinas-inactivas,modal-aledanios,previsualizacion}.js */
@Component({
  selector: 'app-programacion-crear',
  imports: [FormsModule, RouterLink],
  template: `
    <div class="w-full max-w-4xl mx-auto">
      @if (mensajeExito) {
        <div class="bg-green-100 text-green-800 p-4 rounded-lg mb-4 shadow-md border-l-4 border-green-500"><i class="fas fa-check-circle mr-2"></i>{{ mensajeExito }}</div>
      }
      @if (errores.length) {
        <div class="bg-red-100 text-red-800 p-4 rounded-lg mb-4 shadow-md border-l-4 border-red-500">
          <i class="fas fa-exclamation-triangle mr-2"></i>
          @for (e of errores; track $index) { <div>{{ e }}</div> }
        </div>
      }

      @if (propuesta.length) {
        <!-- ===================== PREVISUALIZACIÓN ===================== -->
        <div class="bg-white rounded-xl shadow-lg border-2 border-indigo-300 overflow-hidden">
          <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 p-5 flex justify-between items-center text-white">
            <div>
              <h2 class="text-2xl font-bold"><i class="fas fa-calendar-check mr-2"></i> Previsualización de Rutas</h2>
              <p class="text-sm text-indigo-200 mt-1">Revise y edite antes de aprobar</p>
            </div>
            <div class="text-sm bg-indigo-800 px-4 py-2 rounded-lg">{{ propuestaInicial }} servicios programados</div>
          </div>
          <div class="overflow-x-auto max-h-[600px]">
            <table class="w-full text-sm text-left text-gray-600">
              <thead class="text-xs text-gray-700 uppercase bg-gray-100 sticky top-0 z-10">
                <tr><th class="px-6 py-3">Fecha</th><th class="px-6 py-3">Técnico (Ruta)</th><th class="px-6 py-3">Zona</th><th class="px-6 py-3">Punto</th><th class="px-6 py-3 text-center">Acción</th></tr>
              </thead>
              <tbody>
                @for (p of propuesta; track p._k; let i = $index) {
                  @if (i === 0 || propuesta[i - 1].fechaGrupo !== p.fechaGrupo) {
                    <tr class="bg-gradient-to-r from-indigo-100 to-blue-50 border-y-2 border-indigo-300">
                      <td colspan="5" class="px-6 py-3 font-bold text-gray-800 text-sm uppercase tracking-wide">
                        <i class="fas fa-calendar-day mr-2 text-indigo-600"></i> {{ encabezadoFecha(p.fechaGrupo) }}
                        <span class="ml-3 text-xs bg-indigo-600 text-white px-3 py-1 rounded-full">{{ conteoDia[p.fechaGrupo] }} servicio{{ conteoDia[p.fechaGrupo] != 1 ? 's' : '' }}</span>
                      </td>
                    </tr>
                  }
                  <tr class="bg-white border-b hover:bg-blue-50 transition">
                    <td class="px-6 py-4"><input type="date" [(ngModel)]="p.fecha_visita" (change)="reordenar()" class="border border-gray-300 rounded px-2 py-1 text-xs w-full" /></td>
                    <td class="px-6 py-4">
                      <select [(ngModel)]="p.id_tecnico" class="border border-gray-300 rounded px-2 py-1 text-xs w-full bg-white">
                        @for (t of tecnicos; track t.id_tecnico) { <option [value]="'' + t.id_tecnico">{{ t.nombre_tecnico }}</option> }
                      </select>
                    </td>
                    <td class="px-6 py-4"><span class="bg-indigo-100 text-indigo-800 text-xs font-semibold px-3 py-1 rounded-full">{{ p.zona }}</span></td>
                    <td class="px-6 py-4">
                      <div class="font-bold text-gray-900">
                        {{ p.nombre_punto }}
                        @if (p.es_fuera_servicio) { <span class="bg-red-100 text-red-700 text-xs font-bold px-2 py-0.5 rounded ml-1" title="Maquina fuera de servicio"><i class="fas fa-power-off mr-1"></i>Fuera de Servicio</span> }
                      </div>
                      <div class="text-xs text-gray-600">{{ p.nombre_cliente }}</div>
                    </td>
                    <td class="px-6 py-4 text-center"><button type="button" (click)="eliminarFila(i)" class="text-red-600 hover:text-red-800 px-2 py-1 rounded"><i class="fas fa-trash-alt"></i></button></td>
                  </tr>
                }
              </tbody>
            </table>
          </div>
          <div class="p-6 bg-gray-50 border-t flex justify-between items-center">
            <button type="button" (click)="volverConfig()" class="px-6 py-3 bg-indigo-100 text-indigo-700 hover:bg-indigo-200 rounded-lg font-bold transition"><i class="fas fa-arrow-left mr-2"></i> Volver para Agregar Más</button>
            <button type="button" [disabled]="guardando" (click)="aprobar()" class="px-8 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 font-bold shadow-lg">
              @if (guardando) { <i class="fas fa-spinner fa-spin mr-2"></i> Guardando... } @else { <i class="fas fa-check-circle mr-2"></i> Aprobar y Crear Órdenes }
            </button>
          </div>
        </div>
      } @else {
        <!-- ===================== MÁQUINAS FUERA DE SERVICIO ===================== -->
        @if (inactivas.length) {
          <div class="bg-white p-6 rounded-xl shadow-md border border-red-200 mb-6">
            <h2 class="text-xl font-bold text-red-800 mb-2 border-b-2 border-red-500 pb-2">
              <i class="fas fa-exclamation-triangle mr-2 text-red-600"></i> Maquinas Fuera de Servicio
              <span class="ml-2 text-sm font-normal text-red-600">({{ inactivas.length }} encontradas)</span>
              @if (selInactivas.size > 0) { <span class="ml-2 text-sm font-normal"><span class="bg-red-600 text-white px-2 py-1 rounded-full text-xs">{{ selInactivas.size }} seleccionada{{ selInactivas.size > 1 ? 's' : '' }}</span></span> }
            </h2>
            <p class="text-sm text-gray-600 mb-4 bg-red-50 p-3 rounded border-l-4 border-red-400">
              <i class="fas fa-info-circle mr-1"></i> Selecciona una o varias máquinas para buscar <strong>puntos aledaños</strong> — si marcas máquinas de <strong>zonas distintas</strong>, aparece un botón abajo para verlas todas juntas en el mismo modal.
              <br /><span class="text-xs text-gray-500">Los puntos que no se programen tendrán su máquina restaurada automáticamente a "Operativo" al aprobar la programación.</span>
            </p>
            <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
              <p class="text-xs font-semibold text-gray-600 flex items-center"><i class="fas fa-hand-pointer mr-1 text-red-500"></i> Usa los botones para marcar todas las máquinas, una zona completa o solo las que necesites.</p>
              <div class="flex flex-wrap items-center gap-2">
                <button type="button" (click)="marcarInactivas(inactivas, true)" class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded font-bold shadow-sm transition"><i class="fas fa-check-double mr-1"></i> Marcar Todas</button>
                <button type="button" (click)="marcarInactivas(inactivas, false)" class="text-xs bg-white hover:bg-gray-100 text-gray-700 border border-gray-300 px-3 py-1.5 rounded font-bold transition"><i class="fas fa-times mr-1"></i> Desmarcar Todas</button>
              </div>
            </div>
            <div class="space-y-4">
              @for (g of inactivasPorZona; track g.zona) {
                <div class="border border-red-200 rounded-lg overflow-hidden">
                  <div class="bg-red-100 px-4 py-2 flex flex-wrap justify-between items-center gap-2">
                    <span class="font-bold text-red-800"><i class="fas fa-map-marker-alt mr-1"></i> {{ g.zona }} <span class="text-xs font-normal text-red-600">({{ g.maquinas.length }} maquina{{ g.maquinas.length > 1 ? 's' : '' }})</span></span>
                    <div class="flex flex-wrap items-center gap-2">
                      <button type="button" (click)="marcarInactivas(g.maquinas, true)" class="text-xs bg-white hover:bg-red-50 text-red-700 border border-red-300 px-3 py-1 rounded font-semibold shadow-sm transition"><i class="fas fa-check-square mr-1"></i> Marcar Zona</button>
                      <button type="button" (click)="marcarInactivas(g.maquinas, false)" class="text-xs bg-white hover:bg-gray-100 text-gray-600 border border-gray-300 px-3 py-1 rounded font-semibold shadow-sm transition"><i class="fas fa-square mr-1"></i> Desmarcar Zona</button>
                      <button type="button" (click)="lanzarAledanios(0, g.zona)" class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded font-semibold shadow-sm transition"><i class="fas fa-search mr-1"></i> Ver Aledaños</button>
                    </div>
                  </div>
                  <div class="p-3 bg-white">
                    @for (m of g.maquinas; track m.id_maquina) {
                      <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg mb-2 border border-red-100 hover:shadow-md transition">
                        <div class="flex items-center space-x-3">
                          <input type="checkbox" class="w-5 h-5 text-red-600 rounded border-red-300" [checked]="selInactivas.has('' + m.id_punto)" (change)="alternarInactiva(m, $any($event.target).checked)" />
                          <div>
                            <div class="font-bold text-gray-900 text-sm">{{ m.nombre_punto }} <span class="ml-1 text-xs bg-red-600 text-white px-1.5 rounded">FUERA DE SERVICIO</span></div>
                            <div class="text-xs text-gray-600">{{ m.nombre_cliente }} | {{ m.device_id }} | {{ m.nombre_tipo_maquina }}</div>
                          </div>
                        </div>
                        <button type="button" (click)="restaurarMaquina(m.device_id)" class="text-xs bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded font-semibold shadow-sm transition" title="Volver a Operativo (no se va a programar)"><i class="fas fa-check-circle mr-1"></i> Restaurar a Operativo</button>
                      </div>
                    }
                  </div>
                </div>
              }
            </div>
          </div>
          @if (selInactivas.size >= 2) {
            <div class="fixed bottom-4 left-1/2 -translate-x-1/2 z-40 bg-gray-900 text-white rounded-full shadow-2xl px-5 py-3 flex items-center gap-4">
              <span class="text-sm font-semibold"><i class="fas fa-check-circle text-green-400 mr-1"></i> {{ selInactivas.size }} máquinas · {{ zonasInactivasSel.length }} zona{{ zonasInactivasSel.length > 1 ? 's' : '' }}</span>
              <button type="button" (click)="aledaniosSeleccionadas()" class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold px-4 py-2 rounded-full"><i class="fas fa-search-location mr-1"></i> Ver aledaños de zonas seleccionadas</button>
            </div>
          }
        } @else if (cargado) {
          <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-lg mb-6">
            <p class="font-bold text-green-800"><i class="fas fa-check-circle mr-1"></i> No hay maquinas fuera de servicio actualmente.</p>
            <p class="text-sm text-green-700">Puedes importar un Excel en <a routerLink="/importarEstadoMaquina" class="underline font-bold">Importar Estado Maquinas</a> para marcarlas.</p>
          </div>
        }

        <!-- ===================== PASO 1: DELEGACIÓN Y CLIENTES ===================== -->
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200">
          <h2 class="text-xl font-bold text-gray-800 mb-4 border-b-2 border-indigo-500 pb-2"><i class="fas fa-map-marked-alt mr-2 text-indigo-600"></i> Programación de Rutas por Semana</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-building mr-1"></i> Delegación *</label>
              <select [(ngModel)]="delegacion" (change)="cambiarDelegacion()" class="w-full border border-gray-300 rounded-lg shadow-sm p-2">
                <option value="">-- Seleccione una delegación --</option>
                @for (d of delegaciones; track d.id_delegacion) { <option [value]="'' + d.id_delegacion">{{ d.nombre_delegacion }}</option> }
              </select>
            </div>
            @if (delegacion && clientes.length) {
              <div class="md:col-span-2">
                <div class="flex justify-between items-center mb-2">
                  <label class="text-sm font-semibold text-gray-700"><i class="fas fa-users mr-1"></i> Clientes a Incluir</label>
                  <div class="space-x-2">
                    <button type="button" (click)="todosClientes(true)" class="text-xs bg-green-100 hover:bg-green-200 text-green-800 px-3 py-1 rounded font-semibold"><i class="fas fa-check-double mr-1"></i> Todos</button>
                    <button type="button" (click)="todosClientes(false)" class="text-xs bg-red-100 hover:bg-red-200 text-red-800 px-3 py-1 rounded font-semibold"><i class="fas fa-times mr-1"></i> Ninguno</button>
                    <button type="button" (click)="aplicarClientes()" class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded font-semibold"><i class="fas fa-sync-alt mr-1"></i> Aplicar</button>
                  </div>
                </div>
                <div class="border border-gray-300 rounded-lg p-3 bg-white max-h-48 overflow-y-auto">
                  @for (c of clientes; track c.id_cliente) {
                    <label class="flex items-start space-x-2 p-2 hover:bg-gray-50 rounded cursor-pointer">
                      <input type="checkbox" class="mt-1 w-4 h-4 text-indigo-600 rounded border-gray-300" [checked]="clientesMarcados.has('' + c.id_cliente)" (change)="alternarCliente(c.id_cliente, $any($event.target).checked)" />
                      <span class="text-sm text-gray-700 flex-1"><strong>{{ c.nombre_cliente }}</strong> <span class="text-gray-500">({{ c.puntos_pendientes }} puntos pendientes)</span></span>
                    </label>
                  }
                </div>
                <p class="text-xs text-gray-600 mt-1"><i class="fas fa-info-circle mr-1"></i> <strong>Por defecto están TODOS seleccionados.</strong> Desmarca los clientes que NO quieres programar y pulsa "Aplicar".</p>
              </div>
            }
            @if (delegacion && zonasConteo.length) {
              <div class="md:col-span-3 bg-blue-50 p-4 rounded-lg border border-blue-200">
                <p class="text-sm font-bold text-blue-800 mb-2"><i class="fas fa-info-circle mr-1"></i> Puntos Pendientes por Zona ({{ totalConteo }} total):</p>
                <div class="flex flex-wrap gap-2">
                  @for (z of zonasConteo; track z[0]) { <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-3 py-1 rounded-full">{{ z[0] }}: {{ z[1] }}</span> }
                </div>
                @if (clientesAplicados.length < clientes.length) {
                  <p class="text-xs text-orange-600 mt-2"><i class="fas fa-exclamation-triangle mr-1"></i> {{ clientes.length - clientesAplicados.length }} cliente(s) excluidos del cálculo</p>
                }
              </div>
            }
          </div>

          <!-- ===================== PASO 2: CALENDARIO SEMANAL ===================== -->
          @if (delegacion && zonas.length) {
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-xl border-2 border-blue-300 mb-6 mt-6">
              <h3 class="font-bold text-blue-900 mb-4 text-lg"><i class="fas fa-calendar-week mr-2"></i> Configuración del Calendario Semanal</h3>
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
                <div><label class="text-sm font-semibold text-gray-700 mb-2 block">Fecha de Inicio *</label><input type="date" [(ngModel)]="fechaInicio" class="w-full border border-gray-300 rounded-lg p-2" /></div>
                <div><label class="text-sm font-semibold text-gray-700 mb-2 block">Número de Semanas *</label><input type="number" min="1" max="12" [(ngModel)]="semanas" class="w-full border border-gray-300 rounded-lg p-2" /></div>
                <div><label class="text-sm font-semibold text-gray-700 mb-2 block">Máximo Servicios por Día *</label><input type="number" min="1" max="20" [(ngModel)]="maxServicios" class="w-full border border-gray-300 rounded-lg p-2" /></div>
              </div>
              <div class="bg-white p-6 rounded-lg shadow-md">
                <h4 class="font-bold text-gray-800 mb-2 flex items-center"><i class="fas fa-route mr-2 text-indigo-600"></i> Planificación Semanal de Rutas</h4>
                <p class="text-sm text-gray-600 mb-4 bg-blue-50 p-3 rounded border-l-4 border-blue-400">
                  <i class="fas fa-info-circle mr-1"></i> <strong>Ejemplo:</strong> Juan Pérez puede trabajar <strong>Lunes: Sur + Sur Oriente</strong>, <strong>Martes: Sur Occidente + Sur</strong>, etc.
                  <br /><span class="text-xs">Cada día puede tener diferentes combinaciones de zonas para el mismo técnico.</span>
                </p>
                <div class="space-y-4">
                  @for (dia of dias; track dia) {
                    <div class="border-2 rounded-lg p-4 transition hover:shadow-md" [class]="cfgDia(dia).border + ' ' + cfgDia(dia).bg">
                      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                        <div class="md:col-span-1">
                          <div class="flex items-center mb-2"><i class="fas fa-calendar-day mr-2 text-xl" [class]="cfgDia(dia).icono"></i><span class="font-bold text-gray-800 text-lg capitalize">{{ cap(dia) }}</span></div>
                          <select [(ngModel)]="cal[dia].tecnico" (change)="toggleZonas(dia)" class="w-full border border-gray-300 rounded-lg text-sm bg-white shadow-sm p-2">
                            <option value="">-- Sin programar este día --</option>
                            @for (t of tecnicos; track t.id_tecnico) { <option [value]="'' + t.id_tecnico">{{ t.nombre_tecnico }}</option> }
                          </select>
                        </div>
                        <div class="md:col-span-2">
                          <div class="flex flex-wrap justify-between items-center gap-2 mb-2">
                            <label class="block text-sm font-bold text-gray-700"><i class="fas fa-map-marked-alt mr-1"></i> Zonas a Recorrer este Día</label>
                            <div class="flex items-center gap-1.5">
                              <button type="button" (click)="todasZonasDia(dia, true)" class="text-[11px] bg-green-100 hover:bg-green-200 text-green-800 px-2.5 py-1 rounded font-bold transition"><i class="fas fa-check-double mr-1"></i> Todas</button>
                              <button type="button" (click)="todasZonasDia(dia, false)" class="text-[11px] bg-red-100 hover:bg-red-200 text-red-800 px-2.5 py-1 rounded font-bold transition"><i class="fas fa-times mr-1"></i> Ninguna</button>
                            </div>
                          </div>
                          <div class="border border-gray-300 rounded-lg p-3 max-h-40 overflow-y-auto" [class]="cal[dia].tecnico ? 'bg-white' : 'bg-gray-50 opacity-50 pointer-events-none'">
                            @for (z of zonas; track z) {
                              <label class="flex items-start space-x-2 p-2 hover:bg-white rounded cursor-pointer">
                                <input type="checkbox" class="mt-1 w-4 h-4 rounded border-gray-300" [checked]="cal[dia].zonas.has(z)" (change)="alternarZona(dia, z, $any($event.target).checked)" />
                                <span class="text-sm text-gray-700 flex-1"><strong>{{ z }}</strong> <span class="text-gray-500"> - {{ conteo[z] ?? 0 }} puntos pendientes</span></span>
                              </label>
                            }
                          </div>
                          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mt-2">
                            <p class="text-xs text-gray-600 flex items-center"><i class="fas fa-check-square mr-1"></i> Marca una o varias zonas para el día</p>
                            <button type="button" [disabled]="!cal[dia].tecnico || !cal[dia].zonas.size" (click)="abrirModalDia(dia)" class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-3.5 py-1.5 rounded-lg font-bold shadow-sm transition flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                              <i class="fas fa-tasks mr-1.5"></i> Elegir Puntos ({{ cal[dia].puntos.length }})
                            </button>
                          </div>
                        </div>
                      </div>
                      @if (cal[dia].tecnico) {
                        <div class="mt-3">
                          <div class="bg-white p-3 rounded-lg border shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-2">
                            <div class="text-xs text-gray-700">
                              @if (cal[dia].zonas.size) {
                                <strong>{{ nombreTecnico(cal[dia].tecnico) }}</strong> recorrerá: <span class="font-semibold text-indigo-600">{{ zonasDe(dia).join(' + ') }}</span>
                              } @else {
                                <strong>{{ nombreTecnico(cal[dia].tecnico) }}</strong> asignado - <span class="text-orange-600 font-semibold">Falta seleccionar zonas</span>
                              }
                            </div>
                            @if (cal[dia].zonas.size) {
                              @if (cal[dia].puntos.length > 0) {
                                <span class="bg-indigo-100 text-indigo-800 text-xs font-bold px-3 py-1 rounded-full border border-indigo-300 flex items-center"><i class="fas fa-check-circle mr-1 text-indigo-600"></i> {{ cal[dia].puntos.length }} punto{{ cal[dia].puntos.length > 1 ? 's' : '' }} elegido{{ cal[dia].puntos.length > 1 ? 's' : '' }}</span>
                              } @else {
                                <span class="bg-amber-50 text-amber-700 text-xs font-semibold px-2.5 py-1 rounded-full border border-amber-200 flex items-center"><i class="fas fa-exclamation-triangle mr-1 text-amber-500"></i> Sin puntos elegidos</span>
                              }
                            }
                          </div>
                        </div>
                      }
                    </div>
                  }
                </div>
              </div>
              <div class="mt-6 text-center">
                <button type="button" [disabled]="enviando" (click)="generar()" class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-10 py-4 rounded-lg font-bold text-lg shadow-xl hover:from-blue-700 hover:to-blue-800">
                  <i class="fas mr-2" [class]="enviando ? 'fa-spinner fa-spin' : 'fa-magic'"></i> Generar Programación de Rutas
                </button>
              </div>
            </div>
          } @else if (delegacion && cargado) {
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg"><p class="font-bold text-yellow-800">No hay zonas disponibles en esta delegación</p></div>
          }
        </div>
      }
    </div>

    <!-- ===================== MODAL ALEDAÑOS ===================== -->
    @if (modalAled) {
      <div class="fixed inset-0 z-50 bg-black bg-opacity-60 flex items-center justify-center p-4">
        <div class="bg-gray-50 rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden border border-gray-300">
          <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-6 py-4 flex justify-between items-center shadow-md">
            <h3 class="font-bold text-lg flex items-center"><i class="fas fa-map-marked-alt mr-2 text-blue-200"></i> Puntos en Zona(s): <span class="text-blue-100 ml-1">{{ aledZonas }}</span></h3>
            <button type="button" (click)="modalAled = false" class="text-white hover:text-red-300 transition"><i class="fas fa-times text-2xl"></i></button>
          </div>
          <div class="p-6 overflow-y-auto flex-1 bg-gray-50">
            @if (aledCargando) {
              <div class="text-center p-12"><i class="fas fa-spinner fa-spin fa-3x text-indigo-500 mb-4"></i><p class="text-gray-600 font-semibold text-lg">Buscando puntos cercanos...</p></div>
            } @else if (aledError) {
              <div class="bg-red-100 text-red-800 p-4 rounded-lg font-bold"><i class="fas fa-exclamation-triangle mr-2"></i> {{ aledError }}</div>
            } @else if (!aledPuntos.length) {
              <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg shadow-sm"><p class="font-bold text-yellow-800 text-lg"><i class="fas fa-info-circle mr-2"></i> No hay puntos pendientes en esta zona.</p></div>
            } @else {
              <div class="flex justify-between items-center mb-5 bg-white p-3 rounded-lg shadow-sm border border-gray-200">
                <p class="text-gray-800 font-semibold text-sm">Selecciona los puntos y asigna fecha/técnico:</p>
                <div class="space-x-2">
                  <button type="button" (click)="marcarAled(true)" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-800 px-3 py-1.5 rounded-lg font-bold border"><i class="fas fa-check-double mr-1"></i> Todos</button>
                  <button type="button" (click)="marcarAled(false)" class="text-xs bg-white hover:bg-gray-50 text-gray-600 px-3 py-1.5 rounded-lg font-bold border"><i class="fas fa-times mr-1"></i> Ninguno</button>
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @for (p of aledPuntos; track p.id_punto) {
                  <div class="p-4 bg-white border border-gray-200 rounded-xl hover:shadow-md transition w-full flex flex-col justify-between">
                    <div>
                      <div class="flex items-start space-x-3">
                        <input type="checkbox" class="mt-1 w-5 h-5 text-blue-600 rounded border-gray-300 shadow-sm" [(ngModel)]="p._sel" />
                        <div class="flex-1 min-w-0">
                          <div class="font-bold text-gray-900 text-sm mb-1 break-words">{{ p.nombre_punto }} @if (p.fuera_de_servicio == 1) { <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-[10px] font-bold ml-1 border border-red-200">FUERA DE SERVICIO</span> }</div>
                          <div class="text-xs text-gray-600 space-y-1">
                            <div><i class="fas fa-user-tie text-gray-400 w-4"></i> {{ p.nombre_cliente }}</div>
                            <div><i class="fas fa-map-marker-alt text-red-400 w-4"></i> {{ p.direccion || 'Sin Dirección' }}</div>
                            <div><i class="fas fa-microchip text-gray-400 w-4"></i> {{ p.device_id || 'S/N' }} ({{ p.tipo_maquina || 'N/A' }})</div>
                          </div>
                        </div>
                      </div>
                      <div class="mt-3 pt-2 border-t border-gray-100 flex justify-between items-center text-[11px] text-gray-500 font-semibold">
                        <span><i class="fas fa-calendar-check text-green-500 mr-1"></i> Último: {{ p.fecha_ultima_visita ? ('' + p.fecha_ultima_visita).split(' ')[0] : 'Sin Registro' }}</span>
                        <span class="px-2 py-0.5 bg-orange-50 text-orange-600 rounded-full"><i class="fas fa-clock mr-1"></i> {{ p.dias_sin_visita || '0' }} días</span>
                      </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-200 grid grid-cols-2 gap-2 bg-gray-50 p-2 rounded-lg">
                      <div><label class="text-[10px] font-bold text-gray-600 block mb-1">Fecha Visita:</label><input type="date" [(ngModel)]="p._fecha" class="w-full text-xs border border-gray-300 rounded px-2 py-1 bg-white" /></div>
                      <div>
                        <label class="text-[10px] font-bold text-gray-600 block mb-1">Técnico:</label>
                        <select [(ngModel)]="p._tecnico" class="w-full text-xs border border-gray-300 rounded px-2 py-1 bg-white">
                          <option value="">-- Seleccionar --</option>
                          @for (t of tecnicos; track t.id_tecnico) { <option [value]="'' + t.id_tecnico">{{ t.nombre_tecnico }}</option> }
                        </select>
                      </div>
                    </div>
                  </div>
                }
              </div>
            }
          </div>
          <div class="bg-white px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
            <button type="button" (click)="modalAled = false" class="px-5 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-bold transition shadow-sm">Cancelar</button>
            <button type="button" (click)="agregarAled()" class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg font-bold shadow-md transition flex items-center"><i class="fas fa-plus-circle mr-2"></i> Agregar a Programación</button>
          </div>
        </div>
      </div>
    }

    <!-- ===================== MODAL PUNTOS POR DÍA ===================== -->
    @if (diaModal) {
      <div class="fixed inset-0 z-50 bg-black bg-opacity-60 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden border border-gray-200">
          <div class="bg-gradient-to-r from-indigo-700 via-indigo-600 to-blue-600 text-white px-6 py-4 flex justify-between items-center shadow-md">
            <div>
              <h3 class="font-bold text-lg flex items-center"><i class="fas fa-calendar-check mr-2 text-indigo-200"></i> Seleccionar Puntos para <span class="text-yellow-300 ml-1 font-extrabold">{{ cap(diaModal) }}</span></h3>
              <p class="text-xs text-indigo-100 mt-0.5">Técnico: <strong class="text-white">{{ nombreTecnico(cal[diaModal].tecnico) }}</strong> | Zona(s): <strong class="text-white">{{ zonasDe(diaModal).join(', ') }}</strong></p>
            </div>
            <button type="button" (click)="cerrarModalDia()" class="text-white hover:text-red-300 transition text-2xl font-bold"><i class="fas fa-times"></i></button>
          </div>
          <div class="p-4 bg-gray-50 border-b border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="relative w-full sm:w-80">
              <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-xs"></i>
              <input type="text" [(ngModel)]="buscador" placeholder="Buscar por punto, cliente, zona o dirección..." class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div class="flex items-center space-x-2 w-full sm:w-auto justify-end">
              <button type="button" (click)="marcarModalDia(true)" title="Marca todos los puntos que se ven en la lista (respeta el buscador)" class="text-xs bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 px-3 py-1.5 rounded-lg font-semibold transition flex items-center"><i class="fas fa-check-double mr-1.5"></i> Todos</button>
              <button type="button" (click)="marcarModalDia(false)" title="Desmarca todos los puntos que se ven en la lista" class="text-xs bg-white hover:bg-gray-100 text-gray-700 border border-gray-300 px-3 py-1.5 rounded-lg font-semibold transition flex items-center"><i class="fas fa-times mr-1.5"></i> Ninguno</button>
            </div>
          </div>
          <div class="p-6 overflow-y-auto flex-1 bg-gray-50/50">
            @if (diaCargando) {
              <div class="text-center p-12"><i class="fas fa-spinner fa-spin fa-3x text-indigo-600 mb-4"></i><p class="text-gray-600 font-semibold text-base">Cargando puntos de las zonas seleccionadas...</p><p class="text-xs text-gray-400 mt-1">Filtrando por delegación y clientes activos</p></div>
            } @else if (diaError) {
              <div class="bg-red-100 text-red-800 p-4 rounded-lg font-bold"><i class="fas fa-exclamation-triangle mr-2"></i> {{ diaError }}</div>
            } @else if (!diaPuntos.length) {
              <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg shadow-sm text-center">
                <p class="font-bold text-yellow-800 text-base"><i class="fas fa-info-circle mr-2"></i> No se encontraron puntos pendientes en la(s) zona(s) seleccionada(s).</p>
                <p class="text-xs text-yellow-700 mt-1">Verifica que los clientes tengan puntos pendientes o que no estén ya programados en otras órdenes activas.</p>
              </div>
            } @else {
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @for (p of diaPuntos; track p.id_punto) {
                  @if (visible(p)) {
                    <div class="p-3.5 border rounded-xl hover:shadow-md transition flex flex-col justify-between" [class]="p._sel ? 'border-indigo-500 ring-2 ring-indigo-200 bg-indigo-50/30' : 'border-gray-200 bg-white'">
                      <div class="flex items-start space-x-3">
                        <input type="checkbox" class="mt-1 w-5 h-5 text-indigo-600 rounded border-gray-300 cursor-pointer shadow-sm" [(ngModel)]="p._sel" />
                        <div class="flex-1 min-w-0">
                          <div class="flex items-center justify-between flex-wrap gap-1 mb-1">
                            <div class="font-bold text-gray-900 text-sm break-words">
                              {{ p.nombre_punto }}
                              @if (p.fuera_de_servicio == 1) { <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-[10px] font-bold ml-1 border border-red-200"><i class="fas fa-power-off mr-1"></i>FUERA DE SERVICIO</span> }
                              @if (p._otroDia) { <span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded text-[10px] font-bold ml-1 border border-amber-300" [title]="'Ya estaba marcado para ' + p._otroDia"><i class="fas fa-calendar-day mr-1"></i>En {{ p._otroDia.toUpperCase() }}</span> }
                            </div>
                            <span class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded text-[10px] font-bold border border-indigo-200"><i class="fas fa-map-marker-alt mr-1"></i>{{ p.zona }}</span>
                          </div>
                          <div class="text-xs text-gray-600 space-y-1">
                            <div class="truncate"><i class="fas fa-user-tie text-gray-400 w-4"></i> {{ p.nombre_cliente }}</div>
                            <div class="truncate"><i class="fas fa-location-dot text-red-400 w-4"></i> {{ p.direccion || 'Sin dirección' }}</div>
                            <div class="truncate"><i class="fas fa-microchip text-gray-400 w-4"></i> {{ p.device_id || 'S/N' }} ({{ p.tipo_maquina || 'N/A' }})</div>
                          </div>
                        </div>
                      </div>
                      <div class="mt-2.5 pt-2 border-t border-gray-100 flex justify-between items-center text-[11px] text-gray-500">
                        <span><i class="fas fa-calendar-check text-green-500 mr-1"></i> Último: {{ p.fecha_ultima_visita ? ('' + p.fecha_ultima_visita).split(' ')[0] : 'Sin registro' }}</span>
                        <span class="px-2 py-0.5 bg-orange-50 text-orange-600 rounded-full font-semibold"><i class="fas fa-clock mr-1"></i> {{ p.dias_sin_visita || '0' }} días</span>
                      </div>
                    </div>
                  }
                }
              </div>
            }
          </div>
          <div class="bg-white px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-3">
            <div class="text-sm font-semibold text-gray-700 flex items-center"><i class="fas fa-map-pin mr-2 text-indigo-600"></i> <span><strong>{{ seleccionadosModal }}</strong> punto(s) seleccionado(s)</span></div>
            <div class="flex space-x-2">
              <button type="button" (click)="cerrarModalDia()" class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-bold text-sm transition">Cancelar</button>
              <button type="button" (click)="guardarModalDia()" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold text-sm shadow-md transition flex items-center"><i class="fas fa-check mr-2"></i> Confirmar Puntos para este Día</button>
            </div>
          </div>
        </div>
      </div>
    }
  `
})
export class ProgramacionCrear implements OnInit, OnDestroy {
  private api = inject(Api);
  private route = inject(ActivatedRoute);
  private router = inject(Router);

  dias = DIAS;
  cap = cap;
  cargado = false;
  mensajeExito = '';
  errores: string[] = [];

  delegaciones: any[] = [];
  tecnicos: any[] = [];
  clientes: any[] = [];
  zonas: string[] = [];
  conteo: Record<string, any> = {};
  inactivas: any[] = [];
  delegacion = '';
  clientesMarcados = new Set<string>();
  clientesAplicados: string[] = [];

  fechaInicio = lunesEstaSemana();
  semanas: any = 1;
  maxServicios: any = 8;
  cal: Record<string, DiaCfg> = Object.fromEntries(DIAS.map((d) => [d, { tecnico: '', zonas: new Set<string>(), puntos: [] as string[] }]));
  /** puntos_aledanios_extra (agregados desde el modal mientras se configura el calendario) */
  extraAledanios: string[] = [];
  enviando = false;

  // previsualización
  propuesta: any[] = [];
  propuestaInicial = 0;
  conteoDia: Record<string, number> = {};
  guardando = false;

  // máquinas inactivas
  selInactivas = new Set<string>();
  private zonaPorPunto: Record<string, string> = {};

  // modal aledaños
  modalAled = false;
  aledZonas = '';
  aledCargando = false;
  aledError = '';
  aledPuntos: any[] = [];

  // modal puntos por día
  diaModal: string | null = null;
  diaCargando = false;
  diaError = '';
  diaPuntos: any[] = [];
  buscador = '';

  private sub: any;

  get inactivasPorZona() {
    const g: Record<string, any[]> = {};
    this.inactivas.forEach((m) => (g[m.zona || 'Sin Zona'] ||= []).push(m));
    return Object.entries(g).map(([zona, maquinas]) => ({ zona, maquinas }));
  }
  get zonasInactivasSel() {
    return [...new Set(Array.from(this.selInactivas).map((id) => this.zonaPorPunto[id]).filter(Boolean))];
  }
  get zonasConteo() {
    return Object.entries(this.conteo);
  }
  get totalConteo() {
    return Object.values(this.conteo).reduce((s: number, v: any) => s + Number(v), 0);
  }
  get seleccionadosModal() {
    return this.diaPuntos.filter((p) => p._sel).length;
  }

  ngOnInit() {
    this.sub = this.route.queryParamMap.subscribe((q) => {
      this.mensajeExito = q.get('exito') || '';
      this.delegacion = q.get('delegacion') || '';
      this.cargar(q.getAll('clientes'));
    });
  }

  ngOnDestroy() {
    this.sub?.unsubscribe();
  }

  private aplicarBase(r: any) {
    this.delegaciones = r.listaDelegaciones || [];
    this.tecnicos = r.listaTecnicos || [];
    this.inactivas = r.listaMaquinasInactivas || [];
    this.inactivas.forEach((m) => (this.zonaPorPunto[String(m.id_punto)] = m.zona));
    this.clientes = r.listaClientes || [];
    this.zonas = (r.listaZonas || []).map(String);
    this.conteo = r.conteoZonas || {};
    this.clientesAplicados = (r.clientesSeleccionados || []).map(String);
    this.clientesMarcados = new Set(this.clientesAplicados);
    this.cargado = true;
  }

  private async cargar(clientes: string[] = []) {
    const params: any = {};
    if (this.delegacion) params.delegacion = this.delegacion;
    if (clientes.length) params.clientes = clientes;
    this.aplicarBase(await this.api.get('programacionCrear', 'index', params));
  }

  // ================================================================ DELEGACIÓN / CLIENTES
  cambiarDelegacion() {
    this.router.navigate([], { queryParams: { delegacion: this.delegacion || null } });
  }
  todosClientes(m: boolean) {
    this.clientesMarcados = m ? new Set(this.clientes.map((c) => String(c.id_cliente))) : new Set();
  }
  alternarCliente(id: any, m: boolean) {
    if (m) this.clientesMarcados.add(String(id));
    else this.clientesMarcados.delete(String(id));
  }
  aplicarClientes() {
    this.router.navigate([], { queryParams: { delegacion: this.delegacion, clientes: Array.from(this.clientesMarcados) } });
  }

  // ================================================================ CALENDARIO (calendario-semanal.js)
  cfgDia(d: string) {
    return DIAS_CONFIG[d];
  }
  nombreTecnico(id: any) {
    return this.tecnicos.find((t) => String(t.id_tecnico) === String(id))?.nombre_tecnico || '';
  }
  zonasDe(dia: string) {
    return this.zonas.filter((z) => this.cal[dia].zonas.has(z));
  }
  toggleZonas(dia: string) {
    if (!this.cal[dia].tecnico) {
      this.cal[dia].zonas = new Set();
      this.cal[dia].puntos = [];
    }
  }
  todasZonasDia(dia: string, m: boolean) {
    if (!this.zonas.length) return;
    if (!this.cal[dia].tecnico) {
      alert('⚠️ Primero selecciona un técnico para este día; después podrás marcar las zonas.');
      return;
    }
    this.cal[dia].zonas = m ? new Set(this.zonas) : new Set();
  }
  alternarZona(dia: string, z: string, m: boolean) {
    if (m) this.cal[dia].zonas.add(z);
    else this.cal[dia].zonas.delete(z);
  }
  private diaAsignado(id: string, excluir: string) {
    return DIAS.find((d) => d !== excluir && this.cal[d].puntos.includes(id)) || null;
  }

  async abrirModalDia(dia: string) {
    if (!this.cal[dia].tecnico) {
      alert('⚠️ Primero debes seleccionar un técnico para este día.');
      return;
    }
    const zonas = this.zonasDe(dia);
    if (!zonas.length) {
      alert('⚠️ Marca al menos una zona para este día antes de elegir los puntos.');
      return;
    }
    this.diaModal = dia;
    this.buscador = '';
    this.diaCargando = true;
    this.diaError = '';
    this.diaPuntos = [];
    try {
      const params: any = { delegacion: this.delegacion || '', zonas: zonas.join(',') };
      if (this.clientesAplicados.length) params.clientes = this.clientesAplicados.join(',');
      const d: any = await this.api.get('programacionCrear', 'obtener_puntos_zonas', params);
      if (d.error) this.diaError = d.error;
      else
        this.diaPuntos = (d.puntos || []).map((p: any) => {
          const id = String(p.id_punto);
          const texto = [p.nombre_punto, p.nombre_cliente, p.direccion, p.zona, p.device_id].map((x) => x || '').join(' ').toLowerCase();
          return { ...p, _sel: this.cal[dia].puntos.includes(id), _otroDia: this.diaAsignado(id, dia), _texto: texto };
        });
    } catch (e: any) {
      this.diaError = 'Error de conexión: ' + (e?.message || '');
    } finally {
      this.diaCargando = false;
    }
  }
  visible(p: any) {
    const t = this.buscador.trim().toLowerCase();
    return !t || p._texto.includes(t);
  }
  marcarModalDia(m: boolean) {
    this.diaPuntos.forEach((p) => {
      if (this.visible(p)) p._sel = m;
    });
  }
  cerrarModalDia() {
    this.diaModal = null;
    this.diaPuntos = [];
  }
  guardarModalDia() {
    const dia = this.diaModal!;
    const ids = this.diaPuntos.filter((p) => p._sel).map((p) => String(p.id_punto));
    if (ids.length === 0 && !confirm(`No has seleccionado ningún punto para ${dia.toUpperCase()}.\n\n¿Deseas dejar este día sin puntos asignados?`)) return;
    ids.forEach((id) => DIAS.forEach((d) => d !== dia && (this.cal[d].puntos = this.cal[d].puntos.filter((x) => x !== id))));
    this.cal[dia].puntos = ids;
    this.cerrarModalDia();
  }

  async generar() {
    let valida = false;
    let total = 0;
    const errs: string[] = [];
    DIAS.forEach((d) => {
      const c = this.cal[d];
      if (c.tecnico && c.zonas.size > 0) {
        if (c.puntos.length > 0) {
          valida = true;
          total += c.puntos.length;
        } else errs.push(`${cap(d)}: Asignaste técnico y zonas, pero falta pulsar "Elegir Puntos" para seleccionar las máquinas a visitar.`);
      } else if (c.tecnico && c.zonas.size === 0) errs.push(`${cap(d)}: Tiene técnico seleccionado pero ninguna zona marcada.`);
    });
    if (!valida) {
      alert('⚠️ Debes configurar al menos un día con técnico, zonas y puntos seleccionados para continuar.');
      return;
    }
    if (errs.length) {
      alert('⚠️ Por favor completa los siguientes días antes de continuar:\n\n• ' + errs.join('\n• '));
      return;
    }
    let resumen = 'Se generará la previsualización con la siguiente asignación:\n\n';
    DIAS.forEach((d) => {
      const c = this.cal[d];
      if (c.tecnico && c.puntos.length > 0) resumen += `✓ ${cap(d)}: ${this.nombreTecnico(c.tecnico)} → ${c.puntos.length} punto(s) en [${this.zonasDe(d).join(' + ')}]\n`;
    });
    resumen += `\nTotal de servicios a programar: ${total}\n\n¿Deseas continuar a la Previsualización de Rutas?`;
    if (!confirm(resumen)) return;

    const datos: Record<string, any> = {
      delegacion: this.delegacion,
      clientes: this.clientesAplicados,
      fecha_inicio: this.fechaInicio,
      semanas: this.semanas,
      max_servicios: this.maxServicios,
      puntos_aledanios_extra: this.extraAledanios
    };
    const puntosDia: Record<string, string[]> = {};
    DIAS.forEach((d) => {
      datos['tecnico_' + d] = this.cal[d].tecnico;
      datos['zonas_' + d] = this.zonasDe(d);
      if (this.cal[d].puntos.length) puntosDia[d] = this.cal[d].puntos;
    });
    datos['puntos_dia'] = puntosDia;
    this.enviando = true;
    try {
      const r: any = await this.api.postForm('programacionCrear', 'previsualizar', datos);
      this.mostrarResultado(r);
    } finally {
      this.enviando = false;
    }
  }

  private mostrarResultado(r: any) {
    this.errores = r.errores || [];
    this.mensajeExito = '';
    this.propuesta = (r.propuesta || []).map((p: any, i: number) => ({ ...p, _k: i, id_tecnico: p.id_tecnico != null ? String(p.id_tecnico) : '', fechaGrupo: p.fecha_visita }));
    this.propuestaInicial = this.propuesta.length;
    this.recontar();
    window.scrollTo({ top: 0 });
  }

  // ================================================================ PREVISUALIZACIÓN (previsualizacion.js)
  private recontar() {
    this.conteoDia = {};
    this.propuesta.forEach((p) => (this.conteoDia[p.fechaGrupo] = (this.conteoDia[p.fechaGrupo] || 0) + 1));
  }
  encabezadoFecha(f: string) {
    const [y, m, d] = String(f).split('-').map(Number);
    const dt = new Date(y, m - 1, d);
    return `${NOMBRES_DIA[dt.getDay()]}, ${String(d).padStart(2, '0')} de ${MESES[m]}`;
  }
  eliminarFila(i: number) {
    if (!confirm('¿Eliminar este servicio de la programación?')) return;
    this.propuesta.splice(i, 1);
    this.recontar();
  }
  /** reordenarFilasPorFecha(): en PHP sólo se movían las filas (los encabezados de día quedaban fijos) */
  reordenar() {
    this.propuesta.sort((a, b) => String(a.fecha_visita).localeCompare(String(b.fecha_visita)));
  }
  volverConfig() {
    this.propuesta = [];
    this.errores = [];
  }
  async aprobar() {
    if (!this.propuesta.length) {
      alert('No hay servicios en la tabla para aprobar.');
      return;
    }
    this.guardando = true;
    try {
      const final = this.propuesta.map((p) => ({ fecha_visita: p.fecha_visita, id_punto: p.id_punto, id_tecnico: p.id_tecnico }));
      const r: any = await this.api.postForm('programacionCrear', 'guardar_definitivo', { final });
      if (r.status) {
        this.propuesta = [];
        this.extraAledanios = [];
        DIAS.forEach((d) => (this.cal[d] = { tecnico: '', zonas: new Set(), puntos: [] }));
        this.delegacion = '';
        this.router.navigate([], { queryParams: { exito: r.exito } });
      } else if (r.vacio) {
        this.volverConfig();
      } else {
        this.propuesta = [];
        this.errores = [r.error];
        this.cargar();
      }
    } finally {
      this.guardando = false;
    }
  }

  // ================================================================ MÁQUINAS INACTIVAS (maquinas-inactivas.js)
  marcarInactivas(lista: any[], m: boolean) {
    lista.forEach((x) => (m ? this.selInactivas.add(String(x.id_punto)) : this.selInactivas.delete(String(x.id_punto))));
    this.selInactivas = new Set(this.selInactivas);
  }
  alternarInactiva(m: any, v: boolean) {
    this.marcarInactivas([m], v);
  }
  async restaurarMaquina(dev: string) {
    if (!confirm(`Vas a restaurar la maquina ${dev} a estado Operativo.\n\nEsto significa que NO se programará en esta ocasión. ¿Continuar?`)) return;
    try {
      const r: any = await this.api.postForm('programacionCrear', 'restaurarMaquinaIndividual', { device_id: dev });
      if (r.status) {
        alert('Maquina restaurada a Operativo.');
        this.cargar(this.clientesAplicados);
      } else alert(r.msg || 'No se pudo restaurar la maquina.');
    } catch {
      alert('Error de conexión al restaurar la maquina.');
    }
  }
  aledaniosSeleccionadas() {
    if (this.selInactivas.size === 0) return alert('Por favor marca al menos una máquina fuera de servicio para tomar sus zonas.');
    const zonas = this.zonasInactivasSel;
    if (!zonas.length) return alert('Las máquinas seleccionadas no tienen zona asignada.');
    this.lanzarAledanios(0, zonas.join(','));
  }

  // ================================================================ MODAL ALEDAÑOS (modal-aledanios.js)
  async lanzarAledanios(idPunto: number, zonas: string) {
    this.modalAled = true;
    this.aledZonas = zonas;
    this.aledCargando = true;
    this.aledError = '';
    this.aledPuntos = [];
    try {
      const d: any = await this.api.get('programacionCrear', 'puntos_aledanios', { id_punto: idPunto, zona: zonas });
      if (d.error) this.aledError = d.error;
      else this.aledPuntos = (d.puntos || []).map((p: any) => ({ ...p, _sel: this.selInactivas.has(String(p.id_punto)), _fecha: hoyIso(), _tecnico: '' }));
    } catch (e: any) {
      this.aledError = 'Error de conexión: ' + (e?.message || '');
    } finally {
      this.aledCargando = false;
    }
  }
  marcarAled(m: boolean) {
    this.aledPuntos.forEach((p) => (p._sel = m));
  }
  async agregarAled() {
    const sel = this.aledPuntos.filter((p) => p._sel);
    if (!sel.length) return alert('No has seleccionado ningún punto.');
    if (sel.some((p) => !p._fecha || !p._tecnico)) return alert('⚠️ Asigna Fecha de Visita y Técnico a todos los puntos seleccionados.');
    const nuevos = sel.map((p) => ({ id_punto: String(p.id_punto), fecha_visita: p._fecha, id_tecnico: p._tecnico }));

    // Con el calendario semanal visible (formCalendario) se vinculan como puntos extra
    if (!this.propuesta.length && this.delegacion && this.zonas.length) {
      this.extraAledanios = nuevos.map((n) => n.id_punto);
      alert(`✅ Se vincularon ${nuevos.length} punto(s) inactivo(s) a la programación semanal.`);
      this.modalAled = false;
      return;
    }
    // Si hay previsualización, se combinan con sus filas
    this.propuesta.forEach((p) => {
      if (p.id_punto && p.fecha_visita && p.id_tecnico && !nuevos.some((s) => s.id_punto == p.id_punto)) nuevos.push({ id_punto: String(p.id_punto), fecha_visita: p.fecha_visita, id_tecnico: p.id_tecnico });
    });
    this.modalAled = false;
    const r: any = await this.api.postForm('programacionCrear', 'previsualizar_diario', { servicios_diarios: nuevos });
    this.aplicarBase(r);
    this.mostrarResultado(r);
  }
}
