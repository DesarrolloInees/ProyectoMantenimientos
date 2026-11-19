import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { Api } from '../../core/api';

const ESTILOS = `
  .card { background: #fff; border-radius: 10px; box-shadow: 0 4px 16px rgba(0,0,0,.08); overflow: hidden; }
  .card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; font-weight: 700; font-size: 1.1rem; }
  .card-body { padding: 1.25rem; }
  .zona-carga { text-align: center; padding: 2.5rem; border-radius: 8px; cursor: pointer; display: block; }
  .badge { display: inline-block; padding: .25em .55em; font-size: 75%; font-weight: 700; border-radius: .25rem; color: #fff; }
  .badge-primary { background: #2563eb; } .badge-success { background: #16a34a; } .badge-danger { background: #dc2626; }
  .badge-warning { background: #f59e0b; color: #1f2937; } .badge-secondary { background: #6b7280; } .badge-info { background: #0ea5e9; }
  .progreso { height: 25px; background: #e5e7eb; border-radius: 6px; overflow: hidden; }
  .barra { height: 100%; color: #fff; font-weight: 700; text-align: center; line-height: 25px; transition: width .3s; }
  table.sim { width: 100%; font-size: 13px; } table.sim th { background: #1f2937; color: #fff; padding: .45rem; position: sticky; top: 0; z-index: 1; text-align: left; }
  table.sim td { padding: .4rem; border-bottom: 1px solid #e5e7eb; vertical-align: middle; } table.sim tr:nth-child(even) td { background: #f9fafb; }
  .btn-filtro { border: 1px solid #9ca3af; padding: .35rem .7rem; font-weight: 700; font-size: 13px; } .btn-filtro.activo { background: #374151; color: #fff; }
`;

type Fila = { device: string; cliente: string; punto: string; estado: string; cliente_antiguo?: string; punto_antiguo?: string; zona?: string; tipo_maquina?: string; estado_actual?: string; sel?: boolean };

/** Base común del flujo por lotes (subir → simular por lotes → confirmar → importar por lotes → finalizar). */
abstract class ImportacionPorLotes {
  protected api = inject(Api);
  protected abstract pagina: string;
  archivo: File | null = null;
  fase: 'inicio' | 'progreso' | 'revision' | 'final' = 'inicio';
  modo: 'simular' | 'importar' = 'simular';
  porcentaje = 0;
  textoProgreso = '';
  errorProgreso = false;
  boton = 'INICIAR PROCESO';
  ocupado = false;
  total = 0;
  lote = 200;
  lista: Fila[] = [];
  filtro = 'TODOS';
  busqueda = '';
  stats = { insertados: 0, actualizados: 0, errores: 0 };
  bajas: any = null;
  protected fechaInicio = '';

