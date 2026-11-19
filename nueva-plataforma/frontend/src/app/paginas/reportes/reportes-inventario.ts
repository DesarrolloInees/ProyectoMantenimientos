import { Component, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import * as XLSX from 'xlsx';
import { Api } from '../../core/api';
import { DtAbajo, DtArriba, TablaDatos, Th } from '../../shared/tabla';

function dmy(f: any) {
  const s = String(f || '').substring(0, 10).split('-');
  return s.length === 3 ? `${s[2]}/${s[1]}/${s[0]}` : '';
}
const miles = (n: any) => String(Math.round(Number(n) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ',');

// =====================================================================================
// HISTORIAL / CUMPLIMIENTO (reporteHistorialVIsta.php)
// =====================================================================================
@Component({
  selector: 'app-reporte-historial',
  imports: [FormsModule, DtArriba, DtAbajo, Th],
  template: `
    <div class="w-full px-4 md:px-6">
      <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-8">
        <div class="mb-4 border-b pb-2 flex justify-between items-center flex-wrap gap-2">
          <div>
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-calendar-check text-blue-600 mr-2"></i> Reporte de Cumplimiento Semestral</h1>
            <p class="text-gray-500 text-sm">Validación de mantenimientos ejecutados vs. propuesta por máquina.</p>
          </div>
          @if (dt.datos.length) {
            <button type="button" (click)="exportar()" class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700 shadow flex items-center gap-2 transform hover:scale-105 transition"><i class="fas fa-file-excel"></i> Exportar Excel</button>
          }
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
          <div><label class="block text-sm font-bold text-gray-700 mb-1">Desde</label><input type="date" [(ngModel)]="f.fecha_inicio" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" /></div>
          <div><label class="block text-sm font-bold text-gray-700 mb-1">Hasta</label><input type="date" [(ngModel)]="f.fecha_fin" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" /></div>
          <div><button type="button" (click)="buscar()" [disabled]="cargando" class="w-full py-2 px-4 bg-blue-600 text-white font-bold rounded-lg shadow hover:bg-blue-700 transition-all"><i class="fas mr-2" [class]="cargando ? 'fa-spinner fa-spin' : 'fa-search'"></i> Generar Búsqueda</button></div>
        </div>
      </div>
      @if (dt.datos.length) {
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
          <app-dt-arriba [dt]="dt" />
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
              <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                <tr>
                  <th app-th [dt]="dt" campo="fecha_visita" class="py-3 px-4">Fecha</th>
                  <th app-th [dt]="dt" campo="cliente" class="py-3 px-4">Cliente</th>
                  <th app-th [dt]="dt" campo="punto" class="py-3 px-4">Punto</th>
                  <th app-th [dt]="dt" campo="dele" class="py-3 px-4">Delegación</th>
                  <th app-th [dt]="dt" campo="tipo_maquina" class="py-3 px-4">Tipo Máquina</th>
                  <th app-th [dt]="dt" campo="device_id" class="py-3 px-4">Device ID</th>
                  <th app-th [dt]="dt" campo="tipo_mantenimiento" class="py-3 px-4">Tipo Mantenimiento</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                @for (r of dt.pagina; track r.id_ordenes_servicio) {
                  <tr class="hover:bg-gray-50">
                    <td class="py-3 px-4 whitespace-nowrap">{{ dmy(r.fecha_visita) }}</td>
                    <td class="py-3 px-4 font-bold text-gray-800">{{ r.cliente }}</td>
                    <td class="py-3 px-4 text-gray-700">{{ r.punto }}</td>
                    <td class="py-3 px-4">{{ r.dele ?? 'N/A' }}</td>
                    <td class="py-3 px-4">{{ r.tipo_maquina ?? 'N/A' }}</td>
                    <td class="py-3 px-4 font-mono font-bold text-blue-600">{{ r.device_id ?? 'N/A' }}</td>
                    <td class="py-3 px-4 text-gray-600">{{ r.tipo_mantenimiento ?? 'SIN ESPECIFICAR' }}</td>
                  </tr>
                }
              </tbody>
            </table>
          </div>
          <app-dt-abajo [dt]="dt" />
        </div>
      } @else if (mensaje) {
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mt-4"><p class="text-yellow-700">{{ mensaje }}</p></div>
      }
    </div>
  `
})
export class ReporteHistorial implements OnInit {
  private api = inject(Api);
  f: any = {};
  dt = new TablaDatos<any>([], 10);
  excel: any[] = [];
  mensaje = '';
  cargando = false;
  dmy = dmy;

  async ngOnInit() {
    this.dt.campoOrden = 'fecha_visita';
    this.dt.ordenAsc = false;
    const r: any = await this.api.get('reporteHistorial');
    this.f = { ...r.filtros };
  }
  async buscar() {
    this.cargando = true;
    try {
      const r: any = await this.api.postForm('reporteHistorial', 'index', this.f);
      this.f = { ...r.filtros };
      this.dt.set(r.datosReporte || []);
      this.excel = r.datosExcel || [];
      this.mensaje = r.mensaje || '';
    } finally {
      this.cargando = false;
    }
  }
  exportar() {
    try {
      if (!this.excel.length) {
        alert('No hay datos para exportar.');
        return;
      }
      const matriz: any[][] = [['DEVICE ID', 'TIPO DE MÁQUINA', 'CLIENTE', 'PUNTO', 'DELEGACIÓN', 'PROPUESTA SEMESTRAL', 'TOTAL MANTENIMIENTOS', 'FALTAN', 'FECHA ÚLTIMO MANTENIMIENTO']];
      let gProp = 0,
        gMtto = 0,
        gFalt = 0;
      this.excel.forEach((row) => {
        const frec = Number(row.frecuencia);
        const propuesta: any = row.frecuencia && frec ? Math.floor(180 / frec) : 'N/A';
        let faltan: any = 'N/A';
        const totalMtto = parseInt(row.total_mantenimientos) || 0;
        const totalPrev = parseInt(row.total_preventivos) || 0;
        if (propuesta !== 'N/A') {
          faltan = propuesta - totalPrev;
          gProp += propuesta;
          gFalt += faltan;
        }
        gMtto += totalMtto;
        matriz.push([row.device_id || 'N/A', row.tipo_maquina || 'N/A', row.cliente || 'N/A', row.punto || 'N/A', row.dele || 'N/A', propuesta, totalMtto, faltan, row.fecha_ultima || '']);
      });
      matriz.push([]);
      matriz.push(['TOTALES', '', '', '', '', gProp, gMtto, gFalt, '']);
      const ws = XLSX.utils.aoa_to_sheet(matriz);
      ws['!cols'] = [20, 25, 30, 35, 15, 22, 22, 12, 28].map((wch) => ({ wch }));
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'Historial_Mantenimientos');
      XLSX.writeFile(wb, `Historial_Mantenimientos_${new Date().toISOString().slice(0, 10).replace(/-/g, '')}.xlsx`);
    } catch (e) {
      console.error('Error Excel:', e);
      alert('Error al generar Excel. Revisa consola (F12).');
    }
  }
}

// =====================================================================================
// USO DE REPUESTOS (reporteRepuestoVista.php)
// =====================================================================================
@Component({
  selector: 'app-reporte-repuesto',
  imports: [FormsModule, DtArriba, DtAbajo, Th],
  template: `
    <div class="w-full px-4 md:px-6">
      <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-8">
        <div class="mb-4 border-b pb-2 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-cogs text-blue-600 mr-2"></i> Reporte de Repuestos</h1>
            <p class="text-gray-500 text-sm">Control de inventario y salida de repuestos.</p>
          </div>
          @if (dt.datos.length) {
            <div class="flex gap-2">
              <button type="button" (click)="exportarResumen()" class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700 shadow flex items-center gap-2 transform hover:scale-105 transition text-sm"><i class="fas fa-file-excel"></i> Resumen General</button>
              <button type="button" (click)="exportarInees()" class="bg-indigo-600 text-white px-4 py-2 rounded font-bold hover:bg-indigo-700 shadow flex items-center gap-2 transform hover:scale-105 transition text-sm"><i class="fas fa-boxes"></i> Detallado INEES</button>
            </div>
          }
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Origen del Repuesto</label>
            <select [(ngModel)]="f.origen" class="w-full border border-gray-300 rounded-lg p-2">
              <option value="">-- Todos los Orígenes --</option>
              <option value="INEES">INEES</option>
              <option value="PROSEGUR">PROSEGUR</option>
            </select>
          </div>
          <div><label class="block text-sm font-bold text-gray-700 mb-1">Desde</label><input type="date" [(ngModel)]="f.fecha_inicio" class="w-full px-3 py-2 border border-gray-300 rounded-lg" /></div>
          <div><label class="block text-sm font-bold text-gray-700 mb-1">Hasta</label><input type="date" [(ngModel)]="f.fecha_fin" class="w-full px-3 py-2 border border-gray-300 rounded-lg" /></div>
          <div><button type="button" (click)="generar()" class="w-full py-2 px-4 bg-blue-600 text-white font-bold rounded-lg shadow hover:bg-blue-700 transition-all"><i class="fas fa-search mr-2"></i> Generar</button></div>
        </div>
      </div>
      @if (dt.datos.length) {
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div class="bg-blue-50 p-4 rounded-lg border border-blue-100"><p class="text-sm text-blue-600 font-bold uppercase">Referencias Distintas</p><p class="text-2xl font-bold text-gray-800">{{ dt.datos.length }}</p></div>
          <div class="bg-green-50 p-4 rounded-lg border border-green-100"><p class="text-sm text-green-600 font-bold uppercase">Total Piezas Usadas</p><p class="text-2xl font-bold text-gray-800">{{ miles(total) }}</p></div>
          <div class="bg-gray-50 p-4 rounded-lg border border-gray-200"><p class="text-sm text-gray-500 font-bold uppercase">Rango Consultado</p><p class="text-sm font-medium text-gray-800 mt-1">{{ dmy(f.fecha_inicio) }} - {{ dmy(f.fecha_fin) }}</p></div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
          <app-dt-arriba [dt]="dt" />
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
              <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                <tr>
                  <th app-th [dt]="dt" campo="codigo_referencia" class="py-3 px-4">Ref.</th>
                  <th app-th [dt]="dt" campo="nombre_repuesto" class="py-3 px-4">Repuesto</th>
                  <th app-th [dt]="dt" campo="origen" class="py-3 px-4 text-center">Origen</th>
                  <th app-th [dt]="dt" campo="veces_usado" class="py-3 px-4 text-center">Veces Solicitado</th>
                  <th app-th [dt]="dt" campo="total_cantidad" class="py-3 px-4 text-right">Cantidad Total</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                @for (r of dt.pagina; track $index) {
                  <tr class="hover:bg-gray-50">
                    <td class="py-3 px-4 whitespace-nowrap text-gray-500 font-mono">{{ r.codigo_referencia ?? 'S/R' }}</td>
                    <td class="py-3 px-4 font-bold text-gray-800">{{ r.nombre_repuesto }}</td>
                    <td class="py-3 px-4 text-center">
                      @if (r.origen == 'INEES') { <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">INEES</span> } @else { <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded">PROSEGUR</span> }
                    </td>
                    <td class="py-3 px-4 text-center text-gray-600">{{ r.veces_usado }}</td>
                    <td class="py-3 px-4 text-right font-bold text-blue-600 text-lg">{{ r.total_cantidad }}</td>
                  </tr>
                }
              </tbody>
            </table>
          </div>
          <app-dt-abajo [dt]="dt" />
        </div>
      } @else if (mensaje) {
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded shadow-sm"><div class="flex"><div class="flex-shrink-0"><i class="fas fa-exclamation-circle text-yellow-400"></i></div><div class="ml-3"><p class="text-sm text-yellow-700">{{ mensaje }}</p></div></div></div>
      }
    </div>
  `
})
export class ReporteRepuesto implements OnInit {
  private api = inject(Api);
  f: any = { origen: '' };
  dt = new TablaDatos<any>([], 10);
  inees: any[] = [];
  total = 0;
  mensaje = '';
  dmy = dmy;
  miles = miles;

  async ngOnInit() {
    this.dt.campoOrden = 'total_cantidad';
    this.dt.ordenAsc = false;
    const r: any = await this.api.get('reporteRepuesto');
    this.f = { ...r.filtros };
  }
  async generar() {
    const r: any = await this.api.postForm('reporteRepuesto', 'index', this.f);
    this.f = { ...r.filtros };
    this.dt.set(r.datosReporte || []);
    this.inees = r.datosInees || [];
    this.total = r.totalPiezas || 0;
    this.mensaje = r.mensaje || '';
  }
  exportarResumen() {
    const d = this.dt.datos;
    if (!d.length) return alert('Sin datos.');
    const m: any[][] = [['CÓDIGO', 'REPUESTO', 'ORIGEN', 'VECES SOLICITADO', 'CANTIDAD TOTAL']];
    d.forEach((x) => m.push([x.codigo_referencia, x.nombre_repuesto, x.origen, parseInt(x.veces_usado), parseInt(x.total_cantidad)]));
    const ws = XLSX.utils.aoa_to_sheet(m);
    ws['!cols'] = [15, 40, 15, 20, 15].map((wch) => ({ wch }));
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Resumen');
    XLSX.writeFile(wb, 'Resumen_Repuestos.xlsx');
  }
  exportarInees() {
    if (!this.inees.length) {
      alert('No hay datos detallados de INEES o hubo un error al cargarlos.');
      return;
    }
    const m: any[][] = [
      ['REPORTE DETALLADO DE REPUESTOS - INEES'],
      [],
      ['CLIENTE', 'PUNTO / SEDE', 'DELEGACIÓN', 'MÁQUINA (ID)', 'TIPO MÁQUINA', 'REMISIÓN', 'DESCRIPCIÓN DEL SERVICIO', 'REPUESTO', 'CÓDIGO', 'VALOR', 'CANT.', 'SUBTOTAL']
    ];
    this.inees.forEach((d) =>
      m.push([d.nombre_cliente, d.nombre_punto, d.nombre_delegacion || 'N/A', d.device_id, d.nombre_tipo_maquina || 'N/A', d.numero_remision, d.observacion, d.nombre_repuesto, d.codigo_referencia || 'S/C', '', parseInt(d.cantidad), ''])
    );
    const ws = XLSX.utils.aoa_to_sheet(m);
    ws['!cols'] = [30, 25, 20, 15, 20, 15, 50, 35, 15, 12, 8, 12].map((wch) => ({ wch }));
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Detalle INEES');
    XLSX.writeFile(wb, `Detalle_Repuestos_INEES_${new Date().toISOString().slice(0, 10)}.xlsx`);
  }
}

// =====================================================================================
// INVENTARIO EN CALLE (PDF) – en PHP la página respondía directamente con el PDF
// =====================================================================================
@Component({
  selector: 'app-reporte-repuestos',
  template: `<div class="p-10 text-center text-gray-500"><i class="fas fa-spinner fa-spin text-3xl mb-3 block"></i> Generando reporte de inventario...</div>`
})
export class ReporteRepuestosPdf implements OnInit {
  private api = inject(Api);
  private route = inject(ActivatedRoute);
  ngOnInit() {
    const q = this.route.snapshot.queryParamMap;
    const params: any = {};
    ['fecha_desde', 'fecha_hasta'].forEach((k) => q.get(k) && (params[k] = q.get(k)));
    window.location.replace(this.api.url('reporteRepuestos', undefined, params));
  }
}

// =====================================================================================
// TRAZABILIDAD (reporteTrazabilidadVista.php)
// =====================================================================================
@Component({
  selector: 'app-reporte-trazabilidad',
  imports: [FormsModule, DtArriba, DtAbajo, Th],
  template: `
    <div class="w-full px-4 md:px-6">
      <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-8">
        <div class="mb-4 border-b pb-2 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-route text-teal-600 mr-2"></i> Trazabilidad de Repuestos</h1>
            <p class="text-gray-500 text-sm">Detalle por punto y top consolidado por tipo de maquina. Descarga en Excel (2 hojas).</p>
          </div>
          @if (det.datos.length || top.datos.length) {
            <a [href]="urlExcel()" class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700 shadow flex items-center gap-2 text-sm"><i class="fas fa-file-excel"></i> Descargar Excel (2 hojas)</a>
          }
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
          <div><label class="block text-sm font-bold text-gray-700 mb-1">Desde</label><input type="date" [(ngModel)]="f.fecha_inicio" class="w-full px-3 py-2 border border-gray-300 rounded-lg" /></div>
          <div><label class="block text-sm font-bold text-gray-700 mb-1">Hasta</label><input type="date" [(ngModel)]="f.fecha_fin" class="w-full px-3 py-2 border border-gray-300 rounded-lg" /></div>
          <div><button type="button" (click)="consultar()" class="w-full py-2 px-4 bg-teal-600 text-white font-bold rounded-lg shadow hover:bg-teal-700 transition-all"><i class="fas fa-search mr-2"></i> Consultar</button></div>
        </div>
      </div>
      @if (mensaje && !det.datos.length) {
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded shadow-sm mb-6"><p class="text-sm text-yellow-700">{{ mensaje }}</p></div>
      }
      @if (det.datos.length || top.datos.length) {
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div class="bg-teal-50 p-4 rounded-lg border border-teal-100"><p class="text-sm text-teal-600 font-bold uppercase">Registros detalle</p><p class="text-2xl font-bold text-gray-800">{{ miles(det.datos.length) }}</p></div>
          <div class="bg-green-50 p-4 rounded-lg border border-green-100"><p class="text-sm text-green-600 font-bold uppercase">Total piezas</p><p class="text-2xl font-bold text-gray-800">{{ miles(total) }}</p></div>
          <div class="bg-gray-50 p-4 rounded-lg border border-gray-200"><p class="text-sm text-gray-500 font-bold uppercase">Rango</p><p class="text-sm font-medium text-gray-800 mt-1">{{ dmy(f.fecha_inicio) }} - {{ dmy(f.fecha_fin) }}</p></div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-6">
          <h2 class="font-bold text-gray-800 mb-3">Hoja 1: Trazabilidad por Punto</h2>
          <app-dt-arriba [dt]="det" />
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
              <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                <tr>
                  <th app-th [dt]="det" campo="cliente" class="py-3 px-4">Cliente</th><th app-th [dt]="det" campo="punto" class="py-3 px-4">Punto</th>
                  <th app-th [dt]="det" campo="tipo_maquina" class="py-3 px-4">Tipo Maquina</th><th app-th [dt]="det" campo="device_id" class="py-3 px-4">Device ID</th>
                  <th app-th [dt]="det" campo="repuesto" class="py-3 px-4">Repuesto</th><th app-th [dt]="det" campo="origen" class="py-3 px-4">Origen</th>
                  <th app-th [dt]="det" campo="cantidad" class="py-3 px-4 text-center">Cant.</th><th app-th [dt]="det" campo="fecha_cambio" class="py-3 px-4">Fecha Cambio</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                @for (r of det.pagina; track $index) {
                  <tr class="hover:bg-gray-50">
                    <td class="py-3 px-4 font-bold">{{ r.cliente }}</td><td class="py-3 px-4">{{ r.punto }}</td><td class="py-3 px-4">{{ r.tipo_maquina }}</td>
                    <td class="py-3 px-4 font-mono text-teal-700 font-bold">{{ r.device_id }}</td><td class="py-3 px-4">{{ r.repuesto }}</td>
                    <td class="py-3 px-4 text-center">{{ r.origen }}</td><td class="py-3 px-4 text-center font-bold">{{ +r.cantidad }}</td><td class="py-3 px-4">{{ dmy(r.fecha_cambio) }}</td>
                  </tr>
                }
              </tbody>
            </table>
          </div>
          <app-dt-abajo [dt]="det" />
        </div>
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
          <h2 class="font-bold text-gray-800 mb-3">Hoja 2: Top Repuestos por Tipo de Maquina</h2>
          <app-dt-arriba [dt]="top" />
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
              <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                <tr><th app-th [dt]="top" campo="tipo_maquina" class="py-3 px-4">Tipo Maquina</th><th app-th [dt]="top" campo="repuesto" class="py-3 px-4">Repuesto</th><th app-th [dt]="top" campo="origen" class="py-3 px-4">Origen</th><th app-th [dt]="top" campo="total_cambiados" class="py-3 px-4 text-right">Total</th></tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                @for (t of top.pagina; track $index) {
                  <tr class="hover:bg-gray-50"><td class="py-3 px-4 font-bold">{{ t.tipo_maquina }}</td><td class="py-3 px-4">{{ t.repuesto }}</td><td class="py-3 px-4">{{ t.origen }}</td><td class="py-3 px-4 text-right font-bold text-teal-700">{{ +t.total_cambiados }}</td></tr>
                }
              </tbody>
            </table>
          </div>
          <app-dt-abajo [dt]="top" />
        </div>
      }
    </div>
  `
})
export class ReporteTrazabilidad implements OnInit {
  private api = inject(Api);
  f: any = {};
  det = new TablaDatos<any>([], 10);
  top = new TablaDatos<any>([], 10);
  total = 0;
  mensaje = '';
  dmy = dmy;
  miles = miles;

  async ngOnInit() {
    const r: any = await this.api.get('reporteTrazabilidad');
    this.f = { ...r.filtros };
  }
  async consultar() {
    const r: any = await this.api.postForm('reporteTrazabilidad', 'index', this.f);
    this.f = { ...r.filtros };
    this.det.set(r.datosTrazabilidad || []);
    this.top.set(r.datosTop || []);
    this.total = r.totalPiezas || 0;
    this.mensaje = r.mensaje || '';
  }
  urlExcel() {
    return this.api.url('reporteTrazabilidad', 'descargarExcel', { fecha_inicio: this.f.fecha_inicio, fecha_fin: this.f.fecha_fin });
  }
}
