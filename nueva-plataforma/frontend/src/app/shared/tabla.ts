import { Component, Input } from '@angular/core';
import { FormsModule } from '@angular/forms';

/**
 * Reemplazo de jQuery DataTables: búsqueda global, ordenamiento por columna
 * y paginación en memoria, con los textos en español que usaba la plataforma.
 */
export class TablaDatos<T = any> {
  datos: T[] = [];
  busqueda = '';
  porPagina = 10;
  paginaActual = 1;
  campoOrden: string | null = null;
  ordenAsc = true;
  private cacheFiltrados: T[] | null = null;

  constructor(datos: T[] = [], porPagina = 10, private camposBusqueda?: string[]) {
    this.datos = datos;
    this.porPagina = porPagina;
  }

  set(datos: T[]) {
    this.datos = datos || [];
    this.cacheFiltrados = null;
    const tp = this.totalPaginas;
    if (this.paginaActual > tp) this.paginaActual = Math.max(1, tp);
  }

  refrescar() {
    this.cacheFiltrados = null;
  }

  buscar(t: string) {
    this.busqueda = t;
    this.paginaActual = 1;
    this.cacheFiltrados = null;
  }

  cambiarPorPagina(n: number) {
    this.porPagina = Number(n);
    this.paginaActual = 1;
  }

  ordenar(campo: string) {
    if (this.campoOrden === campo) this.ordenAsc = !this.ordenAsc;
    else {
      this.campoOrden = campo;
      this.ordenAsc = true;
    }
    this.cacheFiltrados = null;
  }

  icono(campo: string): string {
    if (this.campoOrden !== campo) return 'fa-sort text-gray-300';
    return this.ordenAsc ? 'fa-sort-up text-blue-600' : 'fa-sort-down text-blue-600';
  }

  private static norm(v: any): string {
    return String(v ?? '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[̀-ͯ]/g, '');
  }

  get filtrados(): T[] {
    if (this.cacheFiltrados) return this.cacheFiltrados;
    let r = this.datos;
    const q = TablaDatos.norm(this.busqueda.trim());
    if (q) {
      const palabras = q.split(/\s+/);
      r = r.filter((fila: any) => {
        const texto = this.camposBusqueda
          ? this.camposBusqueda.map((c) => TablaDatos.norm(fila?.[c])).join(' ')
          : Object.values(fila ?? {})
              .map((v) => (typeof v === 'object' ? '' : TablaDatos.norm(v)))
              .join(' ');
        return palabras.every((p) => texto.includes(p));
      });
    }
    if (this.campoOrden) {
      const c = this.campoOrden;
      const dir = this.ordenAsc ? 1 : -1;
      r = [...r].sort((a: any, b: any) => {
        const va = a?.[c];
        const vb = b?.[c];
        const na = Number(va);
        const nb = Number(vb);
        if (va !== null && vb !== null && va !== '' && vb !== '' && !isNaN(na) && !isNaN(nb)) return (na - nb) * dir;
        return String(va ?? '').localeCompare(String(vb ?? ''), 'es', { numeric: true }) * dir;
      });
    }
    this.cacheFiltrados = r;
    return r;
  }

  get total(): number {
    return this.filtrados.length;
  }

  get totalSinFiltro(): number {
    return this.datos.length;
  }

  get totalPaginas(): number {
    return this.porPagina <= 0 ? 1 : Math.max(1, Math.ceil(this.total / this.porPagina));
  }

  get pagina(): T[] {
    if (this.porPagina <= 0) return this.filtrados;
    const ini = (this.paginaActual - 1) * this.porPagina;
    return this.filtrados.slice(ini, ini + this.porPagina);
  }

  get desde(): number {
    return this.total === 0 ? 0 : (this.paginaActual - 1) * (this.porPagina <= 0 ? this.total : this.porPagina) + 1;
  }

  get hasta(): number {
    if (this.porPagina <= 0) return this.total;
    return Math.min(this.paginaActual * this.porPagina, this.total);
  }

  ir(p: number) {
    if (p < 1 || p > this.totalPaginas) return;
    this.paginaActual = p;
  }

  get botones(): (number | string)[] {
    const tp = this.totalPaginas;
    const p = this.paginaActual;
    if (tp <= 7) return Array.from({ length: tp }, (_, i) => i + 1);
    const r: (number | string)[] = [1];
    if (p > 4) r.push('…');
    for (let i = Math.max(2, p - 1); i <= Math.min(tp - 1, p + 1); i++) r.push(i);
    if (p < tp - 3) r.push('…');
    r.push(tp);
    return r;
  }
}

@Component({
  selector: 'app-dt-arriba',
  imports: [FormsModule],
  template: `
    <div class="dt-top mb-3">
      <label class="flex items-center gap-2">
        Mostrar
        <select class="dt-length" [ngModel]="dt.porPagina" (ngModelChange)="dt.cambiarPorPagina($event)">
          @for (o of opciones; track o) {
            <option [ngValue]="o">{{ o === -1 ? 'Todos' : o }}</option>
          }
        </select>
        registros
      </label>
      <label class="flex items-center gap-2">
        Buscar:
        <input type="search" class="dt-search" [ngModel]="dt.busqueda" (ngModelChange)="dt.buscar($event)" [placeholder]="placeholder" />
      </label>
    </div>
  `
})
export class DtArriba {
  @Input({ required: true }) dt!: TablaDatos<any>;
  @Input() opciones: number[] = [10, 25, 50, 100, -1];
  @Input() placeholder = '';
}

@Component({
  selector: 'app-dt-abajo',
  template: `
    <div class="dt-bottom mt-3">
      <div>
        @if (dt.total === 0) {
          Mostrando registros del 0 al 0 de un total de 0 registros
        } @else {
          Mostrando registros del {{ dt.desde }} al {{ dt.hasta }} de un total de {{ dt.total }} registros
          @if (dt.busqueda && dt.total !== dt.totalSinFiltro) {
            <span class="text-gray-400">(filtrado de un total de {{ dt.totalSinFiltro }} registros)</span>
          }
        }
      </div>
      <div class="dt-pag flex">
        <button type="button" class="rounded-l" [disabled]="dt.paginaActual <= 1" (click)="dt.ir(dt.paginaActual - 1)">Anterior</button>
        @for (b of dt.botones; track $index) {
          @if (b === '…') {
            <button type="button" disabled>…</button>
          } @else {
            <button type="button" [class.activo]="b === dt.paginaActual" (click)="dt.ir(+b)">{{ b }}</button>
          }
        }
        <button type="button" class="rounded-r" [disabled]="dt.paginaActual >= dt.totalPaginas" (click)="dt.ir(dt.paginaActual + 1)">Siguiente</button>
      </div>
    </div>
  `
})
export class DtAbajo {
  @Input({ required: true }) dt!: TablaDatos<any>;
}

/** Encabezado ordenable: <th app-th campo="x" [dt]="dt">Texto</th> */
@Component({
  selector: 'th[app-th]',
  host: { class: 'dt-sortable', '(click)': 'dt.ordenar(campo)' },
  template: `<ng-content></ng-content> <i class="fas ml-1 text-xs" [class]="dt.icono(campo)"></i>`
})
export class Th {
  @Input({ required: true }) dt!: TablaDatos<any>;
  @Input({ required: true }) campo!: string;
}

/**
 * DataTables "serverSide": el servidor pagina/filtra/ordena.
 * cargar(params) recibe {draw,start,length,search[value],order[0][column],order[0][dir]} y
 * devuelve {recordsTotal, recordsFiltered, data}.
 */
export class TablaServidor<T = any> extends TablaDatos<T> {
  totalRegistros = 0;
  totalFiltrados = 0;
  columnaOrden = 0;
  dirOrden: 'asc' | 'desc' = 'desc';
  cargando = false;
  private draw = 0;
  private temporizador: any;

