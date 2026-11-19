import { Component, ElementRef, OnDestroy, OnInit, ViewChild, inject } from '@angular/core';
import { Location } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import Chart from 'chart.js/auto';
import { Api } from '../../core/api';
import { SelectBuscar } from '../../shared/select-buscar';
import { DtAbajo, DtArriba, TablaDatos } from '../../shared/tabla';

const fechaJs = (s: any) => new Date(String(s ?? '').replace(/-/g, '/')).getTime();
const dos = (n: number) => (n < 10 ? '0' + n : '' + n);
function reloj(inicio: any) {
  const d = Date.now() - fechaJs(inicio);
  const h = Math.floor((d % 86400000) / 3600000), m = Math.floor((d % 3600000) / 60000), s = Math.floor((d % 60000) / 1000);
  return `${dos(h)}:${dos(m)}:${dos(s)}`;
}
function dmy(f: any) {
  const s = String(f || '').substring(0, 10).split('-');
  return s.length === 3 ? `${s[2]}/${s[1]}/${s[0]}` : '';
}
function hora12(f: any) {
  const d = new Date(fechaJs(f));
  if (isNaN(d.getTime())) return '';
  let h = d.getHours();
  const ap = h >= 12 ? 'PM' : 'AM';
  h = h % 12 || 12;
  return `${dos(h)}:${dos(d.getMinutes())} ${ap}`;
}
function ymd(d: Date) {
  return `${d.getFullYear()}-${dos(d.getMonth() + 1)}-${dos(d.getDate())}`;
}

// =====================================================================================
// CRONÓMETRO — TÉCNICO (cronometroCrearVista.php)
// =====================================================================================
@Component({
  selector: 'app-cronometro-crear',
  imports: [FormsModule, SelectBuscar],
  styles: [`.cronometro-fuente { font-family: 'Courier New', Courier, monospace; }`],
  template: `
    <div class="bg-blue-800 text-white p-4 shadow-md sticky top-0 z-40 flex items-center gap-3 -mx-4 md:mx-0 rounded-none md:rounded-lg">
      <button (click)="volver()" class="text-white bg-blue-700 hover:bg-blue-600 p-2 rounded-full w-10 h-10 flex items-center justify-center transition"><i class="fas fa-arrow-left"></i></button>
      <div><h1 class="font-bold text-lg leading-tight">Control de Servicio</h1><p class="text-blue-200 text-xs">Monitoreo en tiempo real</p></div>
    </div>
    @if (error) {
      <div class="max-w-lg mx-auto p-3 mt-4"><div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded">{{ error }}</div></div>
    }
    @if (d && !error) {
      <div class="max-w-lg mx-auto p-3 space-y-4 mt-2 mb-24">
        @if (activo) {
          <div class="bg-white rounded-xl shadow-md border border-blue-200 p-6 text-center space-y-4">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 text-blue-600 rounded-full mb-2"><i class="fas fa-stopwatch text-3xl animate-pulse"></i></div>
            <h2 class="text-gray-500 font-bold uppercase text-xs">Tiempo Transcurrido</h2>
            <div class="bg-gray-900 text-green-400 rounded-lg py-4 px-2 shadow-inner"><span class="text-5xl font-bold cronometro-fuente tracking-widest">{{ tiempo }}</span></div>
            <div class="text-left bg-gray-50 p-4 rounded-lg border border-gray-200 mt-4 text-sm space-y-3">
              <p><strong class="text-gray-700">Cliente:</strong> {{ activo.nombre_cliente }}</p>
              <p><strong class="text-gray-700">Punto:</strong> {{ activo.nombre_punto }}</p>
              <div class="pt-2 border-t border-gray-200">
                <div class="flex justify-between items-center mb-1">
                  <label class="block text-xs font-bold text-gray-600 uppercase">Servicio Realizado <span class="text-red-500">*</span></label>
                  @if (bloqueado) {
                    <span class="inline-block bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded-full border border-amber-200"><i class="fas fa-lock text-[9px] mr-1"></i> Cambio registrado (1/1)</span>
                  }
                </div>
                <app-select-buscar [opciones]="d.tiposMantenimiento" valor="id_tipo_mantenimiento" texto="nombre_completo" [permitirVacio]="false" [limpiable]="false"
                  [disabled]="bloqueado" [(ngModel)]="tipoFinal" (cambio)="cambiarTipo()" />
              </div>
              @if (requiereJustificacion) {
                <div class="pt-3 transition-all duration-300">
                  <div class="bg-red-50 p-3 rounded-lg border border-red-200 shadow-inner">
                    <p class="text-xs text-red-600 font-bold mb-2"><i class="fas fa-exclamation-triangle"></i> Tiempo excedido para este tipo de servicio. Por favor justifica el retraso:</p>
                    <textarea [(ngModel)]="justificacion" rows="2" class="w-full bg-white border border-red-300 rounded-lg p-2 text-sm text-gray-800 outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500" placeholder="Ej: No abrían la puerta, fallas adicionales encontradas..."></textarea>
                  </div>
                </div>
              }
            </div>
          </div>
          <div class="fixed bottom-0 left-0 w-full bg-white shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.1)] p-4 z-40 border-t border-gray-200">
            <button type="button" (click)="finalizar()" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-4 rounded-xl shadow-lg transform active:scale-95 transition text-lg flex items-center justify-center gap-2"><i class="fas fa-flag-checkered"></i> FINALIZAR SERVICIO</button>
          </div>
        } @else {
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 space-y-4">
            <div class="flex items-center gap-2 border-b pb-2"><i class="fas fa-play-circle text-green-500 text-lg"></i><h2 class="font-bold text-gray-700 text-sm uppercase">Nuevo Servicio</h2></div>
            <div>
              <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Cliente <span class="text-red-500">*</span></label>
              <app-select-buscar [opciones]="d.clientes" valor="id_cliente" texto="nombre_cliente" placeholder="- Seleccione Cliente -" [(ngModel)]="idCliente" (cambio)="cambioCliente()" />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Punto Visitado <span class="text-red-500">*</span></label>
              <app-select-buscar [opciones]="puntosFiltrados" valor="id_punto" texto="nombre_punto" placeholder="- Seleccione el Punto -" [(ngModel)]="idPunto" (cambio)="cambioPunto()" />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Tipo de Mantenimiento <span class="text-red-500">*</span></label>
              <app-select-buscar [opciones]="d.tiposMantenimiento" valor="id_tipo_mantenimiento" texto="nombre_completo" placeholder="- Seleccione Tipo -" [(ngModel)]="idTipo" />
            </div>
          </div>
          <div class="fixed bottom-0 left-0 w-full bg-white shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.1)] p-4 z-40 border-t border-gray-200">
            <button type="button" (click)="iniciar()" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-4 rounded-xl shadow-lg transform active:scale-95 transition text-lg flex items-center justify-center gap-2"><i class="fas fa-play"></i> INICIAR CRONÓMETRO</button>
          </div>
        }
      </div>
    }
  `
})
export class CronometroCrear implements OnInit, OnDestroy {
  private api = inject(Api);
  private router = inject(Router);
  private location = inject(Location);
  d: any = null;
  error = '';
  activo: any = null;
  tiempo = '00:00:00';
  tipoFinal: any = null;
  bloqueado = false;
  requiereJustificacion = false;
  justificacion = '';
  idCliente: any = null;
  idPunto: any = null;
  idTipo: any = null;
  puntosFiltrados: any[] = [];
  private timer: any;

