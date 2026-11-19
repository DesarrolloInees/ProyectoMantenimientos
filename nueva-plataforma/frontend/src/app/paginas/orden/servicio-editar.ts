import { Component, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { Api } from '../../core/api';

const MAX_FOTOS = 10;
type TipoFoto = 'fotos_antes' | 'foto_remision' | 'fotos_despues';

/** armarRutaImagen(): las rutas en BD son relativas a app/ */
export function rutaImagenApp(rutaBD: string): string {
  let r = String(rutaBD || '').replace(/^\/+/, '');
  if (r.indexOf('app/') !== 0) r = 'app/' + r;
  return '/' + r;
}

/** Port de servicioEditarVista.php */
@Component({
  selector: 'app-servicio-editar',
  imports: [FormsModule],
  template: `
    @if (d) {
      <div class="w-full px-4 md:px-6">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
          <div>
            <h2 class="text-2xl font-bold text-gray-800"><i class="fas fa-edit text-blue-600 mr-2"></i> Editar Complementos del Servicio</h2>
            <p class="text-gray-500 text-sm">Completar información faltante para la Orden #{{ d.id_ordenes_servicio }}</p>
          </div>
          <button type="button" (click)="volver()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded transition"><i class="fas fa-arrow-left"></i> Volver</button>
        </div>

        <form (ngSubmit)="guardar()" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
              <label class="block text-sm font-bold text-gray-700 mb-2">Soporte Remoto</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-headset text-gray-400"></i></div>
                <input type="text" name="soporte_remoto" [(ngModel)]="d.soporte_remoto" placeholder="Nombre de quien apoyó..." class="w-full border border-gray-300 rounded-lg py-3 pl-10 pr-3 focus:outline-none focus:ring-2 focus:ring-blue-500" />
              </div>
            </div>
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 md:col-span-2 grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label class="block text-xs font-bold text-blue-800 uppercase mb-1">Núm. Máquina</label>
                <input type="text" name="numero_maquina" [(ngModel)]="d.numero_maquina" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none" />
              </div>
              <div>
                <label class="block text-xs font-bold text-blue-800 uppercase mb-1">Serial Máquina</label>
                <input type="text" name="serial_maquina" [(ngModel)]="d.serial_maquina" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none" />
              </div>
              <div>
                <label class="block text-xs font-bold text-blue-800 uppercase mb-1">Serial Router</label>
                <input type="text" name="serial_router" [(ngModel)]="d.serial_router" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none" />
              </div>
              <div>
                <label class="block text-xs font-bold text-blue-800 uppercase mb-1">Serial UPS</label>
                <input type="text" name="serial_ups" [(ngModel)]="d.serial_ups" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none" />
              </div>
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Administrador del Punto</label>
              <input type="text" name="administrador_punto" [(ngModel)]="d.administrador_punto" class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Celular Encargado</label>
              <input type="text" name="celular_encargado" [(ngModel)]="d.celular_encargado" class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Estado Inicial</label>
              <select name="id_estado_inicial" [(ngModel)]="d.id_estado_inicial" class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">- Seleccione Estado -</option>
                @for (e of estados; track e.id_estado) {
                  <option [value]="'' + e.id_estado">{{ e.nombre_estado }}</option>
                }
              </select>
            </div>
            <div>
              <label class="block text-sm font-bold text-orange-600 mb-2">Pendientes</label>
              <textarea name="pendientes" rows="2" [(ngModel)]="d.pendientes" class="w-full bg-orange-50 border border-orange-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
            </div>
          </div>

          @if (evidencias.length > 0) {
            <div class="mt-6 border-t pt-6">
              <h3 class="text-lg font-bold text-gray-700 mb-4"><i class="fas fa-photo-video text-green-500 mr-2"></i> Evidencia Actual ({{ evidencias.length }} fotos)</h3>
              <p class="text-sm text-gray-500 mb-4">Estas son las fotos que ya están vinculadas a este servicio.</p>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @for (g of grupos; track g.tipo) {
                  <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                    <h4 class="text-xs font-bold text-gray-600 uppercase mb-2 border-b pb-1">{{ g.titulo }} ({{ deTipo(g.tipo).length }})</h4>
                    <div class="flex flex-wrap gap-2">
                      @for (foto of deTipo(g.tipo); track foto.id_evidencia) {
                        <a [href]="ruta(foto.ruta_archivo)" target="_blank">
                          <img [src]="ruta(foto.ruta_archivo)" (error)="imgError($event)" class="w-16 h-16 object-cover rounded shadow hover:scale-110 transition cursor-pointer border border-gray-300 bg-white" [alt]="g.titulo" />
                        </a>
                      } @empty {
                        <span class="text-xs text-gray-400 italic">No hay fotos</span>
                      }
                    </div>
                  </div>
                }
              </div>
            </div>
          }

          <div class="mt-6 border-t pt-6">
            <h3 class="text-lg font-bold text-gray-700 mb-4"><i class="fas fa-camera text-blue-500 mr-2"></i> Anexar Evidencia Fotográfica</h3>
            <p class="text-sm text-gray-500 mb-4">Límite de subida: <strong>10 fotos en total</strong> por sesión de edición.</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              @for (c of cargas; track c.campo) {
                <div>
                  <div class="border border-dashed border-gray-400 rounded-lg p-4 bg-gray-50 text-center hover:bg-gray-100 transition relative overflow-hidden">
                    <i class="fas text-gray-400 text-3xl mb-2" [class]="c.icono"></i>
                    <p class="text-sm font-bold text-gray-700">{{ c.titulo }}</p>
                    <span class="bg-gray-200 text-gray-700 px-3 py-1 rounded-full text-xs font-bold inline-block relative z-10 mt-2">{{ archivos[c.campo].length }} seleccionadas</span>
                    <input type="file" multiple accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" (change)="seleccionar($event, c.campo)" />
                  </div>
                  <div class="flex flex-wrap gap-2 mt-2 justify-center">
                    @for (p of previews[c.campo]; track $index) {
                      <img class="w-16 h-16 object-cover rounded shadow border border-gray-300" [src]="p.src" [title]="p.nombre" />
                    }
                  </div>
                </div>
              }
            </div>
            <div class="text-center pt-4 mt-4 border-t border-gray-200">
              <span class="text-sm font-bold text-gray-600">Total a subir ahora: <span class="text-lg" [class]="total === MAX ? 'text-red-600' : 'text-blue-600'">{{ total }}</span>/10</span>
            </div>
          </div>

          <div class="mt-8 flex justify-end">
            <button type="submit" [disabled]="enviando" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-3 px-8 rounded-lg shadow-lg transition transform hover:scale-105 flex items-center gap-2 disabled:opacity-60">
              <i class="fas" [class.fa-save]="!enviando" [class.fa-spinner]="enviando" [class.fa-spin]="enviando"></i> Guardar Cambios
            </button>
          </div>
        </form>
      </div>
    }
  `
})
export class ServicioEditar implements OnInit {
  private api = inject(Api);
  private route = inject(ActivatedRoute);
  private router = inject(Router);

  readonly MAX = MAX_FOTOS;
  d: any = null;
  estados: any[] = [];
  evidencias: any[] = [];
  enviando = false;
  grupos = [
    { tipo: 'antes', titulo: 'Antes' },
    { tipo: 'remision', titulo: 'Remisión' },
    { tipo: 'despues', titulo: 'Después' }
  ];
  cargas: { campo: TipoFoto; titulo: string; icono: string }[] = [
    { campo: 'fotos_antes', titulo: '1. Fotos del "Antes"', icono: 'fa-images' },
    { campo: 'foto_remision', titulo: '2. Foto de Remisión', icono: 'fa-file-signature' },
    { campo: 'fotos_despues', titulo: '3. Fotos del "Después"', icono: 'fa-check-double' }
  ];
  archivos: Record<TipoFoto, File[]> = { fotos_antes: [], foto_remision: [], fotos_despues: [] };
  previews: Record<TipoFoto, { src: string; nombre: string }[]> = { fotos_antes: [], foto_remision: [], fotos_despues: [] };

  get total() {
    return this.archivos.fotos_antes.length + this.archivos.foto_remision.length + this.archivos.fotos_despues.length;
  }

  async ngOnInit() {
    const id = this.route.snapshot.paramMap.get('id') || this.route.snapshot.queryParamMap.get('id') || '0';
    const r: any = await this.api.get('servicioEditar', 'index', { id });
    if (r.error) {
      alert(r.error);
      history.back();
      return;
    }
    this.d = { ...r.datosOrden };
    ['soporte_remoto', 'numero_maquina', 'serial_maquina', 'serial_router', 'serial_ups', 'pendientes', 'administrador_punto', 'celular_encargado'].forEach((k) => (this.d[k] = this.d[k] ?? ''));
    this.d.id_estado_inicial = this.d.id_estado_inicial != null ? String(this.d.id_estado_inicial) : '';
    this.estados = r.estados || [];
    this.evidencias = r.evidencias || [];
  }

  deTipo(tipo: string) {
    return this.evidencias.filter((e) => e.tipo_evidencia === tipo);
  }

  ruta(r: string) {
    return rutaImagenApp(r);
  }

  imgError(e: Event) {
    const img = e.target as HTMLImageElement;
    img.onerror = null;
    img.src = 'https://placehold.co/100x100?text=Error';
    img.title = 'Imagen no encontrada en el servidor';
  }

  seleccionar(e: Event, campo: TipoFoto) {
    const input = e.target as HTMLInputElement;
    const lista = Array.from(input.files || []);
    const otros = this.total - this.archivos[campo].length;
    const totalActual = otros + lista.length;
    if (totalActual > MAX_FOTOS) {
      alert(`❌ Límite excedido. Solo puedes subir un máximo de ${MAX_FOTOS} imágenes en total. Actualmente seleccionaste ${totalActual}.`);
      input.value = '';
      this.archivos[campo] = [];
      this.previews[campo] = [];
      return;
    }
    this.archivos[campo] = lista;
    this.previews[campo] = [];
    lista.forEach((f) => {
      if (!f.type.match('image.*')) return;
      const rd = new FileReader();
      rd.onload = (ev) => this.previews[campo].push({ src: String(ev.target?.result || ''), nombre: f.name });
      rd.readAsDataURL(f);
    });
  }

  volver() {
    history.back();
  }

  async guardar() {
    if (this.total > MAX_FOTOS) {
      alert(`No puedes guardar. Has excedido el límite de ${MAX_FOTOS} imágenes.`);
      return;
    }
    const fd = new FormData();
    fd.append('id_ordenes_servicio', String(this.d.id_ordenes_servicio));
    fd.append('numero_remision', String(this.d.numero_remision ?? ''));
    ['soporte_remoto', 'numero_maquina', 'serial_maquina', 'serial_router', 'serial_ups', 'administrador_punto', 'celular_encargado', 'id_estado_inicial', 'pendientes'].forEach((k) => fd.append(k, String(this.d[k] ?? '')));
    (Object.keys(this.archivos) as TipoFoto[]).forEach((k) => this.archivos[k].forEach((f) => fd.append(k + '[]', f)));
    this.enviando = true;
    try {
      const r: any = await this.api.post('servicioEditar', 'guardar', fd);
      alert(r.msg);
      if (r.status === 'ok') this.router.navigateByUrl('/serviciosPdf');
    } catch {
      alert('❌ Error al actualizar la información.');
    } finally {
      this.enviando = false;
    }
  }
}
