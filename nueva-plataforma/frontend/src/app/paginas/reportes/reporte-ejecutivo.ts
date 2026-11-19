import { Component, ElementRef, OnDestroy, OnInit, ViewChild, inject } from '@angular/core';
import { DecimalPipe, KeyValuePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { Location } from '@angular/common';
import Chart from 'chart.js/auto';
import { Api } from '../../core/api';

/** Port de configurarReporte.php (generarReporte?accion=configurar) */
@Component({
  selector: 'app-generar-reporte',
  imports: [FormsModule],
  styles: [`
    .reporte-container { background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,.15); max-width: 900px; width: 100%; overflow: hidden; margin: 0 auto; }
    .reporte-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
    .reporte-header h1 { font-size: 24px; margin-bottom: 8px; }
    .reporte-header p { opacity: .9; font-size: 14px; }
    .reporte-content { padding: 30px; }
    .reporte-section { margin-bottom: 30px; }
    .reporte-section-title { font-size: 17px; font-weight: 600; color: #2c3e50; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #667eea; }
    .reporte-date-inputs { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
    .reporte-input-group { display: flex; flex-direction: column; }
    .reporte-input-group label { font-size: 14px; font-weight: 500; color: #34495e; margin-bottom: 8px; }
    .reporte-input-group input[type="date"] { padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; transition: all .3s; }
    .reporte-input-group input[type="date"]:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,.1); }
    .reporte-sections-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 15px; }
    .reporte-card { border: 2px solid #e0e0e0; border-radius: 12px; padding: 15px; transition: all .3s; cursor: pointer; position: relative; }
    .reporte-card:hover { border-color: #667eea; background: #f8f9ff; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102,126,234,.15); }
    .reporte-card.selected { border-color: #667eea; background: #f0f3ff; }
    .reporte-card-header { display: flex; align-items: center; margin-bottom: 8px; }
    .reporte-checkbox-custom { width: 22px; height: 22px; border: 2px solid #667eea; border-radius: 6px; margin-right: 12px; display: flex; align-items: center; justify-content: center; transition: all .3s; flex-shrink: 0; }
    .reporte-card.selected .reporte-checkbox-custom { background: #667eea; }
    .reporte-checkbox-custom::after { content: '✓'; color: white; font-size: 14px; font-weight: bold; opacity: 0; transition: opacity .3s; }
    .reporte-card.selected .reporte-checkbox-custom::after { opacity: 1; }
    .reporte-card-title { font-weight: 600; color: #2c3e50; font-size: 14px; flex: 1; }
    .reporte-card-desc { font-size: 12px; color: #7f8c8d; line-height: 1.4; }
    .reporte-select-all-btn { background: #ecf0f1; border: none; padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; color: #34495e; cursor: pointer; transition: all .3s; margin-bottom: 15px; }
    .reporte-select-all-btn:hover { background: #d5dbdb; }
    .reporte-actions { display: flex; gap: 15px; margin-top: 30px; padding-top: 20px; border-top: 2px solid #ecf0f1; }
    .reporte-btn { flex: 1; padding: 14px; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all .3s; display: flex; align-items: center; justify-content: center; gap: 8px; }
    .reporte-btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; box-shadow: 0 4px 15px rgba(102,126,234,.4); }
    .reporte-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102,126,234,.5); }
    .reporte-btn-secondary { background: #ecf0f1; color: #34495e; }
    .reporte-btn-secondary:hover { background: #d5dbdb; }
    .reporte-alert { background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; }
  `],
  template: `
    <div class="reporte-container">
      <div class="reporte-header">
        <h1>📊 Configurar Reporte Ejecutivo</h1>
        <p>Selecciona el período y las secciones que deseas incluir</p>
      </div>
      <div class="reporte-content">
        <form (ngSubmit)="generar()">
          <div class="reporte-section">
            <div class="reporte-section-title">📅 Período del Reporte</div>
            <div class="reporte-date-inputs">
              <div class="reporte-input-group"><label>Fecha Inicio</label><input type="date" name="inicio" [(ngModel)]="inicio" required></div>
              <div class="reporte-input-group"><label>Fecha Fin</label><input type="date" name="fin" [(ngModel)]="fin" required></div>
            </div>
          </div>
          @if (alerta) {
            <div class="reporte-alert">⚠️ Por favor selecciona al menos una sección para generar el reporte</div>
          }
          <div class="reporte-section">
            <div class="reporte-section-title">📋 Secciones del Reporte</div>
            <button type="button" class="reporte-select-all-btn" (click)="toggleTodo()">✓ Seleccionar / Deseleccionar Todo</button>
            <div class="reporte-sections-grid">
              @for (s of secciones; track s.valor) {
                <div class="reporte-card" [class.selected]="s.sel" (click)="s.sel = !s.sel">
                  <div class="reporte-card-header">
                    <div class="reporte-checkbox-custom"></div>
                    <div class="reporte-card-title">{{ s.titulo }}</div>
                  </div>
                  <div class="reporte-card-desc">
                    {{ s.desc }}
                    @if (s.valor === 'puntos_fallidos') {
                      <div style="margin-top: 10px; padding-top: 8px; border-top: 1px dashed #ccc; display: flex; align-items: center; gap: 5px;">
                        <label style="font-size: 11px; font-weight: bold; color: #555;">Mín. Visitas:</label>
                        <input type="number" name="min_visitas" [(ngModel)]="minVisitas" min="1" style="width: 50px; padding: 2px 5px; border: 1px solid #999; border-radius: 4px; font-size: 12px;" (click)="$event.stopPropagation()">
                      </div>
                    }
                  </div>
                </div>
              }
            </div>
          </div>
          <div class="reporte-actions">
            <button type="button" class="reporte-btn reporte-btn-secondary" (click)="volver()">← Cancelar</button>
            <button type="submit" class="reporte-btn reporte-btn-primary">📄 Generar Reporte PDF</button>
          </div>
        </form>
      </div>
    </div>
  `
})
export class GenerarReporte implements OnInit {
  private api = inject(Api);
  private ruta = inject(ActivatedRoute);
  private location = inject(Location);
  inicio = '';
  fin = '';
  minVisitas: any = 2;
  alerta = false;
  secciones = [
    { valor: 'portada', titulo: '📄 Portada', desc: 'Resumen ejecutivo con KPIs principales' },
    { valor: 'delegaciones', titulo: '🏢 Delegaciones', desc: 'Top delegaciones intervenidas' },
    { valor: 'tendencias', titulo: '📈 Tendencias', desc: 'Evolución diaria de servicios' },
    { valor: 'mantenimiento', titulo: '🔧 Mantenimiento', desc: 'Tipos de servicio y estados finales' },
    { valor: 'maquinas', titulo: '🏧 Tipos de Máquinas', desc: 'Tipos de máquina por delegación' },
    { valor: 'estados', titulo: '✅ Estados Finales', desc: 'Gráfica de operatividad y fallas' },
    { valor: 'puntos_atendidos', titulo: '📍 Cobertura Puntos', desc: 'Matriz de puntos únicos atendidos' },
    { valor: 'puntos_fallidos', titulo: '⚠️ Puntos Más Visitados', desc: 'Puntos con mayor frecuencia de servicios.' },
    { valor: 'repuestos', titulo: '🔩 Repuestos', desc: 'Gestión y origen de repuestos' },
    { valor: 'calificaciones', titulo: '⭐ Calificaciones', desc: 'Satisfacción del cliente' },
    { valor: 'puntos_mas_fallidos', titulo: '⚠️ Puntos Fallidos', desc: 'Puntos con servicios fallidos (2+ fallas)' },
    { valor: 'novedades', titulo: '🏷️ Principales Novedades', desc: 'Top 6 de novedades por delegación en servicios no fallidos' },
    { valor: 'novedades_fallidas', titulo: '⚠️ Novedades Fallidas', desc: 'Top 6 de novedades por delegación en servicios fallidos' },
    { valor: 'clientes_novedades', titulo: '👥 Clientes con Más Novedades', desc: 'Top 3 de clientes por delegación y sus novedades principales' },
    { valor: 'tecnicos', titulo: '👷 Técnicos', desc: 'Productividad del equipo técnico' },
    { valor: 'costos', titulo: '🪙 Costos Operación', desc: 'Costos Operación Motorizados' },
    { valor: 'balance', titulo: '💵 Balance Ingresos, Egresos', desc: 'Resta de Egresos e Ingresos' }
  ].map((s) => ({ ...s, sel: true }));

  ngOnInit() {
    const q = this.ruta.snapshot.queryParamMap;
    if (q.get('inicio') && q.get('fin')) {
      this.inicio = q.get('inicio')!;
      this.fin = q.get('fin')!;
    } else {
      const hoy = new Date();
      const f = (d: Date) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
      this.inicio = f(new Date(hoy.getFullYear(), hoy.getMonth(), 1));
      this.fin = f(new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0));
    }
  }

  toggleTodo() {
    const todas = this.secciones.every((s) => s.sel);
    this.secciones.forEach((s) => (s.sel = !todas));
  }

  volver() {
    this.location.back();
  }

  generar() {
    const sel = this.secciones.filter((s) => s.sel).map((s) => s.valor);
    if (!sel.length) {
      this.alerta = true;
      setTimeout(() => (this.alerta = false), 3000);
      return;
    }
    this.api.abrir('generarReporte', 'generar', { inicio: this.inicio, fin: this.fin, secciones: sel, min_visitas: this.minVisitas });
  }
}