  async ngOnInit() {
    await this.cargar();
  }
  ngOnDestroy() {
    clearInterval(this.timer);
  }
  volver() {
    this.location.back();
  }
  async cargar() {
    clearInterval(this.timer);
    const r: any = await this.api.get('cronometroCrear');
    if (r.error) {
      alert(r.error);
      this.error = r.error;
      return;
    }
    this.d = r;
    this.activo = r.servicioActivo;
    this.puntosFiltrados = r.puntos || [];
    if (this.activo) {
      this.tipoFinal = this.activo.id_tipo_mantenimiento;
      this.bloqueado = Number(this.activo.servicio_modificado) === 1;
      this.tiempo = reloj(this.activo.hora_inicio);
      this.timer = setInterval(() => {
        this.tiempo = reloj(this.activo.hora_inicio);
        if (this.tiempo.endsWith(':00')) this.evaluar();
      }, 1000);
      this.evaluar();
    }
  }
  /** Muestra la justificación cuando el tiempo supera la meta del tipo seleccionado. */
  evaluar() {
    const meta = this.d.catalogoTiempos?.[this.tipoFinal] || 60;
    const min = Math.floor((Date.now() - fechaJs(this.activo.hora_inicio)) / 60000);
    this.requiereJustificacion = min > meta;
    if (!this.requiereJustificacion) this.justificacion = '';
  }
  async cambiarTipo() {
    this.evaluar();
    const r: any = await this.api.postForm('cronometroCrear', 'actualizarTipoVivo', { id_monitoreo: this.activo.id_monitoreo, id_tipo_mantenimiento: this.tipoFinal });
    if (r.status === 'success') {
      alert('ℹ️ ' + r.message);
      this.bloqueado = true;
    } else if (r.status === 'limit_reached') {
      alert('⚠️ ' + r.message);
      this.bloqueado = true;
    }
  }
  cambioCliente() {
    this.puntosFiltrados = (this.d.puntos || []).filter((p: any) => !this.idCliente || p.id_cliente == this.idCliente);
    this.idPunto = null;
  }
  cambioPunto() {
    if (this.idPunto && !this.idCliente) {
      const p = (this.d.puntos || []).find((x: any) => x.id_punto == this.idPunto);
      if (p?.id_cliente) this.idCliente = p.id_cliente;
    }
  }
  async iniciar() {
    if (!this.idCliente || !this.idPunto || !this.idTipo) {
      alert('⚠️ Por favor completa todos los campos para iniciar.');
      return;
    }
    if (!confirm('¿Estás seguro de iniciar el servicio ahora mismo?')) return;
    const r: any = await this.api.postForm('cronometroCrear', 'iniciar', { id_cliente: this.idCliente, id_punto: this.idPunto, id_tipo_mantenimiento: this.idTipo });
    if (r.status !== 'success') {
      alert(r.message);
      return;
    }
    this.cargar();
  }
  async finalizar() {
    if (this.requiereJustificacion && !this.justificacion.trim()) {
      alert('⚠️ El tiempo real supera el estimado para este servicio. Por favor, escribe la justificación del retraso.');
      return;
    }
    if (!confirm('¿Terminaste el servicio? Esto detendrá el cronómetro permanentemente.')) return;
    const r: any = await this.api.postForm('cronometroCrear', 'finalizar', {
      id_monitoreo: this.activo.id_monitoreo,
      hora_inicio_real: this.activo.hora_inicio,
      id_tipo_mantenimiento_inicial: this.activo.id_tipo_mantenimiento,
      id_tipo_mantenimiento_final: this.tipoFinal,
      justificacion_retraso: this.requiereJustificacion ? this.justificacion : ''
    });
    alert(r.message);
    if (r.status === 'success') this.router.navigate(['/cronometroHistorial']);
  }
}

