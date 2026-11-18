import { DatePipe } from '@angular/common';
import { Component, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatExpansionModule } from '@angular/material/expansion';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { Avisos } from '../core/infra';
import { pesos, Rest } from '../core/rest';
import { aHora, aMinutos, calcular, CampoManual, CONFIG_DEFECTO, ConfigAsistencia, DiaAsistencia, MANUALES } from './asistencia-calculo';

interface Empleado {
  nombre: string;
  cargo: string;
  dias: DiaAsistencia[];
}

const PREFS = 'inees.asistencia.preferencias';

/**
 * Procesador de asistencias: carga el CSV del huellero, lo cruza con los servicios y calcula horas extra y recargos.
 * Todo es editable antes de descargar el Excel (asistencia de PHP).
 */
@Component({
  selector: 'app-asistencia',
  imports: [FormsModule, DatePipe, MatButtonModule, MatIconModule, MatFormFieldModule, MatInputModule, MatSelectModule, MatExpansionModule],
  template: `
    <div class="pagina">
      <div class="encabezado">
        <h1>Asistencia (huellero + servicios)</h1>
        <label class="archivo mat-mdc-button"><mat-icon>upload_file</mat-icon> Cargar CSV del huellero
          <input type="file" accept=".csv,text/csv" (change)="procesar($event)" hidden /></label>
        <button mat-flat-button (click)="excel()" [disabled]="!empleados().length"><mat-icon>download</mat-icon> Descargar Excel</button>
      </div>
      <mat-expansion-panel class="config">
        <mat-expansion-panel-header><mat-panel-title>Horario y tasas</mat-panel-title>
          <mat-panel-description>Jornada L-V {{ cfg.lv }} · sábado {{ cfg.sab }} · tope {{ cfg.tope }} · nocturna desde {{ cfg.nocturna }}</mat-panel-description>
        </mat-expansion-panel-header>
        <div class="rejilla">
          <mat-form-field appearance="outline"><mat-label>Jornada lunes a viernes</mat-label><input matInput [(ngModel)]="cfg.lv" (change)="guardarPrefs()" /></mat-form-field>
          <mat-form-field appearance="outline"><mat-label>Jornada sábado</mat-label><input matInput [(ngModel)]="cfg.sab" (change)="guardarPrefs()" /></mat-form-field>
          <mat-form-field appearance="outline"><mat-label>Tope de extras por día</mat-label><input matInput [(ngModel)]="cfg.tope" (change)="guardarPrefs()" /></mat-form-field>
          <mat-form-field appearance="outline"><mat-label>Inicio franja nocturna</mat-label><input matInput [(ngModel)]="cfg.nocturna" (change)="guardarPrefs()" /></mat-form-field>
          <mat-form-field appearance="outline"><mat-label>Inicio de turno (REAL o HH:MM)</mat-label><input matInput [(ngModel)]="cfg.turno" (change)="guardarPrefs()" /></mat-form-field>
          <mat-form-field appearance="outline"><mat-label>Divisor de horas del mes</mat-label><input matInput type="number" [(ngModel)]="cfg.divisor" (change)="guardarPrefs()" /></mat-form-field>
          @for (t of tasas; track t[0]) {
            <mat-form-field appearance="outline"><mat-label>{{ t[1] }} (%)</mat-label>
              <input matInput type="number" [ngModel]="pct(t[0])" (ngModelChange)="ponerPct(t[0], $event)" /></mat-form-field>
          }
        </div>
      </mat-expansion-panel>
      @if (rango()) { <p class="muted">{{ rango() }}</p> }
      @if (sinCoincidencia().length) { <p class="chip aviso">No se encontraron en la plataforma: {{ sinCoincidencia().join(', ') }}</p> }
      @if (empleados().length) {
        <div class="kpis">
          <div class="tarjeta"><span class="muted">Total horas extra</span><h2>{{ pesos(totalGeneral().extras) }}</h2></div>
          <div class="tarjeta"><span class="muted">Total recargos</span><h2>{{ pesos(totalGeneral().recargos) }}</h2></div>
          <div class="tarjeta"><span class="muted">Total a pagar</span><h2>{{ pesos(totalGeneral().extras + totalGeneral().recargos) }}</h2></div>
        </div>
      }
      @for (e of empleados(); track e.nombre) {
        <mat-expansion-panel>
          <mat-expansion-panel-header>
            <mat-panel-title>{{ e.nombre }}</mat-panel-title>
            <mat-panel-description>{{ e.cargo }} · total {{ pesos(totalEmpleado(e)) }}</mat-panel-description>
          </mat-expansion-panel-header>
          <mat-form-field appearance="outline" subscriptSizing="dynamic"><mat-label>Salario mensual</mat-label>
            <input matInput type="number" [ngModel]="salarios()[e.nombre] ?? 0" (ngModelChange)="ponerSalario(e.nombre, $event)" /></mat-form-field>
          <div class="desliza">
            <table class="tabla compacta">
              <thead><tr><th>Día</th><th>Entrada</th><th>Salida</th><th class="num">Serv.</th><th>Trabajado</th>
                <th>HE diurna</th><th>HE nocturna</th><th>Rec. noct.</th><th>Rec. dom/fest</th><th>HE diur. D/F</th><th>HE noct. D/F</th><th>Rec. noct. D/F</th>
                <th class="num">$ HE</th><th class="num">$ recargos</th><th>Novedad</th></tr></thead>
              <tbody>
                @for (d of e.dias; track d.fecha) {
                  @let c = calc(d, e.nombre);
                  <tr [class.festivo]="d.domingoFestivo">
                    <td>{{ d.fecha | date: 'EEE dd/MM' }}</td>
                    <td><input class="h" [(ngModel)]="d.entrada" [placeholder]="d.falta === 'Falta entrada' ? 'Falta' : ''" (change)="refrescar()" /></td>
                    <td><input class="h" [(ngModel)]="d.salida" [placeholder]="d.falta === 'Falta salida' ? 'Falta' : ''" (change)="refrescar()" /></td>
                    <td class="num">{{ d.servicios }}</td>
                    <td>{{ aHora(c.trabajado) }}</td>
                    @for (m of manuales; track m) {
                      <td><input class="h" [class.tocado]="(d.manual?.[m] ?? null) !== null" [ngModel]="aHora(c[m])"
                                 (change)="manual(d, m, $any($event.target).value)" /></td>
                    }
                    <td class="num">{{ pesos(c.valorExtras) }}</td><td class="num">{{ pesos(c.valorRecargos) }}</td>
                    <td><input class="n" [(ngModel)]="d.novedades" /></td>
                  </tr>
                }
              </tbody>
            </table>
          </div>
        </mat-expansion-panel>
      } @empty {
        <div class="tarjeta vacio">Cargue el CSV del huellero (columnas: empleado, fecha, hora).</div>
      }
    </div>
  `,
  styles: `
    .archivo { display: inline-flex; align-items: center; gap: 6px; cursor: pointer; padding: 8px 14px; border: 1px solid var(--linea-2); border-radius: 20px; }
    .config { margin-bottom: 12px; }
    .desliza { overflow-x: auto; margin-top: 8px; }
    .h { width: 58px; padding: 3px; border: 1px solid var(--linea); border-radius: 4px; font: inherit; }
    .h.tocado { background: var(--aviso-fondo); border-color: var(--inees-ambar); }
    .n { width: 140px; padding: 3px; border: 1px solid var(--linea); border-radius: 4px; font: inherit; }
    tr.festivo td { background: var(--info-fondo); }
    .kpis h2 { margin: 4px 0 0; }
    .chip { display: block; padding: 8px 12px; margin-bottom: 8px; white-space: normal; }
  `,
})
export class Asistencia {
  private readonly rest = inject(Rest);
  private readonly avisos = inject(Avisos);
  protected readonly pesos = pesos;
  protected readonly aHora = aHora;
  protected readonly manuales = MANUALES;
  protected readonly tasas: [keyof ConfigAsistencia['tasas'], string][] = [['hed', 'Recargo HE diurna'], ['hen', 'Recargo HE nocturna'],
    ['rn', 'Recargo nocturno'], ['rdf', 'Recargo dom/fest'], ['heddf', 'Recargo HE diurna dom/fest'], ['hendf', 'Recargo HE nocturna dom/fest'],
    ['rndf', 'Recargo nocturno dom/fest']];
  protected cfg: ConfigAsistencia = structuredClone(CONFIG_DEFECTO);
  protected readonly empleados = signal<Empleado[]>([]);
  protected readonly salarios = signal<Record<string, number>>({});
  protected readonly sinCoincidencia = signal<string[]>([]);
  protected readonly rango = signal<string | null>(null);
  private readonly version = signal(0);
  protected readonly totalGeneral = computed(() => {
    this.version();
    let extras = 0, recargos = 0;
    for (const e of this.empleados()) for (const d of e.dias) {
      const c = calcular(d, this.cfg, this.salarios()[e.nombre] ?? 0);
      extras += c.valorExtras;
      recargos += c.valorRecargos;
    }
    return { extras, recargos };
  });

