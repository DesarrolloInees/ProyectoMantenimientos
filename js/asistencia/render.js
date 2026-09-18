/**
 * js/asistencia/render.js
 * Construye las tarjetas por empleado con las 23 columnas del Excel:
 * jornada + horas extra (base 100% y recargo) + recargos/dom-fest + otros.
 * Depende de core.js y calculo.js.
 */
Asistencia.render = (function () {
    const U = Asistencia.util;
    const C = Asistencia.calculo;

    /** Inputs de configuración del empleado (salario + horarios). */
    function barraConfig(cfg) {
        return `
            <div class="config-bar">
                <div><i class="fas fa-money-bill-wave text-success"></i> Básico:
                    <input type="number" class="config-input config-input-money cfg-input cfg-salario" value="${cfg.salario}"></div>
                <div><i class="fas fa-clock text-primary"></i> L-V:
                    <input type="time" class="config-input cfg-input cfg-lv" value="${cfg.lv}"></div>
                <div>Sáb:
                    <input type="time" class="config-input cfg-input cfg-sab" value="${cfg.sab}"></div>
                <div>Lím. Ext:
                    <input type="time" class="config-input cfg-input cfg-tope" value="${cfg.tope}"></div>
                <div>Ini. Turno:
                    <input type="text" class="config-input cfg-input cfg-turno" value="${cfg.turno}" title="Escribe REAL o una hora (07:00)"></div>
                <div>Ini. Noct:
                    <input type="time" class="config-input cfg-input cfg-noct" value="${cfg.nocturna}"></div>
                <button type="button" class="btn-tasas js-toggle-tasas">
                    <i class="fas fa-percent"></i> Tarifas
                </button>
            </div>`;
    }

    /** Panel de tarifas EDITABLES (todas las que usa el Excel). */
    function panelTarifas(tasas, divisor) {
        const item = (clase, etiqueta, valor, ayuda) => `
            <div class="tarifa-item" title="${ayuda}">
                <label>${etiqueta}</label>
                <div class="tarifa-input">
                    <input type="number" step="0.01" min="0" class="config-input cfg-input ${clase}" value="${Math.round(valor * 10000) / 100}">
                    <span>%</span>
                </div>
            </div>`;

        return `
            <div class="tarifas-panel d-none">
                <div class="tarifas-titulo"><i class="fas fa-percent"></i> Tarifas editables (se escriben en el Excel)</div>
                <div class="tarifas-grid">
                    ${item('cfg-tasa-hed', 'H.E. Diurna', tasas.hed, 'Recargo sumado a la base 100% (125% total)')}
                    ${item('cfg-tasa-hen', 'H.E. Nocturna', tasas.hen, 'Recargo sumado a la base 100% (175% total)')}
                    ${item('cfg-tasa-rn', 'Nocturno ordinario', tasas.rn, 'Recargo de la franja nocturna normal')}
                    ${item('cfg-tasa-rdf', 'Dom/Fest ordinario', tasas.rdf, 'Recargo dominical/festivo de las horas ordinarias')}
                    ${item('cfg-tasa-heddf', 'H.E. Diurna Dom/Fest', tasas.heddf, 'Recargo dom/fest de la H.E. diurna (215% total)')}
                    ${item('cfg-tasa-hendf', 'H.E. Nocturna Dom/Fest', tasas.hendf, 'Recargo dom/fest de la H.E. nocturna (265% total)')}
                    ${item('cfg-tasa-rndf', 'Nocturno Dom/Fest', tasas.rndf, 'Recargo nocturno en dominical/festivo (125%)')}
                    <div class="tarifa-item" title="Horas del mes para calcular el valor de la hora (ley: 210)">
                        <label>Divisor horas mes</label>
                        <div class="tarifa-input">
                            <input type="number" step="1" min="1" class="config-input cfg-input cfg-divisor" value="${divisor}">
                        </div>
                    </div>
                </div>
            </div>`;
    }

    /** Datos del acta que van a la hoja "Resumen Extras". */
    function barraActa(cfg) {
        return `
            <div class="acta-bar">
                <div><i class="fas fa-tasks text-secondary"></i> Actividad realizada:
                    <input type="text" class="config-input cfg-input cfg-actividad" value="${U.escapeHtml(cfg.actividad)}"
                        placeholder="Se escribe en la hoja Resumen Extras"></div>
                <div><i class="fas fa-user-check text-secondary"></i> Quién autoriza:
                    <input type="text" class="config-input cfg-input cfg-autoriza" value="${U.escapeHtml(cfg.autoriza)}"
                        placeholder="Ej: Autorizados por don Antonio"></div>
            </div>`;
    }

/** Encabezado de la tabla: grupos + las 23 columnas del Excel. */
    function encabezadoTabla() {
        return `
            <thead>
                <tr class="fila-grupos">
                    <th colspan="7" class="grupo-jornada">JORNADA</th>
                    <th colspan="4" class="grupo-extra">HORAS EXTRA (BASE 100% + RECARGO)</th>
                    <th colspan="10" class="grupo-recargo">RECARGOS Y DOM/FEST</th>
                    <th colspan="2" class="grupo-otros">OTROS</th>
                </tr>
                <tr>
                    <th class="col-fecha">FECHA</th>
                    <th class="col-hora">H. ENTRADA</th>
                    <th class="col-hora">H. SALIDA</th>
                    <th class="col-hora">TOTAL TRABAJADO</th>
                    <th class="col-hora">H. EXTRA DIURNA</th>
                    <th class="col-hora">H. EXTRA NOCTURNA</th>
                    <th class="col-dia">DOM/FEST</th>
                    <th class="col-dinero th-base">VALOR BASE<br>H.E. DIURNA (100%)</th>
                    <th class="col-dinero th-recargo">RECARGO<br>H.E. DIURNA</th>
                    <th class="col-dinero th-base">VALOR BASE<br>H.E. NOCTURNA (100%)</th>
                    <th class="col-dinero th-recargo">RECARGO<br>H.E. NOCTURNA</th>
                    <th class="col-manual"># RECARGO<br>NOCTURNO</th>
                    <th class="col-dinero th-recargo">VALOR RECARGO<br>NOCTURNO</th>
                    <th class="col-manual"># RECARGO<br>DOM/FEST</th>
                    <th class="col-dinero th-recargo">VALOR RECARGO<br>DOM/FEST</th>
                    <th class="col-manual"># H.E. DIURNA<br>DOM/FEST</th>
                    <th class="col-dinero th-recargo">VALOR H.E. DIURNA<br>DOM/FEST</th>
                    <th class="col-manual"># H.E. NOCTURNA<br>DOM/FEST</th>
                    <th class="col-dinero th-recargo">VALOR H.E. NOCTURNA<br>DOM/FEST</th>
                    <th class="col-manual"># RECARGO NOCTURNO<br>DOM/FEST</th>
                    <th class="col-dinero th-recargo">VALOR RECARGO NOCTURNO<br>DOM/FEST</th>
                    <th class="col-servicios">SERVICIOS</th>
                    <th class="col-novedades">NOVEDADES</th>
                </tr>
            </thead>`;
    }

    /** Pie de la tarjeta: los mismos totales del mes que trae el Excel. */
    function resumenEmpleado() {
        return `
            <div class="empleado-resumen">
                <div class="res-item">
                    <span>Horas trabajadas</span><b class="res-horas-trab">00:00</b>
                </div>
                <div class="res-item">
                    <span>Horas extra</span><b class="res-horas-extras">00:00</b>
                </div>
                <div class="res-item res-verde">
                    <span>$ Horas extra</span><b class="res-valor-extras">$0</b>
                </div>
                <div class="res-item">
                    <span>Horas dom/recargos</span><b class="res-horas-recargos">00:00</b>
                </div>
                <div class="res-item res-morado">
                    <span>$ Recargos y dom/fest</span><b class="res-valor-recargos">$0</b>
                </div>
                <div class="res-item">
                    <span>Servicios</span><b class="res-servicios">0</b>
                </div>
                <div class="res-item res-total">
                    <span>Total general a pagar</span><b class="res-total-general">$0</b>
                </div>
            </div>`;
    }

    /** Una fila de día con los inputs y las celdas de dinero. */
    function filaDia(r) {
        const domFest = Number(r.dom_fest) === 1;
        const clase = domFest ? 'row-festivo' : '';
        const val = (campo) => {
            const v = r[campo] || '';
            return v ? U.escapeHtml(v) : '';
        };

        return `
            <tr class="${clase}" data-id="${U.escapeHtml(r.id)}" data-fecha="${U.escapeHtml(r.fecha_raw)}"
                data-festivo="${domFest ? '1' : '0'}">
                <td class="text-start fw-bold col-fecha">
                    ${U.escapeHtml(r.fecha_formateada)}
                    ${domFest ? '<i class="fas fa-star text-warning" title="Dominical/Festivo"></i>' : ''}
                </td>
                <td><input type="time" class="input-grid in-ent" value="${val('entrada_valor')}"></td>
                <td><input type="time" class="input-grid in-sal" value="${val('salida_valor')}"></td>
                <td><input type="text" class="input-grid input-readonly out-total" readonly></td>

                <td><input type="time" class="input-grid in-hed"></td>
                <td><input type="time" class="input-grid in-hen"></td>
                <td class="col-dia">
                    <input type="checkbox" class="chk-domfest in-domfest" ${domFest ? 'checked' : ''}
                        title="Marca/desmarca el día como dominical o festivo">
                </td>

                <td class="col-dinero out-base-d">$0</td>
                <td class="col-dinero out-rec-d">$0</td>
                <td class="col-dinero out-base-n">$0</td>
                <td class="col-dinero out-rec-n">$0</td>

                <td class="col-manual"><input type="time" class="input-grid in-rec-noct" value="${val('rec_nocturno')}"></td>
                <td class="col-dinero out-val-rec-noct">$0</td>
                <td class="col-manual"><input type="time" class="input-grid in-rec-domfest" value="${val('rec_domfest')}"></td>
                <td class="col-dinero out-val-rec-domfest">$0</td>
                <td class="col-manual"><input type="time" class="input-grid in-hed-df" value="${val('hed_df')}"></td>
                <td class="col-dinero out-val-hed-df">$0</td>
                <td class="col-manual"><input type="time" class="input-grid in-hen-df" value="${val('hen_df')}"></td>
                <td class="col-dinero out-val-hen-df">$0</td>
                <td class="col-manual"><input type="time" class="input-grid in-rec-noct-df" value="${val('rec_noct_df')}"></td>
                <td class="col-dinero out-val-rec-noct-df">$0</td>

                <td><input type="number" min="0" class="input-grid in-servicios" value="${Number(r.servicios) || 0}"></td>
                <td><input type="text" class="input-grid in-nov" value="${U.escapeHtml(r.novedades || '')}"></td>
            </tr>`;
    }

/** Configuración inicial de un empleado (memoria local + valores por defecto). */
    function configInicial(nombre, cargo) {
        const h = Asistencia.horarioDefecto;
        const guardado = Asistencia.almacen.leer(nombre) || {};
        const tasas = Object.assign({}, Asistencia.tasasDefecto, guardado.tasas || {});

        return {
            salario: guardado.salario !== undefined ? guardado.salario : 1300000,
            divisor: guardado.divisor || Asistencia.divisorDefecto,
            lv: guardado.lv || h.lv,
            sab: guardado.sab || h.sab,
            tope: guardado.tope || h.tope,
            nocturna: guardado.nocturna || h.nocturna,
            // Los técnicos arrancan con su primera marcación; los administrativos a las 07:00
            turno: guardado.turno || (C.esTecnico(cargo) ? 'REAL' : '07:00'),
            actividad: guardado.actividad || '',
            autoriza: guardado.autoriza || '',
            tasas: tasas,
            divisorBase: Asistencia.divisorDefecto
        };
    }

    /** Tarjeta completa de un empleado. */
    function tarjetaEmpleado(nombre, info) {
        const cfg = configInicial(nombre, info.cargo);

        let filas = '';
        info.registros.forEach(r => {
            filas += filaDia(r);
        });

        return `
            <div class="empleado-card" data-nombre="${U.escapeHtml(nombre)}" data-cargo="${U.escapeHtml(info.cargo)}">
                <div class="empleado-header">
                    <h5 class="m-0"><i class="fas fa-user-tie me-2"></i>${U.escapeHtml(nombre)}
                        <span class="badge-cargo ms-2">${U.escapeHtml(info.cargo)}</span></h5>
                    <span class="pill-total"><i class="fas fa-coins"></i> <b class="res-total-general">$0</b></span>
                </div>
                ${barraConfig(cfg)}
                ${panelTarifas(cfg.tasas, cfg.divisor)}
                ${barraActa(cfg)}
                <div class="table-responsive tabla-scroll">
                    <table class="table-nomina">
                        ${encabezadoTabla()}
                        <tbody>${filas}</tbody>
                    </table>
                </div>
                ${resumenEmpleado()}
            </div>`;
    }

    /** Agrupa los registros por empleado (respetando el orden de llegada). */
    function agrupar(registros) {
        const emps = {};
        (registros || []).forEach(r => {
            if (!emps[r.nombre]) {
                emps[r.nombre] = { cargo: r.cargo, registros: [] };
            }
            emps[r.nombre].registros.push(r);
        });
        return emps;
    }

    /** Pinta TODAS las tarjetas y recalcula cada una. */
    function renderizar(registros) {
        const contenedor = document.getElementById('tablasEmpleados');
        if (!contenedor) return;

        const emps = agrupar(registros);
        let html = '';
        for (const [nombre, info] of Object.entries(emps)) {
            html += tarjetaEmpleado(nombre, info);
        }
        contenedor.innerHTML = html;

        U.qsa('.empleado-card').forEach(card => C.recalcularEmpleado(card, false));
        pintarTotalesGenerales();
    }

    /** Panel superior con los totales generales (equivale a la hoja Resumen Extras). */
    function pintarTotalesGenerales() {
        const t = C.totalesGenerales();
        const set = (sel, txt) => {
            const el = document.querySelector(sel);
            if (el) el.innerText = txt;
        };

        set('#tgl-empleados', String(U.qsa('.empleado-card').length));
        set('#tgl-horas-extras', U.minsToStrLargo(t.horasExtras));
        set('#tgl-valor-extras', U.formatoPlata(t.valorExtras));
        set('#tgl-valor-recargos', U.formatoPlata(t.valorRecargos));
        set('#tgl-total', U.formatoPlata(t.total));

        return t;
    }

    return {
        renderizar: renderizar,
        pintarTotalesGenerales: pintarTotalesGenerales,
        tarjetaEmpleado: tarjetaEmpleado,
        agrupar: agrupar,
        configInicial: configInicial
    };
})();