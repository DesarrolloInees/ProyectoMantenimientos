import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import * as XLSX from 'xlsx';
import { pdfMake } from '../../shared/pdfmake';
import { DtAbajo, DtArriba, TablaDatos, Th } from '../../shared/tabla';

const moneda = (v: any) => '$' + (parseFloat(String(v).replace(/[^0-9.-]+/g, '')) || 0).toLocaleString('es-CO');

/** extraerDatosHoja() (segunda definición de la vista PHP, que es la que queda vigente) */
function extraerDatosHoja(hoja: XLSX.WorkSheet | undefined, clave: string): any[] {
  if (!hoja) return [];
  const filas: any[][] = XLSX.utils.sheet_to_json(hoja, { header: 1, defval: '' });
  const idx = filas.findIndex((f) => f.some((c) => String(c).toUpperCase().includes(clave)));
  if (idx === -1) return [];
  const enc = filas[idx];
  const res: any[] = [];
  for (let i = idx + 1; i < filas.length; i++) {
    const fila = filas[i];
    const primero = String(fila[0] || '').trim().toUpperCase();
    if (primero === '' || primero.includes('TOTAL GENERAL')) continue;
    const obj: any = {};
    enc.forEach((e, j) => {
      const n = String(e).trim();
      if (n) obj[n] = fila[j];
    });
    res.push(obj);
  }
  return res;
}

