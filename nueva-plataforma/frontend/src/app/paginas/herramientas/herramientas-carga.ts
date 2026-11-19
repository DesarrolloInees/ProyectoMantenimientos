import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { KeyValuePipe } from '@angular/common';
import { Api } from '../../core/api';

interface Herramienta {
  id: string;
  titulo: string;
  icono: string;
  descripcion: string;
  peligro?: string;
}

/**
 * Port de los scripts helps/*.php (cargas iniciales por CSV). Cada herramienta sube el CSV
 * y muestra el mismo resumen/reporte que mostraba la página PHP.
 */
@Component({
  selector: 'app-herramientas-carga',
  imports: [FormsModule, KeyValuePipe],
  template: `
    <div class="max-w-5xl mx-auto">
      <h1 class="text-2xl font-bold text-gray-800 mb-1"><i class="fas fa-toolbox mr-2"></i>Herramientas de Carga Inicial (CSV)</h1>
      <p class="text-gray-500 text-sm mb-6">Equivalente a los scripts de la carpeta <b>helps/</b>. Solo disponible para el administrador.</p>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        @for (h of herramientas; track h.id) {
          <button type="button" (click)="elegir(h)"
            class="text-left p-3 rounded-lg border-2 transition"
            [class.border-blue-600]="sel?.id === h.id" [class.bg-blue-50]="sel?.id === h.id" [class.border-gray-200]="sel?.id !== h.id">
            <div class="text-2xl">{{ h.icono }}</div>
            <div class="font-bold text-gray-800 text-sm">{{ h.titulo }}</div>
          </button>
        }
      </div>

      @if (sel) {
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-xl font-bold mb-2">{{ sel.icono }} {{ sel.titulo }}</h2>
          <p class="text-gray-600 mb-3" [innerHTML]="sel.descripcion"></p>
          @if (sel.peligro) {
            <div class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm"><i class="fas fa-exclamation-triangle mr-1"></i> {{ sel.peligro }}</div>
          }

          @if (paso === 1) {
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center mb-4">
              <input type="file" accept=".csv" (change)="archivo = $any($event.target).files?.[0] || null">
            </div>
            <button type="button" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg disabled:opacity-50"
              [disabled]="!archivo || cargando" (click)="procesar()">
              @if (cargando) { <i class="fas fa-spinner fa-spin mr-1"></i> Procesando... } @else { 🚀 {{ sel.id === 'clientes' ? 'Analizar Archivo' : 'Procesar Archivo' }} }
            </button>
          }

          @if (paso === 2) {
            @if (conflictosCount > 0) {
              <h3 class="font-bold text-lg mb-2">⚠️ Paso 2: Resuelve los conflictos</h3>
              @for (c of conflictos | keyvalue; track c.key) {
                <div class="flex justify-between items-center border-b py-3">
                  <div class="font-bold text-red-700 w-2/5">{{ c.key }}</div>
                  <div class="w-3/5">
                    @for (cod of $any(c.value); track cod) {
                      <label class="ml-3 cursor-pointer"><input type="radio" [name]="'c_' + c.key" [value]="cod" [(ngModel)]="correcciones[c.key]"> {{ cod }}</label>
                    }
                  </div>
                </div>
              }
            }
            <button type="button" class="w-full mt-4 bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg disabled:opacity-50"
              [disabled]="cargando || faltanCorrecciones()" (click)="guardarClientes()">💾 Guardar en Base de Datos</button>
          }

          @if (mensaje) {
            <div class="mt-4 p-4 rounded" [class]="tipo === 'error' ? 'bg-red-100 text-red-800' : tipo === 'warning' ? 'bg-yellow-100 text-yellow-800' : tipo === 'info' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'">{{ mensaje }}</div>
          }

          @if (resumen) {
            <h3 class="font-bold text-lg mt-4">Resumen del Proceso</h3>
            <ul class="list-disc ml-6">
              <li>Filas leídas: <strong>{{ resumen.leidos }}</strong></li>
              <li class="text-green-700">Puntos Actualizados: <strong>{{ resumen.actualizados }}</strong></li>
              <li class="text-yellow-700">Puntos Omitidos (Ya tenían fecha reciente): <strong>{{ resumen.omitidos.length }}</strong></li>
              <li class="text-red-700">Devices No Encontrados: <strong>{{ resumen.noEncontrados.length }}</strong></li>
            </ul>
            @if (resumen.omitidos.length) {
              <h4 class="font-bold mt-4">⚠️ Puntos NO actualizados (Fecha en BD es más reciente o igual)</h4>
              <div class="overflow-x-auto"><table class="w-full text-sm border mt-2">
                <thead class="bg-gray-100"><tr><th class="border p-1">Device ID</th><th class="border p-1">Nombre del Punto</th><th class="border p-1">Fecha en CSV (Intentada)</th><th class="border p-1">Fecha en BD (Mantenida)</th></tr></thead>
                <tbody>
                  @for (o of resumen.omitidos; track $index) {
                    <tr><td class="border p-1">{{ o.device }}</td><td class="border p-1">{{ o.punto }}</td><td class="border p-1">{{ o.fecha_csv }}</td><td class="border p-1 font-bold" style="background:#e6fffa">{{ o.fecha_bd }}</td></tr>
                  }
                </tbody>
              </table></div>
            }
            @if (resumen.noEncontrados.length) {
              <h4 class="font-bold mt-4">❌ Dispositivos no encontrados en base de datos</h4>
              <div class="p-2 border border-red-500 bg-red-50 break-words">{{ resumen.noEncontrados.join(', ') }}</div>
            }
          }

          @if (errores.length) {
            <div class="mt-4 border rounded">
              <div class="bg-red-600 text-white px-3 py-2 font-bold flex justify-between">
                <span>⚠️ {{ sel.id === 'puntos' ? 'Errores de Clientes (No encontrados en BD)' : 'Puntos No Encontrados' }}</span><span>{{ errores.length }}</span>
              </div>
              <div class="max-h-80 overflow-y-auto p-3 text-sm font-mono">
                @for (e of errores; track $index) { <div class="border-b py-1">{{ e }}</div> }
              </div>
            </div>
            @if (sel.id === 'maquinas') {
              <p class="text-center text-gray-500 text-sm mt-2">* Copia esta lista. Estos son los puntos que debes crear manualmente o corregir el nombre en el CSV.</p>
            }
          }

          @if (paso === 3) {
            <button type="button" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded" (click)="reiniciar()">🔄 Subir otro archivo</button>
          }
        </div>
      }
    </div>
  `
})
export class HerramientasCarga {
  private api = inject(Api);

