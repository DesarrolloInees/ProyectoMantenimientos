import { Component, OnInit, inject } from '@angular/core';
import { DecimalPipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { Api } from '../../core/api';
import { DtAbajo, DtArriba, TablaDatos, Th } from '../../shared/tabla';

const MESES = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
function nombreMes(ym: string) {
  const [a, m] = String(ym || '').split('-');
  return MESES[Number(m)] ? `${MESES[Number(m)]} ${a}` : ym;
}
const usd = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });
function mesHoy() {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
}

const MENSAJES = `
  @if (r?.mensajeExito) {
    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm"><i class="fas fa-check-circle mr-2"></i> {{ r.mensajeExito }}</div>
  }
  @if (r?.errores?.length) {
    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm"><ul class="list-disc list-inside">@for (e of r.errores; track $index) {<li>{{ e }}</li>}</ul></div>
  }`;

// =====================================================================================
// COSTOS MOTORIZADOS — VER (costosVerVista.php)
// =====================================================================================
@Component({
  selector: 'app-costos-ver',
  imports: [RouterLink, DecimalPipe, DtArriba, DtAbajo, Th],
  template: `
    <div class="w-full px-4 md:px-6">
      <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md border border-gray-100">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 border-b border-gray-100 pb-4">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800"><i class="fas fa-motorcycle text-indigo-600 mr-2"></i> Costos Motorizados</h1>
            <p class="text-gray-500 mt-1">Histórico mensual de nómina y gastos operativos de técnicos.</p>
          </div>
          <a routerLink="/costosCrear" class="mt-4 sm:mt-0 px-5 py-2.5 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transition-all duration-300 transform hover:scale-105 flex items-center space-x-2"><i class="fas fa-plus-circle"></i> <span>Nuevo Mes</span></a>
        </div>
        @if (cargado && dt.datos.length) {
          <app-dt-arriba [dt]="dt" />
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
              <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                <tr>
                  <th app-th [dt]="dt" campo="mes_reporte" class="py-3 px-4">Mes Reporte</th>
                  <th app-th [dt]="dt" campo="cantidad_tecnicos" class="py-3 px-4 text-center">Personal</th>
                  <th app-th [dt]="dt" campo="total_nomina" class="py-3 px-4 text-right">Nómina (Salarios)</th>
                  <th app-th [dt]="dt" campo="total_operativo" class="py-3 px-4 text-right">Gastos Operativos</th>
                  <th app-th [dt]="dt" campo="total_general" class="py-3 px-4 text-right">Total Mes</th>
                  <th class="py-3 px-4 text-center">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                @for (f of dt.pagina; track f.mes_reporte) {
                  <tr class="hover:bg-gray-50 transition">
                    <td class="py-4 px-4"><div class="flex items-center space-x-2"><div class="p-2 bg-indigo-50 rounded-lg text-indigo-600"><i class="far fa-calendar-alt"></i></div><span class="font-bold text-gray-700 capitalize">{{ nombreMes(f.mes_reporte) }}</span></div></td>
                    <td class="py-4 px-4 text-center"><span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-bold border border-gray-200">{{ f.cantidad_tecnicos }} Técnicos</span></td>
                    <td class="py-4 px-4 text-right font-mono text-blue-600 font-medium">$ {{ +f.total_nomina | number: '1.2-2' : 'en-US' }}</td>
                    <td class="py-4 px-4 text-right font-mono text-orange-600 font-medium">$ {{ +f.total_operativo | number: '1.2-2' : 'en-US' }}</td>
                    <td class="py-4 px-4 text-right font-mono font-bold text-gray-900 bg-gray-50/50">$ {{ +f.total_general | number: '1.2-2' : 'en-US' }}</td>
                    <td class="py-4 px-4 text-center whitespace-nowrap">
                      <a routerLink="/costosEditar" [queryParams]="{ mes: f.mes_reporte }" class="inline-flex items-center justify-center px-3 py-1.5 bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200 transition-colors border border-yellow-200 text-xs font-semibold space-x-1" title="Ver y Editar"><i class="fas fa-eye"></i> <span>Ver / Editar</span></a>
                      <button type="button" (click)="eliminarMes(f.mes_reporte)" class="text-red-600 hover:text-red-900 ml-2" title="Eliminar Mes Completo"><i class="fas fa-trash"></i></button>
                    </td>
                  </tr>
                }
              </tbody>
              <tfoot>
                <tr>
                  <td class="py-3 px-4 text-right uppercase text-xs">Totales:</td>
                  <td class="py-3 px-4 text-center"></td>
                  <td class="py-3 px-4 text-right font-mono text-indigo-900">{{ suma('total_nomina') }}</td>
                  <td class="py-3 px-4 text-right font-mono text-indigo-900">{{ suma('total_operativo') }}</td>
                  <td class="py-3 px-4 text-right font-mono text-indigo-900">{{ suma('total_general') }}</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
          <app-dt-abajo [dt]="dt" />
        } @else if (cargado) {
          <div class="text-center p-10 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 mb-4"><i class="fas fa-file-invoice-dollar text-indigo-600 text-xl"></i></div>
            <h3 class="text-lg font-medium text-gray-900">No hay registros</h3>
            <p class="text-gray-500 mt-1">Aún no se han cargado costos de motorizados.</p>
          </div>
        }
      </div>
    </div>
  `
})
export class CostosVer implements OnInit {
  private api = inject(Api);
  dt = new TablaDatos<any>([], 10);
  cargado = false;
  nombreMes = nombreMes;