// =====================================================================================
// HISTORIAL — TÉCNICO (cronometroHistorialVista.php)
// =====================================================================================
@Component({
  selector: 'app-cronometro-historial',
  imports: [RouterLink],
  template: `
    <div class="bg-blue-800 text-white p-4 shadow-md sticky top-0 z-40 flex items-center justify-between gap-3 -mx-4 md:mx-0 md:rounded-lg">
      <div class="flex items-center gap-3">
        <button (click)="volver()" class="text-white bg-blue-700 hover:bg-blue-600 p-2 rounded-full w-10 h-10 flex items-center justify-center transition"><i class="fas fa-arrow-left"></i></button>
        <div><h1 class="font-bold text-lg leading-tight">Mis Servicios</h1><p class="text-blue-200 text-xs">Historial de tiempos registrados</p></div>
      </div>
      <a routerLink="/cronometroCrear" class="bg-green-500 hover:bg-green-600 text-white pl-3 pr-4 py-2 rounded-full flex items-center gap-2 transition shadow font-bold text-sm" title="Iniciar Nuevo Servicio"><i class="fas fa-plus"></i><span>Nuevo</span></a>
    </div>
    <div class="max-w-lg mx-auto p-3 mt-2 space-y-4">
      @if (error) {
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded">{{ error }}</div>
      } @else if (cargado && !lista.length) {
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center mt-10">
          <i class="fas fa-stopwatch text-gray-300 text-5xl mb-3"></i>
          <h2 class="text-gray-500 font-bold text-lg">Sin servicios</h2>
          <p class="text-gray-400 text-sm mt-1">Aún no has registrado tiempos de servicio.</p>
        </div>
      }
      @for (s of lista; track s.id_monitoreo) {
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden" [class]="s.estado_actual === 'En Progreso' ? 'ring-1 ring-amber-200' : 'ring-1 ring-gray-100'">
          <div class="bg-gray-50 px-4 py-2.5 border-b border-gray-100 flex justify-between items-center">
            <span class="text-xs font-bold text-gray-500"><i class="far fa-calendar-alt mr-1 text-blue-600"></i> {{ dmy(s.fecha_servicio) }}</span>
            <span class="px-2.5 py-1 rounded-full border text-[10px] font-bold uppercase flex items-center gap-1" [class]="s.estado_actual === 'En Progreso' ? 'bg-amber-100 text-amber-700 border-amber-200' : 'bg-gray-100 text-gray-600 border-gray-200'">
              <i class="fas" [class]="s.estado_actual === 'En Progreso' ? 'fa-spinner fa-spin' : 'fa-check-circle'"></i> {{ s.estado_actual }}
            </span>
          </div>
          <div class="p-4 space-y-3">
            <div>
              <span class="block text-[10px] font-bold text-blue-600 uppercase leading-none">{{ s.nombre_cliente }}</span>
              <h3 class="font-bold text-gray-800 text-sm mt-1 flex items-start gap-1.5"><i class="fas fa-map-marker-alt text-red-500 mt-0.5"></i><span>{{ s.nombre_punto }}</span></h3>
            </div>
            <div class="bg-blue-50 p-2 rounded-lg border border-blue-100 flex items-center gap-2"><i class="fas fa-tools text-blue-500"></i><span class="text-xs font-bold text-blue-800 uppercase">{{ s.tipo_mantenimiento }}</span></div>
            <div class="grid grid-cols-2 gap-2 text-xs">
              <div class="bg-gray-50 p-2.5 rounded-lg border border-gray-100"><span class="block text-gray-400 uppercase font-bold text-[9px]">Inicio</span><span class="text-gray-700 font-semibold"><i class="far fa-clock text-gray-400 mr-1"></i> {{ hora12(s.hora_inicio) }}</span></div>
              <div class="bg-gray-50 p-2.5 rounded-lg border border-gray-100">
                <span class="block text-gray-400 uppercase font-bold text-[9px]">Fin</span>
                <span class="text-gray-700 font-semibold">
                  @if (s.hora_fin) {
                    <i class="fas fa-flag-checkered text-gray-400 mr-1"></i> {{ hora12(s.hora_fin) }}
                  } @else {
                    <span class="text-amber-600 inline-flex items-center gap-1"><span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span></span> Corriendo...</span>
                  }
                </span>
              </div>
            </div>
            @if (s.estado_actual === 'Finalizado') {
              <div class="p-3 rounded-lg border border-gray-100 flex justify-between items-center" [class]="clases(s).bg">
                <div>
                  <span class="block text-gray-400 uppercase font-bold text-[9px] mb-0.5">Meta: {{ s.tiempo_estimado_minutos }} min</span>
                  <span class="text-sm font-bold" [class]="clases(s).txt">
                    @if (clases(s).icono) { <i class="fas mr-1" [class]="clases(s).icono"></i> }
                    Real: {{ s.duracion_real_minutos }} min
                  </span>
                </div>
                @if (!clases(s).aTiempo) {
                  <span class="text-[10px] font-bold text-red-500 uppercase bg-red-100 px-2.5 py-1 rounded-full">Retrasado</span>
                } @else {
                  <span class="text-[10px] font-bold text-green-600 uppercase bg-green-100 px-2.5 py-1 rounded-full">A tiempo</span>
                }
              </div>
            }
          </div>
        </div>
      }
    </div>
  `
})
export class CronometroHistorial implements OnInit {
  private api = inject(Api);
  private location = inject(Location);
  lista: any[] = [];
  cargado = false;
  error = '';
  dmy = dmy;
  hora12 = hora12;