  herramientas: Herramienta[] = [
    { id: 'clientes', icono: '🏭', titulo: 'Importador de Clientes', descripcion: 'CSV separado por <b>;</b> con columnas <b>CÓDIGO; NOMBRE</b>. Si un cliente aparece con varios códigos, se te pedirá elegir cuál usar.' },
    { id: 'puntos', icono: '📍', titulo: 'Carga de Puntos', descripcion: 'Columnas: NOMBRE DEL PUNTO, DIRECCIÓN, COD 1, COD 2, MUNICIPIO, CLIENTE, DELEGACIÓN. Si un punto no tiene municipio, se guarda como <b>"SIN DEFINIR"</b>.' },
    { id: 'maquinas', icono: '🤖', titulo: 'Importador de Máquinas', descripcion: 'Columnas: DEVICE ID, NOMBRE DEL PUNTO, TIPO DE MÁQUINA. Si el punto no existe, <b>se salta y aparece en el reporte</b>. Los tipos nuevos se crean automáticamente.' },
    { id: 'delegaciones', icono: '🗺️', titulo: 'Vincular Delegaciones', descripcion: 'Columnas: NOMBRE DEL PUNTO, DELEGACION (código) y DELEGACIÓN (nombre). Conecta los puntos con su delegación (001 → 1).' },
    { id: 'municipios', icono: '🏙️', titulo: 'Importar Municipios', descripcion: 'Columnas: MUNICIPIO, DELEGACIÓN, DIRECCIÓN. Inserta municipios y los vincula con su delegación.' },
    { id: 'parametros', icono: '💰', titulo: 'Importador Maestro (Tarifas y Zonas)', descripcion: 'Sube el archivo <b>HORAS GENERALES.csv</b>. Actualiza precios y define qué municipios son Urbanos o Interurbanos.', peligro: 'Atención: este proceso BORRA todas las tarifas actuales (TRUNCATE tarifa) antes de cargarlas de nuevo. Solo se ejecuta si la base de datos tiene el esquema para el que fue escrito (columna id_tipo_zona); si no, se cancela sin modificar nada.' },
    { id: 'actualizarPuntos', icono: '📅', titulo: 'Actualizar Última Visita de Puntos', descripcion: 'CSV del CONSOLIDADO (separado por <b>;</b>): columna 1 Device ID, columna 2 fecha (dd/mm/aaaa), columna 5 tipo de mantenimiento. Solo actualiza si la fecha del CSV es más reciente.' }
  ];

