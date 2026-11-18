import { DatePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatTooltipModule } from '@angular/material/tooltip';
import { Avisos } from '../core/infra';
import { Opcion, Rest } from '../core/rest';
import { ICONO_ROL, RolFijo } from './roles';

interface Usuario {
  usuarioId: number;
  nombre: string;
  cedula: string | null;
  cargo: string | null;
  email: string | null;
  celular: string | null;
  usuario: string;
  idRol: number;
  rol: string;
  estado: string;
  forzarCambioPwd: boolean;
  ultimoAcceso: string | null;
  idTecnico: number | null;
  tecnico: string | null;
  idDelegacion: number | null;
  delegacion: string | null;
}

/**
 * Usuarios de la plataforma (usuarioCrear/Ver/Editar de PHP). Los roles son fijos: se elige uno y con eso la persona ve
 * solo lo suyo. Al motorizado se le pide la delegación y su ficha de técnico se crea sola, para que entre a las rutas.
 */
@Component({
  selector: 'app-usuarios',
  imports: [FormsModule, DatePipe, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule, MatSelectModule, MatCheckboxModule, MatTooltipModule],
  template: `
    <div class="pagina">
      <div class="encabezado"><h1>Usuarios</h1><button mat-flat-button (click)="nuevo()"><mat-icon>person_add</mat-icon> Nuevo usuario</button></div>
      @if (form(); as f) {
        <section class="tarjeta formulario">
          <h3>{{ f.usuarioId ? 'Editar ' + f.nombre : 'Nuevo usuario' }}</h3>
          <p class="paso-form">1. ¿Qué rol tiene?</p>
          <div class="elegir-rol" role="radiogroup" aria-label="Rol">
            @for (r of roles(); track r.idRol) {
              <button type="button" role="radio" class="opcion-rol" [class.elegido]="f.idRol === r.idRol" [attr.aria-checked]="f.idRol === r.idRol"
                      (click)="f.idRol = r.idRol">
                <mat-icon>{{ icono(r.idRol) }}</mat-icon>
                <span><strong>{{ r.nombre }}</strong><small>{{ r.descripcion }}</small></span>
              </button>
            }
          </div>
          @if (esMotorizado(f.idRol)) {
            <div class="motorizado">
              <mat-form-field appearance="outline">
                <mat-label>Delegación donde trabaja</mat-label>
                <mat-select [(ngModel)]="f.idDelegacion">@for (d of delegaciones(); track d.id) { <mat-option [value]="d.id">{{ d.nombre }}</mat-option> }</mat-select>
                <mat-hint>Las rutas se arman por delegación: sin ella no recibe servicios</mat-hint>
              </mat-form-field>
            </div>
          }
          <p class="paso-form">2. Sus datos</p>
          <div class="rejilla">
            <mat-form-field appearance="outline"><mat-label>Nombre</mat-label><input matInput [(ngModel)]="f.nombre" required /></mat-form-field>
            <mat-form-field appearance="outline"><mat-label>Usuario para ingresar</mat-label><input matInput [(ngModel)]="f.usuario" required autocapitalize="none" /></mat-form-field>
            <mat-form-field appearance="outline"><mat-label>Cédula</mat-label><input matInput [(ngModel)]="f.cedula" /></mat-form-field>
            <mat-form-field appearance="outline"><mat-label>Cargo</mat-label><input matInput [(ngModel)]="f.cargo" /></mat-form-field>
            <mat-form-field appearance="outline"><mat-label>Correo</mat-label><input matInput type="email" [(ngModel)]="f.email" />
              <mat-hint>Para recuperar la contraseña</mat-hint></mat-form-field>
            <mat-form-field appearance="outline"><mat-label>Celular</mat-label><input matInput [(ngModel)]="f.celular" /></mat-form-field>
            <mat-form-field appearance="outline"><mat-label>{{ f.usuarioId ? 'Nueva contraseña temporal (opcional)' : 'Contraseña temporal' }}</mat-label>
              <input matInput [(ngModel)]="f.clave" autocomplete="new-password" /><mat-hint>Deberá cambiarla al ingresar</mat-hint></mat-form-field>
            @if (f.usuarioId) { <mat-checkbox [(ngModel)]="f.activo">Activo</mat-checkbox> }
          </div>
          <div class="acciones"><button mat-button (click)="form.set(null)">Cancelar</button><button mat-flat-button (click)="guardar()">Guardar</button></div>
        </section>
      }
      <div class="filtros">
        <mat-form-field appearance="outline" subscriptSizing="dynamic" class="ancho"><mat-label>Buscar</mat-label><input matInput [(ngModel)]="texto" (keyup.enter)="cargar()" /></mat-form-field>
        <mat-checkbox [(ngModel)]="inactivos" (change)="cargar()">Ver inactivos</mat-checkbox>
      </div>
      <div class="contenedor-tabla">
        <table class="tabla">
          <thead><tr><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Cargo</th><th>Correo / celular</th><th>Delegación</th><th>Último acceso</th><th></th></tr></thead>
          <tbody>
            @for (u of lista(); track u.usuarioId) {
              <tr [class.inactivo]="u.estado !== 'activo'"><td>{{ u.nombre }}</td><td>{{ u.usuario }}</td><td><span class="chip">{{ u.rol }}</span></td><td>{{ u.cargo }}</td>
                <td>{{ u.email }}<br /><span class="muted">{{ u.celular }}</span></td>
                <td>{{ u.delegacion }} @if (esMotorizado(u.idRol) && !u.delegacion) { <span class="chip aviso">Sin delegación</span> }</td>
                <td>{{ u.ultimoAcceso | date: 'dd/MM/yy HH:mm' }} @if (u.forzarCambioPwd) { <span class="chip aviso">Debe cambiar clave</span> }</td>
                <td class="botones-fila"><button mat-icon-button (click)="editar(u)" matTooltip="Editar"><mat-icon>edit</mat-icon></button>
                  @if (u.estado === 'activo') { <button mat-icon-button (click)="desactivar(u)" matTooltip="Desactivar"><mat-icon>person_off</mat-icon></button> }
                  @if (u.idTecnico) {
                    <label class="firma" matTooltip="Cargar firma del técnico (PDF de servicios)"><mat-icon>draw</mat-icon>
                      <input type="file" accept="image/*" hidden (change)="firma(u, $event)" /></label>
                  }</td></tr>
            } @empty { <tr><td colspan="8" class="vacio">Sin usuarios</td></tr> }
          </tbody>
        </table>
      </div>
    </div>
  `,
  styles: `
    .formulario { margin-bottom: 16px; }
    .firma { cursor: pointer; display: inline-flex; padding: 8px; color: var(--inees-gris); }
    .paso-form { margin: 14px 0 8px; font-weight: 600; color: var(--marca-noche); }
    .elegir-rol { display: grid; gap: 10px; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); }
    .opcion-rol { display: flex; gap: 10px; align-items: flex-start; padding: 12px; text-align: left; border-radius: 10px; cursor: pointer;
      border: 1px solid var(--linea-2); background: #fff; font: inherit; color: var(--tinta); transition: border-color 0.15s ease, background-color 0.15s ease; }
    .opcion-rol:hover { border-color: var(--marca-azul-c); }
    .opcion-rol.elegido { border-color: var(--marca-azul); background: var(--marca-azul-t); box-shadow: 0 0 0 1px var(--marca-azul); }
    .opcion-rol mat-icon { flex: none; color: var(--marca-azul); }
    .opcion-rol strong { display: block; color: var(--marca-noche); }
    .opcion-rol small { display: block; margin-top: 2px; color: var(--tinta-2); font-size: 12.5px; line-height: 1.4; }
    .motorizado { margin: 12px 0 22px; max-width: 360px; }
    .motorizado mat-form-field { width: 100%; }
  `,
})
export class Usuarios implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected readonly lista = signal<Usuario[]>([]);
  protected readonly roles = signal<RolFijo[]>([]);
  protected readonly delegaciones = signal<Opcion[]>([]);
  protected readonly form = signal<any>(null);
  protected texto = '';
  protected inactivos = false;

  async ngOnInit(): Promise<void> {
    const [roles, delegaciones] = await Promise.all([this.rest.get<RolFijo[]>('/api/admin/roles'), this.rest.lista('delegaciones')]);
    this.roles.set(roles);
    this.delegaciones.set(delegaciones);
    this.cargar();
  }

  async cargar(): Promise<void> {
    try {
      this.lista.set(await this.rest.get<Usuario[]>('/api/admin/usuarios', { texto: this.texto, inactivos: this.inactivos }));
    } catch (e) {
      this.avisos.error(e);
    }
  }

  nuevo(): void {
    this.form.set({ idRol: null, idDelegacion: null });
  }

  editar(u: Usuario): void {
    this.form.set({ ...u, clave: '', activo: u.estado === 'activo' });
  }

  async guardar(): Promise<void> {
    const f = this.form()!;
    const datos = { nombre: f['nombre'], cedula: f['cedula'], cargo: f['cargo'], email: f['email'] || null, celular: f['celular'], usuario: f['usuario'],
      clave: f['clave'] || null, idRol: f['idRol'], activo: f['activo'] ?? true, idDelegacion: f['idDelegacion'] ?? null };
    if (!datos.idRol) {
      this.avisos.error(new Error('Elija el rol de la persona.'));
      return;
    }
    try {
      if (f['usuarioId']) await this.rest.put(`/api/admin/usuarios/${f['usuarioId']}`, datos);
      else await this.rest.post('/api/admin/usuarios', datos);
      this.avisos.ok('Usuario guardado.');
      this.form.set(null);
      await this.cargar();
    } catch (e) {
      this.avisos.error(e);
    }
  }

  protected esMotorizado(idRol: number | null): boolean {
    return this.roles().some((r) => r.idRol === idRol && r.motorizado);
  }

  protected icono(id: number): string {
    return ICONO_ROL[id] ?? 'badge';
  }

  async desactivar(u: Usuario): Promise<void> {
    if (!confirm(`¿Desactivar a ${u.nombre}? No podrá ingresar.`)) return;
    try {
      await this.rest.delete(`/api/admin/usuarios/${u.usuarioId}`);
      await this.cargar();
    } catch (e) {
      this.avisos.error(e);
    }
  }

  async firma(u: Usuario, ev: Event): Promise<void> {
    const archivo = (ev.target as HTMLInputElement).files?.[0];
    if (!archivo || !u.idTecnico) return;
    try {
      await this.rest.enviar(`/api/admin/tecnicos/${u.idTecnico}/firma`, { archivo });
      this.avisos.ok('Firma cargada.');
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