  async ngOnInit() {
    const r: any = await this.api.get('cronometroHistorial');
    if (r.error) {
      alert(r.error);
      this.error = r.error;
      return;
    }
    this.lista = r.historialServicios || [];
    this.cargado = true;
  }
  volver() {
    this.location.back();
  }
  clases(s: any) {
    if (s.duracion_real_minutos === null || s.duracion_real_minutos === undefined) return { bg: 'bg-gray-50', txt: 'text-gray-700', icono: '', aTiempo: true };
    const ok = Number(s.duracion_real_minutos) <= Number(s.tiempo_estimado_minutos);
    return ok
      ? { bg: 'bg-green-50', txt: 'text-green-600', icono: 'fa-tachometer-alt', aTiempo: true }
      : { bg: 'bg-red-50', txt: 'text-red-500', icono: 'fa-exclamation-triangle', aTiempo: false };
  }
}

// =====================================================================================
// RADAR — SUPERVISOR (cronometroAdminVista.php)
// =====================================================================================
@Component({
  selector: 'app-cronometro-admin',
  imports: [FormsModule, RouterLink, SelectBuscar, DtArriba, DtAbajo],
  styles: [`.reloj-fuente { font-family: 'Courier New', Courier, monospace; }`],
  template: `
    <div class="p-4 md:p-6 max-w-full mx-auto space-y-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-xl shadow-sm border border-gray-100">
        <div>
          <h1 class="text-xl md:text-2xl font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-satellite-dish text-green-600"></i> Radar de Técnicos</h1>
          <p class="text-gray-500 text-xs md:text-sm mt-1">Monitoreo en tiempo real, auditoría de tiempos y servicios modificados.</p>
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
          <a routerLink="/cronometroReportes" class="bg-indigo-600 hover:bg-indigo-700 text-white p-2.5 px-4 rounded-lg shadow-sm flex items-center gap-2 text-sm font-bold transition"><i class="fas fa-chart-bar"></i> Ver Reportes y Gráficas</a>
          <button (click)="cargar()" class="bg-blue-50 text-blue-600 p-2.5 rounded-lg hover:bg-blue-100 border border-blue-200 shadow-sm flex items-center gap-2 text-sm font-bold transition" title="Actualizar Radar"><i class="fas fa-sync-alt"></i> <span class="hidden md:inline">Actualizar</span></button>
        </div>
      </div>
      <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
        <form (ngSubmit)="filtrar()" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4 items-end">
          <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha Inicio</label><input type="date" name="fi" [(ngModel)]="f.fecha_inicio" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2 text-sm outline-none focus:border-blue-500"></div>
          <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha Fin</label><input type="date" name="ff" [(ngModel)]="f.fecha_fin" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2 text-sm outline-none focus:border-blue-500"></div>
          <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1">Técnico</label><app-select-buscar name="tec" [opciones]="tecnicos" valor="id_tecnico" texto="nombre_tecnico" placeholder="- Todos los técnicos -" [(ngModel)]="f.id_tecnico" /></div>
          <div>
            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Estado</label>
            <select name="est" [(ngModel)]="f.estado_actual" class="w-full border-gray-300 rounded-lg p-2 bg-gray-50 text-sm outline-none focus:border-blue-500">
              <option value="">- Todos los estados -</option>
              <option value="En Progreso">En Progreso (En Vivo)</option>
              <option value="Finalizado">Finalizados</option>
            </select>
          </div>
          <div class="flex flex-wrap gap-2 justify-end mt-2"><button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm flex items-center gap-2 text-sm w-full md:w-auto justify-center"><i class="fas fa-filter"></i> Filtrar</button></div>
        </form>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-r from-green-500 to-green-700 rounded-xl p-4 text-white shadow-md flex items-center gap-3"><div class="bg-white/20 p-3 rounded-full animate-pulse"><i class="fas fa-motorcycle text-2xl"></i></div><div><p class="text-green-100 text-xs font-semibold uppercase">Técnicos Activos</p><h3 class="text-2xl font-bold">{{ r?.totalActivos ?? 0 }}</h3></div></div>
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-xl p-4 text-white shadow-md flex items-center gap-3"><div class="bg-white/20 p-3 rounded-full"><i class="fas fa-check-double text-2xl"></i></div><div><p class="text-blue-100 text-xs font-semibold uppercase">Servicios Terminados</p><h3 class="text-2xl font-bold">{{ r?.totalFinalizados ?? 0 }}</h3></div></div>
        <div class="bg-gradient-to-r from-amber-500 to-orange-600 rounded-xl p-4 text-white shadow-md flex items-center gap-3"><div class="bg-white/20 p-3 rounded-full"><i class="fas fa-exchange-alt text-2xl"></i></div><div><p class="text-amber-100 text-xs font-semibold uppercase">Servicios Modificados</p><h3 class="text-2xl font-bold">{{ r?.totalModificados ?? 0 }}</h3></div></div>
        <div class="bg-gradient-to-r from-red-500 to-rose-700 rounded-xl p-4 text-white shadow-md flex items-center gap-3"><div class="bg-white/20 p-3 rounded-full"><i class="fas fa-stopwatch text-2xl"></i></div><div><p class="text-red-100 text-xs font-semibold uppercase">Retrasos (Fuera Meta)</p><h3 class="text-2xl font-bold">{{ r?.totalRetrasados ?? 0 }}</h3></div></div>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-2 overflow-x-auto">
          <app-dt-arriba [dt]="dt" />
          <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
              <tr>
                <th class="p-3">Estado / Inicio</th><th class="p-3">Técnico</th><th class="p-3">Cliente / Punto</th><th class="p-3">Servicio</th>
                <th class="p-3 text-center">Tiempo Meta</th><th class="p-3 text-center">T. Real / Cronómetro</th><th class="p-3 text-center">Alertas / Justificación</th>
              </tr>
            </thead>
            <tbody>
              @for (s of dt.pagina; track s.id_monitoreo) {
                <tr class="border-b hover:bg-gray-50 transition">
                  <td class="p-3">
                    @if (s.estado_actual === 'En Progreso') {
                      <span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded-full border border-green-200 mb-1 inline-block animate-pulse"><i class="fas fa-circle text-[8px] mr-1"></i> EN VIVO</span>
                    } @else {
                      <span class="bg-gray-100 text-gray-700 text-xs font-bold px-2 py-1 rounded-full border border-gray-200 mb-1 inline-block">FINALIZADO</span>
                    }
                    <br><span class="text-xs font-mono text-gray-500">{{ fechaHora(s.hora_inicio) }}</span>
                  </td>
                  <td class="p-3 font-bold text-blue-700">{{ s.nombre_tecnico }}</td>
                  <td class="p-3"><span class="block text-xs font-bold text-gray-800 truncate w-48">{{ s.nombre_punto || 'N/A' }}</span><span class="block text-[11px] text-gray-400 truncate w-48">{{ s.nombre_cliente || 'Sin cliente' }}</span></td>
                  <td class="p-3"><span class="text-xs font-bold uppercase bg-blue-50 text-blue-800 px-2 py-1 rounded">{{ s.tipo_mantenimiento }}</span></td>
                  <td class="p-3 text-center font-bold text-gray-600">{{ s.tiempo_estimado_minutos ?? 60 }} min</td>
                  <td class="p-3 text-center">
                    @if (s.estado_actual === 'En Progreso') {
                      <span class="text-lg font-bold reloj-fuente bg-gray-900 px-2 py-1 rounded shadow-inner" [class]="excedido(s) ? 'text-red-500 animate-pulse' : 'text-green-600'">{{ relojDe(s) }}</span>
                    } @else {
                      <span class="text-sm font-bold" [class]="+s.duracion_real_minutos > +s.tiempo_estimado_minutos ? 'text-red-600' : 'text-gray-800'">{{ s.duracion_real_minutos }} min</span>
                    }
                  </td>
                  <td class="p-3 text-center">
                    @if (+s.servicio_modificado === 1 || s.justificacion_retraso) {
                      <button type="button" (click)="modal = s" class="bg-red-50 hover:bg-red-100 text-red-600 p-2 rounded-lg border border-red-200 transition font-bold text-xs inline-flex items-center gap-1"><i class="fas fa-exclamation-triangle"></i> Ver Alertas</button>
                    } @else {
                      <span class="text-gray-300 text-xs"><i class="fas fa-check"></i> Normal</span>
                    }
                  </td>
                </tr>
              }
            </tbody>
          </table>
          <app-dt-abajo [dt]="dt" />
        </div>
      </div>
    </div>
    @if (modal) {
      <div class="fixed inset-0 bg-black/80 z-[100] flex justify-center items-center p-4" (click)="modal = null">
        <div class="relative w-full max-w-lg bg-white rounded-xl shadow-2xl overflow-hidden" (click)="$event.stopPropagation()">
          <div class="bg-red-600 text-white p-4 flex justify-between items-center"><h3 class="font-bold text-base"><i class="fas fa-exclamation-triangle mr-2"></i> Detalles de Alerta</h3><button type="button" (click)="modal = null" class="text-white hover:text-red-200 text-2xl leading-none">&times;</button></div>
          <div class="p-5 space-y-4">
            @if (+modal.servicio_modificado === 1) {
              <div class="bg-orange-50 border border-orange-200 p-3 rounded-lg"><h4 class="text-xs font-bold text-orange-800 uppercase mb-1">Servicio Modificado</h4><p class="text-sm text-orange-900">El técnico inició con un servicio distinto al que reportó al finalizar o lo cambió en vivo.</p></div>
            }
            @if (modal.justificacion_retraso) {
              <div><h4 class="text-xs font-bold text-gray-500 uppercase mb-1">Justificación por Retraso:</h4><div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm text-gray-700 italic font-medium shadow-inner">"{{ modal.justificacion_retraso }}"</div></div>
            }
            <div class="pt-4 text-right"><button type="button" (click)="modal = null" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-lg text-sm transition">Cerrar</button></div>
          </div>
        </div>
      </div>
    }
  `
})
export class CronometroAdmin implements OnInit, OnDestroy {
  private api = inject(Api);
  private ruta = inject(ActivatedRoute);
  private router = inject(Router);
  r: any = null;
  f: any = { fecha_inicio: '', fecha_fin: '', id_tecnico: '', estado_actual: '' };
  tecnicos: any[] = [];
  dt = new TablaDatos<any>([], 25);
  modal: any = null;
  ahora = Date.now();
  private timer: any;