/** Port de reporteEjecutivoVista.php (Tablero Ejecutivo de Servicios) */
@Component({
  selector: 'app-reporte-ejecutivo',
  imports: [FormsModule, RouterLink, DecimalPipe, KeyValuePipe],
  template: `
    <div class="w-full px-4 md:px-6">
      <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-8">
        <div class="mb-6 border-b pb-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-chart-line text-blue-600 mr-2"></i> Reporte Ejecutivo Operativo</h1>
            <p class="text-gray-500 text-sm">Dashboard sincronizado con Reporte PDF (Fallidos &amp; KPIs).</p>
          </div>
          @if (d?.datosDia?.length) {
            <a routerLink="/generarReporte" [queryParams]="{ accion: 'configurar', inicio: f.fecha_inicio, fin: f.fecha_fin }"
              class="bg-gray-800 text-white px-5 py-2 rounded-lg font-bold hover:bg-gray-900 shadow flex items-center gap-2 transform hover:scale-105 transition text-sm">
              <i class="fas fa-file-pdf"></i> Generar PDF
            </a>
          }
        </div>
        <form (ngSubmit)="buscar()" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
          <div class="hidden md:block"></div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Desde</label>
            <input type="date" name="fecha_inicio" [(ngModel)]="f.fecha_inicio" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-gray-700">
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Hasta</label>
            <input type="date" name="fecha_fin" [(ngModel)]="f.fecha_fin" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-gray-700">
          </div>
          <div>
            <button type="submit" class="w-full py-2 px-4 bg-blue-600 text-white font-bold rounded-lg shadow hover:bg-blue-700 transition-all"><i class="fas fa-filter mr-2"></i> Actualizar</button>
          </div>
        </form>
      </div>

      @if (d?.mensaje) {
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded shadow-sm mb-6">
          <p class="text-sm text-yellow-700"><i class="fas fa-info-circle mr-1"></i> {{ d.mensaje }}</p>
        </div>
      }

      @if (d?.datosDia?.length) {
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          <div class="bg-white p-4 rounded-xl shadow border-l-4 border-blue-500 flex flex-col items-center justify-center">
            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total Servicios</p>
            <p class="text-3xl font-extrabold text-blue-600 mt-1">{{ d.totalServicios | number: '1.0-0' : 'en-US' }}</p>
          </div>
          <div class="bg-white p-4 rounded-xl shadow border-l-4 border-emerald-500 flex flex-col items-center justify-center">
            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Servicios Esperados (Meta)</p>
            <p class="text-3xl font-extrabold text-emerald-600 mt-1">{{ (d.metaServicios?.esperados || 0) | number: '1.0-0' : 'en-US' }}</p>
            <p class="text-[10px] text-gray-500 mt-1">
              Realizados: <span class="font-bold text-gray-700">{{ d.totalServicios | number: '1.0-0' : 'en-US' }}</span>
              · Cumplimiento: <span class="font-bold" [class]="d.cumplimientoMeta >= 100 ? 'text-emerald-600' : d.cumplimientoMeta >= 80 ? 'text-amber-600' : 'text-rose-600'">{{ d.cumplimientoMeta | number: '1.1-1' : 'en-US' }}%</span>
            </p>
          </div>
          <div class="bg-white p-4 rounded-xl shadow border-l-4 border-red-500 flex flex-col items-center justify-center">
            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider"> Servicios Con Novedad</p>
            <p class="text-3xl font-extrabold text-red-600 mt-1">{{ (d.datosNovedad?.con_novedad || 0) | number: '1.0-0' : 'en-US' }}</p>
          </div>
          <div class="bg-white p-4 rounded-xl shadow border-l-4 border-rose-600 flex flex-col items-center justify-center">
            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Puntos Críticos (&gt;2 Fallos)</p>
            <p class="text-3xl font-extrabold text-rose-600 mt-1">{{ d.datosPuntosCriticos.length }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
          <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100">
            <h3 class="text-gray-700 font-bold mb-4 border-b pb-2">Evolución Diaria de Servicios</h3>
            <div class="relative h-64 w-full"><canvas #chartDias></canvas></div>
          </div>
          <div class="bg-white p-5 rounded-xl shadow-md border border-rose-100">
            <div class="flex justify-between items-center mb-4 border-b border-rose-100 pb-2">
              <h3 class="text-rose-700 font-bold"><i class="fas fa-exclamation-triangle mr-1"></i> Top Puntos Críticos</h3>
              <span class="text-[10px] bg-rose-100 text-rose-600 px-2 py-1 rounded-full font-bold">Tipo: Fallido (&gt; 2)</span>
            </div>
            <div class="relative h-64 w-full"><canvas #chartPuntosCriticos></canvas></div>
          </div>
          <div class="bg-white p-5 rounded-xl shadow-md border border-sky-100 lg:col-span-2">
            <div class="flex justify-between items-start mb-4 border-b border-sky-100 pb-2 gap-3">
              <div>
                <h3 class="text-sky-700 font-bold"><i class="fas fa-location-dot mr-1"></i> Puntos Más Visitados</h3>
                <p class="text-[11px] text-gray-400">Top de puntos por frecuencia de visitas en el período</p>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-[10px] bg-sky-50 text-sky-600 px-2 py-1 rounded-full font-bold whitespace-nowrap">Top 20</span>
                <a [href]="urlExcel('puntos')" class="bg-green-600 text-white px-3 py-1.5 rounded-lg font-bold hover:bg-green-700 shadow flex items-center gap-1.5 text-[11px]"><i class="fas fa-file-excel"></i> Excel</a>
              </div>
            </div>
            <div class="relative h-80 w-full"><canvas #chartPuntosVisitados></canvas></div>
          </div>
          <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100">
            <h3 class="text-gray-700 font-bold mb-4 border-b pb-2">Distribución por Tipo de Servicio</h3>
            <div class="relative h-64 w-full flex justify-center"><canvas #chartTipo></canvas></div>
          </div>
          <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100">
            <h3 class="text-gray-700 font-bold mb-4 border-b pb-2">Participación por Delegación</h3>
            <div class="relative h-64 w-full"><canvas #chartDelegacion></canvas></div>
          </div>
          <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100 lg:col-span-2">
            <h3 class="text-gray-700 font-bold mb-4 border-b pb-2">Productividad por Técnico (Horas vs Servicios)</h3>
            <div class="relative h-80 w-full"><canvas #chartTecnico></canvas></div>
          </div>
          <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100 lg:col-span-2">
            <h3 class="text-gray-700 font-bold mb-4 border-b pb-2">Gestión de Repuestos (Origen)</h3>
            <div class="relative h-64 w-full flex justify-center"><canvas #chartRepuestos></canvas></div>
          </div>
          <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100 lg:col-span-2">
            <div class="flex justify-between items-start mb-4 border-b pb-2 gap-3">
              <div>
                <h3 class="text-gray-700 font-bold"><i class="fas fa-tags text-indigo-500 mr-1"></i> Principales Novedades por Delegación</h3>
                <p class="text-[11px] text-gray-400">Top 3 de novedades en servicios no fallidos</p>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-[10px] bg-indigo-50 text-indigo-600 px-2 py-1 rounded-full font-bold whitespace-nowrap">Sin fallidos</span>
                <a [href]="urlExcel('novedades')" class="bg-green-600 text-white px-3 py-1.5 rounded-lg font-bold hover:bg-green-700 shadow flex items-center gap-1.5 text-[11px]"><i class="fas fa-file-excel"></i> Excel</a>
              </div>
            </div>
            <div class="relative h-96 w-full"><canvas #chartNovedadesDelegacion></canvas></div>
          </div>

          <div class="bg-white p-5 rounded-xl shadow-md border border-amber-100 lg:col-span-2">
            <div class="flex justify-between items-start mb-4 border-b border-amber-100 pb-2 gap-3">
              <div>
                <h3 class="text-amber-700 font-bold"><i class="fas fa-users text-amber-500 mr-1"></i> Clientes con Más Novedades por Delegación</h3>
                <p class="text-[11px] text-gray-400">Top 3 de clientes por delegación y sus 3 novedades más frecuentes</p>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-[10px] bg-amber-50 text-amber-600 px-2 py-1 rounded-full font-bold whitespace-nowrap">Top 3</span>
                <a [href]="urlExcel('clientes')" class="bg-green-600 text-white px-3 py-1.5 rounded-lg font-bold hover:bg-green-700 shadow flex items-center gap-1.5 text-[11px]"><i class="fas fa-file-excel"></i> Excel</a>
              </div>
            </div>
            @if (vacio(d.clientesNovedades)) {
              <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-8 text-center">
                <p class="text-sm font-bold text-emerald-700">No hay novedades registradas por cliente en el período.</p>
              </div>
            } @else {
              <div class="relative h-[520px] w-full mb-6"><canvas #chartClientesNovedades></canvas></div>
              <div class="overflow-x-auto max-h-96 overflow-y-auto">
                <table class="w-full text-sm text-left">
                  <thead class="bg-amber-50 text-amber-700 sticky top-0">
                    <tr>
                      <th class="px-3 py-2 text-xs font-bold uppercase tracking-wider w-36">Delegación</th>
                      <th class="px-3 py-2 text-xs font-bold uppercase tracking-wider">Cliente</th>
                      <th class="px-3 py-2 text-xs font-bold uppercase tracking-wider text-center w-24">Servicios</th>
                      <th class="px-3 py-2 text-xs font-bold uppercase tracking-wider">Top 3 novedades</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100">
                    @for (g of d.clientesNovedades | keyvalue: sinOrden; track g.key) {
                      @for (cli of $any(g.value); track $index; let i = $index) {
                        <tr class="hover:bg-amber-50/40 cursor-pointer" (mouseenter)="popCliente($event, g.key, cli, '')" (mouseleave)="ocultarPopover()" (click)="detalleCliente($event, g.key, cli, '')">
                          @if (i === 0) {
                            <td class="px-3 py-2 align-top font-bold text-gray-700" [attr.rowspan]="$any(g.value).length">{{ g.key }}</td>
                          }
                          <td class="px-3 py-2 align-top font-semibold text-gray-800">{{ cli.cliente }}</td>
                          <td class="px-3 py-2 align-top text-center"><span class="inline-flex items-center justify-center rounded-full bg-amber-600 px-2.5 py-0.5 text-xs font-bold text-white">{{ cli.total }}</span></td>
                          <td class="px-3 py-2 align-top">
                            <div class="flex flex-wrap gap-1.5">
                              @for (nov of cli.novedades; track $index) {
                                <span class="inline-flex items-center gap-1 rounded-full border border-amber-100 bg-amber-50 px-2 py-1 text-[11px] text-amber-800 cursor-pointer hover:bg-amber-100"
                                  (mouseenter)="popCliente($event, g.key, cli, nov.nombre)" (mouseleave)="ocultarPopover()" (click)="detalleCliente($event, g.key, cli, nov.nombre)">
                                  <span>{{ nov.nombre }}</span>
                                  <span class="rounded-full bg-amber-600 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ nov.total }}</span>
                                </span>
                              }
                            </div>
                          </td>
                        </tr>
                      }
                    }
                  </tbody>
                </table>
              </div>
            }
          </div>

          <div class="bg-white p-5 rounded-xl shadow-md border border-rose-100 lg:col-span-2">
            <div class="flex justify-between items-start mb-4 border-b border-rose-100 pb-2 gap-3">
              <div>
                <h3 class="text-rose-700 font-bold"><i class="fas fa-exclamation-circle mr-1"></i> Novedades en Servicios Fallidos</h3>
                <p class="text-[11px] text-gray-400">Top 6 de novedades por delegación en servicios fallidos</p>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-[10px] bg-rose-50 text-rose-600 px-2 py-1 rounded-full font-bold whitespace-nowrap">Solo fallidos</span>
                <a [href]="urlExcel('novedades_fallidas')" class="bg-green-600 text-white px-3 py-1.5 rounded-lg font-bold hover:bg-green-700 shadow flex items-center gap-1.5 text-[11px]"><i class="fas fa-file-excel"></i> Excel</a>
              </div>
            </div>
            @if (vacio(d.datosNovedadesFallidasPorDelegacion)) {
              <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-8 text-center">
                <p class="text-sm font-bold text-emerald-700">No hay novedades registradas en servicios fallidos para el período.</p>
              </div>
            } @else {
              <div class="overflow-x-auto max-h-96 overflow-y-auto">
                <table class="w-full text-sm text-left">
                  <thead class="bg-rose-50 text-rose-700 sticky top-0">
                    <tr>
                      <th class="px-3 py-2 text-xs font-bold uppercase tracking-wider w-40">Delegación</th>
                      <th class="px-3 py-2 text-xs font-bold uppercase tracking-wider">Principales novedades</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100">
                    @for (g of d.datosNovedadesFallidasPorDelegacion | keyvalue: sinOrden; track g.key) {
                      <tr class="hover:bg-rose-50/40">
                        <td class="px-3 py-2 align-top font-bold text-gray-700">{{ g.key }}</td>
                        <td class="px-3 py-2">
                          <div class="flex flex-wrap gap-1.5">
                            @for (item of $any(g.value); track $index) {
                              <span class="inline-flex items-center gap-1 rounded-full border border-rose-100 bg-rose-50 px-2 py-1 text-[11px] text-rose-700 cursor-pointer hover:bg-rose-100"
                                (mouseenter)="popFallida($event, g.key, item.nombre)" (mouseleave)="ocultarPopover()" (click)="detalleFallida($event, g.key, item.nombre)">
                                <span>{{ item.nombre }}</span>
                                <span class="rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ item.total }}</span>
                              </span>
                            }
                          </div>
                        </td>
                      </tr>
                    }
                  </tbody>
                </table>
              </div>
            }
          </div>
        </div>
      }
    </div>

    @if (pop) {
      <div class="fixed z-[99998] w-80 max-w-sm rounded-xl border border-gray-200 bg-white p-3 shadow-2xl" [style.left.px]="pop.x" [style.top.px]="pop.y" #popEl>
        <div class="font-bold text-xs text-gray-800 mb-1">{{ pop.titulo }}</div>
        @if (!pop.filas.length) {
          <p class="text-xs text-gray-500">Sin detalle disponible.</p>
        }
        @for (fl of pop.filas.slice(0, 6); track $index) {
          <div class="py-1.5 border-b border-gray-100 last:border-0">
            <div class="text-[11px] font-bold text-gray-700">{{ fl.fecha }} · {{ fl.cliente }}</div>
            <div class="text-[11px] text-gray-500">{{ fl.punto }} · {{ fl.tecnico }} · {{ fl.tipo }}</div>
            @if (fl.novedades) {
              <div class="text-[11px] text-rose-600">Novedad: {{ fl.novedades }}</div>
            }
          </div>
        }
        <div class="mt-2 text-[10px] text-gray-400">Clic para ver el detalle completo y descargar Excel</div>
      </div>
    }

    @if (modal) {
      <div class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 p-4" (click)="modal = null">
        <div class="flex max-h-[85vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl" (click)="$event.stopPropagation()">
          <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4">
            <div>
              <h3 class="text-lg font-bold text-gray-800">{{ modal.titulo }}</h3>
              <p class="text-xs text-gray-500">{{ modal.subtitulo }}</p>
            </div>
            <button type="button" (click)="modal = null" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700"><i class="fas fa-times"></i></button>
          </div>
          <div class="flex-1 overflow-auto p-5">
            @if (!modal.filas.length) {
              <p class="text-sm text-gray-500 py-6 text-center">No hay detalle de servicios para este elemento.</p>
            } @else {
              <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                  <thead class="bg-gray-100 text-gray-600 uppercase tracking-wider">
                    <tr>
                      <th class="px-3 py-2">Fecha</th><th class="px-3 py-2">Cliente</th><th class="px-3 py-2">Punto</th>
                      <th class="px-3 py-2">Técnico</th><th class="px-3 py-2">Tipo servicio</th><th class="px-3 py-2">Novedades</th>
                    </tr>
                  </thead>
                  <tbody>
                    @for (fl of modal.filas; track $index) {
                      <tr class="border-b border-gray-100">
                        <td class="px-3 py-2 whitespace-nowrap text-gray-700">{{ fl.fecha }}</td>
                        <td class="px-3 py-2 text-gray-800 font-semibold">{{ fl.cliente }}</td>
                        <td class="px-3 py-2 text-gray-600">{{ fl.punto }}</td>
                        <td class="px-3 py-2 text-gray-600">{{ fl.tecnico }}</td>
                        <td class="px-3 py-2 text-gray-600">{{ fl.tipo }}</td>
                        <td class="px-3 py-2 text-rose-700">{{ fl.novedades }}</td>
                      </tr>
                    }
                  </tbody>
                </table>
              </div>
            }
          </div>
          <div class="flex flex-col gap-3 border-t border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-[11px] text-gray-500">{{ modal.nota }}</p>
            @if (modal.excel) {
              <a [href]="modal.excel" class="inline-flex items-center justify-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-bold text-white shadow hover:bg-green-700"><i class="fas fa-file-excel"></i> Descargar Excel de este detalle</a>
            }
          </div>
        </div>
      </div>
    }
  `
})
export class ReporteEjecutivo implements OnInit, OnDestroy {
  private api = inject(Api);
  private host = inject(ElementRef);
  d: any = null;
  f: any = { fecha_inicio: '', fecha_fin: '' };
  pop: { x: number; y: number; titulo: string; filas: any[] } | null = null;
  modal: { titulo: string; subtitulo: string; filas: any[]; nota: string; excel: string } | null = null;
  private charts: Chart[] = [];
  sinOrden = () => 0;