  constructor() {
    try {
      const p = JSON.parse(localStorage.getItem(PREFS) ?? 'null');
      if (p) {
        this.cfg = { ...this.cfg, ...p.cfg, tasas: { ...this.cfg.tasas, ...p.cfg?.tasas } };
        this.salarios.set(p.salarios ?? {});
      }
    } catch {
      /* sin preferencias */
    }
  }

  protected calc(d: DiaAsistencia, nombre: string) {
    this.version();
    return calcular(d, this.cfg, this.salarios()[nombre] ?? 0);
  }

  protected totalEmpleado(e: Empleado): number {
    this.version();
    return e.dias.reduce((s, d) => {
      const c = calcular(d, this.cfg, this.salarios()[e.nombre] ?? 0);
      return s + c.valorExtras + c.valorRecargos;
    }, 0);
  }

  protected pct(k: keyof ConfigAsistencia['tasas']): number {
    return Math.round(this.cfg.tasas[k] * 100);
  }

  protected ponerPct(k: keyof ConfigAsistencia['tasas'], v: number): void {
    this.cfg.tasas[k] = Number(v) / 100;
    this.guardarPrefs();
  }

  protected ponerSalario(nombre: string, v: number): void {
    this.salarios.update((s) => ({ ...s, [nombre]: Number(v) || 0 }));
    this.guardarPrefs();
  }