  constructor(private cargarFn: (p: Record<string, any>) => Promise<any>, porPagina = 100, private columnas: string[] = []) {
    super([], porPagina);
  }

  override get filtrados(): T[] {
    return this.datos;
  }
  override get total(): number {
    return this.totalFiltrados;
  }
  override get totalSinFiltro(): number {
    return this.totalRegistros;
  }
  override get pagina(): T[] {
    return this.datos;
  }
  override get desde(): number {
    return this.totalFiltrados === 0 ? 0 : (this.paginaActual - 1) * this.porPagina + 1;
  }
  override get hasta(): number {
    return Math.min(this.paginaActual * this.porPagina, this.totalFiltrados);
  }

  override buscar(t: string) {
    this.busqueda = t;
    this.paginaActual = 1;
    clearTimeout(this.temporizador);
    this.temporizador = setTimeout(() => this.recargar(), 350);
  }

  override cambiarPorPagina(n: number) {
    this.porPagina = Number(n) <= 0 ? 1000000 : Number(n);
    this.paginaActual = 1;
    this.recargar();
  }

  override ir(p: number) {
    if (p < 1 || p > this.totalPaginas) return;
    this.paginaActual = p;
    this.recargar();
  }

  override ordenar(campo: string) {
    const idx = this.columnas.indexOf(campo);
    if (this.campoOrden === campo) this.dirOrden = this.dirOrden === 'asc' ? 'desc' : 'asc';
    else this.dirOrden = 'asc';
    this.campoOrden = campo;
    this.ordenAsc = this.dirOrden === 'asc';
    this.columnaOrden = idx < 0 ? 0 : idx;
    this.recargar();
  }

  async recargar() {
    this.cargando = true;
    const draw = ++this.draw;
    try {
      const r = await this.cargarFn({
        draw,
        start: (this.paginaActual - 1) * this.porPagina,
        length: this.porPagina,
        'search[value]': this.busqueda,
        'order[0][column]': this.columnaOrden,
        'order[0][dir]': this.dirOrden
      });
      if (draw !== this.draw) return;
      this.datos = r.data || [];
      this.totalRegistros = r.recordsTotal || 0;
      this.totalFiltrados = r.recordsFiltered || 0;
    } finally {
      this.cargando = false;
    }
  }
}

export const TABLA = [DtArriba, DtAbajo, Th] as const;
