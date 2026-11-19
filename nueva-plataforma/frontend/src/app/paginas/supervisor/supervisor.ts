import { Component, OnDestroy, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { DomSanitizer, SafeHtml } from '@angular/platform-browser';
import { Api } from '../../core/api';
import { SelectBuscar } from '../../shared/select-buscar';

// =====================================================================================
// MONITOR DE SERVICIOS (panelSupervisorVista.php)
// =====================================================================================
@Component({
  selector: 'app-panel-supervisor',
  imports: [FormsModule, SelectBuscar],
  styles: [`
    .header-panel { display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 1rem 1.25rem; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.05); margin-bottom: 1rem; }
    .btn-agregar { background: #2563eb; color: #fff; font-weight: 700; padding: .5rem 1rem; border-radius: 8px; }
    .tablero-kanban { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; }
    .columna-kanban { background: #f1f5f9; border-radius: 10px; padding: .75rem; min-height: 60vh; }
    .col-header { font-weight: 700; color: #334155; padding-bottom: .5rem; margin-bottom: .75rem; border-bottom: 3px solid; }
    .tarjeta-servicio { background: #fff; border-radius: 8px; padding: .75rem; margin-bottom: .75rem; box-shadow: 0 1px 3px rgba(0,0,0,.08); border-left: 4px solid #cbd5e1; }
    .tarjeta-servicio.pendiente { border-left-color: #facc15; } .tarjeta-servicio.finalizado { border-left-color: #22c55e; }
    .t-header { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: .4rem; }
    .t-remision { font-weight: 800; color: #1e293b; } .t-tecnico { color: #2563eb; font-weight: 600; }
    .t-body p { margin: 2px 0; font-size: 13px; color: #475569; }
    .t-footer { margin-top: .5rem; text-align: right; }
    .btn-cancelar { background: #fee2e2; color: #b91c1c; font-size: 12px; font-weight: 700; padding: .25rem .6rem; border-radius: 6px; }
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.5); display: flex; justify-content: center; align-items: center; z-index: 1000; }
    .modal-content { background: #fff; border-radius: 12px; padding: 1.25rem; width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
    .form-group { margin-bottom: .75rem; } .form-group label { display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: .25rem; }
    .form-control { width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: .45rem .6rem; }
    .modal-footer { display: flex; justify-content: flex-end; gap: .5rem; margin-top: 1rem; }
    .btn-cerrar-modal { background: #e2e8f0; color: #334155; font-weight: 700; padding: .5rem 1rem; border-radius: 8px; }
    .btn-guardar-modal { background: #16a34a; color: #fff; font-weight: 700; padding: .5rem 1rem; border-radius: 8px; }
  `],
  template: `
    <div class="panel-wrapper">
      <div class="header-panel">
        <h3 style="margin:0; color:#1e293b;" class="font-bold text-lg"><i class="fas fa-desktop"></i> Monitor de Servicios (Hoy)</h3>
        <div class="flex items-center">
          @if (actualizando) { <span style="font-size: 12px; color: #16a34a; font-weight:bold; margin-right: 15px;"><i class="fas fa-sync fa-spin"></i> Actualizando...</span> }
          <button class="btn-agregar" (click)="abrirModal()"><i class="fas fa-plus"></i> Nuevo Servicio</button>
        </div>
      </div>
      @if (modal) {
        <div class="modal-overlay">
          <div class="modal-content">
            <div class="modal-header"><h3 class="font-bold"><i class="fas fa-plus-circle"></i> Nuevo Servicio</h3><button (click)="cerrarModal()"><i class="fas fa-times"></i></button></div>
            <div class="form-group"><label>Fecha de Visita</label><input type="date" [(ngModel)]="ns.fecha_visita" class="form-control"></div>
            <div class="form-group"><label>Técnico Asignado</label><app-select-buscar [opciones]="tecnicos" valor="id_tecnico" texto="nombre_tecnico" placeholder="-- Seleccione Técnico --" [(ngModel)]="ns.id_tecnico" /></div>
            <div class="form-group"><label>1. Cliente</label><app-select-buscar [opciones]="clientes" valor="id_cliente" texto="nombre_cliente" placeholder="-- Seleccione Cliente --" [(ngModel)]="ns.id_cliente" (cambio)="cambioCliente()" /></div>
            <div class="form-group"><label>2. Punto</label><app-select-buscar [opciones]="puntos" valor="id_punto" [texto]="textoPunto" [placeholder]="ns.id_cliente ? '-- Seleccione Punto --' : 'Seleccione un cliente'" [disabled]="!ns.id_cliente" [(ngModel)]="ns.id_punto" (cambio)="cambioPunto()" /></div>
            <div class="form-group"><label>3. Máquina</label><app-select-buscar [opciones]="maquinas" valor="id_maquina" [texto]="textoMaquina" [placeholder]="ns.id_punto ? '-- Seleccione Máquina --' : 'Seleccione un punto'" [disabled]="!ns.id_punto" [(ngModel)]="ns.id_maquina" /></div>
            <div class="form-group"><label>4. Tipo de Mantenimiento</label><app-select-buscar [opciones]="tipos" valor="id_tipo_mantenimiento" texto="nombre_completo" placeholder="-- Seleccione Tipo --" [(ngModel)]="ns.id_tipo_mantenimiento" /></div>
            <div class="modal-footer">
              <button class="btn-cerrar-modal" (click)="cerrarModal()">Cancelar</button>
              <button class="btn-guardar-modal" [disabled]="guardando" (click)="guardar()">{{ guardando ? 'Guardando...' : 'Agendar Servicio' }}</button>
            </div>
          </div>
        </div>
      }
      <div class="tablero-kanban">
        <div class="columna-kanban">
          <div class="col-header" style="border-bottom-color: #fde047;">🕒 Programados ({{ pendientes.length }})</div>
          @for (s of pendientes; track s.id_ordenes_servicio) {
            <div class="tarjeta-servicio pendiente">
              <div class="t-header"><span class="t-remision">#{{ s.numero_remision || 'N/A' }}</span><span class="t-tecnico"><i class="fas fa-user-cog"></i> {{ s.nombre_tecnico || 'Sin Asignar' }}</span></div>
              <div class="t-body"><p><strong>Cliente:</strong> {{ s.nombre_cliente || 'SIN CLIENTE' }}</p><p><strong>Punto:</strong> {{ s.nombre_punto || 'No especificado' }}</p></div>
              <div class="t-footer"><button class="btn-cancelar" (click)="cancelar(s.id_ordenes_servicio)"><i class="fas fa-times"></i> Cancelar</button></div>
            </div>
          }
        </div>
        <div class="columna-kanban">
          <div class="col-header" style="border-bottom-color: #86efac;">✅ Finalizados ({{ finalizados.length }})</div>
          @for (s of finalizados; track s.id_ordenes_servicio) {
            <div class="tarjeta-servicio finalizado">
              <div class="t-header"><span class="t-remision">#{{ s.numero_remision || 'N/A' }}</span><span class="t-tecnico"><i class="fas fa-user-cog"></i> {{ s.nombre_tecnico || 'Sin Asignar' }}</span></div>
              <div class="t-body"><p><strong>Cliente:</strong> {{ s.nombre_cliente || 'SIN CLIENTE' }}</p><p><strong>Punto:</strong> {{ s.nombre_punto || 'No especificado' }}</p></div>
            </div>
          }
        </div>
      </div>
    </div>
  `
})
export class PanelSupervisor implements OnInit, OnDestroy {
  private api = inject(Api);
  pendientes: any[] = [];
  finalizados: any[] = [];
  actualizando = false;
  modal = false;
  guardando = false;
  hoy = '';
  ns: any = {};
  tecnicos: any[] = [];
  clientes: any[] = [];
  puntos: any[] = [];
  maquinas: any[] = [];
  tipos: any[] = [];
  textoPunto = (p: any) => `${p.nombre_punto} (${p.direccion || 'Sin dir'})`;
  textoMaquina = (m: any) => `${m.device_id} - ${m.nombre_tipo_maquina}`;
  private timer: any;

  async ngOnInit() {
    const r: any = await this.api.get('panelSupervisor');
    this.hoy = r.hoy;
    this.cargar();
    this.timer = setInterval(() => this.cargar(), 10000);
  }
  ngOnDestroy() {
    clearInterval(this.timer);
  }
  async cargar() {
    this.actualizando = true;
    try {
      const r: any = await this.api.postForm('panelSupervisor', 'ajaxObtenerServicios', {});
      if (r.success) {
        const datos: any[] = r.data || [];
        const est = (s: any) => {
          const n = parseInt(s.id_estado_maquina, 10);
          return isNaN(n) ? 2 : n;
        };
        this.pendientes = datos.filter((s) => est(s) === 2);
        this.finalizados = datos.filter((s) => est(s) === 1);
      }
    } catch {
      console.error('Error de conexión AJAX al actualizar tablero.');
    } finally {
      setTimeout(() => (this.actualizando = false), 1000);
    }
  }
  async cancelar(id: any) {
    if (!confirm('¿Estás seguro de cancelar el servicio remisión #' + id + '?')) return;
    try {
      const r: any = await this.api.postForm('panelSupervisor', 'ajaxCancelarServicio', { id_orden: id });
      if (r.success) this.cargar();
      else alert('Error: ' + r.msj);
    } catch {
      alert('Error de conexión al intentar cancelar el servicio.');
    }
  }
  async abrirModal() {
    this.ns = { fecha_visita: this.hoy, id_tecnico: null, id_cliente: null, id_punto: null, id_maquina: null, id_tipo_mantenimiento: null };
    this.modal = true;
    if (!this.tecnicos.length) {
      [this.tecnicos, this.clientes, this.tipos] = await Promise.all([
        this.api.postForm('panelSupervisor', 'ajaxObtenerTecnicos', {}),
        this.api.postForm('panelSupervisor', 'ajaxObtenerClientes', {}),
        this.api.postForm('panelSupervisor', 'ajaxObtenerTiposMantenimiento', {})
      ]) as any;
    }
  }
  cerrarModal() {
    this.modal = false;
    this.puntos = [];
    this.maquinas = [];
  }
  async cambioCliente() {
    this.ns.id_punto = null;
    this.ns.id_maquina = null;
    this.maquinas = [];
    this.puntos = this.ns.id_cliente ? await this.api.postForm('panelSupervisor', 'ajaxObtenerPuntos', { id_cliente: this.ns.id_cliente }) : [];
  }
  async cambioPunto() {
    this.ns.id_maquina = null;
    this.maquinas = this.ns.id_punto ? await this.api.postForm('panelSupervisor', 'ajaxObtenerMaquinas', { id_punto: this.ns.id_punto }) : [];
  }
  async guardar() {
    const d = this.ns;
    if (!d.id_tecnico || !d.id_cliente || !d.id_punto || !d.id_maquina || !d.id_tipo_mantenimiento) {
      alert('Por favor, complete todos los campos.');
      return;
    }
    this.guardando = true;
    try {
      const r: any = await this.api.postForm('panelSupervisor', 'ajaxGuardarServicio', d);
      if (r.success) {
        this.cerrarModal();
        this.cargar();
      } else alert('Error: ' + r.msj);
    } catch {
      alert('Error de red al guardar.');
    } finally {
      this.guardando = false;
    }
  }
}

// =====================================================================================
// EMISIÓN DE ALERTAS LOGÍSTICAS (notificacionesLogisticaVista.php)
// =====================================================================================
@Component({
  selector: 'app-notificaciones-logistica',
  template: `
    <div class="max-w-4xl mx-auto mt-4">
      <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <div class="bg-gray-900 text-white p-6"><h3 class="text-xl font-bold flex items-center"><i class="fas fa-bell fa-2x mr-3 text-yellow-400"></i> Emisión de Alertas Logísticas</h3></div>
        <div class="p-8">
          <div class="bg-amber-50 shadow-sm mb-4 p-4 rounded" style="border-left: 5px solid #f59e0b;">
            <h5 class="font-bold text-gray-800"><i class="fas fa-info-circle text-amber-500 mr-2"></i> ¿Qué hace este proceso?</h5>
            <p class="mb-0 text-gray-500">
              Al ejecutar este proceso, el sistema analizará la base de datos para detectar:
              <br>1. Puntos que han sido visitados <b>más de 2 veces</b> en los últimos 7 días.
              <br>2. Desplazamientos <b>urbanos</b> del día de hoy que hayan tardado <b>más de 40 minutos</b>.
              <br><br>Si se encuentran coincidencias, se enviará automáticamente un correo a supervisión.
            </p>
          </div>
          <div class="text-center p-4">
            <button type="button" (click)="generar()" [disabled]="procesando" class="bg-gradient-to-r from-amber-400 to-orange-500 text-white text-lg px-8 py-3 rounded-full font-bold shadow hover:opacity-90">
              @if (procesando) { <i class="fas fa-spinner fa-spin mr-2"></i> ANALIZANDO BASE DE DATOS... } @else { <i class="fas fa-paper-plane mr-2"></i> GENERAR Y ENVIAR REPORTES AHORA }
            </button>
          </div>
          @if (r) {
            <div class="mt-8">
              <hr class="mb-4">
              <div class="flex justify-between items-center p-3 bg-gray-50 rounded shadow-sm mb-4" [style.border-left]="'5px solid ' + (estado.color)">
                <div><h4 class="font-bold text-lg" [style.color]="estado.color"><i class="fas mr-2" [class]="estado.icono"></i>{{ estado.titulo }}</h4><small class="text-gray-500">{{ estado.sub }}</small></div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 rounded shadow-sm bg-white" style="border-left: 5px solid #3b82f6;"><h5 class="font-bold text-gray-800"><i class="fas fa-map-marker-alt text-red-500 mr-2"></i> Visitas Recurrentes</h5><h2 class="text-3xl text-blue-600 mb-0">{{ r.datos_visitas?.length || 0 }}</h2><small class="text-gray-500">Puntos detectados</small></div>
                <div class="p-4 rounded shadow-sm bg-white" style="border-left: 5px solid #ef4444;"><h5 class="font-bold text-gray-800"><i class="fas fa-route text-red-500 mr-2"></i> Tiempos Excedidos (&gt;40m)</h5><h2 class="text-3xl text-red-600 mb-0">{{ r.datos_desplazamientos?.length || 0 }}</h2><small class="text-gray-500">Viajes urbanos detectados hoy</small></div>
              </div>
              <div class="mt-4">
                <button class="border border-gray-400 text-gray-600 text-sm px-3 py-1 rounded hover:bg-gray-100" type="button" (click)="vistaPrevia = !vistaPrevia"><i class="fas fa-eye mr-1"></i> Ver vista previa del correo enviado</button>
                @if (vistaPrevia) {
                  @if (r.html_generado) { <div class="mt-3 p-4 bg-gray-50 border rounded" style="max-height: 400px; overflow-y: auto;" [innerHTML]="html"></div> }
                  @else { <div class="mt-3 p-4 bg-gray-50 border rounded"><p class="text-gray-500">No se generó cuerpo de correo porque no hubo alertas.</p></div> }
                }
              </div>
            </div>
          }
        </div>
      </div>
    </div>
  `
})
export class NotificacionesLogistica {
  private api = inject(Api);
  private san = inject(DomSanitizer);
  r: any = null;
  html: SafeHtml = '';
  procesando = false;
  vistaPrevia = false;
  estado = { titulo: '', sub: '', color: '#198754', icono: 'fa-check-circle' };

  async generar() {
    this.procesando = true;
    this.r = null;
    try {
      const r: any = await this.api.postForm('notificacionesLogistica', 'procesarNotificaciones', {});
      if (!r.exito) {
        alert('Error de Servidor: ' + r.error);
        return;
      }
      const v = r.datos_visitas?.length || 0, d = r.datos_desplazamientos?.length || 0;
      if (r.correo_enviado) this.estado = { titulo: 'Correos Enviados Exitosamente', sub: 'Se enviaron las alertas a los supervisores.', color: '#198754', icono: 'fa-check-circle' };
      else if (!v && !d) this.estado = { titulo: 'Sin novedades hoy', sub: 'No se encontraron registros que cumplan las condiciones. No se envió correo.', color: '#0dcaf0', icono: 'fa-info-circle' };
      else this.estado = { titulo: 'Error al enviar correo', sub: 'Se detectaron datos, pero hubo un fallo al intentar enviar el correo.', color: '#dc3545', icono: 'fa-exclamation-triangle' };
      this.html = this.san.bypassSecurityTrustHtml(r.html_generado || '');
      this.r = r;
    } catch {
      alert('Ocurrió un error en la comunicación. Revisa la consola para más detalles.');
    } finally {
      this.procesando = false;
    }
  }
}
