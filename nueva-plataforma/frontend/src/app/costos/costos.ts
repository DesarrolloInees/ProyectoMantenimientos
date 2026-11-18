import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatTabsModule } from '@angular/material/tabs';
import { Avisos } from '../core/infra';
import { pesos, Rest } from '../core/rest';

interface Mes {
  mes: string;
  personas: number;
  nomina: number;
  operativo: number;
  gastos: number;
  total: number;
}

interface Costo {
  id: number;
  nombre: string;
  cargo: string;
  salario: number | null;
  horasExtra: number | null;
  bonoMeta: number | null;
  gasolina: number | null;
  auxilioComunicacion: number | null;
  auxilioRodamiento: number | null;
}

interface Gasto {
  idGasto: number;
  concepto: string;
  categoria: string | null;
  valor: number;
  observacion: string | null;
}

const CAMPOS: [keyof Costo, string][] = [['salario', 'Salario'], ['horasExtra', 'Horas extra'], ['bonoMeta', 'Bono meta'], ['gasolina', 'Gasolina'],
  ['auxilioComunicacion', 'Aux. comunicación'], ['auxilioRodamiento', 'Aux. rodamiento']];

/** Costos del mes: nómina y gastos de los motorizados, nómina administrativa y gastos generales (costos de PHP). */
@Component({
  selector: 'app-costos',
  imports: [FormsModule, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule, MatTabsModule],
  template: `
    <div class="pagina">
      <div class="encabezado">
        <h1>Costos</h1>
        <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Mes</mat-label>
          <input matInput type="month" [(ngModel)]="mes" (change)="cargar()" /></mat-form-field>
      </div>
      <mat-tab-group animationDuration="0">
        <mat-tab label="Motorizados">
          <div class="contenedor-tabla">
            <table class="tabla compacta">
              <thead><tr><th>Técnico</th>@for (c of campos; track c[0]) { <th class="num">{{ c[1] }}</th> }<th class="num">Total</th></tr></thead>
              <tbody>
                @for (f of motorizados(); track f.id) {
                  <tr><td>{{ f.nombre }}</td>
                    @for (c of campos; track c[0]) { <td><input class="valor" type="number" [(ngModel)]="$any(f)[c[0]]" (change)="refrescar()" /></td> }
                    <td class="num"><strong>{{ pesos(total(f)) }}</strong></td></tr>
                }
                <tr class="total"><td>Total</td>@for (c of campos; track c[0]) { <td class="num">{{ pesos(suma(motorizados(), c[0])) }}</td> }
                  <td class="num"><strong>{{ pesos(granTotal(motorizados())) }}</strong></td></tr>
              </tbody>
            </table>
          </div>
          <div class="acciones">
            <button mat-button (click)="eliminarMes('motorizados')">Borrar el mes</button>
            <button mat-flat-button (click)="guardarMotorizados()">Guardar costos de motorizados</button>
          </div>
          <h3>Meses registrados</h3>
          <table class="tabla"><thead><tr><th>Mes</th><th class="num">Técnicos</th><th class="num">Nómina</th><th class="num">Operativo</th><th class="num">Total</th></tr></thead>
            <tbody>@for (m of mesesMot(); track m.mes) {
              <tr (click)="mes = m.mes; cargar()" class="clic"><td>{{ m.mes }}</td><td class="num">{{ m.personas }}</td><td class="num">{{ pesos(m.nomina) }}</td>
                <td class="num">{{ pesos(m.operativo) }}</td><td class="num">{{ pesos(m.total) }}</td></tr> }</tbody></table>
        </mat-tab>
        <mat-tab label="Administrativos">
          <div class="contenedor-tabla">
            <table class="tabla compacta">
              <thead><tr><th>Persona</th><th>Cargo</th>@for (c of campos.slice(0, 5); track c[0]) { <th class="num">{{ c[1] }}</th> }<th class="num">Total</th></tr></thead>
              <tbody>
                @for (f of nomina(); track f.id) {
                  <tr><td>{{ f.nombre }}</td><td>{{ f.cargo }}</td>
                    @for (c of campos.slice(0, 5); track c[0]) { <td><input class="valor" type="number" [(ngModel)]="$any(f)[c[0]]" (change)="refrescar()" /></td> }
                    <td class="num"><strong>{{ pesos(total(f)) }}</strong></td></tr>
                }
              </tbody>
            </table>
          </div>
          <div class="acciones"><button mat-flat-button (click)="guardarNomina()">Guardar nómina administrativa</button></div>
          <h3>Gastos generales del mes</h3>
          <table class="tabla">
            <thead><tr><th>Concepto</th><th>Categoría</th><th class="num">Valor</th><th>Observación</th><th></th></tr></thead>
            <tbody>
              @for (g of gastos(); track g.idGasto) {
                <tr><td><input [(ngModel)]="g.concepto" /></td><td><input [(ngModel)]="g.categoria" /></td>
                  <td><input class="valor" type="number" [(ngModel)]="g.valor" /></td><td><input [(ngModel)]="g.observacion" /></td>
                  <td class="botones-fila"><button mat-icon-button (click)="guardarGasto(g)" aria-label="Guardar"><mat-icon>save</mat-icon></button>
                    <button mat-icon-button (click)="eliminarGasto(g)" aria-label="Eliminar"><mat-icon>delete</mat-icon></button></td></tr>
              }
              <tr><td><input [(ngModel)]="nuevoGasto.concepto" placeholder="Nuevo concepto" /></td><td><input [(ngModel)]="nuevoGasto.categoria" placeholder="Administrativo" /></td>
                <td><input class="valor" type="number" [(ngModel)]="nuevoGasto.valor" /></td><td><input [(ngModel)]="nuevoGasto.observacion" /></td>
                <td><button mat-stroked-button (click)="agregarGasto()" [disabled]="!nuevoGasto.concepto">Agregar</button></td></tr>
            </tbody>
          </table>
          <p class="muted">Total del mes: nómina {{ pesos(granTotal(nomina())) }} + gastos {{ pesos(totalGastos()) }} = <strong>{{ pesos(granTotal(nomina()) + totalGastos()) }}</strong></p>
          <div class="acciones"><button mat-button (click)="eliminarMes('administrativos')">Borrar el mes administrativo</button></div>
          <h3>Meses registrados</h3>
          <table class="tabla"><thead><tr><th>Mes</th><th class="num">Personas</th><th class="num">Nómina</th><th class="num">Gastos</th><th class="num">Total</th></tr></thead>
            <tbody>@for (m of mesesAdm(); track m.mes) {
              <tr (click)="mes = m.mes; cargar()" class="clic"><td>{{ m.mes }}</td><td class="num">{{ m.personas }}</td><td class="num">{{ pesos(m.nomina) }}</td>
                <td class="num">{{ pesos(m.gastos) }}</td><td class="num">{{ pesos(m.total) }}</td></tr> }</tbody></table>
        </mat-tab>
      </mat-tab-group>
    </div>
  `,
  styles: `
    .valor { width: 110px; padding: 4px; text-align: right; border: 1px solid var(--linea); border-radius: 4px; font: inherit; }
    td input:not(.valor) { width: 100%; padding: 4px; border: 1px solid var(--linea); border-radius: 4px; font: inherit; }
    tr.total td { background: var(--fondo); font-weight: 600; }
    .clic { cursor: pointer; }
    h3 { margin: 20px 0 8px; }
  `,
})
export class Costos implements OnInit {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected readonly pesos = pesos;
  protected readonly campos = CAMPOS;
  protected mes = new Date().toISOString().substring(0, 7);
  protected readonly motorizados = signal<Costo[]>([]);
  protected readonly nomina = signal<Costo[]>([]);
  protected readonly gastos = signal<Gasto[]>([]);
  protected readonly mesesMot = signal<Mes[]>([]);
  protected readonly mesesAdm = signal<Mes[]>([]);
  private readonly version = signal(0);
  protected readonly totalGastos = computed(() => {
    this.version();
    return this.gastos().reduce((s, g) => s + (Number(g.valor) || 0), 0);
  });
  protected nuevoGasto: Partial<Gasto> = { concepto: '', categoria: '', valor: 0, observacion: '' };

