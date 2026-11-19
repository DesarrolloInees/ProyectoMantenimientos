import { Component, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Api } from '../../core/api';
import { fmt } from '../../core/ui';

/** Port de app/views/turno/turnoReportesVista.php */
@Component({
  selector: 'app-turno-reportes',
  imports: [FormsModule],
  template: `
    <div class="p-4 md:p-6 max-w-[1400px] mx-auto space-y-6">
      <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
        <h1 class="text-xl md:text-2xl font-bold text-gray-800"><i class="fas fa-user-clock text-blue-600 mr-2"></i>Reporte de Turnos</h1>
        <p class="text-gray-500 text-xs md:text-sm mt-1">Entradas de turno reportadas por los tÃ©cnicos motorizados, con novedad y ubicaciÃ³n.</p>
      </div>

      <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
        <form (submit)="filtrar($event)" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">
          <div>
            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha inicio</label>
            <input type="date" name="fecha_inicio" [(ngModel)]="fechaInicio" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2 text-sm outline-none focus:border-blue-500" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha fin</label>
            <input type="date" name="fecha_fin" [(ngModel)]="fechaFin" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2 text-sm outline-none focus:border-blue-500" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">TÃ©cnico</label>
            <select name="id_tecnico" [(ngModel)]="idTecnico" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2 text-sm outline-none focus:border-blue-500">
              <option value="">- Todos los tÃ©cnicos -</option>
              @for (t of tecnicos; track t.id_tecnico) {
                <option [value]="t.id_tecnico">{{ t.nombre_tecnico }}</option>
              }
            </select>
          </div>
          <div class="flex flex-wrap gap-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition shadow-sm flex items-center gap-2 text-sm"><i class="fas fa-search"></i> Filtrar</button>
            <button type="button" (click)="limpiar()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded-lg transition shadow-sm flex items-center gap-2 text-sm" title="Limpiar filtros"><i class="fas fa-undo"></i></button>
          </div>
          <div class="flex flex-wrap gap-2 sm:col-span-2 md:col-span-4 justify-end border-t pt-3 mt-1">
            <a [href]="urlExcel" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-lg transition shadow-sm flex items-center gap-2 text-sm"><i class="fas fa-file-excel"></i> Reporte Excel</a>
            <a [href]="urlPdf" target="_blank" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition shadow-sm flex items-center gap-2 text-sm"><i class="fas fa-file-pdf"></i> Reporte PDF</a>
          </div>
        </form>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-blue-600 rounded-xl p-4 text-white shadow-md"><p class="text-blue-100 text-xs font-semibold uppercase">Registros</p><h3 class="text-2xl font-bold">{{ totales.registros || 0 }}</h3></div>
        <div class="bg-amber-500 rounded-xl p-4 text-white shadow-md"><p class="text-amber-100 text-xs font-semibold uppercase">Con novedad</p><h3 class="text-2xl font-bold">{{ totales.novedades || 0 }}</h3></div>
        <div class="bg-emerald-600 rounded-xl p-4 text-white shadow-md"><p class="text-emerald-100 text-xs font-semibold uppercase">Con ubicaciÃ³n</p><h3 class="text-2xl font-bold">{{ totales.ubicacion || 0 }}</h3></div>
        <div class="bg-indigo-600 rounded-xl p-4 text-white shadow-md"><p class="text-indigo-100 text-xs font-semibold uppercase">TÃ©cnicos</p><h3 class="text-2xl font-bold">{{ totales.tecnicos || 0 }}</h3></div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 flex justify-between items-center">
          <h3 class="font-bold text-gray-700 text-sm"><i class="fas fa-list-check text-blue-600 mr-1"></i> Marcaciones del periodo</h3>
          <span class="text-xs text-gray-500">{{ f.fecha(fechaInicioAplicada) }} al {{ f.fecha(fechaFinAplicada) }}</span>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-3 text-left text-xs font-bold text-gray-600 uppercase">TÃ©cnico</th>
                <th class="px-3 py-3 text-left text-xs font-bold text-gray-600 uppercase">Fecha</th>
                <th class="px-3 py-3 text-center text-xs font-bold text-gray-600 uppercase">Hora entrada</th>
                <th class="px-3 py-3 text-left text-xs font-bold text-gray-600 uppercase">Novedad</th>
                <th class="px-3 py-3 text-center text-xs font-bold text-gray-600 uppercase">Registrado el</th>
                <th class="px-3 py-3 text-center text-xs font-bold text-gray-600 uppercase">UbicaciÃ³n</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              @for (r of registros; track r.id_turno) {
                <tr class="hover:bg-blue-50/50 transition">
                  <td class="px-3 py-2.5 font-semibold text-gray-800">
                    {{ r.nombre_tecnico }}
                    @if (r.codigo_ruta) { <span class="block text-[10px] text-gray-400 font-normal">Ruta {{ r.codigo_ruta }}</span> }
                  </td>
                  <td class="px-3 py-2.5 text-gray-600">{{ f.fecha(r.fecha) }}<span class="block text-[10px] text-gray-400">{{ dia(r.fecha) }}</span></td>
                  <td class="px-3 py-2.5 text-center"><span class="font-bold text-blue-700">{{ f.hora(r.hora_entrada) }}</span></td>
                  <td class="px-3 py-2.5 text-gray-700 max-w-md whitespace-pre-line">
                    @if (r.novedad !== null && (r.novedad + '').trim() !== '') { {{ r.novedad }} } @else { <span class="text-gray-300">â€”</span> }
                  </td>
                  <td class="px-3 py-2.5 text-center text-gray-500 text-xs">{{ f.fecha(r.fecha_registro) }} {{ (r.fecha_registro || '').substring(11, 16) }}</td>
                  <td class="px-3 py-2.5 text-center">
                    @if (r.latitud !== null && r.longitud !== null) {
                      <a [href]="'https://www.google.com/maps?q=' + r.latitud + ',' + r.longitud" target="_blank" class="text-emerald-600 hover:text-emerald-700 font-semibold text-xs"><i class="fas fa-map-marker-alt"></i> Ver mapa</a>
                    } @else {
                      <span class="text-gray-300 text-xs">Sin ubicaciÃ³n</span>
                    }
                  </td>
                </tr>
              } @empty {
                <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400"><i class="fas fa-inbox text-3xl mb-2 block"></i>No hay marcaciones en este periodo.</td></tr>
              }
            </tbody>
          </table>
        </div>
      </div>
    </div>
  `
})
export class TurnoReportes implements OnInit {
  private api = inject(Api);
  f = fmt;
  fechaInicio = '';
  fechaFin = '';
  idTecnico: any = '';
  idTecnicoAplicado: any = '';
  fechaInicioAplicada = '';
  fechaFinAplicada = '';
  tecnicos: any[] = [];
  registros: any[] = [];
  totales: any = {};

