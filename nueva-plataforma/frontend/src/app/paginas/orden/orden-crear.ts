import { Component, OnDestroy, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Api } from '../../core/api';
import { SelectBuscar } from '../../shared/select-buscar';
import { Horas, Notif, mostrarNotificacion } from '../../shared/notificaciones';

const CLAVE_GUARDADO = 'borrador_orden_servicios';

interface RepItem {
  id: string;
  nombre: string;
  origen: 'INEES' | 'PROSEGUR';
  cantidad: number;
}

interface Fila {
  id: number;
  id_orden_previa?: any;
  id_tecnico: any;
  remision: any;
  remisiones: any[];
  /** wait | cargando | ok | vacio | sinTec */
  remEstado: string;
  remError: boolean;
  id_cliente: any;
  puntos: any[];
  puntosEstado: string;
  id_punto: any;
  maquinas: any[];
  maqEstado: string;
  id_maquina: any;
  device_id: string;
  id_modalidad: string;
  modalidadHabilitada: boolean;
  modalidadFestivo: boolean;
  tipo_servicio: any;
  hora_in: string;
  hora_out: string;
  errIn: boolean;
  errOut: boolean;
  duracion: string;
  valor: any;
  valorPlaceholder: string;
  sinTarifa: boolean;
  pulso: boolean;
  jsonRep: string;
  estado: any;
  calif: any;
  obs: string;
  /** '', 'rojo', 'amarillo', 'programada' */
  resaltado: string;
  errCampos: Record<string, boolean>;
}

/** Toast simple de uiUtils.js (mostrarNotificacion). */
function toastSimple(mensaje: string, tipo: 'info' | 'success' | 'warning' | 'error' = 'info') {
  const colores = { info: 'bg-blue-500', success: 'bg-green-500', warning: 'bg-yellow-500', error: 'bg-red-500' };
  const n = document.createElement('div');
  n.className = `fixed top-4 right-4 ${colores[tipo]} text-white px-6 py-3 rounded-lg shadow-xl z-50 transform transition-all duration-300`;
  n.textContent = mensaje;
  document.body.appendChild(n);
  setTimeout(() => {
    n.style.opacity = '0';
    n.style.transform = 'translateY(-20px)';
    setTimeout(() => n.remove(), 300);
  }, 3000);
}

