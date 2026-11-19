import { Component, OnDestroy, OnInit, ViewChild, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import Swal from 'sweetalert2';
import { Api } from '../../core/api';
import { SelectBuscar } from '../../shared/select-buscar';
import { EvidenciasTecnico, FirmaCanvas, NotifTec, NovedadesTecnico, RepTec, RepuestosTecnico, calcularTiempo, componerActividades, formatearHoraMilitar } from './tecnico-compartido';

const CAMPOS = [
  'id_tipo_mantenimiento', 'id_modalidad', 'hora_entrada', 'hora_salida', 'tiempo_servicio', 'numero_maquina', 'serial_maquina', 'serial_router', 'serial_ups',
  'numero_remision', 'pendientes', 'administrador_punto', 'celular_encargado', 'id_estado_inicial', 'novedad_estado_inicial', 'id_estado_maquina',
  'actividades_realizadas', 'soporte_remoto', 'id_calificacion'
];

function fechaDmy(f: string) {
  const s = String(f || '').substring(0, 10).split('-');
  return s.length === 3 ? `${s[2]}/${s[1]}/${s[0]}` : '';
}

/** Port de tecnicoReporteVIsta.php + js/tecnico/{tecnicoReporte,validaciones,autoGuardado,tecnicoNovedades}.js */
@Component({
  selector: 'app-tecnico-reporte',
  imports: [FormsModule, SelectBuscar, FirmaCanvas, EvidenciasTecnico, RepuestosTecnico, NovedadesTecnico],
  template: `
    @if (o) {
      <div class="bg-blue-800 text-white p-4 shadow-md sticky top-0 z-40 flex items-center gap-3 -mx-4 -mt-4 md:mx-0 md:mt-0 md:rounded-lg">
        <button (click)="volver()" class="text-white bg-blue-700 hover:bg-blue-600 p-2 rounded-full w-10 h-10 flex items-center justify-center transition"><i class="fas fa-arrow-left"></i></button>
        <div>
          <h1 class="font-bold text-lg leading-tight">Ejecutar Servicio</h1>
          <p class="text-blue-200 text-xs">Orden #{{ o.id_ordenes_servicio }} | {{ dmy(o.fecha_visita) }}</p>
        </div>
      </div>

      <div class="max-w-lg mx-auto p-3 space-y-4 mt-2 mb-24">
        @if (modificado) {
          <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            <strong class="font-bold">⚠️ ¡Atención!</strong>
            <span class="block sm:inline">Este servicio fue modificado por otro usuario. <a href="#" (click)="recargar($event)" class="underline font-bold">Recarga la página</a> para ver los cambios antes de guardar.</span>
          </div>
        }
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
          <div class="flex items-center gap-2 mb-3 border-b pb-2"><i class="fas fa-info-circle text-blue-500 text-lg"></i><h2 class="font-bold text-gray-700 text-sm uppercase">Detalles de Asignación</h2></div>
          <div class="grid grid-cols-1 gap-4 text-sm">
            <div>
              <span class="block text-xs text-gray-400 font-bold uppercase mb-1">Cliente / Punto</span>
              <span class="block font-bold text-gray-800 text-base leading-tight">{{ o.nombre_cliente }}</span>
              <span class="block text-gray-600 font-medium mt-1">{{ o.nombre_punto }}</span>
              @if (o.direccion) { <span class="block text-gray-400 text-xs mt-1"><i class="fas fa-map-marker-alt"></i> {{ o.direccion }}</span> }
            </div>
            <div class="bg-blue-50 p-3 rounded-lg border border-blue-100">
              <span class="block text-xs text-blue-400 font-bold uppercase mb-1">Máquina a intervenir</span>
              <span class="block font-bold text-gray-800">{{ o.nombre_tipo_maquina }}</span>
              <span class="text-blue-600 font-mono text-xs mt-1 bg-white inline-block px-2 py-1 rounded border border-blue-200">ID: {{ o.device_id || 'N/A' }}</span>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 space-y-5">
          <div class="flex items-center gap-2 mb-1 border-b pb-2"><i class="fas fa-clipboard-check text-green-500 text-lg"></i><h2 class="font-bold text-gray-700 text-sm uppercase">Llenar Reporte</h2></div>
          <div>
            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Tipo de Servicio</label>
            <app-select-buscar [opciones]="d.tiposManto" valor="id_tipo_mantenimiento" texto="nombre_completo" placeholder="- Seleccione Tipo -" [(ngModel)]="f.id_tipo_mantenimiento" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Modalidad Operativa</label>
            <app-select-buscar [opciones]="d.modalidades" valor="id_modalidad" texto="nombre_modalidad" placeholder="- Seleccione Modalidad -" [(ngModel)]="f.id_modalidad" />
          </div>
          <div class="grid grid-cols-2 gap-3 bg-gray-50 p-3 rounded-lg border border-gray-200">
            <div>
              <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Hora (Entrada) @if (d.horaEntradaInicial) { <span class="text-green-600 text-[9px] font-bold ml-1">✅ Auto</span> }</label>
              <input type="text" inputmode="numeric" placeholder="HH:MM (ej: 1330)" maxlength="5" [(ngModel)]="f.hora_entrada" (input)="soloHora($event, 'hora_entrada')" (blur)="blurHora('hora_entrada')" class="w-full bg-white border border-gray-300 rounded-lg p-3 text-gray-800 font-bold shadow-sm outline-none focus:border-blue-500" />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Hora (Salida)</label>
              <input type="text" inputmode="numeric" placeholder="HH:MM (ej: 1745)" maxlength="5" [(ngModel)]="f.hora_salida" (input)="soloHora($event, 'hora_salida')" (blur)="blurHora('hora_salida')" class="w-full bg-white border border-gray-300 rounded-lg p-3 text-gray-800 font-bold shadow-sm outline-none focus:border-blue-500" />
            </div>
            <div class="col-span-2 pt-2 border-t border-gray-200 flex justify-between items-center">
              <span class="text-xs font-bold text-gray-500 uppercase">Tiempo Total:</span>
              <strong class="text-blue-600 bg-blue-100 px-3 py-1 rounded-full text-sm">{{ f.tiempo_servicio }} hrs</strong>
            </div>
          </div>
        </div>

        <div class="bg-blue-50 p-3 rounded-lg border border-blue-100 space-y-3">
          <div class="flex items-center gap-2 border-b border-blue-200 pb-1"><i class="fas fa-microchip text-blue-500"></i><h3 class="font-bold text-blue-800 text-xs uppercase">Información de Equipos</h3></div>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="block text-[10px] font-bold text-blue-600 uppercase mb-1">Máquina</label><input type="text" [(ngModel)]="f.numero_maquina" placeholder="Ej: 159" class="w-full bg-white border border-blue-200 rounded-lg p-2 text-sm shadow-sm outline-none focus:border-blue-500" /></div>
            <div><label class="block text-[10px] font-bold text-blue-600 uppercase mb-1">Serial</label><input type="text" [(ngModel)]="f.serial_maquina" placeholder="Ej: 0.100.C2/..." class="w-full bg-white border border-blue-200 rounded-lg p-2 text-sm shadow-sm outline-none focus:border-blue-500" /></div>
            <div><label class="block text-[10px] font-bold text-blue-600 uppercase mb-1">Serial router</label><input type="text" [(ngModel)]="f.serial_router" class="w-full bg-white border border-blue-200 rounded-lg p-2 text-sm shadow-sm outline-none focus:border-blue-500" /></div>
            <div><label class="block text-[10px] font-bold text-blue-600 uppercase mb-1">Serial ups</label><input type="text" [(ngModel)]="f.serial_ups" class="w-full bg-white border border-blue-200 rounded-lg p-2 text-sm shadow-sm outline-none focus:border-blue-500" /></div>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-600 uppercase mb-1">N° Remisión</label>
          <app-select-buscar [opciones]="d.remisiones" valor="numero_remision" texto="numero_remision" placeholder="- Seleccione Remisión -" [(ngModel)]="f.numero_remision" />
        </div>

        <div class="bg-gray-50 rounded-xl shadow-sm border border-gray-200 p-3 space-y-3">
          <div class="flex justify-between items-center border-b border-gray-300 pb-2">
            <h2 class="font-bold text-gray-700 text-sm uppercase"><i class="fas fa-box-open text-blue-500 mr-1 text-lg"></i> Componentes</h2>
            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-bold">{{ rep?.totalItems || 0 }}</span>
          </div>
          <button type="button" (click)="rep.abrir()" class="w-full border-2 border-dashed border-blue-300 text-blue-600 font-bold py-3 rounded-lg hover:bg-blue-50 transition flex items-center justify-center gap-2 bg-white"><i class="fas fa-plus"></i> Añadir Componente (Repuesto)</button>
          <app-repuestos-tecnico #rep [catalogo]="d.todosLosRepuestos" [inventario]="d.inventario" [conStock]="true" (cambio)="cambioRepuestos($event)" />
        </div>

        <div>
          <label class="block text-xs font-bold text-orange-600 uppercase mb-1">Pendientes</label>
          <textarea [(ngModel)]="f.pendientes" rows="2" placeholder="N/A o detalle pendientes..." class="w-full bg-orange-50 border border-orange-200 rounded-lg p-3 text-sm text-gray-800 shadow-sm outline-none focus:border-orange-500"></textarea>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1">Nombre admin</label><input type="text" [(ngModel)]="f.administrador_punto" placeholder="Juana ..." class="w-full bg-white border border-gray-300 rounded-lg p-3 text-sm text-gray-800 shadow-sm outline-none focus:border-blue-500" /></div>
          <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1">Celular</label><input type="tel" [(ngModel)]="f.celular_encargado" placeholder="321257..." class="w-full bg-white border border-gray-300 rounded-lg p-3 text-sm text-gray-800 shadow-sm outline-none focus:border-blue-500" /></div>
        </div>
        <div>
          <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Estado inicial</label>
          <app-select-buscar [opciones]="d.estados" valor="id_estado" texto="nombre_estado" placeholder="- Seleccione Estado Inicial -" [(ngModel)]="f.id_estado_inicial" />
        </div>
        <div>
          <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Novedad estado inicial <span class="text-gray-400 normal-case font-medium">(opcional)</span></label>
          <textarea [(ngModel)]="f.novedad_estado_inicial" rows="2" maxlength="500" placeholder="Ej: máquina apagada, sin energía, no enciende..." class="w-full bg-white border border-gray-300 rounded-lg p-3 text-sm text-gray-800 shadow-sm outline-none focus:border-blue-500"></textarea>
          <div class="flex flex-wrap gap-2 mt-2">
            @for (r of rapidas; track r[0]) {
              <button type="button" (click)="f.novedad_estado_inicial = r[1]" class="text-[11px] font-bold bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-full px-3 py-1 transition">{{ r[0] }}</button>
            }
          </div>
        </div>
        <div>
          <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Estado final</label>
          <app-select-buscar [opciones]="d.estados" valor="id_estado" texto="nombre_estado" placeholder="- Seleccione Estado -" [(ngModel)]="f.id_estado_maquina" />
        </div>

        <div class="bg-red-50 border border-red-200 rounded-xl p-4 space-y-3">
          <div class="flex items-center justify-between border-b border-red-200 pb-2">
            <h2 class="font-bold text-red-800 text-xs uppercase"><i class="fas fa-exclamation-triangle mr-1 text-lg"></i> Novedades del Servicio</h2>
            <span class="text-[10px] font-bold px-2 py-1 rounded-full" [class]="(nov?.actuales?.length || 0) > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700'">{{ (nov?.actuales?.length || 0) > 0 ? nov.actuales.length + ' novedad(es)' : 'Sin novedades' }}</span>
          </div>
          <button type="button" (click)="nov.abrir()" class="w-full border-2 border-dashed border-red-300 text-red-600 font-bold py-3 rounded-lg hover:bg-red-100 transition flex items-center justify-center gap-2 bg-white"><i class="fas fa-plus"></i> Reportar Novedad</button>
        </div>

        <hr class="border-gray-200 my-4" />
        <div class="space-y-5">
          <h3 class="font-bold text-gray-400 text-xs uppercase text-center">--- Completar para el Sistema ---</h3>
          <div>
            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Actividades Realizadas</label>
            <textarea [(ngModel)]="f.actividades_realizadas" rows="3" placeholder="Describa el trabajo realizado..." class="w-full bg-gray-50 border border-gray-300 rounded-lg p-3 text-sm text-gray-800 shadow-sm outline-none focus:border-blue-500"></textarea>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Soporte Remoto</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-headset text-gray-400"></i></div>
              <input type="text" [(ngModel)]="f.soporte_remoto" placeholder="Nombre de quien apoyó..." class="w-full bg-white border border-gray-300 rounded-lg py-3 pl-10 pr-3 text-sm text-gray-800 shadow-sm outline-none focus:border-blue-500" />
            </div>
          </div>
          <div class="bg-gray-50 rounded-xl shadow-sm border border-gray-200 p-4 space-y-4">
            <div class="flex items-center gap-2 mb-1 border-b pb-2"><i class="fas fa-camera text-indigo-500 text-lg"></i><h2 class="font-bold text-gray-700 text-sm uppercase">Evidencia Fotográfica</h2></div>
            <p class="text-xs text-gray-500">Requerido: 8 a 10 fotos en total.</p>
            <app-evidencias-tecnico #ev [idOrden]="o.id_ordenes_servicio" [remision]="f.numero_remision" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Firma / Calificación del Cliente</label>
            <app-select-buscar [opciones]="d.calificaciones" valor="id_calificacion" texto="nombre_calificacion" placeholder="- Seleccione Calificación -" [(ngModel)]="f.id_calificacion" />
          </div>
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 space-y-3">
            <div class="flex items-center gap-2 mb-1 border-b pb-2"><i class="fas fa-signature text-blue-500 text-lg"></i><h2 class="font-bold text-gray-700 text-sm uppercase">Firma del Cliente</h2></div>
            <div class="border-2 border-dashed border-gray-300 rounded-lg overflow-hidden bg-gray-50"><app-firma-canvas #firma (cambio)="firmaBase64 = firma.dataUrl()" /></div>
            <button type="button" (click)="firma.limpiar(); firmaBase64 = ''" class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 rounded-lg text-sm transition flex items-center justify-center gap-2"><i class="fas fa-eraser"></i> Limpiar Firma</button>
          </div>
        </div>
      </div>

      <div class="fixed bottom-0 left-0 w-full bg-white shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.1)] p-4 z-40 border-t border-gray-200">
        <button type="button" [disabled]="enviando" (click)="validarYEnviar()" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-4 rounded-xl shadow-lg transform active:scale-95 transition text-lg flex items-center justify-center gap-2">
          @if (enviando) { <i class="fas fa-spinner fa-spin"></i> PROCESANDO... } @else { <i class="fas fa-check-circle"></i> GUARDAR Y FINALIZAR }
        </button>
      </div>

      <app-novedades-tecnico #nov [idOrden]="o.id_ordenes_servicio" (guardado)="guardarBorrador()" />
    }
  `
})
export class TecnicoReporte implements OnInit, OnDestroy {
  private api = inject(Api);
  private route = inject(ActivatedRoute);
  private router = inject(Router);

  @ViewChild('rep') rep!: RepuestosTecnico;
  @ViewChild('nov') nov!: NovedadesTecnico;
  @ViewChild('firma') firma!: FirmaCanvas;
  @ViewChild('ev') ev!: EvidenciasTecnico;

  d: any = {};
  o: any = null;
  f: any = {};
  repuestos: RepTec[] = [];
  jsonRepuestos = '[]';
  firmaBase64 = '';
  fechaApertura = '';
  modificado = false;
  enviando = false;
  rapidas = [
    ['Máquina apagada', 'máquina apagada'],
    ['Sin energía', 'sin energía en el punto'],
    ['No enciende', 'no enciende']
  ];
  private timers: any[] = [];

  get claveBorrador() {
    return 'borrador_orden_' + this.o?.id_ordenes_servicio;
  }

  dmy = fechaDmy;

  async ngOnInit() {
    const id = this.route.snapshot.queryParamMap.get('orden') || this.route.snapshot.paramMap.get('orden') || '0';
    const r: any = await this.api.get('tecnicoReporte', 'index', { orden: id });
    if (r.error) {
      alert(r.error);
      if (r.redirect) this.router.navigateByUrl('/' + r.redirect);
      else history.back();
      return;
    }
    this.d = r;
    this.o = r.orden;
    this.fechaApertura = r.fechaApertura;
    this.f = Object.fromEntries(CAMPOS.map((k) => [k, '']));
    this.f.id_tipo_mantenimiento = this.o.id_tipo_mantenimiento ?? '';
    this.f.id_modalidad = this.o.id_modalidad ?? '';
    this.f.hora_entrada = r.horaEntradaInicial || '';
    this.f.tiempo_servicio = '00:00';

    setTimeout(async () => {
      // Novedades ya registradas en el servidor
      const ids = await this.nov.consultar();
      this.nov.fusionar(ids);
      this.revisarBorrador();
    });
    this.timers.push(
      setInterval(async () => {
        try {
          const v: any = await this.api.post('tecnicoReporte', 'ajaxVerificarModificacion', { id_orden: this.o.id_ordenes_servicio, fecha_apertura: this.fechaApertura });
          if (v.fue_modificado) this.modificado = true;
        } catch {}
      }, 30000)
    );
  }

  ngOnDestroy() {
    this.timers.forEach((t) => clearInterval(t));
  }

  volver() {
    history.back();
  }

  recargar(e: Event) {
    e.preventDefault();
    location.reload();
  }

  soloHora(e: Event, campo: string) {
    const el = e.target as HTMLInputElement;
    el.value = el.value.replace(/[^0-9:]/g, '');
    this.f[campo] = el.value;
  }

  blurHora(campo: string) {
    this.f[campo] = formatearHoraMilitar(this.f[campo]);
    this.f.tiempo_servicio = calcularTiempo(this.f.hora_entrada, this.f.hora_salida);
  }

  cambioRepuestos(l: RepTec[]) {
    this.repuestos = l;
    this.jsonRepuestos = JSON.stringify(l);
  }

  // ================================================================ AUTOGUARDADO
  guardarBorrador() {
    if (!this.o) return;
    const b: any = { ...this.f };
    b.id_ordenes_servicio = String(this.o.id_ordenes_servicio);
    b.id_cliente = String(this.o.id_cliente ?? '');
    b.id_punto = String(this.o.id_punto ?? '');
    b.fecha_apertura = this.fechaApertura;
    b.json_repuestos = this.jsonRepuestos;
    b.firma_base64 = this.firmaBase64;
    b.tiene_novedad = this.nov?.tiene ?? '0';
    b.novedades_seleccionadas = (this.nov?.actuales || []).join(',');
    b.novedades_gestionada = this.nov?.gestionada ? '1' : '0';
    b.detalle_novedad = '';
    b.lista_repuestos = this.repuestos;
    b.novedades = this.nov?.actuales || [];
    if (this.firma && !this.firma.vacia) b.firma_canvas = this.firma.dataUrl();
    try {
      localStorage.setItem(this.claveBorrador, JSON.stringify(b));
    } catch {}
  }

  private iniciarMotor() {
    this.timers.push(setInterval(() => this.guardarBorrador(), 5000));
  }

  private revisarBorrador() {
    let datos: string | null = null;
    try {
      datos = localStorage.getItem(this.claveBorrador);
    } catch {}
    if (!datos) return this.iniciarMotor();
    Swal.fire({
      title: '¡Borrador Encontrado!',
      text: 'Tienes información sin guardar de una visita anterior a esta orden. ¿Quieres recuperarla o empezar de cero?',
      icon: 'info',
      showCancelButton: true,
      confirmButtonColor: '#2563eb',
      cancelButtonColor: '#d33',
      confirmButtonText: '<i class="fas fa-undo"></i> Sí, recuperar datos',
      cancelButtonText: '<i class="fas fa-trash"></i> No, empezar de cero',
      allowOutsideClick: false
    }).then(async (res) => {
      if (res.isConfirmed) {
        let b: any = {};
        try {
          b = JSON.parse(datos!);
        } catch {}
        CAMPOS.forEach((k) => {
          if (k in b) this.f[k] = b[k] ?? '';
        });
        if (b.firma_base64) this.firmaBase64 = b.firma_base64;
        if (b.novedades && b.novedades.length > 0) {
          this.nov.fusionar(b.novedades.map(String));
          this.nov.gestionada = true;
        }
        if (b.lista_repuestos && b.lista_repuestos.length > 0) this.rep.establecer(b.lista_repuestos);
        if (b.firma_canvas) this.firma.cargar(b.firma_canvas);
        this.f.tiempo_servicio = calcularTiempo(this.f.hora_entrada, this.f.hora_salida);
        NotifTec.toast('Información recuperada', 'success');
      } else {
        try {
          localStorage.removeItem(this.claveBorrador);
        } catch {}
        await this.api.post('tecnicoReporte', 'ajaxLimpiarEvidenciasOrden', { id_orden: this.o.id_ordenes_servicio });
        this.ev?.cargar();
        NotifTec.toast('Empezando de cero...', 'info');
      }
      this.iniciarMotor();
    });
  }

  // ================================================================ VALIDAR Y ENVIAR (validaciones.js)
  private texto(lista: any[], campo: string, valor: string, id: any) {
    return String(lista?.find((x) => String(x[campo]) === String(id))?.[valor] ?? '');
  }

  validarYEnviar() {
    const hayFirma = this.firma.tieneFirma();
    if (hayFirma) this.firmaBase64 = this.firma.dataUrl();
    if (!hayFirma && !this.firmaBase64) {
      alert('⚠️ Por favor, realiza la firma del cliente en el cuadro de arriba.');
      return;
    }
    let tiempo = this.f.tiempo_servicio;
    if (tiempo === '00:00') {
      tiempo = this.f.tiempo_servicio = calcularTiempo(this.f.hora_entrada, this.f.hora_salida);
      if (tiempo === '00:00' && !(this.f.hora_entrada && this.f.hora_salida)) {
        alert('⚠️ Por favor, selecciona la hora de entrada y salida antes de guardar.');
        return;
      }
    }
    if (!this.f.id_tipo_mantenimiento) return alert('⚠️ Por favor, selecciona el Tipo de Servicio.');
    if (!this.f.numero_remision) return alert('⚠️ Por favor, selecciona un número de remisión.');
    const totalFotos = this.ev?.total || 0;
    if (totalFotos < 8 && !confirm('⚠️ Solo has subido ' + totalFotos + ' fotos. Se recomiendan mínimo 8 fotos (Antes, Remisión, Después). ¿Deseas continuar?')) return;
    if (!this.f.id_estado_maquina) return alert('⚠️ Por favor, selecciona el Estado Final de la máquina.');
    this.f.actividades_realizadas = componerActividades(this.f.novedad_estado_inicial, this.f.actividades_realizadas);
    if (!this.f.actividades_realizadas.trim()) return alert('⚠️ Por favor, describe las actividades realizadas.');
    const tipoTxt = this.texto(this.d.tiposManto, 'id_tipo_mantenimiento', 'nombre_completo', this.f.id_tipo_mantenimiento);
    if (tipoTxt.toUpperCase().trim().includes('CORRECTIVO') && this.repuestos.length === 0) {
      if (!confirm('⚠️ ¡ADVERTENCIA!\n\nEste servicio es Mantenimiento CORRECTIVO pero NO has agregado repuestos.\nEsto es inusual.\n\n¿Estás seguro de guardar sin repuestos?')) return;
    }
    const novRes = (this.f.novedad_estado_inicial || '').trim();
    let resumen = '📋 Resumen del Servicio:\n\n';
    resumen += '🕐 Tiempo: ' + this.f.tiempo_servicio + ' hrs\n';
    resumen += '📸 Fotos: ' + totalFotos + '\n';
    resumen += '🔧 Tipo: ' + tipoTxt + '\n';
    resumen += '📦 Remisión: ' + this.f.numero_remision + '\n';
    resumen += '✅ Estado Final: ' + this.texto(this.d.estados, 'id_estado', 'nombre_estado', this.f.id_estado_maquina) + '\n';
    if (!confirm(resumen + (novRes ? '🔔 Novedad inicial: ' + novRes + '\n' : '') + '\n¿Guardar este servicio?')) return;
    this.enviando = true;
    this.capturarGpsYEnviar();
  }

  private capturarGpsYEnviar() {
    Swal.fire({ title: 'Cerrando Servicio...', text: 'Capturando coordenadas finales y subiendo datos...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    if (!navigator.onLine) {
      this.enviando = false;
      Swal.fire('Sin Conexión', 'No tienes internet en este momento. Los datos están a salvo en el borrador. Busca señal e intenta guardar nuevamente.', 'warning');
      return;
    }
    if (!navigator.geolocation) {
      this.enviar('', '');
      return;
    }
    navigator.geolocation.getCurrentPosition(
      (p) => this.enviar(String(p.coords.latitude), String(p.coords.longitude)),
      () => {
        this.enviando = false;
        Swal.fire('GPS Obligatorio', 'No se pudo obtener la ubicación. Sal a un lugar despejado y asegúrate de tener el GPS encendido.', 'error');
      },
      { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
  }

  private async enviar(lat: string, lon: string) {
    const fd = new FormData();
    CAMPOS.forEach((k) => fd.append(k, String(this.f[k] ?? '')));
    fd.append('id_ordenes_servicio', String(this.o.id_ordenes_servicio));
    fd.append('id_cliente', String(this.o.id_cliente ?? ''));
    fd.append('id_punto', String(this.o.id_punto ?? ''));
    fd.append('fecha_apertura', this.fechaApertura);
    fd.append('latitud_fin', lat);
    fd.append('longitud_fin', lon);
    fd.append('json_repuestos', this.jsonRepuestos);
    fd.append('tiene_novedad', this.nov.tiene);
    fd.append('novedades_seleccionadas', this.nov.actuales.join(','));
    fd.append('novedades_gestionada', this.nov.gestionada ? '1' : '0');
    fd.append('detalle_novedad', '');
    fd.append('firma_base64', this.firmaBase64);
    try {
      const data: any = await this.api.post('tecnicoReporte', 'guardar', fd);
      if (data.success) {
        try {
          localStorage.removeItem(this.claveBorrador);
        } catch {}
        this.timers.forEach((t) => clearInterval(t));
        await Swal.fire('¡Éxito!', data.msj, 'success');
        this.router.navigateByUrl('/tecnicoProgramacion');
      } else {
        this.enviando = false;
        Swal.fire('Error del Servidor', data.msj, 'error');
      }
    } catch (e: any) {
      this.enviando = false;
      if (e?.status === 401 || e?.status === 403) {
        Swal.fire('Sesión Expirada', 'Tu sesión se cerró por inactividad. Abre una nueva pestaña, inicia sesión nuevamente, vuelve a esta pestaña y dale a Guardar. ¡Tus datos están a salvo en el autoguardado!', 'warning');
      } else {
        Swal.fire('Fallo de Red', 'La señal de internet falló justo al enviar. Tus datos están guardados en el borrador. Intenta presionar Guardar de nuevo.', 'error');
      }
    }
  }
}

// =====================================================================================
// EDITAR (tecnicoReporteEditarVista.php + validacionesEditar.js)
// =====================================================================================
@Component({
  selector: 'app-tecnico-reporte-editar',
  imports: [FormsModule, SelectBuscar, FirmaCanvas, EvidenciasTecnico, RepuestosTecnico, NovedadesTecnico],
  template: `
    @if (o) {
      <div class="container mx-auto p-2 sm:p-4 max-w-3xl mb-24 space-y-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
          <h2 class="text-lg font-bold text-gray-800 border-b pb-2 mb-2"><i class="fas fa-edit text-amber-500 mr-2"></i> Editando Servicio #{{ o.id_ordenes_servicio }}</h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
            <div><span class="font-semibold text-gray-600">Cliente:</span> {{ o.nombre_cliente || 'N/A' }}</div>
            <div><span class="font-semibold text-gray-600">Punto:</span> {{ o.nombre_punto || 'N/A' }}</div>
            <div class="sm:col-span-2 text-gray-500"><i class="fas fa-map-marker-alt"></i> {{ o.direccion_punto || 'N/A' }}</div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
          <h3 class="font-bold text-gray-700 mb-3"><i class="fas fa-clock text-blue-500 mr-1"></i> Tiempos y Remisión</h3>
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Hora Entrada *</label><input type="time" [(ngModel)]="f.hora_entrada" (blur)="recalcular()" class="w-full border border-gray-300 rounded-md p-2 shadow-sm" /></div>
            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Hora Salida *</label><input type="time" [(ngModel)]="f.hora_salida" (blur)="recalcular()" class="w-full border border-gray-300 rounded-md p-2 shadow-sm" /></div>
          </div>
          <div class="mb-4"><label class="block text-xs font-semibold text-gray-600 mb-1">Tiempo de Servicio</label><input type="text" readonly [value]="f.tiempo_servicio" class="w-full bg-gray-100 border border-gray-300 rounded-md p-2 shadow-sm text-gray-600 font-bold" /></div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Nº Remisión *</label>
            <app-select-buscar [opciones]="remisiones" valor="numero_remision" texto="texto" placeholder="Seleccione..." [(ngModel)]="f.numero_remision" />
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
          <h3 class="font-bold text-gray-700 mb-3"><i class="fas fa-server text-blue-500 mr-1"></i> Datos de la Máquina</h3>
          <div class="grid grid-cols-2 gap-4">
            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Nº Máquina</label><input type="text" [(ngModel)]="f.numero_maquina" class="w-full border border-gray-300 rounded-md p-2 shadow-sm" /></div>
            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Serial Máquina</label><input type="text" [(ngModel)]="f.serial_maquina" class="w-full border border-gray-300 rounded-md p-2 shadow-sm" /></div>
            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Serial Router</label><input type="text" [(ngModel)]="f.serial_router" class="w-full border border-gray-300 rounded-md p-2 shadow-sm" /></div>
            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Serial UPS</label><input type="text" [(ngModel)]="f.serial_ups" class="w-full border border-gray-300 rounded-md p-2 shadow-sm" /></div>
            <div class="col-span-2">
              <label class="block text-xs font-semibold text-gray-600 mb-1">Novedad estado inicial <span class="text-gray-400 font-normal">(opcional)</span></label>
              <textarea [(ngModel)]="f.novedad_estado_inicial" rows="2" maxlength="500" placeholder="Ej: máquina apagada, sin energía..." class="w-full border border-gray-300 rounded-md p-2 shadow-sm"></textarea>
            </div>
            <div class="col-span-2">
              <label class="block text-xs font-semibold text-gray-600 mb-1">Estado Inicial</label>
              <select [(ngModel)]="f.id_estado_inicial" class="w-full border border-gray-300 rounded-md p-2 shadow-sm">
                <option value="">Seleccione...</option>
                @for (e of d.estados; track e.id_estado) { <option [value]="'' + e.id_estado">{{ e.nombre_estado }}</option> }
              </select>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
          <h3 class="font-bold text-gray-700 mb-3"><i class="fas fa-tools text-blue-500 mr-1"></i> Detalles del Trabajo</h3>
          <div class="mb-4">
            <label class="block text-xs font-semibold text-gray-600 mb-1">Tipo de Servicio *</label>
            <app-select-buscar [opciones]="d.tiposManto" valor="id_tipo_mantenimiento" texto="nombre_completo" placeholder="Seleccione..." [(ngModel)]="f.id_tipo_mantenimiento" />
          </div>
          <div class="mb-4">
            <label class="block text-xs font-semibold text-gray-600 mb-1">Modalidad Operativa *</label>
            <app-select-buscar [opciones]="d.modalidades" valor="id_modalidad" texto="nombre_modalidad" placeholder="Seleccione..." [(ngModel)]="f.id_modalidad" />
          </div>
          <div class="mb-4"><label class="block text-xs font-semibold text-gray-600 mb-1">Actividades Realizadas *</label><textarea [(ngModel)]="f.actividades_realizadas" rows="3" class="w-full border border-gray-300 rounded-md p-2 shadow-sm"></textarea></div>
          <div class="mb-4"><label class="block text-xs font-semibold text-gray-600 mb-1">Pendientes</label><textarea [(ngModel)]="f.pendientes" rows="2" class="w-full border border-gray-300 rounded-md p-2 shadow-sm"></textarea></div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
          <div class="flex justify-between items-center mb-3">
            <h3 class="font-bold text-gray-700"><i class="fas fa-cogs text-blue-500 mr-1"></i> Repuestos Usados</h3>
            <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2 py-1 rounded-full">{{ rep?.totalItems || 0 }}</span>
          </div>
          <app-repuestos-tecnico #rep [catalogo]="d.inventario" [inventario]="null" [conStock]="false" (cambio)="cambioRepuestos($event)" />
          <button type="button" (click)="rep.abrir()" class="w-full mt-3 bg-gray-50 hover:bg-gray-100 text-blue-600 font-semibold py-2 px-4 border border-blue-200 rounded-md shadow-sm transition flex items-center justify-center gap-2"><i class="fas fa-plus-circle"></i> Modificar Repuestos</button>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
          <h3 class="font-bold text-gray-700 mb-3"><i class="fas fa-flag-checkered text-blue-500 mr-1"></i> Cierre</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Estado Final *</label>
              <select [(ngModel)]="f.id_estado_maquina" class="w-full border border-gray-300 rounded-md p-2 shadow-sm">
                <option value="">Seleccione...</option>
                @for (e of d.estados; track e.id_estado) { <option [value]="'' + e.id_estado">{{ e.nombre_estado }}</option> }
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Calificación del Servicio</label>
              <select [(ngModel)]="f.id_calificacion" class="w-full border border-gray-300 rounded-md p-2 shadow-sm">
                <option value="">Seleccione...</option>
                @for (c of d.calificaciones; track c.id_calificacion) { <option [value]="'' + c.id_calificacion">{{ c.nombre_calificacion }}</option> }
              </select>
            </div>
          </div>
          <div class="mb-4"><label class="block text-xs font-semibold text-gray-600 mb-1">Soporte Remoto (Opcional)</label><input type="text" [(ngModel)]="f.soporte_remoto" placeholder="Nombre de quien dio soporte" class="w-full border border-gray-300 rounded-md p-2 shadow-sm" /></div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Administrador del Punto</label><input type="text" [(ngModel)]="f.administrador_punto" class="w-full border border-gray-300 rounded-md p-2 shadow-sm" /></div>
            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Celular Encargado</label><input type="tel" [(ngModel)]="f.celular_encargado" class="w-full border border-gray-300 rounded-md p-2 shadow-sm" /></div>
          </div>
          <div class="bg-red-50 border border-red-200 rounded-lg p-4 space-y-3 mt-4">
            <div class="flex items-center justify-between border-b border-red-200 pb-2">
              <h3 class="font-bold text-red-800 text-xs uppercase"><i class="fas fa-exclamation-triangle mr-1 text-lg"></i> Novedades del Servicio</h3>
              <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" [class]="(nov?.actuales?.length || 0) > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700'">{{ !novCargadas ? 'Cargando...' : (nov.actuales.length > 0 ? nov.actuales.length + ' novedad(es)' : 'Sin novedades') }}</span>
            </div>
            <button type="button" (click)="nov.abrir()" class="w-full border-2 border-dashed border-red-300 text-red-600 font-bold py-2 px-4 rounded-md hover:bg-red-100 transition flex items-center justify-center gap-2 bg-white"><i class="fas fa-edit"></i> Modificar Novedades</button>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
          <h3 class="font-bold text-gray-700 mb-3"><i class="fas fa-camera text-blue-500 mr-1"></i> Evidencias Fotográficas</h3>
          <p class="text-xs text-gray-500 mb-4">Las fotos se guardan automáticamente al seleccionarlas.</p>
          <app-evidencias-tecnico [idOrden]="o.id_ordenes_servicio" [remision]="f.numero_remision" [modoEditar]="true" />
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
          <h3 class="font-bold text-gray-700 mb-3"><i class="fas fa-signature text-blue-500 mr-1"></i> Firma del Cliente</h3>
          <p class="text-xs text-amber-600 font-semibold mb-2"><i class="fas fa-info-circle"></i> Ya existe una firma guardada. Dibuja aquí solo si deseas reemplazarla.</p>
          <div class="border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 overflow-hidden"><app-firma-canvas #firma /></div>
          <div class="flex justify-end mt-2"><button type="button" (click)="firma.limpiar()" class="text-xs text-red-500 hover:text-red-700 font-semibold px-3 py-1"><i class="fas fa-eraser"></i> Limpiar Pizarra</button></div>
        </div>

        <button type="button" [disabled]="enviando" (click)="validarYActualizar()" class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-4 rounded-lg shadow-lg text-lg flex items-center justify-center gap-2 transition duration-200">
          @if (enviando) { <i class="fas fa-spinner fa-spin"></i> ACTUALIZANDO... } @else { <i class="fas fa-sync-alt"></i> Actualizar Reporte }
        </button>
      </div>
      <app-novedades-tecnico #nov [idOrden]="o.id_ordenes_servicio" />
    }
  `
})
export class TecnicoReporteEditar implements OnInit {
  private api = inject(Api);
  private route = inject(ActivatedRoute);
  private router = inject(Router);

  @ViewChild('rep') rep!: RepuestosTecnico;
  @ViewChild('nov') nov!: NovedadesTecnico;
  @ViewChild('firma') firma!: FirmaCanvas;

  d: any = {};
  o: any = null;
  f: any = {};
  remisiones: any[] = [];
  repuestos: RepTec[] = [];
  jsonRepuestos = '';
  tieneNovedadPrevio = '0';
  detalleNovedad = '';
  novCargadas = false;
  enviando = false;

  async ngOnInit() {
    const id = this.route.snapshot.queryParamMap.get('orden') || '0';
    const r: any = await this.api.get('tecnicoReporteEditar', 'index', { orden: id });
    if (r.error) {
      alert(r.error);
      history.back();
      return;
    }
    this.d = r;
    this.o = r.orden;
    const g = r.reporteGuardado || {};
    const hm = (v: any) => (v ? String(v).substring(0, 5) : '');
    this.f = {
      hora_entrada: hm(g.hora_entrada),
      hora_salida: hm(g.hora_salida),
      tiempo_servicio: g.tiempo_servicio ?? '00:00',
      numero_remision: g.numero_remision ?? '',
      numero_maquina: g.numero_maquina ?? '',
      serial_maquina: g.serial_maquina ?? '',
      serial_router: g.serial_router ?? '',
      serial_ups: g.serial_ups ?? '',
      novedad_estado_inicial: r.novedadPrevia ?? '',
      id_estado_inicial: g.id_estado_inicial != null ? String(g.id_estado_inicial) : '',
      id_tipo_mantenimiento: g.id_tipo_mantenimiento ?? '',
      id_modalidad: this.o.id_modalidad ?? 1,
      actividades_realizadas: g.actividades_realizadas ?? '',
      pendientes: g.pendientes ?? '',
      id_estado_maquina: g.id_estado_maquina != null ? String(g.id_estado_maquina) : '',
      id_calificacion: g.id_calificacion != null ? String(g.id_calificacion) : '',
      soporte_remoto: g.soporte_remoto ?? '',
      administrador_punto: g.administrador_punto ?? '',
      celular_encargado: g.celular_encargado ?? ''
    };
    this.tieneNovedadPrevio = g.tiene_novedad ? '1' : '0';
    this.detalleNovedad = g.detalle_novedad ?? '';
    // Remisión guardada primero, luego las disponibles
    this.remisiones = [];
    if (g.numero_remision) this.remisiones.push({ numero_remision: g.numero_remision, texto: g.numero_remision + ' (Guardada)' });
    (r.remisiones || []).forEach((x: any) => {
      if (x.numero_remision !== g.numero_remision) this.remisiones.push({ numero_remision: x.numero_remision, texto: x.numero_remision });
    });
    setTimeout(async () => {
      if (r.repuestosGuardados?.length > 0) this.rep.establecer(r.repuestosGuardados.map((x: any) => ({ id: x.id, nombre: x.nombre, cantidad: x.cantidad, origen: x.origen })));
      const ids = await this.nov.consultar();
      this.nov.fusionar(ids);
      this.novCargadas = true;
    });
  }

  recalcular() {
    this.f.tiempo_servicio = calcularTiempo(this.f.hora_entrada, this.f.hora_salida);
  }

  cambioRepuestos(l: RepTec[]) {
    this.repuestos = l;
    this.jsonRepuestos = JSON.stringify(l);
  }

  async validarYActualizar() {
    const firma = this.firma.tieneFirma() ? this.firma.dataUrl() : '';
    let tiempo = this.f.tiempo_servicio;
    if (tiempo === '00:00' || !tiempo) tiempo = this.f.tiempo_servicio = calcularTiempo(this.f.hora_entrada, this.f.hora_salida);
    if (tiempo === '00:00') return alert('⚠️ Selecciona la hora de entrada y salida.');
    if (!this.f.id_tipo_mantenimiento) return alert('⚠️ Selecciona el Tipo de Servicio.');
    if (!this.f.numero_remision) return alert('⚠️ Selecciona un número de remisión.');
    if (!this.f.id_estado_maquina) return alert('⚠️ Selecciona el Estado Final.');
    this.f.actividades_realizadas = componerActividades(this.f.novedad_estado_inicial, this.f.actividades_realizadas);
    if (!this.f.actividades_realizadas.trim()) return alert('⚠️ Describe las actividades.');
    const tipoTxt = String(this.d.tiposManto?.find((x: any) => String(x.id_tipo_mantenimiento) === String(this.f.id_tipo_mantenimiento))?.nombre_completo ?? '');
    if (tipoTxt.toUpperCase().trim().includes('CORRECTIVO') && this.repuestos.length === 0) {
      if (!confirm('⚠️ ¡ADVERTENCIA!\n\nEste servicio es Mantenimiento CORRECTIVO pero NO has agregado repuestos.\nEsto es inusual.\n\n¿Estás seguro de guardar sin repuestos?')) return;
    }
    if (!confirm('¿Estás seguro de guardar estos cambios?')) return;

    const fd = new FormData();
    Object.entries(this.f).forEach(([k, v]) => fd.append(k, String(v ?? '')));
    fd.append('id_ordenes_servicio', String(this.o.id_ordenes_servicio));
    fd.append('json_repuestos', this.jsonRepuestos);
    fd.append('tiene_novedad', this.nov.actuales.length > 0 ? '1' : this.nov.gestionada ? '0' : this.tieneNovedadPrevio);
    fd.append('novedades_seleccionadas', this.nov.actuales.join(','));
    fd.append('novedades_gestionada', this.nov.gestionada ? '1' : '0');
    fd.append('detalle_novedad', this.detalleNovedad);
    fd.append('firma_base64', firma);
    this.enviando = true;
    try {
      const data: any = await this.api.post('tecnicoReporteEditar', 'actualizar', fd);
      if (data.success) {
        await Swal.fire('¡Actualizado!', data.msj, 'success');
        this.router.navigateByUrl('/tecnicoProgramacion');
      } else {
        this.enviando = false;
        Swal.fire('Error', data.msj, 'error');
      }
    } catch (e: any) {
      this.enviando = false;
      if (e?.status === 401 || e?.status === 403) Swal.fire('Sesión Expirada', 'Tu sesión se cerró. Abre una nueva pestaña, inicia sesión y vuelve a intentar.', 'warning');
      else Swal.fire('Error de Red', 'Fallo de conexión. Verifica tu internet.', 'error');
    }
  }
}