  ngOnInit(): void {
    this.cargar();
  }

  async cargar(): Promise<void> {
    try {
      const [mot, adm, mm, ma] = await Promise.all([
        this.rest.get<Costo[]>('/api/costos/motorizados', { mes: this.mes }),
        this.rest.get<{ nomina: Costo[]; gastos: Gasto[] }>('/api/costos/administrativos', { mes: this.mes }),
        this.rest.get<Mes[]>('/api/costos/motorizados/meses'), this.rest.get<Mes[]>('/api/costos/administrativos/meses')]);
      this.motorizados.set(mot);
      this.nomina.set(adm.nomina);
      this.gastos.set(adm.gastos);
      this.mesesMot.set(mm);
      this.mesesAdm.set(ma);
    } catch (e) {
      this.avisos.error(e);
    }
  }

  protected refrescar(): void {
    this.version.update((v) => v + 1);
  }

  protected total(f: Costo): number {
    this.version();
    return CAMPOS.reduce((s, c) => s + (Number(f[c[0]]) || 0), 0);
  }

  protected suma(l: Costo[], campo: keyof Costo): number {
    this.version();
    return l.reduce((s, f) => s + (Number(f[campo]) || 0), 0);
  }

  protected granTotal(l: Costo[]): number {
    return l.reduce((s, f) => s + this.total(f), 0);
  }

