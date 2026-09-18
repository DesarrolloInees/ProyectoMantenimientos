/**
 * js/asistencia/calculo.js
 * Motor de cálculo de asistencias: espejo EXACTO de las fórmulas del Excel.
 *
 *   Hora extra diurna   = base 100% + recargo 25%   (dom/fest: 100% + 115% = 215%)
 *   Hora extra nocturna = base 100% + recargo 75%   (dom/fest: 100% + 165% = 265%)
 *   Recargos ordinarios = 35% nocturno | 90% dom/fest | 125% nocturno dom/fest
 *
 * Depende de core.js (Asistencia.util).
 */
Asistencia.calculo = (function () {
    const U = Asistencia.util;

    const SELECTORES = {
        entrada: '.in-ent',
        salida: '.in-sal',
        total: '.out-total',
        hed: '.in-hed',
        hen: '.in-hen',
        baseD: '.out-base-d',
        recD: '.out-rec-d',
        baseN: '.out-base-n',
        recN: '.out-rec-n'
    };

    /** Columnas manuales: input de horas -> ($ calculado, tasa, campo del payload) */
    const MANUALES = [
        { in: '.in-rec-noct', out: '.out-val-rec-noct', tasa: 'rn', campo: 'rec_nocturno' },
        { in: '.in-rec-domfest', out: '.out-val-rec-domfest', tasa: 'rdf', campo: 'rec_domfest' },
        { in: '.in-hed-df', out: '.out-val-hed-df', tasa: 'heddf', campo: 'hed_df' },
        { in: '.in-hen-df', out: '.out-val-hen-df', tasa: 'hendf', campo: 'hen_df' },
        { in: '.in-rec-noct-df', out: '.out-val-rec-noct-df', tasa: 'rndf', campo: 'rec_noct_df' }
    ];

    /** ¿El cargo es de técnico (motorizado)? */
    function esTecnico(cargo) {
        const c = String(cargo || '').toUpperCase();
        return c.indexOf('TÉCNICO') !== -1 || c.indexOf('TECNICO') !== -1;
    }

    /** Lee la configuración completa (salario, horarios y tasas) de la tarjeta. */
    function leerConfig(card) {
        const d = Asistencia.tasasDefecto;
        const h = Asistencia.horarioDefecto;
        const val = (sel, def) => {
            const el = card.querySelector(sel);
            return el ? el.value : def;
        };

        return {
            nombre: card.dataset.nombre || '',
            salario: U.num(val('.cfg-salario', 0)),
            divisor: U.num(val('.cfg-divisor', Asistencia.divisorDefecto), Asistencia.divisorDefecto) || Asistencia.divisorDefecto,
            lv: val('.cfg-lv', h.lv) || h.lv,
            sab: val('.cfg-sab', h.sab) || h.sab,
            tope: val('.cfg-tope', h.tope) || h.tope,
            nocturna: val('.cfg-noct', h.nocturna) || h.nocturna,
            turno: String(val('.cfg-turno', h.turno) || h.turno).toUpperCase(),
            actividad: val('.cfg-actividad', ''),
            autoriza: val('.cfg-autoriza', ''),
            tasas: {
                hed: U.num(val('.cfg-tasa-hed', d.hed * 100)) / 100,
                hen: U.num(val('.cfg-tasa-hen', d.hen * 100)) / 100,
                rn: U.num(val('.cfg-tasa-rn', d.rn * 100)) / 100,
                rdf: U.num(val('.cfg-tasa-rdf', d.rdf * 100)) / 100,
                heddf: U.num(val('.cfg-tasa-heddf', d.heddf * 100)) / 100,
                hendf: U.num(val('.cfg-tasa-hendf', d.hendf * 100)) / 100,
                rndf: U.num(val('.cfg-tasa-rndf', d.rndf * 100)) / 100
            }
        };
    }

    /** ¿El usuario escribió a mano esa celda? (entonces no la sobreescribimos) */
    function tocado(input) {
        return !!input && input.dataset.tocado === '1';
    }

    function setAuto(tr, sel, minutos) {
        const input = tr.querySelector(sel);
        if (!input || tocado(input)) return;
        input.value = minutos > 0 ? U.minsToStr(minutos) : '';
    }

    function minutosInput(tr, sel) {
        const el = tr.querySelector(sel);
        if (!el || el.value === '') return 0;
        const m = U.strToMins(el.value);
        return m === null ? 0 : m;
    }

    /** Calcula el $ de las 5 columnas de recargos y devuelve horas y valor. */
    function calcularManuales(tr, cfg, valorHora) {
        let horas = 0;
        let valor = 0;

        MANUALES.forEach(col => {
            const mins = minutosInput(tr, col.in);
            const dinero = (mins / 60) * valorHora * (cfg.tasas[col.tasa] || 0);
            const out = tr.querySelector(col.out);
            if (out) out.innerText = U.formatoPlata(dinero);
            horas += mins;
            valor += dinero;
        });

        return { horas: horas, valor: valor };
    }

    /**
     * Calcula UNA fila de día (equivalente exacto de una fila del Excel).
     * Devuelve el resumen numérico de la fila y lo deja en tr._calc.
     */
    function calcularFila(tr, cfg) {
        const domFest = tr.dataset.festivo === '1';
        const fecha = tr.dataset.fecha || '';
        const dia = fecha ? new Date(fecha + 'T00:00:00').getDay() : 1; // 0=Dom, 6=Sáb
        const valorHora = cfg.salario / cfg.divisor;

        const elEnt = tr.querySelector(SELECTORES.entrada);
        const elSal = tr.querySelector(SELECTORES.salida);
        const minEnt = elEnt ? U.strToMins(elEnt.value) : null;
        let minSal = elSal ? U.strToMins(elSal.value) : null;

        const res = {
            dia: fecha,
            domFest: domFest,
            horasTrabajadas: 0,
            horasExtras: 0,
            valorExtras: 0,
            horasRecargos: 0,
            valorRecargos: 0
        };

        const setTxt = (sel, txt) => {
            const el = tr.querySelector(sel);
            if (el) el.innerText = txt;
        };

        // ---- Día sin marcaciones completas ----
        if (minEnt === null || minSal === null) {
            const elTotalVacio = tr.querySelector(SELECTORES.total);
            if (elTotalVacio) elTotalVacio.value = '';

            setAuto(tr, SELECTORES.hed, 0);
            setAuto(tr, SELECTORES.hen, 0);
            [SELECTORES.baseD, SELECTORES.recD, SELECTORES.baseN, SELECTORES.recN].forEach(sel => setTxt(sel, '$0'));

            const manualVacio = calcularManuales(tr, cfg, valorHora);
            res.horasRecargos = manualVacio.horas;
            res.valorRecargos = manualVacio.valor;

            tr._calc = res;
            return res;
        }

        if (minSal < minEnt) minSal += 24 * 60; // turno que cruza medianoche

        // 1. Total trabajado (si el turno no es REAL, arranca a la hora fija)
        const inicioTurno = cfg.turno === 'REAL'
            ? minEnt
            : (U.strToMins(cfg.turno) === null ? minEnt : U.strToMins(cfg.turno));

        const trabajado = Math.max(0, minSal - Math.max(minEnt, inicioTurno));
        const elTotal = tr.querySelector(SELECTORES.total);
        if (elTotal) elTotal.value = U.minsToStr(trabajado);
        res.horasTrabajadas = trabajado;

        // 2. Extras con tope (jornada L-V vs sábado)
        const limite = (dia > 0 && dia < 6) ? (U.strToMins(cfg.lv) || 0) : (U.strToMins(cfg.sab) || 0);
        const tope = U.strToMins(cfg.tope) || 0;
        const nocturnaBase = U.strToMins(cfg.nocturna) || 0;

        const extrasBruto = Math.max(0, trabajado - limite);
        const extrasTope = Math.min(extrasBruto, tope);
        const extrasNoctAuto = Math.max(0, Math.min(extrasTope, Math.max(0, minSal - nocturnaBase)));
        const extrasDiurAuto = Math.max(0, extrasTope - extrasNoctAuto);

        setAuto(tr, SELECTORES.hen, extrasNoctAuto);
        setAuto(tr, SELECTORES.hed, extrasDiurAuto);

        // Si el usuario sobrescribió las horas extra a mano, se respetan
        const extrasDiur = tocado(tr.querySelector(SELECTORES.hed)) ? minutosInput(tr, SELECTORES.hed) : extrasDiurAuto;
        const extrasNoct = tocado(tr.querySelector(SELECTORES.hen)) ? minutosInput(tr, SELECTORES.hen) : extrasNoctAuto;

        // 3. Base 100% + recargo (diurna y nocturna)
        const baseD = (extrasDiur / 60) * valorHora;
        const recD = (extrasDiur / 60) * valorHora * (domFest ? cfg.tasas.heddf : cfg.tasas.hed);
        const baseN = (extrasNoct / 60) * valorHora;
        const recN = (extrasNoct / 60) * valorHora * (domFest ? cfg.tasas.hendf : cfg.tasas.hen);

        setTxt(SELECTORES.baseD, U.formatoPlata(baseD));
        setTxt(SELECTORES.recD, U.formatoPlata(recD));
        setTxt(SELECTORES.baseN, U.formatoPlata(baseN));
        setTxt(SELECTORES.recN, U.formatoPlata(recN));

        res.horasExtras = extrasDiur + extrasNoct;
        res.valorExtras = baseD + recD + baseN + recN;

        // 4. Auto-cálculo de los recargos ordinarios (siempre editable a mano).
        //    En dom/fest las ordinarias se pagan como recargo dom/fest (90%) o
        //    nocturno dom/fest (125%), sin duplicar la misma hora.
        const noctOrd = Math.max(0, Math.min(trabajado, Math.max(0, minSal - nocturnaBase)) - extrasNoct);
        const ordinarias = Math.max(0, trabajado - extrasTope);
        const noctOrdDom = domFest ? Math.min(noctOrd, ordinarias) : 0;

        setAuto(tr, '.in-rec-noct', domFest ? 0 : noctOrd);
        setAuto(tr, '.in-rec-domfest', domFest ? Math.max(0, ordinarias - noctOrdDom) : 0);
        setAuto(tr, '.in-rec-noct-df', noctOrdDom);

        // 5. $ de las columnas de recargos
        const manual = calcularManuales(tr, cfg, valorHora);
        res.horasRecargos = manual.horas;
        res.valorRecargos = manual.valor;

        tr._calc = res;
        return res;
    }

/** Suma del empleado: equivale a los "GRAN TOTAL" del mes en el Excel. */
    function totalesEmpleado(card) {
        const t = {
            horasTrabajadas: 0,
            horasExtras: 0,
            valorExtras: 0,
            horasRecargos: 0,
            valorRecargos: 0,
            servicios: 0,
            total: 0
        };

        U.qsa('tbody tr', card).forEach(tr => {
            const c = tr._calc || {};
            t.horasTrabajadas += c.horasTrabajadas || 0;
            t.horasExtras += c.horasExtras || 0;
            t.valorExtras += c.valorExtras || 0;
            t.horasRecargos += c.horasRecargos || 0;
            t.valorRecargos += c.valorRecargos || 0;

            const elServ = tr.querySelector('.in-servicios');
            t.servicios += elServ ? U.num(elServ.value) : 0;
        });

        t.total = t.valorExtras + t.valorRecargos;
        return t;
    }

    /** Pinta el pie de la tarjeta con los totales del mes (igual que el Excel). */
    function pintarTotales(card) {
        const t = totalesEmpleado(card);
        const set = (sel, txt) => {
            const el = card.querySelector(sel);
            if (el) el.innerText = txt;
        };

        set('.res-horas-trab', U.minsToStrLargo(t.horasTrabajadas));
        set('.res-horas-extras', U.minsToStrLargo(t.horasExtras));
        set('.res-valor-extras', U.formatoPlata(t.valorExtras));
        set('.res-horas-recargos', U.minsToStrLargo(t.horasRecargos));
        set('.res-valor-recargos', U.formatoPlata(t.valorRecargos));
        set('.res-servicios', String(t.servicios));
        set('.res-total-general', U.formatoPlata(t.total));

        return t;
    }

    /**
     * Recalcula TODAS las filas del empleado (cuando cambia salario, horario
     * o alguna tarifa) y guarda las preferencias en el navegador.
     */
    function recalcularEmpleado(card, guardarPreferencias) {
        const cfg = leerConfig(card);
        U.qsa('tbody tr', card).forEach(tr => calcularFila(tr, cfg));
        pintarTotales(card);

        if (guardarPreferencias !== false) {
            Asistencia.almacen.guardar(card.dataset.nombre, {
                salario: cfg.salario,
                divisor: cfg.divisor,
                lv: cfg.lv,
                sab: cfg.sab,
                tope: cfg.tope,
                nocturna: cfg.nocturna,
                turno: cfg.turno,
                actividad: cfg.actividad,
                autoriza: cfg.autoriza,
                tasas: cfg.tasas
            });
        }

        return cfg;
    }

    /** Totales generales de todos los empleados (para el panel superior). */
    function totalesGenerales() {
        const t = { horasExtras: 0, valorExtras: 0, horasRecargos: 0, valorRecargos: 0, total: 0 };
        U.qsa('.empleado-card').forEach(card => {
            const c = totalesEmpleado(card);
            t.horasExtras += c.horasExtras;
            t.valorExtras += c.valorExtras;
            t.horasRecargos += c.horasRecargos;
            t.valorRecargos += c.valorRecargos;
            t.total += c.total;
        });
        return t;
    }

    return {
        SELECTORES: SELECTORES,
        MANUALES: MANUALES,
        esTecnico: esTecnico,
        leerConfig: leerConfig,
        calcularFila: calcularFila,
        calcularManuales: calcularManuales,
        totalesEmpleado: totalesEmpleado,
        totalesGenerales: totalesGenerales,
        pintarTotales: pintarTotales,
        recalcularEmpleado: recalcularEmpleado
    };
})();