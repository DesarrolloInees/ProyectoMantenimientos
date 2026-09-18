/**
 * js/asistencia/exportar.js
 * Recolecta TODO lo editado (marcaciones, recargos, salarios, tarifas y
 * acta), lo guarda en la sesión del backend y dispara la descarga del Excel.
 * Depende de core.js y calculo.js.
 */
Asistencia.exportar = (function () {
    const U = Asistencia.util;
    const C = Asistencia.calculo;
    const urls = Asistencia.config.urls || {};

    /** Arma el payload completo que espera guardarEdicion() en el backend. */
    function construirPayload() {
        const payload = { registros: [], empleados: {} };

        U.qsa('.empleado-card').forEach(card => {
            const nombre = card.dataset.nombre;
            const cfg = C.recalcularEmpleado(card, false);

            payload.empleados[nombre] = {
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
            };

            U.qsa('tbody tr', card).forEach(tr => {
                const v = (sel) => {
                    const el = tr.querySelector(sel);
                    return el ? el.value : '';
                };

                payload.registros.push({
                    id: tr.dataset.id,
                    entrada_valor: v('.in-ent'),
                    salida_valor: v('.in-sal'),
                    hed_manual: v('.in-hed'),
                    hen_manual: v('.in-hen'),
                    rec_nocturno: v('.in-rec-noct'),
                    rec_domfest: v('.in-rec-domfest'),
                    hed_df: v('.in-hed-df'),
                    hen_df: v('.in-hen-df'),
                    rec_noct_df: v('.in-rec-noct-df'),
                    servicios: v('.in-servicios'),
                    novedades: v('.in-nov')
                });
            });
        });

        return payload;
    }

    /** Guarda la edición en sesión y descarga el Excel consolidado. */
    async function confirmarYDescargar(btn) {
        const boton = btn || document.getElementById('btnGuardarDescargar');
        const textoOriginal = boton ? boton.innerHTML : '';

        if (boton) {
            boton.disabled = true;
            boton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando Excel...';
        }

        try {
            const payload = construirPayload();
            const resp = await U.postJSON(urls.guardar, { datos: JSON.stringify(payload) });

            if (!resp.exito) {
                throw new Error(resp.error || 'No se pudo guardar la edición.');
            }

            window.location.href = urls.descargar;
        } catch (err) {
            console.error(err);
            Asistencia.eventos.mostrarAlerta('<i class="fas fa-exclamation-triangle"></i> ' + err.message);
        } finally {
            if (boton) {
                boton.disabled = false;
                boton.innerHTML = textoOriginal;
            }
        }
    }

    function enlazar() {
        const btn = document.getElementById('btnGuardarDescargar');
        if (btn) {
            btn.addEventListener('click', function () {
                confirmarYDescargar(btn);
            });
        }
    }

    return {
        construirPayload: construirPayload,
        confirmarYDescargar: confirmarYDescargar,
        enlazar: enlazar
    };
})();