  async guardarMotorizados(): Promise<void> {
    await this.hacer(async () => this.motorizados.set(await this.rest.put<Costo[]>('/api/costos/motorizados', this.motorizados(), { mes: this.mes })), 'Costos guardados.');
  }

  async guardarNomina(): Promise<void> {
    await this.hacer(async () => {
      const r = await this.rest.put<{ nomina: Costo[]; gastos: Gasto[] }>('/api/costos/administrativos/nomina', this.nomina(), { mes: this.mes });
      this.nomina.set(r.nomina);
    }, 'Nómina guardada.');
  }

  async agregarGasto(): Promise<void> {
    await this.hacer(async () => {
      await this.rest.post('/api/costos/administrativos/gastos', this.nuevoGasto, { mes: this.mes });
      this.nuevoGasto = { concepto: '', categoria: '', valor: 0, observacion: '' };
      await this.cargar();
    }, 'Gasto agregado.');
  }

  async guardarGasto(g: Gasto): Promise<void> {
    await this.hacer(() => this.rest.put(`/api/costos/administrativos/gastos/${g.idGasto}`, g), 'Gasto actualizado.');
    this.refrescar();
  }

  async eliminarGasto(g: Gasto): Promise<void> {
    if (!confirm(`¿Eliminar el gasto «${g.concepto}»?`)) return;
    await this.hacer(async () => {
      await this.rest.delete(`/api/costos/administrativos/gastos/${g.idGasto}`);
      await this.cargar();
    }, 'Gasto eliminado.');
  }

  async eliminarMes(tipo: 'motorizados' | 'administrativos'): Promise<void> {
    if (!confirm(`¿Borrar todos los costos ${tipo} de ${this.mes}?`)) return;
    await this.hacer(async () => {
      await this.rest.delete(`/api/costos/${tipo}`, { mes: this.mes });
      await this.cargar();
    }, 'Mes borrado.');
  }

  private async hacer(f: () => Promise<unknown>, ok: string): Promise<void> {
    try {
      await f();
      this.avisos.ok(ok);
      this.mesesMot.set(await this.rest.get<Mes[]>('/api/costos/motorizados/meses'));
      this.mesesAdm.set(await this.rest.get<Mes[]>('/api/costos/administrativos/meses'));
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
