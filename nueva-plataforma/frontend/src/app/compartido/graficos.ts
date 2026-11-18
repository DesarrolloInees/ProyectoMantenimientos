import { DecimalPipe } from '@angular/common';
import { Component, computed, input } from '@angular/core';

export interface Barra {
  etiqueta: string;
  valor: number;
  detalle?: string;
  color?: string;
}

/** Barras horizontales sin librerías (se imprimen bien en PDF desde el navegador). */
@Component({
  selector: 'app-barras',
  imports: [DecimalPipe],
  template: `
    @if (titulo()) { <h3>{{ titulo() }}</h3> }
    <div class="barras">
      @for (b of barras(); track b.etiqueta) {
        <div class="fila" [title]="b.detalle ?? ''">
          <span class="etq">{{ b.etiqueta }}</span>
          <span class="pista"><span class="barra" [style.width.%]="(b.valor / maximo()) * 100" [style.background]="b.color ?? color()"></span></span>
          <span class="val">{{ b.valor | number: '1.0-1' }}{{ sufijo() }}</span>
        </div>
      } @empty {
        <div class="vacio">Sin datos</div>
      }
    </div>
  `,
  styles: `
    h3 { font-size: 15px; margin: 0 0 10px; }
    .fila { display: grid; grid-template-columns: minmax(90px, 36%) 1fr auto; gap: 10px; align-items: center; padding: 3px 0; font-size: 13px; }
    .etq { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--tinta-2); }
    .pista { background: var(--fondo); border-radius: 2px; height: 12px; overflow: hidden; }
    /* el extremo de cada barra lleva el corte diagonal del logo */
    .barra { display: block; height: 100%; min-width: 3px; border-radius: 2px 0 0 2px; clip-path: polygon(0 0, 100% 0, calc(100% - 6px) 100%, 0 100%);
      transition: width 0.3s var(--curva); print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    .val { font-variant-numeric: tabular-nums; font-weight: 600; min-width: 44px; text-align: right; color: var(--marca-noche); }
  `,
})
export class Barras {
  readonly barras = input<Barra[]>([]);
  readonly titulo = input<string | null>(null);
  readonly color = input('#0b5591');
  readonly sufijo = input('');
  protected readonly maximo = computed(() => Math.max(1, ...this.barras().map((b) => b.valor)));
}

/** Tabla cruzada (fila × columna → valor) con totales y calor por celda. */
@Component({
  selector: 'app-matriz',
  template: `
    @if (titulo()) { <h3>{{ titulo() }}</h3> }
    <div class="desliza">
      <table class="tabla compacta">
        <thead>
          <tr><th>{{ nombreFila() }}</th>@for (c of columnas(); track c) { <th class="num">{{ c }}</th> }<th class="num">Total</th></tr>
        </thead>
        <tbody>
          @for (f of filas(); track f) {
            <tr>
              <td>{{ f }}</td>
              @for (c of columnas(); track c) {
                <td class="num" [style.background]="calor(valor(f, c))">{{ valor(f, c) || '' }}</td>
              }
              <td class="num"><strong>{{ totalFila(f) }}</strong></td>
            </tr>
          } @empty {
            <tr><td [attr.colspan]="columnas().length + 2" class="vacio">Sin datos</td></tr>
          }
        </tbody>
      </table>
    </div>
  `,
  styles: `
    h3 { font-size: 15px; margin: 0 0 10px; }
    .desliza { overflow-x: auto; }
    td { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
  `,
})
export class Matriz {
  readonly datos = input<{ fila: unknown; columna: unknown; valor: unknown }[]>([]);
  readonly titulo = input<string | null>(null);
  readonly nombreFila = input('');
  protected readonly filas = computed(() => [...new Set(this.datos().map((d) => String(d.fila)))].sort());
  protected readonly columnas = computed(() => [...new Set(this.datos().map((d) => String(d.columna)))].sort());
  private readonly mapa = computed(() => {
    const m = new Map<string, number>();
    for (const d of this.datos()) m.set(`${d.fila}|${d.columna}`, Number(d.valor) || 0);
    return m;
  });
  private readonly maximo = computed(() => Math.max(1, ...this.mapa().values()));

  protected valor(f: string, c: string): number {
    return this.mapa().get(`${f}|${c}`) ?? 0;
  }

  protected totalFila(f: string): number {
    return this.columnas().reduce((s, c) => s + this.valor(f, c), 0);
  }

  protected calor(v: number): string {
    if (!v) return 'transparent';
    return `rgb(11 85 145 / ${0.07 + (v / this.maximo()) * 0.4})`;
  }
}

/** Tarjeta de indicador. */
@Component({
  selector: 'app-kpi',
  template: `<div class="kpi" [class.alerta]="alerta()"><span class="v">{{ valor() }}</span><span class="t">{{ texto() }}</span>
    @if (avance() !== null) {
      <div class="avance" [class.alerta]="alerta()" role="img" [attr.aria-label]="avance() + ' %'">
        <span [style.width.%]="limitar(avance()!)"></span>
      </div>
    }
    @if (detalle()) { <span class="d">{{ detalle() }}</span> }</div>`,
  styles: `
    .kpi { background: #fff; border: 1px solid var(--linea); border-radius: 12px; padding: 14px 16px 15px; display: flex; flex-direction: column;
      gap: 3px; box-shadow: var(--sombra-1); height: 100%; }
    .v { font-size: 26px; line-height: 1.1; font-weight: 600; font-stretch: 108%; letter-spacing: -0.02em; color: var(--marca-noche);
      font-variant-numeric: tabular-nums; }
    .t { color: var(--tinta-2); font-size: 13px; }
    .d { font-size: 12px; color: var(--mut); }
    .kpi.alerta { background: var(--aviso-fondo); border-color: #f1d9a6; }
    .kpi.alerta .v { color: var(--aviso); }
    .avance { margin: 6px 0 2px; }
  `,
})
export class Kpi {
  readonly valor = input<string | number>('');
  readonly texto = input('');
  readonly detalle = input<string | null>(null);
  readonly alerta = input(false);
  /** Porcentaje de avance hacia una meta: se dibuja con el corte diagonal de la marca. */
  readonly avance = input<number | null>(null);

  protected limitar(v: number): number {
    return Math.max(0, Math.min(100, v));
  }
}
