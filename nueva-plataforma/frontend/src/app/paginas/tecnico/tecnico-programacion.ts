import { Component, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import Swal from 'sweetalert2';
import { Api } from '../../core/api';
import { SelectBuscar } from '../../shared/select-buscar';
import { DtAbajo, DtArriba, TablaDatos, Th } from '../../shared/tabla';

function hoy() {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

/** Port de tecnicoProgramacionVista.php (programación diaria del técnico logueado) */
@Component({
  selector: 'app-tecnico-programacion',
  imports: [FormsModule, RouterLink, SelectBuscar, DtArriba, DtAbajo, Th],
  styles: [
    `
      .stat-card { background: #fff; border-radius: 10px; padding: 1rem 1.25rem; box-shadow: 0 1px 4px rgba(0,0,0,.08); border-left: 4px solid #2563eb; display: flex; flex-direction: column; }
      .stat-card.verde { border-left-color: #16a34a; }
      .stat-card.naranja { border-left-color: #ea580c; }
      .stat-numero { font-size: 1.6rem; font-weight: 800; color: #1e293b; line-height: 1.1; }
      .stat-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; color: #64748b; font-weight: 700; }
      .badge { display: inline-flex; align-items: center; gap: .3rem; padding: .25rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 700; }
      .badge-azul { background: #dbeafe; color: #1e40af; }
      .badge-gris { background: #f1f5f9; color: #64748b; }
      .btn-atender { background: #2563eb; color: #fff; border: none; padding: .5rem .9rem; border-radius: 7px; font-weight: 700; cursor: pointer; }
      .btn-atender:hover { background: #1d4ed8; }
    `
  ],
  template: `
    <div class="w-full">
      <div class="bg-white rounded-lg shadow p-4 mb-4 flex flex-col md:flex-row md:justify-between md:items-end gap-3">
        <h2 class="text-xl font-bold text-gray-800"><i class="fas fa-calendar-check text-blue-600 mr-2"></i> Mis Servicios del Día</h2>
        <div class="flex gap-4 items-end">
          <div>
            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha de consulta</label>
            <input type="date" [(ngModel)]="fecha" (change)="cargar()" class="border border-gray-300 rounded px-2 py-1.5" />
          </div>
          <button (click)="abrirExtra()" style="background: #10b981; color: white; border: none; padding: 0.45rem 1rem; border-radius: 6px; font-weight: 700; cursor: pointer"><i class="fas fa-plus"></i> Agregar Extra</button>
        </div>
      </div>

      <div class="flex border-b border-gray-200 mb-4 bg-white rounded-t-lg overflow-hidden">
        <button (click)="pestana('pendientes')" class="flex-1 py-3 px-4 text-center font-bold text-sm transition-all duration-200" [class]="estado === 'pendientes' ? 'bg-blue-600 text-white border-b-4 border-blue-800' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
          <i class="fas fa-clock mr-2"></i> Pendientes
          <span class="ml-2 px-2 py-0.5 rounded-full text-xs" [class]="estado === 'pendientes' ? 'bg-white text-blue-600' : 'bg-gray-300 text-gray-700'">{{ badgePendientes }}</span>
        </button>
        <button (click)="pestana('finalizados')" class="flex-1 py-3 px-4 text-center font-bold text-sm transition-all duration-200" [class]="estado === 'finalizados' ? 'bg-green-600 text-white border-b-4 border-green-800' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
          <i class="fas fa-check-circle mr-2"></i> Finalizados
          <span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-gray-300 text-gray-700">{{ badgeFinalizados }}</span>
        </button>
      </div>

      @if (error) {
        <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 rounded p-3 mb-4 text-sm"><i class="fas fa-exclamation-triangle mr-2"></i>{{ error }}</div>
      }

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
        <div class="stat-card"><span class="stat-numero">{{ statTotal }}</span><span class="stat-label">Total servicios</span></div>
        <div class="stat-card verde"><span class="stat-numero">{{ statFecha }}</span><span class="stat-label">Fecha seleccionada</span></div>
        <div class="stat-card naranja"><span class="stat-numero">{{ statPuntos }}</span><span class="stat-label">Puntos distintos</span></div>
      </div>

      <div class="bg-white rounded-lg shadow p-4 relative">
        @if (cargando) {
          <div class="absolute inset-0 bg-white bg-opacity-70 flex items-center justify-center z-10"><i class="fas fa-spinner fa-spin text-3xl text-blue-600"></i></div>
        }
        <app-dt-arriba [dt]="dt" />
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-100 text-gray-700">
              <tr>
                <th app-th [dt]="dt" campo="nombre_cliente" class="py-2 px-3 text-left">Cliente</th>
                <th app-th [dt]="dt" campo="nombre_punto" class="py-2 px-3 text-left">Punto / Dirección</th>
                <th app-th [dt]="dt" campo="nombre_tipo_maquina" class="py-2 px-3 text-left">Máquina</th>
                <th app-th [dt]="dt" campo="tipo_mantenimiento" class="py-2 px-3 text-left">Tipo Mantenimiento</th>
                <th class="py-2 px-3 text-center">Acción</th>
              </tr>
            </thead>
            <tbody>
              @for (r of dt.pagina; track r.id_ordenes_servicio) {
                <tr class="border-b hover:bg-gray-50">
                  <td class="py-2 px-3">
                    @if (r.nombre_cliente) { <strong>{{ r.nombre_cliente }}</strong> } @else { <span class="text-gray-400">Sin cliente</span> }
                  </td>
                  <td class="py-2 px-3">
                    <strong>{{ r.nombre_punto || 'Sin punto' }}</strong>
                    @if (r.direccion_punto) { <small class="block text-gray-500"><i class="fas fa-map-marker-alt"></i> {{ r.direccion_punto }}</small> }
                  </td>
                  <td class="py-2 px-3">
                    <strong>{{ r.nombre_tipo_maquina || 'N/A' }}</strong>
                    <small class="block text-gray-500">{{ r.device_id ? 'Device ID: ' + r.device_id : 'Sin Device ID' }}</small>
                  </td>
                  <td class="py-2 px-3">
                    @if (r.tipo_mantenimiento) { <span class="badge badge-azul"><i class="fas fa-tools"></i> {{ r.tipo_mantenimiento }}</span> } @else { <span class="badge badge-gris">Sin definir</span> }
                  </td>
                  <td class="py-2 px-3 text-center">
                    <div style="display: flex; gap: 0.5rem; justify-content: center; align-items: center; flex-wrap: wrap">
                      @if (+r.estado === 2) {
                        <button class="btn-atender" (click)="atender(r.id_ordenes_servicio)"><i class="fas fa-clipboard-check"></i> Atender</button>
                        <button (click)="eliminar(r.id_ordenes_servicio)" style="background: #dc2626; color: white; border: none; padding: 0.5rem 0.75rem; border-radius: 7px; cursor: pointer" title="Cancelar servicio"><i class="fas fa-trash-alt"></i></button>
                      } @else {
                        <button (click)="verDetalle(r.id_ordenes_servicio)" style="background: #16a34a; color: white; border: none; padding: 0.5rem 0.75rem; border-radius: 7px; cursor: pointer" title="Ver reporte"><i class="fas fa-eye"></i> Ver</button>
                        <a [routerLink]="['/tecnicoReporteEditar']" [queryParams]="{ orden: r.id_ordenes_servicio }" style="background: #d97706; color: white; padding: 0.5rem 0.75rem; border-radius: 7px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px" title="Editar reporte"><i class="fas fa-edit"></i> Editar</a>
                      }
                    </div>
                  </td>
                </tr>
              } @empty {
                <tr>
                  <td colspan="5" class="text-center py-8 text-gray-400">
                    <i class="fas text-3xl mb-2 block" [class.fa-calendar-times]="!dt.busqueda" [class.fa-search]="!!dt.busqueda"></i>
                    {{ dt.busqueda ? 'No se encontraron resultados con ese filtro.' : 'No hay servicios programados para esta fecha.' }}
                  </td>
                </tr>
              }
            </tbody>
          </table>
        </div>
        <app-dt-abajo [dt]="dt" />
      </div>
    </div>

    <!-- Modal servicio extra -->
    @if (modalExtra) {
      <div class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-md p-6">
          <div class="flex justify-between items-center border-b pb-3 mb-4">
            <h3 class="text-lg font-bold text-gray-800"><i class="fas fa-plus-circle text-green-600"></i> Agendar Servicio Extra</h3>
            <button class="text-gray-400 hover:text-red-500" (click)="cerrarExtra()"><i class="fas fa-times"></i></button>
          </div>
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Fecha de Visita</label>
              <input type="date" [(ngModel)]="extra.fecha_visita" class="w-full border border-gray-300 rounded p-2" />
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">1. Cliente</label>
              <app-select-buscar [opciones]="clientes" valor="id_cliente" texto="nombre_cliente" [placeholder]="clientes.length ? '-- Seleccione Cliente --' : 'Cargando clientes...'" [ngModel]="extra.id_cliente" (cambio)="extra.id_cliente = $event; cambioCliente($event)" />
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">2. Punto</label>
              <app-select-buscar [opciones]="puntos" valor="id_punto" [texto]="textoPunto" [placeholder]="phPunto" [disabled]="!extra.id_cliente" [ngModel]="extra.id_punto" (cambio)="extra.id_punto = $event; cambioPunto($event)" />
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">3. Máquina</label>
              <app-select-buscar [opciones]="maquinas" valor="id_maquina" [texto]="textoMaquina" [placeholder]="phMaquina" [disabled]="!extra.id_punto" [(ngModel)]="extra.id_maquina" />
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">4. Tipo de Mantenimiento</label>
              <app-select-buscar [opciones]="tipos" valor="id_tipo_mantenimiento" texto="nombre_completo" [placeholder]="tipos.length ? '-- Seleccione Tipo --' : 'Cargando mantenimientos...'" [(ngModel)]="extra.id_tipo_mantenimiento" />
            </div>
          </div>
          <div class="flex justify-end gap-2 mt-6">
            <button class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded font-bold" [disabled]="guardando" (click)="cerrarExtra()">Cancelar</button>
            <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded font-bold" [disabled]="guardando" (click)="guardarExtra()">{{ guardando ? 'Guardando...' : 'Agendar Servicio' }}</button>
          </div>
        </div>
      </div>
    }

    <!-- Modal detalle -->
    @if (modalDetalle) {
      <div class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg p-6">
          <div class="flex justify-between items-center border-b pb-3 mb-4">
            <h3 class="text-lg font-bold text-gray-800"><i class="fas fa-info-circle text-green-600"></i> Detalle del Servicio</h3>
            <button (click)="modalDetalle = false" class="text-gray-400 hover:text-red-500 text-xl"><i class="fas fa-times"></i></button>
          </div>
          <div class="space-y-2 text-sm">
            @if (detalleError) {
              <p class="text-red-500">{{ detalleError }}</p>
            } @else if (detalle) {
              <div class="grid grid-cols-2 gap-2">
                <div><strong>Cliente:</strong></div><div>{{ detalle.nombre_cliente || 'N/A' }}</div>
                <div><strong>Punto:</strong></div><div>{{ detalle.nombre_punto || 'N/A' }}</div>
                <div><strong>Máquina:</strong></div><div>{{ detalle.nombre_tipo_maquina || 'N/A' }}</div>
                <div><strong>Tipo Mantenimiento:</strong></div><div>{{ detalle.tipo_mantenimiento || 'N/A' }}</div>
                <div><strong>Estado Final:</strong></div><div>{{ detalle.estado_final || 'N/A' }}</div>
                <div><strong>Hora Entrada:</strong></div><div>{{ detalle.hora_entrada || 'N/A' }}</div>
                <div><strong>Hora Salida:</strong></div><div>{{ detalle.hora_salida || 'N/A' }}</div>
                <div><strong>Tiempo:</strong></div><div>{{ detalle.tiempo_servicio || 'N/A' }}</div>
                <div><strong>Actividades:</strong></div>
                <div class="col-span-2 bg-gray-50 p-2 rounded whitespace-pre-line">{{ detalle.actividades_realizadas || 'N/A' }}</div>
              </div>
            } @else {
              <p class="text-gray-400"><i class="fas fa-spinner fa-spin"></i> Cargando...</p>
            }
          </div>
          <div class="mt-4 border-t pt-3 text-right">
            <button (click)="modalDetalle = false" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded font-bold">Cerrar</button>
          </div>
        </div>
      </div>
    }
  `
})
export class TecnicoProgramacion implements OnInit {
  private api = inject(Api);
  private router = inject(Router);

  fecha = hoy();
  estado: 'pendientes' | 'finalizados' = 'pendientes';
  dt = new TablaDatos<any>([], 25);
  cargando = false;
  error = '';
  badgePendientes = 0;
  badgeFinalizados = 0;
  statTotal: any = '—';
  statFecha = '—';
  statPuntos: any = '—';

  modalExtra = false;
  guardando = false;
  extra: any = {};
  clientes: any[] = [];
  tipos: any[] = [];
  puntos: any[] = [];
  maquinas: any[] = [];
  phPunto = 'Seleccione primero un cliente';
  phMaquina = 'Seleccione primero un punto';

  modalDetalle = false;
  detalle: any = null;
  detalleError = '';

  textoPunto = (p: any) => `${p.nombre_punto} (${p.direccion || 'Sin dir'})`;
  textoMaquina = (m: any) => `${m.device_id} - ${m.nombre_tipo_maquina}`;

  ngOnInit() {
    this.dt.campoOrden = 'nombre_cliente';
    this.cargar();
  }

  async cargar() {
    this.cargando = true;
    this.error = '';
    try {
      const r: any = await this.api.postForm('tecnicoProgramacion', 'ajaxObtenerProgramacion', { fecha: this.fecha, estado: this.estado });
      if (r.error) this.error = r.error + (r.debug ? ' | ' + r.debug : '');
      const datos = r.data || [];
      this.stats(datos);
      this.dt.set(datos);
    } catch (e: any) {
      this.error = 'Error de comunicación con el servidor: ' + (e?.statusText || '');
      this.dt.set([]);
    } finally {
      this.cargando = false;
    }
  }

  private stats(datos: any[]) {
    const [y, m, d] = (this.fecha || '--').split('-');
    const pendientes = datos.filter((r) => +r.estado === 2).length;
    const finalizados = datos.filter((r) => +r.estado === 1).length;
    this.badgePendientes = pendientes;
    this.badgeFinalizados = finalizados;
    this.statTotal = this.estado === 'pendientes' ? pendientes : finalizados;
    this.statFecha = `${d}/${m}/${y}`;
    this.statPuntos = new Set(datos.map((r) => r.nombre_punto)).size;
  }

  pestana(e: 'pendientes' | 'finalizados') {
    this.estado = e;
    this.cargar();
  }

  async eliminar(id: number) {
    if (!confirm('¿Estás seguro de que Prosegur canceló este servicio? Esta acción lo borrará de tu programación de forma definitiva.')) return;
    this.cargando = true;
    try {
      const r: any = await this.api.postForm('tecnicoProgramacion', 'ajaxEliminarServicio', { id_orden: id });
      if (r.success) await this.cargar();
      else alert('Error: ' + r.msj);
    } catch {
      alert('Error de comunicación con el servidor al intentar eliminar.');
    } finally {
      this.cargando = false;
    }
  }

  async verDetalle(id: number) {
    this.modalDetalle = true;
    this.detalle = null;
    this.detalleError = '';
    try {
      const r: any = await this.api.postForm('tecnicoProgramacion', 'ajaxObtenerDetalleServicio', { id_orden: id });
      if (r.success) this.detalle = r.data;
      else this.detalleError = 'Error al cargar el detalle: ' + r.msj;
    } catch {
      this.detalleError = 'Error de comunicación con el servidor.';
    }
  }

  // ================================================================ EXTRA
  async abrirExtra() {
    this.extra = { fecha_visita: this.fecha, id_cliente: '', id_punto: '', id_maquina: '', id_tipo_mantenimiento: '' };
    this.modalExtra = true;
    if (!this.clientes.length) {
      this.api.postForm('tecnicoProgramacion', 'ajaxObtenerClientes').then((d: any) => (this.clientes = d || []));
      this.api.postForm('tecnicoProgramacion', 'ajaxObtenerTiposMantenimiento').then((d: any) => (this.tipos = d || []));
    }
  }

  cerrarExtra() {
    this.modalExtra = false;
    this.puntos = [];
    this.maquinas = [];
    this.phPunto = 'Seleccione primero un cliente';
    this.phMaquina = 'Seleccione primero un punto';
  }

  async cambioCliente(id: any) {
    this.extra.id_punto = '';
    this.extra.id_maquina = '';
    this.puntos = [];
    this.maquinas = [];
    this.phMaquina = 'Seleccione primero un punto';
    if (!id) {
      this.phPunto = 'Seleccione primero un cliente';
      return;
    }
    this.phPunto = 'Cargando puntos...';
    this.puntos = (await this.api.postForm<any[]>('tecnicoProgramacion', 'ajaxObtenerPuntos', { id_cliente: id })) || [];
    this.phPunto = '-- Seleccione Punto --';
  }

  async cambioPunto(id: any) {
    this.extra.id_maquina = '';
    this.maquinas = [];
    if (!id) {
      this.phMaquina = 'Seleccione primero un punto';
      return;
    }
    this.phMaquina = 'Cargando máquinas...';
    this.maquinas = (await this.api.postForm<any[]>('tecnicoProgramacion', 'ajaxObtenerMaquinas', { id_punto: id })) || [];
    this.phMaquina = '-- Seleccione Máquina --';
  }

  async guardarExtra() {
    const d = this.extra;
    if (!d.fecha_visita || !d.id_cliente || !d.id_punto || !d.id_maquina || !d.id_tipo_mantenimiento) {
      alert('Por favor, complete todos los campos.');
      return;
    }
    this.guardando = true;
    try {
      const r: any = await this.api.postForm('tecnicoProgramacion', 'ajaxGuardarExtra', d);
      if (r.success) {
        this.cerrarExtra();
        await this.cargar();
        alert('¡Servicio agregado!');
      } else alert('Error: ' + r.msj);
    } catch {
      alert('Error de red al guardar.');
    } finally {
      this.guardando = false;
    }
  }

  // ================================================================ ATENDER (GPS)
  atender(idOrden: number) {
    Swal.fire({ title: 'Iniciando Servicio...', text: 'Obteniendo tu ubicación actual. Asegúrate de tener el GPS encendido.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    if (!navigator.geolocation) {
      Swal.fire('Incompatible', 'Tu dispositivo o navegador no soporta ubicación GPS.', 'error');
      return;
    }
    navigator.geolocation.getCurrentPosition(
      async (pos) => {
        try {
          const r: any = await this.api.postForm('tecnicoProgramacion', 'ajaxIniciarServicio', {
            id_orden: idOrden,
            latitud_inicio: pos.coords.latitude,
            longitud_inicio: pos.coords.longitude
          });
          if (r.success) {
            Swal.close();
            this.router.navigate(['/tecnicoReporte'], { queryParams: { orden: idOrden } });
          } else Swal.fire('Error', r.msj, 'error');
        } catch {
          Swal.fire('Error de red', 'No se pudo comunicar con el servidor para iniciar el servicio.', 'error');
        }
      },
      (error) => {
        let msj = 'Error desconocido.';
        if (error.code == 1) msj = 'Denegaste el permiso de ubicación. Es OBLIGATORIO permitirlo para iniciar el servicio.';
        if (error.code == 2) msj = 'No se pudo obtener la señal GPS. Sal a un lugar más despejado.';
        if (error.code == 3) msj = 'Se agotó el tiempo para obtener la ubicación.';
        Swal.fire('GPS Requerido', msj, 'error');
      },
      { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
  }
}