  ngOnInit() {
    this.dt.campoOrden = 'mes_reporte';
    this.dt.ordenAsc = false;
    this.cargar();
  }
  async cargar() {
    const r: any = await this.api.get('costosVer');
    this.dt.set(r.listaMeses || []);
    this.cargado = true;
  }
  suma(c: string) {
    return usd.format(this.dt.pagina.reduce((a, f: any) => a + (Number(f[c]) || 0), 0));
  }
  async eliminarMes(mes: string) {
    if (!confirm(`¿Estás seguro de eliminar todo el reporte del mes ${mes}?`)) return;
    await this.api.get('costosVer', 'eliminarMes', { mes });
    this.cargar();
  }
}

// =====================================================================================
// COSTOS MOTORIZADOS — CREAR (costosCrearVista.php)
// =====================================================================================
const CAMPOS_MOTO = ['salario', 'auxilio_rodamiento', 'gasolina', 'bono_meta', 'horas_extra', 'auxilio_comunicacion'];

@Component({
  selector: 'app-costos-crear',
  imports: [FormsModule, RouterLink],
  template: `
    <div class="w-full max-w-4xl mx-auto">
      <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md border border-gray-100">
        <div class="mb-6 border-b pb-4">
          <h1 class="text-2xl sm:text-3xl font-bold text-gray-800"><i class="fas fa-motorcycle text-indigo-600 mr-2"></i> Costos Motorizados</h1>
          <p class="text-gray-500 mt-1">Gestión de costos operativos para técnicos de campo (Tabla Técnico).</p>
        </div>
        @if (r?.mensajeExito) {
          <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm"><i class="fas fa-check-circle mr-2"></i> {{ r.mensajeExito }}</div>
        }
        <form (ngSubmit)="guardar()" class="space-y-6">
          <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 w-full sm:w-1/3">
            <label for="mes_reporte" class="block text-sm font-bold text-gray-700 mb-1">Mes a Reportar <span class="text-red-500">*</span></label>
            <input type="month" id="mes_reporte" name="mes_reporte" required [(ngModel)]="mes" class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          <div class="overflow-x-auto shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
              <thead class="bg-gray-100">
                <tr>
                  <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sticky left-0 bg-gray-100 z-10">Motorizado</th>
                  <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32">Salario</th>
                  <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32">Aux. Rodamiento</th>
                  <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32">Gasolina</th>
                  <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32">Bono Meta</th>
                  <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32">Horas Extra</th>
                  <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32">Aux. Com</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 bg-white">
                @for (t of personal; track t.id) {
                  <tr class="hover:bg-gray-50 transition">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sticky left-0 bg-white z-10 border-r border-gray-200"><div class="flex flex-col"><span>{{ t.nombre }}</span><span class="text-xs text-gray-400">ID: {{ t.id }}</span></div></td>
                    @for (c of campos; track c) {
                      <td class="p-2"><input type="number" step="0.01" [name]="'c_' + t.id + '_' + c" [(ngModel)]="costos[t.id][c]" placeholder="0" class="w-24 border-gray-300 rounded-md text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></td>
                    }
                  </tr>
                } @empty {
                  <tr><td colspan="7" class="p-4 text-center text-gray-500">No se encontraron técnicos activos.</td></tr>
                }
              </tbody>
            </table>
          </div>
          <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
            <a routerLink="/inicio" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50">Cancelar</a>
            <button type="submit" class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transition-all transform hover:-translate-y-1"><i class="fas fa-save mr-2"></i> Guardar Costos</button>
          </div>
        </form>
      </div>
    </div>
  `
})
export class CostosCrear implements OnInit {
  private api = inject(Api);
  r: any = null;
  mes = mesHoy();
  personal: any[] = [];
  costos: Record<string, Record<string, any>> = {};
  campos = CAMPOS_MOTO;