  @ViewChild('chartDias') cDias?: ElementRef<HTMLCanvasElement>;
  @ViewChild('chartPuntosCriticos') cCriticos?: ElementRef<HTMLCanvasElement>;
  @ViewChild('chartPuntosVisitados') cVisitados?: ElementRef<HTMLCanvasElement>;
  @ViewChild('chartTipo') cTipo?: ElementRef<HTMLCanvasElement>;
  @ViewChild('chartDelegacion') cDelegacion?: ElementRef<HTMLCanvasElement>;
  @ViewChild('chartTecnico') cTecnico?: ElementRef<HTMLCanvasElement>;
  @ViewChild('chartRepuestos') cRepuestos?: ElementRef<HTMLCanvasElement>;
  @ViewChild('chartNovedadesDelegacion') cNovedades?: ElementRef<HTMLCanvasElement>;
  @ViewChild('chartClientesNovedades') cClientes?: ElementRef<HTMLCanvasElement>;

  async ngOnInit() {
    Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
    Chart.defaults.color = '#64748b';
    this.aplicar(await this.api.get('reporteEjecutivo'));
  }

  ngOnDestroy() {
    this.destruir();
  }

  async buscar() {
    this.aplicar(await this.api.postForm('reporteEjecutivo', 'index', this.f));
  }