  sel: Herramienta | null = null;
  archivo: File | null = null;
  cargando = false;
  paso = 1;
  mensaje = '';
  tipo = 'success';
  errores: string[] = [];
  resumen: any = null;
  conflictos: Record<string, string[]> = {};
  conflictosCount = 0;
  correcciones: Record<string, string> = {};

  elegir(h: Herramienta) {
    this.sel = h;
    this.reiniciar();
  }

  reiniciar() {
    this.archivo = null;
    this.paso = 1;
    this.mensaje = '';
    this.errores = [];
    this.resumen = null;
    this.conflictos = {};
    this.conflictosCount = 0;
    this.correcciones = {};
  }

  faltanCorrecciones() {
    return Object.keys(this.conflictos).some((k) => !this.correcciones[k]);
  }

  async procesar() {
    if (!this.sel || !this.archivo) return;
    if (this.sel.peligro && !confirm(this.sel.peligro + '\n\n¿Deseas continuar?')) return;
    const fd = new FormData();
    fd.append('archivo_csv', this.archivo);
    const accion = this.sel.id === 'clientes' ? 'clientesAnalizar' : this.sel.id;
    this.cargando = true;
    this.mensaje = '';
    this.errores = [];
    this.resumen = null;
    try {
      const r: any = await this.api.post('herramientasCarga', accion, fd);
      if (!r.exito) {
        this.tipo = 'error';
        this.mensaje = r.error || 'Error desconocido';
        return;
      }
      if (this.sel.id === 'clientes') {
        this.conflictos = r.conflictos || {};
        this.conflictosCount = Object.keys(this.conflictos).length;
        this.paso = 2;
        if (r.mensaje) { this.tipo = 'info'; this.mensaje = r.mensaje; }
        return;
      }
      if (this.sel.id === 'actualizarPuntos') {
        this.resumen = r;
      } else {
        this.tipo = r.tipo || 'success';
        this.mensaje = r.mensaje;
      }
      this.errores = r.errores || [];
      this.paso = 3;
    } catch (e: any) {
      this.tipo = 'error';
      this.mensaje = e?.error?.error || e?.message || 'Error de conexión';
    } finally {
      this.cargando = false;
    }
  }

  async guardarClientes() {
    this.cargando = true;
    try {
      const r: any = await this.api.postForm('herramientasCarga', 'clientesGuardar', { correcciones: JSON.stringify(this.correcciones) });
      this.tipo = r.exito ? 'success' : 'error';
      this.mensaje = r.exito ? r.mensaje : r.error;
      if (r.exito) this.paso = 3;
      else if ((r.error || '').includes('expiró')) this.paso = 1;
    } catch (e: any) {
      this.tipo = 'error';
      this.mensaje = e?.message || 'Error de conexión';
    } finally {
      this.cargando = false;
    }
  }
}