  async ngOnInit() {
    this.aplicar(await this.api.get('costosCrear'), false);
  }
  private aplicar(r: any, conservar: boolean) {
    this.r = r;
    this.personal = r.listaPersonal || [];
    this.personal.forEach((t) => {
      if (!conservar || !this.costos[t.id]) this.costos[t.id] = {};
    });
  }
  async guardar() {
    const costos: Record<string, any> = {};
    this.personal.forEach((t) => {
      costos[t.id] = {};
      CAMPOS_MOTO.forEach((c) => (costos[t.id][c] = this.costos[t.id]?.[c] ?? ''));
    });
    this.aplicar(await this.api.postForm('costosCrear', 'index', { mes_reporte: this.mes, costos }), true);
  }
}

// =====================================================================================
// COSTOS MOTORIZADOS — EDITAR (costosEditarVista.php)
// =====================================================================================
@Component({
  selector: 'app-costos-editar',
  imports: [FormsModule, RouterLink, DecimalPipe],
  template: `
    <div class="w-full max-w-4xl mx-auto">
      <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md border border-gray-100">
        <div class="mb-6 border-b pb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800"><i class="fas fa-edit text-yellow-500 mr-2"></i> Gestión de Costos</h1>
            <p class="text-gray-500 mt-1">Visualizando y editando reporte del mes: <span class="font-bold text-indigo-600 text-lg">{{ mes }}</span></p>
          </div>
          <a routerLink="/costosVer" class="mt-4 sm:mt-0 px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition border border-gray-300 flex items-center"><i class="fas fa-arrow-left mr-2"></i> Volver al Listado</a>
        </div>
        @if (r?.mensajeExito) {
          <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center"><i class="fas fa-check-circle mr-2"></i> {{ r.mensajeExito }}</div>
        }
        @if (r?.errores?.length) {
          <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">@for (e of r.errores; track $index) {<p><i class="fas fa-times-circle mr-2"></i> {{ e }}</p>}</div>
        }
        <form (ngSubmit)="guardar()" class="space-y-6">
          <div class="overflow-x-auto shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
              <thead class="bg-gray-100">
                <tr>
                  <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sticky left-0 bg-gray-100 z-10 shadow-sm border-r">Motorizado</th>
                  <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32">Salario</th>
                  <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32">Rodamiento</th>
                  <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32">Gasolina</th>
                  <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32">Bono Meta</th>
                  <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32">Horas Extra</th>
                  <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32">Aux. Com</th>
                  <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32 bg-gray-50">Total Fila</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 bg-white">
                @for (d of datos; track d.id_costo) {
                  <tr class="hover:bg-gray-50 transition group">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sticky left-0 bg-white group-hover:bg-gray-50 z-10 border-r border-gray-200 shadow-sm"><div class="flex flex-col"><span>{{ d.nombre_tecnico }}</span></div></td>
                    @for (c of campos; track c) {
                      <td class="p-2"><input type="number" step="0.01" [name]="'e_' + d.id_costo + '_' + c" [(ngModel)]="d[c]" class="w-24 border-gray-300 rounded-md text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-right font-mono text-gray-600"></td>
                    }
                    <td class="p-2 text-right font-bold text-gray-900 bg-gray-50 border-l">\${{ totalFila(original[d.id_costo]) | number: '1.0-0' : 'en-US' }}</td>
                  </tr>
                }
              </tbody>
              <tfoot>
                <tr class="bg-gray-100 font-bold border-t border-gray-300">
                  <td class="py-3 px-4 text-right uppercase text-xs sticky left-0 bg-gray-100 z-10 border-r">Total General:</td>
                  <td colspan="6"></td>
                  <td class="py-3 px-4 text-right text-indigo-700">\${{ granTotal() | number: '1.0-0' : 'en-US' }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
          <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
            <a routerLink="/costosVer" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50 transition">Cancelar</a>
            <button type="submit" class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transition-all transform hover:-translate-y-1"><i class="fas fa-save mr-2"></i> Guardar Cambios</button>
          </div>
        </form>
      </div>
    </div>
  `
})
export class CostosEditar implements OnInit {
  private api = inject(Api);
  private router = inject(Router);
  private ruta = inject(ActivatedRoute);
  r: any = null;
  mes = '';
  datos: any[] = [];
  /** Totales calculados con lo guardado (como PHP, que los pinta al renderizar). */
  original: Record<string, any> = {};
  campos = CAMPOS_MOTO;