/** Port de reporteFacturacionVista.php (Analizador de Cotizaciones: todo se procesa en el navegador) */
@Component({
  selector: 'app-reporte-facturacion',
  imports: [FormsModule, DtArriba, DtAbajo, Th],
  template: `
    <div class="w-full px-4 md:px-6">
      <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-8">
        <div class="mb-4 border-b pb-4 flex justify-between items-center flex-wrap gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-file-invoice-dollar text-green-600 mr-2"></i> Analizador de Cotizaciones</h1>
            <p class="text-gray-500 text-sm">Filtra tus cotizaciones en pantalla y exporta el reporte gerencial en PDF.</p>
          </div>
          <div class="flex gap-2">
            <input #archivo type="file" accept=".xlsx, .xls, .csv" class="hidden" (change)="leer($event)" />
            <button type="button" (click)="archivo.click()" class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700 shadow flex items-center gap-2 transform hover:scale-105 transition"><i class="fas fa-upload"></i> Subir Excel</button>
            <button type="button" [disabled]="!cargado" (click)="pdfTresHojas()" class="bg-red-600 text-white px-4 py-2 rounded font-bold shadow flex items-center gap-2 transition" [class]="cargado ? 'hover:bg-red-700' : 'opacity-50 cursor-not-allowed'"><i class="fas fa-file-pdf"></i> Exportar PDF (IM FAC, COT, Q)</button>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end mt-4">
          <div><label class="block text-sm font-bold text-gray-700 mb-1">Desde Fecha</label><input type="date" [(ngModel)]="f.inicio" class="w-full px-3 py-2 border border-gray-300 rounded-lg" /></div>
          <div><label class="block text-sm font-bold text-gray-700 mb-1">Hasta Fecha</label><input type="date" [(ngModel)]="f.fin" class="w-full px-3 py-2 border border-gray-300 rounded-lg" /></div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Categoría</label>
            <select [(ngModel)]="f.categoria" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
              <option value="">Todas</option><option value="MQ">Máquinas (MQ)</option><option value="RP">Repuestos (RP)</option><option value="SE">Servicios (SE)</option><option value="TRA">Transportadora (TRA)</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Estado</label>
            <select [(ngModel)]="f.estado" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
              <option value="">Todos</option><option value="FACTURADO">Facturado</option><option value="PENDIENTE">Pendiente</option><option value="REFACTURADA">Refacturada</option><option value="ANULADA">Anulada</option><option value="PAGADA">Pagada</option>
            </select>
          </div>
          <div><button type="button" (click)="filtrar()" class="w-full py-2 px-4 bg-gray-800 text-white font-bold rounded-lg shadow hover:bg-black transition-all"><i class="fas fa-filter mr-2"></i> Aplicar Filtros</button></div>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-100"><p class="text-sm text-blue-600 font-bold uppercase">Registros Listados</p><p class="text-2xl font-bold text-gray-800">{{ dt.datos.length }}</p></div>
        <div class="bg-green-50 p-4 rounded-lg border border-green-100"><p class="text-sm text-green-600 font-bold uppercase">Total Subtotal Calculado</p><p class="text-2xl font-bold text-gray-800">{{ moneda(total) }}</p></div>
      </div>
      <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
        <div class="flex gap-2 mb-3">
          <button type="button" (click)="excelLista()" class="bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-700 transition-colors text-sm"><i class="fas fa-file-excel mr-1"></i> Excel</button>
          <button type="button" (click)="pdfLista()" class="bg-red-600 text-white px-4 py-2 rounded shadow hover:bg-red-700 transition-colors text-sm"><i class="fas fa-file-pdf mr-1"></i> PDF Lista</button>
        </div>
        <app-dt-arriba [dt]="dt" />
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead class="text-xs text-gray-700 uppercase bg-gray-100">
              <tr>
                <th app-th [dt]="dt" campo="cotizacion" class="py-3 px-4">N° Cotización</th><th app-th [dt]="dt" campo="remision" class="py-3 px-4">Remisión</th>
                <th app-th [dt]="dt" campo="fecha" class="py-3 px-4">Fecha</th><th app-th [dt]="dt" campo="categoria" class="py-3 px-4">Categoría</th>
                <th app-th [dt]="dt" campo="estado" class="py-3 px-4">Estado</th><th class="py-3 px-4">Items</th><th app-th [dt]="dt" campo="subtotal" class="py-3 px-4 text-right">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              @for (r of dt.pagina; track $index) {
                <tr class="border-b hover:bg-gray-50">
                  <td class="py-2 px-4"><strong>{{ r.cotizacion }}</strong></td><td class="py-2 px-4">{{ r.remision }}</td><td class="py-2 px-4">{{ r.fecha }}</td>
                  <td class="py-2 px-4">{{ r.categoria }}</td><td class="py-2 px-4">{{ r.estado }}</td>
                  <td class="py-2 px-4"><span [title]="r.items">{{ r.items.length > 40 ? r.items.substring(0, 40) + '...' : r.items }}</span></td>
                  <td class="py-2 px-4 text-right"><strong>{{ '$' + r.subtotal.toLocaleString('es-CO') }}</strong></td>
                </tr>
              } @empty {
                <tr><td colspan="7" class="text-center py-6 text-gray-400">Ningún dato disponible en esta tabla</td></tr>
              }
            </tbody>
          </table>
        </div>
        <app-dt-abajo [dt]="dt" />
      </div>
    </div>
  `
})
export class ReporteFacturacion {
  f: any = { inicio: '', fin: '', categoria: '', estado: '' };
  dt = new TablaDatos<any>([], 10);
  memoria: any[] = [];
  fac: any[] = [];
  cot: any[] = [];
  q: any[] = [];
  cargado = false;
  total = 0;
  moneda = moneda;

  constructor() {
    this.dt.campoOrden = 'fecha';
    this.dt.ordenAsc = false;
  }

