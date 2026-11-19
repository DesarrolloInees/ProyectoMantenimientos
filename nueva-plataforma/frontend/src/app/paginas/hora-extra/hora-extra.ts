import { Component, OnInit, inject } from '@angular/core';
import { DecimalPipe, Location } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { Api } from '../../core/api';
import { SelectBuscar } from '../../shared/select-buscar';
import { DtAbajo, DtArriba, TablaDatos } from '../../shared/tabla';

const dos = (n: number) => (n < 10 ? '0' + n : '' + n);
const urlArchivo = (ruta: string) => '/' + String(ruta || '').replace(/^\/+/, '');
function dmy(f: any) {
  const s = String(f || '').substring(0, 10).split('-');
  return s.length === 3 ? `${s[2]}/${s[1]}/${s[0]}` : '';
}
function hm(t: any) {
  return String(t ?? '').substring(0, 5);
}
function hora12(t: any) {
  const [h0, m] = String(t ?? '').split(':').map(Number);
  if (isNaN(h0)) return '';
  const ap = h0 >= 12 ? 'PM' : 'AM';
  return `${dos(h0 % 12 || 12)}:${dos(m || 0)} ${ap}`;
}
function badgeEstado(nombre: string) {
  if (nombre === 'Aprobada') return 'bg-green-100 text-green-800 border-green-200';
  if (nombre === 'Rechazada') return 'bg-red-100 text-red-800 border-red-200';
  return 'bg-yellow-100 text-yellow-800 border-yellow-200';
}

// =====================================================================================
// REPORTAR HORAS EXTRA — TÉCNICO (horaExtraCrearVista.php)
// =====================================================================================
@Component({
  selector: 'app-hora-extra-crear',
  imports: [FormsModule, SelectBuscar],
  template: `
    <div class="bg-blue-800 text-white p-4 shadow-md sticky top-0 z-40 flex items-center gap-3 -mx-4 md:mx-0 md:rounded-lg">
      <button (click)="volver()" class="text-white bg-blue-700 hover:bg-blue-600 p-2 rounded-full w-10 h-10 flex items-center justify-center transition"><i class="fas fa-arrow-left"></i></button>
      <div><h1 class="font-bold text-lg leading-tight">Reportar Horas Extra</h1><p class="text-blue-200 text-xs">Registro adicional por cliente y punto</p></div>
    </div>
    @if (error) {
      <div class="max-w-lg mx-auto p-3 mt-4"><div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded">{{ error }}</div></div>
    }
    @if (d && !error) {
      <div class="max-w-lg mx-auto p-3 space-y-4 mt-2 mb-24">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 space-y-4">
          <div class="flex items-center gap-2 border-b pb-2"><i class="fas fa-clock text-blue-500 text-lg"></i><h2 class="font-bold text-gray-700 text-sm uppercase">Detalles del Tiempo</h2></div>
          <div>
            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha <span class="text-red-500">*</span></label>
            <input type="date" [(ngModel)]="f.fecha_reporte" [max]="d.hoy" class="w-full bg-white border border-gray-300 rounded-lg p-3 text-gray-800 font-bold shadow-sm outline-none focus:border-blue-500">
          </div>
          <div class="grid grid-cols-2 gap-3 bg-gray-50 p-3 rounded-lg border border-gray-200">
            <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1">Hora Inicio <span class="text-red-500">*</span></label><input type="time" [(ngModel)]="f.hora_inicio" class="w-full bg-white border border-gray-300 rounded-lg p-2 text-sm text-gray-800 font-bold shadow-sm outline-none focus:border-blue-500"></div>
            <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1">Hora Fin <span class="text-red-500">*</span></label><input type="time" [(ngModel)]="f.hora_fin" class="w-full bg-white border border-gray-300 rounded-lg p-2 text-sm text-gray-800 font-bold shadow-sm outline-none focus:border-blue-500"></div>
            <div class="col-span-2 pt-2 border-t border-gray-200 flex justify-between items-center"><span class="text-xs font-bold text-gray-500 uppercase">Total Calculado:</span><strong class="text-blue-600 bg-blue-100 px-3 py-1 rounded-full text-sm">{{ totalTexto() }}</strong></div>
          </div>
          <div class="flex items-center gap-2 border-b pb-2 pt-2"><i class="fas fa-building text-blue-500 text-lg"></i><h2 class="font-bold text-gray-700 text-sm uppercase">Ubicación del Servicio</h2></div>
          <div>
            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Cliente</label>
            <app-select-buscar [opciones]="d.clientes" valor="id_cliente" texto="nombre_cliente" placeholder="- Seleccione Cliente -" [(ngModel)]="f.id_cliente" (cambio)="cambioCliente()" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Punto Visitado <span class="text-red-500">*</span></label>
            <app-select-buscar [opciones]="puntos" valor="id_punto" texto="nombre_punto" placeholder="- Seleccione el Punto -" [(ngModel)]="f.id_punto" (cambio)="cambioPunto()" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Justificación <span class="text-red-500">*</span></label>
            <textarea [(ngModel)]="f.justificacion" rows="3" placeholder="Describe por qué te quedaste hasta tarde..." class="w-full bg-gray-50 border border-gray-300 rounded-lg p-3 text-sm text-gray-800 shadow-sm outline-none focus:border-blue-500"></textarea>
          </div>
          <div class="bg-gray-50 rounded-xl shadow-sm border border-gray-200 p-4 mt-2">
            <div class="flex items-center gap-2 mb-2 border-b pb-2"><i class="fas fa-camera text-indigo-500 text-lg"></i><h2 class="font-bold text-gray-700 text-sm uppercase">Evidencia Fotográfica</h2></div>
            <div class="border border-dashed border-gray-300 rounded-lg p-4 bg-white text-center transition hover:bg-gray-100 relative">
              <i class="fas fa-images text-gray-400 text-3xl mb-2"></i>
              <p class="text-sm font-bold text-gray-700">Toca para agregar fotos</p>
              @if (fotos.length) {
                <span class="inline-block bg-green-100 text-green-700 px-3 py-1 mt-2 rounded-full text-xs font-bold relative z-10">{{ fotos.length }} foto(s) seleccionada(s)</span>
              }
              <input type="file" accept="image/*" multiple (change)="agregarFotos($event)" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20">
            </div>
            <div class="flex flex-wrap gap-3 mt-3 justify-center">
              @for (p of previews; track $index; let i = $index) {
                <div class="relative group w-20 h-20">
                  <img [src]="p" class="w-20 h-20 object-cover rounded-lg border border-gray-300 shadow-sm">
                  <button type="button" (click)="quitarFoto(i)" class="absolute -top-2 -right-2 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center font-bold text-xs shadow-md hover:bg-red-700 transition">&times;</button>
                </div>
              }
            </div>
          </div>
        </div>
      </div>
      <div class="fixed bottom-0 left-0 w-full bg-white shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.1)] p-4 z-40 border-t border-gray-200">
        <button type="button" (click)="enviar()" [disabled]="enviando" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl shadow-lg transform active:scale-95 transition text-lg flex items-center justify-center gap-2"><i class="fas fa-paper-plane"></i> ENVIAR REPORTE</button>
      </div>
    }
  `
})
export class HoraExtraCrear implements OnInit {
  private api = inject(Api);
  private router = inject(Router);
  private location = inject(Location);
  d: any = null;
  error = '';
  f: any = { fecha_reporte: '', hora_inicio: '', hora_fin: '', id_cliente: null, id_punto: null, justificacion: '' };
  puntos: any[] = [];
  fotos: File[] = [];
  previews: string[] = [];
  enviando = false;