  async ngOnInit() {
    this.mes = this.ruta.snapshot.queryParamMap.get('mes') || '';
    this.aplicar(await this.api.get('costosEditar', 'index', { mes: this.mes }));
  }
  private aplicar(r: any) {
    if (r.redirigir) {
      this.router.navigate(['/' + r.redirigir]);
      return;
    }
    this.r = r;
    this.datos = (r.datosExistentes || []).map((d: any) => ({ ...d }));
    this.original = {};
    (r.datosExistentes || []).forEach((d: any) => (this.original[d.id_costo] = d));
  }
  totalFila(d: any) {
    return d ? CAMPOS_MOTO.reduce((a, c) => a + (Number(d[c]) || 0), 0) : 0;
  }
  granTotal() {
    return Object.values(this.original).reduce((a, d) => a + this.totalFila(d), 0);
  }
  async guardar() {
    const costos: Record<string, any> = {};
    this.datos.forEach((d) => {
      costos[d.id_costo] = {};
      CAMPOS_MOTO.forEach((c) => (costos[d.id_costo][c] = d[c] ?? ''));
    });
    this.aplicar(await this.api.postForm('costosEditar', 'index', { mes: this.mes, costos }));
  }
}

// =====================================================================================
// COSTOS ADMINISTRATIVOS — VER (costosAdministrativosVerVista.php)
// =====================================================================================
@Component({
  selector: 'app-costos-adm-ver',
  imports: [RouterLink, DecimalPipe, DtArriba, DtAbajo, Th],
  template: `
    <div class="w-full px-4 md:px-6">
      <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md border border-gray-100">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 border-b border-gray-100 pb-4">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800"><i class="fas fa-chart-line text-indigo-600 mr-2"></i> Histórico Administrativo</h1>
            <p class="text-gray-500 mt-1">Resumen mensual de nómina y gastos generales.</p>
          </div>
          <a routerLink="/costosAdministrativosCrear" class="mt-4 sm:mt-0 px-5 py-2.5 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transition-all duration-300 transform hover:scale-105 flex items-center space-x-2"><i class="fas fa-plus-circle"></i> <span>Nuevo Registro</span></a>
        </div>
        @if (cargado && dt.datos.length) {
          <app-dt-arriba [dt]="dt" />
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
              <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                <tr>
                  <th app-th [dt]="dt" campo="mes_reporte" class="py-3 px-4">Mes Reporte</th>
                  <th app-th [dt]="dt" campo="total_nomina" class="py-3 px-4 text-right">Nómina Admin</th>
                  <th app-th [dt]="dt" campo="total_gastos" class="py-3 px-4 text-right">Gastos Generales</th>
                  <th app-th [dt]="dt" campo="total_mes" class="py-3 px-4 text-right">Total Mes</th>
                  <th class="py-3 px-4 text-center">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                @for (f of dt.pagina; track f.mes_reporte) {
                  <tr class="hover:bg-gray-50 transition">
                    <td class="py-4 px-4"><div class="flex items-center space-x-2"><div class="p-2 bg-indigo-50 rounded-lg text-indigo-600"><i class="far fa-calendar-alt"></i></div><span class="font-bold text-gray-700 capitalize">{{ nombreMes(f.mes_reporte) }}</span></div></td>
                    <td class="py-4 px-4 text-right font-mono text-blue-600 font-medium">$ {{ +f.total_nomina | number: '1.2-2' : 'en-US' }}</td>
                    <td class="py-4 px-4 text-right font-mono text-orange-600 font-medium">$ {{ +f.total_gastos | number: '1.2-2' : 'en-US' }}</td>
                    <td class="py-4 px-4 text-right font-mono font-bold text-gray-900 bg-gray-50/50">$ {{ f.total_mes | number: '1.2-2' : 'en-US' }}</td>
                    <td class="py-4 px-4 text-center whitespace-nowrap">
                      <a routerLink="/costosAdministrativosEditar" [queryParams]="{ mes_reporte: f.mes_reporte }" class="inline-flex items-center justify-center px-3 py-1.5 bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200 transition-colors border border-yellow-200 text-xs font-semibold space-x-1" title="Ver detalles"><i class="fas fa-eye"></i> <span>Ver / Editar</span></a>
                      <button type="button" (click)="eliminarMes(f.mes_reporte)" class="inline-flex items-center justify-center px-3 py-1.5 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors border border-red-200 text-xs font-semibold ml-2" title="Eliminar Reporte Mensual"><i class="fas fa-trash"></i></button>
                    </td>
                  </tr>
                }
              </tbody>
              <tfoot>
                <tr>
                  <td class="py-3 px-4 text-right uppercase text-xs">Totales Generales:</td>
                  <td class="py-3 px-4 text-right font-mono text-indigo-900">{{ suma('total_nomina') }}</td>
                  <td class="py-3 px-4 text-right font-mono text-indigo-900">{{ suma('total_gastos') }}</td>
                  <td class="py-3 px-4 text-right font-mono text-indigo-900">{{ suma('total_mes') }}</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
          <app-dt-abajo [dt]="dt" />
        } @else if (cargado) {
          <div class="text-center p-10 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 mb-4"><i class="fas fa-file-invoice-dollar text-indigo-600 text-xl"></i></div>
            <h3 class="text-lg font-medium text-gray-900">No hay registros</h3>
            <p class="text-gray-500 mt-1">Aún no se han cargado costos administrativos.</p>
          </div>
        }
      </div>
    </div>
  `
})
export class CostosAdministrativosVer implements OnInit {
  private api = inject(Api);
  dt = new TablaDatos<any>([], 10);
  cargado = false;
  nombreMes = nombreMes;

