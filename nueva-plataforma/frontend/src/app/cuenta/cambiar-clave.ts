import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { Router } from '@angular/router';
import { Avisos, mensajeDe } from '../core/infra';
import { Rest } from '../core/rest';
import { Sesion } from '../core/sesion';

export const REGLA_CLAVE = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/;

/** Cambio de contraseña (obligatorio en el primer ingreso o cuando el administrador la reinicia). */
@Component({
  selector: 'app-cambiar-clave',
  imports: [FormsModule, MatFormFieldModule, MatInputModule, MatButtonModule],
  template: `
    <div class="pagina">
      <div class="encabezado"><h1>Cambiar contraseña</h1></div>
      <form class="tarjeta caja" (ngSubmit)="guardar()">
        @if (sesion.perfil()?.debeCambiarClave) {
          <p class="aviso">Antes de continuar debe cambiar su contraseña temporal.</p>
        }
        <mat-form-field appearance="outline"><mat-label>Contraseña actual</mat-label>
          <input matInput type="password" name="actual" [(ngModel)]="actual" required autocomplete="current-password" /></mat-form-field>
        <mat-form-field appearance="outline"><mat-label>Contraseña nueva</mat-label>
          <input matInput type="password" name="nueva" [(ngModel)]="nueva" required autocomplete="new-password" />
          <mat-hint>8 o más caracteres con mayúscula, minúscula, número y símbolo</mat-hint></mat-form-field>
        <mat-form-field appearance="outline"><mat-label>Repita la contraseña nueva</mat-label>
          <input matInput type="password" name="repetida" [(ngModel)]="repetida" required autocomplete="new-password" /></mat-form-field>
        @if (error()) { <p class="error" role="alert">{{ error() }}</p> }
        <button mat-flat-button type="submit" [disabled]="guardando()">Guardar</button>
      </form>
    </div>
  `,
  styles: `
    .caja { max-width: 420px; display: flex; flex-direction: column; gap: 8px; }
    .aviso { background: var(--aviso-fondo); color: var(--aviso); padding: 8px 12px; border-radius: 8px; margin: 0 0 8px; }
    .error { color: var(--inees-rojo); margin: 0; }
  `,
})
export class CambiarClave {
  protected readonly sesion = inject(Sesion);
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  private readonly router = inject(Router);
  protected actual = '';
  protected nueva = '';
  protected repetida = '';
  protected readonly error = signal<string | null>(null);
  protected readonly guardando = signal(false);

  async guardar(): Promise<void> {
    this.error.set(null);
    if (this.nueva !== this.repetida) return this.error.set('Las contraseñas nuevas no coinciden.');
    if (!REGLA_CLAVE.test(this.nueva)) {
      return this.error.set('La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula, un número y un símbolo.');
    }
    this.guardando.set(true);
    try {
      await this.rest.post('/api/auth/cambiar-clave', { claveActual: this.actual, claveNueva: this.nueva });
      this.sesion.claveCambiada();
      this.avisos.ok('Contraseña actualizada.');
      await this.router.navigateByUrl(this.sesion.inicio());
    } catch (e) {
      this.error.set(mensajeDe(e));
    } finally {
      this.guardando.set(false);
    }
  }
}
