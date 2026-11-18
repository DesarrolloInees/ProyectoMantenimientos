import { Component, inject, OnInit, signal } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { RouterLink } from '@angular/router';
import { Avisos } from '../core/infra';
import { Rest } from '../core/rest';

export interface RolFijo {
  idRol: number;
  nombre: string;
  descripcion: string;
  motorizado: boolean;
  usuarios: number;
  permisos: { codigo: string; descripcion: string }[];
}

/** Ícono de cada rol (los roles son fijos: ver V7). */
export const ICONO_ROL: Record<number, string> = { 1: 'shield_person', 5: 'supervisor_account', 2: 'support_agent', 3: 'two_wheeler' };

/**
 * Qué puede hacer cada rol. Los roles son fijos (Superusuario, Supervisor de motorizados, Auxiliar de oficina y
 * Motorizado): para dar acceso a alguien basta con elegir su rol en «Usuarios».
 */
@Component({
  selector: 'app-roles',
  imports: [RouterLink, MatButtonModule, MatIconModule],
  template: `
    <div class="pagina">
      <div class="encabezado">
        <h1>Qué puede hacer cada rol</h1>
        <a mat-flat-button routerLink="/admin/usuarios"><mat-icon>group</mat-icon> Ir a usuarios</a>
      </div>
      <p class="intro">Los roles son fijos. Para dar acceso a una persona, créela en <a routerLink="/admin/usuarios">Usuarios</a> y elija
        su rol: con eso ve solo lo que le corresponde.</p>
      <div class="roles">
        @for (r of roles(); track r.idRol) {
          <section class="tarjeta rol">
            <div class="cab">
              <span class="icono"><mat-icon>{{ icono(r.idRol) }}</mat-icon></span>
              <div><h2>{{ r.nombre }}</h2><span class="muted">{{ r.usuarios }} {{ r.usuarios === 1 ? 'usuario activo' : 'usuarios activos' }}</span></div>
            </div>
            <p class="desc">{{ r.descripcion }}</p>
            <ul>
              @for (p of r.permisos; track p.codigo) { <li><mat-icon>check</mat-icon>{{ p.descripcion }}</li> }
            </ul>
            @if (noIncluye(r).length) {
              <p class="no-titulo">No incluye</p>
              <ul class="no">
                @for (p of noIncluye(r); track p.codigo) { <li><mat-icon>remove</mat-icon>{{ p.descripcion }}</li> }
              </ul>
            }
          </section>
        }
      </div>
    </div>
  `,
  styles: `
    .intro { max-width: 70ch; margin: -6px 0 20px; color: var(--tinta-2); font-size: 15px; line-height: 1.5; }
    .roles { display: grid; gap: 16px; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); align-items: start; }
    .rol { padding: 20px; }
    .cab { display: flex; gap: 12px; align-items: center; margin-bottom: 10px; }
    .icono { display: grid; place-items: center; width: 42px; height: 42px; border-radius: 10px; background: var(--marca-noche); color: #fff; }
    h2 { font-size: 17px; }
    .muted { font-size: 13px; }
    .desc { margin: 0 0 12px; color: var(--tinta-2); font-size: 14px; line-height: 1.5; }
    ul { list-style: none; margin: 0; padding: 12px 0 0; border-top: 1px solid var(--linea); display: flex; flex-direction: column; gap: 8px; }
    li { display: flex; gap: 8px; font-size: 13.5px; line-height: 1.4; color: var(--tinta); }
    li mat-icon { flex: none; font-size: 18px; width: 18px; height: 18px; color: var(--marca-verde); }
    .no-titulo { margin: 14px 0 0; font-size: 12px; font-weight: 600; color: var(--mut); }
    ul.no { border-top: none; padding-top: 6px; }
    ul.no li { color: var(--mut); }
    ul.no li mat-icon { color: var(--linea-2); }
  `,
})
export class Roles implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected readonly roles = signal<RolFijo[]>([]);

  async ngOnInit(): Promise<void> {
    try {
      this.roles.set(await this.rest.get<RolFijo[]>('/api/admin/roles'));
    } catch (e) {
      this.avisos.error(e);
    }
  }

  /** Lo que tienen los demás roles de oficina y este no (el motorizado solo usa el celular: no se compara). */
  protected noIncluye(r: RolFijo): { codigo: string; descripcion: string }[] {
    if (r.motorizado) return [];
    const propios = new Set(r.permisos.map((p) => p.codigo));
    const vistos = new Set<string>();
    const faltan: { codigo: string; descripcion: string }[] = [];
    for (const otro of this.roles()) {
      if (otro.motorizado) continue;
      for (const p of otro.permisos) {
        if (!propios.has(p.codigo) && !vistos.has(p.codigo)) {
          vistos.add(p.codigo);
          faltan.push(p);
        }
      }
    }
    return faltan;
  }

  protected icono(id: number): string {
    return ICONO_ROL[id] ?? 'badge';
  }
}