  ngOnInit() {
    this.dt.campoOrden = 'mes_reporte';
    this.dt.ordenAsc = false;
    this.cargar();
  }
  async cargar() {
    const r: any = await this.api.get('costosAdministrativosVer');
    this.dt.set((r.reporteMensual || []).map((f: any) => ({ ...f, total_mes: (Number(f.total_nomina) || 0) + (Number(f.total_gastos) || 0) })));
    this.cargado = true;
  }
  suma(c: string) {
    return usd.format(this.dt.pagina.reduce((a, f: any) => a + (Number(f[c]) || 0), 0));
  }
  async eliminarMes(mes: string) {
    const n = nombreMes(mes);
    if (!confirm(`ATENCIÓN: ¿Estás seguro de eliminar TODO el reporte de ${n}?\n\nEsto borrará:\n- Gastos Generales\n- Nómina de Administrativos\n\n(Los técnicos NO se verán afectados)`)) return;
    await this.api.get('costosAdministrativosVer', 'eliminarMes', { mes });
    this.cargar();
  }
}

// =====================================================================================
// COSTOS ADMINISTRATIVOS — CREAR (costosAdministrativosCrearVista.php)
// =====================================================================================
@Component({
  selector: 'app-costos-adm-crear',
  imports: [FormsModule, DecimalPipe],
  template: `
    <div class="w-full max-w-4xl mx-auto space-y-6">
      <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-chart-pie text-purple-600 mr-2"></i> Gestión Costos Administrativos</h1>
          <p class="text-gray-500 text-sm">Administra la nómina de oficina y otros gastos generales.</p>
          <p class="text-red-500 text-s">Por favor guardar primero la Nómina Administrativa y posteriormente los Otros Gastos Generales.</p>
        </div>
        <div class="flex items-center gap-2 bg-purple-50 p-2 rounded-lg border border-purple-100">
          <label class="font-bold text-purple-800 text-sm">Mes de Reporte:</label>
          <input type="month" [ngModel]="mes" (ngModelChange)="cambiarMes($event)" class="border border-purple-200 rounded px-2 py-1 text-sm focus:ring-purple-500 focus:border-purple-500 text-purple-900 bg-white">
        </div>
      </div>
      ${MENSAJES}
      <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
          <h2 class="font-bold text-lg text-gray-800"><i class="fas fa-users text-blue-500 mr-2"></i> 1. Nómina Administrativa</h2>
          <p class="text-xs text-gray-500">Ingresa salarios y bonos para Jefes, Coordinadores y Auxiliares.</p>
        </div>
        <form (ngSubmit)="guardarNomina()" class="p-0">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-white">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Funcionario</th>
                  <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase w-32">Salario</th>
                  <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase w-32">Bono</th>
                  <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase w-32">Horas Extra</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 bg-white">
                @for (u of r?.listaPersonal || []; track u.id) {
                  <tr class="hover:bg-blue-50 transition">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-100"><div class="flex flex-col"><span>{{ u.nombre }}</span><span class="text-xs text-blue-600">{{ u.cargo }}</span></div></td>
                    @for (c of ['salario', 'bono_meta', 'horas_extra']; track c) {
                      <td class="p-2"><input type="number" step="0.01" [name]="'n_' + u.id + '_' + c" [(ngModel)]="nomina[u.id][c]" placeholder="0" class="w-full border-gray-300 rounded text-sm focus:ring-blue-500"></td>
                    }
                  </tr>
                } @empty {
                  <tr><td colspan="5" class="p-4 text-center text-sm text-gray-500">No hay usuarios administrativos activos.</td></tr>
                }
              </tbody>
            </table>
          </div>
          <div class="bg-gray-50 px-6 py-3 text-right"><button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold shadow hover:bg-blue-700 text-sm">Guardar Nómina</button></div>
        </form>
      </div>
      <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
          <div>
            <h2 class="font-bold text-lg text-gray-800"><i class="fas fa-receipt text-orange-500 mr-2"></i> 2. Otros Gastos Generales</h2>
            <p class="text-xs text-gray-500">Registra costos asociados a la administración.</p>
          </div>
          <span class="bg-orange-100 text-orange-800 text-xs font-bold px-3 py-1 rounded-full">Total Gastos: $ {{ (r?.totalGastos || 0) | number: '1.2-2' : 'en-US' }}</span>
        </div>
        <div class="p-6 border-b border-gray-100">
          <form (ngSubmit)="guardarGasto()" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-3">
              <label class="block text-xs font-bold text-gray-700 mb-1">Categoría</label>
              <select name="categoria" [(ngModel)]="gasto.categoria" class="w-full border-gray-300 rounded-md text-sm">
                <option value="Software">Software/Tecnología</option>
                <option value="Administrativo">Administrativo</option>
                <option value="Servicios">Servicios Públicos</option>
                <option value="Infraestructura">Infraestructura</option>
                <option value="Otros">Otros</option>
              </select>
            </div>
            <div class="md:col-span-6">
              <label class="block text-xs font-bold text-gray-700 mb-1">Concepto</label>
              <input type="text" name="concepto" [(ngModel)]="gasto.concepto" required placeholder="Ej: Pagó Software Motorizados" class="w-full border-gray-300 rounded-md text-sm">
            </div>
            <div class="md:col-span-2">
              <label class="block text-xs font-bold text-gray-700 mb-1">Valor</label>
              <input type="number" step="0.01" name="valor" [(ngModel)]="gasto.valor" required placeholder="0" class="w-full border-gray-300 rounded-md text-sm">
            </div>
            <div class="md:col-span-1"><button type="submit" class="w-full bg-orange-500 text-white h-9 rounded-md hover:bg-orange-600 shadow"><i class="fas fa-plus"></i></button></div>
          </form>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-white">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-bold text-gray-400 uppercase">Categoría</th>
                <th class="px-6 py-3 text-left text-xs font-bold text-gray-400 uppercase">Concepto</th>
                <th class="px-6 py-3 text-right text-xs font-bold text-gray-400 uppercase">Valor</th>
                <th class="px-6 py-3 text-center text-xs font-bold text-gray-400 uppercase">Acción</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
              @for (g of r?.listaGastos || []; track g.id_gasto) {
                <tr class="hover:bg-gray-50">
                  <td class="px-6 py-3 text-sm text-gray-500"><span class="bg-gray-100 px-2 py-0.5 rounded text-xs">{{ g.categoria }}</span></td>
                  <td class="px-6 py-3 text-sm font-medium text-gray-800">{{ g.concepto }}</td>
                  <td class="px-6 py-3 text-sm text-gray-800 text-right font-mono">$ {{ +g.valor | number: '1.2-2' : 'en-US' }}</td>
                  <td class="px-6 py-3 text-center"><button type="button" (click)="eliminar(g.id_gasto)" class="text-red-400 hover:text-red-600"><i class="fas fa-trash"></i></button></td>
                </tr>
              } @empty {
                <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400 text-sm">No hay gastos generales registrados este mes.</td></tr>
              }
            </tbody>
          </table>
        </div>
      </div>
    </div>
  `
})
export class CostosAdministrativosCrear implements OnInit {
  private api = inject(Api);
  private ruta = inject(ActivatedRoute);
  private router = inject(Router);
  r: any = null;
  mes = mesHoy();
  nomina: Record<string, Record<string, any>> = {};
  gasto: any = { categoria: 'Software', concepto: '', valor: '' };

