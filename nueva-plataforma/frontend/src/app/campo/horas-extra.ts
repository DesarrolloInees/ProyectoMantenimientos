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
import { Rest } from '../core/rest';
import { HoraExtra } from '../personal/horas-extra';

/** El técnico registra sus horas extra con fotos y ve si se aprobaron (horaExtraCrear y horaExtraHistorial de PHP). */
@Component({
  selector: 'app-mis-horas-extra',
  imports: [FormsModule, DatePipe, Selector, Fotos, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule],
  template: `
    <div class="pagina movil">
      <h1>Horas extra</h1>
      <section class="tarjeta">
        <h3>{{ f.id ? 'Corregir registro' : 'Registrar horas extra' }}</h3>
        <mat-form-field appearance="outline"><mat-label>Fecha</mat-label><input matInput type="date" [(ngModel)]="f.fecha" [max]="hoy" /></mat-form-field>
        <div class="dos">
          <mat-form-field appearance="outline"><mat-label>Desde</mat-label><input matInput type="time" [(ngModel)]="f.horaInicio" /></mat-form-field>
          <mat-form-field appearance="outline"><mat-label>Hasta</mat-label><input matInput type="time" [(ngModel)]="f.horaFin" /></mat-form-field>
        </div>
        <app-selector lista="clientes" etiqueta="Cliente (opcional)" [(valor)]="f.idCliente" />
        <app-selector lista="puntos" etiqueta="Punto (opcional)" [padre]="f.idCliente" [(valor)]="f.idPunto" />
        <mat-form-field appearance="outline"><mat-label>¿Por qué trabajó horas extra?</mat-label><textarea matInput rows="3" [(ngModel)]="f.justificacion"></textarea></mat-form-field>
        <label class="foto"><mat-icon>photo_camera</mat-icon> {{ fotos.length ? fotos.length + ' foto(s)' : 'Agregar fotos de evidencia' }}
          <input type="file" accept="image/*" capture="environment" multiple hidden (change)="elegir($event)" /></label>
        <button mat-flat-button class="grande" (click)="guardar()" [disabled]="enviando()">{{ f.id ? 'Guardar corrección' : 'Registrar' }}</button>
        @if (f.id) { <button mat-button (click)="limpiar()">Cancelar</button> }
      </section>
      <h3>Mis registros</h3>
      @for (h of lista(); track h.id) {
        <article class="tarjeta">
          <div class="cab"><strong>{{ h.fecha | date: 'd MMM' }} · {{ hora(h.horaInicio) }}–{{ hora(h.horaFin) }} ({{ h.totalHoras }} h)</strong>
            <span class="chip" [class.ok]="h.idEstado === 2" [class.error]="h.idEstado === 3" [class.aviso]="h.idEstado === 1">{{ h.estado }}</span></div>
          <p>{{ h.justificacion }}</p>
          @if (h.observacionSupervisor) { <p class="muted">Supervisor: {{ h.observacionSupervisor }}</p> }
          <app-fotos [rutas]="rutas(h)" />
          @if (h.idEstado !== 2) {
            <div class="acciones"><button mat-button (click)="editar(h)">Corregir</button>
              @for (x of h.fotos; track x.id) { <button mat-button (click)="quitarFoto(h, x.id)">Quitar foto {{ $index + 1 }}</button> }</div>
          }
        </article>
      } @empty { <p class="vacio">Sin registros.</p> }
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
    .cab { display: flex; justify-content: space-between; gap: 8px; align-items: center; }
    p { margin: 6px 0; white-space: pre-wrap; }
  `,
})
export class MisHorasExtra implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected readonly hora = hora;
  protected readonly hoy = hoyIso();
  protected readonly lista = signal<HoraExtra[]>([]);
  protected readonly enviando = signal(false);
  protected f: any = this.vacio();
  protected fotos: File[] = [];

  ngOnInit(): void {
    this.cargar();
  }

  private vacio(): Record<string, any> {
    return { fecha: hoyIso(), horaInicio: '', horaFin: '', justificacion: '', idCliente: null, idPunto: null };
  }

  protected rutas(h: HoraExtra): string[] {
    return h.fotos.map((x) => x.ruta);
  }

  async cargar(): Promise<void> {
    try {
      this.lista.set(await this.rest.get<HoraExtra[]>('/api/campo/horas-extra'));
    } catch (e) {
      this.avisos.error(e);
    }
  }

  elegir(ev: Event): void {
    this.fotos = Array.from((ev.target as HTMLInputElement).files ?? []);
  }

  editar(h: HoraExtra): void {
    this.f = { id: h.id, fecha: h.fecha, horaInicio: h.horaInicio.substring(0, 5), horaFin: h.horaFin.substring(0, 5), justificacion: h.justificacion,
      idCliente: h.idCliente, idPunto: h.idPunto };
    this.fotos = [];
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  limpiar(): void {
    this.f = this.vacio();
    this.fotos = [];
  }

  async guardar(): Promise<void> {
    if (!this.f['horaInicio'] || !this.f['horaFin']) return this.avisos.ok('Indique la hora de inicio y fin.');
    this.enviando.set(true);
    try {
      const url = this.f['id'] ? `/api/campo/horas-extra/${this.f['id']}` : '/api/campo/horas-extra';
      await this.rest.enviar(url, { fecha: this.f['fecha'], horaInicio: this.f['horaInicio'], horaFin: this.f['horaFin'], justificacion: this.f['justificacion'],
        idCliente: this.f['idCliente'], idPunto: this.f['idPunto'], fotos: this.fotos });
      this.avisos.ok('Horas extra registradas; quedan pendientes de aprobación.');
      this.limpiar();
      await this.cargar();
    } catch (e) {
      this.avisos.error(e);
    } finally {
      this.enviando.set(false);
    }
  }

  async quitarFoto(h: HoraExtra, idFoto: number): Promise<void> {
    try {
      await this.rest.delete(`/api/campo/horas-extra/${h.id}/fotos/${idFoto}`);
      await this.cargar();
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
