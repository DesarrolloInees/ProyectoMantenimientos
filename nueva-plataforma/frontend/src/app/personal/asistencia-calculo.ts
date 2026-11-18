/**
 * Motor de cálculo de asistencias (igual al de la aplicación PHP, Ley 2466 vigente en 2026):
 *   Hora extra diurna   = base 100 % + recargo 25 %   (dom/fest: 100 % + 115 %)
 *   Hora extra nocturna = base 100 % + recargo 75 %   (dom/fest: 100 % + 165 %)
 *   Recargos ordinarios = 35 % nocturno | 90 % dom/fest | 125 % nocturno dom/fest
 */

export interface Tasas {
  hed: number;
  hen: number;
  rn: number;
  rdf: number;
  heddf: number;
  hendf: number;
  rndf: number;
}

export interface ConfigAsistencia {
  divisor: number;
  lv: string;
  sab: string;
  tope: string;
  nocturna: string;
  /** 'REAL' = la jornada empieza con la primera marcación; o una hora fija 'HH:MM'. */
  turno: string;
  tasas: Tasas;
}

export const TASAS_DEFECTO: Tasas = { hed: 0.25, hen: 0.75, rn: 0.35, rdf: 0.9, heddf: 1.15, hendf: 1.65, rndf: 1.25 };

export const CONFIG_DEFECTO: ConfigAsistencia = { divisor: 210, lv: '09:00', sab: '04:00', tope: '02:00', nocturna: '19:00', turno: 'REAL', tasas: { ...TASAS_DEFECTO } };

/** Campos de minutos que se pueden corregir a mano (null = cálculo automático). */
export const MANUALES = ['hed', 'hen', 'recNoct', 'recDomFest', 'hedDf', 'henDf', 'recNoctDf'] as const;
export type CampoManual = (typeof MANUALES)[number];

export interface DiaAsistencia {
  nombre: string;
  cargo: string;
  fecha: string;
  domingoFestivo: boolean;
  entrada: string | null;
  salida: string | null;
  servicios: number;
  falta: string | null;
  novedades?: string;
  manual?: Partial<Record<CampoManual, number | null>>;
}

export interface Calculo {
  trabajado: number;
  hed: number;
  hen: number;
  recNoct: number;
  recDomFest: number;
  hedDf: number;
  henDf: number;
  recNoctDf: number;
  valorExtras: number;
  valorRecargos: number;
}

export function aMinutos(t: string | null | undefined): number | null {
  if (!t || /falta/i.test(t)) return null;
  const m = /^(\d{1,2}):(\d{2})/.exec(t.trim());
  return m ? Number(m[1]) * 60 + Number(m[2]) : null;
}

export function aHora(min: number | null | undefined): string {
  if (min === null || min === undefined || min <= 0) return '';
  return `${String(Math.floor(min / 60)).padStart(2, '0')}:${String(Math.round(min % 60)).padStart(2, '0')}`;
}

export function calcular(d: DiaAsistencia, cfg: ConfigAsistencia, salario: number): Calculo {
  const vh = salario / (cfg.divisor || 210);
  const man = d.manual ?? {};
  const usar = (campo: CampoManual, auto: number) => (man[campo] ?? null) !== null ? (man[campo] as number) : auto;
  const ent = aMinutos(d.entrada);
  let sal = aMinutos(d.salida);
  const t = cfg.tasas;
  const r: Calculo = { trabajado: 0, hed: 0, hen: 0, recNoct: 0, recDomFest: 0, hedDf: 0, henDf: 0, recNoctDf: 0, valorExtras: 0, valorRecargos: 0 };

  if (ent !== null && sal !== null) {
    if (sal < ent) sal += 24 * 60; // turno que cruza la medianoche
    const inicioTurno = cfg.turno === 'REAL' ? ent : aMinutos(cfg.turno) ?? ent;
    const trabajado = Math.max(0, sal - Math.max(ent, inicioTurno));
    const dia = new Date(d.fecha + 'T00:00:00').getDay(); // 0 domingo … 6 sábado
    const limite = dia > 0 && dia < 6 ? aMinutos(cfg.lv) ?? 0 : aMinutos(cfg.sab) ?? 0;
    const tope = aMinutos(cfg.tope) ?? 0;
    const nocturna = aMinutos(cfg.nocturna) ?? 0;
    const extrasTope = Math.min(Math.max(0, trabajado - limite), tope);
    const noctAuto = Math.max(0, Math.min(extrasTope, Math.max(0, sal - nocturna)));
    const diurAuto = Math.max(0, extrasTope - noctAuto);
    r.trabajado = trabajado;
    r.hed = usar('hed', diurAuto);
    r.hen = usar('hen', noctAuto);
    const df = d.domingoFestivo;
    r.valorExtras = (r.hed / 60) * vh * (1 + (df ? t.heddf : t.hed)) + (r.hen / 60) * vh * (1 + (df ? t.hendf : t.hen));
    const noctOrd = Math.max(0, Math.min(trabajado, Math.max(0, sal - nocturna)) - r.hen);
    const ordinarias = Math.max(0, trabajado - extrasTope);
    const noctOrdDom = df ? Math.min(noctOrd, ordinarias) : 0;
    r.recNoct = usar('recNoct', df ? 0 : noctOrd);
    r.recDomFest = usar('recDomFest', df ? Math.max(0, ordinarias - noctOrdDom) : 0);
    r.recNoctDf = usar('recNoctDf', noctOrdDom);
  } else {
    r.recNoct = usar('recNoct', 0);
    r.recDomFest = usar('recDomFest', 0);
    r.recNoctDf = usar('recNoctDf', 0);
  }
  r.hedDf = usar('hedDf', 0);
  r.henDf = usar('henDf', 0);
  r.valorRecargos = ((r.recNoct * t.rn + r.recDomFest * t.rdf + r.hedDf * t.heddf + r.henDf * t.hendf + r.recNoctDf * t.rndf) / 60) * vh;
  return r;
}