  ngOnInit() {
    this.mes = this.ruta.snapshot.queryParamMap.get('mes_reporte') || mesHoy();
    this.cargar();
  }
  private aplicar(r: any, limpiarNomina = true) {
    this.r = r;
    this.mes = r.mesSeleccionado || this.mes;
    (r.listaPersonal || []).forEach((u: any) => {
      if (limpiarNomina || !this.nomina[u.id]) this.nomina[u.id] = {};
    });
  }
  async cargar(extra: Record<string, any> = {}) {
    this.aplicar(await this.api.get('costosAdministrativosCrear', 'index', { mes_reporte: this.mes, ...extra }));
  }
  cambiarMes(m: string) {
    this.mes = m;
    this.router.navigate([], { queryParams: { mes_reporte: m }, replaceUrl: true });
    this.cargar();
  }
  async eliminar(id: any) {
    if (!confirm('¿Borrar?')) return;
    this.cargar({ eliminar_id: id });
  }
  async guardarNomina() {
    const costos: Record<string, any> = {};
    (this.r?.listaPersonal || []).forEach((u: any) => {
      costos[u.id] = {};
      ['salario', 'bono_meta', 'horas_extra'].forEach((c) => (costos[u.id][c] = this.nomina[u.id]?.[c] ?? ''));
    });
    this.aplicar(await this.api.postForm('costosAdministrativosCrear', 'guardar_nomina', { mes_reporte: this.mes, costos }));
  }
  async guardarGasto() {
    const r: any = await this.api.postForm('costosAdministrativosCrear', 'guardar_gasto', { mes_reporte: this.mes, ...this.gasto });
    if (!r.errores?.length) this.gasto = { categoria: 'Software', concepto: '', valor: '' };
    this.aplicar(r, false);
  }
}

