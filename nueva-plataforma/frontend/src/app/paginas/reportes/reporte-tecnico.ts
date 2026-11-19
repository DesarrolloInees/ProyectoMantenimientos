import { Component, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import * as XLSX from 'xlsx';
import { Api } from '../../core/api';
import { SelectBuscar } from '../../shared/select-buscar';
import { DtAbajo, DtArriba, TablaDatos, Th } from '../../shared/tabla';

function dmy(f: any) {
  const s = String(f || '').substring(0, 10).split('-');
  return s.length === 3 ? `${s[2]}/${s[1]}/${s[0]}` : '';
}
const n2 = (v: any) => (Number(v) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const ORDEN = ['MURGAS', 'JHONY', 'MAICOL', 'RUIZ', 'ORJUELA', 'FORERO', 'ESPINOSA', 'MAURICIO', 'JHONATAN', 'ORTIZ', 'CERVERA', 'VILORIA', 'SAAVEDRA', 'BENAVIDES'];
const FMT_MONEDA = '_-"$"* #,##0_-;-"$"* #,##0_-;-"$"* "-"??_-;-_-@_-';
const HEAD = ['Device_id', 'Número de Remisión', 'Cliente', 'Nombre Punto', 'Preventivo Básico', 'Preventivo Profundo', 'Correctivo', 'Tarifa', 'Observaciones', 'Delegación', 'Fecha', 'Técnico', 'Tipo de Máquina', 'Tipo de Servicio', 'Hora Entrada', 'Hora Salida', 'Duración', 'Desplazamiento', 'Repuestos', 'Estado', 'Calificación', 'Modalidad'];
const ANCHOS = [15, 12, 25, 25, 8, 8, 8, 15, 50, 15, 12, 20, 15, 20, 10, 10, 10, 12, 30, 15, 15, 15].map((wch) => ({ wch }));

function difHoras(ini: string, fin: string) {
  if (!ini || !fin) return '00:00';
  const d1 = new Date(`2000-01-01T${ini}`),
    d2 = new Date(`2000-01-01T${fin}`);
  if (isNaN(d1.getTime()) || isNaN(d2.getTime())) return '00:00';
  const diff = d2.getTime() - d1.getTime();
  if (diff < 0) return 'Err';
  const m = Math.floor(diff / 60000);
  return `${String(Math.floor(m / 60)).padStart(2, '0')}:${String(m % 60).padStart(2, '0')}`;
}

/** Port de reporteTecnicoVista.php */
@Component({
  selector: 'app-reporte-tecnico',
  imports: [FormsModule, SelectBuscar, DtArriba, DtAbajo, Th],
  template: `
    <div class="w-full px-4 md:px-6">
      <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-8">
        <div class="mb-4 border-b pb-2 flex justify-between items-center flex-wrap gap-2">
          <div>
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-chart-line text-blue-600 mr-2"></i> Reporte de Servicios</h1>
            <p class="text-gray-500 text-sm">Consulta la productividad y servicios realizados por técnico.</p>
          </div>
          @if (dt.datos.length) {
            <div class="flex items-center gap-2 flex-wrap">
              <button type="button" (click)="pdf()" class="bg-red-600 text-white px-4 py-2 rounded font-bold hover:bg-red-700 shadow flex items-center gap-2 transform hover:scale-105 transition"><i class="fas fa-file-pdf"></i> PDF del Día</button>
              <button type="button" (click)="excel()" class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700 shadow flex items-center gap-2 transform hover:scale-105 transition"><i class="fas fa-file-excel"></i> Exportar Excel</button>
            </div>
          }
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Técnico</label>
            <app-select-buscar [opciones]="tecnicos" valor="id_tecnico" texto="nombre_tecnico" placeholder="-- Todos los Técnicos --" [(ngModel)]="f.id_tecnico" />
          </div>
          <div><label class="block text-sm font-bold text-gray-700 mb-1">Desde</label><input type="date" [(ngModel)]="f.fecha_inicio" class="w-full px-3 py-2 border border-gray-300 rounded-lg" /></div>
          <div><label class="block text-sm font-bold text-gray-700 mb-1">Hasta</label><input type="date" [(ngModel)]="f.fecha_fin" class="w-full px-3 py-2 border border-gray-300 rounded-lg" /></div>
          <div><button type="button" (click)="generar()" [disabled]="cargando" class="w-full py-2 px-4 bg-blue-600 text-white font-bold rounded-lg shadow hover:bg-blue-700 transition-all"><i class="fas mr-2" [class]="cargando ? 'fa-spinner fa-spin' : 'fa-search'"></i> Generar</button></div>
        </div>
      </div>

      @if (dt.datos.length) {
        <div class="grid grid-cols-1 gap-4 mb-6" [class.md:grid-cols-2]="rol === 5" [class.md:grid-cols-3]="rol !== 5">
          <div class="bg-blue-50 p-4 rounded-lg border border-blue-100"><p class="text-sm text-blue-600 font-bold uppercase">Total Servicios</p><p class="text-2xl font-bold text-gray-800">{{ dt.datos.length }}</p></div>
          @if (rol !== 5) {
            <div class="bg-green-50 p-4 rounded-lg border border-green-100"><p class="text-sm text-green-600 font-bold uppercase">Valor Total (Ingresos)</p><p class="text-2xl font-bold text-gray-800">\${{ n2(totalValor) }}</p></div>
          }
          <div class="bg-gray-50 p-4 rounded-lg border border-gray-200"><p class="text-sm text-gray-500 font-bold uppercase">Rango Consultado</p><p class="text-sm font-medium text-gray-800 mt-1">{{ dmy(f.fecha_inicio) }} - {{ dmy(f.fecha_fin) }}</p></div>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
          <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3"><i class="fas fa-wrench text-indigo-500 mr-1"></i> Servicios por tipo de mantenimiento</p>
          <div class="grid grid-cols-2 md:grid-cols-7 gap-3">
            @for (c of tarjetas; track c[0]) {
              <div class="p-3 rounded-lg border text-center" [class]="c[2]">
                <p class="text-[11px] font-bold uppercase" [class]="c[3]">{{ c[0] }}</p>
                <p class="text-xl font-extrabold" [class]="c[0] === 'Total' ? 'text-white' : 'text-gray-800'">{{ +(tipos[c[1]] ?? 0) }}</p>
              </div>
            }
          </div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
          <app-dt-arriba [dt]="dt" />
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
              <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                <tr>
                  <th app-th [dt]="dt" campo="fecha_visita" class="py-3 px-4">Fecha</th>
                  <th app-th [dt]="dt" campo="numero_remision" class="py-3 px-4">Remisión</th>
                  <th app-th [dt]="dt" campo="nombre_cliente" class="py-3 px-4">Cliente / Punto</th>
                  <th app-th [dt]="dt" campo="device_id" class="py-3 px-4">Equipo</th>
                  <th class="py-3 px-4">Actividad</th>
                  <th app-th [dt]="dt" campo="valor_servicio" class="py-3 px-4 text-right">Valor</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                @for (r of dt.pagina; track $index) {
                  <tr class="hover:bg-gray-50">
                    <td class="py-3 px-4 whitespace-nowrap">{{ dmy(r.fecha_visita) }}</td>
                    <td class="py-3 px-4 font-mono font-bold text-blue-600">{{ r.numero_remision ?? 'N/A' }}</td>
                    <td class="py-3 px-4"><div class="font-bold text-gray-800">{{ r.nombre_cliente }}</div><div class="text-xs text-gray-500">{{ r.nombre_punto }}</div></td>
                    <td class="py-3 px-4"><div class="text-xs font-mono bg-gray-100 px-2 py-1 rounded inline-block">{{ r.device_id }}</div></td>
                    <td class="py-3 px-4 text-gray-600 italic">{{ ('' + (r.actividades_realizadas ?? '')).substring(0, 50) }}...</td>
                    <td class="py-3 px-4 text-right font-bold text-gray-700">\${{ n2(r.valor_servicio) }}</td>
                  </tr>
                }
              </tbody>
            </table>
          </div>
          <app-dt-abajo [dt]="dt" />
        </div>
      } @else if (mensaje) {
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded"><p class="text-yellow-700">{{ mensaje }}</p></div>
      }
    </div>
  `
})
export class ReporteTecnico implements OnInit {
  private api = inject(Api);
  f: any = { id_tecnico: '' };
  tecnicos: any[] = [];
  festivos: string[] = [];
  dt = new TablaDatos<any>([], 10);
  datosExcel: any[] = [];
  global: any[] = [];
  tipos: any = {};
  totalValor = 0;
  rol = 0;
  mensaje = '';
  cargando = false;
  dmy = dmy;
  n2 = n2;
  tarjetas = [
    ['Preventivo Básico', 'basico', 'bg-sky-50 border-sky-100', 'text-sky-600'],
    ['Preventivo Profundo', 'profundo', 'bg-indigo-50 border-indigo-100', 'text-indigo-600'],
    ['Correctivo', 'correctivo', 'bg-amber-50 border-amber-100', 'text-amber-600'],
    ['Fallido', 'fallido', 'bg-red-50 border-red-100', 'text-red-600'],
    ['Nueva Arquitectura', 'nueva_arquitectura', 'bg-teal-50 border-teal-100', 'text-teal-600'],
    ['Otros', 'otros', 'bg-gray-50 border-gray-200', 'text-gray-500'],
    ['Total', 'total', 'bg-blue-600 border-blue-600', 'text-blue-100']
  ];

  async ngOnInit() {
    this.dt.campoOrden = 'fecha_visita';
    this.dt.ordenAsc = false;
    this.aplicar(await this.api.get('reporteTecnico'));
  }

  private aplicar(r: any) {
    this.f = { ...r.filtros, id_tecnico: r.filtros.id_tecnico || '' };
    this.tecnicos = r.listaTecnicos || [];
    this.festivos = (r.listaFestivos || []).map((x: any) => String(x).substring(0, 10));
    this.rol = Number(r.rolActual) || 0;
    this.dt.set(r.datosReporte || []);
    this.datosExcel = r.datosExcel || [];
    this.global = r.datosServiciosGlobal || [];
    this.tipos = r.resumenTipos || {};
    this.totalValor = Number(r.totalValor) || 0;
    this.mensaje = r.mensaje || '';
  }

  async generar() {
    this.cargando = true;
    try {
      this.aplicar(await this.api.postForm('reporteTecnico', 'index', this.f));
    } finally {
      this.cargando = false;
    }
  }

  pdf() {
    if (!this.f.fecha_inicio || !this.f.fecha_fin) return alert('Selecciona ambas fechas (Desde y Hasta) para generar el PDF.');
    if (this.f.fecha_fin < this.f.fecha_inicio) return alert('La fecha Hasta no puede ser anterior a la fecha Desde.');
    this.api.abrir('reporteTecnico', 'generarPDF', { fecha_inicio: this.f.fecha_inicio, fecha_fin: this.f.fecha_fin, id_tecnico: this.f.id_tecnico || '' });
  }

  // ================================================================ EXCEL (exportarExcelTecnico)
  private horasExtra(he: string, hs: string, fecha: string) {
    const r = { hed: 0, hen: 0, hedf: 0 };
    if (!he || !hs || he === 'null' || hs === 'null') return r;
    const [h1, m1] = he.split(':').map(Number);
    const [h2, m2] = hs.split(':').map(Number);
    const ini = h1 * 60 + m1,
      fin = h2 * 60 + m2;
    const esp = new Date(fecha + 'T00:00:00').getDay() === 0 || this.festivos.includes(fecha);
    if (esp) {
      if (fin - ini > 0) r.hedf = (fin - ini) / 60;
    } else {
      const l17 = 17 * 60,
        l19 = 19 * 60;
      if (fin > l17) {
        const cd = Math.max(ini, l17),
          fd = Math.min(fin, l19);
        if (fd > cd) r.hed = (fd - cd) / 60;
        if (fin > l19) {
          const cn = Math.max(ini, l19);
          if (fin > cn) r.hen = (fin - cn) / 60;
        }
      }
    }
    return r;
  }

  private metaPeriodo(ini: string, fin: string) {
    let meta = 0;
    const a = new Date(ini + 'T00:00:00'),
      b = new Date(fin + 'T00:00:00');
    while (a <= b) {
      const s = a.toISOString().split('T')[0];
      const d = a.getDay();
      if (!this.festivos.includes(s) && d !== 0) meta += d >= 1 && d <= 5 ? 6 : d === 6 ? 3 : 0;
      a.setDate(a.getDate() + 1);
    }
    return meta;
  }

  private formatoServicios(ws: XLSX.WorkSheet) {
    if (!ws['!ref']) return;
    const rg = XLSX.utils.decode_range(ws['!ref']);
    for (let R = rg.s.r + 1; R <= rg.e.r; ++R) {
      const cd = XLSX.utils.encode_cell({ c: 0, r: R });
      if (ws[cd]) {
        const l = String(ws[cd].v || '').replace(/\D/g, '');
        ws[cd].t = 'n';
        ws[cd].v = l !== '' ? Number(l) : 0;
        ws[cd].z = '000000000000';
      }
      const ct = XLSX.utils.encode_cell({ c: 7, r: R });
      if (ws[ct] && ws[ct].f) {
        ws[ct].z = FMT_MONEDA;
        continue;
      }
      if (!ws[ct]) ws[ct] = { t: 'n', v: 0 };
      ws[ct].t = 'n';
      ws[ct].v = parseFloat(ws[ct].v) || 0;
      ws[ct].z = FMT_MONEDA;
    }
  }

  private copiaServicios(wb: XLSX.WorkBook, datos: any[]) {
    if (!datos.length) return;
    const porDel: Record<string, any[]> = {};
    let viat: any = null,
      pT: any = null,
      pF: any = null,
      pS: any = null;
    datos.forEach((d, i) => {
      const del = d.delegacion || 'SIN ASIGNAR';
      porDel[del] ||= [];
      let desp = '';
      if (d.nombre_tecnico === pT && d.fecha_visita === pF && pS) desp = difHoras(pS, d.hora_entrada);
      pT = d.nombre_tecnico;
      pF = d.fecha_visita;
      pS = d.hora_salida;
      const t = String(d.txt_servicio || '').toLowerCase();
      let pb = t.includes('basico') || t.includes('básico') ? 'X' : '';
      const pp = t.includes('profundo') || t.includes('completo') ? 'X' : '';
      const co = t.includes('correctivo') || t.includes('reparacion') ? 'X' : '';
      if (!pb && !pp && !co && t.includes('preventivo')) pb = 'X';
      let dur = d.tiempo_servicio;
      if (!dur || dur === '00:00' || dur === '00:00:00') dur = difHoras(d.hora_entrada, d.hora_salida);
      const vS = parseFloat(d.valor_servicio) || 0,
        vV = parseFloat(d.valor_viaticos) || 0;
      porDel[del].push([d.device_id, d.numero_remision, d.nombre_cliente, d.nombre_punto, pb, pp, co, vS, d.que_se_hizo, del, d.fecha_visita, d.nombre_tecnico, d.nombre_tipo_maquina, d.txt_servicio, d.hora_entrada, d.hora_salida, dur, desp, d.repuestos_texto, d.estado_maquina, d.nombre_calificacion, d.tipo_zona]);
      if (vV > 0) viat = ['', '', '', '', '', '', '', vV, 'TARIFA ADICIONAL POR DÍA', '', '', '', '', '', '', '', '', '', '', '', '', ''];
      const sig = datos[i + 1];
      const cerrar = !sig || sig.fecha_visita !== d.fecha_visita || sig.nombre_tecnico !== d.nombre_tecnico || (sig.delegacion || 'SIN ASIGNAR') !== del;
      if (cerrar && viat) {
        porDel[del].push(viat);
        viat = null;
      }
    });
    for (const del in porDel) {
      const ws = XLSX.utils.aoa_to_sheet([HEAD.slice(), ...porDel[del]]);
      this.formatoServicios(ws);
      ws['!cols'] = ANCHOS.slice();
      XLSX.utils.book_append_sheet(wb, ws, String(del).replace(/[:\\/?*\[\]]/g, '').substring(0, 30) || 'Data');
    }
    const mRes: any[][] = [HEAD.slice()];
    let fila = 2;
    for (const del in porDel)
      porDel[del].forEach((f) => {
        mRes.push(f);
        fila++;
      });
    const tot: any[] = ['', '', '', '', '', '', 'TOTAL GENERAL:', { t: 'n', f: 'SUM(H2:H' + (fila - 1) + ')' }];
    for (let i = 8; i < 22; i++) tot.push('');
    mRes.push(tot);
    const wsR = XLSX.utils.aoa_to_sheet(mRes);
    this.formatoServicios(wsR);
    wsR['!cols'] = ANCHOS.slice();
    XLSX.utils.book_append_sheet(wb, wsR, 'RESUMEN TOTAL');
  }

  excel() {
    try {
      const datos = this.datosExcel;
      if (!datos.length) return alert('No hay datos para exportar.');
      const wb = XLSX.utils.book_new();
      const colTipos = Array.from(new Set(datos.map((i) => i.tipo_mantenimiento || 'SIN ESPECIFICAR'))).sort();
      const resumen: Record<string, any> = {};
      datos.forEach((it) => {
        const tec = it.nombre_tecnico ? String(it.nombre_tecnico).trim() : 'Sin Nombre';
        const fecha = it.fecha_visita ? String(it.fecha_visita).split(' ')[0] : 'Sin Fecha';
        const he = it.hora_entrada,
          hs = it.hora_salida;
        const tipo = it.tipo_mantenimiento || 'SIN ESPECIFICAR';
        if (!resumen[tec]) {
          resumen[tec] = { fechas: {}, contadoresTipos: {}, totalHED: 0, totalHEN: 0, totalHEDF: 0 };
          colTipos.forEach((t) => (resumen[tec].contadoresTipos[t] = 0));
        }
        const ex = this.horasExtra(he, hs, fecha);
        resumen[tec].totalHED += ex.hed;
        resumen[tec].totalHEN += ex.hen;
        resumen[tec].totalHEDF += ex.hedf;
        resumen[tec].fechas[fecha] ||= { cantidad: 0, primera_entrada: '23:59:59', ultima_salida: '00:00:00' };
        const fd = resumen[tec].fechas[fecha];
        if (he && he !== 'null' && he < fd.primera_entrada) fd.primera_entrada = he;
        if (hs && hs !== 'null' && hs > fd.ultima_salida) fd.ultima_salida = hs;
        const up = String(tipo).toUpperCase();
        let peso = 1;
        if (up.includes('FALLID')) peso = 1;
        else if (up.includes('MÁS CAPACITACIÓN') || up.includes('MAS CAPACITACION')) peso = 3;
        else if (up.includes('SIN CAPACITACIÓN') || up.includes('SIN CAPACITACION')) peso = 2;
        else if (up === 'INSTALACIÓN' || up === 'INSTALACION') peso = 2;
        fd.cantidad += peso;
        resumen[tec].contadoresTipos[tipo] += peso;
      });
      const norm = (s: string) => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toUpperCase();
      const tecOrd = Object.keys(resumen).sort((a, b) => {
        let ia = ORDEN.findIndex((c) => norm(a).includes(c));
        let ib = ORDEN.findIndex((c) => norm(b).includes(c));
        if (ia === -1) ia = 999;
        if (ib === -1) ib = 999;
        return ia - ib;
      });

      const mRG: any[][] = [['NOMBRE DEL TÉCNICO', 'TOTAL SERVICIOS', ...colTipos.map((t) => String(t).toUpperCase())]];
      let hay = false;
      tecOrd.forEach((t) => {
        hay = true;
        const c = resumen[t].contadoresTipos;
        mRG.push([t, Object.values(c).reduce((a: any, b: any) => a + b, 0), ...colTipos.map((x) => c[x])]);
      });
      const fall: Record<string, Record<string, number>> = {};
      let gF = 0;
      datos.forEach((it) => {
        if (String(it.tipo_mantenimiento || '').toUpperCase().includes('FALLIDO')) {
          const del = it.delegacion ? String(it.delegacion).toUpperCase() : 'SIN DELEGACIÓN';
          const cli = it.nombre_cliente || 'Sin Cliente';
          fall[del] ||= {};
          fall[del][cli] = (fall[del][cli] || 0) + 1;
          gF++;
        }
      });
      if (gF > 0) {
        mRG.push(['']);
        mRG.push(['']);
        mRG.push(['Cuenta de Cliente', 'Etiquetas de columna']);
        mRG.push(['Etiquetas de fila', 'Fallido']);
        for (const [del, clis] of Object.entries(fall)) {
          mRG.push(['- ' + del, Object.values(clis).reduce((a, b) => a + b, 0)]);
          for (const [cli, n] of Object.entries(clis)) mRG.push(['    ' + cli, n]);
        }
      }
      const wsRG = XLSX.utils.aoa_to_sheet(mRG);
      wsRG['!cols'] = [{ wch: 40 }, { wch: 20 }, ...colTipos.map(() => ({ wch: 20 }))];
      XLSX.utils.book_append_sheet(wb, wsRG, 'RESUMEN GENERAL');

      const meta = this.metaPeriodo(this.f.fecha_inicio, this.f.fecha_fin);
      const mL: any[][] = [['NOMBRE DEL TÉCNICO', 'SERVICIOS * MES', 'PP*1.5', 'MC*1.5', 'F+PB', 'KS + OTROS', 'TOTAL', 'TIME', 'H.E.D', 'H.E.N', 'H.E.D.F']];
      const r1 = (v: number) => Math.round(v * 10) / 10;
      tecOrd.forEach((t) => {
        const d = resumen[t];
        let serv = 0,
          pp = 0,
          mc = 0,
          fpb = 0,
          ks = 0;
        for (const [tipo, cant] of Object.entries(d.contadoresTipos) as [string, number][]) {
          const up = tipo.toUpperCase();
          serv += cant;
          if (up.includes('PROFUNDO')) pp += cant * 1.5;
          else if (up.includes('CORRECTIVO')) mc += cant * 1.5;
          else if (up.includes('FALLIDO') || up.includes('BASICO') || up.includes('BÁSICO')) fpb += cant;
          else ks += cant;
        }
        mL.push([t.toUpperCase(), serv, r1(pp), r1(mc), fpb, ks, r1(pp + mc + fpb + ks), Object.keys(d.fechas).length, r1(d.totalHED), r1(d.totalHEN), r1(d.totalHEDF)]);
      });
      const mes = new Date(this.f.fecha_inicio + 'T00:00:00').toLocaleString('es-ES', { month: 'long' }).toUpperCase();
      mL.push(['']);
      mL.push([`PROMEDIO DE SERVICIOS PARA ${mes} ${meta}`]);
      const wsL = XLSX.utils.aoa_to_sheet(mL);
      if (wsL['!ref']) {
        const rg = XLSX.utils.decode_range(wsL['!ref']);
        for (let R = rg.s.r + 1; R <= rg.e.r; ++R) {
          const c0 = wsL[XLSX.utils.encode_cell({ c: 0, r: R })];
          if (!c0 || String(c0.v || '').includes('PROMEDIO')) continue;
          for (let C = 1; C <= 10; C++) {
            const cel = wsL[XLSX.utils.encode_cell({ c: C, r: R })];
            if (!cel || cel.f) continue;
            cel.t = 'n';
            cel.v = parseFloat(cel.v) || 0;
          }
        }
      }
      wsL['!cols'] = [40, 18, 12, 12, 12, 15, 12, 12, 10, 10, 10].map((wch) => ({ wch }));
      const iP = mL.length - 2;
      wsL['!merges'] = [{ s: { r: iP, c: 0 }, e: { r: iP, c: 10 } }];
      XLSX.utils.book_append_sheet(wb, wsL, 'REPORTE DETALLADO DE SERVICIOS');

      this.copiaServicios(wb, this.global);

      tecOrd.forEach((t) => {
        const fechas = resumen[t].fechas;
        const total = Object.values(fechas).reduce((a: number, b: any) => a + b.cantidad, 0);
        const m: any[][] = [['FECHA', 'PRIMERA ENTRADA', 'ÚLTIMA SALIDA', 'CANTIDAD', 'CALIDAD', 'OTROS', 'KISAN', 'CRP', 'TOTAL SERVICIOS'], [t.toUpperCase(), '', '', '', '', '', '', '', total]];
        Object.keys(fechas)
          .sort()
          .forEach((f) => {
            const x = fechas[f];
            m.push([f, x.primera_entrada === '23:59:59' ? '--' : x.primera_entrada, x.ultima_salida === '00:00:00' ? '--' : x.ultima_salida, x.cantidad, '', '', '', '', x.cantidad]);
          });
        const ws = XLSX.utils.aoa_to_sheet(m);
        ws['!cols'] = [12, 18, 18, 10, 10, 10, 10, 10, 20].map((wch) => ({ wch }));
        ws['!merges'] = [{ s: { r: 1, c: 0 }, e: { r: 1, c: 7 } }];
        XLSX.utils.book_append_sheet(wb, ws, t.replace(/[\\/?*\[\]]/g, '').substring(0, 30) || 'Técnico');
      });

      if (hay) XLSX.writeFile(wb, `Reporte_Técnico_Detallado_${new Date().toISOString().slice(0, 10).replace(/-/g, '')}.xlsx`);
      else alert('No se generaron datos para el reporte.');
    } catch (e: any) {
      console.error('Error al generar Excel:', e);
      alert('Ocurrió un error al generar el Excel: ' + e.message + '\n\nRevisa la consola (F12) para más detalles.');
    }
  }
}
