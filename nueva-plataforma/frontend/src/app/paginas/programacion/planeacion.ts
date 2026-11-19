import { Component, ElementRef, OnInit, ViewChild, ViewEncapsulation, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Api } from '../../core/api';
import { SelectBuscar } from '../../shared/select-buscar';

const MAPA_CLASE: Record<string, string> = { Activo: 'activo', Aplazado: 'aplazado', Cancelado: 'cancelado', Finalizado: 'finalizado' };
const claseEstado = (n: string) => MAPA_CLASE[n] || 'sin-estado';

function ymd(f: any) {
  const d = new Date(f);
  return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}

/** Port de planeacionVista.php (Gantt de estrategias y tareas) */
@Component({
  selector: 'app-planeacion',
  imports: [FormsModule, SelectBuscar],
  encapsulation: ViewEncapsulation.None,
  styles: [
    `
      .bar-wrapper.estado-activo .bar { fill: #22c55e !important; }
      .bar-wrapper.estado-aplazado .bar { fill: #eab308 !important; }
      .bar-wrapper.estado-cancelado .bar { fill: #ef4444 !important; }
      .bar-wrapper.estado-finalizado .bar { fill: #3b82f6 !important; }
      .bar-wrapper.estado-sin-estado .bar { fill: #94a3b8 !important; }
      .badge-estado-activo { background: #dcfce7; color: #166534; }
      .badge-estado-aplazado { background: #fef9c3; color: #854d0e; }
      .badge-estado-cancelado { background: #fee2e2; color: #991b1b; }
      .badge-estado-finalizado { background: #dbeafe; color: #1e40af; }
      .badge-estado-sin-estado { background: #f1f5f9; color: #475569; }
    `
  ],
  template: `
    <div class="bg-blue-800 text-white p-4 shadow-md sticky top-0 z-40 flex items-center justify-between gap-3 rounded-lg">
      <div class="flex items-center gap-3">
        <button (click)="volver()" class="text-white bg-blue-700 hover:bg-blue-600 p-2 rounded-full w-10 h-10 flex items-center justify-center transition"><i class="fas fa-arrow-left"></i></button>
        <div>
          <h1 class="font-bold text-lg leading-tight">Planeación Estratégica</h1>
          <p class="text-blue-200 text-xs">Gantt de estrategias y tareas</p>
        </div>
      </div>
      <button (click)="abrirEstrategia()" class="bg-white text-blue-800 font-bold px-3 py-2 rounded-lg text-sm flex items-center gap-1 shadow hover:bg-blue-50"><i class="fas fa-plus"></i> Nueva estrategia</button>
    </div>

    <div class="max-w-6xl mx-auto p-3 space-y-4 mt-2">
      <div class="flex gap-2">
        @for (v of vistas; track v[0]) {
          <button (click)="cambiarVista(v[0])" class="px-4 py-2 rounded-lg border border-gray-300 text-sm" [class]="vista === v[0] ? 'bg-blue-800 text-white' : 'bg-white'">{{ v[1] }}</button>
        }
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-3 overflow-x-auto">
        <svg #svg [style.display]="vacio ? 'none' : ''"></svg>
        @if (vacio) {
          <p class="text-center text-gray-400 py-10">Aún no hay estrategias registradas. Crea la primera con el botón "Nueva estrategia".</p>
        }
      </div>
    </div>

    @if (modal === 'estrategia' || modal === 'tarea') {
      <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-5 space-y-4">
          <div class="flex justify-between items-center border-b pb-2">
            <h2 class="font-bold text-gray-700">{{ modal === 'estrategia' ? 'Nueva Estrategia' : form.id_tarea_padre ? 'Nueva Subtarea' : 'Nueva Tarea' }}</h2>
            <button (click)="modal = ''" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
          </div>
          <div class="space-y-3">
            <div>
              <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Nombre <span class="text-red-500">*</span></label>
              <input type="text" [(ngModel)]="form.nombre" class="w-full border border-gray-300 rounded-lg p-2" />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Responsable</label>
              <app-select-buscar [opciones]="usuarios" valor="usuario_id" texto="nombre" placeholder="- Sin asignar -" [(ngModel)]="form.id_responsable" />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Estado <span class="text-red-500">*</span></label>
              <app-select-buscar [opciones]="estados" valor="id_estado" texto="nombre_estado" placeholder="- Seleccione -" [(ngModel)]="form.id_estado" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha inicial <span class="text-red-500">*</span></label><input type="date" [(ngModel)]="form.fecha_inicial" class="w-full border border-gray-300 rounded-lg p-2" /></div>
              <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha final <span class="text-red-500">*</span></label><input type="date" [(ngModel)]="form.fecha_final" class="w-full border border-gray-300 rounded-lg p-2" /></div>
            </div>
            <button type="button" (click)="guardar()" class="w-full bg-blue-800 hover:bg-blue-900 text-white font-bold py-3 rounded-lg mt-2">{{ modal === 'estrategia' ? 'Guardar estrategia' : 'Guardar tarea' }}</button>
          </div>
        </div>
      </div>
    }

    @if (modal === 'detalle' && detalle) {
      <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-sm p-5 space-y-3">
          <div class="flex justify-between items-center border-b pb-2">
            <h2 class="font-bold text-gray-700">{{ detalle.name }}</h2>
            <button (click)="modal = ''" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
          </div>
          <p class="text-sm"><strong class="text-gray-600">Responsable:</strong> {{ detalle.responsable || 'Sin asignar' }}</p>
          <p class="text-sm flex items-center gap-2">
            <strong class="text-gray-600">Estado:</strong>
            <span class="text-xs font-bold px-2 py-0.5 rounded-full" [class]="'badge-estado-' + clase(detalle.estado)">{{ detalle.estado || 'Sin estado' }}</span>
          </p>
          <p class="text-sm"><strong class="text-gray-600">Avance:</strong> {{ detalle.progress || 0 }}%</p>
          <button (click)="subtareaDesdeDetalle()" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2 rounded-lg text-sm mt-2"><i class="fas fa-plus"></i> Agregar subtarea</button>
        </div>
      </div>
    }
  `
})
export class Planeacion implements OnInit {
  private api = inject(Api);
  @ViewChild('svg', { static: true }) svg!: ElementRef<SVGElement>;