  leer(e: Event) {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;
    const rd = new FileReader();
    rd.onload = (ev) => {
      try {
        const wb = XLSX.read(new Uint8Array(ev.target!.result as ArrayBuffer), { type: 'array' });
        const principal = wb.SheetNames.find((n) => n.toUpperCase().includes('CONTROL COT')) || wb.SheetNames[0];
        this.procesar(XLSX.utils.sheet_to_json(wb.Sheets[principal], { raw: false }));
        const hoja = (t: string) => {
          const n = wb.SheetNames.find((x) => x.toUpperCase().includes(t));
          return n ? wb.Sheets[n] : undefined;
        };
        this.fac = extraerDatosHoja(hoja('IM FAC'), 'N° FACTURA');
        this.cot = extraerDatosHoja(hoja('IM COT'), 'N° COTIZACION');
        this.q = extraerDatosHoja(hoja('IM Q'), 'N° COTIZACION');
        this.cargado = true;
        alert('¡Archivo procesado con éxito!');
      } catch (err) {
        console.error('Error al leer el archivo:', err);
        alert('Error al procesar el archivo. Revisa la consola (F12).');
      }
      input.value = '';
    };
    rd.readAsArrayBuffer(file);
  }

  private procesar(json: any[]) {
    this.memoria = [];
    json.forEach((row) => {
      const cot = String(row['N° COTIZACION'] || row['COTIZACION'] || '');
      const sub = parseFloat(String(row['SUBTOTAL'] || row['TOTAL COT'] || '0').replace(/[^0-9.-]+/g, '')) || 0;
      if (cot && cot.trim() !== '' && !cot.toUpperCase().includes('TOTAL')) {
        this.memoria.push({
          cotizacion: cot,
          remision: row['N° REMISION'] || row['REMISION'] || '',
          categoria: row['CATEGORIA'] || '',
          fecha: row['FECHA DE REALIZACION'] || row['FECHA REALIZACION'] || '',
          estado: row['ESTADO'] || row['ESTADO '] || '',
          items: String(row['ITEMS'] || ''),
          subtotal: sub
        });
      }
    });
    this.filtrar();
  }

  filtrar() {
    const cat = String(this.f.categoria || '').toUpperCase();
    const est = String(this.f.estado || '').toUpperCase();
    const datos = this.memoria.filter((i) => {
      if (this.f.inicio && i.fecha < this.f.inicio) return false;
      if (this.f.fin && i.fecha > this.f.fin) return false;
      if (cat && String(i.categoria || '').toUpperCase() !== cat) return false;
      if (est && String(i.estado || '').toUpperCase() !== est) return false;
      return true;
    });
    this.total = datos.reduce((s, i) => s + i.subtotal, 0);
    this.dt.set(datos);
  }

  private filasVisibles() {
    return this.dt.filtrados.map((r) => [r.cotizacion, r.remision, r.fecha, r.categoria, r.estado, r.items.length > 40 ? r.items.substring(0, 40) + '...' : r.items, '$' + r.subtotal.toLocaleString('es-CO')]);
  }
  private readonly enc = ['N° Cotización', 'Remisión', 'Fecha', 'Categoría', 'Estado', 'Items', 'Subtotal'];

  /** Botón "Excel" de DataTables (excelHtml5) */
  excelLista() {
    const ws = XLSX.utils.aoa_to_sheet([this.enc, ...this.filasVisibles()]);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Hoja1');
    XLSX.writeFile(wb, (document.title || 'Reporte') + '.xlsx');
  }

  /** Botón "PDF Lista" de DataTables (pdfHtml5) */
  async pdfLista() {
    const pm = await pdfMake();
    pm.createPdf({
      content: [
        { text: document.title || 'Reporte', style: 'title' },
        { table: { headerRows: 1, body: [this.enc.map((t) => ({ text: t, style: 'tableHeader' })), ...this.filasVisibles()] }, layout: 'noBorders' }
      ],
      styles: { title: { fontSize: 15, bold: true, alignment: 'center', margin: [0, 0, 0, 10] }, tableHeader: { bold: true, fontSize: 11, color: 'white', fillColor: '#2d4154' } },
      defaultStyle: { fontSize: 10 }
    }).download((document.title || 'Reporte') + '.pdf');
  }