  private aplicar(r: any) {
    this.destruir();
    this.d = r;
    this.f = { ...r.filtros };
    setTimeout(() => this.dibujar());
  }

  private destruir() {
    this.charts.forEach((c) => c.destroy());
    this.charts = [];
  }

  vacio(o: any) {
    return !o || (Array.isArray(o) ? o.length === 0 : Object.keys(o).length === 0);
  }

  urlExcel(tipo: string, extra: Record<string, any> = {}) {
    const p: Record<string, any> = { tipo, fecha_inicio: this.d?.filtros?.fecha_inicio ?? '', fecha_fin: this.d?.filtros?.fecha_fin ?? '' };
    Object.entries(extra).forEach(([k, v]) => {
      if (v !== undefined && v !== null && String(v) !== '') p[k] = v;
    });
    return this.api.url('reporteEjecutivo', 'descargarExcel', p);
  }

  private abrirModal(titulo: string, subtitulo: string, filas: any[], excel: string) {
    this.pop = null;
    this.modal = { titulo, subtitulo, filas, nota: filas.length >= 6 ? 'Mostrando 6 servicios. Descarga el Excel para ver todos.' : '', excel };
  }

  private mostrarPopover(ev: MouseEvent, titulo: string, filas: any[]) {
    const margen = 14;
    const ancho = 320;
    const alto = 60 + Math.min(filas.length, 6) * 52;
    let x = ev.clientX + margen;
    let y = ev.clientY + margen;
    if (x + ancho > window.innerWidth - margen) x = ev.clientX - ancho - margen;
    if (y + alto > window.innerHeight - margen) y = ev.clientY - alto - margen;
    this.pop = { x: Math.max(margen, x), y: Math.max(margen, y), titulo, filas };
  }

