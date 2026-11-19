/**
 * js/asistencia/core.js
 * Namespace, tasas y utilidades compartidas por los módulos de Asistencias.
 * Debe cargarse PRIMERO (antes que los demás archivos de esta carpeta).
 *
 * La vista define antes de este script:
 *   <script>
 *     window.AsistenciaConfig = {
 *       baseUrl: '<?= BASE_URL ?>',
 *       urls: { procesar: '...', guardar: '...', descargar: '...' },
 *       tasas: { hed: 0.25, ... },
 *       horario: { lv: '09:00', ... },
 *       divisor: 210
 *     };
 *   </script>
 */
window.Asistencia = window.Asistencia || {};

Asistencia.config = window.AsistenciaConfig || { baseUrl: '', urls: {}, tasas: {}, horario: {} };

/** Tasas por defecto (Ley 2466 — vigentes 2026). Se pueden editar en pantalla. */
Asistencia.tasasDefecto = Object.assign({
    hed: 0.25,   // recargo H.E. diurna          -> 125% con la base
    hen: 0.75,   // recargo H.E. nocturna        -> 175% con la base
    rn: 0.35,    // recargo nocturno ordinario
    rdf: 0.90,   // recargo dom/fest ordinario
    heddf: 1.15, // recargo H.E. diurna dom/fest -> 215% con la base
    hendf: 1.65, // recargo H.E. nocturna dom/fest -> 265% con la base
    rndf: 1.25   // recargo nocturno dom/fest    -> 125%
}, Asistencia.config.tasas || {});

/** Horario y topes por defecto. */
Asistencia.horarioDefecto = Object.assign({
    lv: '09:00',
    sab: '04:00',
    tope: '02:00',
    nocturna: '19:00',
    turno: 'REAL'
}, Asistencia.config.horario || {});

Asistencia.divisorDefecto = Asistencia.config.divisor || 210;

Asistencia.util = {
    qs(sel, ctx) {
        return (ctx || document).querySelector(sel);
    },
    qsa(sel, ctx) {
        return Array.from((ctx || document).querySelectorAll(sel));
    },
    escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    },
    /** "HH:MM" -> minutos. Devuelve null si no hay hora válida. */
    strToMins(t) {
        if (!t || String(t).toLowerCase().indexOf('falta') !== -1) return null;
        const p = String(t).split(':');
        const h = parseInt(p[0], 10);
        const m = parseInt(p[1] === undefined ? '0' : p[1], 10);
        if (isNaN(h) || isNaN(m)) return null;
        return h * 60 + m;
    },
    /** minutos -> "HH:MM" (para inputs y para valores de un día) */
    minsToStr(m) {
        if (m === null || m === undefined || isNaN(m) || m < 0) return '';
        const total = Math.round(m);
        const h = Math.floor(total / 60);
        const mm = total % 60;
        return String(h).padStart(2, '0') + ':' + String(mm).padStart(2, '0');
    },
    /** minutos -> "HH:MM" permitiendo pasar de 24h (para totales de mes) */
    minsToStrLargo(m) {
        if (m === null || m === undefined || isNaN(m) || m <= 0) return '00:00';
        const total = Math.round(m);
        return String(Math.floor(total / 60)).padStart(2, '0') + ':' + String(total % 60).padStart(2, '0');
    },
    /** 1234567 -> "$1.234.567" */
    formatoPlata(num) {
        const n = Math.round(num || 0);
        return '$' + n.toLocaleString('es-CO');
    },
    /** Convierte a número de forma segura */
    num(v, def) {
        const n = parseFloat(v);
        return isNaN(n) ? (def === undefined ? 0 : def) : n;
    },
    /** 0.25 -> "25%" */
    pct(v) {
        return (Math.round((v || 0) * 10000) / 100) + '%';
    },
    /** POST simple (x-www-form-urlencoded) que devuelve JSON. */
    async postJSON(url, dataObj) {
        const body = new URLSearchParams();
        Object.entries(dataObj).forEach(([k, v]) => body.append(k, v));
        const resp = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
        });
        return resp.json();
    }
};

/**
 * Memoria local por empleado: así no hay que volver a digitar el salario,
 * las tarifas y los horarios cada mes. Solo se guardan preferencias.
 */
Asistencia.almacen = {
    clave(nombre) {
        return 'inees_asistencia_cfg_' + String(nombre || '').trim().replace(/\s+/g, '_');
    },
    leer(nombre) {
        try {
            const raw = window.localStorage.getItem(Asistencia.almacen.clave(nombre));
            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            return null;
        }
    },
    guardar(nombre, cfg) {
        try {
            window.localStorage.setItem(Asistencia.almacen.clave(nombre), JSON.stringify(cfg));
        } catch (e) {
            /* almacenamiento no disponible: no es crítico */
        }
    }
};