  elegir(ev: Event) {
    this.archivo = (ev.target as HTMLInputElement).files?.[0] || null;
  }
  visibles() {
    const q = this.busqueda.toLowerCase();
    return this.lista.filter((i) => (this.filtro === 'TODOS' || i.estado === this.filtro) && [i.device, i.cliente, i.punto, i.tipo_maquina, i.cliente_antiguo, i.punto_antiguo].join(' ').toLowerCase().includes(q));
  }
  contar(e: string) {
    return this.lista.filter((i) => i.estado === e).length;
  }
  marcar(v: boolean) {
    this.visibles().forEach((i) => i.sel !== undefined && (i.sel = v));
  }
  async iniciar() {
    if (!this.archivo) {
      alert('Selecciona un archivo primero.');
      return;
    }
    this.modo = 'simular';
    this.lista = [];
    this.ocupado = true;
    this.boton = 'Analizando...';
    this.fase = 'progreso';
    this.errorProgreso = false;
    const d = new Date(), p = (n: number) => String(n).padStart(2, '0');
    this.fechaInicio = `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`;
    const fd = new FormData();
    fd.append('archivo_excel', this.archivo);
    try {
      const r: any = await this.api.post(this.pagina, 'subirArchivo', fd);
      if (!r.exito) return this.mostrarError(r.error);
      this.total = r.total_filas;
      this.textoProgreso = 'Archivo cargado. Iniciando simulación...';
      this.siguiente(2);
    } catch (e: any) {
      this.mostrarError('Error de conexión al subir: ' + (e?.message || ''));
    }
  }
  protected mostrarError(msg: string) {
    this.fase = 'inicio';
    this.ocupado = false;
    this.boton = 'REINTENTAR';
    alert(msg);
  }
  protected async siguiente(inicio: number) {
    this.porcentaje = Math.min(100, Math.round((inicio / this.total) * 100));
    this.textoProgreso = `${this.modo === 'simular' ? 'Analizando' : this.textoFaseImportar} filas ${inicio} a ${inicio + this.lote}...`;
    if (inicio > this.total) return this.finalizar();
    const aprobados = this.modo === 'importar' ? this.lista.filter((i) => i.sel).map((i) => i.device) : [];
    try {
      const r: any = await this.api.postForm(this.pagina, 'procesarLote', { inicio, cantidad: this.lote, modo: this.modo, aprobados: JSON.stringify(aprobados) });
      if (!r.exito) {
        this.textoProgreso = 'Error: ' + r.error;
        this.errorProgreso = true;
        return;
      }
      if (this.modo === 'simular' && r.detalles) this.lista.push(...r.detalles.map((x: Fila) => this.prepararFila(x)));
      else if (this.modo === 'importar' && r.stats) {
        this.stats.insertados += r.stats.insertados || 0;
        this.stats.actualizados += r.stats.actualizados || 0;
        this.stats.errores += r.stats.errores || 0;
      }
      if (r.detener === true) {
        this.porcentaje = 100;
        this.finalizar();
      } else this.siguiente(inicio + this.lote);
    } catch {
      setTimeout(() => this.siguiente(inicio), 3000);
    }
  }
  protected textoFaseImportar = 'Importando';
  protected abstract prepararFila(x: Fila): Fila;
  protected abstract finalizar(): void;
  confirmarImportacion(seleccionados: number) {
    this.fase = 'progreso';
    this.porcentaje = 0;
    this.textoProgreso = 'Iniciando...';
    this.modo = 'importar';
    this.stats = { insertados: 0, actualizados: 0, errores: 0 };
    void seleccionados;
    this.siguiente(2);
  }
  seleccionados() {
    return this.lista.filter((i) => i.sel).length;
  }
}