  ngOnInit() {
    const q = this.ruta.snapshot.queryParamMap;
    ['fecha_inicio', 'fecha_fin', 'id_tecnico', 'estado_actual'].forEach((k) => q.get(k) !== null && (this.f[k] = q.get(k)));
    this.cargar();
    this.timer = setInterval(() => (this.ahora = Date.now()), 1000);
  }
  ngOnDestroy() {
    clearInterval(this.timer);
  }
  async cargar() {
    const p: any = {};
    Object.entries(this.f).forEach(([k, v]) => v !== '' && v !== null && (p[k] = v));
    const r: any = await this.api.get('cronometroAdmin', 'index', p);
    if (r.error) {
      alert(r.error);
      return;
    }
    this.r = r;
    this.f = { ...r.filtros };
    this.tecnicos = r.tecnicos || [];
    this.dt.set(r.servicios || []);
  }
  filtrar() {
    const q: any = {};
    Object.entries(this.f).forEach(([k, v]) => (q[k] = v ?? ''));
    this.router.navigate([], { queryParams: q, replaceUrl: true });
    this.cargar();
  }
  relojDe(s: any) {
    void this.ahora;
    return reloj(s.hora_inicio);
  }
  excedido(s: any) {
    return Math.floor((this.ahora - fechaJs(s.hora_inicio)) / 60000) > Number(s.tiempo_estimado_minutos ?? 60);
  }
  fechaHora(f: any) {
    const d = new Date(fechaJs(f));
    return isNaN(d.getTime()) ? '' : `${dos(d.getDate())}/${dos(d.getMonth() + 1)}/${d.getFullYear()} ${dos(d.getHours())}:${dos(d.getMinutes())}`;
  }
}

