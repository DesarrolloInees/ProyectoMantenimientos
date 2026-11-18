import { DatePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { Fotos } from '../compartido/fotos';
import { Selector } from '../compartido/selector';
import { Avisos, hora, hoyIso } from '../core/infra';
import { pesos, Rest } from '../core/rest';
import { Parqueadero } from '../personal/parqueaderos';

/** El técnico registra las facturas de parqueadero con foto (parqueaderoCrear y parqueaderoHistorial de PHP). */
@Component({
  selector: 'app-mis-parqueaderos',
  imports: [FormsModule, DatePipe, Selector, Fotos, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule],
  template: `
    <div class="pagina movil">
      <h1>Parqueaderos</h1>
      <section class="tarjeta">
        <h3>{{ f['id'] ? 'Corregir factura' : 'Registrar factura' }}</h3>
        <app-selector lista="puntos" etiqueta="Punto visitado" [(valor)]="f['idPunto']" [nombreInicial]="f['punto']" />
        <mat-form-field appearance="outline"><mat-label>Fecha</mat-label><input matInput type="date" [(ngModel)]="f['fecha']" [max]="hoy" /></mat-form-field>
        <div class="dos">
          <mat-form-field appearance="outline"><mat-label>Entrada</mat-label><input matInput type="time" [(ngModel)]="f['horaInicio']" /></mat-form-field>
          <mat-form-field appearance="outline"><mat-label>Salida</mat-label><input matInput type="time" [(ngModel)]="f['horaFin']" /></mat-form-field>
        </div>
        <div class="dos">
          <mat-form-field appearance="outline"><mat-label>N° de factura</mat-label><input matInput [(ngModel)]="f['numeroFactura']" /></mat-form-field>
          <mat-form-field appearance="outline"><mat-label>Valor</mat-label><input matInput type="number" [(ngModel)]="f['valor']" /></mat-form-field>
        </div>
        <label class="foto"><mat-icon>photo_camera</mat-icon> {{ foto ? 'Foto lista' : 'Foto de la factura' }}
          <input type="file" accept="image/*" capture="environment" hidden (change)="foto = $any($event.target).files[0]" /></label>
        <button mat-flat-button class="grande" (click)="guardar()" [disabled]="enviando()">{{ f['id'] ? 'Guardar corrección' : 'Registrar' }}</button>
        @if (f['id']) { <button mat-button (click)="limpiar()">Cancelar</button> }
      </section>
      <h3>Mis facturas</h3>
      @for (p of lista(); track p.id) {
        <article class="tarjeta">
          <div class="cab"><strong>{{ p.fecha | date: 'd MMM' }} · {{ pesos(p.valor) }}</strong><span class="muted">Factura {{ p.numeroFactura }}</span></div>
          <div>{{ p.punto }} · {{ hora(p.horaInicio) }}–{{ hora(p.horaFin) }}</div>
          <app-fotos [rutas]="[p.rutaFoto]" />
          <button mat-button (click)="editar(p)">Corregir</button>
        </article>
      } @empty { <p class="vacio">Sin facturas.</p> }
    </div>
  `,
  styles: `
    .movil { max-width: 640px; }
    h1 { font-size: 20px; margin-bottom: 8px; }
    section mat-form-field, section app-selector { width: 100%; }
    .dos { display: flex; gap: 8px; mat-form-field { flex: 1; } }
    .foto { display: flex; align-items: center; gap: 8px; padding: 12px; border: 1px dashed var(--linea-2); border-radius: 10px; margin-bottom: 12px; cursor: pointer; }
    .grande { width: 100%; height: 48px; }
    article { margin-top: 10px; }
    .cab { display: flex; justify-content: space-between; gap: 8px; }
  `,
})
export class MisParqueaderos implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected readonly hora = hora;
  protected readonly pesos = pesos;
  protected readonly hoy = hoyIso();
  protected readonly lista = signal<Parqueadero[]>([]);
  protected readonly enviando = signal(false);
  protected f: Record<string, any> = this.vacio();
  protected foto?: File;

  ngOnInit(): void {
    this.cargar();
  }

  private vacio(): Record<string, any> {
    return { fecha: hoyIso(), horaInicio: '', horaFin: '', numeroFactura: '', valor: null, idPunto: null };
  }

  async cargar(): Promise<void> {
    try {
      this.lista.set(await this.rest.get<Parqueadero[]>('/api/campo/parqueaderos'));
    } catch (e) {
      this.avisos.error(e);
    }
  }

  editar(p: Parqueadero): void {
    this.f = { ...p, horaInicio: p.horaInicio.substring(0, 5), horaFin: p.horaFin.substring(0, 5) };
    this.foto = undefined;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  limpiar(): void {
    this.f = this.vacio();
    this.foto = undefined;
  }

  async guardar(): Promise<void> {
    if (!this.f['id'] && !this.foto) return this.avisos.ok('Tome la foto de la factura.');
    this.enviando.set(true);
    try {
      await this.rest.enviar(this.f['id'] ? `/api/campo/parqueaderos/${this.f['id']}` : '/api/campo/parqueaderos', {
        idPunto: this.f['idPunto'], fecha: this.f['fecha'], horaInicio: this.f['horaInicio'], horaFin: this.f['horaFin'],
        numeroFactura: this.f['numeroFactura'], valor: this.f['valor'], foto: this.foto });
      this.avisos.ok('Factura registrada.');
      this.limpiar();
      await this.cargar();
    } catch (e) {
      this.avisos.error(e);
    } finally {
      this.enviando.set(false);
    }
  }
}
