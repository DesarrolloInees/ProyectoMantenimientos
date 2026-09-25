<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

// 🔒 SEGURIDAD: Capturamos el nivel de acceso actual
$rolActual = isset($_SESSION['nivel_acceso']) ? (int) $_SESSION['nivel_acceso'] : 0;
?>

<script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">

<style>
    /* Estilos Select2 y DataTables (Igual que antes) */
    .select2-container .select2-selection--single {
        height: 42px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.5rem !important;
        display: flex;
        align-items: center;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        padding-left: 0.75rem;
        color: #374151;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
    }

    .dataTables_length select,
    .dataTables_filter input {
        background-color: white !important;
        color: #374151 !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        margin: 0 0.5rem;
    }

    #reporteTable tbody tr {
        background-color: white !important;
    }

    #reporteTable tbody tr:hover {
        background-color: #f9fafb !important;
    }

    .dataTables_paginate .paginate_button.current {
        background-color: #4f46e5 !important;
        color: white !important;
        border-color: #4f46e5 !important;
    }

    .dataTables_wrapper>div:first-child,
    .dataTables_wrapper>div:last-of-type {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        margin: 1.5rem 0;
    }

    /* 🔒 MAGIA CSS PARA OCULTAR LA COLUMNA DE PRECIOS SI ES ROL 5 */
    <?php if ($rolActual === 5): ?>
        /* La columna "Valor" es la 6ta en la tabla */
        #reporteTable th:nth-child(6),
        #reporteTable td:nth-child(6) {
            display: none !important;
        }

    <?php endif; ?>
</style>

