/**
 * js/programacion/core.js
 * Namespace y utilidades compartidas por todos los módulos de Programación.
 * Debe cargarse PRIMERO (antes que los demás archivos de esta carpeta).
 *
 * Espera que la vista defina antes de este script:
 *   <script>
 *     window.ProgConfig = {
 *       baseUrl: '<?= BASE_URL ?>',
 *       tecnicos: <?= json_encode($listaTecnicos) ?>
 *     };
 *   </script>
 */
window.Prog = window.Prog || {};

Prog.config = window.ProgConfig || { baseUrl: '', tecnicos: [] };

Prog.util = {
    qs(sel, ctx) {
        return (ctx || document).querySelector(sel);
    },
    qsa(sel, ctx) {
        return Array.from((ctx || document).querySelectorAll(sel));
    },
    /** Crea un elemento con atributos y (opcional) innerHTML, sin usar innerHTML para los valores. */
    el(tag, attrs = {}, html) {
        const e = document.createElement(tag);
        Object.entries(attrs).forEach(([k, v]) => e.setAttribute(k, v));
        if (html !== undefined) e.innerHTML = html;
        return e;
    },
    /** Construye <option> para técnicos, reutilizable en cualquier <select>. */
    opcionesTecnicos(seleccionadoId) {
        return Prog.config.tecnicos.map(t => {
            const sel = String(t.id_tecnico) === String(seleccionadoId) ? 'selected' : '';
            return `<option value="${t.id_tecnico}" ${sel}>${Prog.util.escapeHtml(t.nombre_tecnico)}</option>`;
        }).join('');
    },
    escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    },
    /** POST simple con application/x-www-form-urlencoded, devuelve JSON. */
    async postJSON(url, dataObj) {
        const body = new URLSearchParams();
        Object.entries(dataObj).forEach(([k, v]) => body.append(k, v));
        const resp = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body
        });
        return resp.json();
    }
};