// =====================================================================================
// IMPORTACIÓN INTELIGENTE C2D (importarExcelVista.php)
// =====================================================================================
@Component({
  selector: 'app-importar-excel',
  imports: [FormsModule],
  styles: [ESTILOS],
  template: `
    <div class="max-w-5xl mx-auto">
      <div class="card">
        <div class="card-header"><i class="fas fa-file-excel mr-2 text-green-600"></i> Importación Inteligente C2D</div>
        <div class="card-body">
          <div class="bg-sky-50 border border-sky-200 text-sky-800 p-4 rounded mb-4">
            <h5 class="font-bold"><i class="fas fa-info mr-1"></i> ¿Cómo funciona?</h5>
            <ul class="text-sm list-disc ml-5">
              <li>Sube el archivo <b>.xlsx</b>. El sistema procesará en lotes para evitar bloqueos.</li>
              <li><b>Existentes:</b> Se actualiza su ubicación (traslados automáticos).</li>
              <li><b>Nuevos:</b> Se crean automáticamente Cliente, Punto y Máquina.</li>
              <li><b>Fantasmas:</b> Si una máquina/punto NO está en este Excel, se desactiva.</li>
            </ul>
          </div>
          <label class="zona-carga" style="border: 2px dashed #ced4da; background-color: #f8f9fa;">
            <i class="fas fa-cloud-upload-alt fa-4x text-blue-600 mb-3"></i>
            <h5 class="text-gray-500">Arrastra tu archivo aquí</h5>
            @if (archivo) { <span class="badge badge-primary p-2 mt-2">{{ archivo.name }}</span> }
            <input type="file" class="hidden" accept=".xlsx, .xls" (change)="elegir($event)">
          </label>
          <div class="text-center mt-4"><button type="button" (click)="iniciar()" [disabled]="ocupado" class="bg-green-600 text-white text-lg px-10 py-3 rounded font-bold shadow"><i class="fas fa-play mr-2"></i> {{ boton }}</button></div>

          @if (fase === 'progreso') {
            <div class="mt-4">
              <h5 class="text-center font-bold text-gray-800">Procesando... Por favor no cierres esta ventana</h5>
              <div class="progreso"><div class="barra bg-blue-600" [style.width.%]="porcentaje">{{ porcentaje }}%</div></div>
              <div class="text-center mt-2"><span [class]="errorProgreso ? 'text-red-600' : 'text-gray-500'">{{ textoProgreso }}</span></div>
            </div>
          }

          @if (fase === 'revision') {
            <div class="card mt-4 border border-sky-500">
              <div class="card-header bg-sky-600 text-white"><i class="fas fa-clipboard-check mr-2"></i> Revisión Previa ({{ lista.length }} registros)</div>
              <div class="card-body bg-gray-50 pb-2">
                <div class="flex flex-wrap justify-between items-center gap-3 mb-3">
                  <input type="text" [(ngModel)]="busqueda" class="border rounded px-3 py-2 w-full md:w-1/3" placeholder="Buscar ID, Cliente...">
                  <div class="flex">
                    <button type="button" class="btn-filtro rounded-l" [class.activo]="filtro === 'TODOS'" (click)="filtro = 'TODOS'">Todos <span class="badge badge-secondary ml-1">{{ lista.length }}</span></button>
                    <button type="button" class="btn-filtro" [class.activo]="filtro === 'NUEVO'" (click)="filtro = 'NUEVO'">Nuevos <span class="badge badge-success ml-1">{{ contar('NUEVO') }}</span></button>
                    <button type="button" class="btn-filtro" [class.activo]="filtro === 'ACTUALIZAR'" (click)="filtro = 'ACTUALIZAR'">Mover <span class="badge badge-primary ml-1">{{ contar('ACTUALIZAR') }}</span></button>
                    <button type="button" class="btn-filtro rounded-r" [class.activo]="filtro === 'BAJA'" (click)="filtro = 'BAJA'">Bajas <span class="badge badge-danger ml-1">{{ contar('BAJA') }}</span></button>
                  </div>
                </div>
                <div class="mb-2 text-right">
                  <button type="button" class="text-sm text-gray-500 font-bold mr-3" (click)="marcar(true)"><i class="fas fa-check-square mr-1"></i>Marcar Visibles</button>
                  <button type="button" class="text-sm text-gray-500 font-bold" (click)="marcar(false)"><i class="far fa-square mr-1"></i>Desmarcar Visibles</button>
                </div>
              </div>
              <div style="max-height: 500px; overflow-y: auto;">
                <table class="sim">
                  <thead><tr><th class="text-center" style="width: 60px;">Subir</th><th style="width: 170px;">Acción Sugerida</th><th>Device ID</th><th>Cliente</th><th>Punto Destino</th></tr></thead>
                  <tbody>
                    @for (i of visibles(); track $index) {
                      <tr>
                        <td class="text-center bg-white">
                          @if (i.estado === 'BAJA') { <i class="fas fa-ban text-red-600" title="Se dará de baja automáticamente"></i> }
                          @else { <input type="checkbox" [(ngModel)]="i.sel" style="transform: scale(1.5);"> }
                        </td>
                        <td>
                          @if (i.estado === 'NUEVO') { <span class="badge badge-success">NUEVO</span> }
                          @else if (i.estado === 'ACTUALIZAR') { <span class="badge badge-primary">ACTUALIZAR</span> }
                          @else { <span class="badge badge-danger"><i class="fas fa-power-off mr-1"></i>DESACTIVAR (BAJA)</span> }
                        </td>
                        <td class="font-bold text-gray-900">{{ i.device }}</td>
                        <td>
                          @if (i.estado === 'ACTUALIZAR') {
                            @if (i.cliente_antiguo && i.cliente_antiguo !== i.cliente) { <del class="text-red-600 text-xs">{{ i.cliente_antiguo }}</del><br><span class="text-green-600 font-bold"><i class="fas fa-arrow-right mx-1"></i>{{ i.cliente }}</span> }
                            @else { <span class="text-gray-500">{{ i.cliente }} <small>(Sin cambios)</small></span> }
                          } @else if (i.estado === 'NUEVO') { <span class="text-green-600 font-bold">{{ i.cliente }}</span> }
                          @else { <span class="text-red-600">{{ i.cliente }}</span> }
                        </td>
                        <td>
                          @if (i.estado === 'ACTUALIZAR') {
                            @if (i.punto_antiguo && i.punto_antiguo !== i.punto) { <del class="text-red-600 text-xs">{{ i.punto_antiguo }}</del><br><span class="text-green-600 font-bold"><i class="fas fa-arrow-right mx-1"></i>{{ i.punto }}</span> }
                            @else { <span class="text-gray-500">{{ i.punto }} <small>(Sin cambios)</small></span> }
                          } @else if (i.estado === 'NUEVO') { <span class="text-green-600 font-bold">{{ i.punto }}</span> }
                          @else { <span class="text-red-600">{{ i.punto }}</span> }
                        </td>
                      </tr>
                    }
                  </tbody>
                </table>
                @if (!visibles().length && lista.length) { <div class="text-center p-4"><i class="fas fa-search-minus fa-3x text-gray-400 mb-3"></i><h5 class="text-gray-500">No se encontraron registros con esa búsqueda.</h5></div> }
              </div>
              <div class="text-center bg-white p-4" style="border-top: 2px dashed #17a2b8;">
                <h4 class="text-gray-800 mb-3 text-lg font-bold">¿Todo listo, bro?</h4>
                <button type="button" (click)="confirmar()" class="bg-green-600 text-white text-lg px-10 py-3 font-bold shadow-lg" style="border-radius: 30px;"><i class="fas fa-rocket mr-2"></i> CONFIRMAR E IMPORTAR SELECCIONADOS</button>
                <p class="text-gray-500 text-sm mt-2">Nota: Las máquinas marcadas como "BAJA" se desactivarán automáticamente.</p>
              </div>
            </div>
          }

          @if (fase === 'final') {
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
              <div class="bg-green-600 text-white p-4 rounded shadow"><i class="fas fa-plus-circle fa-2x"></i><div>Nuevos Creados</div><div class="text-2xl font-bold">{{ stats.insertados }}</div></div>
              <div class="bg-sky-600 text-white p-4 rounded shadow"><i class="fas fa-sync-alt fa-2x"></i><div>Verificados/Movidos</div><div class="text-2xl font-bold">{{ stats.actualizados }}</div></div>
              <div class="bg-gray-600 text-white p-4 rounded shadow"><i class="fas fa-power-off fa-2x"></i><div>Desactivados (Bajas)</div><div class="font-bold">Máq: {{ bajas?.maquinas }} <small>| Ptos: {{ bajas?.puntos }}</small></div></div>
            </div>
            <div class="text-center mt-8 mb-3">
              <div class="bg-green-50 border border-green-300 text-green-800 p-4 rounded shadow-sm"><h4 class="font-bold"><i class="fas fa-check-circle"></i> ¡Importación Realizada!</h4>Los registros marcados fueron guardados en la base de datos.</div>
              <button type="button" (click)="reiniciar()" class="mt-4 bg-blue-600 text-white text-lg px-10 py-3 rounded shadow"><i class="fas fa-file-excel mr-2"></i> Subir otro archivo</button>
            </div>
          }
        </div>
      </div>
    </div>
  `
})
export class ImportarExcel extends ImportacionPorLotes {
  protected pagina = 'importarExcel';

