import { Component, effect, inject, input, signal } from '@angular/core';
import { Rest } from '../core/rest';

/** Miniaturas de fotos guardadas en el servidor (se piden con el token); clic para ver en grande. */
@Component({
  selector: 'app-fotos',
  template: `
    <div class="fotos">
      @for (u of urls(); track $index) {
        <a [href]="u" target="_blank" rel="noopener"><img class="foto-mini" [src]="u" alt="Foto" /></a>
      }
    </div>
  `,
})
export class Fotos {
  private readonly rest = inject(Rest);
  readonly rutas = input<(string | null)[]>([]);
  protected readonly urls = signal<string[]>([]);

  constructor() {
    effect(async () => {
      const rutas = this.rutas().filter((r): r is string => !!r);
      const urls: string[] = [];
      for (const r of rutas) {
        try {
          urls.push(await this.rest.imagen(r));
        } catch {
          /* foto no disponible */
        }
      }
      this.urls.set(urls);
    });
  }
}
