import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { Router, RouterLink } from '@angular/router';
import { Acceso } from '../compartido/acceso';
import { Avisos, mensajeDe } from '../core/infra';
import { Rest } from '../core/rest';
import { REGLA_CLAVE } from './cambiar-clave';

/** Recuperación de contraseña con un código de 6 dígitos que llega al correo (válido 15 minutos). */
@Component({
  selector: 'app-recuperar',
  imports: [Acceso, FormsModule, MatFormFieldModule, MatInputModule, MatButtonModule, RouterLink],
  template: `
    <app-acceso>
        <h1>Recuperar la contraseña</h1>
        @if (paso() === 1) {
          <p class="muted">Escriba el correo registrado en su usuario y le enviaremos un código.</p>
          <mat-form-field appearance="outline"><mat-label>Correo</mat-label>
            <input matInput type="email" [(ngModel)]="email" autocomplete="email" /></mat-form-field>
          <button mat-flat-button (click)="enviar()" [disabled]="trabajando() || !email">Enviar código</button>
        } @else {
          <p class="muted">Si el correo está registrado, le llegó un código. Escríbalo con su contraseña nueva.</p>
          <mat-form-field appearance="outline"><mat-label>Código</mat-label>
            <input matInput [(ngModel)]="codigo" inputmode="numeric" maxlength="6" autocomplete="one-time-code" /></mat-form-field>
          <mat-form-field appearance="outline"><mat-label>Contraseña nueva</mat-label>
            <input matInput type="password" [(ngModel)]="clave" autocomplete="new-password" />
            <mat-hint>8+ caracteres, mayúscula, minúscula, número y símbolo</mat-hint></mat-form-field>
          <mat-form-field appearance="outline"><mat-label>Repita la contraseña</mat-label>
            <input matInput type="password" [(ngModel)]="repetida" autocomplete="new-password" /></mat-form-field>
          <button mat-flat-button (click)="restablecer()" [disabled]="trabajando() || !codigo || !clave">Cambiar contraseña</button>
          <button mat-button (click)="paso.set(1)">Volver a enviar el código</button>
        }
        @if (error()) { <p class="error" role="alert">{{ error() }}</p> }
        <a routerLink="/login" class="volver">Volver al ingreso</a>
    </app-acceso>
  `,
  styles: `
    h1 { font-size: 26px; letter-spacing: -0.02em; }
    p.muted { margin: 6px 0 18px; font-size: 15px; }
    button[mat-flat-button] { --mat-button-filled-container-height: 50px; margin-top: 4px; }
    .error { margin: 10px 0 0; padding: 10px 12px; border-radius: 8px; background: var(--error-fondo); color: var(--error); }
    .volver { align-self: center; margin-top: 18px; font-weight: 600; font-size: 14.5px; text-decoration: none; }
    .volver:hover { text-decoration: underline; }
  `,
})
export class Recuperar {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  private readonly router = inject(Router);
  protected readonly paso = signal(1);
  protected readonly trabajando = signal(false);
  protected readonly error = signal<string | null>(null);
  protected email = '';
  protected codigo = '';
  protected clave = '';
  protected repetida = '';

  async enviar(): Promise<void> {
    await this.hacer(async () => {
      await this.rest.post('/api/auth/recuperar', { email: this.email.trim() });
      this.paso.set(2);
    });
  }

  async restablecer(): Promise<void> {
    if (this.clave !== this.repetida) return this.error.set('Las contraseñas no coinciden.');
    if (!REGLA_CLAVE.test(this.clave)) return this.error.set('La contraseña no cumple las reglas de seguridad.');
    await this.hacer(async () => {
      await this.rest.post('/api/auth/restablecer', { email: this.email.trim(), codigo: this.codigo.trim(), claveNueva: this.clave });
      this.avisos.ok('Contraseña actualizada. Ya puede ingresar.');
      await this.router.navigateByUrl('/login');
    });
  }

  private async hacer(f: () => Promise<void>): Promise<void> {
    this.error.set(null);
    this.trabajando.set(true);
    try {
      await f();
    } catch (e) {
      this.error.set(mensajeDe(e));
    } finally {
      this.trabajando.set(false);
    }
  }
}