  protected prepararFila(x: Fila): Fila {
    return { ...x, sel: true };
  }
  protected async finalizar() {
    this.textoProgreso = 'Finalizando fase actual y buscando bajas...';
    const r: any = await this.api.postForm(this.pagina, 'finalizarImportacion', {
      fecha_inicio: this.fechaInicio,
      modo: this.modo,
      devices_excel: JSON.stringify(this.lista.map((i) => i.device))
    });
    if (this.modo === 'simular') {
      (r.fantasmas || []).forEach((f: any) => this.lista.push({ device: f.device_id, cliente: f.nombre_cliente, punto: f.nombre_punto, estado: 'BAJA' }));
      this.ocupado = false;
      this.boton = 'REINICIAR';
      this.filtro = 'TODOS';
      this.fase = 'revision';
    } else {
      this.bajas = r.bajas;
      this.fase = 'final';
    }
  }
  confirmar() {
    const sel = this.seleccionados(), bajas = this.contar('BAJA');
    if (!sel && !bajas) {
      alert('No hay nada que actualizar ni desactivar.');
      return;
    }
    if (confirm(`Vas a inyectar ${sel} registros. Las ${bajas} máquinas faltantes se desactivarán. ¿Continuar?`)) this.confirmarImportacion(sel);
  }
  reiniciar() {
    this.fase = 'inicio';
    this.archivo = null;
    this.ocupado = false;
    this.boton = 'INICIAR PROCESO';
  }
}

