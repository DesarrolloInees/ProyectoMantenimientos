import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import * as XLSX from 'xlsx';
import { Api } from '../../core/api';

const ENCABEZADOS = [
  'Device_id', 'Número de Remisión', 'Cliente', 'Nombre Punto',
  'Preventivo Básico', 'Preventivo Profundo', 'Correctivo', 'Tarifa',
  'Observaciones', 'Delegación', 'Fecha', 'Técnico',
  'Tipo de Máquina', 'Tipo de Servicio', 'Hora Entrada', 'Hora Salida',
  'Duración', 'Desplazamiento', 'Repuestos', 'Estado', 'Calificación', 'Modalidad'
];
const ANCHOS = [15, 12, 25, 25, 8, 8, 8, 15, 50, 15, 12, 20, 15, 20, 10, 10, 10, 12, 30, 15, 15, 15].map((wch) => ({ wch }));
const FORMATO_MONEDA = '_-"$"* #,##0_-;-"$"* #,##0_-;-"$"* "-"??_-;-_-@_-';

function fechaLocal(d: Date) {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function calcularDiferenciaHoras(ini: string, fin: string): string {
  if (!ini || !fin) return '00:00';
  const d1 = new Date(`2000-01-01T${ini}`);
  const d2 = new Date(`2000-01-01T${fin}`);
  if (isNaN(d1.getTime()) || isNaN(d2.getTime())) return '00:00';
  const diff = d2.getTime() - d1.getTime();
  if (diff < 0) return 'Err';
  const mins = Math.floor(diff / 60000);
  return `${String(Math.floor(mins / 60)).padStart(2, '0')}:${String(mins % 60).padStart(2, '0')}`;
}

/** Port de ordenReporteVista.php (Centro de Reportes: Excel de servicios y novedades) */
@Component({
  selector: 'app-orden-reporte',
  imports: [FormsModule, RouterLink],
  template: `
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-xl mt-10">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">📊 Centro de Reportes</h1>
        <p class="text-gray-500 mt-2">Selecciona un rango de fechas y elige qué tipo de informe necesitas.</p>
      </div>
      <div class="space-y-6 border p-6 rounded bg-gray-50">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Fecha Inicio:</label>
            <input type="date" [(ngModel)]="fechaInicio" class="w-full p-3 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none" />
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Fecha Fin:</label>
            <input type="date" [(ngModel)]="fechaFin" class="w-full p-3 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none" />
          </div>
        </div>
        <div class="mt-6">
          <label class="block text-sm font-bold text-gray-700 mb-2">Filtro de Mantenimiento (Aplica para Reporte de Servicios):</label>
          <select [(ngModel)]="filtro" class="w-full p-3 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
            <option value="todos">Todos los servicios</option>
            <option value="basico">Solo Preventivos Básicos</option>
            <option value="profundo">Solo Preventivos Profundos</option>
            <option value="correctivo">Solo Correctivos</option>
            <option value="fallido">Solo Fallidos</option>
            <option value="garantia">Solo Garantía</option>
          </select>
        </div>
        <hr class="border-gray-200" />
        <div class="flex flex-col md:flex-row justify-center gap-6 mt-6">
          <button type="button" [disabled]="!!cargando" (click)="descargar('servicios')" [class.opacity-50]="cargando === 'novedades'" [class.opacity-75]="cargando === 'servicios'" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-6 rounded-lg shadow-md transform hover:scale-105 transition flex flex-col items-center justify-center gap-2">
            @if (cargando === 'servicios') {
              <i class="fas fa-spinner fa-spin text-2xl"></i><span>Generando...</span>
            } @else {
              <i class="fas fa-tools text-2xl"></i><span>Reporte de Servicios</span><span class="text-xs font-normal opacity-80">(Por Delegaciones)</span>
            }
          </button>
          <button type="button" [disabled]="!!cargando" (click)="descargar('novedades')" [class.opacity-50]="cargando === 'servicios'" [class.opacity-75]="cargando === 'novedades'" class="flex-1 bg-orange-600 hover:bg-orange-700 text-white font-bold py-4 px-6 rounded-lg shadow-md transform hover:scale-105 transition flex flex-col items-center justify-center gap-2">
            @if (cargando === 'novedades') {
              <i class="fas fa-spinner fa-spin text-2xl"></i><span>Generando...</span>
            } @else {
              <i class="fas fa-exclamation-triangle text-2xl"></i><span>Reporte de Novedades</span><span class="text-xs font-normal opacity-80">(Solo incidencias)</span>
            }
          </button>
        </div>
        <div class="text-center mt-4">
          <a routerLink="/inicio" class="text-gray-500 hover:text-gray-700 underline text-sm"><i class="fas fa-arrow-left mr-1"></i> Volver al inicio</a>
        </div>
      </div>
      @if (mensaje) {
        <div class="mt-4 p-4 rounded text-center font-bold" [class]="mensajeOk ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">{{ mensaje }}</div>
      }
    </div>
  `
})
export class OrdenReporte {
  private api = inject(Api);
  fechaInicio = fechaLocal(new Date()).substring(0, 8) + '01';
  fechaFin = fechaLocal(new Date());
  filtro = 'todos';
  cargando: '' | 'servicios' | 'novedades' = '';
  mensaje = '';
  mensajeOk = false;

  async descargar(tipo: 'servicios' | 'novedades') {
    const accion = tipo === 'servicios' ? 'ajaxDescargarServicios' : 'ajaxDescargarNovedades';
    const ini = this.fechaInicio;
    const fin = this.fechaFin;
    this.cargando = tipo;
    this.mensaje = '';
    try {
      const r: any = await this.api.postForm('ordenReporte', accion, { fecha_inicio: ini, fecha_fin: fin, tipo_mantenimiento: this.filtro });
      if (r.status === 'ok') {
        if (r.datos.length === 0) {
          this.mostrar('No se encontraron registros en este rango.', false);
        } else {
          if (tipo === 'servicios') this.generarExcelServicios(r.datos, ini, fin);
          else this.generarExcelNovedades(r.datos, ini, fin);
          this.mostrar('¡Descarga iniciada! (' + r.datos.length + ' registros)', true);
        }
      } else {
        this.mostrar(r.msg, false);
      }
    } catch (e) {
      console.error(e);
      this.mostrar('Error de conexión.', false);
    } finally {
      this.cargando = '';
    }
  }

  private mostrar(m: string, ok: boolean) {
    this.mensaje = m;
    this.mensajeOk = ok;
  }

  private formatearHoja(ws: XLSX.WorkSheet, rellenarTarifa: boolean) {
    if (!ws['!ref']) return;
    const range = XLSX.utils.decode_range(ws['!ref']);
    for (let R = range.s.r + 1; R <= range.e.r; ++R) {
      const cd = XLSX.utils.encode_cell({ c: 0, r: R });
      if (ws[cd]) {
        const limpio = String(ws[cd].v || '').replace(/\D/g, '');
        ws[cd].t = 'n';
        ws[cd].v = limpio !== '' ? Number(limpio) : 0;
        ws[cd].z = '000000000000';
      }
      const ct = XLSX.utils.encode_cell({ c: 7, r: R });
      if (rellenarTarifa) {
        if (!ws[ct]) ws[ct] = { t: 'n', v: 0 };
        ws[ct].t = 'n';
        ws[ct].z = FORMATO_MONEDA;
      } else if (ws[ct]) {
        ws[ct].z = FORMATO_MONEDA;
      }
    }
  }

  private generarExcelServicios(datos: any[], inicio: string, fin: string) {
    const wb = XLSX.utils.book_new();
    const porDel: Record<string, any[]> = {};
    let viaticoPendiente: any = null;
    let prevTec: any = null;
    let prevFecha: any = null;
    let prevSalida: any = null;

    datos.forEach((d, index) => {
      const del = d.delegacion || 'SIN ASIGNAR';
      (porDel[del] ||= []);
      let desplazamiento = '';
      if (d.nombre_tecnico === prevTec && d.fecha_visita === prevFecha && prevSalida) desplazamiento = calcularDiferenciaHoras(prevSalida, d.hora_entrada);
      prevTec = d.nombre_tecnico;
      prevFecha = d.fecha_visita;
      prevSalida = d.hora_salida;

      const txt = String(d.txt_servicio || '').toLowerCase();
      let basico = txt.includes('basico') || txt.includes('básico') ? 'X' : '';
      const profundo = txt.includes('profundo') || txt.includes('completo') ? 'X' : '';
      const correctivo = txt.includes('correctivo') || txt.includes('reparacion') ? 'X' : '';
      if (!basico && !profundo && !correctivo && txt.includes('preventivo')) basico = 'X';

      let duracion = d.tiempo_servicio;
      if (!duracion || duracion === '00:00' || duracion === '00:00:00') duracion = calcularDiferenciaHoras(d.hora_entrada, d.hora_salida);
      const valorServicio = parseFloat(d.valor_servicio) || 0;
      const valorViaticos = parseFloat(d.valor_viaticos) || 0;

      porDel[del].push([
        d.device_id, d.numero_remision, d.nombre_cliente, d.nombre_punto,
        basico, profundo, correctivo, valorServicio,
        d.que_se_hizo, del, d.fecha_visita, d.nombre_tecnico,
        d.nombre_tipo_maquina, d.txt_servicio, d.hora_entrada, d.hora_salida,
        duracion, desplazamiento, d.repuestos_texto, d.estado_maquina, d.nombre_calificacion, d.tipo_zona
      ]);
      if (valorViaticos > 0) {
        viaticoPendiente = ['', '', '', '', '', '', '', valorViaticos, 'TARIFA ADICIONAL POR DÍA', '', '', '', '', '', '', '', '', '', '', '', '', ''];
      }
      const sig = datos[index + 1];
      const cerrar = !sig || sig.fecha_visita !== d.fecha_visita || sig.nombre_tecnico !== d.nombre_tecnico || (sig.delegacion || 'SIN ASIGNAR') !== del;
      if (cerrar && viaticoPendiente) {
        porDel[del].push(viaticoPendiente);
        viaticoPendiente = null;
      }
    });

    for (const del in porDel) {
      const ws = XLSX.utils.aoa_to_sheet([ENCABEZADOS, ...porDel[del]]);
      this.formatearHoja(ws, true);
      ws['!cols'] = ANCHOS;
      const nombre = del.replace(/[:\\/?*\[\]]/g, '').substring(0, 30) || 'Data';
      XLSX.utils.book_append_sheet(wb, ws, nombre);
    }

    const resumen: any[][] = [ENCABEZADOS];
    let filaActual = 2;
    for (const del in porDel) {
      porDel[del].forEach((f) => {
        resumen.push(f);
        filaActual++;
      });
    }
    const total: any[] = ['', '', '', '', '', '', 'TOTAL GENERAL:', { t: 'n', f: 'SUM(H2:H' + (filaActual - 1) + ')' }];
    for (let i = 8; i < 22; i++) total.push('');
    resumen.push(total);
    const wsR = XLSX.utils.aoa_to_sheet(resumen);
    this.formatearHoja(wsR, false);
    wsR['!cols'] = ANCHOS;
    XLSX.utils.book_append_sheet(wb, wsR, 'RESUMEN TOTAL');
    XLSX.writeFile(wb, `Reporte_Servicios_${inicio}_a_${fin}.xlsx`);
  }

  private generarExcelNovedades(datos: any[], inicio: string, fin: string) {
    const lista = datos.map((d) => {
      const num = d.device_id !== null && d.device_id !== undefined ? String(d.device_id).replace(/\D/g, '') : '';
      return {
        'Tipo de Novedad': d.nombre_novedad,
        'Descripción del Servicio': d.observacion,
        Cliente: d.nombre_cliente,
        Punto: d.nombre_punto,
        Delegación: d.delegacion,
        'Tipo de Máquina': d.nombre_tipo_maquina,
        Device_id: num !== '' ? Number(num) : 0,
        'Número de Remisión': d.numero_remision,
        'Fecha del Servicio': d.fecha_visita,
        'Nombre del Técnico': d.nombre_tecnico
      } as Record<string, any>;
    });
    if (lista.length === 0) {
      alert('No hay datos para exportar.');
      return;
    }
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.json_to_sheet(lista);
    const range = XLSX.utils.decode_range(ws['!ref'] as string);
    for (let R = range.s.r + 1; R <= range.e.r; ++R) {
      const ref = XLSX.utils.encode_cell({ r: R, c: 6 });
      if (ws[ref]) {
        ws[ref].t = 'n';
        ws[ref].z = '000000000000';
      }
    }
    ws['!cols'] = Object.keys(lista[0]).map((key) => {
      let max = key.length;
      lista.forEach((row) => {
        const v = row[key] ? String(row[key]) : '';
        if (v.length > max) max = v.length;
      });
      if (max > 70) max = 70;
      return { wch: max + 2 };
    });
    XLSX.utils.book_append_sheet(wb, ws, 'Novedades');
    XLSX.writeFile(wb, `Novedades_${inicio}_a_${fin}.xlsx`);
  }
}