  async ngOnInit() {
    const r: any = await this.api.get('horaExtraCrear');
    if (r.error) {
      alert(r.error);
      this.error = r.error;
      return;
    }
    this.d = r;
    this.f.fecha_reporte = r.hoy;
    this.puntos = r.puntos || [];
  }
  volver() {
    this.location.back();
  }
  totalTexto() {
    if (!this.f.hora_inicio || !this.f.hora_fin) return '0h 0m';
    const [h1, m1] = this.f.hora_inicio.split(':').map(Number);
    const [h2, m2] = this.f.hora_fin.split(':').map(Number);
    let a = h1 * 60 + m1, b = h2 * 60 + m2;
    if (b < a) b += 1440;
    const diff = b - a;
    return `${Math.floor(diff / 60)}h ${diff % 60}m`;
  }
  cambioCliente() {
    this.puntos = (this.d.puntos || []).filter((p: any) => !this.f.id_cliente || p.id_cliente == this.f.id_cliente);
    this.f.id_punto = null;
  }
  cambioPunto() {
    if (this.f.id_punto && !this.f.id_cliente) {
      const p = (this.d.puntos || []).find((x: any) => x.id_punto == this.f.id_punto);
      if (p?.id_cliente) this.f.id_cliente = p.id_cliente;
    }
  }
  agregarFotos(ev: Event) {
    const input = ev.target as HTMLInputElement;
    Array.from(input.files || []).forEach((file) => {
      this.fotos.push(file);
      const rd = new FileReader();
      rd.onload = () => this.previews.push(String(rd.result));
      rd.readAsDataURL(file);
    });
    input.value = '';
  }
  quitarFoto(i: number) {
    this.fotos.splice(i, 1);
    this.previews.splice(i, 1);
  }
  async enviar() {
    if (!this.f.fecha_reporte || !this.f.hora_inicio || !this.f.hora_fin || !this.f.id_punto || !String(this.f.justificacion).trim()) {
      alert('⚠️ Completa todos los campos obligatorios (*).');
      return;
    }
    const fd = new FormData();
    Object.entries(this.f).forEach(([k, v]) => fd.append(k, v === null || v === undefined ? '' : String(v)));
    this.fotos.forEach((f) => fd.append('evidencias[]', f));
    this.enviando = true;
    try {
      const r: any = await this.api.post('horaExtraCrear', 'guardar', fd);
      alert(r.msj);
      if (r.success) this.router.navigate(['/inicio']);
    } finally {
      this.enviando = false;
    }
  }
}