// =====================================================================================
// IMPORTAR ESTADO DE MÁQUINAS (importarEstadoMaquinaVista.php)
// =====================================================================================
@Component({
  selector: 'app-importar-estado-maquina',
  imports: [FormsModule, RouterLink],
  styles: [ESTILOS],
  template: `
    <div class="max-w-5xl mx-auto">
      <div class="card">
        <div class="card-header"><i class="fas fa-file-excel mr-2 text-red-600"></i> Importar Estado de Maquinas</div>
        <div class="card-body">
          <div class="bg-amber-50 border border-amber-200 text-amber-800 p-4 rounded mb-4">
            <h5 class="font-bold"><i class="fas fa-exclamation-triangle mr-1"></i> Como funciona?</h5>
            <ul class="text-sm list-disc ml-5">
              <li>Sube un archivo <b>.xlsx</b> con los <b>Device IDs</b> en la <b>columna A</b>.</li>
              <li>El sistema buscara esas maquinas y las marcara como <b>FUERA DE SERVICIO</b>.</li>
              <li>Las maquinas marcadas apareceran en la <b>Programacion de Rutas</b> para programar servicios aledaños.</li>
              <li>Si un punto no se programa, su maquina volvera automaticamente a estado <b>Operativo</b>.</li>
            </ul>
          </div>
          <label class="zona-carga" style="border: 2px dashed #dc3545; background-color: #fff5f5;">
            <i class="fas fa-cloud-upload-alt fa-4x text-red-600 mb-3"></i>
            <h5 class="text-gray-500">Arrastra tu archivo aqui</h5><small class="text-gray-500">Solo columna A con Device IDs</small><br>
            @if (archivo) { <span class="badge badge-danger p-2 mt-2">{{ archivo.name }}</span> }
            <input type="file" class="hidden" accept=".xlsx, .xls" (change)="elegir($event)">
          </label>
          <div class="text-center mt-4"><button type="button" (click)="iniciar()" [disabled]="ocupado" class="bg-red-600 text-white text-lg px-10 py-3 rounded font-bold shadow"><i class="fas fa-play mr-2"></i> {{ boton }}</button></div>

          @if (fase === 'progreso') {
            <div class="mt-4">
              <h5 class="text-center font-bold text-gray-800">Procesando... Por favor no cierres esta ventana</h5>
              <div class="progreso"><div class="barra bg-red-600" [style.width.%]="porcentaje">{{ porcentaje }}%</div></div>
              <div class="text-center mt-2"><span [class]="errorProgreso ? 'text-red-600' : 'text-gray-500'">{{ textoProgreso }}</span></div>
            </div>
          }

          @if (fase === 'revision') {
            <div class="card mt-4 border border-red-500">
              <div class="card-header bg-red-600 text-white"><i class="fas fa-clipboard-check mr-2"></i> Revision Previa ({{ lista.length }} registros)</div>
              <div class="card-body bg-gray-50 pb-2">
                <div class="flex flex-wrap justify-between items-center gap-3 mb-3">
                  <input type="text" [(ngModel)]="busqueda" class="border rounded px-3 py-2 w-full md:w-1/3" placeholder="Buscar Device ID, Cliente...">
                  <div class="flex">
                    <button type="button" class="btn-filtro rounded-l" [class.activo]="filtro === 'TODOS'" (click)="filtro = 'TODOS'">Todos <span class="badge badge-secondary ml-1">{{ lista.length }}</span></button>
                    <button type="button" class="btn-filtro" [class.activo]="filtro === 'MARCAR_INACTIVO'" (click)="filtro = 'MARCAR_INACTIVO'">A Marcar <span class="badge badge-danger ml-1">{{ contar('MARCAR_INACTIVO') }}</span></button>
                    <button type="button" class="btn-filtro" [class.activo]="filtro === 'YA_INACTIVA'" (click)="filtro = 'YA_INACTIVA'">Ya Inactivas <span class="badge badge-secondary ml-1">{{ contar('YA_INACTIVA') }}</span></button>
                    <button type="button" class="btn-filtro rounded-r" [class.activo]="filtro === 'NO_ENCONTRADO'" (click)="filtro = 'NO_ENCONTRADO'">No Encontradas <span class="badge badge-warning ml-1">{{ contar('NO_ENCONTRADO') }}</span></button>
                  </div>
                </div>
                <div class="mb-2 text-right">
                  <button type="button" class="text-sm text-gray-500 font-bold mr-3" (click)="marcar(true)"><i class="fas fa-check-square mr-1"></i>Marcar Visibles</button>
                  <button type="button" class="text-sm text-gray-500 font-bold" (click)="marcar(false)"><i class="far fa-square mr-1"></i>Desmarcar Visibles</button>
                </div>
              </div>
              <div style="max-height: 500px; overflow-y: auto;">
                <table class="sim">
                  <thead><tr><th class="text-center" style="width: 60px;">Marcar</th><th style="width: 180px;">Accion</th><th>Device ID</th><th>Cliente</th><th>Punto</th><th>Zona</th><th>Tipo Maquina</th><th>Estado Actual</th></tr></thead>
                  <tbody>
                    @for (i of visibles(); track $index) {
                      <tr>
                        <td class="text-center bg-white">
                          @if (i.estado === 'MARCAR_INACTIVO') { <input type="checkbox" [(ngModel)]="i.sel" style="transform: scale(1.5);"> }
                          @else if (i.estado === 'NO_ENCONTRADO') { <i class="fas fa-exclamation-triangle text-amber-500" title="Device ID no encontrado en la base de datos"></i> }
                          @else { <i class="fas fa-check-circle text-gray-500" title="Ya esta marcada como fuera de servicio"></i> }
                        </td>
                        <td>
                          @if (i.estado === 'MARCAR_INACTIVO') { <span class="badge badge-danger">FUERA DE SERVICIO</span> }
                          @else if (i.estado === 'YA_INACTIVA') { <span class="badge badge-secondary">YA INACTIVA</span> }
                          @else { <span class="badge badge-warning">NO ENCONTRADO</span> }
                        </td>
                        <td class="font-bold text-gray-900">{{ i.device }}</td>
                        <td>{{ i.cliente }}</td>
                        <td>{{ i.punto }}</td>
                        <td><span class="badge badge-info">{{ i.zona || 'N/A' }}</span></td>
                        <td>{{ i.tipo_maquina }}</td>
                        <td>@if (i.estado_actual === 'Operativo') { <span class="badge badge-success">Operativo</span> } @else { <span class="badge badge-secondary">Inactivo</span> }</td>
                      </tr>
                    }
                  </tbody>
                </table>
                @if (!visibles().length && lista.length) { <div class="text-center p-4"><i class="fas fa-search-minus fa-3x text-gray-400 mb-3"></i><h5 class="text-gray-500">No se encontraron registros con esa busqueda.</h5></div> }
              </div>
              <div class="text-center bg-white p-4" style="border-top: 2px dashed #dc3545;">
                <h4 class="text-gray-800 mb-3 text-lg font-bold">Confirmar marcado de maquinas?</h4>
                <button type="button" (click)="confirmar()" class="bg-red-600 text-white text-lg px-10 py-3 font-bold shadow-lg" style="border-radius: 30px;"><i class="fas fa-power-off mr-2"></i> CONFIRMAR Y MARCAR SELECCIONADAS</button>
                <p class="text-gray-500 text-sm mt-2">Las maquinas seleccionadas quedaran como "Fuera de Servicio".</p>
              </div>
            </div>
          }

          @if (fase === 'final') {
            <div class="text-center mt-8 mb-3">
              <div class="bg-green-50 border border-green-300 text-green-800 p-4 rounded shadow-sm">
                <h4 class="font-bold"><i class="fas fa-check-circle"></i> ¡Marcado Realizado!</h4>
                Las maquinas seleccionadas fueron marcadas como "Fuera de Servicio".<br>
                <small class="text-gray-500">Ahora ve a <b>Programacion de Rutas</b> para ver los puntos afectados.</small>
              </div>
              <button type="button" (click)="reiniciar()" class="mt-4 bg-blue-600 text-white text-lg px-10 py-3 rounded shadow"><i class="fas fa-file-excel mr-2"></i> Subir otro archivo</button>
              <a routerLink="/programacionCrear" class="inline-block mt-4 ml-2 bg-green-600 text-white text-lg px-10 py-3 rounded shadow"><i class="fas fa-calendar-alt mr-2"></i> Ir a Programacion</a>
            </div>
          }
        </div>
      </div>
    </div>
  `
})
export class ImportarEstadoMaquina extends ImportacionPorLotes {
  protected pagina = 'importarEstadoMaquina';
  protected override textoFaseImportar = 'Marcando';