  vistas = [
    ['Day', 'Día'],
    ['Week', 'Semana'],
    ['Month', 'Mes']
  ];
  vista = 'Week';
  estados: any[] = [];
  usuarios: any[] = [];
  filas: any[] = [];
  vacio = false;
  modal: '' | 'estrategia' | 'tarea' | 'detalle' = '';
  form: any = {};
  detalle: any = null;
  private gantt: any = null;

  clase = claseEstado;

  async ngOnInit() {
    const r: any = await this.api.get('planeacionVer');
    if (r.error) {
      alert(r.error);
      return;
    }
    this.estados = r.estados || [];
    this.usuarios = r.usuarios || [];
    this.cargarGantt();
  }

  volver() {
    history.back();
  }

  async cargarGantt() {
    this.filas = (await this.api.get<any[]>('planeacionVer', 'datosGantt')) || [];
    this.vacio = this.filas.length === 0;
    if (this.vacio) return;
    this.svg.nativeElement.innerHTML = '';
    const conClase = this.filas.map((f) => ({ ...f, custom_class: 'estado-' + claseEstado(f.estado) }));
    // frappe-gantt se carga como script global (angular.json > scripts), igual que el CDN de la vista PHP
    const Gantt = (window as any).Gantt;
    this.gantt = new Gantt(this.svg.nativeElement, conClase, {
      view_mode: this.vista,
      language: 'es',
      on_click: (b: any) => {
        this.detalle = b;
        this.modal = 'detalle';
      },
      on_date_change: (b: any, ini: any, fin: any) => {
        const { tipo, id } = this.separar(b.id);
        this.api.postForm('planeacionVer', 'actualizarFechas', { tipo, id, fecha_inicial: ymd(ini), fecha_final: ymd(fin) });
      },
      on_progress_change: (b: any, p: any) => {
        const { tipo, id } = this.separar(b.id);
        this.api.postForm('planeacionVer', 'actualizarAvance', { tipo, id, avance_pct: p });
      }
    });
  }

  cambiarVista(v: string) {
    this.vista = v;
    this.gantt?.change_view_mode(v);
  }

  private separar(idG: string) {
    return idG.startsWith('estrategia_') ? { tipo: 'estrategia', id: idG.replace('estrategia_', '') } : { tipo: 'tarea', id: idG.replace('tarea_', '') };
  }

  private estrategiaDeTarea(idG: string): string | null {
    let a = this.filas.find((f) => f.id === idG);
    while (a && a.tipo === 'tarea') a = this.filas.find((f) => f.id === a.dependencies);
    return a ? a.id.replace('estrategia_', '') : null;
  }

  abrirEstrategia() {
    this.form = { nombre: '', id_responsable: '', id_estado: '', fecha_inicial: '', fecha_final: '' };
    this.modal = 'estrategia';
  }

  private abrirTarea(idE: any, padre: any) {
    this.form = { nombre: '', id_responsable: '', id_estado: '', fecha_inicial: '', fecha_final: '', id_estrategia: idE, id_tarea_padre: padre || '' };
    this.modal = 'tarea';
  }

  subtareaDesdeDetalle() {
    const idO: string = this.detalle.id;
    if (idO.startsWith('estrategia_')) this.abrirTarea(idO.replace('estrategia_', ''), null);
    else this.abrirTarea(this.estrategiaDeTarea(idO), idO.replace('tarea_', ''));
  }

  async guardar() {
    const f = this.form;
    if (!f.nombre?.trim() || !f.id_estado || !f.fecha_inicial || !f.fecha_final) {
      alert('Por favor completa los campos obligatorios (*).');
      return;
    }
    const esE = this.modal === 'estrategia';
    const datos: any = { id_responsable: f.id_responsable ?? '', id_estado: f.id_estado, fecha_inicial: f.fecha_inicial, fecha_final: f.fecha_final };
    if (esE) datos.nombre_estrategia = f.nombre;
    else Object.assign(datos, { nombre_tarea: f.nombre, id_estrategia: f.id_estrategia, id_tarea_padre: f.id_tarea_padre });
    const r: any = await this.api.postForm('planeacionVer', esE ? 'crearEstrategia' : 'crearTarea', datos);
    if (r.status === 'success') {
      this.modal = '';
      this.cargarGantt();
    } else alert('⚠️ ' + (r.message || 'No se pudo guardar.'));
  }
}
