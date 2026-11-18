import { afterNextRender, Component, ElementRef, output, signal, viewChild } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';

/** Recuadro para firmar con el dedo. Emite la firma como imagen PNG en base64 (data URL). */
@Component({
  selector: 'app-firma',
  imports: [MatButtonModule],
  template: `
    <canvas #lienzo class="lienzo" aria-label="Firme aquí"
            (pointerdown)="empezar($event)" (pointermove)="trazar($event)" (pointerup)="terminar()" (pointerleave)="terminar()"></canvas>
    <div class="pie">
      <span class="muted">{{ firmado() ? 'Firmado' : 'Firme dentro del recuadro' }}</span>
      <button mat-button type="button" (click)="limpiar()">Borrar</button>
    </div>
  `,
  styles: `
    .lienzo { width: 100%; height: 180px; border: 2px dashed var(--linea-2); border-radius: 10px; background: #fff; touch-action: none; }
    .pie { display: flex; justify-content: space-between; align-items: center; }
  `,
})
export class Firma {
  readonly cambio = output<string | null>();
  protected readonly firmado = signal(false);
  private readonly lienzo = viewChild.required<ElementRef<HTMLCanvasElement>>('lienzo');
  private ctx?: CanvasRenderingContext2D;
  private dibujando = false;

  constructor() {
    afterNextRender(() => {
      const c = this.lienzo().nativeElement;
      const escala = window.devicePixelRatio || 1;
      c.width = c.clientWidth * escala;
      c.height = c.clientHeight * escala;
      this.ctx = c.getContext('2d')!;
      this.ctx.scale(escala, escala);
      this.ctx.lineWidth = 2.5;
      this.ctx.lineCap = 'round';
      this.ctx.strokeStyle = '#0f1f2c';
    });
  }

  empezar(e: PointerEvent): void {
    this.dibujando = true;
    this.ctx?.beginPath();
    this.ctx?.moveTo(e.offsetX, e.offsetY);
  }

  trazar(e: PointerEvent): void {
    if (!this.dibujando || !this.ctx) return;
    this.ctx.lineTo(e.offsetX, e.offsetY);
    this.ctx.stroke();
  }

  terminar(): void {
    if (!this.dibujando) return;
    this.dibujando = false;
    this.firmado.set(true);
    this.cambio.emit(this.lienzo().nativeElement.toDataURL('image/png'));
  }

  limpiar(): void {
    const c = this.lienzo().nativeElement;
    this.ctx?.clearRect(0, 0, c.width, c.height);
    this.firmado.set(false);
    this.cambio.emit(null);
  }
}