<div class="w-full px-4 md:px-6">
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-8">
        <div class="mb-4 border-b pb-2 flex justify-between items-center flex-wrap gap-2">
            <div>
                <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-chart-line text-blue-600 mr-2"></i>
                    Reporte de Servicios</h1>
                <p class="text-gray-500 text-sm">Consulta la productividad y servicios realizados por técnico.</p>
            </div>
            <?php if (!empty($datosReporte)): ?>
                <button type="button" onclick="exportarExcelTecnico()"
                    class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700 shadow flex items-center gap-2 transform hover:scale-105 transition">
                    <i class="fas fa-file-excel"></i> Exportar Excel
                </button>
            <?php endif; ?>
        </div>

        <form action="<?= BASE_URL ?>reporteTecnico" method="POST"
            class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div class="md:col-span-1">
                <label class="block text-sm font-bold text-gray-700 mb-1">Técnico</label>
                <select name="id_tecnico" id="select_tecnico"
                    class="select2-search w-full border border-gray-300 rounded-lg">
                    <option value="" <?= ($filtros['id_tecnico'] == '') ? 'selected' : '' ?>></option>
                    <?php foreach ($listaTecnicos as $t): ?>
                        <option value="<?= $t['id_tecnico'] ?>" <?= ($filtros['id_tecnico'] == $t['id_tecnico']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['nombre_tecnico']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Desde</label>
                <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?= $filtros['fecha_inicio'] ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Hasta</label>
                <input type="date" name="fecha_fin" id="fecha_fin" value="<?= $filtros['fecha_fin'] ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <button type="submit"
                    class="w-full py-2 px-4 bg-blue-600 text-white font-bold rounded-lg shadow hover:bg-blue-700 transition-all">
                    <i class="fas fa-search mr-2"></i> Generar
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($datosReporte)): ?>
        <?php $resumenTipos = isset($resumenTipos) ? $resumenTipos : ['basico' => 0, 'profundo' => 0, 'correctivo' => 0, 'fallido' => 0, 'otros' => 0, 'total' => count($datosReporte)]; ?>
        <div class="grid grid-cols-1 md:grid-cols-<?= ($rolActual === 5) ? '2' : '3' ?> gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                <p class="text-sm text-blue-600 font-bold uppercase">Total Servicios</p>
                <p class="text-2xl font-bold text-gray-800"><?= count($datosReporte) ?></p>
            </div>

            <?php if ($rolActual !== 5): ?>
                <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                    <p class="text-sm text-green-600 font-bold uppercase">Valor Total (Ingresos)</p>
                    <p class="text-2xl font-bold text-gray-800">$<?= number_format($totalValor, 2) ?></p>
                </div>
            <?php endif; ?>

            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <p class="text-sm text-gray-500 font-bold uppercase">Rango Consultado</p>
                <p class="text-sm font-medium text-gray-800 mt-1"><?= date('d/m/Y', strtotime($filtros['fecha_inicio'])) ?>
                    - <?= date('d/m/Y', strtotime($filtros['fecha_fin'])) ?></p>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">
                <i class="fas fa-wrench text-indigo-500 mr-1"></i> Servicios por tipo de mantenimiento
            </p>
            <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                <div class="bg-sky-50 p-3 rounded-lg border border-sky-100 text-center">
                    <p class="text-[11px] text-sky-600 font-bold uppercase">Preventivo Básico</p>
                    <p class="text-xl font-extrabold text-gray-800"><?= (int)($resumenTipos['basico'] ?? 0) ?></p>
                </div>
                <div class="bg-indigo-50 p-3 rounded-lg border border-indigo-100 text-center">
                    <p class="text-[11px] text-indigo-600 font-bold uppercase">Preventivo Profundo</p>
                    <p class="text-xl font-extrabold text-gray-800"><?= (int)($resumenTipos['profundo'] ?? 0) ?></p>
                </div>
                <div class="bg-amber-50 p-3 rounded-lg border border-amber-100 text-center">
                    <p class="text-[11px] text-amber-600 font-bold uppercase">Correctivo</p>
                    <p class="text-xl font-extrabold text-gray-800"><?= (int)($resumenTipos['correctivo'] ?? 0) ?></p>
                </div>
                <div class="bg-red-50 p-3 rounded-lg border border-red-100 text-center">
                    <p class="text-[11px] text-red-600 font-bold uppercase">Fallido</p>
                    <p class="text-xl font-extrabold text-gray-800"><?= (int)($resumenTipos['fallido'] ?? 0) ?></p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 text-center">
                    <p class="text-[11px] text-gray-500 font-bold uppercase" title="Garantía, Kisan, instalaciones y otros estados">Otros</p>
                    <p class="text-xl font-extrabold text-gray-800"><?= (int)($resumenTipos['otros'] ?? 0) ?></p>
                </div>
                <div class="bg-blue-600 p-3 rounded-lg border border-blue-600 text-center">
                    <p class="text-[11px] text-blue-100 font-bold uppercase">Total</p>
                    <p class="text-xl font-extrabold text-white"><?= (int)($resumenTipos['total'] ?? count($datosReporte)) ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
            <div class="overflow-x-auto">
                <table id="reporteTable" class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                        <tr>
                            <th class="py-3 px-4">Fecha</th>
                            <th class="py-3 px-4">Remisión</th>
                            <th class="py-3 px-4">Cliente / Punto</th>
                            <th class="py-3 px-4">Equipo</th>
                            <th class="py-3 px-4">Actividad</th>
                            <th class="py-3 px-4 text-right">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($datosReporte as $fila): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-4 whitespace-nowrap"><?= date('d/m/Y', strtotime($fila['fecha_visita'])) ?>
                                </td>
                                <td class="py-3 px-4 font-mono font-bold text-blue-600">
                                    <?= htmlspecialchars($fila['numero_remision'] ?? 'N/A') ?>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-gray-800"><?= htmlspecialchars($fila['nombre_cliente']) ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($fila['nombre_punto']) ?></div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="text-xs font-mono bg-gray-100 px-2 py-1 rounded inline-block">
                                        <?= htmlspecialchars($fila['device_id']) ?>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-gray-600 italic">
                                    <?= substr(htmlspecialchars($fila['actividades_realizadas']), 0, 50) ?>...
                                </td>
                                <td class="py-3 px-4 text-right font-bold text-gray-700">
                                    $<?= number_format($fila['valor_servicio'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>
<script>
    // Recibimos los datos COMPLETOS de PHP (Si es rol 5, ya vienen con valor_servicio = 0)
    const datosServicios = <?= json_encode($datosExcel ?? []) ?>;
    // Copia tal cual de ordenReporte (todos los técnicos del rango, filtro 'todos')
    const datosServiciosGlobal = <?= json_encode($datosServiciosGlobal ?? []) ?>;

    $(document).ready(function () {
        $('.select2-search').select2({
            width: '100%',
            placeholder: '-- Todos los Técnicos --',
            allowClear: true
        });
        if ($('#reporteTable').length) {
            $('#reporteTable').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                order: [
                    [0, "desc"]
                ]
            });
        }
    });

    function calcularHorasExtra(horaEntrada, horaSalida, fechaStr) {
        let horas = { hed: 0, hen: 0, hedf: 0 };

        // Validamos que existan horas válidas
        if (!horaEntrada || !horaSalida || horaEntrada === "null" || horaSalida === "null" || horaEntrada === "" || horaSalida === "") {
            return horas;
        }

        // Convertimos horas a minutos totales para calcular fácil
        const [hE, mE] = horaEntrada.split(':').map(Number);
        const [hS, mS] = horaSalida.split(':').map(Number);
        let inicio = hE * 60 + mE;
        let fin = hS * 60 + mS;

        // 1. Identificamos si es un día "especial" (Domingo o Festivo de tu tabla)
        let fecha = new Date(fechaStr + 'T00:00:00');
        let esEspecial = (fecha.getDay() === 0) || festivosDB.includes(fechaStr);

        if (esEspecial) {
            // REGLA CRACK: En festivos/domingos, TODO el tiempo trabajado va a H.E.D.F
            // No importa si fue por la mañana o por la tarde.
            let duracionMinutos = fin - inicio;
            if (duracionMinutos > 0) {
                horas.hedf = duracionMinutos / 60;
            }
        } else {
            // REGLA DÍAS NORMALES: Solo cuenta después de las 17:00
            const limite17 = 17 * 60; // 5:00 PM
            const limite19 = 19 * 60; // 7:00 PM

            if (fin > limite17) {
                // Extra Diurna (17:00 a 19:00)
                let comienzoD = Math.max(inicio, limite17);
                let finalD = Math.min(fin, limite19);
                if (finalD > comienzoD) {
                    horas.hed = (finalD - comienzoD) / 60;
                }

                // Extra Nocturna (19:00 en adelante)
                if (fin > limite19) {
                    let comienzoN = Math.max(inicio, limite19);
                    let finalN = fin;
                    if (finalN > comienzoN) {
                        horas.hen = (finalN - comienzoN) / 60;
                    }
                }
            }
        }

        return horas;
    }

    // Recibimos los festivos parametrizados desde la base de datos
    const festivosDB = <?= json_encode($listaFestivos ?? []) ?>;

    // Función para verificar si es festivo o domingo
    function esDiaNoLaboral(fechaStr) {
        let fecha = new Date(fechaStr + 'T00:00:00');
        let esDomingo = fecha.getDay() === 0;
        let esFestivo = festivosDB.includes(fechaStr);
        return esDomingo || esFestivo;
    }


    function calcularMetaPeriodo(fechaInicio, fechaFin) {
        let meta = 0;
        let fechaActual = new Date(fechaInicio + 'T00:00:00');
        let fechaFinal = new Date(fechaFin + 'T00:00:00');

        while (fechaActual <= fechaFinal) {
            let stringFecha = fechaActual.toISOString().split('T')[0];
            let diaSemana = fechaActual.getDay(); // 0: Dom, 1: Lun... 6: Sab

            // Si NO es festivo y NO es domingo
            if (!festivosDB.includes(stringFecha) && diaSemana !== 0) {
                if (diaSemana >= 1 && diaSemana <= 5) {
                    meta += 6; // Lunes a Viernes
                } else if (diaSemana === 6) {
                    meta += 3; // Sábados
                }
            }

            fechaActual.setDate(fechaActual.getDate() + 1);
        }
        return meta;
    }

    // =========================================================
    function calcularDiferenciaHorasCopia(horaInicio, horaFin) {
        if (!horaInicio || !horaFin) return "00:00";
        let d1 = new Date(`2000-01-01T${horaInicio}`);
        let d2 = new Date(`2000-01-01T${horaFin}`);
        if (isNaN(d1.getTime()) || isNaN(d2.getTime())) return "00:00";
        let diffMs = d2 - d1;
        if (diffMs < 0) return "Err";
        let diffMins = Math.floor(diffMs / 60000);
        let horas = Math.floor(diffMins / 60);
        let mins = diffMins % 60;
        return `${String(horas).padStart(2, '0')}:${String(mins).padStart(2, '0')}`;
    }

    function aplicarFormatoServiciosCopia(ws) {
        if (!ws['!ref']) return;
        const fmtMoneda = '_-"$"* #,##0_-;-"$"* #,##0_-;-"$"* "-"??_-;-_-@_-';
        const range = XLSX.utils.decode_range(ws['!ref']);
        for (let R = range.s.r + 1; R <= range.e.r; ++R) {
            let cDev = XLSX.utils.encode_cell({ c: 0, r: R });
            if (ws[cDev]) {
                let limpio = String(ws[cDev].v || '').replace(/\D/g, '');
                ws[cDev].t = 'n';
                ws[cDev].v = limpio !== '' ? Number(limpio) : 0;
                ws[cDev].z = '000000000000';
            }
            let cTar = XLSX.utils.encode_cell({ c: 7, r: R });
            if (ws[cTar] && ws[cTar].f) { ws[cTar].z = fmtMoneda; continue; }
            if (!ws[cTar]) ws[cTar] = { t: 'n', v: 0 };
            ws[cTar].t = 'n';
            ws[cTar].v = parseFloat(ws[cTar].v) || 0;
            ws[cTar].z = fmtMoneda;
        }
    }

    function agregarCopiaReporteServicios(workbook, datos) {
        if (!datos || datos.length === 0) return;
        let porDel = {};
        let viaticoPendiente = null;
        let prevTec = null, prevFecha = null, prevSalida = null;
        const HEAD = ["Device_id", "Número de Remisión", "Cliente", "Nombre Punto", "Preventivo Básico", "Preventivo Profundo", "Correctivo", "Tarifa", "Observaciones", "Delegación", "Fecha", "Técnico", "Tipo de Máquina", "Tipo de Servicio", "Hora Entrada", "Hora Salida", "Duración", "Desplazamiento", "Repuestos", "Estado", "Calificación", "Modalidad"];
        const ANCHOS = [{ wch: 15 }, { wch: 12 }, { wch: 25 }, { wch: 25 }, { wch: 8 }, { wch: 8 }, { wch: 8 }, { wch: 15 }, { wch: 50 }, { wch: 15 }, { wch: 12 }, { wch: 20 }, { wch: 15 }, { wch: 20 }, { wch: 10 }, { wch: 10 }, { wch: 10 }, { wch: 12 }, { wch: 30 }, { wch: 15 }, { wch: 15 }, { wch: 15 }];
        datos.forEach((d, index) => {
            let del = d.delegacion || "SIN ASIGNAR";
            if (!porDel[del]) porDel[del] = [];
            let desp = "";
            if (d.nombre_tecnico === prevTec && d.fecha_visita === prevFecha && prevSalida) {
                desp = calcularDiferenciaHorasCopia(prevSalida, d.hora_entrada);
            }
            prevTec = d.nombre_tecnico; prevFecha = d.fecha_visita; prevSalida = d.hora_salida;
            let txt = (d.txt_servicio || "").toLowerCase();
            let pb = (txt.includes("basico") || txt.includes("básico")) ? "X" : "";
            let pp = (txt.includes("profundo") || txt.includes("completo")) ? "X" : "";
            let co = (txt.includes("correctivo") || txt.includes("reparacion")) ? "X" : "";
            if (!pb && !pp && !co && txt.includes("preventivo")) pb = "X";
            let dur = d.tiempo_servicio;
            if (!dur || dur === '00:00' || dur === '00:00:00') dur = calcularDiferenciaHorasCopia(d.hora_entrada, d.hora_salida);
            let vServ = parseFloat(d.valor_servicio) || 0;
            let vViat = parseFloat(d.valor_viaticos) || 0;
            porDel[del].push([d.device_id, d.numero_remision, d.nombre_cliente, d.nombre_punto, pb, pp, co, vServ, d.que_se_hizo, del, d.fecha_visita, d.nombre_tecnico, d.nombre_tipo_maquina, d.txt_servicio, d.hora_entrada, d.hora_salida, dur, desp, d.repuestos_texto, d.estado_maquina, d.nombre_calificacion, d.tipo_zona]);
            if (vViat > 0) {
                viaticoPendiente = ["", "", "", "", "", "", "", vViat, "TARIFA ADICIONAL POR DÍA", "", "", "", "", "", "", "", "", "", "", "", "", ""];
            }
            let sig = datos[index + 1];
            let cerrar = false;
            if (!sig) cerrar = true;
            else {
                let sigDel = sig.delegacion || "SIN ASIGNAR";
                if (sig.fecha_visita !== d.fecha_visita || sig.nombre_tecnico !== d.nombre_tecnico || sigDel !== del) cerrar = true;
            }
            if (cerrar && viaticoPendiente) { porDel[del].push(viaticoPendiente); viaticoPendiente = null; }
        });
        for (let del in porDel) {
            let matriz = [HEAD.slice()];
            porDel[del].forEach(f => matriz.push(f));
            let ws = XLSX.utils.aoa_to_sheet(matriz);
            aplicarFormatoServiciosCopia(ws);
            ws["!cols"] = ANCHOS.slice();
            let nombreHoja = String(del).replace(/[:\\/?*\[\]]/g, "").substring(0, 30) || "Data";
            XLSX.utils.book_append_sheet(workbook, ws, nombreHoja);
        }
        let mRes = [HEAD.slice()];
        let filaActual = 2;
        for (let del in porDel) {
            porDel[del].forEach(f => { mRes.push(f); filaActual++; });
        }
        let filaTotal = ["", "", "", "", "", "", "TOTAL GENERAL:"];
        filaTotal.push({ t: 'n', f: 'SUM(H2:H' + (filaActual - 1) + ')' });
        for (let i = 8; i < 22; i++) filaTotal.push("");
        mRes.push(filaTotal);
        let wsRes = XLSX.utils.aoa_to_sheet(mRes);
        aplicarFormatoServiciosCopia(wsRes);
        wsRes["!cols"] = ANCHOS.slice();
        XLSX.utils.book_append_sheet(workbook, wsRes, "RESUMEN TOTAL");
    }

    // FUNCIÓN: EXCEL CON DESGLOSE POR TIPO DE MANTENIMIENTO Y FALLIDOS
    // =========================================================
    function exportarExcelTecnico() {
        try {
            if (typeof XLSX === 'undefined') {
                alert("Error: Librería SheetJS no cargada.");
                return;
            }
            if (!datosServicios || datosServicios.length === 0) {
                alert("No hay datos para exportar.");
                return;
            }

            let workbook = XLSX.utils.book_new();

            // ---------------------------------------------------------
            // PASO 1: DETECTAR TODOS LOS TIPOS DE MANTENIMIENTO ÚNICOS
            // ---------------------------------------------------------
            let tiposMantenimientoSet = new Set();

            datosServicios.forEach(item => {
                let tipo = item.tipo_mantenimiento || "SIN ESPECIFICAR";
                tiposMantenimientoSet.add(tipo);
            });
            let columnasTipos = Array.from(tiposMantenimientoSet).sort();

            // ---------------------------------------------------------
            // PASO 2: AGRUPAR DATOS CON PONDERACIÓN Y HORAS EXTRA
            // ---------------------------------------------------------
            let resumen = {};

            datosServicios.forEach(item => {
                let tecnico = item.nombre_tecnico ? item.nombre_tecnico.toString().trim() : "Sin Nombre";
                let fecha = item.fecha_visita ? item.fecha_visita.split(' ')[0] : "Sin Fecha";
                let horaEnt = item.hora_entrada;
                let horaSal = item.hora_salida;
                let tipoMant = item.tipo_mantenimiento || "SIN ESPECIFICAR";

                if (!resumen[tecnico]) {
                    resumen[tecnico] = {
                        fechas: {},
                        contadoresTipos: {},
                        totalHED: 0,
                        totalHEN: 0,
                        totalHEDF: 0
                    };
                    columnasTipos.forEach(t => resumen[tecnico].contadoresTipos[t] = 0);
                }

                // 1. Calcular Horas Extra del servicio
                let extras = calcularHorasExtra(horaEnt, horaSal, fecha);
                resumen[tecnico].totalHED += extras.hed;
                resumen[tecnico].totalHEN += extras.hen;
                resumen[tecnico].totalHEDF += extras.hedf;

                // 2. Crear el objeto del día si no existe
                if (!resumen[tecnico].fechas[fecha]) {
                    resumen[tecnico].fechas[fecha] = {
                        cantidad: 0,
                        primera_entrada: "23:59:59",
                        ultima_salida: "00:00:00"
                    };
                }

                // 3. LOGICA RESTAURADA: Identificar la primera entrada y última salida reales
                if (horaEnt && horaEnt !== "null" && horaEnt !== "") {
                    if (horaEnt < resumen[tecnico].fechas[fecha].primera_entrada) {
                        resumen[tecnico].fechas[fecha].primera_entrada = horaEnt;
                    }
                }

                if (horaSal && horaSal !== "null" && horaSal !== "") {
                    if (horaSal > resumen[tecnico].fechas[fecha].ultima_salida) {
                        resumen[tecnico].fechas[fecha].ultima_salida = horaSal;
                    }
                }

                // 4. Sumar contadores (con multiplicador)
                let pesoServicio = 1;
                let tipoUpper = tipoMant.toUpperCase();

                // 1. Primero filtramos todo lo que sea "FALLIDA" o "FALLIDO" para asegurar que valga 1
                if (tipoUpper.includes('FALLID')) {
                    pesoServicio = 1;
                }
                // 2. Instalación MÁS Capacitación (Peso 3)
                else if (tipoUpper.includes('MÁS CAPACITACIÓN') || tipoUpper.includes('MAS CAPACITACION')) {
                    pesoServicio = 3;
                }
                // 3. Instalación SIN Capacitación (Peso 2)
                else if (tipoUpper.includes('SIN CAPACITACIÓN') || tipoUpper.includes('SIN CAPACITACION')) {
                    pesoServicio = 2;
                }
                // 4. Si el estado es exactamente la palabra "INSTALACIÓN" sola (Peso 3)
                else if (tipoUpper === 'INSTALACIÓN' || tipoUpper === 'INSTALACION') {
                    pesoServicio = 2;
                }

                resumen[tecnico].fechas[fecha].cantidad += pesoServicio;
                resumen[tecnico].contadoresTipos[tipoMant] += pesoServicio;
            });

            // ---------------------------------------------------------
            // PASO 2.5: ORDENAR TÉCNICOS SEGÚN LISTA PERSONALIZADA
            // ---------------------------------------------------------
            const ordenPersonalizado = [
                "MURGAS", "JHONY", "MAICOL", "RUIZ", "ORJUELA", "FORERO",
                "ESPINOSA", "MAURICIO", "JHONATAN", "ORTIZ", "CERVERA",
                "VILORIA", "SAAVEDRA", "BENAVIDES"
            ];

            let tecnicosOrdenados = Object.keys(resumen);

            tecnicosOrdenados.sort((a, b) => {
                let nombreA = a.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toUpperCase();
                let nombreB = b.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toUpperCase();

                let indexA = ordenPersonalizado.findIndex(clave => nombreA.includes(clave.toUpperCase()));
                let indexB = ordenPersonalizado.findIndex(clave => nombreB.includes(clave.toUpperCase()));

                if (indexA === -1) indexA = 999;
                if (indexB === -1) indexB = 999;

                return indexA - indexB;
            });

            // ---------------------------------------------------------
            // PASO 3: CREAR MATRIZ RESUMEN GENERAL (HOJA 1)
            // ---------------------------------------------------------
            let encabezadosResumen = ['NOMBRE DEL TÉCNICO', 'TOTAL SERVICIOS'];
            columnasTipos.forEach(tipo => encabezadosResumen.push(tipo.toUpperCase()));

            let matrizResumenGeneral = [encabezadosResumen];
            let hayDatos = false;

            tecnicosOrdenados.forEach(nombreTecnico => {
                let datos = resumen[nombreTecnico];
                hayDatos = true;
                let totalTecnico = Object.values(datos.contadoresTipos).reduce((a, b) => a + b, 0);
                let fila = [nombreTecnico, totalTecnico];

                columnasTipos.forEach(tipo => {
                    fila.push(datos.contadoresTipos[tipo]);
                });
                matrizResumenGeneral.push(fila);
            });

            // ---------------------------------------------------------
            // PASO 3.5: AGREGAR LA TABLA DE FALLIDOS A LA MISMA HOJA
            // ---------------------------------------------------------
            let fallidosData = {};
            let granTotalFallidos = 0;

            datosServicios.forEach(item => {
                let tipo = item.tipo_mantenimiento || "";
                let esFallido = tipo.toUpperCase().includes('FALLIDO');

                if (esFallido) {
                    let delegacion = item.delegacion ? item.delegacion.toUpperCase() : "SIN DELEGACIÓN";
                    let cliente = item.nombre_cliente || "Sin Cliente";

                    if (!fallidosData[delegacion]) fallidosData[delegacion] = {};
                    if (!fallidosData[delegacion][cliente]) fallidosData[delegacion][cliente] = 0;

                    fallidosData[delegacion][cliente]++;
                    granTotalFallidos++;
                }
            });

            if (granTotalFallidos > 0) {
                matrizResumenGeneral.push(['']);
                matrizResumenGeneral.push(['']);
                matrizResumenGeneral.push(['Cuenta de Cliente', 'Etiquetas de columna']);
                matrizResumenGeneral.push(['Etiquetas de fila', 'Fallido']);

                for (const [delegacion, clientes] of Object.entries(fallidosData)) {
                    let totalDelegacion = 0;
                    for (const count of Object.values(clientes)) totalDelegacion += count;

                    matrizResumenGeneral.push(['- ' + delegacion, totalDelegacion]);

                    for (const [cliente, count] of Object.entries(clientes)) {
                        matrizResumenGeneral.push(['    ' + cliente, count]);
                    }
                }
            }

            let wsResumen = XLSX.utils.aoa_to_sheet(matrizResumenGeneral);
            let wscols = [{ wch: 40 }, { wch: 20 }];
            columnasTipos.forEach(() => wscols.push({ wch: 20 }));
            wsResumen['!cols'] = wscols;

            XLSX.utils.book_append_sheet(workbook, wsResumen, "RESUMEN GENERAL");

            // =========================================================
            // PASO 3.6: CREAR NUEVA HOJA "TABLA DE LIQUIDACIÓN" (SEGÚN IMÁGEN)
            // =========================================================
            const fechaDesde = document.getElementById('fecha_inicio').value;
            const fechaHasta = document.getElementById('fecha_fin').value;
            const metaRequerida = calcularMetaPeriodo(fechaDesde, fechaHasta);

            let matrizLiquidacion = [];

            matrizLiquidacion.push([
                'NOMBRE DEL TÉCNICO', 'SERVICIOS * MES', 'PP*1.5', 'MC*1.5',
                'F+PB', 'KS + OTROS', 'TOTAL', 'TIME', 'H.E.D', 'H.E.N', 'H.E.D.F'
            ]);

            let totalServiciosTodos = 0;
            let tecnicosConServicios = 0;

            tecnicosOrdenados.forEach(nombreTecnico => {
                let datos = resumen[nombreTecnico];
                let contadores = datos.contadoresTipos;

                let serviciosMes = 0;
                let pp_15 = 0;
                let mc_15 = 0;
                let f_pb = 0;
                let ks_otros = 0;

                for (const [tipo, cantidad] of Object.entries(contadores)) {
                    let tipoUp = tipo.toUpperCase();
                    serviciosMes += cantidad;

                    if (tipoUp.includes('PROFUNDO')) {
                        pp_15 += (cantidad * 1.5);
                    } else if (tipoUp.includes('CORRECTIVO')) {
                        mc_15 += (cantidad * 1.5);
                    } else if (tipoUp.includes('FALLIDO') || tipoUp.includes('BASICO') || tipoUp.includes('BÁSICO')) {
                        f_pb += cantidad;
                    } else {
                        ks_otros += cantidad;
                    }
                }

                let totalSuma = pp_15 + mc_15 + f_pb + ks_otros;
                let diasTrabajados = Object.keys(datos.fechas).length;

                if (serviciosMes > 0) {
                    totalServiciosTodos += serviciosMes;
                    tecnicosConServicios++;
                }

                // Agregamos la fila del técnico (TODO NUMÉRICO para permitir autosuma en Excel)
                matrizLiquidacion.push([
                    nombreTecnico.toUpperCase(),
                    serviciosMes,
                    Math.round(pp_15 * 10) / 10,
                    Math.round(mc_15 * 10) / 10,
                    f_pb,
                    ks_otros,
                    Math.round(totalSuma * 10) / 10,
                    diasTrabajados,
                    Math.round(datos.totalHED * 10) / 10,
                    Math.round(datos.totalHEN * 10) / 10,
                    Math.round(datos.totalHEDF * 10) / 10
                ]);
            });

            const fechaObj = new Date(fechaDesde + 'T00:00:00');
            const nombreMes = fechaObj.toLocaleString('es-ES', { month: 'long' }).toUpperCase();

            matrizLiquidacion.push(['']);
            matrizLiquidacion.push([`PROMEDIO DE SERVICIOS PARA ${nombreMes} ${metaRequerida}`]);

            let wsLiquidacion = XLSX.utils.aoa_to_sheet(matrizLiquidacion);

            // Forzar tipo numérico en columnas B..K (autosuma en Excel)
            if (wsLiquidacion['!ref']) {
                const rgLiq = XLSX.utils.decode_range(wsLiquidacion['!ref']);
                for (let R = rgLiq.s.r + 1; R <= rgLiq.e.r; ++R) {
                    // Evitar la fila vacía y la de PROMEDIO (solo tienen 1 celda)
                    let c0 = XLSX.utils.encode_cell({ c: 0, r: R });
                    if (!wsLiquidacion[c0] || String(wsLiquidacion[c0].v || '').includes('PROMEDIO')) continue;
                    for (let C = 1; C <= 10; C++) {
                        let ref = XLSX.utils.encode_cell({ c: C, r: R });
                        if (!wsLiquidacion[ref]) continue;
                        if (wsLiquidacion[ref].f) continue;
                        wsLiquidacion[ref].t = 'n';
                        wsLiquidacion[ref].v = parseFloat(wsLiquidacion[ref].v) || 0;
                    }
                }
            }

            wsLiquidacion['!cols'] = [
                { wch: 40 }, { wch: 18 }, { wch: 12 }, { wch: 12 },
                { wch: 12 }, { wch: 15 }, { wch: 12 }, { wch: 12 },
                { wch: 10 }, { wch: 10 }, { wch: 10 }
            ];

            // Combinamos las celdas para la barra verde del "PROMEDIO"
            let indexPromedio = matrizLiquidacion.length - 2;
            wsLiquidacion['!merges'] = [
                { s: { r: indexPromedio, c: 0 }, e: { r: indexPromedio, c: 10 } }
            ];

            XLSX.utils.book_append_sheet(workbook, wsLiquidacion, "REPORTE DETALLADO DE SERVICIOS");

            // =========================================================
            // PASO 3.7: COPIA TAL CUAL DEL REPORTE DE SERVICIOS (ordenReporte)
            // Hojas por delegación + RESUMEN TOTAL, con las mismas reglas:
            // desplazamiento, clasificación X, viáticos, formatos y SUM().
            // Se inserta DESPUÉS del detallado y ANTES de las hojas por técnico.
            // =========================================================
            agregarCopiaReporteServicios(workbook, datosServiciosGlobal);

            // ---------------------------------------------------------
            // PASO 4: CREAR HOJAS INDIVIDUALES POR TÉCNICO
            // ---------------------------------------------------------
            tecnicosOrdenados.forEach(nombreTecnico => {
                let datos = resumen[nombreTecnico];
                let fechasObj = datos.fechas;
                let fechasOrdenadas = Object.keys(fechasObj).sort();
                let totalServiciosTecnico = Object.values(fechasObj).reduce((a, b) => a + b.cantidad, 0);

                let matriz = [];
                matriz.push(['FECHA', 'PRIMERA ENTRADA', 'ÚLTIMA SALIDA', 'CANTIDAD', 'CALIDAD', 'OTROS', 'KISAN', 'CRP', 'TOTAL SERVICIOS']);
                matriz.push([nombreTecnico.toUpperCase(), '', '', '', '', '', '', '', totalServiciosTecnico]);

                fechasOrdenadas.forEach(fecha => {
                    let dataDia = fechasObj[fecha];
                    let hIn = (dataDia.primera_entrada === "23:59:59") ? "--" : dataDia.primera_entrada;
                    let hOut = (dataDia.ultima_salida === "00:00:00") ? "--" : dataDia.ultima_salida;

                    matriz.push([
                        fecha, hIn, hOut, dataDia.cantidad, '', '', '', '', dataDia.cantidad
                    ]);
                });

                let ws = XLSX.utils.aoa_to_sheet(matriz);
                ws['!cols'] = [
                    { wch: 12 }, { wch: 18 }, { wch: 18 }, { wch: 10 },
                    { wch: 10 }, { wch: 10 }, { wch: 10 }, { wch: 10 }, { wch: 20 }
                ];
                ws['!merges'] = [{ s: { r: 1, c: 0 }, e: { r: 1, c: 7 } }];

                let nombreHoja = nombreTecnico.replace(/[\\/?*\[\]]/g, "").substring(0, 30);
                if (!nombreHoja) nombreHoja = "Técnico";
                XLSX.utils.book_append_sheet(workbook, ws, nombreHoja);
            });

            if (hayDatos) {
                let fechaHoy = new Date().toISOString().slice(0, 10).replace(/-/g, "");
                XLSX.writeFile(workbook, `Reporte_Técnico_Detallado_${fechaHoy}.xlsx`);
            } else {
                alert("No se generaron datos para el reporte.");
            }

        } catch (error) {
            console.error("Error al generar Excel:", error);
            alert("Ocurrió un error al generar el Excel: " + error.message + "\n\nRevisa la consola (F12) para más detalles.");
        }
    }
</script>