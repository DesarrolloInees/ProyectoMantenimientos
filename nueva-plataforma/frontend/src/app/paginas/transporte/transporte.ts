import { Component, EventEmitter, Input, OnInit, Output, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import Swal from 'sweetalert2';
import { Api } from '../../core/api';
import { SelectBuscar } from '../../shared/select-buscar';
import { DtAbajo, DtArriba, TablaDatos, Th } from '../../shared/tabla';

/**
 * Combo "select2 con tags": se elige de la lista o se escribe un texto libre.
 * Emite el id (si coincide con una opción) o el texto escrito, igual que el PHP.
 */
@Component({
  selector: 'app-combo-libre',
  imports: [FormsModule],
  template: `
    <input type="text" class="form-control w-full" [attr.list]="lista" [placeholder]="placeholder" [ngModel]="texto" (ngModelChange)="cambiar($event)">
    <datalist [id]="lista">
      @for (o of opciones; track o[valor]) { <option [value]="o[campo]"></option> }
    </datalist>
  `
})
export class ComboLibre {
  private static n = 0;
  lista = 'combo-libre-' + ComboLibre.n++;
  @Input() opciones: any[] = [];
  @Input() valor = 'id';
  @Input() campo = 'nombre';
  @Input() placeholder = 'Seleccione de la lista o escriba uno nuevo...';
  @Input() texto = '';
  @Output() cambio = new EventEmitter<string>();

  cambiar(t: string) {
    this.texto = t;
    const o = this.opciones.find((x) => String(x[this.campo]).trim() === String(t).trim());
    this.cambio.emit(o ? String(o[this.valor]) : t);
  }
}

const TIPOS_COBRO = [
  ['Desmonte Definitivo', 'Desmonte Definitivo'],
  ['Desmonte Provicional', 'Desmonte Provicional'],
  ['Cambio - Instalación', 'Cambio de Máquina (Instalación)'],
  ['Cambio - Desinstalación', 'Cambio de Máquina (Desinstalación)'],
  ['Instalación Punto Nuevo', 'Instalación Punto Nuevo'],
  ['Traslados Prosegur', 'Traslados Prosegur']
];
const TIPOS_NOCOBRO = ['Maquinas Para Bodegaje', 'Máquinas Para Recuperar'];
const TIPOS_INEES = ['Malaver', 'Internos Don Antonio Osorio', 'Internos Inees'];

const ESTILOS = `
  .form-section { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
  .form-section-title { font-weight: 700; color: #4f46e5; font-size: 14px; text-transform: uppercase; border-bottom: 2px solid #e0e7ff; padding-bottom: 8px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
  .form-label { display: block; font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 4px; }
  .form-control { width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 10px; font-size: 14px; background: #fff; }
  .form-control:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,.2); }
  .req { color: #ef4444; }
  .caja-tarifa { background: #ecfdf5; border-color: #a7f3d0; }
`;

// =====================================================================================
// FORMULARIO CREAR / EDITAR (transporteCrearVista.php / transporteEditarVista.php)
// =====================================================================================
@Component({
  selector: 'app-transporte-form',
  imports: [FormsModule, RouterLink, SelectBuscar, ComboLibre],
  styles: [ESTILOS],
  template: `
    <div class="w-full max-w-5xl mx-auto px-2 py-4 md:py-6">
      <div class="bg-gradient-to-r from-gray-800 to-gray-700 rounded-xl p-5 mb-6 shadow-lg flex items-center justify-between">
        @if (editar) {
          <div>
            <h1 class="text-white font-bold text-xl"><i class="fas fa-edit mr-2 text-amber-400"></i> Editar Transporte #{{ f.id_instalacion }}</h1>
            <p class="text-gray-300 text-sm mt-1">Revisa los datos del técnico y asigna la tarifa de cobro correspondiente.</p>
          </div>
          <a routerLink="/transporteVer" class="text-white bg-gray-600 hover:bg-gray-500 px-4 py-2 rounded-lg text-sm font-bold transition"><i class="fas fa-arrow-left mr-1"></i> Regresar</a>
        } @else {
          <h1 class="text-white font-bold text-xl"><i class="fas fa-truck-loading mr-2 text-indigo-400"></i> Registro de Transporte</h1>
        }
      </div>
      @if (d) {
        <form (ngSubmit)="guardar()">
          <div class="form-section">
            <div class="form-section-title"><i class="fas fa-info-circle"></i> Datos Generales</div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
              <div class="md:col-span-2">
                <label class="form-label">Categoría del Servicio <span class="req">*</span></label>
                <select name="cat" [(ngModel)]="f.categoria_servicio" class="form-control" required>
                  <option value="">— Seleccione la categoría —</option>
                  <optgroup label="Prosegur"><option value="Prosegur_Cobro">Prosegur - Con Cobro</option><option value="Prosegur_NoCobro">Prosegur - Sin Cobro</option></optgroup>
                  <optgroup label="Inees"><option value="Inees">Transportes Inees (Internos)</option></optgroup>
                </select>
              </div>
              <div>
                <label class="form-label">Técnico Asignado <span class="req">*</span></label>
                <app-select-buscar name="tec" [opciones]="d.tecnicos" valor="id_tecnico" texto="nombre_tecnico" placeholder="— Seleccione —" [(ngModel)]="f.id_tecnico" (cambio)="cambioTecnico()" />
              </div>
              <div>
                <label class="form-label">Número Remisión</label>
                <app-select-buscar name="rem" [opciones]="remisiones" valor="id_control" texto="numero_remision" [placeholder]="f.id_tecnico ? '— Sin remisión —' : '— Elija técnico primero —'" [(ngModel)]="f.id_control_remision" />
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div><label class="form-label">Fecha de Realización <span class="req">*</span></label><input type="date" name="fecha" [(ngModel)]="f.fecha_instalacion" class="form-control" required></div>
              @if (f.categoria_servicio === 'Prosegur_Cobro') {
                <div><label class="form-label">Tipo Servicio (Cobro)</label>
                  <select name="tsc" [(ngModel)]="f.tipo_servicio_cobro" class="form-control">
                    @if (!editar) { <option value="">— Seleccione el servicio —</option> }
                    @for (t of tiposCobro; track t[0]) { <option [value]="t[0]">{{ t[1] }}</option> }
                  </select></div>
              }
              @if (f.categoria_servicio === 'Prosegur_NoCobro') {
                <div><label class="form-label">Tipo Servicio (No Cobro)</label><select name="tsn" [(ngModel)]="f.tipo_servicio_nocobro" class="form-control">@for (t of tiposNoCobro; track t) { <option [value]="t">{{ t }}</option> }</select></div>
              }
              @if (f.categoria_servicio === 'Inees') {
                <div><label class="form-label">Tipo Servicio (Inees)</label><select name="tsi" [(ngModel)]="f.tipo_servicio_inees" class="form-control">@for (t of tiposInees; track t) { <option [value]="t">{{ t }}</option> }</select></div>
              }
            </div>
          </div>

          @if (editar) {
            <div class="form-section caja-tarifa">
              <div class="form-section-title text-emerald-700 !border-emerald-200"><i class="fas fa-hand-holding-usd"></i> Tarifa de Cobro</div>
              <div class="w-full md:w-1/3">
                <label class="form-label text-emerald-800">Valor Final del Servicio</label>
                <div class="relative"><span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold">$</span>
                  <input type="text" name="valor" [ngModel]="f.valor_servicio" (ngModelChange)="formatearValor($event)" class="form-control pl-7 font-bold text-lg text-emerald-600" style="padding-left: 1.75rem"></div>
                <p class="text-xs text-emerald-600 mt-1">Escribe la tarifa a cobrar por esta operación.</p>
              </div>
            </div>
          }

          @if (f.categoria_servicio === 'Inees') {
            <div class="form-section">
              <div class="form-section-title"><i class="fas fa-building"></i> Detalles Inees</div>
              <label class="form-label">Descripción de lo que se hizo</label>
              <textarea name="di" [(ngModel)]="f.descripcion_inees" rows="3" class="form-control" placeholder="Detalle las actividades internas realizadas..."></textarea>
            </div>
          }

          @if (esProsegur()) {
            <div class="form-section">
              <div class="form-section-title"><i class="fas fa-box"></i> Logística y Producto</div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div><label class="form-label">Lugar de Recogida</label><select name="lr" [(ngModel)]="f.lugar_recogida" class="form-control"><option value="">— Seleccione —</option><option value="Innes">Innes</option><option value="Prosegur">Prosegur</option></select></div>
                <div><label class="form-label">Fecha de Recogida</label><input type="date" name="fr" [(ngModel)]="f.fecha_recogida" class="form-control"></div>
              </div>
              <div class="mb-4">
                <label class="form-label">Producto a Enviar</label>
                <div class="flex gap-4 mb-3">
                  <label class="flex items-center gap-2 cursor-pointer font-bold text-sm"><input type="radio" name="em" value="1" [(ngModel)]="f.es_maquina" class="w-4 h-4 text-indigo-600"> Máquina</label>
                  <label class="flex items-center gap-2 cursor-pointer font-bold text-sm"><input type="radio" name="em" value="0" [(ngModel)]="f.es_maquina" class="w-4 h-4 text-indigo-600"> Otros</label>
                </div>
                @if (f.es_maquina === '1') {
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="form-label">Tipo de Máquina</label><app-select-buscar name="tm" [opciones]="d.tiposMaquina" valor="id_tipo_maquina" texto="nombre_tipo_maquina" placeholder="— Seleccione máquina —" [(ngModel)]="f.id_tipo_maquina" /></div>
                    <div><label class="form-label">Serial Físico</label><input type="text" name="sm" [(ngModel)]="f.serial_maquina" class="form-control" placeholder="Ej: SN-12345"></div>
                  </div>
                } @else {
                  <div><label class="form-label">Descripción del Producto</label><input type="text" name="po" [(ngModel)]="f.producto_otro" class="form-control" placeholder="Describa qué transportó (Ej: Repuestos, Cables...)"></div>
                }
              </div>
            </div>
            <div class="form-section">
              <div class="form-section-title"><i class="fas fa-map-marker-alt"></i> Origen y Destino</div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h4 class="text-sm font-bold text-gray-700 mb-3 border-b pb-2">Datos de Origen</h4>
                  <div class="mb-3"><label class="form-label">Cliente Origen</label><app-combo-libre [opciones]="d.clientes" valor="id_cliente" campo="nombre_cliente" [texto]="txt.co" (cambio)="cambioCliente('origen', $event)" /></div>
                  <div><label class="form-label">Punto Origen</label><app-combo-libre [opciones]="puntosOrigen" valor="id_punto" campo="nombre_punto" [texto]="txt.po" (cambio)="f.punto_origen = $event" /></div>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h4 class="text-sm font-bold text-gray-700 mb-3 border-b pb-2">Datos de Destino</h4>
                  <div class="mb-3"><label class="form-label">Cliente Destino</label><app-combo-libre [opciones]="d.clientes" valor="id_cliente" campo="nombre_cliente" [texto]="txt.cd" (cambio)="cambioCliente('destino', $event)" /></div>
                  <div><label class="form-label">Punto Destino</label><app-combo-libre [opciones]="puntosDestino" valor="id_punto" campo="nombre_punto" [texto]="txt.pd" (cambio)="f.punto_destino = $event" /></div>
                </div>
              </div>
            </div>
          }

          <div class="form-section">
            <div class="form-section-title"><i class="fas fa-comment-alt"></i> {{ editar ? 'Observaciones Generales' : 'Observaciones' }}</div>
            <textarea name="notas" [(ngModel)]="f.notas" rows="2" class="form-control" placeholder="Notas opcionales..."></textarea>
          </div>

          @if (!editar && esProsegur() && f.es_maquina === '1') {
            <div class="form-section">
              <div class="form-section-title"><i class="fas fa-camera"></i> Evidencias Obligatorias</div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div><label class="form-label">Foto Remisión <span class="req">*</span></label><input type="file" class="form-control !p-1 cursor-pointer" accept="image/*,application/pdf" (change)="archivo('foto_remision', $event)"></div>
                <div><label class="form-label">Foto Máquina <span class="req">*</span></label><input type="file" class="form-control !p-1 cursor-pointer" accept="image/*" (change)="archivo('foto_maquina', $event)"></div>
                <div><label class="form-label">Foto Chazos <span class="req">*</span></label><input type="file" class="form-control !p-1 cursor-pointer" accept="image/*" (change)="archivo('foto_chazos', $event)"></div>
              </div>
              <p class="text-xs text-red-500 mt-2 font-bold"><i class="fas fa-exclamation-triangle"></i> Estas fotos son obligatorias cuando se transporta una máquina.</p>
            </div>
          }

          <div class="flex justify-center mt-6 mb-10">
            <button type="submit" [disabled]="guardando" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition">
              @if (guardando) { <i class="fas fa-spinner fa-spin mr-2"></i> {{ editar ? 'Actualizando...' : 'Guardando...' }} }
              @else { <i class="fas fa-save mr-2"></i> {{ editar ? 'ACTUALIZAR REGISTRO Y TARIFA' : 'GUARDAR REGISTRO' }} }
            </button>
          </div>
        </form>
      }
    </div>
  `
})
export class TransporteForm implements OnInit {
  private api = inject(Api);
  private router = inject(Router);
  private ruta = inject(ActivatedRoute);
  editar = false;
  d: any = null;
  f: any = {};
  txt = { co: '', po: '', cd: '', pd: '' };
  remisiones: any[] = [];
  puntosOrigen: any[] = [];
  puntosDestino: any[] = [];
  fotos: Record<string, File> = {};
  guardando = false;
  tiposCobro = TIPOS_COBRO;
  tiposNoCobro = TIPOS_NOCOBRO;
  tiposInees = TIPOS_INEES;

  async ngOnInit() {
    this.editar = this.ruta.snapshot.data['modo'] === 'editar';
    if (!this.editar) {
      const r: any = await this.api.get('transporteCrear');
      this.d = r;
      this.f = {
        categoria_servicio: '', id_tecnico: null, id_control_remision: null, fecha_instalacion: r.hoy,
        tipo_servicio_cobro: '', tipo_servicio_nocobro: TIPOS_NOCOBRO[0], tipo_servicio_inees: TIPOS_INEES[0],
        descripcion_inees: '', lugar_recogida: '', fecha_recogida: '', es_maquina: '1', id_tipo_maquina: null, serial_maquina: '', producto_otro: '',
        cliente_origen: '', punto_origen: '', cliente_destino: '', punto_destino: '', notas: ''
      };
      return;
    }
    const id = this.ruta.snapshot.queryParamMap.get('id') || '';
    const r: any = await this.api.get('transporteEditar', 'index', { id });
    if (r.error) {
      alert(r.error);
      this.router.navigate(['/transporteVer']);
      return;
    }
    this.d = r;
    const i = r.instalacion;
    const tsn = i.tipo_servicio_nombre;
    this.remisiones = r.remisionesTecnico || [];
    this.puntosOrigen = r.puntosOrigen || [];
    this.puntosDestino = r.puntosDestino || [];
    const nombre = (lst: any[], k: string, id: any, campo: string) => lst.find((x) => String(x[k]) === String(id))?.[campo] ?? '';
    this.f = {
      id_instalacion: i.id_instalacion, categoria_servicio: i.categoria_servicio || '', id_tecnico: i.id_tecnico, id_control_remision: i.id_control_remision,
      fecha_instalacion: String(i.fecha_instalacion ?? '').substring(0, 10),
      tipo_servicio_cobro: TIPOS_COBRO.some((t) => t[0] === tsn) ? tsn : TIPOS_COBRO[0][0],
      tipo_servicio_nocobro: TIPOS_NOCOBRO.includes(tsn) ? tsn : TIPOS_NOCOBRO[0],
      tipo_servicio_inees: TIPOS_INEES.includes(tsn) ? tsn : TIPOS_INEES[0],
      descripcion_inees: i.descripcion_inees ?? '', lugar_recogida: i.lugar_recogida ?? '', fecha_recogida: String(i.fecha_recogida ?? '').substring(0, 10),
      es_maquina: String(i.es_maquina ?? 1) === '0' ? '0' : '1', id_tipo_maquina: i.id_tipo_maquina, serial_maquina: i.serial_maquina ?? '', producto_otro: i.producto_otro ?? '',
      cliente_origen: i.id_cliente_origen ? String(i.id_cliente_origen) : i.cliente_origen_texto ?? '',
      punto_origen: i.punto_origen_texto ? i.punto_origen_texto : i.id_punto_origen ? String(i.id_punto_origen) : '',
      cliente_destino: i.id_cliente_destino ? String(i.id_cliente_destino) : i.cliente_destino_texto ?? '',
      punto_destino: i.punto_destino_texto ? i.punto_destino_texto : i.id_punto_destino ? String(i.id_punto_destino) : '',
      notas: i.notas ?? '',
      valor_servicio: this.miles(i.valor_servicio)
    };
    this.txt = {
      co: i.id_cliente_origen ? nombre(r.clientes, 'id_cliente', i.id_cliente_origen, 'nombre_cliente') : i.cliente_origen_texto ?? '',
      po: i.punto_origen_texto ? i.punto_origen_texto : nombre(this.puntosOrigen, 'id_punto', i.id_punto_origen, 'nombre_punto'),
      cd: i.id_cliente_destino ? nombre(r.clientes, 'id_cliente', i.id_cliente_destino, 'nombre_cliente') : i.cliente_destino_texto ?? '',
      pd: i.punto_destino_texto ? i.punto_destino_texto : nombre(this.puntosDestino, 'id_punto', i.id_punto_destino, 'nombre_punto')
    };
  }

  esProsegur() {
    return this.f.categoria_servicio === 'Prosegur_Cobro' || this.f.categoria_servicio === 'Prosegur_NoCobro';
  }
  private miles(v: any) {
    return Math.trunc(Number(v) || 0).toLocaleString('es-CO');
  }
  formatearValor(v: string) {
    this.f.valor_servicio = parseInt(String(v).replace(/[^\d]/g, '') || '0', 10).toLocaleString('es-CO');
  }
  async cambioTecnico() {
    this.f.id_control_remision = null;
    this.remisiones = [];
    if (!this.f.id_tecnico) return;
    this.remisiones = await this.api.postForm(this.editar ? 'transporteEditar' : 'transporteCrear', 'ajaxRemisiones', { id_tecnico: this.f.id_tecnico });
  }
  async cambioCliente(lado: 'origen' | 'destino', valor: string) {
    this.f['cliente_' + lado] = valor;
    this.f['punto_' + lado] = '';
    if (lado === 'origen') this.txt.po = ''; else this.txt.pd = '';
    let puntos: any[] = [];
    if (valor && !isNaN(Number(valor))) puntos = await this.api.postForm(this.editar ? 'transporteEditar' : 'transporteCrear', 'ajaxPuntos', { id_cliente: valor });
    if (lado === 'origen') this.puntosOrigen = puntos; else this.puntosDestino = puntos;
  }
  archivo(campo: string, ev: Event) {
    const f = (ev.target as HTMLInputElement).files?.[0];
    if (f) this.fotos[campo] = f; else delete this.fotos[campo];
  }
  async guardar() {
    if (!this.f.categoria_servicio || !this.f.id_tecnico || !this.f.fecha_instalacion) {
      alert('⚠️ Faltan datos requeridos (Categoría, Técnico o Fecha).');
      return;
    }
    if (!this.editar && this.esProsegur() && this.f.es_maquina === '1' && ['foto_remision', 'foto_maquina', 'foto_chazos'].some((k) => !this.fotos[k])) {
      alert('⚠️ Las fotos de remisión, máquina y chazos son obligatorias cuando se transporta una máquina.');
      return;
    }
    const fd = new FormData();
    Object.entries(this.f).forEach(([k, v]) => fd.append(k, v === null || v === undefined ? '' : String(v)));
    if (!this.editar) {
      const rem = this.remisiones.find((r) => String(r.id_control) === String(this.f.id_control_remision));
      fd.append('texto_remision', rem ? String(rem.numero_remision) : 'SIN_REMISION');
      Object.entries(this.fotos).forEach(([k, f]) => fd.append(k, f));
    }
    this.guardando = true;
    try {
      const r: any = await this.api.post(this.editar ? 'transporteEditar' : 'transporteCrear', this.editar ? 'actualizar' : 'guardar', fd);
      if (r.success) {
        await Swal.fire({ icon: 'success', title: r.message, timer: 2500, showConfirmButton: false });
        this.router.navigate(['/transporteVer']);
      } else {
        Swal.fire('Error', r.message, 'error');
      }
    } finally {
      this.guardando = false;
    }
  }
}

// =====================================================================================
// GESTIÓN DE TRANSPORTES (transporteVerVista.php)
// =====================================================================================
@Component({
  selector: 'app-transporte-ver',
  imports: [RouterLink, DtArriba, DtAbajo, Th],
  template: `
    <div class="w-full px-4 py-4 md:py-6 md:px-8">
      <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-truck-loading text-indigo-500 mr-2"></i> Gestión de Transportes</h1>
          <p class="text-sm text-gray-500 mt-1">Listado general de servicios logísticos, recogidas y operaciones internas.</p>
        </div>
        <a routerLink="/transporteCrear" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-200 flex items-center gap-2"><i class="fas fa-plus"></i> Nuevo Registro</a>
      </div>
      <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <app-dt-arriba [dt]="dt" />
        <table class="w-full text-sm text-left">
          <thead class="text-xs text-gray-700 uppercase bg-gray-50">
            <tr>
              <th app-th [dt]="dt" campo="id_instalacion" class="p-3 text-center w-10">ID</th>
              <th app-th [dt]="dt" campo="fecha_instalacion" class="p-3">F. Realización</th>
              <th app-th [dt]="dt" campo="categoria_servicio" class="p-3 text-center">Categoría / Servicio</th>
              <th app-th [dt]="dt" campo="nombre_tecnico" class="p-3">Técnico</th>
              <th class="p-3">Producto Transportado</th>
              <th class="p-3">Destino (Cliente / Punto)</th>
              <th class="p-3 text-center w-24">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @for (r of dt.pagina; track r.id_instalacion) {
              <tr class="border-b hover:bg-gray-50">
                <td class="p-3 text-center">{{ r.id_instalacion }}</td>
                <td class="p-3">{{ r.fecha_instalacion }}</td>
                <td class="p-3 text-center">
                  @if (r.categoria_servicio === 'Prosegur_Cobro') { <span class="px-2 py-1 bg-emerald-100 text-emerald-800 text-xs font-bold rounded-full uppercase"><i class="fas fa-dollar-sign mr-1"></i> P. Cobro</span> }
                  @else if (r.categoria_servicio === 'Prosegur_NoCobro') { <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-bold rounded-full uppercase"><i class="fas fa-handshake mr-1"></i> P. Sin Cobro</span> }
                  @else { <span class="px-2 py-1 bg-amber-100 text-amber-800 text-xs font-bold rounded-full uppercase"><i class="fas fa-building mr-1"></i> Inees</span> }
                  <br><small class="text-gray-500">{{ r.tipo_servicio_nombre || 'N/A' }}</small>
                </td>
                <td class="p-3">{{ r.nombre_tecnico }}</td>
                <td class="p-3">
                  @if (+r.es_maquina === 1) { <strong><i class="fas fa-hdd text-gray-400"></i> {{ r.serial_maquina || 'Sin serial' }}</strong><br><small class="text-gray-500">{{ r.nombre_tipo_maquina || 'Tipo no definido' }}</small> }
                  @else { <strong><i class="fas fa-box-open text-gray-400"></i> Otros:</strong><br><small class="text-gray-500">{{ r.producto_otro || 'No especificado' }}</small> }
                </td>
                <td class="p-3"><strong>{{ r.nombre_cliente || r.cliente_destino_texto || 'Sin cliente' }}</strong><br><small class="text-gray-500">{{ r.nombre_punto || r.punto_destino_texto || 'Sin punto' }}</small></td>
                <td class="p-3">
                  <div class="flex justify-center text-lg">
                    <button (click)="verDetalle(r.id_instalacion)" class="text-blue-500 hover:text-blue-700 mx-1" title="Ver Detalles"><i class="fas fa-eye"></i></button>
                    <a routerLink="/transporteEditar" [queryParams]="{ id: r.id_instalacion }" class="text-amber-500 hover:text-amber-700 mx-1" title="Editar"><i class="fas fa-edit"></i></a>
                    <button (click)="eliminar(r.id_instalacion)" class="text-red-500 hover:text-red-700 mx-1" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                  </div>
                </td>
              </tr>
            }
          </tbody>
        </table>
        <app-dt-abajo [dt]="dt" />
      </div>
    </div>
  `
})
export class TransporteVer implements OnInit {
  private api = inject(Api);
  dt = new TablaDatos<any>([], 10);

  ngOnInit() {
    this.dt.campoOrden = 'id_instalacion';
    this.dt.ordenAsc = false;
    this.cargar();
  }
  async cargar() {
    const r: any = await this.api.get('transporteVer', 'ajaxListar');
    this.dt.set(r.data || []);
  }
  verDetalle(id: any) {
    window.open(this.api.url('transportePdf', undefined, { id }), '_blank');
  }
  async eliminar(id: any) {
    const c = await Swal.fire({
      title: '¿Estás seguro?', text: 'Esta acción anulará el registro #' + id + ' y no se mostrará en esta lista.', icon: 'warning',
      showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6b7280', confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
    });
    if (!c.isConfirmed) return;
    try {
      const r: any = await this.api.postForm('transporteVer', 'eliminar', { id });
      if (r.status === 'success') {
        Swal.fire({ title: '¡Eliminado!', text: r.message, icon: 'success', confirmButtonColor: '#10b981' });
        this.cargar();
      } else Swal.fire('Error', r.message, 'error');
    } catch {
      Swal.fire('Error', 'Hubo un problema de conexión con el servidor.', 'error');
    }
  }
}