// =====================================================================================
// COSTOS ADMINISTRATIVOS — EDITAR (costosAdministrativosEditarVista.php)
// =====================================================================================
@Component({
  selector: 'app-costos-adm-editar',
  imports: [FormsModule, RouterLink],
  template: `
    <div class="w-full max-w-4xl mx-auto space-y-6">
      <div class="bg-white p-6 rounded-xl shadow-md border border-yellow-100 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-4 w-full md:w-auto">
          <a routerLink="/costosAdministrativosVer" class="bg-gray-100 hover:bg-gray-200 text-gray-600 w-10 h-10 flex items-center justify-center rounded-full transition shadow-sm" title="Volver al reporte"><i class="fas fa-arrow-left"></i></a>
          <div>
            <h1 class="text-2xl font-bold text-gray-800 leading-tight"><i class="fas fa-edit text-yellow-600 mr-2"></i> Editar Costos</h1>
            <p class="text-gray-500 text-sm">Modifica valores masivamente.</p>
          </div>
        </div>
        <div class="flex items-center gap-2 bg-yellow-50 p-2 rounded-lg border border-yellow-200 w-full md:w-auto justify-center">
          <label class="font-bold text-yellow-800 text-sm whitespace-nowrap">Editando Mes:</label>
          <input type="month" [ngModel]="mes" (ngModelChange)="cambiarMes($event)" class="border border-yellow-200 rounded px-2 py-1 text-sm focus:ring-yellow-500 focus:border-yellow-500 text-yellow-900 bg-white">
        </div>
      </div>
      @if (r?.mensajeExito) {
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm flex items-center justify-between">
          <div class="flex items-center"><i class="fas fa-check-circle mr-2"></i> {{ r.mensajeExito }}</div>
          <a routerLink="/costosAdministrativosCrear" [queryParams]="{ mes_reporte: mes }" class="text-sm font-bold underline hover:text-green-900">Volver a ver todo &rarr;</a>
        </div>
      }
      @if (r?.errores?.length) {
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm"><ul class="list-disc list-inside">@for (e of r.errores; track $index) {<li>{{ e }}</li>}</ul></div>
      }
      <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
          <h2 class="font-bold text-lg text-gray-800"><i class="fas fa-users-cog text-blue-500 mr-2"></i> Nómina (Edición Masiva)</h2>
          <span class="text-xs text-gray-400 hidden sm:block">Cambia los valores y guarda todo al final</span>
        </div>
        <form (ngSubmit)="actualizarNomina()">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-blue-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Funcionario</th>
                  <th class="px-2 py-3 text-left text-xs font-bold text-gray-500 uppercase w-32">Salario</th>
                  <th class="px-2 py-3 text-left text-xs font-bold text-gray-500 uppercase w-32">Bono Meta</th>
                  <th class="px-2 py-3 text-left text-xs font-bold text-gray-500 uppercase w-24">H. Extra</th>
                  <th class="px-2 py-3 text-left text-xs font-bold text-gray-500 uppercase w-24">Aux. Com.</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 bg-white">
                @for (u of nomina; track u.id) {
                  <tr class="hover:bg-blue-50 transition">
                    <td class="px-4 py-3 whitespace-nowrap"><div class="text-sm font-medium text-gray-900">{{ u.nombre }}</div><div class="text-xs text-gray-500">{{ u.cargo }}</div></td>
                    @for (c of ['salario', 'bono_meta', 'horas_extra', 'auxilio_comunicacion']; track c) {
                      <td class="p-2"><input type="number" step="0.01" [name]="'n_' + u.id + '_' + c" [(ngModel)]="u[c]" class="w-full border-gray-300 rounded text-sm focus:ring-blue-500 bg-gray-50 focus:bg-white transition"></td>
                    }
                  </tr>
                } @empty {
                  <tr><td colspan="5" class="p-4 text-center text-gray-500">No se encontró personal para este mes.</td></tr>
                }
              </tbody>
            </table>
          </div>
          <div class="bg-gray-50 px-6 py-3 flex justify-end items-center gap-3 border-t border-gray-200">
            <a routerLink="/costosAdministrativosCrear" [queryParams]="{ mes_reporte: mes }" class="text-gray-500 hover:text-gray-700 text-sm font-medium">Cancelar</a>
            <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded shadow hover:bg-blue-700 text-sm font-bold transition flex items-center"><i class="fas fa-save mr-2"></i> Actualizar Nómina</button>
          </div>
        </form>
      </div>
      <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
          <h2 class="font-bold text-lg text-gray-800"><i class="fas fa-receipt text-orange-500 mr-2"></i> Gastos Generales (Edición Masiva)</h2>
          <p class="text-xs text-gray-500">Corrige conceptos o valores ya registrados.</p>
        </div>
        @if (gastos.length) {
          <form (ngSubmit)="actualizarGastos()">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-orange-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase w-40">Categoría</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Concepto</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase w-32">Valor</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase w-16">Borrar</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                  @for (g of gastos; track g.id_gasto) {
                    <tr class="hover:bg-orange-50 transition">
                      <td class="p-2">
                        <select [name]="'gc_' + g.id_gasto" [(ngModel)]="g.categoria" class="w-full border-gray-300 rounded text-sm focus:ring-orange-500 bg-gray-50">
                          <option value="Software">Software</option>
                          <option value="Administrativo">Admin</option>
                          <option value="Servicios">Servicios</option>
                          <option value="Infraestructura">Infra.</option>
                          <option value="Otros">Otros</option>
                        </select>
                      </td>
                      <td class="p-2"><input type="text" [name]="'gn_' + g.id_gasto" [(ngModel)]="g.concepto" class="w-full border-gray-300 rounded text-sm focus:ring-orange-500 bg-gray-50"></td>
                      <td class="p-2"><input type="number" step="0.01" [name]="'gv_' + g.id_gasto" [(ngModel)]="g.valor" class="w-full border-gray-300 rounded text-sm focus:ring-orange-500 font-mono text-right bg-gray-50"></td>
                      <td class="p-2 text-center align-middle"><button type="button" (click)="eliminar(g.id_gasto)" class="text-red-400 hover:text-red-600 transition"><i class="fas fa-trash-alt"></i></button></td>
                    </tr>
                  }
                </tbody>
              </table>
            </div>
            <div class="bg-gray-50 px-6 py-3 flex justify-end items-center gap-3 border-t border-gray-200">
              <a routerLink="/costosAdministrativosCrear" [queryParams]="{ mes_reporte: mes }" class="text-gray-500 hover:text-gray-700 text-sm font-medium">Cancelar</a>
              <button type="submit" class="bg-orange-600 text-white px-5 py-2 rounded shadow hover:bg-orange-700 text-sm font-bold transition flex items-center"><i class="fas fa-save mr-2"></i> Actualizar Gastos</button>
            </div>
          </form>
        } @else if (r) {
          <div class="p-8 text-center text-gray-400 bg-white">
            <i class="fas fa-folder-open text-4xl mb-2 opacity-50"></i>
            <p>No hay gastos registrados para editar en este mes.</p>
            <a routerLink="/costosAdministrativosCrear" class="text-blue-500 hover:underline mt-2 inline-block">Volver a crear</a>
          </div>
        }
      </div>
    </div>
  `
})
export class CostosAdministrativosEditar implements OnInit {
  private api = inject(Api);
  private ruta = inject(ActivatedRoute);
  private router = inject(Router);
  r: any = null;
  mes = mesHoy();
  nomina: any[] = [];
  gastos: any[] = [];