  protected prepararFila(x: Fila): Fila {
    return x.estado === 'MARCAR_INACTIVO' ? { ...x, sel: true } : { ...x };
  }
  protected async finalizar() {
    this.textoProgreso = 'Finalizando...';
    await this.api.postForm(this.pagina, 'finalizarImportacion', { modo: this.modo });
    if (this.modo === 'simular') {
      this.ocupado = false;
      this.boton = 'REINICIAR';
      this.filtro = 'TODOS';
      this.fase = 'revision';
    } else this.fase = 'final';
  }
  confirmar() {
    const sel = this.seleccionados();
    if (!sel) {
      alert('No hay nada que marcar.');
      return;
    }
    if (confirm(`Vas a marcar ${sel} maquina(s) como "Fuera de Servicio".\n\nLas maquinas no seleccionadas o no encontradas no se modificaran.\n\nContinuar?`)) this.confirmarImportacion(sel);
  }
  reiniciar() {
    this.fase = 'inicio';
    this.archivo = null;
    this.ocupado = false;
    this.boton = 'INICIAR PROCESO';
  }
}

// =====================================================================================
// SIMULACIÓN Y ACTUALIZACIÓN DE ZONAS (importarMunicipiosVista.php)
// =====================================================================================
@Component({
  selector: 'app-importar-municipios',
  imports: [FormsModule],
  styles: [ESTILOS + ' .bg-warning-light td { background-color: #fff3cd !important; }'],
  template: `
    <div class="max-w-5xl mx-auto">
      <div class="card">
        <div class="card-header"><i class="fas fa-map-marked-alt mr-2"></i> Simulación y Actualización de Zonas</div>
        <div class="card-body">
          <label class="zona-carga" style="border: 2px dashed #17a2b8; background-color: #f4f8f9; padding: 1.5rem;">
            <i class="fas fa-file-excel fa-3x text-sky-500 mb-2"></i>
            <h6 class="text-gray-500">Cargar Excel para ver Simulación</h6>
            @if (archivo) { <span class="badge badge-info mt-2 p-2">{{ archivo.name }}</span> }
            <input type="file" class="hidden" accept=".xlsx, .xls" (change)="elegir($event)">
          </label>
          <div class="text-center mt-3"><button type="button" (click)="simular()" [disabled]="cargando" class="bg-sky-600 text-white px-6 py-2 rounded font-bold shadow">
            @if (cargando) { <i class="fas fa-spinner fa-spin mr-2"></i> Analizando... } @else { <i class="fas fa-eye mr-2"></i> CARGAR VISTA PREVIA }
          </button></div>
          @if (datos) {
            <div class="mt-4">
              <hr>
              <div class="bg-amber-50 border border-amber-200 text-amber-800 p-3 rounded my-3"><i class="fas fa-exclamation-triangle"></i> <strong>Modo Simulación:</strong> Aún no se ha guardado nada en la base de datos. Selecciona los puntos que deseas actualizar.</div>
              <div class="flex justify-between items-center mb-2">
                <h5 class="text-gray-600 font-bold">Puntos Encontrados</h5>
                <button type="button" (click)="ejecutar()" [disabled]="guardando" class="bg-green-600 text-white px-4 py-2 rounded shadow font-bold">
                  @if (guardando) { <i class="fas fa-spinner fa-spin mr-1"></i> Guardando... } @else { <i class="fas fa-save mr-1"></i> ACTUALIZAR SELECCIONADOS }
                </button>
              </div>
              <div style="max-height: 500px; overflow-y: auto;">
                <table class="sim text-center">
                  <thead><tr><th width="5%"><input type="checkbox" [ngModel]="todos" (ngModelChange)="marcarTodos($event)"></th><th>Device ID</th><th>Punto</th><th>Zona Actual</th><th>Nueva Zona (Excel)</th></tr></thead>
                  <tbody>
                    @for (i of datos; track $index) {
                      <tr [class.bg-warning-light]="i.es_diferente">
                        <td><input type="checkbox" [(ngModel)]="i.sel"></td>
                        <td>{{ i.device_id }}</td>
                        <td class="text-left"><small>{{ i.nombre_punto }}</small></td>
                        <td class="text-red-600 font-bold">{{ i.zona_actual }}</td>
                        <td class="text-green-600 font-bold">{{ i.zona_nueva }}</td>
                      </tr>
                    } @empty {
                      <tr><td colspan="5">No se encontraron cruces con la base de datos o no hay zonas válidas.</td></tr>
                    }
                  </tbody>
                </table>
              </div>
            </div>
          }
        </div>
      </div>
    </div>
  `
})
export class ImportarMunicipios {
  private api = inject(Api);
  archivo: File | null = null;
  datos: any[] | null = null;
  cargando = false;
  guardando = false;
  todos = false;

