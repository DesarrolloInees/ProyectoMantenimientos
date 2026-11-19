import { Component, ElementRef, EventEmitter, HostListener, Input, Output, ViewChild, forwardRef } from '@angular/core';
import { ControlValueAccessor, FormsModule, NG_VALUE_ACCESSOR } from '@angular/forms';

/**
 * Reemplazo de Select2: lista desplegable con búsqueda (simple o múltiple).
 * Uso: <app-select-buscar [opciones]="clientes" valor="id_cliente" texto="nombre_cliente" [(ngModel)]="x" />
 */
@Component({
  selector: 'app-select-buscar',
  imports: [FormsModule],
  providers: [{ provide: NG_VALUE_ACCESSOR, useExisting: forwardRef(() => SelectBuscar), multi: true }],
  template: `
    <div class="relative w-full">
      <button
        type="button"
        [disabled]="deshabilitado"
        (click)="alternar()"
        class="w-full text-left border border-gray-300 bg-white flex items-center justify-between gap-2 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
        [class]="(compacto ? 'rounded-[5px] px-1.5 h-[26px] text-[0.7rem] font-semibold ' : 'rounded px-3 py-2 ') + claseBoton"
      >
        <span class="truncate" [class.text-gray-400]="!tieneValor">{{ etiqueta }}</span>
        <span class="flex items-center gap-2">
          @if (tieneValor && limpiable && !deshabilitado) {
            <i class="fas fa-times text-gray-400 hover:text-red-500 text-xs" (click)="limpiar($event)"></i>
          }
          <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
        </span>
      </button>
      @if (abierto) {
        <div class="absolute left-0 mt-1 bg-white border border-gray-300 rounded shadow-lg z-[9500]" [class]="compacto ? 'min-w-[220px]' : 'right-0'">
          <div class="p-2 border-b border-gray-100">
            <input #buscador type="text" class="w-full border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" [(ngModel)]="filtro" (keydown.enter)="enterSeleccion($event)" placeholder="Buscar..." />
          </div>
          <ul class="sb-panel text-sm">
            @if (!multiple && permitirVacio) {
              <li class="px-3 py-2 cursor-pointer hover:bg-blue-600 hover:text-white text-gray-400" (click)="elegir(null)">{{ placeholder }}</li>
            }
            @for (o of filtradas; track $index) {
              <li class="px-3 py-2 cursor-pointer hover:bg-blue-600 hover:text-white flex items-center gap-2" [class.bg-blue-50]="estaSeleccionado(o)" [class]="claseOpcion ? claseOpcion(o) : ''" (click)="elegir(o)">
                @if (multiple) {
                  <i class="far" [class.fa-check-square]="estaSeleccionado(o)" [class.fa-square]="!estaSeleccionado(o)"></i>
                }
                {{ textoDe(o) }}
              </li>
            } @empty {
              <li class="px-3 py-2 text-gray-400">No se encontraron resultados</li>
            }
          </ul>
        </div>
      }
    </div>
  `
})
export class SelectBuscar implements ControlValueAccessor {
  @Input() opciones: any[] = [];
  @Input() valor = 'id';
  @Input() texto: string | ((o: any) => string) = 'nombre';
  @Input() placeholder = 'Seleccione...';
  @Input() multiple = false;
  @Input() permitirVacio = true;
  @Input() limpiable = true;
  @Input() claseBoton = '';
  @Input() maxResultados = 300;
  @Input() compacto = false;
  /** Estilo por opción (ej. inactivos en rojo) */
  @Input() claseOpcion: ((o: any) => string) | null = null;
  @Output() cambio = new EventEmitter<any>();
  /** Se emite al abrir el desplegable (equivalente a select2:opening) */
  @Output() abrir = new EventEmitter<void>();
  @ViewChild('buscador') buscador?: ElementRef<HTMLInputElement>;

  abierto = false;
  filtro = '';
  deshabilitado = false;
  private actual: any = null;
  private onChange: (v: any) => void = () => {};
  private onTouched: () => void = () => {};

  constructor(private el: ElementRef) {}

  @HostListener('document:click', ['$event'])
  clickFuera(e: MouseEvent) {
    if (this.abierto && !this.el.nativeElement.contains(e.target)) this.abierto = false;
  }

  textoDe(o: any): string {
    if (o == null) return '';
    if (typeof o !== 'object') return String(o);
    return typeof this.texto === 'function' ? this.texto(o) : String(o[this.texto] ?? '');
  }

  valorDe(o: any): any {
    if (o == null) return null;
    if (typeof o !== 'object') return o;
    return o[this.valor];
  }

  get tieneValor(): boolean {
    return this.multiple ? Array.isArray(this.actual) && this.actual.length > 0 : this.actual !== null && this.actual !== undefined && this.actual !== '';
  }

  get etiqueta(): string {
    if (!this.tieneValor) return this.placeholder;
    if (this.multiple) {
      const sel = (this.opciones || []).filter((o) => (this.actual as any[]).some((v) => String(v) === String(this.valorDe(o))));
      return sel.map((o) => this.textoDe(o)).join(', ');
    }
    const o = (this.opciones || []).find((x) => String(this.valorDe(x)) === String(this.actual));
    return o ? this.textoDe(o) : this.placeholder;
  }

  get filtradas(): any[] {
    const q = this.filtro
      .toLowerCase()
      .normalize('NFD')
      .replace(/[̀-ͯ]/g, '')
      .trim();
    const base = this.opciones || [];
    const r = q
      ? base.filter((o) =>
          this.textoDe(o)
            .toLowerCase()
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '')
            .includes(q)
        )
      : base;
    return r.slice(0, this.maxResultados);
  }

  estaSeleccionado(o: any): boolean {
    const v = this.valorDe(o);
    if (this.multiple) return Array.isArray(this.actual) && this.actual.some((x) => String(x) === String(v));
    return String(this.actual) === String(v);
  }

  alternar() {
    if (this.deshabilitado) return;
    this.abierto = !this.abierto;
    if (this.abierto) {
      this.abrir.emit();
      this.filtro = '';
      setTimeout(() => this.buscador?.nativeElement.focus(), 0);
    }
    this.onTouched();
  }

  enterSeleccion(e: Event) {
    e.preventDefault();
    const f = this.filtradas;
    if (f.length) this.elegir(f[0]);
  }

  elegir(o: any) {
    if (this.multiple) {
      const v = this.valorDe(o);
      const arr = Array.isArray(this.actual) ? [...this.actual] : [];
      const i = arr.findIndex((x) => String(x) === String(v));
      if (i >= 0) arr.splice(i, 1);
      else arr.push(v);
      this.actual = arr;
    } else {
      this.actual = o == null ? '' : this.valorDe(o);
      this.abierto = false;
    }
    this.onChange(this.actual);
    this.cambio.emit(this.actual);
  }

  limpiar(e: Event) {
    e.stopPropagation();
    this.actual = this.multiple ? [] : '';
    this.onChange(this.actual);
    this.cambio.emit(this.actual);
  }

  writeValue(v: any): void {
    this.actual = this.multiple ? (Array.isArray(v) ? v : v ? [v] : []) : v;
  }
  registerOnChange(fn: any): void {
    this.onChange = fn;
  }
  registerOnTouched(fn: any): void {
    this.onTouched = fn;
  }
  setDisabledState(d: boolean): void {
    this.deshabilitado = d;
  }
}