  ngOnInit() {
    this.mes = this.ruta.snapshot.queryParamMap.get('mes_reporte') || mesHoy();
    this.cargar();
  }
  private aplicar(r: any) {
    this.r = r;
    this.mes = r.mesSeleccionado || this.mes;
    this.nomina = (r.listaNomina || []).map((u: any) => ({ ...u }));
    this.gastos = (r.listaGastos || []).map((g: any) => ({ ...g }));
  }
  async cargar(extra: Record<string, any> = {}) {
    this.aplicar(await this.api.get('costosAdministrativosEditar', 'index', { mes_reporte: this.mes, ...extra }));
  }
  cambiarMes(m: string) {
    this.mes = m;
    this.router.navigate([], { queryParams: { mes_reporte: m }, replaceUrl: true });
    this.cargar();
  }
  eliminar(id: any) {
    if (!confirm('¿Eliminar este gasto permanentemente?')) return;
    this.cargar({ eliminar_gasto_id: id });
  }
  async actualizarNomina() {
    const nomina: Record<string, any> = {};
    this.nomina.forEach((u) => (nomina[u.id] = { salario: u.salario ?? '', bono_meta: u.bono_meta ?? '', horas_extra: u.horas_extra ?? '', auxilio_comunicacion: u.auxilio_comunicacion ?? '' }));
    this.aplicar(await this.api.postForm('costosAdministrativosEditar', 'actualizar_nomina', { mes_reporte: this.mes, nomina }));
  }
  async actualizarGastos() {
    const gastos_generales: Record<string, any> = {};
    this.gastos.forEach((g) => (gastos_generales[g.id_gasto] = { categoria: g.categoria ?? '', concepto: g.concepto ?? '', valor: g.valor ?? '' }));
    this.aplicar(await this.api.postForm('costosAdministrativosEditar', 'actualizar_gastos_generales', { mes_reporte: this.mes, gastos_generales }));
  }
}
