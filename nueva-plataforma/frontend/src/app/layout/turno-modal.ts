import { Component, ElementRef, HostListener, Injectable, ViewChild, effect, inject, signal, untracked } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Api } from '../core/api';

export interface TurnoDia {
  id_turno: number;
  fecha: string;
  fecha_texto: string;
  hora_entrada: string;
  novedad: string;
  latitud: any;
  longitud: any;
}

/** Estado compartido del módulo "Marcar Entrada" (js/turno/turno.js). */
@Injectable({ providedIn: 'root' })
export class TurnoService {
  private api = inject(Api);
  readonly abierto = signal(false);
  readonly turnoDelDia = signal<TurnoDia | null>(null);

  abrirModal() {
    this.abierto.set(true);
  }

  cerrarModal() {
    this.abierto.set(false);
  }

  async consultar(fecha: string): Promise<TurnoDia | null> {
    try {
      const data = await this.api.get('turno', 'estadoHoy', { fecha });
      if (data.ok) return data.turno;
    } catch (e) {
      console.error('[Turno] No se pudo consultar la marcación:', e);
    }
    return null;
  }

  async cargarHoy() {
    this.turnoDelDia.set(await this.consultar(TurnoService.hoy()));
  }

  static dos(n: number) {
    return String(n).padStart(2, '0');
  }

  static hoy() {
    const d = new Date();
    return d.getFullYear() + '-' + TurnoService.dos(d.getMonth() + 1) + '-' + TurnoService.dos(d.getDate());
  }

  static ahoraHora() {
    const d = new Date();
    return TurnoService.dos(d.getHours()) + ':' + TurnoService.dos(d.getMinutes());
  }

  /** 1300 -> 13:00 | 930 -> 09:30 | 13 -> 13:00 | 1 -> 01:00 */
  static horaAMilitar(valor: string): string {
    const d = String(valor || '').replace(/\D/g, '').substring(0, 4);
    if (d.length === 4) {
      let h = parseInt(d.substring(0, 2), 10);
      let m = parseInt(d.substring(2, 4), 10);
      if (h > 23) h = 23;
      if (m > 59) m = 59;
      return TurnoService.dos(h) + ':' + TurnoService.dos(m);
    }
    if (d.length === 3) {
      const h = '0' + d.substring(0, 1);
      let m = d.substring(1, 3);
      if (parseInt(m, 10) > 59) m = '59';
      return h + ':' + m;
    }
    if (d.length === 2) return d + ':00';
    if (d.length === 1) return '0' + d + ':00';
    return '';
  }
}

/** Port de app/views/turno/partials/modalTurno.php */
@Component({
  selector: 'app-turno-modal',
  imports: [FormsModule],
  template: `
    @if (svc.abierto()) {
      <div class="fixed inset-0 bg-black bg-opacity-60 z-[10000] flex justify-center items-center p-4 overflow-y-auto" (click)="fondo($event)" role="dialog" aria-modal="true">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden my-8" (click)="$event.stopPropagation()">
          <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-5 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3 text-white">
              <div class="bg-white/20 p-2 rounded-full"><i class="fas fa-user-clock"></i></div>
              <div>
                <h3 class="font-bold leading-tight">Marcar entrada</h3>
                <p class="text-emerald-100 text-[11px]">Registra el día y la hora en que entraste</p>
              </div>
            </div>
            <button type="button" (click)="svc.cerrarModal()" class="text-white/80 hover:text-white bg-white/10 hover:bg-white/20 w-9 h-9 rounded-full transition">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <form class="p-5 space-y-4" novalidate (submit)="guardar($event)">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Día</label>
                <input type="date" name="fecha" required [max]="hoy" [(ngModel)]="fecha" (ngModelChange)="cambioFecha()"
                  class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm outline-none focus:border-emerald-500" />
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Hora de entrada <span class="text-emerald-600 font-normal normal-case">(24 h)</span></label>
                <input #horaInput type="text" name="hora_entrada" required inputmode="numeric" pattern="[0-9]*" maxlength="5" autocomplete="off" placeholder="1300"
                  [ngModel]="hora" (input)="inputHora($event)" (blur)="hora = militar(hora)"
                  class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-center text-lg font-bold tracking-widest outline-none focus:border-emerald-500" />
                <p class="text-[10px] text-gray-400 mt-1 text-center">Escribe solo los números: <b>1300</b> = 13:00</p>
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Novedad <span class="text-gray-400 font-normal normal-case">(opcional)</span></label>
              <textarea name="novedad" rows="3" maxlength="1000" [(ngModel)]="novedad" placeholder="Ej: entré 20 minutos tarde porque tuve una novedad en la ruta..."
                class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm outline-none focus:border-emerald-500 resize-none"></textarea>
            </div>

            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 flex items-start gap-2">
              <i class="fas fa-map-marker-alt text-emerald-600 mt-0.5"></i>
              <div class="flex-1">
                <p class="text-[11px] font-bold text-gray-600 uppercase">Ubicación</p>
                <p class="text-xs text-gray-500" [innerHTML]="geoEstado"></p>
              </div>
              <button type="button" (click)="capturarUbicacion(true)" class="text-[11px] text-emerald-700 hover:text-emerald-800 font-bold underline">Reintentar</button>
            </div>

            @if (alerta) {
              <div class="rounded-lg px-3 py-2 text-sm" [class]="alertaError ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-emerald-50 border border-emerald-200 text-emerald-700'">
                <i class="fas mr-1" [class]="alertaError ? 'fa-exclamation-triangle' : 'fa-check-circle'"></i>{{ alerta }}
              </div>
            }

            <div class="flex gap-2 pt-1">
              <button type="button" (click)="svc.cerrarModal()" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 rounded-lg transition text-sm">Cancelar</button>
              <button type="submit" [disabled]="guardando" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 rounded-lg transition text-sm shadow flex items-center justify-center gap-2">
                @if (guardando) { <i class="fas fa-spinner fa-spin"></i> Guardando... } @else { <i class="fas fa-check"></i> Guardar entrada }
              </button>
            </div>
          </form>
        </div>
      </div>
    }
  `
})
export class TurnoModal {
  svc = inject(TurnoService);
  private api = inject(Api);
  @ViewChild('horaInput') horaInput?: ElementRef<HTMLInputElement>;

