import { AfterViewInit, Component, ElementRef, EventEmitter, Input, OnInit, Output, ViewChild, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import Swal from 'sweetalert2';
import { Api } from '../../core/api';
import { SelectBuscar } from '../../shared/select-buscar';

/** Port de js/tecnico/notificaciones.js */
export const NotifTec = {
  exito: (t: string, x = '') => Swal.fire({ icon: 'success', title: t, text: x, confirmButtonColor: '#2563eb' }),
  error: (t: string, x = '') => Swal.fire({ icon: 'error', title: t, text: x, confirmButtonColor: '#d33' }),
  advertencia: (t: string, x = '') => Swal.fire({ icon: 'warning', title: t, text: x, confirmButtonColor: '#f59e0b' }),
  toast: (m: string, icono: any = 'success') =>
    Swal.mixin({ toast: true, position: 'bottom-end', showConfirmButton: false, timer: 3000, timerProgressBar: true }).fire({ icon: icono, title: m })
};

/** formatearHoraMilitar(): "1330" -> "13:30", "930" -> "09:30" */
export function formatearHoraMilitar(v: string): string {
  let valor = String(v || '').replace(/\D/g, '');
  if (valor.length > 4) valor = valor.substring(0, 4);
  if (valor.length === 4) {
    let h = parseInt(valor.substring(0, 2), 10);
    let m = parseInt(valor.substring(2, 4), 10);
    if (h > 23) h = 23;
    if (m > 59) m = 59;
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
  }
  if (valor.length === 3) {
    let m = valor.substring(1, 3);
    if (parseInt(m, 10) > 59) m = '59';
    return `0${valor.substring(0, 1)}:${m}`;
  }
  return v;
}

/** calcularTiempoServicio(): 'HH:MM' o '00:00' */
export function calcularTiempo(entrada: string, salida: string): string {
  const re = /^([01]\d|2[0-3]):([0-5]\d)$/;
  if (!re.test(entrada) || !re.test(salida)) return '00:00';
  const [h1, m1] = entrada.split(':').map(Number);
  const [h2, m2] = salida.split(':').map(Number);
  let a = h1 * 60 + m1;
  let b = h2 * 60 + m2;
  if (b < a) b += 1440;
  const d = b - a;
  return `${String(Math.floor(d / 60)).padStart(2, '0')}:${String(d % 60).padStart(2, '0')}`;
}

/** Reemplaza el prefijo [ESTADO INICIAL: ...] de actividades */
export function componerActividades(novedadIni: string, actividades: string): string {
  const nov = (novedadIni || '').trim().substring(0, 500);
  const base = (actividades || '').replace(/^\[ESTADO INICIAL:.*?\]\s*/i, '').trim();
  return nov !== '' ? '[ESTADO INICIAL: ' + nov + '] ' + base : base;
}

// =====================================================================================
// FIRMA (canvas)
// =====================================================================================
@Component({
  selector: 'app-firma-canvas',
  template: `<canvas #c width="400" height="200" class="w-full h-48 bg-white cursor-crosshair block" style="touch-action: none"></canvas>`
})
export class FirmaCanvas implements AfterViewInit {
  @ViewChild('c', { static: true }) ref!: ElementRef<HTMLCanvasElement>;
  @Output() cambio = new EventEmitter<void>();
  vacia = true;
  private ctx!: CanvasRenderingContext2D;
  private dibujando = false;

  ngAfterViewInit() {
    const c = this.ref.nativeElement;
    this.ctx = c.getContext('2d')!;
    this.ctx.lineWidth = 3;
    this.ctx.lineCap = 'round';
    this.ctx.strokeStyle = '#0f172a';
    const ini = (e: any) => {
      if (e.cancelable) e.preventDefault();
      this.dibujando = true;
      const p = this.pos(e);
      this.ctx.beginPath();
      this.ctx.moveTo(p.x, p.y);
    };
    const mov = (e: any) => {
      if (!this.dibujando) return;
      if (e.cancelable) e.preventDefault();
      const p = this.pos(e);
      this.ctx.lineTo(p.x, p.y);
      this.ctx.stroke();
      this.vacia = false;
    };
    const fin = (e: any) => {
      if (e && e.cancelable) e.preventDefault();
      if (this.dibujando && !this.vacia) this.cambio.emit();
      this.dibujando = false;
    };
    c.addEventListener('touchstart', ini, { passive: false });
    c.addEventListener('touchmove', mov, { passive: false });
    c.addEventListener('touchend', fin, { passive: false });
    c.addEventListener('touchcancel', fin, { passive: false });
    c.addEventListener('mousedown', ini);
    c.addEventListener('mousemove', mov);
    c.addEventListener('mouseup', fin);
    c.addEventListener('mouseout', fin);
  }

  private pos(e: any) {
    const c = this.ref.nativeElement;
    const r = c.getBoundingClientRect();
    let x = e.clientX,
      y = e.clientY;
    if (e.touches && e.touches.length > 0) {
      x = e.touches[0].clientX;
      y = e.touches[0].clientY;
    }
    return { x: (x - r.left) * (c.width / r.width), y: (y - r.top) * (c.height / r.height) };
  }

  limpiar() {
    this.ctx.clearRect(0, 0, this.ref.nativeElement.width, this.ref.nativeElement.height);
    this.vacia = true;
  }

  /** Hay trazo real (canal alfa) — corrige la verificación RGB del PHP que siempre daba verdadero */
  tieneFirma(): boolean {
    const c = this.ref.nativeElement;
    const d = this.ctx.getImageData(0, 0, c.width, c.height).data;
    for (let i = 3; i < d.length; i += 4) if (d[i] > 0) return true;
    return false;
  }

  dataUrl(): string {
    return this.ref.nativeElement.toDataURL('image/png');
  }

  cargar(src: string) {
    const img = new Image();
    img.onload = () => {
      this.ctx.drawImage(img, 0, 0);
      this.vacia = false;
    };
    img.src = src;
  }
}

// =====================================================================================
// EVIDENCIAS (subida inmediata por foto)
// =====================================================================================
@Component({
  selector: 'app-evidencias-tecnico',
  template: `
    @for (g of grupos; track g.tipo) {
      <div class="mb-3">
        <div class="border border-dashed border-gray-300 rounded-lg p-3 bg-white text-center transition hover:bg-gray-100 relative">
          <i class="fas text-gray-400 text-2xl mb-2" [class]="g.icono"></i>
          <p class="text-sm font-bold text-gray-700">{{ g.titulo }}</p>
          <span class="px-3 py-1 rounded-full text-xs font-bold inline-block relative z-10" [class]="conteo(g.tipo) > 0 ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-200 text-gray-700'">{{ conteo(g.tipo) }} subidas</span>
          <input type="file" [multiple]="g.tipo !== 'remision' || !modoEditar" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" (change)="seleccionar($event, g.tipo)" />
        </div>
        <div class="flex flex-wrap gap-2 mt-2 justify-center">
          @for (f of deTipo(g.tipo); track f.id_evidencia) {
            <div class="relative w-16 h-16 rounded-md overflow-hidden border border-gray-300 shadow-sm">
              <img [src]="f.ruta_archivo" class="w-full h-full object-cover" />
              <button type="button" (click)="eliminar(f.id_evidencia)" class="absolute top-0 right-0 bg-red-600 text-white w-6 h-6 rounded-bl-md flex items-center justify-center text-xs hover:bg-red-700 opacity-90 transition"><i class="fas fa-trash"></i></button>
            </div>
          }
          @for (s of subiendo[g.tipo]; track $index) {
            <div class="relative w-16 h-16 rounded-md overflow-hidden border border-gray-300 shadow-sm flex items-center justify-center bg-gray-100"><i class="fas fa-spinner fa-spin text-blue-500 text-xl"></i></div>
          }
        </div>
      </div>
    }
    @if (!modoEditar) {
      <div class="text-center pt-2 border-t border-gray-200">
        <span class="text-sm font-bold text-gray-600">Total Evidencias: <span class="text-red-600 text-lg">{{ total }}</span>/10</span>
      </div>
    }
  `
})
export class EvidenciasTecnico implements OnInit {
  private api = inject(Api);
  @Input({ required: true }) idOrden!: number;
  @Input() remision = '';
  @Input() modoEditar = false;
  evidencias: any[] = [];
  subiendo: Record<string, number[]> = { antes: [], remision: [], despues: [] };
  grupos = [
    { tipo: 'antes', titulo: '1. Fotos del "Antes"', icono: 'fa-images' },
    { tipo: 'remision', titulo: '2. Foto de la Remisión', icono: 'fa-file-signature' },
    { tipo: 'despues', titulo: '3. Fotos del "Después"', icono: 'fa-check-double' }
  ];

  get total() {
    return this.conteo('antes') + this.conteo('remision') + this.conteo('despues');
  }

  ngOnInit() {
    this.cargar();
  }

  deTipo(t: string) {
    return this.evidencias.filter((e) => e.tipo_evidencia === t);
  }

  conteo(t: string) {
    return this.deTipo(t).length;
  }

  async cargar() {
    const r: any = await this.api.post('tecnicoReporte', 'ajaxObtenerEvidencias', { id_orden: this.idOrden });
    if (r.success) this.evidencias = r.data || [];
  }

  seleccionar(e: Event, tipo: string) {
    const input = e.target as HTMLInputElement;
    const files = Array.from(input.files || []);
    input.value = '';
    files.forEach((f) => this.subir(f, tipo));
  }

  private async subir(file: File, tipo: string) {
    if (!navigator.onLine) {
      Swal.fire('Sin conexión', 'No puedes subir fotos sin internet. Busca señal para continuar.', 'warning');
      return;
    }
    const fd = new FormData();
    fd.append('foto', file);
    fd.append('id_orden', String(this.idOrden));
    fd.append('tipo_evidencia', tipo);
    fd.append('numero_remision', this.remision || '');
    this.subiendo[tipo] = [...this.subiendo[tipo], 1];
    try {
      const d: any = await this.api.post('tecnicoReporte', 'ajaxSubirFotoUnica', fd);
      if (d.success) await this.cargar();
      else Swal.fire('Error', d.msj, 'error');
    } catch {
      Swal.fire('Error', 'Fallo al subir la foto por red', 'error');
    } finally {
      this.subiendo[tipo] = this.subiendo[tipo].slice(1);
    }
  }

  async eliminar(id: number) {
    if (!confirm('¿Borrar esta foto permanentemente?')) return;
    const d: any = await this.api.post('tecnicoReporte', 'ajaxEliminarFotoUnica', { id_evidencia: id });
    if (d.success) this.cargar();
    else Swal.fire('Error al borrar', d.msj, 'error');
  }
}

// =====================================================================================
// REPUESTOS
// =====================================================================================
export interface RepTec {
  id: any;
  nombre: string;
  origen: string;
  cantidad: number;
  enInventario?: boolean;
}

@Component({
  selector: 'app-repuestos-tecnico',
  imports: [FormsModule, SelectBuscar],
  template: `
    <ul class="space-y-2 mt-2">
      @for (item of lista; track $index; let i = $index) {
        <li class="flex justify-between items-center bg-white p-2 border border-gray-200 rounded shadow-sm">
          <div class="flex items-center gap-1 overflow-hidden w-full">
            <span class="text-[10px] font-bold px-2 py-0.5 rounded border border-opacity-20 flex-shrink-0" [class]="item.origen === 'INEES' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'" style="min-width: 60px; text-align: center">{{ item.origen }}</span>
            <span class="text-xs text-gray-700 font-medium truncate flex-grow">{{ item.nombre }}</span>
            @if (conStock) {
              @if (item.enInventario) {
                <span class="bg-green-100 text-green-700 text-[10px] font-bold px-1 py-0.5 rounded ml-1 flex-shrink-0"><i class="fas fa-check"></i> Stock</span>
              } @else {
                <span class="bg-yellow-100 text-yellow-700 text-[10px] font-bold px-1 py-0.5 rounded ml-1 flex-shrink-0"><i class="fas fa-exclamation"></i> Sin stock</span>
              }
            }
            <span class="bg-gray-800 text-white text-[11px] px-2 py-0.5 rounded-full font-bold flex-shrink-0">x{{ item.cantidad }}</span>
          </div>
          <button type="button" (click)="borrar(i)" class="text-red-400 hover:text-red-600 px-3 ml-2 text-lg transition"><i class="fas fa-trash-alt"></i></button>
        </li>
      }
    </ul>

    @if (abierto) {
      <div class="fixed inset-0 bg-black bg-opacity-70 z-[100] flex justify-center items-center px-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm overflow-visible">
          <div class="bg-blue-800 text-white p-3 flex justify-between items-center rounded-t-xl">
            <h3 class="font-bold text-sm uppercase"><i class="fas fa-box-open mr-1"></i> {{ conStock ? 'Añadir Componente' : 'Agregar Repuesto' }}</h3>
            <button type="button" (click)="cerrar()" class="text-white hover:text-red-300 text-2xl leading-none">&times;</button>
          </div>
          <div class="p-4 space-y-4">
            <div>
              <label class="block text-xs font-bold text-gray-600 uppercase mb-1">{{ conStock ? 'Buscar Repuesto' : 'Catálogo de Repuestos' }}</label>
              <app-select-buscar [opciones]="opciones" valor="id" texto="texto" placeholder="Buscar por nombre o código..." [ngModel]="sel" (cambio)="sel = $event" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Origen</label>
                <select [(ngModel)]="origen" class="w-full border border-gray-300 rounded-lg p-3 text-sm bg-gray-50 outline-none focus:border-blue-500">
                  <option value="INEES">INEES</option>
                  <option value="PROSEGUR">PROSEGUR</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">{{ conStock ? 'Cantidad' : 'Cantidad Sugerida' }}</label>
                <input type="number" [(ngModel)]="cantidad" min="1" class="w-full border border-gray-300 rounded-lg p-3 text-sm text-center font-bold outline-none focus:border-blue-500" />
              </div>
            </div>
            @if (conStock && opcionSel) {
              @if (opcionSel.enInventario) {
                <div class="bg-green-50 border border-green-200 rounded-lg p-2 text-xs text-green-700">
                  <i class="fas fa-check-circle mr-1"></i><strong>{{ opcionSel.nombre }}</strong> — Disponible en inventario: <strong>{{ opcionSel.stock }}</strong> unidades
                </div>
              } @else {
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-2 text-xs text-yellow-700">
                  <i class="fas fa-exclamation-triangle mr-1"></i><strong>{{ opcionSel.nombre }}</strong> — No está en tu inventario. Se registrará como solicitud para gestión.
                </div>
              }
            }
            <button type="button" (click)="agregar()" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-lg shadow transition mt-2 flex justify-center items-center gap-2"><i class="fas fa-plus-circle"></i> Agregar a la Lista</button>
          </div>
        </div>
      </div>
    }
  `
})
export class RepuestosTecnico {
  /** todosLosRepuestos: {id_repuesto, nombre_repuesto, codigo_referencia} */
  @Input() set catalogo(v: any[]) {
    this._catalogo = v || [];
    this.armar();
  }
  /** inventario del técnico {id_repuesto, cantidad_actual}; null = sin control de stock (edición) */
  @Input() set inventario(v: any[] | null) {
    this._inventario = v;
    this.armar();
  }
  @Input() conStock = true;
  @Output() cambio = new EventEmitter<RepTec[]>();

  lista: RepTec[] = [];
  opciones: any[] = [];
  abierto = false;
  sel: any = '';
  origen = 'INEES';
  cantidad: any = 1;
  private _catalogo: any[] = [];
  private _inventario: any[] | null = null;

  get opcionSel() {
    return this.opciones.find((o) => String(o.id) === String(this.sel));
  }

  get totalItems() {
    return this.lista.reduce((s, i) => s + Number(i.cantidad), 0);
  }

  private armar() {
    const inv: Record<string, number> = {};
    (this._inventario || []).forEach((i) => (inv[String(i.id_repuesto)] = Number(i.cantidad_actual)));
    this.opciones = this._catalogo.map((r) => {
      const en = String(r.id_repuesto) in inv;
      const stock = en ? inv[String(r.id_repuesto)] : 0;
      const texto = this.conStock
        ? `${r.nombre_repuesto}${r.codigo_referencia ? ' [' + r.codigo_referencia + ']' : ''}${en ? ` (Stock: ${stock})` : ' (Sin stock)'}`
        : `${r.nombre_repuesto}${r.codigo_referencia ? ' (' + r.codigo_referencia + ')' : ''}`;
      return { id: String(r.id_repuesto), nombre: r.nombre_repuesto, stock, enInventario: en, texto };
    });
  }

  establecer(lista: RepTec[]) {
    this.lista = (lista || []).map((r) => ({ ...r, cantidad: Number(r.cantidad) }));
    this.cambio.emit(this.lista);
  }

  abrir() {
    this.sel = '';
    this.cantidad = 1;
    this.abierto = true;
  }

  cerrar() {
    this.abierto = false;
    this.sel = '';
    this.cantidad = 1;
  }

  agregar() {
    const op = this.opcionSel;
    if (!this.sel || !op) {
      alert('Seleccione un repuesto de la lista.');
      return;
    }
    const cant = parseInt(this.cantidad) || 1;
    if (cant <= 0) {
      alert('La cantidad debe ser mayor a 0.');
      return;
    }
    if (this.conStock && op.enInventario && op.stock > 0) {
      const ya = this.lista.filter((r) => String(r.id) == op.id).reduce((s, r) => s + Number(r.cantidad), 0);
      if (ya + cant > op.stock) {
        Swal.fire({
          title: 'Stock insuficiente',
          html: 'Solo tienes <strong>' + op.stock + '</strong> unidades en inventario.<br>Ya agregaste <strong>' + ya + '</strong> y estás intentando agregar <strong>' + cant + ' más</strong>.',
          icon: 'warning',
          confirmButtonText: 'Entendido'
        });
        return;
      }
    }
    const i = this.lista.findIndex((r) => String(r.id) === op.id && r.origen === this.origen);
    if (i !== -1) this.lista[i].cantidad += cant;
    else this.lista.push({ id: op.id, nombre: op.nombre, origen: this.origen, cantidad: cant, ...(this.conStock ? { enInventario: op.enInventario } : {}) });
    this.cambio.emit(this.lista);
    this.cerrar();
  }

  borrar(i: number) {
    this.lista.splice(i, 1);
    this.cambio.emit(this.lista);
  }
}

// =====================================================================================
// NOVEDADES (modal)
// =====================================================================================
@Component({
  selector: 'app-novedades-tecnico',
  imports: [FormsModule, SelectBuscar],
  template: `
    @if (abierto) {
      <div class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-11/12 md:w-1/3 p-6">
          <div class="flex justify-between items-center mb-4 border-b pb-2">
            <h3 class="text-xl font-bold text-red-600"><i class="fas fa-exclamation-triangle mr-2"></i> Reportar Novedad</h3>
            <button (click)="cerrar()" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times text-xl"></i></button>
          </div>
          <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Tipo de Novedad (Puedes seleccionar varias):</label>
            <app-select-buscar [opciones]="catalogo" valor="id_tipo_novedad" texto="nombre_novedad" placeholder="Buscar y seleccionar novedades..." [multiple]="true" [(ngModel)]="seleccion" />
          </div>
          <div class="flex justify-between mt-6">
            <button type="button" (click)="eliminarTodas()" class="text-red-500 hover:text-red-700 font-bold text-sm underline">Quitar Novedades</button>
            <div class="space-x-2">
              <button type="button" (click)="cerrar()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Cancelar</button>
              <button type="button" [disabled]="guardando" (click)="guardar()" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded shadow">{{ guardando ? 'Guardando...' : 'Guardar' }}</button>
            </div>
          </div>
        </div>
      </div>
    }
  `
})
export class NovedadesTecnico {
  private api = inject(Api);
  @Input({ required: true }) idOrden!: number;
  /** Emite tras guardar/eliminar con éxito */
  @Output() guardado = new EventEmitter<string[]>();

  /** novedadesTecnicoActuales */
  actuales: string[] = [];
  /** novedades_gestionada */
  gestionada = false;
  catalogo: any[] = [];
  seleccion: any[] = [];
  abierto = false;
  guardando = false;

  get tiene() {
    return this.actuales.length > 0 ? '1' : '0';
  }

  fusionar(ids: any[]) {
    const u = (ids || []).map(String);
    this.actuales.forEach((v) => {
      if (!u.includes(String(v))) u.push(String(v));
    });
    this.actuales = u;
    return u;
  }

  async consultar(): Promise<string[]> {
    try {
      const d: any = await this.api.post('tecnicoReporte', 'ajaxObtenerNovedadesTecnico', { id_orden: this.idOrden });
      return d && d.success ? (d.ids || []).map(String) : [];
    } catch {
      return [];
    }
  }

  async abrir() {
    if (!this.catalogo.length) this.catalogo = ((await this.api.post('tecnicoReporte', 'ajaxObtenerCatalogoNovedades', {})) as any[]) || [];
    const ids = this.fusionar(await this.consultar());
    this.seleccion = ids.map((x) => Number(x));
    this.abierto = true;
  }

  cerrar() {
    this.abierto = false;
    this.seleccion = [];
  }

  async guardar() {
    const arr = (this.seleccion || []).map(String);
    if (arr.length === 0 && !confirm('No hay novedades seleccionadas. Quieres quitar todas las novedades de esta orden?')) return;
    this.guardando = true;
    try {
      const fd = new FormData();
      fd.append('id_orden', String(this.idOrden));
      arr.forEach((n) => fd.append('novedades[]', n));
      const r: any = await this.api.post('tecnicoReporte', 'ajaxGuardarNovedadTecnico', fd);
      if (r.success) {
        this.actuales = arr;
        this.gestionada = true;
        this.cerrar();
        this.guardado.emit(this.actuales);
        NotifTec.exito(arr.length > 0 ? 'Novedad guardada' : 'Novedades eliminadas', r.msj);
      } else alert('Error: ' + (r.msj || 'No se pudo guardar la novedad.'));
    } catch {
      alert('Error de conexion al guardar la novedad.');
    } finally {
      this.guardando = false;
    }
  }

  async eliminarTodas() {
    if (!confirm('Seguro que deseas quitar todas las novedades de esta orden?')) return;
    try {
      const fd = new FormData();
      fd.append('id_orden', String(this.idOrden));
      fd.append('novedades[]', '');
      const r: any = await this.api.post('tecnicoReporte', 'ajaxGuardarNovedadTecnico', fd);
      if (r.success) {
        this.actuales = [];
        this.gestionada = true;
        this.cerrar();
        this.guardado.emit(this.actuales);
      } else alert('No se pudieron eliminar las novedades.');
    } catch {
      alert('Error de conexion al eliminar.');
    }
  }
}