// =====================================================================================
// REPORTES DE RENDIMIENTO (cronometroReportesVista.php)
// =====================================================================================
@Component({
  selector: 'app-cronometro-reportes',
  imports: [FormsModule, RouterLink],
  template: `
    <div class="p-4 md:p-6 max-w-full mx-auto space-y-6">
      <div class="flex justify-between items-center bg-white p-5 rounded-xl shadow-sm border border-gray-100">
        <div>
          <h1 class="text-xl md:text-2xl font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-chart-bar text-indigo-600"></i> Rendimiento y Cumplimiento de Flota</h1>
          <p class="text-gray-500 text-xs md:text-sm mt-1">Comparativa de servicios completados frente a la meta del periodo calculada.</p>
        </div>
        <a routerLink="/cronometroAdmin" class="bg-gray-100 text-gray-700 hover:bg-gray-200 p-2.5 px-4 rounded-lg border border-gray-300 shadow-sm flex items-center gap-2 text-sm font-bold transition"><i class="fas fa-arrow-left"></i> Volver al Radar</a>
      </div>
      <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
        <form (ngSubmit)="generar()" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">
          <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha Inicio</label><input type="date" name="fi" [(ngModel)]="fi" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2 text-sm outline-none focus:border-indigo-500"></div>
          <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha Fin</label><input type="date" name="ff" [(ngModel)]="ff" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2 text-sm outline-none focus:border-indigo-500"></div>
          <div>
            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Meta de Servicios (por técnico)</label>
            <input type="number" name="meta" [(ngModel)]="meta" min="1" step="1" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2 text-sm outline-none focus:border-indigo-500 font-bold text-indigo-700">
            <p class="text-[10px] text-gray-400 mt-1">Sugerida automáticamente según el rango (6 L-V / 3 Sáb). Puedes editarla.</p>
          </div>
          <div class="flex flex-wrap gap-2 sm:col-span-2 md:col-span-4 justify-end border-t pt-4 mt-2 border-gray-100">
            <button type="button" (click)="preset('hoy')" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2 px-3 rounded-lg border border-gray-300 transition">Hoy</button>
            <button type="button" (click)="preset('semana')" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2 px-3 rounded-lg border border-gray-300 transition">Esta Semana</button>
            <button type="button" (click)="preset('mes')" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2 px-3 rounded-lg border border-gray-300 transition">Este Mes</button>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm flex items-center gap-2 text-sm ml-2"><i class="fas fa-filter"></i> Generar</button>
            <button type="button" (click)="pdf()" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm flex items-center gap-2 text-sm"><i class="fas fa-file-pdf"></i> PDF</button>
            <button type="button" (click)="excel()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm flex items-center gap-2 text-sm"><i class="fas fa-file-excel"></i> Excel</button>
          </div>
        </form>
      </div>
      @if (r) {
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex items-center gap-3"><div class="bg-indigo-50 text-indigo-600 p-3 rounded-full"><i class="fas fa-bullseye text-2xl"></i></div><div><p class="text-gray-400 text-xs font-semibold uppercase">Meta Individual Periodo</p><h3 class="text-2xl font-bold text-gray-800">{{ r.metaTotalServicios }} <span class="text-xs font-normal text-gray-500">servicios</span></h3></div></div>
          <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex items-center gap-3"><div class="bg-emerald-50 text-emerald-600 p-3 rounded-full"><i class="fas fa-check-circle text-2xl"></i></div><div><p class="text-gray-400 text-xs font-semibold uppercase">Total Servicios Flota</p><h3 class="text-2xl font-bold text-gray-800">{{ r.totalGeneralServicios }}</h3></div></div>
          <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex items-center gap-3"><div class="bg-blue-50 text-blue-600 p-3 rounded-full"><i class="fas fa-user-check text-2xl"></i></div><div><p class="text-gray-400 text-xs font-semibold uppercase">Técnicos Cumplieron</p><h3 class="text-2xl font-bold text-gray-800">{{ r.tecnicosCumplieron }} / {{ r.rendimientoRaw.length }}</h3></div></div>
          <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex items-center gap-3"><div class="bg-amber-50 text-amber-600 p-3 rounded-full"><i class="fas fa-calendar-day text-2xl"></i></div><div><p class="text-gray-400 text-xs font-semibold uppercase">Meta Diaria Básica</p><h3 class="text-2xl font-bold text-gray-800">6 <span class="text-xs font-normal text-gray-500">L-V / 3 Sáb</span></h3></div></div>
        </div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 space-y-4">
          <div class="flex justify-between items-center border-b pb-3"><h2 class="font-bold text-gray-800 text-base">Comparativa de Cumplimiento por Técnico</h2><span class="text-xs font-semibold text-gray-500">Línea Punteada: Meta del Periodo ({{ r.metaTotalServicios }})</span></div>
          <div class="relative w-full h-80 md:h-96"><canvas #grafica></canvas></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="p-4 border-b border-gray-100"><h2 class="font-bold text-gray-800 text-base">Detalle Cuantitativo</h2></div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
              <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                  <th class="p-3">Técnico</th>
                  @for (t of r.tipos; track t.id_tipo_mantenimiento) { <th class="p-3 text-center">{{ t.nombre_completo }}</th> }
                  <th class="p-3 text-center">Finalizados</th><th class="p-3 text-center">En Progreso</th><th class="p-3 text-center">Meta Periodo</th><th class="p-3 text-center">% Cumplimiento</th><th class="p-3 text-center">Estado</th>
                </tr>
              </thead>
              <tbody>
                @for (x of r.rendimientoRaw; track x.id_tecnico) {
                  <tr class="border-b hover:bg-gray-50">
                    <td class="p-3 font-bold text-gray-800">{{ x.nombre_tecnico }}</td>
                    @for (t of r.tipos; track t.id_tipo_mantenimiento) { <td class="p-3 text-center font-semibold text-blue-600">{{ +(x['tipo_' + t.id_tipo_mantenimiento] || 0) }}</td> }
                    <td class="p-3 text-center font-semibold text-emerald-600">{{ +x.total_finalizados }}</td>
                    <td class="p-3 text-center text-amber-600 font-semibold">{{ +(x.total_en_progreso || 0) }}</td>
                    <td class="p-3 text-center text-gray-600 font-medium">{{ r.metaTotalServicios }}</td>
                    <td class="p-3 text-center font-bold text-gray-800">{{ pct(x) }}%</td>
                    <td class="p-3 text-center"><span class="px-2.5 py-1 rounded-full border text-xs font-bold uppercase" [class]="estado(x).badge">{{ estado(x).txt }}</span></td>
                  </tr>
                }
              </tbody>
            </table>
          </div>
        </div>
      }
    </div>
  `
})
export class CronometroReportes implements OnInit, OnDestroy {
  private api = inject(Api);
  private ruta = inject(ActivatedRoute);
  private router = inject(Router);
  @ViewChild('grafica') grafica?: ElementRef<HTMLCanvasElement>;
  r: any = null;
  fi = '';
  ff = '';
  meta: any = '';
  private chart?: Chart;