  async pdfTresHojas() {
    const th = (t: string) => ({ text: t, style: 'th' });
    const bodyFac: any[] = [['N° FACTURA', 'N° COTIZACION', 'N° OC', 'FECHA DE FACTURA', 'DATAICO', 'TOTAL COT'].map(th)];
    let tFac = 0;
    this.fac.forEach((r) => {
      const v = parseFloat(r['Suma de TOTAL COT']) || 0;
      tFac += v;
      bodyFac.push([{ text: r['N° FACTURA'] || '', bold: true }, r['N° COTIZACION'] || '', r['N° OC'] || '', r['FECHA DE FACTURA'] || '', r['DATAICO'] || '', { text: moneda(v), alignment: 'right' }]);
    });
    bodyFac.push([{ text: 'Total general', colSpan: 5, alignment: 'right', bold: true }, {}, {}, {}, {}, { text: moneda(tFac), alignment: 'right', bold: true }]);
    const bodyCot: any[] = [['N° COTIZACION', 'N° REMISION', 'CATEGORIA', 'FECHA REALIZACION', 'FECHA ENVIADO', 'SUBTOTAL'].map(th)];
    let tCot = 0;
    this.cot.forEach((r) => {
      const v = parseFloat(r['Suma de SUBTOTAL']) || 0;
      tCot += v;
      bodyCot.push([{ text: r['N° COTIZACION'] || '', bold: true }, r['N° REMISION'] || '', r['CATEGORIA'] || '', r['FECHA REALIZACION'] || '', r['FECHA ENVIADO'] || '', { text: moneda(v), alignment: 'right' }]);
    });
    bodyCot.push([{ text: 'Total general', colSpan: 5, alignment: 'right', bold: true }, {}, {}, {}, {}, { text: moneda(tCot), alignment: 'right', bold: true }]);
    const bodyQ: any[] = [['N° COTIZACION', 'N° REMISION', 'FECHA DE REALIZACION', 'SUBTOTAL'].map(th)];
    let tQ = 0;
    this.q.forEach((r) => {
      const v = parseFloat(r['Suma de SUBTOTAL']) || 0;
      tQ += v;
      bodyQ.push([{ text: r['N° COTIZACION'] || '', bold: true }, r['N° REMISION'] || '', r['FECHA DE REALIZACION'] || '', { text: moneda(v), alignment: 'right' }]);
    });
    bodyQ.push([{ text: 'Total general', colSpan: 3, alignment: 'right', bold: true }, {}, {}, { text: moneda(tQ), alignment: 'right', bold: true }]);
    const pm = await pdfMake();
    pm.createPdf({
      pageSize: 'A4',
      pageOrientation: 'portrait',
      pageMargins: [30, 40, 30, 40],
      content: [
        { text: 'REPORTE DE FACTURAS', style: 'titulo' },
        { table: { headerRows: 1, widths: ['15%', '15%', '25%', '15%', '15%', '15%'], body: bodyFac }, layout: 'lightHorizontalLines', margin: [0, 0, 0, 30] },
        { text: 'COTIZACIONES PENDIENTES', style: 'titulo' },
        { table: { headerRows: 1, widths: ['15%', '20%', '15%', '15%', '15%', '20%'], body: bodyCot }, layout: 'lightHorizontalLines', margin: [0, 0, 0, 30] },
        { text: 'COTIZACIONES Q PENDIENTES', style: 'titulo' },
        { table: { headerRows: 1, widths: ['25%', '25%', '25%', '25%'], body: bodyQ }, layout: 'lightHorizontalLines', margin: [0, 0, 0, 30] }
      ],
      styles: {
        titulo: { fontSize: 14, bold: true, color: '#1e3a8a', margin: [0, 10, 0, 10], alignment: 'center' },
        th: { fillColor: '#1e3a8a', color: 'white', bold: true, alignment: 'center', fontSize: 10 }
      },
      defaultStyle: { fontSize: 9 }
    }).download('Reportes_Gerenciales.pdf');
  }
}