function hoy(): string {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

/** Port de ordenCrearVista.php + js/orden/ordenCrear/*.js */
@Component({
  selector: 'app-orden-crear',
  imports: [FormsModule, SelectBuscar],
  styles: [
    `
      :host ::ng-deep .oc-tabla td {
        vertical-align: top;
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
      }
    `
  ],
  template: `
    <div class="w-full bg-white shadow-xl rounded-lg p-2 md:p-6">
      <!-- HEADER CON FECHA -->
      <div class="bg-gradient-to-r from-gray-800 to-gray-700 p-4 rounded-lg mb-6 flex flex-wrap gap-4 items-center text-white shadow-md">
        <div>
          <label class="block text-xs font-bold text-gray-300 uppercase mb-1">Fecha del Reporte</label>
          <input type="date" name="fecha_reporte" [(ngModel)]="fechaReporte" (change)="cambioFecha()" class="text-gray-900 border-none p-2 rounded w-40 font-bold focus:ring-2 focus:ring-blue-500" />
        </div>
      </div>

      <!-- BOTÓN FLOTANTE AGREGAR -->
      <div class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-2">
        <div class="bg-blue-600 text-white text-xs font-bold py-1 px-3 rounded-full shadow-lg">
          <span>{{ contadorDisplay }}</span> Servicios agregados
        </div>
        <button type="button" (click)="agregarFila()" class="bg-green-500 hover:bg-green-600 text-white font-bold py-4 px-6 rounded-full shadow-2xl transition transform hover:scale-110 flex items-center gap-2">
          <i class="fas fa-plus text-xl"></i>
          <span class="font-bold">Agregar Servicio</span>
        </button>
      </div>

      <!-- TABLA DE SERVICIOS -->
      <div class="overflow-x-auto shadow-inner rounded-lg border border-gray-200" style="max-height: 60vh; overflow-y: auto">
        <table class="min-w-max text-xs w-full oc-tabla">
          <thead class="sticky top-0 z-20 bg-gray-100">
            <tr class="text-gray-600 uppercase tracking-wider border-b-2 border-gray-300 h-10">
              <th class="px-2 sticky left-0 bg-gray-100 z-10 w-8">#</th>
              <th class="px-2 w-40">Técnico</th>
              <th class="px-2 w-32">Remisión</th>
              <th class="px-2 w-48">Ubicación (Cliente/Punto)</th>
              <th class="px-2 w-40">Máquina / Device ID</th>
              <th class="px-2 w-40">Modalidad Operativa</th>
              <th class="px-2 w-40">Resultado Mantenimiento</th>
              <th class="px-2 w-32">Tiempos Servicio</th>
              <th class="px-2 w-24 text-center">Duración</th>
              <th class="px-2 w-32">Valor Servicio</th>
              <th class="px-2 w-32">Repuestos</th>
              <th class="px-2 w-32">Estado Final</th>
              <th class="px-2 w-32">Calificación</th>
              <th class="px-2 w-48">Información Servicio</th>
              <th class="px-2 w-10"></th>
            </tr>
          </thead>
          <tbody id="contenedorFilas" class="divide-y divide-gray-100 bg-white" (keydown)="navegarTabla($event)" (click)="seleccionarTexto($event)">
            @for (f of filas; track f.id) {
              <tr [id]="'fila_' + f.id" [attr.data-id]="f.id" class="hover:bg-blue-50 transition" [class.bg-red-200]="f.resaltado === 'rojo'" [class.bg-yellow-200]="f.resaltado === 'amarillo'" [class.bg-blue-50]="f.resaltado === 'programada'">
                <td class="px-2 py-3 text-center font-bold text-gray-400 sticky left-0 bg-white">{{ f.id }}</td>
                <td class="px-2 min-w-[160px]">
                  <app-select-buscar [opciones]="tecnicos" valor="id_tecnico" texto="nombre_tecnico" placeholder="- Técnico -" [compacto]="true" claseBoton="font-semibold" [ngModel]="f.id_tecnico" (cambio)="f.id_tecnico = $event; cargarRemisiones(f, $event)" />
                </td>
                <td class="px-2 min-w-[128px]">
                  <app-select-buscar
                    [opciones]="f.remisiones"
                    valor="numero_remision"
                    texto="numero_remision"
                    [placeholder]="placeholderRemision(f)"
                    [compacto]="true"
                    [claseBoton]="'text-center font-bold ' + (f.remEstado === 'ok' ? 'bg-white' : 'bg-gray-100') + (f.remError || f.errCampos['remision'] ? ' border-red-500 bg-red-50' : '')"
                    [disabled]="f.remEstado !== 'ok'"
                    [ngModel]="f.remision"
                    (cambio)="f.remision = $event; validarRemisionCompleta(f)"
                  />
                </td>
                <td class="px-2 space-y-1 min-w-[200px]">
                  <app-select-buscar [opciones]="clientes" valor="id_cliente" texto="nombre_cliente" placeholder="- Cliente -" [compacto]="true" [ngModel]="f.id_cliente" (cambio)="f.id_cliente = $event; cargarPuntos(f, $event)" />
                  <app-select-buscar [opciones]="f.puntos" valor="id_punto" [texto]="textoPunto" [placeholder]="f.puntosEstado" [compacto]="true" [disabled]="!f.puntos.length" [ngModel]="f.id_punto" (cambio)="f.id_punto = $event; cargarMaquinas(f, $event)" />
                </td>
                <td class="px-2 space-y-1 min-w-[170px]">
                  <app-select-buscar [opciones]="f.maquinas" valor="id_maquina" [texto]="textoMaquina" [placeholder]="f.maqEstado" [compacto]="true" claseBoton="bg-yellow-50" [permitirVacio]="false" [limpiable]="false" [disabled]="!f.maquinas.length" [ngModel]="f.id_maquina" (cambio)="f.id_maquina = $event; rellenarDeviceId(f)" />
                  <input type="text" [value]="f.device_id" readonly class="w-full border p-1 text-xs text-center bg-gray-100 font-mono" placeholder="Device ID" />
                </td>
                <td class="p-1">
                  <select class="w-full border rounded p-1 text-xs font-bold" [class.bg-gray-50]="!f.modalidadFestivo" [class.bg-yellow-100]="f.modalidadFestivo" [class.border-yellow-500]="f.modalidadFestivo" [class.text-yellow-800]="f.modalidadFestivo" [disabled]="!f.modalidadHabilitada" [(ngModel)]="f.id_modalidad" (change)="calcularPrecio(f)">
                    <option value="1">URBANO</option>
                    <option value="2">INTERURBANO</option>
                  </select>
                </td>
                <td class="px-2 min-w-[160px]">
                  <app-select-buscar [opciones]="mantos" valor="id_tipo_mantenimiento" texto="nombre_completo" placeholder="- Resultado Mantenimiento -" [compacto]="true" claseBoton="font-semibold" [ngModel]="f.tipo_servicio" (cambio)="f.tipo_servicio = $event; calcularPrecio(f)" />
                </td>
                <td class="px-2 space-y-1">
                  <div class="flex items-center justify-between gap-1">
                    <span class="text-gray-400 text-[10px]">Entrada:</span>
                    <input type="text" placeholder="HH:MM" maxlength="5" class="border rounded p-0.5 w-20 text-center font-bold" [class.border-red-500]="f.errIn || f.errCampos['hora_in']" [class.bg-red-50]="f.errIn || f.errCampos['hora_in']" [value]="f.hora_in" (input)="mascaraHora($event, f, 'hora_in')" (paste)="pegarHora($event, f, 'hora_in')" (blur)="validarHora(f, 'hora_in')" />
                  </div>
                  <div class="flex items-center justify-between gap-1">
                    <span class="text-gray-400 text-[10px]">Salida:</span>
                    <input type="text" placeholder="HH:MM" maxlength="5" class="border rounded p-0.5 w-20 text-center font-bold" [class.border-red-500]="f.errOut || f.errCampos['hora_out']" [class.bg-red-50]="f.errOut || f.errCampos['hora_out']" [value]="f.hora_out" (input)="mascaraHora($event, f, 'hora_out')" (paste)="pegarHora($event, f, 'hora_out')" (blur)="validarHora(f, 'hora_out')" />
                  </div>
                </td>
                <td class="px-2 text-center">
                  <input type="text" readonly [value]="f.duracion" class="w-full text-center bg-transparent font-mono text-xs font-bold" [class.text-green-600]="!!f.duracion" placeholder="00:00" />
                </td>
                <td class="px-2">
                  <div class="relative">
                    <span class="absolute left-2 top-1 text-gray-400">$</span>
                    <input
                      type="text"
                      class="w-full border rounded pl-4 pr-1 py-1 text-right font-mono font-bold"
                      [class.bg-green-50]="!f.sinTarifa"
                      [class.bg-red-200]="f.sinTarifa"
                      [class.border-red-500]="f.sinTarifa"
                      [class.text-red-700]="f.sinTarifa"
                      [class.placeholder-red-700]="f.sinTarifa"
                      [class.animate-pulse]="f.pulso"
                      [placeholder]="f.valorPlaceholder"
                      [(ngModel)]="f.valor"
                    />
                  </div>
                </td>
                <td class="px-2 text-center">
                  <button type="button" (click)="abrirModal(f)" class="text-xs px-2 py-1 rounded w-full" [class]="totalItems(f) > 0 ? 'bg-blue-600 text-white border-blue-700' : 'bg-gray-200 hover:bg-gray-300 text-gray-700'">
                    <i class="fas fa-tools"></i> <span>{{ totalItems(f) }} Items</span>
                  </button>
                </td>
                <td class="px-2">
                  <select class="w-full border rounded p-1 text-[10px]" [(ngModel)]="f.estado">
                    @for (e of estados; track e.id_estado) {
                      <option [value]="e.id_estado">{{ e.nombre_estado }}</option>
                    }
                  </select>
                </td>
                <td class="px-2">
                  <select class="w-full border rounded p-1 text-[10px]" [(ngModel)]="f.calif">
                    @for (c of califs; track c.id_calificacion) {
                      <option [value]="c.id_calificacion">{{ c.nombre_calificacion }}</option>
                    }
                  </select>
                </td>
                <td class="px-2 min-w-[190px]">
                  <textarea rows="2" class="w-full border rounded p-1 text-xs resize-y" [class.border-red-500]="f.errCampos['obs']" [class.bg-red-50]="f.errCampos['obs']" style="min-height: 38px" placeholder="Describa actividades..." [(ngModel)]="f.obs"></textarea>
                </td>
                <td class="px-2 text-center">
                  <button type="button" (click)="eliminarFila(f)" class="text-red-400 hover:text-red-600"><i class="fas fa-trash"></i></button>
                </td>
              </tr>
            }
          </tbody>
        </table>
      </div>

      <!-- BOTÓN GUARDAR -->
      <div class="mt-8 text-center pb-8">
        <button type="button" [disabled]="guardando || recuperando" (click)="procesarGuardado()" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-4 px-12 rounded-xl shadow-xl transform hover:scale-105 transition text-lg disabled:opacity-60">
          @if (recuperando) {
            <i class="fas fa-spinner fa-spin"></i> RECUPERANDO DATOS...
          } @else {
            <i class="fas fa-save mr-2"></i> GUARDAR REPORTE COMPLETO
          }
        </button>
      </div>
    </div>

    <!-- MODAL DE REPUESTOS -->
    @if (modalFila) {
      <div class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg p-6 transform scale-100 transition-transform">
          <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2 flex justify-between">
            <span>🛠️ Gestión de Repuestos</span>
            <button type="button" (click)="cerrarModal()" class="text-gray-400 hover:text-red-500"><i class="fas fa-times"></i></button>
          </h3>
          <div class="flex gap-2 items-center">
            <div class="flex-1 min-w-0">
              <app-select-buscar [opciones]="opcionesModal" valor="id" texto="texto" [placeholder]="cargandoModal ? 'Cargando catálogo completo...' : '- Buscar Repuesto -'" [disabled]="cargandoModal" [(ngModel)]="modalRepuesto" />
            </div>
            <div class="w-20 flex-shrink-0">
              <input type="number" min="1" [(ngModel)]="modalCantidad" class="w-full border rounded p-2 text-sm text-center font-bold bg-gray-50 h-[38px]" placeholder="Cant." />
            </div>
            <div class="w-1/3 flex-shrink-0" style="max-width: 130px">
              <select [(ngModel)]="modalOrigen" class="w-full border rounded p-2 text-xs bg-gray-100 font-bold text-gray-700 h-[38px]">
                <option value="INEES">INEES</option>
                <option value="PROSEGUR">PROSEGUR</option>
              </select>
            </div>
            <button type="button" (click)="agregarRepuestoALista()" class="bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700 shadow transition h-[38px] flex-shrink-0"><i class="fas fa-plus"></i></button>
          </div>
          <ul class="border rounded p-2 h-48 overflow-y-auto bg-gray-50 text-sm mt-2">
            @for (item of listaModal(); track $index; let i = $index) {
              <li class="flex justify-between items-center bg-gray-50 p-2 mb-2 border rounded shadow-sm hover:bg-gray-100 transition">
                <div class="flex items-center gap-2 overflow-hidden w-full">
                  <span class="text-[10px] font-bold px-2 py-0.5 rounded border border-opacity-20 flex-shrink-0" [class]="item.origen === 'INEES' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'" style="min-width: 60px; text-align: center">{{ item.origen }}</span>
                  <span class="text-xs text-gray-700 font-medium truncate flex-grow">{{ item.nombre }}</span>
                  <span class="bg-gray-800 text-white text-[11px] px-2 py-0.5 rounded-full font-bold flex-shrink-0">x{{ item.cantidad || 1 }}</span>
                </div>
                <button type="button" (click)="borrarRepuesto(i)" class="text-red-400 hover:text-red-600 px-2 ml-2 transition transform hover:scale-110"><i class="fas fa-trash-alt"></i></button>
              </li>
            } @empty {
              <li class="text-gray-400 text-center italic mt-10">No hay repuestos seleccionados.</li>
            }
          </ul>
          <div class="mt-6 text-right border-t pt-4">
            <button type="button" (click)="guardarCambiosModal()" class="bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700 shadow-lg transform hover:scale-105 transition"><i class="fas fa-check mr-2"></i> Confirmar Cambios</button>
          </div>
        </div>
      </div>
    }

    @if (guardando) {
      <div class="fixed inset-0 z-[999999] bg-gray-900 bg-opacity-90 flex flex-col items-center justify-center">
        <div class="animate-spin rounded-full h-32 w-32 border-t-4 border-b-4 border-blue-500 mb-6"></div>
        <h2 class="text-3xl font-bold text-white text-center">Guardando Servicios...</h2>
        <p class="text-gray-300 mt-4 text-lg text-center max-w-md">Por favor, no cierres ni recargues esta ventana hasta que el proceso haya finalizado. Esto puede tomar unos segundos.</p>
      </div>
    }
  `
})
export class OrdenCrear implements OnInit, OnDestroy {
  private api = inject(Api);

  clientes: any[] = [];
  mantos: any[] = [];
  tecnicos: any[] = [];
  estados: any[] = [];
  califs: any[] = [];
  listaRepuestos: any[] = [];
  festivos: string[] = [];

  fechaReporte = hoy();
  filas: Fila[] = [];
  contadorFilas = 0;
  contadorDisplay = 0;
  /** almacenRepuestos: { idFila: RepItem[] } */
  almacen: Record<string, RepItem[]> = {};
  ignorarCambios = false;
  enviandoFormulario = false;
  guardando = false;
  recuperando = false;

  // Modal repuestos
  modalFila: Fila | null = null;
  cargandoModal = false;
  opcionesModal: any[] = [];
  stockModal: Record<string, number> = {};
  modalRepuesto: any = '';
  modalCantidad: any = 1;
  modalOrigen: 'INEES' | 'PROSEGUR' = 'INEES';

  private intervalo: any;
  private atajos = (e: KeyboardEvent) => this.atajosTeclado(e);

  textoPunto = (p: any) => `${p.nombre_punto} - (${p.codigo_1 || 'S/C'})`;
  textoMaquina = (m: any) => `${m.nombre_tipo_maquina} (${m.device_id})`;

  async ngOnInit() {
    const d = await this.api.get('ordenCrear');
    this.clientes = d.clientes || [];
    this.mantos = d.tiposManto || [];
    this.tecnicos = d.tecnicos || [];
    this.estados = d.estados || [];
    this.califs = d.califs || [];
    this.listaRepuestos = d.listaRepuestos || [];
    this.festivos = (d.listaFestivos || []).map((x: any) => String(x).substring(0, 10));

    document.addEventListener('keydown', this.atajos);
    this.intervalo = setInterval(() => this.guardarProgresoLocal(), 4000);
    this.cargarProgramacion(this.fechaReporte);
    setTimeout(() => this.verificarYRestaurar(), 500);
  }

  ngOnDestroy() {
    clearInterval(this.intervalo);
    document.removeEventListener('keydown', this.atajos);
  }

  // ================================================================ AJAX
  private async enviarAjax(accion: string, datos: Record<string, any>): Promise<any> {
    try {
      return await this.api.post('ordenCrear', accion, datos);
    } catch (e) {
      console.error('Error en AJAX:', e);
      return [];
    }
  }

  // ================================================================ FILAS
  private nuevaFila(): Fila {
    this.contadorFilas++;
    return {
      id: this.contadorFilas,
      id_tecnico: '',
      remision: '',
      remisiones: [],
      remEstado: 'wait',
      remError: false,
      id_cliente: '',
      puntos: [],
      puntosEstado: '- Primero seleccione cliente -',
      id_punto: '',
      maquinas: [],
      maqEstado: '- Primero seleccione punto -',
      id_maquina: '',
      device_id: '',
      id_modalidad: '1',
      modalidadHabilitada: false,
      modalidadFestivo: false,
      tipo_servicio: '',
      hora_in: '',
      hora_out: '',
      errIn: false,
      errOut: false,
      duracion: '',
      valor: '0',
      valorPlaceholder: '0',
      sinTarifa: false,
      pulso: false,
      jsonRep: '[]',
      estado: this.estados[0]?.id_estado != null ? String(this.estados[0].id_estado) : '',
      calif: this.califs[0]?.id_calificacion != null ? String(this.califs[0].id_calificacion) : '',
      obs: '',
      resaltado: '',
      errCampos: {}
    };
  }

  agregarFila(): Fila {
    const f = this.nuevaFila();
    this.filas.push(f);
    this.contadorDisplay = this.contadorFilas;
    setTimeout(() => document.getElementById(`fila_${f.id}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' }), 100);
    return f;
  }

  eliminarFila(f: Fila) {
    this.filas = this.filas.filter((x) => x !== f);
    delete this.almacen[f.id];
    this.contadorDisplay = this.filas.length;
  }

  placeholderRemision(f: Fila): string {
    switch (f.remEstado) {
      case 'wait':
        return 'Wait...';
      case 'cargando':
        return 'Cargando...';
      case 'sinTec':
        return '- Seleccione Técnico -';
      case 'vacio':
        return '🚫 Sin remisiones';
      default:
        return 'Escriba remisión...';
    }
  }

  async cargarRemisiones(f: Fila, idTecnico: any) {
    f.remisiones = [];
    f.remision = '';
    f.remEstado = 'cargando';
    if (!idTecnico) {
      f.remEstado = 'sinTec';
      return;
    }
    const remisiones = await this.enviarAjax('ajaxRemisiones', { id_tecnico: idTecnico });
    if (Array.isArray(remisiones) && remisiones.length > 0) {
      f.remisiones = remisiones;
      f.remEstado = 'ok';
    } else {
      f.remEstado = 'vacio';
      alert('⚠️ Este técnico no tiene remisiones disponibles.');
    }
  }

  private limpiarFilaDesde(f: Fila, desde: 'punto' | 'maquina') {
    f.maquinas = [];
    f.id_maquina = '';
    f.maqEstado = '- Primero seleccione punto -';
    f.device_id = '';
    if (desde === 'punto') {
      f.puntos = [];
      f.id_punto = '';
      f.puntosEstado = '- Primero seleccione cliente -';
    }
  }

  async cargarPuntos(f: Fila, idCliente: any) {
    this.limpiarFilaDesde(f, 'punto');
    if (!idCliente) return;
    f.puntosEstado = 'Cargando...';
    const puntos = await this.enviarAjax('ajaxPuntos', { id_cliente: idCliente });
    f.puntos = Array.isArray(puntos) ? puntos : [];
    f.puntosEstado = '- Seleccione Punto -';
  }

  async cargarMaquinas(f: Fila, idPunto: any) {
    this.limpiarFilaDesde(f, 'maquina');
    if (!idPunto) return;
    const punto = f.puntos.find((p) => String(p.id_punto) === String(idPunto));
    f.id_modalidad = String(punto?.id_modalidad ?? 1);
    f.modalidadHabilitada = true;
    f.maqEstado = 'Cargando...';
    const maquinas = await this.enviarAjax('ajaxMaquinas', { id_punto: idPunto });
    f.maquinas = Array.isArray(maquinas) ? maquinas : [];
    if (f.maquinas.length === 0) {
      f.maqEstado = '- No hay máquinas -';
      return;
    }
    f.id_maquina = f.maquinas[0].id_maquina;
    this.rellenarDeviceId(f);
  }

  rellenarDeviceId(f: Fila) {
    const m = f.maquinas.find((x) => String(x.id_maquina) === String(f.id_maquina));
    f.device_id = m?.device_id || '';
    this.calcularPrecio(f);
  }

  async calcularPrecio(f: Fila) {
    if (!this.filas.includes(f)) return;
    const m = f.maquinas.find((x) => String(x.id_maquina) === String(f.id_maquina));
    const tipoMaq = m?.id_tipo_maquina;
    const idManto = f.tipo_servicio;
    f.sinTarifa = false;
    f.pulso = false;
    f.valorPlaceholder = 'Valor';
    if (f.id_modalidad && tipoMaq && idManto) {
      f.valor = '...';
      const res = await this.enviarAjax('ajaxCalcularPrecio', {
        id_maquina_tipo: tipoMaq,
        id_manto: idManto,
        id_modalidad: f.id_modalidad,
        fecha_visita: this.fechaReporte || ''
      });
      if (res && res.precio !== undefined) {
        if (parseInt(res.precio) === -1) {
          f.valor = '';
          f.valorPlaceholder = '🚫 SIN TARIFA';
          f.sinTarifa = true;
        } else {
          f.valor = new Intl.NumberFormat('es-CO').format(Number(res.precio));
          if (Number(res.precio) > 0) Notif.precioCalculado(f.id, Number(res.precio));
        }
      } else {
        f.valor = 0;
      }
    }
  }

  // ================================================================ HORAS (timeManager.js)
  mascaraHora(e: Event, f: Fila, campo: 'hora_in' | 'hora_out') {
    const el = e.target as HTMLInputElement;
    const v = Horas.mascara(el.value);
    f[campo] = v;
    el.value = v;
  }

  pegarHora(e: ClipboardEvent, f: Fila, campo: 'hora_in' | 'hora_out') {
    e.preventDefault();
    e.stopPropagation();
    const texto = e.clipboardData?.getData('text') || '';
    let valorFinal = '';
    const c = texto.match(/(\d{1,2})[^\d]*(\d{2})/);
    if (c) {
      valorFinal = c[1].padStart(2, '0') + ':' + c[2].padStart(2, '0');
    } else {
      let nums = texto.replace(/\D/g, '').substring(0, 4);
      if (nums.length === 3) nums = '0' + nums;
      valorFinal = nums.length === 4 ? nums.substring(0, 2) + ':' + nums.substring(2, 4) : nums;
    }
    f[campo] = valorFinal;
    (e.target as HTMLInputElement).value = valorFinal;
    if (campo === 'hora_in') f.errIn = false;
    else f.errOut = false;
    this.validarHora(f, campo);
  }

  validarHora(f: Fila, campo: 'hora_in' | 'hora_out') {
    const valor = f[campo];
    const setErr = (v: boolean) => (campo === 'hora_in' ? (f.errIn = v) : (f.errOut = v));
    if (valor === '') {
      setErr(false);
      this.calcTiempo(f);
      return;
    }
    const p = valor.split(':');
    const h = parseInt(p[0], 10);
    const m = parseInt(p[1], 10);
    let ok = true;
    if (valor.length !== 5 || isNaN(h) || isNaN(m)) ok = false;
    if (h < 0 || h > 23) ok = false;
    if (m < 0 || m > 59) ok = false;
    if (!ok) {
      toastSimple('Hora inválida', 'error');
      f[campo] = '';
      setErr(true);
    } else {
      setErr(false);
    }
    this.calcTiempo(f);
  }

  calcTiempo(f: Fila) {
    if (f.hora_in && f.hora_out) {
      const a = Horas.aMinutos(f.hora_in);
      const b = Horas.aMinutos(f.hora_out);
      if (a === null || b === null || isNaN(a) || isNaN(b)) {
        f.duracion = '';
        return;
      }
      let diff = b - a;
      if (diff < 0) diff += 24 * 60;
      f.duracion = `${String(Math.floor(diff / 60)).padStart(2, '0')}:${String(diff % 60).padStart(2, '0')}`;
    } else {
      f.duracion = '';
    }
  }

  cambioFecha() {
    const esFestivo = Horas.esDiaEspecial(this.fechaReporte, this.festivos);
    if (esFestivo) toastSimple('Fecha Domingo/Festivo. Cambiando a INTERURBANO.', 'warning');
    for (const f of this.filas) {
      if (esFestivo) {
        if (f.id_modalidad !== '2') f.id_modalidad = '2';
        f.modalidadFestivo = true;
      } else {
        if (f.id_modalidad === '2') f.id_modalidad = '1';
        f.modalidadFestivo = false;
      }
      this.calcularPrecio(f);
    }
    this.cargarProgramacion(this.fechaReporte);
  }

  /** ajaxProgramacion: precarga servicios programados para la fecha. */
  private async cargarProgramacion(fecha: string) {
    if (!fecha) return;
    try {
      const r: any = await this.api.postForm('ordenCrear', 'ajaxProgramacion', { fecha });
      if (r?.status && Array.isArray(r.data) && r.data.length > 0) {
        this.filas = [];
        for (const s of r.data) {
          const f = this.agregarFila();
          f.id_orden_previa = s.id_ordenes_servicio;
          f.id_tecnico = s.id_tecnico;
          this.cargarRemisiones(f, s.id_tecnico);
          f.id_cliente = s.id_cliente;
          await this.cargarPuntos(f, s.id_cliente);
          f.id_punto = s.id_punto;
          await this.cargarMaquinas(f, s.id_punto);
          f.id_maquina = s.id_maquina;
          f.tipo_servicio = s.id_tipo_mantenimiento;
          this.rellenarDeviceId(f);
          f.resaltado = 'programada';
        }
      }
    } catch (e) {
      console.error('Error cargando programación', e);
    }
  }

  // ================================================================ REMISIONES (validadorRemisiones.js)
  private async validarRemisionEnBD(numero: string, idTecnico: any): Promise<boolean> {
    if (!numero) return true;
    try {
      const data: any = await this.api.post('ordenCrear', 'ajaxValidarRemision', { numero_remision: numero, id_tecnico: idTecnico });
      return data.disponible === true;
    } catch (e) {
      console.error('Error validando remisión:', e);
      return true;
    }
  }

  private marcarRemError(f: Fila) {
    f.remError = true;
    setTimeout(() => (f.remError = false), 3000);
  }

  async validarRemisionCompleta(f: Fila) {
    const numero = f.remision;
    if (!numero || !f.id_tecnico) return;
    if (this.filas.some((x) => x !== f && x.remision && String(x.remision) === String(numero))) {
      Notif.remisionDuplicada(numero);
      this.marcarRemError(f);
      return;
    }
    const valida = await this.validarRemisionEnBD(numero, f.id_tecnico);
    if (!valida) {
      mostrarNotificacion(`⚠️ Remisión YA USADA: ${numero}<br>📋 Esta remisión fue registrada anteriormente en la BD`, 'error', 5000);
      this.marcarRemError(f);
      return;
    }
    Notif.remisionValida(numero);
  }

  private async validarTodasRemisiones(): Promise<boolean> {
    const encontradas = new Map<string, Fila>();
    const errores: string[] = [];
    for (const f of this.filas) {
      if (!f.remision) continue;
      const n = String(f.remision);
      if (encontradas.has(n)) errores.push(`• Fila #${f.id}: Remisión ${n} duplicada (ya está en Fila #${encontradas.get(n)!.id})`);
      else encontradas.set(n, f);
    }
    for (const [n, f] of encontradas) {
      if (f.id_tecnico && !(await this.validarRemisionEnBD(n, f.id_tecnico))) errores.push(`• Fila #${f.id}: Remisión ${n} ya fue usada anteriormente`);
    }
    if (errores.length > 0) {
      alert('⚠️ ERRORES EN REMISIONES:\n\n' + errores.join('\n') + '\n\n❌ Corrija estos errores antes de guardar.');
      Notif.error(`${errores.length} remisión(es) con problemas`);
      return false;
    }
    return true;
  }

  // ================================================================ REPUESTOS (repuestosManager.js)
  totalItems(f: Fila): number {
    return (this.almacen[f.id] || []).reduce((s, i) => s + (parseInt(String(i.cantidad || 1)) || 1), 0);
  }

  listaModal(): RepItem[] {
    return this.modalFila ? this.almacen[this.modalFila.id] || [] : [];
  }

  async abrirModal(f: Fila) {
    if (!f.id_tecnico) {
      alert('⚠️ Seleccione primero un TÉCNICO para verificar su inventario.');
      return;
    }
    if (!this.almacen[f.id]) {
      try {
        this.almacen[f.id] = f.jsonRep && f.jsonRep !== '[]' ? JSON.parse(f.jsonRep) : [];
      } catch {
        this.almacen[f.id] = [];
      }
    }
    this.modalFila = f;
    this.modalRepuesto = '';
    this.modalCantidad = 1;
    this.modalOrigen = 'INEES';
    this.cargandoModal = true;
    this.opcionesModal = [];
    try {
      const inv = await this.enviarAjax('ajaxInventarioTecnico', { id_tecnico: f.id_tecnico });
      this.stockModal = {};
      (Array.isArray(inv) ? inv : []).forEach((i: any) => (this.stockModal[String(i.id_repuesto)] = parseInt(i.cantidad_actual)));
      this.opcionesModal = this.listaRepuestos.map((g) => {
        const stock = this.stockModal[String(g.id_repuesto)] || 0;
        return {
          id: String(g.id_repuesto),
          texto: stock > 0 ? `✅ ${g.nombre_repuesto} (Stock: ${stock})` : `📦 ${g.nombre_repuesto} (Sin Stock)`,
          stock,
          nombre: g.nombre_repuesto
        };
      });
    } catch (e) {
      console.error('Error al cruzar inventarios:', e);
    } finally {
      this.cargandoModal = false;
    }
  }

  cerrarModal() {
    this.modalFila = null;
  }

  agregarRepuestoALista() {
    const f = this.modalFila;
    if (!f) return;
    const op = this.opcionesModal.find((o) => o.id === String(this.modalRepuesto));
    if (!this.modalRepuesto || !op) {
      alert('⚠️ Por favor seleccione un repuesto.');
      return;
    }
    const origen = this.modalOrigen;
    const cant = parseInt(this.modalCantidad) || 1;
    if (cant <= 0) {
      alert('La cantidad debe ser mayor a 0.');
      return;
    }
    const lista = (this.almacen[f.id] ||= []);
    if (origen === 'INEES') {
      const ex = lista.find((r) => r.id == op.id && r.origen === 'INEES');
      const enUso = ex ? ex.cantidad : 0;
      const total = enUso + cant;
      if (total > op.stock) {
        alert(
          `🛑 ERROR DE INVENTARIO (INEES)\n\n` +
            `El técnico solo tiene: ${op.stock} unidades.\n` +
            `Ya agregó: ${enUso}\n` +
            `Intenta agregar: ${cant}\n\n` +
            `Total requerido: ${total} > Disponible: ${op.stock}\n\n` +
            `👉 SOLUCIÓN: Si el repuesto lo suministró el cliente, cambie el Origen a "PROSEGUR".`
        );
        return;
      }
    }
    const i = lista.findIndex((r) => r.id === op.id && r.origen === origen);
    if (i !== -1) lista[i].cantidad += cant;
    else lista.push({ id: op.id, nombre: op.nombre, origen, cantidad: cant });
    this.modalCantidad = 1;
    this.modalRepuesto = '';
  }

  borrarRepuesto(index: number) {
    if (this.modalFila && this.almacen[this.modalFila.id]) this.almacen[this.modalFila.id].splice(index, 1);
  }

  guardarCambiosModal() {
    const f = this.modalFila;
    if (!f) return;
    f.jsonRep = JSON.stringify(this.almacen[f.id] || []);
    this.cerrarModal();
    // validarCoherencia
    if (this.ignorarCambios) return;
    const texto = String(this.mantos.find((m) => String(m.id_tipo_mantenimiento) === String(f.tipo_servicio))?.nombre_completo || '- Resultado Mantenimiento -').toUpperCase();
    if (texto.includes('PREVENTIVO') && (this.almacen[f.id]?.length || 0) > 0) {
      setTimeout(() => alert('🤔 AVISO: Mantenimiento PREVENTIVO con repuestos.\n\nVerifica si deberías cambiar a Correctivo.'), 300);
    }
  }

  // ================================================================ BORRADOR (storageManager.js)
  guardarProgresoLocal() {
    if (this.ignorarCambios || this.enviandoFormulario) return;
    let hayDatos = false;
    const filas = this.filas.map((f) => {
      if (f.remision || f.id_cliente || f.id_punto || f.id_maquina || f.id_tecnico) hayDatos = true;
      return {
        id: String(f.id),
        remision: f.remision || '',
        id_cliente: f.id_cliente,
        id_punto: f.id_punto,
        id_maquina: f.id_maquina,
        modalidad: f.id_modalidad,
        id_tecnico: f.id_tecnico,
        tipo_servicio: f.tipo_servicio,
        hora_in: f.hora_in,
        hora_out: f.hora_out,
        valor: f.valor,
        estado: f.estado,
        calif: f.calif,
        obs: f.obs
      };
    });
    try {
      if (!hayDatos) {
        localStorage.removeItem(CLAVE_GUARDADO);
        return;
      }
      localStorage.setItem(CLAVE_GUARDADO, JSON.stringify({ fecha: new Date().getTime(), filas, repuestos: this.almacen }));
    } catch {}
  }

  private iniciarLimpio() {
    if (this.filas.length > 0) return; // ya hay filas (p.ej. programación)
    for (let i = 0; i < 3; i++) this.agregarFila();
  }

  private async verificarYRestaurar() {
    let borrador: string | null = null;
    try {
      borrador = localStorage.getItem(CLAVE_GUARDADO);
    } catch {}
    if (!borrador) return this.iniciarLimpio();
    let datos: any;
    try {
      datos = JSON.parse(borrador);
    } catch {
      localStorage.removeItem(CLAVE_GUARDADO);
      return this.iniciarLimpio();
    }
    if (!datos.filas || datos.filas.length === 0) return this.iniciarLimpio();
    const utiles = datos.filas.some((f: any) => f.remision || f.id_cliente || f.id_punto || f.id_maquina || f.id_tecnico);
    if (!utiles) {
      localStorage.removeItem(CLAVE_GUARDADO);
      return this.iniciarLimpio();
    }
    if (!confirm(`📂 RECUPERACIÓN DE DATOS\n\nHay un reporte pendiente con ${datos.filas.length} servicios del último cierre inesperado.\n\n¿Quieres recuperarlos?`)) {
      localStorage.removeItem(CLAVE_GUARDADO);
      return this.iniciarLimpio();
    }
    this.ignorarCambios = true;
    this.recuperando = true;
    try {
      this.filas = [];
      this.contadorFilas = 0;
      const almacenViejo: Record<string, RepItem[]> = datos.repuestos || {};
      this.almacen = {};
      for (const d of datos.filas) {
        const f = this.agregarFila();
        // Los repuestos se guardaron con el id de fila original; se re-asignan en orden
        if (almacenViejo[d.id]) this.almacen[f.id] = almacenViejo[d.id];
        else if (almacenViejo[f.id] && d.id == f.id) this.almacen[f.id] = almacenViejo[f.id];
        if (d.id_tecnico) {
          f.id_tecnico = d.id_tecnico;
          if (d.remision) {
            await this.cargarRemisiones(f, d.id_tecnico);
            f.remision = d.remision;
          }
        }
        if (d.id_cliente) f.id_cliente = d.id_cliente;
        if (d.id_cliente && d.id_punto) {
          await this.cargarPuntos(f, d.id_cliente);
          f.id_punto = d.id_punto;
        }
        if (d.id_punto && d.id_maquina) {
          await this.cargarMaquinas(f, d.id_punto);
          f.id_maquina = d.id_maquina;
          const m = f.maquinas.find((x) => String(x.id_maquina) === String(d.id_maquina));
          f.device_id = m?.device_id || '';
        }
        if (d.tipo_servicio) f.tipo_servicio = d.tipo_servicio;
        if (d.modalidad) f.id_modalidad = String(d.modalidad);
        f.hora_in = d.hora_in || '';
        f.hora_out = d.hora_out || '';
        this.calcTiempo(f);
        if (d.estado !== undefined && d.estado !== null && d.estado !== '') f.estado = String(d.estado);
        if (d.calif) f.calif = String(d.calif);
        f.obs = d.obs || '';
        if (this.almacen[f.id]) f.jsonRep = JSON.stringify(this.almacen[f.id]);
        if (d.modalidad) await this.calcularPrecio(f);
        if (d.valor) f.valor = d.valor;
      }
      Notif.borradorRecuperado(datos.filas.length);
    } catch (e) {
      console.error('Error en restauración:', e);
      localStorage.removeItem(CLAVE_GUARDADO);
    } finally {
      this.ignorarCambios = false;
      this.recuperando = false;
    }
  }

  // ================================================================ GUARDAR (app.js + crearNotificaciones.js)
  private nombreServicio(f: Fila): string {
    return String(this.mantos.find((m) => String(m.id_tipo_mantenimiento) === String(f.tipo_servicio))?.nombre_completo || '- Resultado Mantenimiento -')
      .toUpperCase()
      .trim();
  }

  async procesarGuardado() {
    if (this.filas.length === 0) {
      mostrarNotificacion('⚠️ No hay servicios para guardar.', 'error');
      return;
    }
    if (!(await this.validarTodasRemisiones())) return;

    let bloqueantes = false;
    let advertencias = false;
    for (const f of this.filas) {
      f.resaltado = '';
      if (!f.id_tecnico || !f.id_cliente || !f.id_punto || !f.id_maquina || !f.tipo_servicio) {
        bloqueantes = true;
        f.resaltado = 'rojo';
        setTimeout(() => f.resaltado === 'rojo' && (f.resaltado = ''), 4000);
      }
      if (f.sinTarifa) {
        bloqueantes = true;
        f.resaltado = 'rojo';
        f.pulso = true;
      }
      let cantRep = 0;
      try {
        cantRep = JSON.parse(f.jsonRep || '[]').length;
      } catch {}
      if (this.nombreServicio(f).includes('CORRECTIVO') && cantRep === 0) {
        advertencias = true;
        if (f.resaltado !== 'rojo') f.resaltado = 'amarillo';
      }
    }
    if (bloqueantes) {
      mostrarNotificacion('⛔ No se puede guardar: Hay datos incompletos o servicios SIN TARIFA (marcados en rojo).', 'error');
      return;
    }
    const mensaje = advertencias
      ? '⚠️ <b>¡ADVERTENCIA!</b><br><br>Hay mantenimientos <b>CORRECTIVOS que NO tienen repuestos</b>.<br>Esto es inusual.<br><br>¿Deseas guardar de todas formas?'
      : '¿Estás seguro de que deseas guardar y enviar este reporte?';
    if (!this.validarCamposEstrictos()) return;
    Notif.confirmar(mensaje, () => {
      this.guardando = true;
      this.enviandoFormulario = true;
      try {
        localStorage.removeItem(CLAVE_GUARDADO);
      } catch {}
      this.ejecutarGuardadoJSON();
    });
  }

  private validarCamposEstrictos(): boolean {
    const msgs: string[] = [];
    this.filas.forEach((f, i) => {
      if (f.id_maquina === '' || f.id_maquina == null) return;
      const faltan: string[] = [];
      const err: Record<string, boolean> = {};
      if (!String(f.remision || '').trim()) (faltan.push('Remisión'), (err['remision'] = true));
      if (!f.hora_in.trim()) (faltan.push('Hora Entrada'), (err['hora_in'] = true));
      if (!f.hora_out.trim()) (faltan.push('Hora Salida'), (err['hora_out'] = true));
      if (!f.obs.trim()) (faltan.push('¿Qué se Realizó?'), (err['obs'] = true));
      if (faltan.length) {
        msgs.push(`<b>Fila ${i + 1}:</b> ${faltan.join(', ')}`);
        f.errCampos = err;
        setTimeout(() => (f.errCampos = {}), 5000);
      }
    });
    if (msgs.length) {
      mostrarNotificacion(`❌ Error antes de guardar:<br>${msgs.join('<br>')}`, 'error', 6000);
      return false;
    }
    return true;
  }

  private async ejecutarGuardadoJSON() {
    this.guardando = true;
    const filasData = this.filas.map((f) => {
      const o: Record<string, any> = {
        id_tecnico: String(f.id_tecnico ?? ''),
        remision: String(f.remision ?? ''),
        id_cliente: String(f.id_cliente ?? ''),
        id_punto: String(f.id_punto ?? ''),
        id_maquina: String(f.id_maquina ?? ''),
        device_id: f.device_id,
        id_modalidad: String(f.id_modalidad ?? '1'),
        tipo_servicio: String(f.tipo_servicio ?? ''),
        hora_in: f.hora_in,
        hora_out: f.hora_out,
        valor: String(f.valor ?? ''),
        json_repuestos: f.jsonRep,
        estado: String(f.estado ?? ''),
        calif: String(f.calif ?? ''),
        obs: f.obs
      };
      if (f.id_orden_previa) o['id_orden_previa'] = String(f.id_orden_previa);
      return o;
    });
    try {
      const data: any = await this.api.post('ordenCrear', 'ajaxGuardarJSON', { fecha_reporte: this.fechaReporte, json_data: JSON.stringify(filasData) });
      if (data.status === 'ok') {
        alert('✅ ' + data.msg);
        window.location.reload();
      } else if (data.status === 'warning') {
        alert('⚠️ ' + data.msg);
        window.location.reload();
      } else {
        alert('❌ Error: ' + data.msg);
      }
    } catch (e) {
      console.error('Error guardando:', e);
      alert('❌ Ocurrió un error de conexión con el servidor.');
    } finally {
      this.guardando = false;
    }
  }

  // ================================================================ TECLADO (app.js)
  private atajosTeclado(e: KeyboardEvent) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
      e.preventDefault();
      this.agregarFila();
      toastSimple('Nueva fila agregada', 'success');
    }
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
      e.preventDefault();
      this.guardarProgresoLocal();
      toastSimple('Progreso guardado', 'success');
    }
  }

  seleccionarTexto(e: MouseEvent) {
    const t = e.target as HTMLElement;
    if (t instanceof HTMLInputElement) t.select();
  }

  navegarTabla(e: KeyboardEvent) {
    if (!['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key)) return;
    const t = e.target as HTMLElement;
    if (t.closest('.sb-panel') || (t instanceof HTMLInputElement && t.placeholder === 'Buscar...')) return;
    const tr = t.closest('tr');
    if (!tr) return;
    const sel = 'input:not([type="hidden"]):not([readonly]), select, app-select-buscar > div > button';
    const elems = Array.from(tr.querySelectorAll<HTMLElement>(sel));
    const idx = elems.indexOf(t);
    if (idx < 0) return;
    const esOpciones = t.tagName === 'SELECT' || t.tagName === 'BUTTON';
    const input = t instanceof HTMLInputElement ? t : null;
    switch (e.key) {
      case 'ArrowDown':
      case 'ArrowUp': {
        e.preventDefault();
        const destino = (e.key === 'ArrowDown' ? tr.nextElementSibling : tr.previousElementSibling) as HTMLElement | null;
        destino?.querySelectorAll<HTMLElement>(sel)[idx]?.focus();
        break;
      }
      case 'ArrowRight':
        if (esOpciones || (input && input.selectionEnd === input.value.length)) {
          if (idx < elems.length - 1) {
            e.preventDefault();
            elems[idx + 1].focus();
          }
        }
        break;
      case 'ArrowLeft':
        if (esOpciones || (input && input.selectionStart === 0)) {
          if (idx > 0) {
            e.preventDefault();
            elems[idx - 1].focus();
          }
        }
        break;
    }
  }
}