  ngOnInit() {
    const q = this.ruta.snapshot.queryParamMap;
    this.fi = q.get('fecha_inicio') || '';
    this.ff = q.get('fecha_fin') || '';
    this.meta = q.get('meta_total') || '';
    this.cargar();
  }
  ngOnDestroy() {
    this.chart?.destroy();
  }
  private params() {
    const p: any = {};
    if (this.fi) p.fecha_inicio = this.fi;
    if (this.ff) p.fecha_fin = this.ff;
    if (this.meta !== '' && this.meta !== null) p.meta_total = this.meta;
    return p;
  }
  async cargar() {
    const r: any = await this.api.get('cronometroReportes', 'index', this.params());
    if (r.error) {
      alert(r.error);
      return;
    }
    this.r = r;
    this.fi = r.fechaInicio;
    this.ff = r.fechaFin;
    this.meta = r.metaTotalServicios;
    setTimeout(() => this.dibujar());
  }
  generar() {
    this.router.navigate([], { queryParams: this.params(), replaceUrl: true });
    this.cargar();
  }
  pct(x: any) {
    const m = Number(this.r.metaTotalServicios);
    return m > 0 ? Math.round((Number(x.total_finalizados) / m) * 1000) / 10 : 0;
  }
  estado(x: any) {
    const p = this.pct(x);
    if (p >= 100) return { badge: 'bg-green-100 text-green-800 border-green-200', txt: 'Meta Cumplida' };
    if (p >= 70) return { badge: 'bg-amber-100 text-amber-800 border-amber-200', txt: 'En Progreso / Aceptable' };
    return { badge: 'bg-red-100 text-red-800 border-red-200', txt: 'Bajo Rendimiento' };
  }
  preset(tipo: string) {
    const hoy = new Date();
    let inicio = new Date();
    const fin = new Date();
    if (tipo === 'semana') {
      const day = hoy.getDay();
      inicio = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() - day + (day === 0 ? -6 : 1));
    } else if (tipo === 'mes') {
      inicio = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    }
    this.fi = ymd(inicio);
    this.ff = ymd(fin);
  }
  pdf() {
    this.api.abrir('cronometroReportes', 'pdf', { fecha_inicio: this.fi, fecha_fin: this.ff, meta_total: this.meta });
  }
  excel() {
    window.location.href = this.api.url('cronometroReportes', 'excel', { fecha_inicio: this.fi, fecha_fin: this.ff, meta_total: this.meta });
  }
  private dibujar() {
    this.chart?.destroy();
    if (!this.grafica) return;
    const metaRango = Number(this.r.metaTotalServicios);
    this.chart = new Chart(this.grafica.nativeElement, {
      type: 'bar',
      data: { labels: this.r.tecnicosNombres, datasets: [{ label: 'Servicios Finalizados', data: this.r.serviciosRealizados, backgroundColor: this.r.coloresBarras, borderRadius: 6, maxBarThickness: 45 }] },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: (c: any) => `Servicios: ${c.raw} (${metaRango > 0 ? ((c.raw / metaRango) * 100).toFixed(1) : 0}% de la meta)` } }
        },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: 'Cantidad de Servicios' } }, x: { ticks: { font: { weight: 'bold' } } } }
      }
    });
  }
}