// =====================================================================================
// MIS HORAS EXTRA — TÉCNICO (horaExtraHistorialVista.php)
// =====================================================================================
@Component({
  selector: 'app-hora-extra-historial',
  imports: [FormsModule, RouterLink, DecimalPipe],
  template: `
    <div class="bg-blue-800 text-white p-4 shadow-md sticky top-0 z-40 flex items-center justify-between gap-3 -mx-4 md:mx-0 md:rounded-lg">
      <div class="flex items-center gap-3">
        <button (click)="volver()" class="text-white bg-blue-700 hover:bg-blue-600 p-2 rounded-full w-10 h-10 flex items-center justify-center transition"><i class="fas fa-arrow-left"></i></button>
        <div><h1 class="font-bold text-lg leading-tight">Mis Horas Extra</h1><p class="text-blue-200 text-xs">Historial de reportes creados</p></div>
      </div>
      <a routerLink="/horaExtraCrear" class="bg-green-500 hover:bg-green-600 text-white p-2 rounded-full w-10 h-10 flex items-center justify-center transition shadow" title="Reportar Nuevas Horas Extra"><i class="fas fa-plus"></i></a>
    </div>
    <div class="max-w-lg mx-auto p-3 mt-2 space-y-4">
      @if (error) {
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded">{{ error }}</div>
      } @else if (cargado && !lista.length) {
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center mt-10"><i class="fas fa-business-time text-gray-300 text-5xl mb-3"></i><h2 class="text-gray-500 font-bold text-lg">Sin registros</h2><p class="text-gray-400 text-sm mt-1">Aún no has registrado ninguna hora extra.</p></div>
      }
      @for (he of lista; track he.id_registro_he) {
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gray-50 px-4 py-2 border-b border-gray-100 flex justify-between items-center">
            <span class="text-xs font-bold text-gray-600"><i class="far fa-calendar-alt mr-1 text-blue-600"></i> {{ dmy(he.fecha_reporte) }}</span>
            <span class="px-2 py-0.5 rounded-full border text-[10px] font-bold uppercase flex items-center gap-1" [class]="badge(he.estado_nombre)">
              <i class="fas" [class]="he.estado_nombre === 'Aprobada' ? 'fa-check-circle' : he.estado_nombre === 'Rechazada' ? 'fa-times-circle' : 'fa-clock'"></i> {{ he.estado_nombre }}
            </span>
          </div>
          <div class="p-4 space-y-3">
            <div>
              @if (he.nombre_cliente) { <span class="block text-[10px] font-bold text-blue-600 uppercase leading-none">{{ he.nombre_cliente }}</span> }
              <h3 class="font-bold text-gray-800 text-sm mt-0.5"><i class="fas fa-map-marker-alt text-red-500 mr-1"></i> {{ he.nombre_punto || 'Sin punto asignado' }}</h3>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs">
              <div class="bg-gray-50 p-2 rounded border border-gray-100"><span class="block text-gray-400 uppercase font-bold text-[9px]">Horario</span><span class="text-gray-700 font-semibold">{{ hora12(he.hora_inicio) }} - {{ hora12(he.hora_fin) }}</span></div>
              <div class="bg-blue-50 p-2 rounded border border-blue-100 text-right"><span class="block text-blue-500 uppercase font-bold text-[9px]">Total Horas</span><span class="text-blue-700 font-bold text-sm">{{ +he.total_horas | number: '1.2-2' : 'en-US' }} hrs</span></div>
            </div>
            <div class="bg-gray-50 p-2.5 rounded-lg border border-gray-100"><span class="block text-gray-400 uppercase font-bold text-[9px] mb-0.5">Justificación:</span><p class="text-xs text-gray-700 italic leading-snug">"{{ he.justificacion_tecnico }}"</p></div>
            @if (he.observacion_supervisor) {
              <div class="bg-red-50 p-2.5 rounded-lg border border-red-100"><span class="block text-red-500 uppercase font-bold text-[9px] mb-0.5">Nota Supervisor:</span><p class="text-xs text-red-700 font-medium leading-snug">{{ he.observacion_supervisor }}</p></div>
            }
            @if (fotosDe(he).length) {
              <button type="button" (click)="galeria = { titulo: 'Evidencias - Reporte ' + dmy(he.fecha_reporte), fotos: fotosDe(he) }" class="w-full border border-blue-500 text-blue-600 hover:bg-blue-50 font-bold py-2 rounded-lg transition flex items-center justify-center gap-2 text-sm mt-2"><i class="fas fa-images"></i> Ver Evidencias ({{ fotosDe(he).length }})</button>
            }
            @if (he.estado_nombre !== 'Aprobada') {
              <button type="button" (click)="abrirEditar(he)" class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 rounded-lg transition flex items-center justify-center gap-2 text-sm mt-2 shadow"><i class="fas fa-edit"></i> Corregir / Editar Reporte</button>
            }
          </div>
        </div>
      }
    </div>

    @if (galeria) {
      <div class="fixed inset-0 bg-black bg-opacity-90 z-[100] flex justify-center items-center p-4" (click)="galeria = null">
        <div class="relative w-full max-w-2xl space-y-3" (click)="$event.stopPropagation()">
          <div class="flex justify-between items-center text-white"><h3 class="font-bold text-sm">{{ galeria.titulo }}</h3><button type="button" (click)="galeria = null" class="text-white hover:text-red-400 text-3xl leading-none">&times;</button></div>
          <div class="bg-white rounded-xl overflow-hidden p-3 flex flex-wrap gap-2 justify-center max-h-[75vh] overflow-y-auto">
            @for (f of galeria.fotos; track f.ruta) {
              <div class="w-full sm:w-48 h-48 bg-gray-100 rounded-lg overflow-hidden border border-gray-300 flex items-center justify-center"><a [href]="url(f.ruta)" target="_blank"><img [src]="url(f.ruta)" class="max-w-full max-h-full object-cover" alt="Evidencia HE"></a></div>
            }
          </div>
        </div>
      </div>
    }

    @if (edit) {
      <div class="fixed inset-0 bg-black/80 z-[100] flex justify-center items-center p-4" (click)="edit = null">
        <div class="relative w-full max-w-lg bg-white rounded-xl shadow-2xl overflow-hidden" (click)="$event.stopPropagation()">
          <div class="bg-amber-600 text-white p-4 flex justify-between items-center">
            <div><h3 class="font-bold text-base"><i class="fas fa-edit mr-1"></i> Corregir Reporte y Fotos</h3><p class="text-amber-100 text-xs">Ajusta horarios, justificación y sube o renueva fotos</p></div>
            <button type="button" (click)="edit = null" class="text-white hover:text-red-200 text-2xl leading-none">&times;</button>
          </div>
          <div class="p-5 space-y-4 max-h-[80vh] overflow-y-auto">
            <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1">Fecha del Reporte</label><input type="date" [(ngModel)]="edit.fecha_reporte" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm font-semibold outline-none focus:border-amber-500"></div>
            <div class="grid grid-cols-2 gap-3">
              <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1">Hora Inicio</label><input type="time" [(ngModel)]="edit.hora_inicio" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm font-semibold outline-none focus:border-amber-500"></div>
              <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1">Hora Fin</label><input type="time" [(ngModel)]="edit.hora_fin" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm font-semibold outline-none focus:border-amber-500"></div>
            </div>
            <div class="bg-amber-50 p-3 rounded-lg border border-amber-200 text-center"><span class="block text-amber-700 font-bold text-xs uppercase">Total Horas Recalculadas:</span><span class="text-xl font-extrabold text-amber-800">{{ totalEdit() }} hrs</span></div>
            <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1">Justificación del Trabajo</label><textarea [(ngModel)]="edit.justificacion_tecnico" rows="2" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm outline-none focus:border-amber-500"></textarea></div>
            <div class="border-t pt-3 space-y-2">
              <label class="block text-xs font-bold text-gray-700 uppercase">Evidencias Fotográficas</label>
              <div class="flex flex-wrap gap-3 mb-2">
                @for (f of edit.fotos; track f.id) {
                  <div class="w-20 h-20 rounded-lg border border-gray-300 overflow-hidden relative group">
                    <img [src]="url(f.ruta)" class="w-full h-full object-cover">
                    <button type="button" (click)="eliminarFoto(f)" class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs shadow hover:bg-red-700 transition" title="Eliminar esta foto"><i class="fas fa-times"></i></button>
                  </div>
                } @empty {
                  <span class="text-xs text-gray-400 italic">Sin fotos adjuntas previamente.</span>
                }
              </div>
              <div>
                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Agregar fotos adicionales (JPG, PNG, WEBP):</label>
                <input type="file" multiple accept="image/*" (change)="nuevasFotos($event)" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-amber-100 file:text-amber-800 hover:file:bg-amber-200 cursor-pointer">
              </div>
              @if (edit.previews.length) {
                <div class="flex flex-wrap gap-2 pt-2">
                  <span class="w-full text-[10px] font-bold text-amber-700 uppercase">Nuevas fotos a subir:</span>
                  @for (p of edit.previews; track $index) {
                    <div class="w-16 h-16 rounded-lg border-2 border-amber-400 overflow-hidden relative shadow-sm"><img [src]="p" class="w-full h-full object-cover"><span class="absolute bottom-0 inset-x-0 bg-amber-600/80 text-white text-[8px] text-center font-bold truncate px-0.5">NUEVA</span></div>
                  }
                </div>
              }
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t">
              <button type="button" (click)="edit = null" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold px-4 py-2 rounded-lg text-sm transition">Cancelar</button>
              <button type="button" (click)="guardarEdicion()" class="bg-amber-600 hover:bg-amber-700 text-white font-bold px-5 py-2 rounded-lg text-sm shadow transition flex items-center gap-1.5"><i class="fas fa-paper-plane"></i> Reenviar a Revisión</button>
            </div>
          </div>
        </div>
      </div>
    }
  `
})
export class HoraExtraHistorial implements OnInit {
  private api = inject(Api);
  private location = inject(Location);
  lista: any[] = [];
  cargado = false;
  error = '';
  galeria: { titulo: string; fotos: any[] } | null = null;
  edit: any = null;
  dmy = dmy;
  hora12 = hora12;
  badge = badgeEstado;
  url = urlArchivo;