  hoy = TurnoService.hoy();
  fecha = this.hoy;
  hora = '';
  novedad = '';
  latitud = '';
  longitud = '';
  geoEstado = 'Obteniendo ubicación...';
  alerta = '';
  alertaError = false;
  guardando = false;
  constructor() {
    // Inicializar el formulario cada vez que se abre el modal
    effect(() => {
      const ab = this.svc.abierto();
      untracked(() => {
        if (ab) this.alAbrir();
        else document.body.style.overflow = '';
      });
    });
  }

  militar(v: string) {
    return TurnoService.horaAMilitar(v);
  }

  private alAbrir() {
    this.alerta = '';
    this.hoy = TurnoService.hoy();
    this.fecha = this.hoy;
    this.hora = TurnoService.ahoraHora();
    this.novedad = '';
    const t = this.svc.turnoDelDia();
    if (t && t.fecha === this.hoy) {
      this.hora = t.hora_entrada;
      this.novedad = t.novedad || '';
    }
    this.hora = this.militar(this.hora);
    document.body.style.overflow = 'hidden';
    setTimeout(() => {
      const el = this.horaInput?.nativeElement;
      if (el) {
        el.focus();
        el.select();
      }
    }, 150);
    this.capturarUbicacion(true);
  }

  inputHora(e: Event) {
    const el = e.target as HTMLInputElement;
    const d = el.value.replace(/\D/g, '').substring(0, 4);
    this.hora = d.length === 4 ? this.militar(d) : d;
    el.value = this.hora;
  }

  fondo(e: MouseEvent) {
    if (e.target === e.currentTarget) this.svc.cerrarModal();
  }

  @HostListener('document:keydown.escape')
  esc() {
    this.svc.cerrarModal();
  }

  capturarUbicacion(forzar: boolean) {
    if (forzar) {
      this.latitud = '';
      this.longitud = '';
    }
    if (!navigator.geolocation) {
      this.geoEstado = '<span class="text-amber-600">Este dispositivo no permite ubicación. Puedes guardar igual.</span>';
      return;
    }
    this.geoEstado = 'Obteniendo ubicación...';
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        this.latitud = pos.coords.latitude.toFixed(8);
        this.longitud = pos.coords.longitude.toFixed(8);
        this.geoEstado =
          '<span class="text-emerald-700 font-semibold"><i class="fas fa-map-marker-alt"></i> ' +
          pos.coords.latitude.toFixed(5) + ', ' + pos.coords.longitude.toFixed(5) + '</span>';
      },
      () => {
        this.geoEstado = '<span class="text-amber-600">No se pudo obtener la ubicación (permiso negado). Puedes guardar igual.</span>';
      },
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 }
    );
  }

  async cambioFecha() {
    const registro = await this.svc.consultar(this.fecha);
    if (registro) {
      this.hora = this.militar(registro.hora_entrada);
      this.novedad = registro.novedad || '';
      this.mostrar('Ya tenías una marcación el ' + registro.fecha_texto + ' a las ' + registro.hora_entrada + '. Puedes corregirla.', false);
    } else {
      const p = this.fecha.split('-');
      this.mostrar('Sin marcación registrada para el ' + (p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : this.fecha) + '.', false);
    }
  }

  private mostrar(m: string, error: boolean) {
    this.alerta = m;
    this.alertaError = error;
  }

  async guardar(e: Event) {
    e.preventDefault();
    if (this.guardando) return;
    const digitos = (this.hora || '').replace(/\D/g, '');
    if (!this.fecha || digitos.length < 4) {
      this.mostrar('Escribe la hora completa: 4 dígitos (ej: 1300 = 13:00).', true);
      return;
    }
    const hora = this.militar(digitos);
    if (!/^([01]\d|2[0-3]):([0-5]\d)$/.test(hora)) {
      this.mostrar('La hora no es válida (usa 24 horas, ej: 07:41 o 19:30).', true);
      return;
    }
    if (this.fecha > this.hoy) {
      this.mostrar('No puedes registrar una fecha futura.', true);
      return;
    }
    this.guardando = true;
    try {
      const datos: Record<string, any> = { fecha: this.fecha, hora_entrada: hora, novedad: this.novedad.trim() };
      if (this.latitud) datos['latitud'] = this.latitud;
      if (this.longitud) datos['longitud'] = this.longitud;
      const data = await this.api.postForm('turno', 'guardar', datos);
      if (!data.ok) {
        this.mostrar(data.msj || 'No se pudo guardar la marcación.', true);
        return;
      }
      this.svc.turnoDelDia.set(data.turno);
      this.mostrar(data.msj, false);
      const f = this.fecha;
      setTimeout(() => {
        if (f === this.hoy) this.svc.cerrarModal();
      }, 1500);
    } catch (err) {
      console.error('[Turno] Error al guardar:', err);
      this.mostrar('Error de conexión. Revisa tu internet e intenta de nuevo.', true);
    } finally {
      this.guardando = false;
    }
  }
}
