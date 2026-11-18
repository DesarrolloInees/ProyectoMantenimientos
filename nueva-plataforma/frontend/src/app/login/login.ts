import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { RouterLink } from '@angular/router';
import { Acceso } from '../compartido/acceso';
import { mensajeDe } from '../core/infra';
import { Sesion } from '../core/sesion';

@Component({
  selector: 'app-login',
  imports: [Acceso, FormsModule, MatFormFieldModule, MatInputModule, MatButtonModule, MatIconModule, MatProgressSpinnerModule, RouterLink],
  template: `
    <app-acceso>
      <form (ngSubmit)="ingresar()" class="form">
        <h1>Ingresar</h1>
        <p class="muted ayuda">Use su usuario y contraseña de siempre.</p>
        <mat-form-field appearance="outline">
          <mat-label>Usuario</mat-label>
          <input matInput name="usuario" [(ngModel)]="usuario" autocomplete="username" autocapitalize="none" required />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Contraseña</mat-label>
          <input matInput name="clave" [(ngModel)]="clave" [type]="ver() ? 'text' : 'password'"
                 autocomplete="current-password" required />
          <button mat-icon-button matSuffix type="button" (click)="ver.set(!ver())"
                  [attr.aria-label]="ver() ? 'Ocultar contraseña' : 'Ver contraseña'">
            <mat-icon>{{ ver() ? 'visibility_off' : 'visibility' }}</mat-icon>
          </button>
        </mat-form-field>
        @if (error()) {
          <div class="error" role="alert"><mat-icon>error</mat-icon>{{ error() }}</div>
        }
        <button mat-flat-button type="submit" class="entrar" [disabled]="cargando() || !usuario || !clave">
          @if (cargando()) {
            <mat-spinner diameter="20" />
          } @else {
            Ingresar
          }
        </button>
        <a routerLink="/recuperar" class="olvido">¿Olvidó su contraseña?</a>
      </form>
    </app-acceso>
  `,
  styles: `
    .form { display: flex; flex-direction: column; gap: 4px; }
    h1 { font-size: 28px; letter-spacing: -0.02em; }
    .ayuda { margin: 6px 0 22px; font-size: 15px; }
    .error {
      display: flex; gap: 8px; align-items: flex-start; margin: 0 0 14px; padding: 10px 12px; border-radius: 8px;
      background: var(--error-fondo); color: var(--error); font-size: 14px;
    }
    .error mat-icon { font-size: 20px; width: 20px; height: 20px; flex: none; }
    .entrar { --mat-button-filled-container-height: 50px; --mat-button-filled-label-text-size: 15.5px; margin-top: 4px; }
    .olvido { align-self: center; margin-top: 18px; font-weight: 600; font-size: 14.5px; text-decoration: none; }
    .olvido:hover { text-decoration: underline; }
  `,
})
export class Login {
  private readonly sesion = inject(Sesion);
  protected usuario = '';
  protected clave = '';
  protected readonly ver = signal(false);
  protected readonly cargando = signal(false);
  protected readonly error = signal<string | null>(null);

  async ingresar(): Promise<void> {
    this.cargando.set(true);
    this.error.set(null);
    try {
      await this.sesion.ingresar(this.usuario.trim(), this.clave);
    } catch (e) {
      this.error.set(mensajeDe(e));
    } finally {
      this.cargando.set(false);
    }
  }
}