  ngOnInit() {
    this.cargar();
  }
  async cargar() {
    const r: any = await this.api.get('horaExtraHistorial');
    if (r.error) {
      alert(r.error);
      this.error = r.error;
      return;
    }
    this.lista = r.reportesHE || [];
    this.cargado = true;
  }
  volver() {
    this.location.back();
  }
  fotosDe(he: any): { id: string; ruta: string }[] {
    if (!he.fotos_detalle) return [];
    return String(he.fotos_detalle)
      .split('||')
      .map((x) => {
        const [id, ruta] = x.split('::');
        return { id, ruta };
      });
  }
  abrirEditar(he: any) {
    this.edit = {
      id_registro: he.id_registro_he,
      fecha_reporte: String(he.fecha_reporte).substring(0, 10),
      hora_inicio: hm(he.hora_inicio),
      hora_fin: hm(he.hora_fin),
      justificacion_tecnico: he.justificacion_tecnico,
      fotos: this.fotosDe(he),
      nuevas: [] as File[],
      previews: [] as string[]
    };
  }
  totalEdit() {
    const e = this.edit;
    if (!e?.hora_inicio || !e?.hora_fin) return '0.00';
    const d1 = new Date('2000/01/01 ' + e.hora_inicio);
    const d2 = new Date('2000/01/01 ' + e.hora_fin);
    if (d2 <= d1) d2.setDate(d2.getDate() + 1);
    return ((d2.getTime() - d1.getTime()) / 3600000).toFixed(2);
  }
  nuevasFotos(ev: Event) {
    const input = ev.target as HTMLInputElement;
    this.edit.nuevas = Array.from(input.files || []);
    this.edit.previews = [];
    this.edit.nuevas.forEach((file: File) => {
      if (!file.type.startsWith('image/')) return;
      const rd = new FileReader();
      rd.onload = () => this.edit?.previews.push(String(rd.result));
      rd.readAsDataURL(file);
    });
  }
  async eliminarFoto(f: any) {
    if (!confirm('¿Seguro que deseas eliminar esta imagen del reporte?')) return;
    const r: any = await this.api.postForm('horaExtraHistorial', 'ajaxEliminarEvidencia', { id_evidencia: f.id, id_registro: this.edit.id_registro });
    if (r.success) this.edit.fotos = this.edit.fotos.filter((x: any) => x.id !== f.id);
    else alert('❌ ' + r.msj);
  }
  async guardarEdicion() {
    const e = this.edit;
    const fd = new FormData();
    fd.append('id_registro', e.id_registro);
    fd.append('fecha_reporte', e.fecha_reporte || '');
    fd.append('hora_inicio', e.hora_inicio || '');
    fd.append('hora_fin', e.hora_fin || '');
    fd.append('justificacion_tecnico', e.justificacion_tecnico || '');
    e.nuevas.forEach((f: File) => fd.append('nuevas_evidencias[]', f));
    try {
      const r: any = await this.api.post('horaExtraHistorial', 'ajaxEditarRegistro', fd);
      if (r.success) {
        alert('✅ ' + r.msj);
        this.edit = null;
        this.cargar();
      } else alert('❌ ' + r.msj);
    } catch {
      alert('❌ Error al intentar procesar los cambios y subir las fotos.');
    }
  }
}

