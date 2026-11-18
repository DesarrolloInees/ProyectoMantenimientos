import { DatePipe } from '@angular/common';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { CapaMapa, COLORES_RUTA, Mapa } from '../compartido/mapa';
import { Selector } from '../compartido/selector';
import { Avisos, hora, hoyIso } from '../core/infra';
import { Rest } from '../core/rest';

interface Rastro {
  servicios: { idOrden: number; tecnico: string; cliente: string; punto: string; horaEntrada: string | null; horaSalida: string | null;
    latitudInicio: number | null; longitudInicio: number | null; latitudFin: number | null; longitudFin: number | null; observacion: string | null }[];
  eventos: { tecnico: string; fecha: string; etapa: string; latitud: number; longitud: number; punto: string | null }[];
}

/** Recorrido de los técnicos en un día: puntos GPS de cada paso registrado y de inicio/fin de los servicios (rastreoTecnico). */
@Component({
  selector: 'app-rastreo',
  imports: [FormsModule, DatePipe, Selector, Mapa, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule],
  template: `
    <div class="pagina">
      <div class="encabezado"><h1>Rastreo de técnicos</h1></div>
      <div class="filtros">
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Fecha</mat-label><input matInput type="date" [(ngModel)]="fecha" /></mat-form-field>
        <app-selector lista="tecnicos" etiqueta="Técnico (vacío = todos)" [(valor)]="idTecnico" />
        <button mat-flat-button (click)="cargar()"><mat-icon>search</mat-icon> Ver recorrido</button>
      </div>
      <div class="dos">
        <app-mapa class="mapa" [capas]="capas()" />
        <div class="tarjeta lista">
          @for (t of tecnicos(); track t; let i = $index) {
            <h3><span class="punto" [style.background]="color(i)"></span> {{ t }}</h3>
            <ol>
              @for (e of eventosDe(t); track $index) {
                <li><strong>{{ e.fecha | date: 'HH:mm' }}</strong> {{ e.etapa }} @if (e.punto) { · {{ e.punto }} }</li>
              }
              @for (s of serviciosDe(t); track s.idOrden) {
                <li class="muted">Servicio {{ hora(s.horaEntrada) }}–{{ hora(s.horaSalida) }} · {{ s.punto }}</li>
              }
            </ol>
          } @empty { <p class="vacio">Sin recorridos para la fecha.</p> }
        </div>
      </div>
    </div>
  `,
  styles: `
    .dos { display: grid; grid-template-columns: 1fr 360px; gap: 16px; }
    .mapa { height: calc(100vh - 220px); min-height: 420px; }
    .lista { max-height: calc(100vh - 220px); overflow: auto; }
    .punto { display: inline-block; width: 12px; height: 12px; border-radius: 50%; }
    ol { padding-left: 20px; font-size: 13px; }
    @media (max-width: 900px) { .dos { grid-template-columns: 1fr; } }
  `,
})
export class Rastreo implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected readonly hora = hora;
  protected fecha = hoyIso();
  protected idTecnico: number | null = null;
  protected readonly datos = signal<Rastro>({ servicios: [], eventos: [] });
  protected readonly tecnicos = computed(() => [...new Set([...this.datos().eventos.map((e) => e.tecnico), ...this.datos().servicios.map((s) => s.tecnico)])]);
  protected readonly capas = computed<CapaMapa[]>(() =>
    this.tecnicos().map((t, i) => {
      const color = this.color(i);
      const puntos = [
        ...this.eventosDe(t).map((e) => ({ lat: e.latitud, lon: e.longitud, color, titulo: `${e.fecha.substring(11, 16)} ${e.etapa}`, orden: e.fecha })),
        ...this.serviciosDe(t).flatMap((s) => [
          s.latitudInicio ? { lat: s.latitudInicio, lon: s.longitudInicio!, color, titulo: `Inicio · ${s.punto}`, orden: `${this.fecha}T${s.horaEntrada}` } : null,
          s.latitudFin ? { lat: s.latitudFin, lon: s.longitudFin!, color, titulo: `Fin · ${s.punto}`, orden: `${this.fecha}T${s.horaSalida}` } : null,
        ]).filter((x) => x !== null),
      ].sort((a, b) => String(a!.orden).localeCompare(String(b!.orden)));
      return { color, unir: true, puntos: puntos.map((p, k) => ({ lat: p!.lat, lon: p!.lon, color, titulo: p!.titulo, etiqueta: String(k + 1) })) };
    }),
  );

  ngOnInit(): void {
    this.cargar();
  }

  protected color(i: number): string {
    return COLORES_RUTA[i % COLORES_RUTA.length];
  }

  protected eventosDe(t: string) {
    return this.datos().eventos.filter((e) => e.tecnico === t);
  }

  protected serviciosDe(t: string) {
    return this.datos().servicios.filter((s) => s.tecnico === t);
  }

  async cargar(): Promise<void> {
    try {
      this.datos.set(await this.rest.get<Rastro>('/api/rastreo', { fecha: this.fecha, idTecnico: this.idTecnico }));
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