  ocultarPopover() {
    this.pop = null;
  }

  private filasCliente(del: string, cli: any, novedad: string) {
    return novedad ? this.d.detalleClienteNovedadHover?.[del + '||' + cli.id_cliente + '||' + novedad] || [] : this.d.detalleClientesHover?.[del + '||' + cli.id_cliente] || [];
  }

  popCliente(ev: MouseEvent, del: any, cli: any, novedad: string) {
    ev.stopPropagation();
    this.mostrarPopover(ev, (cli.cliente || 'Cliente') + (novedad ? ' · ' + novedad : ''), this.filasCliente(del, cli, novedad));
  }

  detalleCliente(ev: MouseEvent, del: any, cli: any, novedad: string) {
    ev.stopPropagation();
    const p: any = { delegacion: del, id_cliente: cli.id_cliente };
    if (novedad) p.novedad = novedad;
    this.abrirModal('Cliente con Novedades', `${cli.cliente} · ${del}${novedad ? ' · ' + novedad : ''}`, this.filasCliente(del, cli, novedad), this.urlExcel('clientes', p));
  }

  popFallida(ev: MouseEvent, del: any, novedad: string) {
    ev.stopPropagation();
    this.mostrarPopover(ev, `${del} · ${novedad}`, this.d.detalleNovedadesFallidasHover?.[del + '||' + novedad] || []);
  }