  protected manual(d: DiaAsistencia, campo: CampoManual, valor: string): void {
    d.manual = { ...(d.manual ?? {}), [campo]: valor.trim() === '' ? null : aMinutos(valor) ?? 0 };
    this.refrescar();
  }

  protected refrescar(): void {
    this.version.update((v) => v + 1);
  }

  guardarPrefs(): void {
    this.refrescar();
    try {
      localStorage.setItem(PREFS, JSON.stringify({ cfg: this.cfg, salarios: this.salarios() }));
    } catch {
      /* sin almacenamiento */
    }
  }

  async procesar(ev: Event): Promise<void> {
    const archivo = (ev.target as HTMLInputElement).files?.[0];
    if (!archivo) return;
    try {
      const r = await this.rest.enviar<{ desde: string; hasta: string; dias: DiaAsistencia[]; sinCoincidencia: string[] }>(
        '/api/personal/asistencia/procesar', { archivo });
      const porNombre = new Map<string, Empleado>();
      for (const d of r.dias) {
        if (!porNombre.has(d.nombre)) porNombre.set(d.nombre, { nombre: d.nombre, cargo: d.cargo, dias: [] });
        porNombre.get(d.nombre)!.dias.push({ ...d, novedades: '' });
      }
      this.empleados.set([...porNombre.values()]);
      this.sinCoincidencia.set(r.sinCoincidencia);
      this.rango.set(`Del ${r.desde} al ${r.hasta} · ${porNombre.size} empleados`);
    } catch (e) {
      this.avisos.error(e);
    } finally {
      (ev.target as HTMLInputElement).value = '';
    }
  }

  async excel(): Promise<void> {
    const filas = this.empleados().flatMap((e) => e.dias.map((d) => {
      const c = calcular(d, this.cfg, this.salarios()[e.nombre] ?? 0);
      return { nombre: e.nombre, cargo: e.cargo, fecha: d.fecha, domingoFestivo: d.domingoFestivo, entrada: d.entrada, salida: d.salida,
        servicios: d.servicios, minutosTrabajados: c.trabajado, extrasDiurnas: c.hed, extrasNocturnas: c.hen, recargoNocturno: c.recNoct,
        recargoDomFest: c.recDomFest, extrasDiurnasDomFest: c.hedDf, extrasNocturnasDomFest: c.henDf, recargoNocturnoDomFest: c.recNoctDf,
        valorExtras: Math.round(c.valorExtras), valorRecargos: Math.round(c.valorRecargos), novedades: d.novedades ?? '' };
    }));
    try {
      await this.rest.descargar('/api/personal/asistencia/excel', undefined, { filas, salarios: this.salarios() });
    } catch (e) {
      this.avisos.error(e);
    }
  }
}
