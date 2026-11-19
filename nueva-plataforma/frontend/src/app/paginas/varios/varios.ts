import { Component, ElementRef, OnDestroy, OnInit, ViewChild, inject } from '@angular/core';
import { DecimalPipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import Chart from 'chart.js/auto';
import * as L from 'leaflet';
import * as XLSX from 'xlsx';
import { Api } from '../../core/api';
import { SelectBuscar } from '../../shared/select-buscar';

// =====================================================================================
// ESTADO DE LA BASE DE DATOS (informacionBaseDatosVista.php)
// =====================================================================================
@Component({
  selector: 'app-informacion-bd',
  imports: [DecimalPipe],
  template: `
    <div class="w-full px-4 md:px-6">
      <div class="mb-8 border-b pb-4">
        <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-database text-purple-600 mr-2"></i> Estado de la Base de Datos</h1>
        <p class="text-gray-500 text-sm">Resumen actual del inventario, máquinas y cobertura.</p>
      </div>
      @if (r) {
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
          <div class="bg-white p-4 rounded-xl shadow border-l-4 border-purple-500"><p class="text-xs text-gray-500 font-bold uppercase">Total Máquinas</p><p class="text-2xl font-extrabold text-purple-700">{{ +r.kpis.total_maquinas | number: '1.0-0' : 'en-US' }}</p></div>
          <div class="bg-white p-4 rounded-xl shadow border-l-4 border-green-500"><p class="text-xs text-gray-500 font-bold uppercase">Activas</p><p class="text-2xl font-extrabold text-green-600">{{ +r.kpis.maquinas_activas | number: '1.0-0' : 'en-US' }}</p></div>
          <div class="bg-white p-4 rounded-xl shadow border-l-4 border-blue-500"><p class="text-xs text-gray-500 font-bold uppercase">Puntos / Sitios</p><p class="text-2xl font-extrabold text-blue-600">{{ +(r.kpis.total_puntos || 0) | number: '1.0-0' : 'en-US' }}</p></div>
          <div class="bg-white p-4 rounded-xl shadow border-l-4 border-gray-500"><p class="text-xs text-gray-500 font-bold uppercase">Delegaciones</p><p class="text-2xl font-extrabold text-gray-600">{{ +(r.kpis.total_delegaciones || 0) | number: '1.0-0' : 'en-US' }}</p></div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
          <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100 lg:col-span-2">
            <h3 class="text-gray-700 font-bold mb-4 border-b pb-2">Distribución de Máquinas por Delegación</h3>
            <div class="relative h-96 w-full"><canvas #cInv></canvas></div>
          </div>
          <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100">
            <h3 class="text-gray-700 font-bold mb-4 border-b pb-2">Top 10 Clientes</h3>
            <div class="overflow-y-auto h-96">
              <table class="min-w-full text-sm">
                <thead class="bg-gray-50 sticky top-0"><tr><th class="px-4 py-2 text-left text-gray-600">Cliente</th><th class="px-4 py-2 text-right text-gray-600">Máq.</th></tr></thead>
                <tbody>
                  @for (c of r.topClientes; track $index) {
                    <tr class="border-b hover:bg-gray-50"><td class="px-4 py-3 text-gray-700 font-medium">{{ c.nombre_cliente }}</td><td class="px-4 py-3 text-right font-bold text-blue-600">{{ c.total }}</td></tr>
                  } @empty {
                    <tr><td colspan="2" class="p-4 text-center text-gray-400">No hay datos de clientes</td></tr>
                  }
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
          <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100">
            <div class="flex justify-between items-center mb-4 border-b pb-2"><h3 class="text-gray-700 font-bold">Actualidad de Visitas</h3><span class="text-xs text-gray-400">Basado en 'ultima_visita'</span></div>
            <div class="relative h-64 w-full flex justify-center"><canvas #cAnt></canvas></div>
            <div class="mt-4 text-xs text-gray-500 text-center">Muestra qué tan recientes son las intervenciones en las máquinas activas.</div>
          </div>
          <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100 flex flex-col justify-center items-center text-center">
            <div class="p-6 bg-blue-50 rounded-full mb-4"><i class="fas fa-server text-4xl text-blue-400"></i></div>
            <h3 class="text-lg font-bold text-gray-700">Estado del Sistema</h3>
            <p class="text-gray-500 mt-2 px-6">La base de datos se actualiza en tiempo real con cada orden de servicio cerrada por los técnicos.</p>
          </div>
        </div>
      }
    </div>
  `
})
export class InformacionBaseDatos implements OnInit, OnDestroy {
  private api = inject(Api);
  @ViewChild('cInv') cInv?: ElementRef<HTMLCanvasElement>;
  @ViewChild('cAnt') cAnt?: ElementRef<HTMLCanvasElement>;
  r: any = null;
  private charts: Chart[] = [];

  async ngOnInit() {
    this.r = await this.api.get('informacionBaseDatos');
    setTimeout(() => this.dibujar());
  }
  ngOnDestroy() {
    this.charts.forEach((c) => c.destroy());
  }
  private dibujar() {
    const inv: any[] = this.r.datosInventario || [];
    if (this.cInv && inv.length) {
      const dels = [...new Set(inv.map((i) => i.nombre_delegacion))];
      const tipos = [...new Set(inv.map((i) => i.nombre_tipo_maquina))];
      const colores = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4'];
      this.charts.push(new Chart(this.cInv.nativeElement, {
        type: 'bar',
        data: {
          labels: dels,
          datasets: tipos.map((t, i) => ({
            label: t,
            data: dels.map((d) => inv.find((x) => x.nombre_delegacion === d && x.nombre_tipo_maquina === t)?.total ?? 0),
            backgroundColor: colores[i % colores.length],
            stack: 'Stack 0'
          }))
        },
        options: { maintainAspectRatio: false, responsive: true, plugins: { legend: { position: 'top' } }, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } } }
      }));
    }
    const ant: any[] = this.r.antiguedad || [];
    if (this.cAnt && ant.length) {
      this.charts.push(new Chart(this.cAnt.nativeElement, {
        type: 'doughnut',
        data: { labels: ant.map((d) => d.rango), datasets: [{ data: ant.map((d) => d.total), backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#9ca3af'], borderWidth: 0 }] },
        options: { maintainAspectRatio: false, responsive: true, plugins: { legend: { position: 'right' } } }
      }));
    }
  }
}

// =====================================================================================
// EXPORTAR REPORTE DE PUNTOS (exportarExcelVista.php)
// =====================================================================================
@Component({
  selector: 'app-exportar-excel',
  imports: [RouterLink],
  template: `
    <div class="w-full max-w-4xl mx-auto">
      <div class="bg-white shadow-lg rounded-lg overflow-hidden border border-gray-200">
        <div class="bg-blue-600 p-6">
          <h2 class="text-2xl font-bold text-white flex items-center gap-2"><i class="fas fa-download"></i> Exportar Reporte de Puntos</h2>
          <p class="text-blue-100 mt-2">Descarga la información filtrada de Puntos con Máquinas en formato Excel (.xlsx).</p>
        </div>
        <div class="p-8">
          <div class="flex justify-center">
            <button (click)="generar()" [disabled]="generando" [class.opacity-50]="generando" class="group relative w-full flex justify-center py-3 px-8 border border-transparent text-lg font-medium rounded-md text-white bg-green-600 hover:bg-green-700 transition-all duration-200 shadow-md transform hover:-translate-y-1 cursor-pointer">
              <span class="absolute left-0 inset-y-0 flex items-center pl-3"><i class="fas fa-file-excel text-green-300 text-xl"></i></span>
              <span>{{ generando ? 'Generando archivo...' : 'GENERAR EXCEL AHORA (.XLSX)' }}</span>
            </button>
          </div>
        </div>
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-between items-center">
          <span class="text-sm text-gray-500">Formato de salida: Excel Original (.xlsx)</span>
          <a routerLink="/inicio" class="text-sm text-blue-600 hover:text-blue-800 hover:underline">&larr; Volver al inicio</a>
        </div>
      </div>
    </div>
  `
})
export class ExportarExcel {
  private api = inject(Api);
  generando = false;

  async generar() {
    this.generando = true;
    try {
      const datos: any[] = await this.api.get('exportarExcel', 'descargarReporte');
      if (!datos.length) {
        alert('No hay datos para exportar.');
        return;
      }
      const filas = datos.map((f) => {
        let fecha: Date | null = null;
        const s = String(f.fecha_ultima_visita || '');
        if (s && !s.startsWith('0000')) {
          const p = s.split(' ')[0].split('-').map((x) => parseInt(x, 10));
          if (p.length === 3 && p[0] > 1990 && !p.some(isNaN)) fecha = new Date(p[0], p[1] - 1, p[2], 12, 0, 0);
        }
        return {
          'Código Cliente': f.codigo_cliente || '',
          Cliente: f.nombre_cliente || '',
          'Nombre del Punto': f.nombre_punto,
          Dirección: f.direccion,
          Municipio: f.nombre_municipio || '',
          Zona: f.zona || '',
          Delegación: f.nombre_delegacion || 'Sin Asignar',
          'ID Dispositivo (Device)': f.device_id,
          'Fecha Última Visita': fecha,
          'Último Mantenimiento': f.nombre_mantenimiento || '',
          'Frecuencia (Días)': f.frecuencia_mantenimiento_dias || '0',
          'Tipo de Máquina': f.nombre_tipo_maquina || ''
        };
      });
      const wb = XLSX.utils.book_new();
      const ws = XLSX.utils.json_to_sheet(filas, { cellDates: true });
      if (ws['!ref']) {
        const range = XLSX.utils.decode_range(ws['!ref']);
        for (let R = range.s.r + 1; R <= range.e.r; ++R) {
          const a = XLSX.utils.encode_cell({ r: R, c: 6 });
          if (ws[a] && ws[a].t === 'd') ws[a].z = 'dd/mm/yyyy';
        }
      }
      ws['!cols'] = [15, 35, 30, 40, 25, 20, 20, 25, 15, 25, 20, 25].map((wch) => ({ wch }));
      XLSX.utils.book_append_sheet(wb, ws, 'Reporte Puntos');
      XLSX.writeFile(wb, 'Reporte_Puntos_' + new Date().toISOString().slice(0, 10) + '.xlsx');
    } catch (e) {
      console.error('Error:', e);
      alert('Hubo un error al generar el Excel.');
    } finally {
      this.generando = false;
    }
  }
}

// =====================================================================================
// GEOLOCALIZAR PUNTOS (geolocalizarControlador.php - lote de prueba)
// =====================================================================================
@Component({
  selector: 'app-geolocalizar',
  template: `
    <div class="w-full max-w-4xl mx-auto bg-white p-6 rounded-xl shadow border border-gray-100">
      @if (cargando) {
        <h2 class="text-xl font-bold text-gray-700"><i class="fas fa-spinner fa-spin mr-2"></i> Iniciando prueba con Google Maps...</h2>
      } @else if (r?.terminado) {
        <h3 class="text-lg font-bold">¡Todos los puntos están listos o llegaron a su límite de intentos fallidos!</h3>
      } @else if (r?.error) {
        <h3 class="text-lg font-bold text-red-600">{{ r.error }}</h3>
      } @else if (r) {
        <h2 class="text-xl font-bold mb-4">Iniciando prueba con Google Maps (Lote de {{ r.limite }})...</h2>
        @for (x of r.resultados; track $index) {
          @if (x.ok) {
            <div><span style="color:green;">✅ Éxito:</span> ID {{ x.id }} | Google entendió: <b>{{ x.texto }}</b></div>
          } @else {
            <div><span style="color:red;">❌ Fallo:</span> ID {{ x.id }} | Error: <b>{{ x.texto }}</b> (Buscó: {{ x.busqueda }})</div>
          }
        }
        <h3 class="text-lg font-bold mt-4">Prueba de {{ r.limite }} registros terminada.</h3>
        <button (click)="ejecutar()" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-blue-700"><i class="fas fa-redo mr-1"></i> Procesar otro lote</button>
      }
    </div>
  `
})
export class Geolocalizar implements OnInit {
  private api = inject(Api);
  r: any = null;
  cargando = false;

  ngOnInit() {
    this.ejecutar();
  }
  async ejecutar() {
    this.cargando = true;
    try {
      this.r = await this.api.get('geolocalizar');
    } finally {
      this.cargando = false;
    }
  }
}

// =====================================================================================
// RASTREO DE TÉCNICOS (rastreoTecnicoVista.php)
// =====================================================================================
const COLORES = ['#2563eb', '#dc2626', '#16a34a', '#d97706', '#7c3aed', '#db2777', '#0891b2', '#ea580c'];
function icono(color: string) {
  return L.icon({
    iconUrl: `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-${color}.png`,
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
    iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
  });
}
const esc = (s: any) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[c]!);

@Component({
  selector: 'app-rastreo-tecnico',
  imports: [FormsModule, SelectBuscar],
  styles: [`
    .filtros-container { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; background: #fff; padding: 1rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,.05); margin-bottom: 1rem; }
    .form-group label { display: block; font-size: .8rem; font-weight: 700; color: #475569; margin-bottom: .25rem; }
    .form-control { border: 1px solid #cbd5e1; border-radius: 6px; padding: .5rem .75rem; }
    .btn-buscar { background: #2563eb; color: #fff; font-weight: 700; padding: .55rem 1rem; border-radius: 6px; }
    .btn-excel { background: #16a34a; color: #fff; font-weight: 700; padding: .55rem 1rem; border-radius: 6px; }
    #mapaRastreo { height: 70vh; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,.05); z-index: 0; }
    :host ::ng-deep .popup-custom p { margin: 3px 0; }
    :host ::ng-deep .badge-estado { color: #fff; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 4px; }
    :host ::ng-deep .bg-verde { background: #16a34a; } :host ::ng-deep .bg-rojo { background: #dc2626; }
  `],
  template: `
    <div class="rastreo-wrapper">
      <div class="filtros-container">
        <div class="form-group"><label>Fecha del recorrido</label><input type="date" [(ngModel)]="fecha" class="form-control"></div>
        <div class="form-group" style="flex-grow: 1;"><label>Técnico</label><app-select-buscar [opciones]="tecnicos" valor="id_tecnico" texto="nombre_tecnico" [permitirVacio]="false" [limpiable]="false" [(ngModel)]="idTecnico" /></div>
        <button class="btn-buscar" (click)="cargarRuta()"><i class="fas fa-search"></i> Trazar Ruta</button>
        <button class="btn-excel" [disabled]="generando" (click)="excelPrimerServicio()"><i class="fas fa-file-excel"></i> {{ generando ? 'Generando...' : 'Excel Primer Servicio' }}</button>
      </div>
      @if (resumen.length) {
        <div style="background: #fff; padding: 1rem; margin-bottom: 1rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #16a34a;">
          <h4 style="margin: 0; color: #1e3a5f; font-size: 1.1rem;"><i class="fas fa-route"></i> Resumen de Ruta</h4>
          <div style="margin-top: 0.5rem; font-size: 1rem; color: #475569;">
            @for (x of resumen; track x.tec) { <div style="margin-bottom: 5px;"><strong>{{ x.tec }}:</strong> {{ x.km }} km</div> }
            @if (total !== null) { <hr style="margin: 8px 0; border: 0; border-top: 1px solid #e2e8f0;"><div><strong>Total Recorrido (Todos):</strong> {{ total }} km</div> }
          </div>
        </div>
      }
      <div id="mapaRastreo" #mapa></div>
    </div>
  `
})
export class RastreoTecnico implements OnInit, OnDestroy {
  private api = inject(Api);
  @ViewChild('mapa', { static: true }) mapaEl!: ElementRef<HTMLDivElement>;
  tecnicos: any[] = [];
  idTecnico: any = 'todos';
  fecha = '';
  resumen: { tec: string; km: string }[] = [];
  total: string | null = null;
  generando = false;
  private mapa?: L.Map;
  private marcadores = L.layerGroup();
  private lineas = L.layerGroup();
  private ultimaRuta: any[] = [];
  private ultimaFecha = '';

  async ngOnInit() {
    this.mapa = L.map(this.mapaEl.nativeElement).setView([4.6097, -74.0817], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap contributors', maxZoom: 19 }).addTo(this.mapa);
    this.marcadores.addTo(this.mapa);
    this.lineas.addTo(this.mapa);
    const r: any = await this.api.get('rastreoTecnico');
    this.fecha = r.hoy;
    this.tecnicos = [{ id_tecnico: 'todos', nombre_tecnico: '-- TODOS LOS TÉCNICOS --' }, ...(r.tecnicos || [])];
  }
  ngOnDestroy() {
    this.mapa?.remove();
  }
  async cargarRuta() {
    if (!this.idTecnico) {
      alert('Por favor seleccione un técnico.');
      return;
    }
    this.marcadores.clearLayers();
    this.lineas.clearLayers();
    try {
      const res: any = await this.api.postForm('rastreoTecnico', 'ajaxObtenerRuta', { id_tecnico: this.idTecnico, fecha: this.fecha });
      if (res.success) {
        this.ultimaRuta = res.data || [];
        this.ultimaFecha = this.fecha;
        this.dibujar(res.data);
      } else {
        alert(res.msj);
        this.resumen = [];
        this.mapa?.setView([4.6097, -74.0817], 12);
      }
    } catch {
      alert('Error de conexión al obtener la ruta.');
    }
  }
  private dibujar(datos: any[]) {
    const limites = L.latLngBounds([]);
    const porTec: Record<string, L.LatLng[]> = {};
    datos.forEach((s, i) => {
      const n = i + 1, tec = s.nombre_tecnico;
      porTec[tec] ??= [];
      if (s.latitud_inicio && s.longitud_inicio) {
        const ll = L.latLng(Number(s.latitud_inicio), Number(s.longitud_inicio));
        porTec[tec].push(ll);
        limites.extend(ll);
        L.marker(ll, { icon: icono('green') })
          .bindPopup(`<div class="popup-custom"><strong>#${n} - ${esc(s.nombre_cliente)}</strong><p><i class="fas fa-user-cog"></i> <b>${esc(tec)}</b></p><p><i class="fas fa-store"></i> ${esc(s.nombre_punto)}</p><p><span class="badge-estado bg-verde">INICIO SERVICIO</span></p><p><i class="far fa-clock"></i> Hora entrada: ${esc(s.hora_entrada || 'N/A')}</p></div>`)
          .bindTooltip(esc(tec), { className: 'tec-tooltip', direction: 'top', offset: [0, -40] })
          .addTo(this.marcadores);
      }
      if (s.latitud_fin && s.longitud_fin) {
        const ll = L.latLng(Number(s.latitud_fin), Number(s.longitud_fin) + 0.0001);
        porTec[tec].push(ll);
        limites.extend(ll);
        L.marker(ll, { icon: icono('red') })
          .bindPopup(`<div class="popup-custom"><strong>#${n} - ${esc(s.nombre_cliente)}</strong><p><i class="fas fa-user-cog"></i> <b>${esc(tec)}</b></p><p><i class="fas fa-store"></i> ${esc(s.nombre_punto)}</p><p><span class="badge-estado bg-rojo">FIN SERVICIO</span></p><p><i class="far fa-clock"></i> Hora salida: ${esc(s.hora_salida || 'N/A')}</p></div>`)
          .bindTooltip(esc(tec), { className: 'tec-tooltip', direction: 'top', offset: [0, -40] })
          .addTo(this.marcadores);
      }
    });
    let colorIdx = 0, totalKm = 0;
    this.resumen = [];
    Object.entries(porTec).forEach(([tec, coords]) => {
      let metros = 0;
      if (coords.length > 1) {
        const color = COLORES[colorIdx % COLORES.length];
        for (let i = 0; i < coords.length - 1; i++) {
          const d = coords[i].distanceTo(coords[i + 1]);
          metros += d;
          L.polyline([coords[i], coords[i + 1]], { color, weight: 4, opacity: 0.8, dashArray: '10, 10' })
            .bindTooltip(`<b>Tramo:</b> ${(d / 1000).toFixed(2)} km`, { sticky: true, className: 'tramo-tooltip' })
            .addTo(this.lineas);
        }
        colorIdx++;
      }
      const km = (metros / 1000).toFixed(2);
      totalKm += parseFloat(km);
      this.resumen.push({ tec, km });
    });
    this.total = this.resumen.length && this.idTecnico === 'todos' ? totalKm.toFixed(2) : null;
    if (this.resumen.length && limites.isValid()) this.mapa?.fitBounds(limites, { padding: [50, 50] });
  }
  excelPrimerServicio() {
    if (!this.ultimaRuta.length) {
      alert("Primero trace una ruta con el botón 'Trazar Ruta'.");
      return;
    }
    this.generando = true;
    try {
      const porTec: Record<string, any[]> = {};
      this.ultimaRuta.forEach((s) => {
        const k = s.id_tecnico || s.nombre_tecnico || 'SIN_TECNICO';
        (porTec[k] ??= []).push(s);
      });
      const minutos = (h: any) => {
        if (!h) return 99999;
        const [hh, mm] = String(h).substring(0, 5).split(':').map((x) => parseInt(x, 10));
        return isNaN(hh) || isNaN(mm) ? 99999 : hh * 60 + mm;
      };
      const fila = (s: any) => ({
        Fecha: this.ultimaFecha || s.fecha_visita || '',
        Técnico: s.nombre_tecnico || '',
        Cliente: s.nombre_cliente || '',
        Punto: s.nombre_punto || '',
        'Hora Llegada': s.hora_entrada ? String(s.hora_entrada).substring(0, 5) : '',
        Observaciones: ''
      });
      const resumen: any[] = [], detalle: any[] = [];
      Object.keys(porTec).sort().forEach((k) => {
        const servicios = porTec[k].slice().sort((a, b) => minutos(a.hora_entrada) - minutos(b.hora_entrada));
        servicios.forEach((s) => detalle.push(fila(s)));
        const primero = servicios.find((s) => s.hora_entrada) || servicios[0];
        if (primero) resumen.push(fila(primero));
      });
      if (!resumen.length) {
        alert('No hay horas de entrada registradas para exportar.');
        return;
      }
      const wb = XLSX.utils.book_new();
      const anchos = [12, 30, 30, 30, 14, 40].map((wch) => ({ wch }));
      const wsR = XLSX.utils.json_to_sheet(resumen), wsD = XLSX.utils.json_to_sheet(detalle);
      wsR['!cols'] = anchos;
      wsD['!cols'] = anchos;
      XLSX.utils.book_append_sheet(wb, wsR, 'Primer Servicio');
      XLSX.utils.book_append_sheet(wb, wsD, 'Detalle');
      XLSX.writeFile(wb, 'Primer_Servicio_' + (this.ultimaFecha || new Date().toISOString().slice(0, 10)) + '.xlsx');
    } catch (e) {
      console.error('Error al generar Excel:', e);
      alert('Hubo un error al generar el Excel.');
    } finally {
      this.generando = false;
    }
  }
}