  detalleFallida(ev: MouseEvent, del: any, novedad: string) {
    ev.stopPropagation();
    this.abrirModal('Novedades en Servicios Fallidos', `${del} · ${novedad}`, this.d.detalleNovedadesFallidasHover?.[del + '||' + novedad] || [],
      this.urlExcel('novedades_fallidas', { delegacion: del, novedad }));
  }

  private sinDatos(c: ElementRef<HTMLCanvasElement> | undefined, texto: string) {
    if (!c) return;
    const ctx = c.nativeElement.getContext('2d')!;
    ctx.font = '14px Arial';
    ctx.fillStyle = '#9ca3af';
    ctx.textAlign = 'center';
    ctx.fillText(texto, ctx.canvas.width / 2, ctx.canvas.height / 2);
  }

  private nuevo(c: ElementRef<HTMLCanvasElement> | undefined, cfg: any) {
    if (c) this.charts.push(new Chart(c.nativeElement, cfg));
  }

  private dibujar() {
    const d = this.d;
    if (!d?.datosDia?.length) return;
    const recortar = (max: number, corte: number) =>
      function (this: any, value: any) {
        const t = this.getLabelForValue(value);
        return t.length > max ? t.slice(0, corte) + '…' : t;
      };

    // A. Días
    this.nuevo(this.cDias, {
      type: 'line',
      data: {
        labels: d.datosDia.map((i: any) => i.fecha_visita),
        datasets: [{ label: 'Servicios', data: d.datosDia.map((i: any) => i.total), borderColor: '#3b82f6', backgroundColor: 'rgba(59, 130, 246, 0.1)', fill: true, tension: 0.3, borderWidth: 2, pointRadius: 3 }]
      },
      options: { maintainAspectRatio: false, responsive: true }
    });

    // B. Puntos críticos (top 10)
    const criticos = (d.datosPuntosCriticos || []).slice(0, 10);
    if (criticos.length) {
      this.nuevo(this.cCriticos, {
        type: 'bar',
        data: { labels: criticos.map((i: any) => i.nombre_punto), datasets: [{ label: 'Cant. Fallidos', data: criticos.map((i: any) => i.total_fallidos), backgroundColor: '#f43f5e', borderRadius: 4 }] },
        options: { indexAxis: 'y', maintainAspectRatio: false, responsive: true, scales: { x: { beginAtZero: true, grid: { display: true } }, y: { grid: { display: false } } }, plugins: { legend: { display: false } } }
      });
    } else this.sinDatos(this.cCriticos, '¡Excelente! No hay puntos críticos (> 2 fallos)');

    // B2. Puntos más visitados
    const pv = d.puntosVisitados || [];
    if (pv.length) {
      const ids = pv.map((p: any) => String(p.id_punto));
      this.nuevo(this.cVisitados, {
        type: 'bar',
        data: { labels: pv.map((p: any) => `${p.nombre_punto} · ${p.nombre_delegacion}`), datasets: [{ label: 'Visitas', data: pv.map((p: any) => Number(p.total_visitas) || 0), backgroundColor: '#0ea5e9', borderRadius: 4, maxBarThickness: 22 }] },
        options: {
          indexAxis: 'y', maintainAspectRatio: false, responsive: true,
          scales: { x: { beginAtZero: true, ticks: { precision: 0 }, grid: { display: true } }, y: { grid: { display: false }, ticks: { autoSkip: false, font: { size: 10 }, callback: recortar(42, 40) } } },
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: (ctx: any) => `Visitas: ${ctx.raw}`,
                afterLabel: (ctx: any) => {
                  const filas = d.detallePuntosHover?.[ids[ctx.dataIndex]] || [];
                  return filas.length ? ['Servicios:', ...filas.slice(0, 6).map((f: any) => `• ${f.fecha} · ${f.cliente} · ${f.tecnico} · ${f.tipo}`)] : '';
                }
              }
            }
          },
          onClick: (_e: any, el: any[]) => {
            if (!el.length) return;
            const p = pv[el[0].index];
            if (!p) return;
            this.abrirModal('Puntos Más Visitados', `${p.nombre_punto} · ${p.nombre_delegacion} (${p.total_visitas} visitas)`, d.detallePuntosHover?.[String(p.id_punto)] || [],
              this.urlExcel('puntos', { id_punto: p.id_punto, delegacion: p.nombre_delegacion }));
          }
        }
      });
    } else this.sinDatos(this.cVisitados, 'Sin puntos visitados en el período');

    // C. Tipo
    this.nuevo(this.cTipo, {
      type: 'doughnut',
      data: { labels: d.datosTipo.map((i: any) => i.tipo), datasets: [{ data: d.datosTipo.map((i: any) => i.total), backgroundColor: ['#ef4444', '#fff000', '#f59e0b', '#3b82f6', '#10b981'], borderWidth: 0 }] },
      options: { maintainAspectRatio: false, responsive: true }
    });

    // D. Delegaciones
    this.nuevo(this.cDelegacion, {
      type: 'bar',
      data: { labels: d.datosDelegacion.map((i: any) => i.nombre_delegacion), datasets: [{ label: 'Intervenciones', data: d.datosDelegacion.map((i: any) => i.total), backgroundColor: '#8b5cf6', borderRadius: 4 }] },
      options: { maintainAspectRatio: false, responsive: true, scales: { y: { beginAtZero: true } } }
    });

    // E. Técnicos
    this.nuevo(this.cTecnico, {
      type: 'bar',
      data: {
        labels: d.datosHoras.map((i: any) => i.nombre_tecnico),
        datasets: [
          { label: 'Servicios', data: d.datosHoras.map((i: any) => i.total_servicios), backgroundColor: '#334155', borderRadius: 4, order: 2 },
          { label: 'Horas', data: d.datosHoras.map((i: any) => i.total_horas), type: 'line', borderColor: '#fbbf24', borderWidth: 2, order: 1, yAxisID: 'y1' }
        ]
      },
      options: { maintainAspectRatio: false, responsive: true, scales: { y: { beginAtZero: true }, y1: { position: 'right', grid: { drawOnChartArea: false } } } }
    });

    // F. Repuestos
    const labelsRep = d.datosRepuestos.map((i: any) => String(i.origen ?? '').toUpperCase());
    this.nuevo(this.cRepuestos, {
      type: 'pie',
      data: { labels: labelsRep, datasets: [{ data: d.datosRepuestos.map((i: any) => i.total), backgroundColor: labelsRep.map((l: string) => (l.includes('INEES') ? '#f97316' : '#3b82f6')), borderWidth: 2, borderColor: '#ffffff' }] },
      options: { maintainAspectRatio: false, responsive: true }
    });

    // G. Novedades por delegación
    const nd = d.datosNovedadesPorDelegacion || {};
    const top: string[] = d.topNovedadesGlobales || [];
    const entradas = Object.keys(nd)
      .map((del) => ({ del, total: (nd[del] || []).reduce((s: number, it: any) => s + (Number(it.total) || 0), 0) }))
      .sort((a, b) => b.total - a.total)
      .slice(0, 12);
    const labelsNov = entradas.map((e) => e.del);
    const colores = ['#4f46e5', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#94a3b8'];
    const datasets: any[] = top.map((nombre, i) => ({
      label: nombre,
      data: entradas.map((e) => {
        const x = (nd[e.del] || []).find((it: any) => it.nombre === nombre);
        return x ? Number(x.total) : 0;
      }),
      backgroundColor: colores[i % colores.length], borderRadius: 2, stack: 'novedades'
    }));
    const otras = entradas.map((e) => (nd[e.del] || []).filter((it: any) => !top.includes(it.nombre)).reduce((s: number, it: any) => s + (Number(it.total) || 0), 0));
    if (otras.some((v) => v > 0)) datasets.push({ label: 'Otras', data: otras, backgroundColor: '#cbd5e1', borderRadius: 2, stack: 'novedades' });
    if (labelsNov.length && datasets.length) {
      this.nuevo(this.cNovedades, {
        type: 'bar',
        data: { labels: labelsNov, datasets },
        options: {
          indexAxis: 'y', maintainAspectRatio: false, responsive: true,
          scales: { x: { stacked: true, beginAtZero: true, grid: { display: true } }, y: { stacked: true, grid: { display: false } } },
          plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } },
            tooltip: {
              callbacks: {
                afterLabel: (ctx: any) => {
                  const del = labelsNov[ctx.dataIndex] || '';
                  const nov = ctx.dataset.label || '';
                  if (nov === 'Otras') return ['Varias novedades agrupadas. Descarga el Excel para ver el detalle.'];
                  const filas = d.detalleNovedadesHover?.[del + '||' + nov] || [];
                  return filas.length ? ['Servicios:', ...filas.slice(0, 6).map((f: any) => `• ${f.fecha} · ${f.cliente} · ${f.tecnico} · ${f.tipo}`)] : '';
                }
              }
            }
          },
          onClick: (_e: any, el: any[]) => {
            if (!el.length) return;
            const del = labelsNov[el[0].index] || '';
            const ds = datasets[el[0].datasetIndex];
            const nov = ds ? ds.label : '';
            if (!nov || nov === 'Otras') return;
            this.abrirModal('Principales Novedades por Delegación', `${del} · ${nov} (${ds.data[el[0].index] || 0} servicios)`, d.detalleNovedadesHover?.[del + '||' + nov] || [],
              this.urlExcel('novedades', { delegacion: del, novedad: nov }));
          }
        }
      });
    } else this.sinDatos(this.cNovedades, 'Sin novedades registradas en el período');

    // H. Clientes con más novedades
    const cn = d.clientesNovedades || {};
    const paleta = ['#f59e0b', '#ef4444', '#3b82f6', '#10b981', '#8b5cf6', '#0ea5e9', '#f97316', '#14b8a6', '#e11d48', '#64748b'];
    const etiquetas: string[] = [], valores: number[] = [], cols: string[] = [], detNov: string[][] = [], orden: any[] = [];
    Object.keys(cn).forEach((del, idx) => {
      const color = paleta[idx % paleta.length];
      (cn[del] || []).forEach((cli: any) => {
        etiquetas.push(`${cli.cliente} · ${del}`);
        valores.push(Number(cli.total) || 0);
        cols.push(color);
        detNov.push((cli.novedades || []).map((n: any) => `${n.nombre} (${n.total})`));
        orden.push({ del, cli });
      });
    });
    if (etiquetas.length) {
      this.nuevo(this.cClientes, {
        type: 'bar',
        data: { labels: etiquetas, datasets: [{ label: 'Servicios con novedad', data: valores, backgroundColor: cols, borderRadius: 4, maxBarThickness: 22 }] },
        options: {
          indexAxis: 'y', maintainAspectRatio: false, responsive: true,
          scales: { x: { beginAtZero: true, ticks: { precision: 0 }, grid: { display: true } }, y: { grid: { display: false }, ticks: { autoSkip: false, font: { size: 10 }, callback: recortar(40, 38) } } },
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: (ctx: any) => `Servicios con novedad: ${ctx.raw}`,
                afterLabel: (ctx: any) => {
                  const it = orden[ctx.dataIndex];
                  if (!it) return '';
                  const l: string[] = [];
                  const nv = detNov[ctx.dataIndex] || [];
                  if (nv.length) l.push('Novedades: ' + nv.join(' · '));
                  const filas = d.detalleClientesHover?.[`${it.del}||${it.cli.id_cliente}`] || [];
                  if (filas.length) {
                    l.push('Servicios:');
                    filas.slice(0, 6).forEach((f: any) => l.push(`• ${f.fecha} · ${f.punto} · ${f.tecnico} · ${f.tipo}`));
                  }
                  return l;
                }
              }
            }
          },
          onClick: (_e: any, el: any[]) => {
            if (!el.length) return;
            const it = orden[el[0].index];
            if (!it) return;
            this.abrirModal('Clientes con Más Novedades por Delegación', `${it.cli.cliente} · ${it.del} (${it.cli.total} servicios con novedad)`,
              d.detalleClientesHover?.[`${it.del}||${it.cli.id_cliente}`] || [], this.urlExcel('clientes', { delegacion: it.del, id_cliente: it.cli.id_cliente }));
          }
        }
      });
    }
  }
}