  elegir(ev: Event) {
    this.archivo = (ev.target as HTMLInputElement).files?.[0] || null;
  }
  async simular() {
    if (!this.archivo) {
      alert('Por favor selecciona un archivo de Excel.');
      return;
    }
    this.cargando = true;
    this.datos = null;
    const fd = new FormData();
    fd.append('archivo_excel', this.archivo);
    try {
      const r: any = await this.api.post('importarMunicipios', 'generarSimulacion', fd);
      if (r.exito) {
        this.datos = (r.datos || []).map((i: any) => ({ ...i, sel: !!i.es_diferente }));
        this.todos = false;
      } else alert('Error: ' + r.error);
    } catch (e: any) {
      alert('Error en la conexión: ' + (e?.message || ''));
    } finally {
      this.cargando = false;
    }
  }
  marcarTodos(v: boolean) {
    this.todos = v;
    this.datos?.forEach((i) => (i.sel = v));
  }
  async ejecutar() {
    const sel = (this.datos || []).filter((i) => i.sel).map((i) => ({ id_punto: i.id_punto, zona_nueva: i.zona_nueva }));
    if (!sel.length) {
      alert('No has seleccionado ningún punto para actualizar.');
      return;
    }
    if (!confirm(`¿Estás seguro de actualizar la zona a ${sel.length} puntos?`)) return;
    this.guardando = true;
    try {
      const r: any = await this.api.postForm('importarMunicipios', 'ejecutarActualizacion', { datos_seleccionados: JSON.stringify(sel) });
      if (r.exito) {
        alert('¡Éxito! ' + r.mensaje);
        this.datos = null;
        this.archivo = null;
      } else alert('Error al actualizar: ' + r.error);
    } catch {
      alert('Ocurrió un error al intentar guardar.');
    } finally {
      this.guardando = false;
    }
  }
}