// =====================================================================================
// APROBACIÓN DE HORAS EXTRA — ADMIN (horaExtraAdminVista.php)
// =====================================================================================
@Component({
  selector: 'app-hora-extra-admin',
  imports: [FormsModule, DecimalPipe, SelectBuscar, DtArriba, DtAbajo],
  template: `
    <div class="p-4 md:p-6 max-w-full mx-auto space-y-6">
      <div class="flex justify-between items-center bg-white p-5 rounded-xl shadow-sm border border-gray-100">
        <div>
          <h1 class="text-xl md:text-2xl font-bold text-gray-800"><i class="fas fa-user-clock text-blue-600 mr-2"></i>Aprobación de Horas Extra</h1>
          <p class="text-gray-500 text-xs md:text-sm mt-1">Auditoría, revisión de evidencias y corte para nómina de horas adicionales.</p>
        </div>
      </div>
      <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
        <form (ngSubmit)="filtrar()" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4 items-end">
          <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha Inicio</label><input type="date" name="fi" [(ngModel)]="f.fecha_inicio" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2 text-sm outline-none focus:border-blue-500"></div>
          <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha Fin</label><input type="date" name="ff" [(ngModel)]="f.fecha_fin" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2 text-sm outline-none focus:border-blue-500"></div>
          <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1">Técnico</label><app-select-buscar name="tec" [opciones]="r?.tecnicos || []" valor="id_tecnico" texto="nombre_tecnico" placeholder="- Todos los técnicos -" [(ngModel)]="f.id_tecnico" /></div>
          <div>
            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Estado</label>
            <select name="est" [(ngModel)]="f.id_estado" class="w-full border-gray-300 rounded-lg p-2 bg-gray-50 text-sm outline-none focus:border-blue-500">
              <option value="">- Todos los estados -</option>
              @for (e of r?.estados || []; track e.id_estado) { <option [value]="'' + e.id_estado">{{ e.nombre_estado }}</option> }
            </select>
          </div>
          <div class="flex flex-wrap gap-2 sm:col-span-2 md:col-span-5 justify-end mt-2 border-t pt-3">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition shadow-sm flex items-center gap-2 text-sm"><i class="fas fa-search"></i> Filtrar</button>
            <button type="button" (click)="limpiar()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded-lg transition shadow-sm flex items-center gap-1 text-sm" title="Limpiar Filtros"><i class="fas fa-undo"></i></button>
            <button type="button" (click)="pdf()" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition shadow-sm flex items-center gap-2 text-sm"><i class="fas fa-file-pdf"></i> Reporte PDF</button>
            <button type="button" (click)="excel()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-lg transition shadow-sm flex items-center gap-2 text-sm"><i class="fas fa-file-excel"></i> Formato Excel</button>
          </div>
        </form>
      </div>
      @if (r) {
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl p-4 text-white shadow-md flex items-center gap-3"><div class="bg-white/20 p-3 rounded-full"><i class="fas fa-check-circle text-2xl"></i></div><div><p class="text-emerald-100 text-xs font-semibold uppercase">Horas Aprobadas</p><h3 class="text-2xl font-bold">{{ r.totalHorasAprobadas | number: '1.2-2' : 'en-US' }} <span class="text-sm font-normal">hrs</span></h3><p class="text-[11px] text-emerald-200 mt-0.5">{{ r.cantAprobadas }} registros listos para nómina</p></div></div>
          <div class="bg-gradient-to-r from-amber-500 to-orange-600 rounded-xl p-4 text-white shadow-md flex items-center gap-3"><div class="bg-white/20 p-3 rounded-full"><i class="fas fa-hourglass-half text-2xl"></i></div><div><p class="text-amber-100 text-xs font-semibold uppercase">Por Revisar</p><h3 class="text-2xl font-bold">{{ r.totalHorasPendientes | number: '1.2-2' : 'en-US' }} <span class="text-sm font-normal">hrs</span></h3><p class="text-[11px] text-amber-200 mt-0.5">{{ r.cantPendientes }} solicitudes pendientes</p></div></div>
          <div class="bg-gradient-to-r from-rose-500 to-red-600 rounded-xl p-4 text-white shadow-md flex items-center gap-3"><div class="bg-white/20 p-3 rounded-full"><i class="fas fa-times-circle text-2xl"></i></div><div><p class="text-rose-100 text-xs font-semibold uppercase">Horas Rechazadas</p><h3 class="text-2xl font-bold">{{ r.totalHorasRechazadas | number: '1.2-2' : 'en-US' }} <span class="text-sm font-normal">hrs</span></h3><p class="text-[11px] text-rose-200 mt-0.5">{{ r.cantRechazadas }} registros denegados</p></div></div>
          <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-xl p-4 text-white shadow-md flex items-center gap-3"><div class="bg-white/20 p-3 rounded-full"><i class="fas fa-history text-2xl"></i></div><div><p class="text-blue-100 text-xs font-semibold uppercase">Total Solicitado</p><h3 class="text-2xl font-bold">{{ r.totalHorasAprobadas + r.totalHorasPendientes + r.totalHorasRechazadas | number: '1.2-2' : 'en-US' }} <span class="text-sm font-normal">hrs</span></h3><p class="text-[11px] text-blue-200 mt-0.5">Volumen total reportado</p></div></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="p-2 overflow-x-auto">
            <app-dt-arriba [dt]="dt" [opciones]="[10, 25, 50, -1]" />
            <table class="w-full text-sm text-left text-gray-500">
              <thead class="text-xs text-gray-700 uppercase bg-gray-50"><tr><th class="p-3">Fecha</th><th class="p-3">Técnico</th><th class="p-3">Cliente / Punto</th><th class="p-3">Horario</th><th class="p-3 text-center">Total Horas</th><th class="p-3 text-center">Estado</th><th class="p-3 text-center">Acciones</th></tr></thead>
              <tbody>
                @for (x of dt.pagina; track x.id_registro_he) {
                  <tr class="border-b hover:bg-gray-50 transition">
                    <td class="p-3 font-medium text-gray-900">{{ dmy(x.fecha_reporte) }}</td>
                    <td class="p-3 font-semibold text-blue-700">{{ x.nombre_tecnico }}</td>
                    <td class="p-3"><span class="block text-xs font-bold text-gray-700">{{ x.nombre_punto || 'N/A' }}</span><span class="block text-[11px] text-gray-400">{{ x.nombre_cliente || 'Sin cliente' }}</span></td>
                    <td class="p-3"><span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs font-mono border border-gray-200">{{ hm(x.hora_inicio) }} - {{ hm(x.hora_fin) }}</span></td>
                    <td class="p-3 text-center font-bold text-gray-800">{{ +x.total_horas | number: '1.2-2' : 'en-US' }} hrs</td>
                    <td class="p-3 text-center"><span class="px-2.5 py-1 rounded-full border text-xs font-bold uppercase" [class]="badge(x.estado_nombre)">{{ x.estado_nombre }}</span></td>
                    <td class="p-3 text-center">
                      <div class="inline-flex items-center gap-1.5">
                        <button type="button" (click)="auditar(x)" class="text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 p-2 px-3 rounded-lg font-bold text-xs transition inline-flex items-center gap-1.5"><i class="fas fa-search"></i> Auditar / Ver</button>
                        @if (+x.id_estado_aprobacion !== 2) {
                          <button type="button" (click)="eliminar(x)" title="Borrar registro y fotos" class="text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 p-2 px-3 rounded-lg font-bold text-xs transition inline-flex items-center gap-1.5"><i class="fas fa-trash"></i> Borrar</button>
                        }
                      </div>
                    </td>
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
      <div class="fixed inset-0 bg-black/80 z-[100] flex justify-center items-center p-4" (click)="modal = null">
        <div class="relative w-full max-w-2xl bg-white rounded-xl shadow-2xl overflow-hidden" (click)="$event.stopPropagation()">
          <div class="bg-blue-800 text-white p-4 flex justify-between items-center">
            <div><h3 class="font-bold text-base">Técnico: {{ modal.x.nombre_tecnico }}</h3><p class="text-blue-200 text-xs">Fecha Reporte: {{ modal.x.fecha_reporte }}</p></div>
            <button type="button" (click)="modal = null" class="text-white hover:text-red-300 text-2xl leading-none">&times;</button>
          </div>
          <div class="p-5 space-y-4 max-h-[80vh] overflow-y-auto">
            <div class="grid grid-cols-2 gap-3 text-xs bg-gray-50 p-3 rounded-lg border border-gray-200">
              <div><span class="block text-gray-400 font-bold uppercase text-[10px]">Cliente / Punto:</span><strong class="text-gray-800">{{ modal.x.nombre_punto || 'Sin punto' }} ({{ modal.x.nombre_cliente || 'Sin cliente' }})</strong></div>
              <div><span class="block text-gray-400 font-bold uppercase text-[10px]">Horario y Total:</span><strong class="text-blue-700">{{ modal.x.hora_inicio }} - {{ modal.x.hora_fin }} ({{ modal.x.total_horas }} hrs)</strong></div>
            </div>
            <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1">Justificación del Técnico:</label><div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm text-gray-700 italic">"{{ modal.x.justificacion_tecnico }}"</div></div>
            <div>
              <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Evidencias Adjuntas:</label>
              <div class="flex flex-wrap gap-2 justify-start">
                @for (ruta of modal.fotos; track ruta) {
                  <a [href]="url(ruta)" target="_blank" class="block w-24 h-24 rounded-lg overflow-hidden border border-gray-300 hover:opacity-80 transition"><img [src]="url(ruta)" class="w-full h-full object-cover" alt="Evidencia"></a>
                } @empty {
                  <span class="text-xs text-gray-400 italic">No se adjuntaron fotos en este reporte.</span>
                }
              </div>
            </div>
            <hr class="border-gray-200">
            <div class="space-y-3">
              <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Acción de Auditoría <span class="text-red-500">*</span></label>
                <select [(ngModel)]="modal.id_estado" class="w-full bg-white border border-gray-300 rounded-lg p-2.5 text-sm font-bold shadow-sm outline-none focus:border-blue-500">
                  @for (e of r.estados; track e.id_estado) { <option [value]="'' + e.id_estado">{{ e.nombre_estado }}</option> }
                </select>
              </div>
              <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1">Observación / Nota del Supervisor:</label><textarea [(ngModel)]="modal.observacion" rows="2" placeholder="Escribe un motivo si rechazas o apruebas con novedades..." class="w-full bg-white border border-gray-300 rounded-lg p-2.5 text-sm shadow-sm outline-none focus:border-blue-500"></textarea></div>
              <div class="flex justify-end gap-2 pt-2">
                <button type="button" (click)="modal = null" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold px-4 py-2 rounded-lg text-sm transition">Cancelar</button>
                <button type="button" (click)="guardarAuditoria()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-5 py-2 rounded-lg text-sm shadow transition flex items-center gap-1.5"><i class="fas fa-save"></i> Guardar Cambios</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    }
  `
})
export class HoraExtraAdmin implements OnInit {
  private api = inject(Api);
  private ruta = inject(ActivatedRoute);
  private router = inject(Router);
  r: any = null;
  f: any = { fecha_inicio: '', fecha_fin: '', id_tecnico: '', id_estado: '' };
  dt = new TablaDatos<any>([], 10);
  modal: any = null;
  dmy = dmy;
  hm = hm;
  badge = badgeEstado;
  url = urlArchivo;

