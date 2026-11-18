import { Component, inject, OnInit, signal } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { Avisos } from '../core/infra';
import { Rest } from '../core/rest';

interface Logistica {
  visitasFrecuentes: { punto: string; cliente: string; visitas: number; fechas: string }[];
  desplazamientosLargos: { tecnico: string; punto: string; salida_anterior: string; llegada: string; minutos: number }[];
  html: string;
  correoConfigurado: boolean;
  telegramConfigurado: boolean;
}

/**
 * Alertas logísticas (notificacionesLogistica de PHP): puntos visitados muchas veces en 7 días y desplazamientos
 * urbanos largos de hoy. El correo sale solo cada día a las 18:30; aquí también se puede enviar a mano.
 */
@Component({
  selector: 'app-alertas',
  imports: [MatButtonModule, MatIconModule],
  template: `
    <div class="pagina">
      <div class="encabezado">
        <h1>Alertas logísticas</h1>
        <button mat-stroked-button (click)="cargar()"><mat-icon>refresh</mat-icon> Actualizar</button>
        <button mat-flat-button (click)="enviar()" [disabled]="!datos()?.correoConfigurado || !datos()?.html">
          <mat-icon>send</mat-icon> Enviar por correo</button>
      </div>
      @if (datos(); as d) {
        @if (!d.correoConfigurado) { <p class="chip aviso">El correo no está configurado en el servidor: el envío automático está apagado.</p> }
        @if (!d.telegramConfigurado) { <p class="chip aviso">Telegram no está configurado: no se avisan los servicios que superan su tiempo.</p> }
        <section class="tarjeta">
          <h3>Puntos visitados muchas veces en los últimos 7 días</h3>
          <table class="tabla"><thead><tr><th>Punto</th><th>Cliente</th><th class="num">Visitas</th><th>Fechas</th></tr></thead>
            <tbody>@for (v of d.visitasFrecuentes; track v.punto) {
              <tr><td>{{ v.punto }}</td><td>{{ v.cliente }}</td><td class="num">{{ v.visitas }}</td><td>{{ v.fechas }}</td></tr>
            } @empty { <tr><td colspan="4" class="vacio">Sin alertas</td></tr> }</tbody></table>
        </section>
        <section class="tarjeta">
          <h3>Desplazamientos urbanos largos de hoy</h3>
          <table class="tabla"><thead><tr><th>Técnico</th><th>Punto destino</th><th>Salida anterior</th><th>Llegada</th><th class="num">Minutos</th></tr></thead>
            <tbody>@for (x of d.desplazamientosLargos; track $index) {
              <tr><td>{{ x.tecnico }}</td><td>{{ x.punto }}</td><td>{{ x.salida_anterior.substring(11, 16) }}</td><td>{{ x.llegada.substring(11, 16) }}</td>
                <td class="num"><strong>{{ x.minutos }}</strong></td></tr>
            } @empty { <tr><td colspan="5" class="vacio">Sin alertas</td></tr> }</tbody></table>
        </section>
      }
    </div>
  `,
  styles: `section { margin-bottom: 16px; } .chip { display: block; margin-bottom: 8px; padding: 8px 12px; }`,
})
export class AlertasLogisticas implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected readonly datos = signal<Logistica | null>(null);

  ngOnInit(): void {
    this.cargar();
  }

  async cargar(): Promise<void> {
    try {
      this.datos.set(await this.rest.get<Logistica>('/api/personal/alertas'));
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async enviar(): Promise<void> {
    try {
      this.datos.set(await this.rest.post<Logistica>('/api/personal/alertas/enviar'));
      this.avisos.ok('Correo enviado.');
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
