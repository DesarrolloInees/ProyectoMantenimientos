import { Component, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import * as XLSX from 'xlsx';
import { Api } from '../../core/api';
import { SelectBuscar } from '../../shared/select-buscar';
import { DtAbajo, DtArriba, TablaDatos, Th } from '../../shared/tabla';

function dmy(f: any) {
  const s = String(f || '').substring(0, 10).split('-');
  return s.length === 3 ? `${s[2]}/${s[1]}/${s[0]}` : '';
}
function ymd(d: Date) {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

// =====================================================================================
// REMISIONES SALTEADAS (remisionesPendientesVista.php)
// =====================================================================================
@Component({
  selector: 'app-remisiones-pendientes',
  imports: [RouterLink, DtArriba, DtAbajo, Th],
  template: `
    <div class="w-full px-4 md:px-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-bullseye text-red-600 mr-2"></i> Remisiones Salteadas</h1>
          <p class="text-gray-500 mt-1">Detectando saltos de 1 o 2 remisiones <strong>DISPONIBLES</strong> atrapadas entre <strong>USADAS</strong>.</p>
        </div>
        <a routerLink="/controlRemisionVer" class="text-gray-600 hover:text-gray-900 font-medium"><i class="fas fa-arrow-left"></i> Volver</a>
      </div>
      @if (cargado && !dt.datos.length) {
        <div class="bg-green-50 border border-green-200 rounded-lg p-10 text-center shadow-sm">
          <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4"><i class="fas fa-check text-2xl text-green-600"></i></div>
          <h3 class="text-lg font-bold text-gray-800">Todo Correcto</h3>
          <p class="text-gray-600">No hay saltos de 1 o 2 remisiones detectados actualmente.</p>
        </div>
      } @else if (cargado) {
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
          <app-dt-arriba [dt]="dt" />
          <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
              <tr>
                <th app-th [dt]="dt" campo="nombre_tecnico" class="px-6 py-3">Técnico</th>
                <th class="px-6 py-3 text-center">Secuencia (Ant - Actual - Sig)</th>
                <th class="px-6 py-3 text-center">Estado</th>
                <th class="px-6 py-3 text-right">Acción</th>
              </tr>
            </thead>
            <tbody>
              @for (p of dt.pagina; track p.id_control) {
                <tr class="bg-white border-b hover:bg-red-50 transition">
                  <td class="px-6 py-4 font-bold text-gray-800">{{ p.nombre_tecnico }}<div class="text-xs text-gray-400 font-normal mt-1">Asig: {{ dmy(p.fecha_asignacion) }}</div></td>
                  <td class="px-6 py-4">
                    <div class="flex items-center justify-center gap-2">
                      <div class="text-center opacity-60"><span class="bg-gray-200 text-gray-600 px-2 py-1 rounded text-xs font-bold block mb-1">#{{ p.anterior }}</span><span class="text-[10px] uppercase text-green-600 font-bold">Usada</span></div>
                      <i class="fas fa-arrow-right text-gray-300 text-xs"></i>
                      <div class="text-center transform scale-110 mx-1"><span class="bg-red-100 text-red-600 border border-red-200 px-3 py-1.5 rounded text-sm font-black block mb-1">#{{ p.numero_remision }}</span><span class="text-[10px] uppercase text-red-500 font-bold">Disponible</span></div>
                      <i class="fas fa-arrow-right text-gray-300 text-xs"></i>
                      <div class="text-center opacity-60"><span class="bg-gray-200 text-gray-600 px-2 py-1 rounded text-xs font-bold block mb-1">#{{ p.siguiente }}</span><span class="text-[10px] uppercase text-green-600 font-bold">Usada</span></div>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-center"><span class="bg-orange-100 text-orange-800 text-xs font-bold px-2.5 py-0.5 rounded border border-orange-200">Salteada</span></td>
                  <td class="px-6 py-4 text-right">
                    <div class="flex justify-end gap-2">
                      <button (click)="cambiar(p, 'ANULADA', '¿Confirmas ANULAR la remisión #' + p.numero_remision + '?')" class="text-white bg-red-500 hover:bg-red-600 px-3 py-1.5 rounded shadow text-xs font-bold transition flex items-center gap-1"><i class="fas fa-ban"></i> Anular</button>
                      <button (click)="cambiar(p, 'USADA', '¿Marcar #' + p.numero_remision + ' como USADA?')" class="text-green-600 hover:text-green-800 bg-green-50 hover:bg-green-100 p-2 rounded transition border border-green-200" title="Marcar como Usada"><i class="fas fa-check"></i></button>
                    </div>
                  </td>
                </tr>
              }
            </tbody>
          </table>
          <app-dt-abajo [dt]="dt" />
        </div>
      }
    </div>
  `
})
export class RemisionesPendientes implements OnInit {
  private api = inject(Api);
  dt = new TablaDatos<any>([], 10);
  cargado = false;
  dmy = dmy;

  ngOnInit() {
    this.dt.campoOrden = 'nombre_tecnico';
    this.cargar();
  }
  async cargar() {
    const r: any = await this.api.get('remisionesPendientes');
    this.dt.set(r.pendientes || []);
    this.cargado = true;
  }
  async cambiar(p: any, estado: string, pregunta: string) {
    if (!confirm(pregunta)) return;
    await this.api.postForm('remisionesPendientes', 'cambiarEstado', { id: p.id_control, estado });
    this.cargar();
  }
}

// =====================================================================================
// DEVOLUCIONES (reporteDevolucionVista.php)
// =====================================================================================
@Component({
  selector: 'app-reporte-devolucion',
  imports: [FormsModule, SelectBuscar, DtArriba, DtAbajo, Th],
  template: `
    <div class="w-full px-4 md:px-6">
      <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-8">
        <div class="mb-6 border-b pb-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-undo-alt text-orange-600 mr-2"></i> Control de Devoluciones</h1>
            <p class="text-gray-500 text-sm">Listado de piezas retiradas que los técnicos deben entregar en sede.</p>
          </div>
          @if (dt.datos.length) {
            <button type="button" (click)="exportar()" class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700 shadow flex items-center gap-2 transition text-sm"><i class="fas fa-file-excel"></i> Exportar a Excel</button>
          }
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Técnico</label>
            <app-select-buscar [opciones]="tecnicos" valor="id_tecnico" texto="nombre_tecnico" placeholder="-- Todos los Técnicos --" [(ngModel)]="f.id_tecnico" />
          </div>
          <div><label class="block text-sm font-bold text-gray-700 mb-1">Desde</label><input type="date" [(ngModel)]="f.fecha_inicio" class="w-full px-3 py-2 border border-gray-300 rounded-lg" /></div>
          <div><label class="block text-sm font-bold text-gray-700 mb-1">Hasta</label><input type="date" [(ngModel)]="f.fecha_fin" class="w-full px-3 py-2 border border-gray-300 rounded-lg" /></div>
          <div><button type="button" (click)="consultar()" class="w-full py-2 px-4 bg-orange-600 text-white font-bold rounded-lg shadow hover:bg-orange-700 transition-all"><i class="fas fa-search mr-2"></i> Consultar Pendientes</button></div>
        </div>
      </div>
      @if (dt.datos.length) {
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
          <app-dt-arriba [dt]="dt" />
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
              <thead class="bg-gray-50 text-gray-700 uppercase text-xs">
                <tr>
                  <th app-th [dt]="dt" campo="nombre_tecnico" class="py-3 px-4">Técnico</th>
                  <th app-th [dt]="dt" campo="nombre_repuesto" class="py-3 px-4">Repuesto / Código</th>
                  <th app-th [dt]="dt" campo="fecha_visita" class="py-3 px-4">Fecha Servicio</th>
                  <th app-th [dt]="dt" campo="numero_remision" class="py-3 px-4">Remisión</th>
                  <th app-th [dt]="dt" campo="nombre_cliente" class="py-3 px-4">Cliente / Punto</th>
                  <th app-th [dt]="dt" campo="cantidad" class="py-3 px-4 text-center">Cant.</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                @for (d of dt.pagina; track $index) {
                  <tr class="hover:bg-orange-50 transition-colors">
                    <td class="py-3 px-4 font-bold text-gray-800">{{ d.nombre_tecnico }}</td>
                    <td class="py-3 px-4"><div class="font-medium">{{ d.nombre_repuesto }}</div><div class="text-xs text-gray-400 font-mono">{{ d.codigo_referencia ?? 'S/R' }}</div></td>
                    <td class="py-3 px-4">{{ dmy(d.fecha_visita) }}</td>
                    <td class="py-3 px-4 font-semibold text-blue-600">{{ d.numero_remision }}</td>
                    <td class="py-3 px-4"><div class="text-xs font-bold">{{ d.nombre_cliente }}</div><div class="text-xs text-gray-500">{{ d.nombre_punto }}</div></td>
                    <td class="py-3 px-4 text-center font-bold text-lg">{{ d.cantidad }}</td>
                  </tr>
                }
              </tbody>
            </table>
          </div>
          <app-dt-abajo [dt]="dt" />
        </div>
      }
    </div>
  `
})
export class ReporteDevolucion implements OnInit {
  private api = inject(Api);
  tecnicos: any[] = [];
  f: any = { id_tecnico: '', fecha_inicio: '', fecha_fin: '' };
  dt = new TablaDatos<any>([], 10);
  dmy = dmy;

  async ngOnInit() {
    this.dt.campoOrden = 'nombre_tecnico';
    const r: any = await this.api.get('reporteDevolucion');
    this.tecnicos = r.tecnicos || [];
    this.f = { ...r.filtros };
  }
  async consultar() {
    const r: any = await this.api.postForm('reporteDevolucion', 'index', this.f);
    this.f = { ...r.filtros, id_tecnico: r.filtros.id_tecnico || '' };
    this.dt.set(r.datosDevoluciones || []);
  }
  exportar() {
    const datos = this.dt.datos;
    if (!datos.length) return;
    const matriz: any[][] = [['TECNICO', 'REPUESTO', 'CODIGO', 'FECHA', 'REMISIÓN', 'DELEGACIÓN', 'CLIENTE', 'PUNTO', 'CANTIDAD']];
    datos.forEach((d) =>
      matriz.push([d.nombre_tecnico, d.nombre_repuesto, d.codigo_referencia || 'S/C', d.fecha_visita, d.numero_remision, d.nombre_delegacion || 'N/A', d.nombre_cliente, d.nombre_punto, parseInt(d.cantidad)])
    );
    const ws = XLSX.utils.aoa_to_sheet(matriz);
    ws['!cols'] = [25, 35, 15, 12, 15, 20, 30, 30, 10].map((wch) => ({ wch }));
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Pendientes Devolucion');
    XLSX.writeFile(wb, `Control_Devoluciones_${ymd(new Date())}.xlsx`);
  }
}

// =====================================================================================
// MÁQUINAS POR TIPO (reporteMaquinasVista.php)
// =====================================================================================
@Component({
  selector: 'app-reporte-maquinas',
  imports: [FormsModule, SelectBuscar, DtArriba, DtAbajo, Th],
  template: `
    <div class="w-full px-4 md:px-6">
      <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-6">
        <div class="flex justify-between items-center border-b pb-4 mb-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-filter text-indigo-600 mr-2"></i> Reporte por Tipo de Máquina</h1>
            <p class="text-gray-500 text-sm">Selecciona el tipo de equipo para ver su ubicación y fechas.</p>
          </div>
          @if (dt.datos.length) {
            <button type="button" (click)="exportar()" class="bg-green-600 text-white px-5 py-2 rounded-lg font-bold hover:bg-green-700 shadow flex items-center gap-2 transition-all"><i class="fas fa-file-excel"></i> Exportar Resultados</button>
          }
        </div>
        <div class="flex flex-wrap gap-4 items-end">
          <div class="w-full md:w-1/3">
            <label class="block text-sm font-bold text-gray-700 mb-1">Seleccionar Tipo de Máquina:</label>
            <app-select-buscar [opciones]="tipos" valor="id_tipo_maquina" texto="nombre_tipo_maquina" placeholder="Escribe para buscar..." [(ngModel)]="idTipo" />
          </div>
          <div><button type="button" (click)="consultar()" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-indigo-700 shadow transition-all"><i class="fas fa-search mr-2"></i> Consultar</button></div>
        </div>
      </div>
      @if (consultado) {
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
          <app-dt-arriba [dt]="dt" />
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
              <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                <tr>
                  <th app-th [dt]="dt" campo="nombre_delegacion" class="px-6 py-3">Delegación</th>
                  <th app-th [dt]="dt" campo="device_id" class="px-6 py-3">Device ID</th>
                  <th app-th [dt]="dt" campo="nombre_tipo_maquina" class="px-6 py-3">Tipo</th>
                  <th app-th [dt]="dt" campo="nombre_punto" class="px-6 py-3">Punto</th>
                  <th app-th [dt]="dt" campo="direccion" class="px-6 py-3">Dirección</th>
                  <th app-th [dt]="dt" campo="fecha_ultima_visita" class="px-6 py-3">Última Visita (Punto)</th>
                </tr>
              </thead>
              <tbody>
                @for (r of dt.pagina; track $index) {
                  <tr class="bg-white border-b hover:bg-gray-50">
                    <td class="px-6 py-4 font-bold text-blue-600">{{ r.nombre_delegacion || 'N/A' }}</td>
                    <td class="px-6 py-4 font-mono font-bold text-indigo-600">{{ r.device_id }}</td>
                    <td class="px-6 py-4">{{ r.nombre_tipo_maquina }}</td>
                    <td class="px-6 py-4 font-bold text-gray-900">{{ r.nombre_punto }}</td>
                    <td class="px-6 py-4">{{ r.direccion }}</td>
                    <td class="px-6 py-4">
                      @if (r.fecha_ultima_visita) { {{ dmy(r.fecha_ultima_visita) }} } @else { <span class="text-red-400">Sin registro</span> }
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
      }
    </div>
  `
})
export class ReporteMaquinas implements OnInit {
  private api = inject(Api);
  tipos: any[] = [];
  idTipo: any = '';
  consultado = false;
  dt = new TablaDatos<any>([], 10);
  dmy = dmy;

  async ngOnInit() {
    const r: any = await this.api.get('reporteMaquinas');
    this.tipos = r.listaTipos || [];
  }
  async consultar() {
    if (!this.idTipo) {
      alert('Seleccione un tipo de máquina.');
      return;
    }
    const r: any = await this.api.postForm('reporteMaquinas', 'index', { id_tipo_maquina: this.idTipo });
    this.dt.set(r.datosMaquinas || []);
    this.consultado = !!r.idTipoSeleccionado;
  }
  exportar() {
    const d = this.dt.datos;
    if (!d.length) {
      alert('No hay datos para exportar');
      return;
    }
    const ws = XLSX.utils.json_to_sheet(
      d.map((x) => ({
        Delegación: x.nombre_delegacion || 'N/A',
        'ID Dispositivo': x.device_id,
        'Tipo Máquina': x.nombre_tipo_maquina,
        'Nombre Punto': x.nombre_punto,
        Dirección: x.direccion,
        'Fecha Visita Punto': x.fecha_ultima_visita
      }))
    );
    ws['!cols'] = [20, 15, 20, 30, 40, 15].map((wch) => ({ wch }));
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Reporte');
    XLSX.writeFile(wb, `Reporte_${String(d[0].nombre_tipo_maquina).replace(/[^a-z0-9]/gi, '_')}.xlsx`);
  }
}

// =====================================================================================
// TARIFAS MENSUALES (reporteTarifasVista.php)
// =====================================================================================
@Component({
  selector: 'app-reporte-tarifas',
  imports: [FormsModule, RouterLink],
  template: `
    <div class="w-full max-w-4xl mx-auto mt-6">
      <div class="bg-white shadow-lg rounded-lg overflow-hidden border border-gray-200">
        <div class="bg-indigo-600 p-6">
          <h2 class="text-2xl font-bold text-white flex items-center gap-2"><i class="fas fa-file-invoice text-3xl"></i> Reporte de Tarifas Mensuales</h2>
          <p class="text-indigo-100 mt-2">Genera el reporte definitivo en 4 hojas separando los Resúmenes Ejecutivos y los Desgloses por tamaño.</p>
        </div>
        <div class="p-8">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 bg-gray-50 p-4 rounded-md border border-gray-200">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Inicio</label><input type="date" [(ngModel)]="ini" class="w-full border-gray-300 rounded-md shadow-sm sm:text-sm p-2 border" /></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Fin</label><input type="date" [(ngModel)]="fin" class="w-full border-gray-300 rounded-md shadow-sm sm:text-sm p-2 border" /></div>
          </div>
          <div class="flex justify-center">
            <button (click)="generar()" [disabled]="cargando" class="group relative w-full flex justify-center py-3 px-8 border border-transparent text-lg font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 transition-all duration-200 shadow-md transform hover:-translate-y-1 cursor-pointer" [class.opacity-50]="cargando">
              <span class="absolute left-0 inset-y-0 flex items-center pl-3"><i class="fas fa-file-alt text-indigo-300 group-hover:text-indigo-100"></i></span>
              <span>{{ cargando ? 'Calculando archivo...' : 'GENERAR REPORTE (.XLSX)' }}</span>
            </button>
          </div>
        </div>
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-between items-center">
          <span class="text-sm text-gray-500">Formato de salida: Excel Original (.xlsx)</span>
          <a routerLink="/inicio" class="text-sm text-indigo-600 hover:text-indigo-800 hover:underline">&larr; Volver al inicio</a>
        </div>
      </div>
    </div>
  `
})
export class ReporteTarifas {
  private api = inject(Api);
  // En la vista PHP se usaba toISOString() (UTC); aquí se usa la fecha local
  ini = ymd(new Date(new Date().getFullYear(), new Date().getMonth(), 1));
  fin = ymd(new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0));
  cargando = false;

  async generar() {
    if (!this.ini || !this.fin) {
      alert('Por favor selecciona ambas fechas.');
      return;
    }
    this.cargando = true;
    try {
      const datos: any[] = await this.api.get('reporteTarifas', 'descargarReportePorcentajes', { fecha_inicio: this.ini, fecha_fin: this.fin });
      if (!Array.isArray(datos) || datos.length === 0) {
        alert('No hay datos para el rango de fechas seleccionado.');
        return;
      }
      const wb = XLSX.utils.book_new();
      const mapa: Record<string, any> = {};
      const totales: Record<string, number> = { Grandes: 0, Pequeñas: 0 };
      datos.forEach((d) => {
        const cat = d.categoria_tamano;
        const mant = d.tipo_mantenimiento || 'Desconocido';
        const k = cat + '|' + mant;
        const precio = parseFloat(d.precio_total || 0);
        mapa[k] ||= { Categoria: cat, Mantenimiento: mant, Cantidad: 0, Precio: 0 };
        mapa[k].Cantidad += parseInt(d.cantidad_servicios);
        mapa[k].Precio += precio;
        totales[cat] += precio;
      });
      const resumen = Object.values(mapa).map((i: any) => ({
        'Grupo de Máquinas': i.Categoria,
        'Tipo de Mantenimiento': i.Mantenimiento,
        'Cantidad de Servicios': i.Cantidad,
        'Precio Total Recaudado': i.Precio,
        'Porcentaje del Grupo (%)': totales[i.Categoria] > 0 ? i.Precio / totales[i.Categoria] : 0
      }));
      const desglose = (cat: string) =>
        datos
          .filter((d) => d.categoria_tamano === cat)
          .map((d) => {
            const precio = parseFloat(d.precio_total || 0);
            return {
              'Máquina Específica': d.nombre_tipo_maquina || 'Sin Grupo',
              'Tipo de Mantenimiento': d.tipo_mantenimiento || 'Desconocido',
              'Cantidad de Servicios': parseInt(d.cantidad_servicios || 0),
              'Precio Total Recaudado': precio,
              'Porcentaje del Grupo (%)': totales[cat] > 0 ? precio / totales[cat] : 0
            };
          });
      this.hoja(wb, resumen.filter((r) => r['Grupo de Máquinas'] === 'Grandes'), 'Resumen Grandes', [20, 35, 22, 25, 25]);
      this.hoja(wb, desglose('Grandes'), 'Desglose Grandes', [30, 35, 22, 25, 25]);
      this.hoja(wb, resumen.filter((r) => r['Grupo de Máquinas'] === 'Pequeñas'), 'Resumen Pequeñas', [20, 35, 22, 25, 25]);
      this.hoja(wb, desglose('Pequeñas'), 'Desglose Pequeñas', [30, 35, 22, 25, 25]);
      XLSX.writeFile(wb, `Reporte_Tarifas_${this.ini}_al_${this.fin}.xlsx`);
    } catch (e) {
      console.error('Error al exportar:', e);
      alert('Hubo un error al generar el Excel.');
    } finally {
      this.cargando = false;
    }
  }

  private hoja(wb: XLSX.WorkBook, datos: any[], nombre: string, anchos: number[]) {
    if (!datos.length) return;
    const ws = XLSX.utils.json_to_sheet(datos);
    if (ws['!ref']) {
      const rg = XLSX.utils.decode_range(ws['!ref']);
      for (let R = rg.s.r + 1; R <= rg.e.r; ++R) {
        const p = ws[XLSX.utils.encode_cell({ r: R, c: 3 })];
        if (p) p.z = '"$"#,##0.00';
        const q = ws[XLSX.utils.encode_cell({ r: R, c: 4 })];
        if (q) q.z = '0.00%';
      }
    }
    ws['!cols'] = anchos.map((wch) => ({ wch }));
    XLSX.utils.book_append_sheet(wb, ws, nombre);
  }
}
