/**
 * js/asistencia/eventos.js
 * Enlaza todos los eventos: subida del CSV, edición en vivo, panel de
 * tarifas, marca manual de dom/fest y arranque del export.
 * Depende de core.js, calculo.js, render.js y exportar.js.
 */
Asistencia.eventos = (function () {
    const U = Asistencia.util;
    const C = Asistencia.calculo;
    const R = Asistencia.render;
    const urls = Asistencia.config.urls || {};

    /** Celdas que el usuario puede sobrescribir a mano (no se auto-pisan). */
    const TOCABLES = ['.in-hed', '.in-hen', '.in-rec-noct', '.in-rec-domfest', '.in-hed-df', '.in-hen-df', '.in-rec-noct-df'];

    function mostrarAlerta(mensaje, tipo) {
        const alerta = document.getElementById('alertaMensaje');
        if (!alerta) return;
        alerta.className = 'alert alert-' + (tipo || 'danger') + ' shadow-sm rounded';
        alerta.innerHTML = mensaje;
        alerta.classList.remove('d-none');
    }

    function ocultarAlerta() {
        const alerta = document.getElementById('alertaMensaje');
        if (alerta) alerta.classList.add('d-none');
    }

    /** Subida del CSV del reloj biométrico. */
    function enlazarFormulario() {
        const form = document.getElementById('formHuellero');
        if (!form) return;

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const btn = document.getElementById('btnProcesar');
            const editor = document.getElementById('editorContainer');
            const textoBoton = btn ? btn.innerHTML : '';

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            }
            ocultarAlerta();
            if (editor) editor.classList.add('d-none');

            try {
                const resp = await fetch(urls.procesar, { method: 'POST', body: new FormData(form) });
                const data = JSON.parse(await resp.text());

                if (data.exito) {
                    R.renderizar(data.datos);
                    if (editor) editor.classList.remove('d-none');
                    mostrarAlerta('<i class="fas fa-check-circle"></i> ' + (data.mensaje || 'Archivo procesado.'), 'success');
                } else {
                    mostrarAlerta('<i class="fas fa-exclamation-triangle"></i> ' + (data.error || 'No se pudo procesar el archivo.'));
                }
            } catch (err) {
                console.error(err);
                mostrarAlerta('<i class="fas fa-bug"></i> Error crítico al procesar el archivo. Revisa la consola (F12).');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = textoBoton;
                }
            }
        });
    }

/** Marca/limpia las celdas sobrescritas a mano. */
    function limpiarTocados(tr) {
        TOCABLES.forEach(sel => {
            const el = tr.querySelector(sel);
            if (el) delete el.dataset.tocado;
        });
    }

    /** Edición en vivo dentro de las tablas de cada empleado. */
    function enlazarEditor() {
        const contenedor = document.getElementById('tablasEmpleados');
        if (!contenedor) return;

        const alCambiar = function (e) {
            const target = e.target;
            const card = target.closest('.empleado-card');
            if (!card) return;

            // 1. Cambió salario, horario, tarifa o divisor -> recalcula toda la tarjeta
            if (target.classList.contains('cfg-input')) {
                C.recalcularEmpleado(card);
                R.pintarTotalesGenerales();
                return;
            }

            const tr = target.closest('tbody tr');
            if (!tr) return;

            // 2. El usuario marca/desmarca el día como dominical/festivo
            if (target.classList.contains('in-domfest')) {
                tr.dataset.festivo = target.checked ? '1' : '0';
                tr.classList.toggle('row-festivo', target.checked);
                limpiarTocados(tr); // al cambiar el tipo de día, los autos se recalculan
                C.calcularFila(tr, C.leerConfig(card));
                C.pintarTotales(card);
                R.pintarTotalesGenerales();
                return;
            }

            // 3. Si escribió a mano una celda auto-calculable, se respeta su valor
            TOCABLES.forEach(sel => {
                if (target.matches(sel)) target.dataset.tocado = '1';
            });

            // 4. Recalcular la fila afectada
            C.calcularFila(tr, C.leerConfig(card));
            C.pintarTotales(card);
            R.pintarTotalesGenerales();
        };

        contenedor.addEventListener('input', alCambiar);
        contenedor.addEventListener('change', alCambiar);
    }

    /** Botón "Tarifas": muestra/oculta el panel de porcentajes del empleado. */
    function enlazarTasas() {
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.js-toggle-tasas');
            if (!btn) return;

            const card = btn.closest('.empleado-card');
            const panel = card ? card.querySelector('.tarifas-panel') : null;
            if (panel) panel.classList.toggle('d-none');
        });
    }

    /** Arranque de todos los eventos. */
    function iniciar() {
        enlazarFormulario();
        enlazarEditor();
        enlazarTasas();
        if (Asistencia.exportar && Asistencia.exportar.enlazar) {
            Asistencia.exportar.enlazar();
        }
    }

    return {
        iniciar: iniciar,
        mostrarAlerta: mostrarAlerta,
        ocultarAlerta: ocultarAlerta
    };
})();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', Asistencia.eventos.iniciar);
} else {
    Asistencia.eventos.iniciar();
}