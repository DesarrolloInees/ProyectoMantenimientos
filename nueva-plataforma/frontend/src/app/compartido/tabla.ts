import { DatePipe, DecimalPipe } from '@angular/common';
import { Component, computed, input, signal } from '@angular/core';
import { pesos } from '../core/rest';

export interface Columna {
  clave: string;
  titulo: string;
  tipo?: 'texto' | 'num' | 'pesos' | 'fecha' | 'pct' | 'si';
}

/** Tabla de solo lectura para reportes: columnas declaradas, filtro de texto y paginación local. */
@Component({
  selector: 'app-tabla',
  imports: [DatePipe, DecimalPipe],
  template: `
    @if (filtrable()) {
      <input class="filtro no-imprimir" placeholder="Filtrar…" [value]="texto()" (input)="texto.set($any($event.target).value)" />
      <span class="muted"> {{ filtradas().length }} filas</span>
    }
    <div class="contenedor-tabla">
      <table class="tabla compacta">
        <thead><tr>@for (c of columnas(); track c.clave) { <th [class.num]="esNum(c)">{{ c.titulo }}</th> }</tr></thead>
        <tbody>
          @for (f of visibles(); track $index) {
            <tr>
              @for (c of columnas(); track c.clave) {
                <td [class.num]="esNum(c)">
                  @switch (c.tipo) {
                    @case ('pesos') { {{ pesos(f[c.clave]) }} }
                    @case ('fecha') { {{ f[c.clave] | date: 'dd/MM/yy' }} }
                    @case ('pct') { {{ f[c.clave] | number: '1.0-1' }} % }
                    @case ('num') { {{ f[c.clave] | number: '1.0-2' }} }
                    @case ('si') { {{ f[c.clave] ? 'Sí' : 'No' }} }
                    @default { {{ f[c.clave] }} }
                  }
                </td>
              }
            </tr>
          } @empty { <tr><td [attr.colspan]="columnas().length" class="vacio">Sin datos</td></tr> }
        </tbody>
      </table>
      @if (filtradas().length > limite()) {
        <div class="paginador no-imprimir">Mostrando {{ limite() }} de {{ filtradas().length }}
          <button (click)="limite.set(limite() + 500)">Ver más</button></div>
      }
    </div>
  `,
  styles: `.filtro { padding: 6px 10px; border: 1px solid var(--linea-2); border-radius: 6px; margin-bottom: 8px; width: 260px; font: inherit; }`,
})
export class Tabla {
  readonly filas = input<Record<string, any>[]>([]);
  readonly columnas = input.required<Columna[]>();
  readonly filtrable = input(true);
  protected readonly pesos = pesos;
  protected readonly texto = signal('');
  protected readonly limite = signal(500);
  protected readonly filtradas = computed(() => {
    const t = this.texto().trim().toLowerCase();
    if (!t) return this.filas();
    return this.filas().filter((f) => Object.values(f).some((v) => String(v ?? '').toLowerCase().includes(t)));
  });
  protected readonly visibles = computed(() => this.filtradas().slice(0, this.limite()));

  protected esNum(c: Columna): boolean {
    return c.tipo === 'num' || c.tipo === 'pesos' || c.tipo === 'pct';
  }
}