  ngOnInit() {
    const q = this.ruta.snapshot.queryParamMap;
    ['fecha_inicio', 'fecha_fin', 'id_tecnico', 'id_estado'].forEach((k) => q.get(k) !== null && (this.f[k] = q.get(k)));
    this.dt.campoOrden = 'fecha_reporte';
    this.dt.ordenAsc = false;
    this.cargar();
  }
  private params() {
    const p: any = {};
    Object.entries(this.f).forEach(([k, v]) => v !== '' && v !== null && v !== undefined && (p[k] = v));
    return p;
  }
  async cargar() {
    const r: any = await this.api.get('horaExtraAdmin', 'index', this.params());
    if (r.error) {
      alert(r.error);
      return;
    }
    this.r = r;
    this.f = { ...r.filtros };
    this.dt.set(r.reportes || []);
  }
  filtrar() {
    this.router.navigate([], { queryParams: this.params(), replaceUrl: true });
    this.cargar();
  }
  limpiar() {
    this.f = { fecha_inicio: '', fecha_fin: '', id_tecnico: '', id_estado: '' };
    this.router.navigate([], { queryParams: {}, replaceUrl: true });
    this.cargar();
  }
  fotosDe(x: any): string[] {
    return x.rutas_fotos ? String(x.rutas_fotos).split('||') : [];
  }
  auditar(x: any) {
    this.modal = { x, fotos: this.fotosDe(x), id_estado: String(x.id_estado_aprobacion), observacion: x.observacion_supervisor || '' };
  }
  async guardarAuditoria() {
    try {
      const res: any = await this.api.postForm('horaExtraAdmin', 'ajaxCambiarEstado', { id_registro: this.modal.x.id_registro_he, id_estado: this.modal.id_estado, observacion: this.modal.observacion });
      if (res.success) {
        alert('✅ ' + res.msj);
        this.modal = null;
        this.cargar();
      } else alert('❌ ' + res.msj);
    } catch {
      alert('❌ Error al procesar la solicitud.');
    }
  }
  async eliminar(x: any) {
    const n = this.fotosDe(x).length;
    if (!confirm(`¿Eliminar el registro de "${x.nombre_tecnico}" y sus ${n} foto(s)?\n\nEsta acción no se puede deshacer.`)) return;
    try {
      const res: any = await this.api.postForm('horaExtraAdmin', 'ajaxEliminarRegistro', { id_registro: x.id_registro_he });
      if (res.success) {
        alert('✅ ' + res.msj);
        this.cargar();
      } else alert('❌ ' + res.msj);
    } catch {
      alert('❌ Error al procesar la solicitud.');
    }
  }
  private exportParams() {
    return { fecha_inicio: this.f.fecha_inicio || '', fecha_fin: this.f.fecha_fin || '', id_tecnico: this.f.id_tecnico || '', id_estado: this.f.id_estado || '' };
  }
  pdf() {
    this.api.abrir('horaExtraGenerar', 'generar', this.exportParams());
  }
  excel() {
    window.location.href = this.api.url('horaExtraExcel', 'generar', this.exportParams());
  }
}
