import { Component, OnInit, inject } from '@angular/core';
import { Location } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { Api } from '../../core/api';
import { SelectBuscar } from '../../shared/select-buscar';
import { DtAbajo, DtArriba, TablaDatos } from '../../shared/tabla';

const dos = (n: number) => (n < 10 ? '0' + n : '' + n);
const urlArchivo = (ruta: string) => (/^(https?:)?\//.test(ruta || '') ? ruta : '/' + String(ruta || ''));
function dmy(f: any) {
  const s = String(f || '').substring(0, 10).split('-');
  return s.length === 3 ? `${s[2]}/${s[1]}/${s[0]}` : '';
}
const hm = (t: any) => String(t ?? '').substring(0, 5);
function hora12(t: any) {
  const [h0, m] = String(t ?? '').split(':').map(Number);
  if (isNaN(h0)) return '';
  return `${dos(h0 % 12 || 12)}:${dos(m || 0)} ${h0 >= 12 ? 'PM' : 'AM'}`;
}
/** number_format($v, 2, ',', '.') */
function pesos(v: any) {
  const [e, d] = (Number(v) || 0).toFixed(2).split('.');
  return e.replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ',' + d;
}

// =====================================================================================
// REGISTRAR PARQUEADERO — TÉCNICO (parqueaderoCrearVista.php)
// =====================================================================================
@Component({
  selector: 'app-parqueadero-crear',
  imports: [FormsModule, SelectBuscar],
  template: `
    <div class="bg-blue-800 text-white p-4 shadow-md sticky top-0 z-40 flex items-center gap-3 -mx-4 md:mx-0 md:rounded-lg">
      <button (click)="volver()" class="text-white bg-blue-700 hover:bg-blue-600 p-2 rounded-full w-10 h-10 flex items-center justify-center transition"><i class="fas fa-arrow-left"></i></button>
      <div><h1 class="font-bold text-lg leading-tight">Registrar Parqueadero</h1><p class="text-blue-200 text-xs">Subida de facturas</p></div>
    </div>
    @if (error) {
      <div class="max-w-lg mx-auto p-3 mt-4"><div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded">{{ error }}</div></div>
    }
    @if (d && !error) {
      <div class="max-w-lg mx-auto p-3 space-y-4 mt-2 mb-24">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 space-y-5">
          <div class="flex items-center gap-2 mb-1 border-b pb-2"><i class="fas fa-parking text-blue-500 text-lg"></i><h2 class="font-bold text-gray-700 text-sm uppercase">Datos de la Factura</h2></div>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha <span class="text-red-500">*</span></label><input type="date" [(ngModel)]="f.fecha_servicio" class="w-full bg-white border border-gray-300 rounded-lg p-3 text-gray-800 font-bold shadow-sm outline-none focus:border-blue-500"></div>
            <div class="grid grid-cols-2 gap-3">
              <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1">Hora Inicio <span class="text-red-500">*</span></label><input type="time" [(ngModel)]="f.hora_inicio" class="w-full bg-white border border-gray-300 rounded-lg p-2 text-sm text-gray-800 font-bold shadow-sm outline-none focus:border-blue-500"></div>
              <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1">Hora Fin <span class="text-red-500">*</span></label><input type="time" [(ngModel)]="f.hora_fin" class="w-full bg-white border border-gray-300 rounded-lg p-2 text-sm text-gray-800 font-bold shadow-sm outline-none focus:border-blue-500"></div>
            </div>
          </div>
          <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1">Punto Visitado <span class="text-red-500">*</span></label><app-select-buscar [opciones]="d.puntos" valor="id_punto" texto="nombre_punto" placeholder="- Seleccione el Punto -" [(ngModel)]="f.id_punto" /></div>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1">Valor ($) <span class="text-red-500">*</span></label><input type="number" [(ngModel)]="f.valor_factura" step="0.01" min="0" placeholder="Ej: 15000" class="w-full bg-white border border-gray-300 rounded-lg p-3 text-gray-800 shadow-sm outline-none focus:border-blue-500"></div>
            <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1">N° Factura <span class="text-red-500">*</span></label><input type="text" [(ngModel)]="f.numero_factura" placeholder="Ej: A-12345" class="w-full bg-white border border-gray-300 rounded-lg p-3 text-gray-800 shadow-sm outline-none focus:border-blue-500"></div>
          </div>
          <div class="bg-gray-50 rounded-xl shadow-sm border border-gray-200 p-4 mt-4">
            <div class="flex items-center gap-2 mb-2 border-b pb-2"><i class="fas fa-camera text-indigo-500 text-lg"></i><h2 class="font-bold text-gray-700 text-sm uppercase">Adjuntar Foto <span class="text-red-500">*</span></h2></div>
            <div class="border border-dashed border-gray-300 rounded-lg p-4 bg-white text-center transition hover:bg-gray-100 relative">
              <i class="fas fa-file-invoice-dollar text-gray-400 text-3xl mb-2"></i>
              <p class="text-sm font-bold text-gray-700">Toca para cargar la foto</p>
              @if (foto) { <span class="inline-block bg-green-100 text-green-700 px-3 py-1 mt-2 rounded-full text-xs font-bold relative z-10">¡Foto seleccionada!</span> }
              <input type="file" accept="image/*" (change)="elegirFoto($event)" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20">
            </div>
            @if (preview) { <div class="mt-3 text-center"><img [src]="preview" class="max-h-48 mx-auto rounded-lg border border-gray-300 shadow-sm" alt="Vista previa"></div> }
          </div>
        </div>
      </div>
      <div class="fixed bottom-0 left-0 w-full bg-white shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.1)] p-4 z-40 border-t border-gray-200">
        <button type="button" (click)="enviar()" [disabled]="enviando" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl shadow-lg transform active:scale-95 transition text-lg flex items-center justify-center gap-2"><i class="fas fa-cloud-upload-alt"></i> SUBIR FACTURA</button>
      </div>
    }
  `
})
export class ParqueaderoCrear implements OnInit {
  private api = inject(Api);
  private router = inject(Router);
  private location = inject(Location);
  d: any = null;
  error = '';
  f: any = { fecha_servicio: '', hora_inicio: '', hora_fin: '', id_punto: null, valor_factura: '', numero_factura: '' };
  foto: File | null = null;
  preview = '';
  enviando = false;

  async ngOnInit() {
    const r: any = await this.api.get('parqueaderoCrear');
    if (r.error) {
      alert(r.error);
      this.error = r.error;
      return;
    }
    this.d = r;
    this.f.fecha_servicio = r.hoy;
  }
  volver() {
    this.location.back();
  }
  elegirFoto(ev: Event) {
    const file = (ev.target as HTMLInputElement).files?.[0] || null;
    this.foto = file;
    this.preview = '';
    if (file) {
      const rd = new FileReader();
      rd.onload = () => (this.preview = String(rd.result));
      rd.readAsDataURL(file);
    }
  }
  async enviar() {
    if (!this.f.id_punto || !this.f.valor_factura || !this.f.numero_factura || !this.f.hora_fin || !this.foto) {
      alert('⚠️ Por favor, completa todos los campos obligatorios y adjunta la foto.');
      return;
    }
    const fd = new FormData();
    Object.entries(this.f).forEach(([k, v]) => fd.append(k, v === null || v === undefined ? '' : String(v)));
    fd.append('foto_factura', this.foto);
    this.enviando = true;
    try {
      const r: any = await this.api.post('parqueaderoCrear', 'guardar', fd);
      alert(r.msj);
      if (r.success) this.router.navigate(['/parqueaderoHistorial']);
    } finally {
      this.enviando = false;
    }
  }
}

// =====================================================================================
// MIS PARQUEADEROS — TÉCNICO (parqueaderoHistorialVista.php)
// =====================================================================================
@Component({
  selector: 'app-parqueadero-historial',
  imports: [FormsModule, RouterLink, SelectBuscar],
  template: `
    <div class="bg-blue-800 text-white p-4 shadow-md sticky top-0 z-40 flex items-center justify-between gap-3 -mx-4 md:mx-0 md:rounded-lg">
      <div class="flex items-center gap-3">
        <button (click)="volver()" class="text-white bg-blue-700 hover:bg-blue-600 p-2 rounded-full w-10 h-10 flex items-center justify-center transition"><i class="fas fa-arrow-left"></i></button>
        <div><h1 class="font-bold text-lg leading-tight">Mis Parqueaderos</h1><p class="text-blue-200 text-xs">Historial de facturas subidas</p></div>
      </div>
      <a routerLink="/parqueaderoCrear" class="bg-green-500 hover:bg-green-600 text-white p-2 rounded-full w-10 h-10 flex items-center justify-center transition shadow"><i class="fas fa-plus"></i></a>
    </div>
    <div class="max-w-lg mx-auto p-3 mt-2 space-y-4">
      @if (error) {
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded">{{ error }}</div>
      } @else if (cargado && !facturas.length) {
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center mt-10"><i class="fas fa-receipt text-gray-300 text-5xl mb-3"></i><h2 class="text-gray-500 font-bold text-lg">Sin registros</h2><p class="text-gray-400 text-sm mt-1">Aún no has subido ninguna factura de parqueadero.</p></div>
      }
      @for (fac of facturas; track fac.id_factura_parqueadero) {
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gray-50 px-4 py-2 border-b border-gray-100 flex justify-between items-center">
            <span class="text-xs font-bold text-gray-500"><i class="far fa-calendar-alt mr-1"></i> {{ dmy(fac.fecha_servicio) }}</span>
            <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-[10px] font-bold uppercase">Factura: {{ fac.numero_factura }}</span>
          </div>
          <div class="p-4 space-y-3">
            <h3 class="font-bold text-gray-800 text-sm"><i class="fas fa-map-marker-alt text-red-500 mr-1"></i> {{ fac.nombre_punto }}</h3>
            <div class="grid grid-cols-2 gap-2 text-xs">
              <div class="bg-gray-50 p-2 rounded border border-gray-100"><span class="block text-gray-400 uppercase font-bold text-[9px]">Horario</span><span class="text-gray-700 font-semibold">{{ hora12(fac.hora_inicio) }} - {{ hora12(fac.hora_fin) }}</span></div>
              <div class="bg-green-50 p-2 rounded border border-green-100 text-right"><span class="block text-green-500 uppercase font-bold text-[9px]">Valor Pagado</span><span class="text-green-700 font-bold text-sm">\${{ pesos(fac.valor_factura) }}</span></div>
            </div>
            <div class="grid grid-cols-2 gap-2">
              <button type="button" (click)="foto = { url: url(fac.ruta_foto), titulo: 'Factura N° ' + fac.numero_factura }" class="border border-blue-500 text-blue-600 hover:bg-blue-50 font-bold py-2 rounded-lg transition flex items-center justify-center gap-1.5 text-xs"><i class="fas fa-image"></i> Ver Foto</button>
              <button type="button" (click)="abrirEditar(fac)" class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 rounded-lg transition flex items-center justify-center gap-1.5 text-xs shadow"><i class="fas fa-edit"></i> Editar Factura</button>
            </div>
          </div>
        </div>
      }
    </div>
    @if (foto) {
      <div class="fixed inset-0 bg-black bg-opacity-90 z-[100] flex justify-center items-center p-4" (click)="foto = null">
        <div class="relative w-full max-w-2xl" (click)="$event.stopPropagation()">
          <div class="flex justify-between items-center mb-2 text-white"><h3 class="font-bold text-sm">{{ foto.titulo }}</h3><button type="button" (click)="foto = null" class="text-white hover:text-red-400 text-3xl leading-none">&times;</button></div>
          <div class="bg-white rounded-lg overflow-hidden flex justify-center items-center min-h-[200px]"><img [src]="foto.url" alt="Foto Factura" class="max-w-full max-h-[80vh] object-contain"></div>
        </div>
      </div>
    }
    @if (edit) {
      <div class="fixed inset-0 bg-black/80 z-[100] flex justify-center items-center p-4" (click)="edit = null">
        <div class="relative w-full max-w-lg bg-white rounded-xl shadow-2xl overflow-hidden" (click)="$event.stopPropagation()">
          <div class="bg-amber-600 text-white p-4 flex justify-between items-center">
            <div><h3 class="font-bold text-base"><i class="fas fa-edit mr-1"></i> Corregir Factura Parqueadero</h3><p class="text-amber-100 text-xs">Modifica datos o cambia la imagen del recibo</p></div>
            <button type="button" (click)="edit = null" class="text-white hover:text-red-200 text-2xl leading-none">&times;</button>
          </div>
          <div class="p-5 space-y-4 max-h-[80vh] overflow-y-auto">
            <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1">Punto Visitado <span class="text-red-500">*</span></label><app-select-buscar [opciones]="puntos" valor="id_punto" texto="nombre_punto" placeholder="- Seleccionar punto -" [(ngModel)]="edit.id_punto" /></div>
            <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1">Fecha del Servicio <span class="text-red-500">*</span></label><input type="date" [(ngModel)]="edit.fecha_servicio" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm font-semibold outline-none focus:border-amber-500"></div>
            <div class="grid grid-cols-2 gap-3">
              <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1">Hora Inicio <span class="text-red-500">*</span></label><input type="time" [(ngModel)]="edit.hora_inicio" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm font-semibold outline-none focus:border-amber-500"></div>
              <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1">Hora Fin <span class="text-red-500">*</span></label><input type="time" [(ngModel)]="edit.hora_fin" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm font-semibold outline-none focus:border-amber-500"></div>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1">N° Factura <span class="text-red-500">*</span></label><input type="text" [(ngModel)]="edit.numero_factura" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm font-semibold outline-none focus:border-amber-500"></div>
              <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1">Valor Pagado ($) <span class="text-red-500">*</span></label><input type="number" step="0.01" [(ngModel)]="edit.valor_factura" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm font-semibold outline-none focus:border-amber-500"></div>
            </div>
            <div class="border-t pt-3 space-y-2">
              <label class="block text-xs font-bold text-gray-700 uppercase">Foto de la Factura</label>
              <div class="flex items-center gap-3">
                @if (edit.ruta_foto) {
                  <div class="w-20 h-20 rounded-lg border border-gray-300 overflow-hidden relative shadow-sm">
                    <img [src]="url(edit.ruta_foto)" class="w-full h-full object-cover">
                    <button type="button" (click)="eliminarFotoActual()" class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] shadow hover:bg-red-700 transition" title="Eliminar foto actual"><i class="fas fa-times"></i></button>
                    <span class="absolute bottom-0 inset-x-0 bg-gray-800/80 text-white text-[8px] text-center font-bold uppercase">ACTUAL</span>
                  </div>
                }
                @if (edit.preview) {
                  <div class="w-20 h-20 rounded-lg border-2 border-amber-400 overflow-hidden relative shadow-sm">
                    <img [src]="edit.preview" class="w-full h-full object-cover">
                    <button type="button" (click)="edit.nueva = null; edit.preview = ''" class="absolute top-1 right-1 bg-gray-700 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] shadow hover:bg-gray-800 transition" title="Quitar nueva foto seleccionada"><i class="fas fa-times"></i></button>
                    <span class="absolute bottom-0 inset-x-0 bg-amber-600/90 text-white text-[8px] text-center font-bold uppercase">NUEVA</span>
                  </div>
                }
              </div>
              <div><label class="block text-[11px] font-semibold text-gray-500 mb-1">Cargar/Reemplazar foto de factura:</label><input type="file" accept="image/*" (change)="nuevaFoto($event)" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-amber-100 file:text-amber-800 hover:file:bg-amber-200 cursor-pointer"></div>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t">
              <button type="button" (click)="edit = null" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold px-4 py-2 rounded-lg text-sm transition">Cancelar</button>
              <button type="button" (click)="guardar()" class="bg-amber-600 hover:bg-amber-700 text-white font-bold px-5 py-2 rounded-lg text-sm shadow transition flex items-center gap-1.5"><i class="fas fa-save"></i> Guardar Cambios</button>
            </div>
          </div>
        </div>
      </div>
    }
  `
})
export class ParqueaderoHistorial implements OnInit {
  private api = inject(Api);
  private location = inject(Location);
  facturas: any[] = [];
  puntos: any[] = [];
  cargado = false;
  error = '';
  foto: { url: string; titulo: string } | null = null;
  edit: any = null;
  dmy = dmy;
  hora12 = hora12;
  pesos = pesos;
  url = urlArchivo;

  ngOnInit() {
    this.cargar();
  }
  async cargar() {
    const r: any = await this.api.get('parqueaderoHistorial');
    if (r.error) {
      alert(r.error);
      this.error = r.error;
      return;
    }
    this.facturas = r.facturas || [];
    this.puntos = r.puntos || [];
    this.cargado = true;
  }
  volver() {
    this.location.back();
  }
  abrirEditar(fac: any) {
    this.edit = {
      id_factura: fac.id_factura_parqueadero,
      id_punto: fac.id_punto,
      fecha_servicio: String(fac.fecha_servicio).substring(0, 10),
      hora_inicio: hm(fac.hora_inicio),
      hora_fin: hm(fac.hora_fin),
      numero_factura: fac.numero_factura,
      valor_factura: fac.valor_factura,
      ruta_foto: fac.ruta_foto,
      nueva: null as File | null,
      preview: ''
    };
  }
  nuevaFoto(ev: Event) {
    const file = (ev.target as HTMLInputElement).files?.[0] || null;
    this.edit.nueva = file;
    this.edit.preview = '';
    if (file) {
      const rd = new FileReader();
      rd.onload = () => this.edit && (this.edit.preview = String(rd.result));
      rd.readAsDataURL(file);
    }
  }
  async eliminarFotoActual() {
    if (!confirm('¿Seguro que deseas eliminar la foto actual de la factura? Deberás subir una nueva foto para guardar.')) return;
    try {
      const r: any = await this.api.postForm('parqueaderoHistorial', 'ajaxEliminarFoto', { id_factura: this.edit.id_factura });
      if (r.success) {
        this.edit.ruta_foto = '';
        alert('⚠️ ' + r.msj);
      } else alert('❌ ' + r.msj);
    } catch {
      alert('❌ Error de conexión al eliminar la imagen.');
    }
  }
  async guardar() {
    const e = this.edit;
    const fd = new FormData();
    ['id_factura', 'id_punto', 'fecha_servicio', 'hora_inicio', 'hora_fin', 'numero_factura', 'valor_factura'].forEach((k) => fd.append(k, e[k] === null || e[k] === undefined ? '' : String(e[k])));
    if (e.nueva) fd.append('foto_factura', e.nueva);
    try {
      const r: any = await this.api.post('parqueaderoHistorial', 'ajaxEditarFactura', fd);
      if (r.success) {
        alert('✅ ' + r.msj);
        this.edit = null;
        this.cargar();
      } else alert('❌ ' + r.msj);
    } catch {
      alert('❌ Error al intentar procesar los cambios.');
    }
  }
}

// =====================================================================================
// REPORTE DE PARQUEADEROS — ADMIN (parqueaderoAdminVista.php)
// =====================================================================================
@Component({
  selector: 'app-parqueadero-admin',
  imports: [FormsModule, SelectBuscar, DtArriba, DtAbajo],
  template: `
    <div class="p-4 md:p-6 max-w-full mx-auto space-y-6">
      <div class="flex flex-col md:flex-row md:items-center justify-between bg-white p-5 rounded-xl shadow-sm border border-gray-100 gap-4">
        <div>
          <h1 class="text-xl md:text-2xl font-bold text-gray-800"><i class="fas fa-parking text-blue-600 mr-2"></i>Reporte de Parqueaderos</h1>
          <p class="text-gray-500 text-xs md:text-sm mt-1">Gestión y auditoría de facturas registradas por los técnicos.</p>
        </div>
        <div class="flex items-center gap-2">
          <a [href]="urlExport('exportarExcel')" target="_blank" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-2.5 px-4 rounded-lg shadow-sm transition flex items-center gap-2"><i class="fas fa-file-excel text-base"></i> Exportar Excel</a>
          <a [href]="urlExport('exportarPdf')" target="_blank" class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2.5 px-4 rounded-lg shadow-sm transition flex items-center gap-2"><i class="fas fa-file-pdf text-base"></i> Exportar PDF</a>
        </div>
      </div>
      <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
        <form (ngSubmit)="filtrar()" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">
          <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha Inicio</label><input type="date" name="fi" [(ngModel)]="f.fecha_inicio" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2 text-sm outline-none focus:border-blue-500"></div>
          <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha Fin</label><input type="date" name="ff" [(ngModel)]="f.fecha_fin" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2 text-sm outline-none focus:border-blue-500"></div>
          <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1">Técnico</label><app-select-buscar name="tec" [opciones]="r?.tecnicos || []" valor="id_tecnico" texto="nombre_tecnico" placeholder="- Todos los técnicos -" [(ngModel)]="f.id_tecnico" /></div>
          <div class="flex gap-2">
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition shadow-sm flex items-center justify-center gap-2"><i class="fas fa-search"></i> Filtrar</button>
            <button type="button" (click)="limpiar()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded-lg transition shadow-sm flex items-center justify-center" title="Limpiar Filtros"><i class="fas fa-undo"></i></button>
          </div>
        </form>
      </div>
      @if (r) {
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-xl p-5 text-white shadow-md flex items-center gap-4"><div class="bg-white/20 p-4 rounded-full"><i class="fas fa-receipt text-3xl"></i></div><div><p class="text-blue-100 text-sm font-semibold uppercase">Total Facturas</p><h3 class="text-3xl font-bold">{{ r.totalFacturas }}</h3></div></div>
          <div class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl p-5 text-white shadow-md flex items-center gap-4"><div class="bg-white/20 p-4 rounded-full"><i class="fas fa-dollar-sign text-3xl"></i></div><div><p class="text-emerald-100 text-sm font-semibold uppercase">Total Gastado</p><h3 class="text-3xl font-bold">\${{ pesos(r.totalGastado) }}</h3></div></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="p-2 overflow-x-auto">
            <app-dt-arriba [dt]="dt" [opciones]="[10, 25, 50, -1]" />
            <table class="w-full text-sm text-left text-gray-500">
              <thead class="text-xs text-gray-700 uppercase bg-gray-50"><tr><th class="p-3">Fecha</th><th class="p-3">Técnico</th><th class="p-3">Punto</th><th class="p-3">Horario</th><th class="p-3">N° Factura</th><th class="p-3 text-right">Valor</th><th class="p-3 text-center">Acción</th></tr></thead>
              <tbody>
                @for (fac of dt.pagina; track fac.id_factura_parqueadero) {
                  <tr class="border-b hover:bg-gray-50 transition">
                    <td class="p-3 font-medium text-gray-900">{{ dmy(fac.fecha_servicio) }}</td>
                    <td class="p-3 font-semibold text-blue-700">{{ fac.nombre_tecnico }}</td>
                    <td class="p-3 text-gray-600">{{ fac.nombre_punto }}</td>
                    <td class="p-3"><span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs font-mono border border-gray-200">{{ hm(fac.hora_inicio) }} - {{ hm(fac.hora_fin) }}</span></td>
                    <td class="p-3 font-mono font-bold">{{ fac.numero_factura }}</td>
                    <td class="p-3 text-right font-bold text-green-600">\${{ pesos(fac.valor_factura) }}</td>
                    <td class="p-3 text-center"><button type="button" (click)="abrirFoto(fac)" class="text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 p-2 px-3 rounded-lg font-bold text-xs transition inline-flex items-center justify-center gap-1.5 whitespace-nowrap" title="Ver Factura"><i class="fas fa-eye"></i> Ver Factura</button></td>
                  </tr>
                }
              </tbody>
            </table>
            <app-dt-abajo [dt]="dt" />
          </div>
        </div>
      }
    </div>
    @if (modal) {
      <div class="fixed inset-0 bg-black/90 z-[100] flex justify-center items-center p-4" (click)="modal = null">
        <div class="relative w-full max-w-4xl" (click)="$event.stopPropagation()">
          <div class="flex flex-wrap justify-between items-center mb-3 text-white border-b border-gray-700 pb-2 gap-2">
            <div>
              <h3 class="font-bold text-lg"><i class="fas fa-file-invoice-dollar mr-2 text-blue-400"></i>Factura N° {{ modal.numero }}</h3>
              <p class="text-gray-400 text-sm"><i class="fas fa-user-hard-hat mr-1"></i> Subida por: {{ modal.tecnico }}</p>
            </div>
            <div class="flex items-center gap-2">
              <button type="button" (click)="rotar(-90)" class="bg-gray-800 hover:bg-gray-700 text-white p-2 px-3 rounded-lg text-xs font-bold transition flex items-center gap-1" title="Rotar 90° a la izquierda"><i class="fas fa-undo"></i> 90°</button>
              <button type="button" (click)="rotar(90)" class="bg-gray-800 hover:bg-gray-700 text-white p-2 px-3 rounded-lg text-xs font-bold transition flex items-center gap-1" title="Rotar 90° a la derecha"><i class="fas fa-redo"></i> 90°</button>
              @if (modal.angulo !== 0) {
                <button type="button" (click)="guardarRotacion()" [disabled]="modal.guardando" class="inline-flex bg-emerald-600 hover:bg-emerald-700 text-white p-2 px-3 rounded-lg text-xs font-bold transition items-center gap-1 shadow-sm">
                  @if (modal.guardando) { <i class="fas fa-spinner fa-spin"></i> Guardando... } @else { <i class="fas fa-save"></i> Guardar Rotación }
                </button>
              }
              <button type="button" (click)="modal = null" class="text-gray-400 hover:text-white text-3xl leading-none ml-2">&times;</button>
            </div>
          </div>
          <div class="bg-white rounded-xl overflow-hidden flex justify-center items-center min-h-[300px] shadow-2xl relative p-2">
            <img [src]="modal.src" alt="Foto Factura" class="max-w-full max-h-[75vh] object-contain transition-transform duration-300" [style.transform]="'rotate(' + modal.angulo + 'deg)'">
          </div>
        </div>
      </div>
    }
  `
})
export class ParqueaderoAdmin implements OnInit {
  private api = inject(Api);
  private ruta = inject(ActivatedRoute);
  private router = inject(Router);
  r: any = null;
  f: any = { fecha_inicio: '', fecha_fin: '', id_tecnico: '' };
  dt = new TablaDatos<any>([], 10);
  modal: any = null;
  dmy = dmy;
  hm = hm;
  pesos = pesos;

  ngOnInit() {
    const q = this.ruta.snapshot.queryParamMap;
    ['fecha_inicio', 'fecha_fin', 'id_tecnico'].forEach((k) => q.get(k) !== null && (this.f[k] = q.get(k)));
    this.dt.campoOrden = 'fecha_servicio';
    this.dt.ordenAsc = false;
    this.cargar();
  }
  private params() {
    const p: any = {};
    Object.entries(this.f).forEach(([k, v]) => v !== '' && v !== null && v !== undefined && (p[k] = v));
    return p;
  }
  async cargar() {
    const r: any = await this.api.get('parqueaderoAdmin', 'index', this.params());
    if (r.error) {
      alert(r.error);
      return;
    }
    this.r = r;
    this.f = { ...r.filtros };
    this.dt.set(r.facturas || []);
  }
  filtrar() {
    this.router.navigate([], { queryParams: this.params(), replaceUrl: true });
    this.cargar();
  }
  limpiar() {
    this.f = { fecha_inicio: '', fecha_fin: '', id_tecnico: '' };
    this.router.navigate([], { queryParams: {}, replaceUrl: true });
    this.cargar();
  }
  urlExport(accion: string) {
    return this.api.url('parqueaderoAdmin', accion, { fecha_inicio: this.r?.filtros?.fecha_inicio ?? '', fecha_fin: this.r?.filtros?.fecha_fin ?? '', id_tecnico: this.r?.filtros?.id_tecnico ?? '' });
  }
  abrirFoto(fac: any) {
    this.modal = { ruta: fac.ruta_foto, src: urlArchivo(fac.ruta_foto), numero: fac.numero_factura, tecnico: fac.nombre_tecnico, angulo: 0, guardando: false };
  }
  rotar(g: number) {
    this.modal.angulo = (this.modal.angulo + g) % 360;
  }
  async guardarRotacion() {
    if (this.modal.angulo === 0) return;
    this.modal.guardando = true;
    try {
      const r: any = await this.api.postForm('parqueaderoAdmin', 'guardarRotacion', { ruta_foto: this.modal.ruta, grados: this.modal.angulo });
      if (r.exito) {
        alert('✅ Rotación guardada correctamente en el servidor.');
        this.modal.src = urlArchivo(this.modal.ruta).split('?')[0] + '?v=' + Date.now();
        this.modal.angulo = 0;
      } else alert('❌ Error: ' + (r.mensaje || 'No se pudo rotar la imagen.'));
    } catch {
      alert('❌ Error de comunicación con el servidor.');
    } finally {
      if (this.modal) this.modal.guardando = false;
    }
  }
}