  ngOnInit() {
    this.cargar({});
  }

  async cargar(params: Record<string, any>) {
    const d = await this.api.get('turnoReportes', 'index', params);
    this.fechaInicio = this.fechaInicioAplicada = d.fechaInicio;
    this.fechaFin = this.fechaFinAplicada = d.fechaFin;
    this.idTecnico = this.idTecnicoAplicado = d.idTecnico ?? '';
    this.tecnicos = d.tecnicos;
    this.registros = d.registros;
    this.totales = d.totales;
  }

  filtrar(e: Event) {
    e.preventDefault();
    this.cargar({ fecha_inicio: this.fechaInicio, fecha_fin: this.fechaFin, id_tecnico: this.idTecnico });
  }

  limpiar() {
    this.cargar({});
  }

  private get query() {
    return { fecha_inicio: this.fechaInicioAplicada, fecha_fin: this.fechaFinAplicada, id_tecnico: this.idTecnicoAplicado || undefined };
  }

  get urlExcel() {
    return this.api.url('turnoExcel', 'generar', this.query);
  }

  get urlPdf() {
    return this.api.url('turnoPdf', 'generar', this.query);
  }

  dia(f: string) {
    const d = ['Domingo', 'Lunes', 'Martes', 'MiÃ©rcoles', 'Jueves', 'Viernes', 'SÃ¡bado'];
    const [y, m, dd] = String(f).substring(0, 10).split('-').map(Number);
    return d[new Date(y, m - 1, dd).getDay()];
  }
}
