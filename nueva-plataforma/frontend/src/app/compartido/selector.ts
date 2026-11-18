import { Component, effect, inject, input, model, output, signal, untracked } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatAutocompleteModule } from '@angular/material/autocomplete';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { Opcion, Rest } from '../core/rest';

/**
 * Selector con búsqueda sobre una lista del servidor (/api/listas/{lista}). Sirve para catálogos pequeños y grandes
 * (puntos, máquinas): busca mientras se escribe. Con [padre] filtra en cascada (puntos de un cliente, etc.).
 */
@Component({
  selector: 'app-selector',
  imports: [FormsModule, MatFormFieldModule, MatInputModule, MatAutocompleteModule],
  template: `
    <mat-form-field appearance="outline" subscriptSizing="dynamic" class="campo">
      <mat-label>{{ etiqueta() }}</mat-label>
      <input matInput [ngModel]="texto()" (ngModelChange)="escribir($event)" [matAutocomplete]="auto"
             [required]="requerido()" [disabled]="deshabilitado()" (focus)="abrir()" (blur)="alSalir()" />
      @if (valor() && !deshabilitado()) {
        <button matSuffix type="button" class="limpiar" (click)="limpiar()" aria-label="Quitar selección">×</button>
      }
      <mat-autocomplete #auto="matAutocomplete" (optionSelected)="elegir($event.option.value)">
        @for (o of opciones(); track o.id) {
          <mat-option [value]="o">
            {{ o.nombre }} @if (o.detalle) { <small class="muted">· {{ o.detalle }}</small> }
          </mat-option>
        } @empty {
          @if (!cargando()) { <mat-option disabled>Sin resultados</mat-option> }
        }
      </mat-autocomplete>
    </mat-form-field>
  `,
  styles: `
    :host { display: block; min-width: 180px; }
    .campo { width: 100%; }
    .limpiar { border: none; background: none; font-size: 20px; cursor: pointer; color: var(--inees-gris); padding: 0 8px; }
  `,
})
export class Selector {
  private readonly rest = inject(Rest);
  readonly lista = input.required<string>();
  readonly etiqueta = input('Seleccione');
  readonly padre = input<number | null | undefined>(undefined);
  readonly requerido = input(false);
  readonly deshabilitado = input(false);
  /** Texto a mostrar cuando el valor llega ya elegido (al editar). */
  readonly nombreInicial = input<string | null | undefined>(null);
  readonly valor = model<number | null | undefined>(null);
  readonly elegido = output<Opcion | null>();

  protected readonly texto = signal('');
  protected readonly opciones = signal<Opcion[]>([]);
  protected readonly cargando = signal(false);
  private espera?: ReturnType<typeof setTimeout>;
  private seleccionado: Opcion | null = null;

  constructor() {
    // Al cambiar el padre se limpia la selección y se recargan las opciones.
    effect(() => {
      const p = this.padre();
      untracked(() => {
        if (p !== undefined && this.seleccionado && this.valor()) {
          this.limpiar();
        }
        this.opciones.set([]);
      });
    });
    effect(() => {
      const v = this.valor();
      const n = this.nombreInicial();
      untracked(() => {
        if (!v) {
          if (this.seleccionado) this.texto.set('');
          this.seleccionado = null;
        } else if (!this.seleccionado || this.seleccionado.id !== v) {
          this.seleccionado = { id: v, nombre: n ?? `#${v}`, detalle: null };
          this.texto.set(n ?? '');
          if (!n) this.resolverNombre(v);
        }
      });
    });
  }

  protected abrir(): void {
    if (!this.opciones().length) this.buscar(this.seleccionado ? '' : this.texto());
  }

  protected escribir(t: string | Opcion): void {
    if (typeof t !== 'string') return;
    this.texto.set(t);
    clearTimeout(this.espera);
    this.espera = setTimeout(() => this.buscar(t), 250);
  }

  protected elegir(o: Opcion): void {
    this.seleccionado = o;
    this.texto.set(o.nombre);
    this.valor.set(o.id);
    this.elegido.emit(o);
  }

  protected alSalir(): void {
    // Si escribió y no eligió, vuelve a mostrar lo seleccionado.
    setTimeout(() => this.texto.set(this.seleccionado?.nombre ?? ''), 200);
  }

  protected limpiar(): void {
    this.seleccionado = null;
    this.texto.set('');
    this.valor.set(null);
    this.elegido.emit(null);
  }

  private async buscar(q: string): Promise<void> {
    const p = this.padre();
    this.cargando.set(true);
    try {
      this.opciones.set(await this.rest.lista(this.lista(), q, p ?? null, 80));
    } catch {
      this.opciones.set([]);
    } finally {
      this.cargando.set(false);
    }
  }

  private async resolverNombre(id: number): Promise<void> {
    try {
      const todas = await this.rest.lista(this.lista(), null, this.padre() ?? null, 5000);
      const o = todas.find((x) => x.id === id);
      if (o && this.valor() === id) {
        this.seleccionado = o;
        this.texto.set(o.nombre);
      }
    } catch {
      /* se queda con el número */
    }
  }
}
