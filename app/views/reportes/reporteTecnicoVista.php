<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

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
</style>

<div class="w-full max-w-7xl mx-auto">
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-8">
        <div class="mb-4 border-b pb-2 flex justify-between items-center flex-wrap gap-2">
            <div>
                <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-chart-line text-blue-600 mr-2"></i> Reporte de Servicios</h1>
                <p class="text-gray-500 text-sm">Consulta la productividad y servicios realizados por técnico.</p>
            </div>
            <?php if (!empty($datosReporte)): ?>
                <button type="button" onclick="exportarExcelTecnico()" class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700 shadow flex items-center gap-2 transform hover:scale-105 transition">
                    <i class="fas fa-file-excel"></i> Exportar Excel
                </button>
            <?php endif; ?>
        </div>

        <form action="<?= BASE_URL ?>reporteTecnico" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div class="md:col-span-1">
                <label class="block text-sm font-bold text-gray-700 mb-1">Técnico</label>
                <select name="id_tecnico" id="select_tecnico" class="select2-search w-full border border-gray-300 rounded-lg">
                    <option value="" <?= ($filtros['id_tecnico'] == '') ? 'selected' : '' ?>>-- VER TODOS LOS SERVICIOS --</option>
                    <?php foreach ($listaTecnicos as $t): ?>
                        <option value="<?= $t['id_tecnico'] ?>" <?= ($filtros['id_tecnico'] == $t['id_tecnico']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['nombre_tecnico']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Desde</label>
                <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?= $filtros['fecha_inicio'] ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Hasta</label>
                <input type="date" name="fecha_fin" id="fecha_fin" value="<?= $filtros['fecha_fin'] ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <button type="submit" class="w-full py-2 px-4 bg-blue-600 text-white font-bold rounded-lg shadow hover:bg-blue-700 transition-all">
                    <i class="fas fa-search mr-2"></i> Generar
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($datosReporte)): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                <p class="text-sm text-blue-600 font-bold uppercase">Total Servicios</p>
                <p class="text-2xl font-bold text-gray-800"><?= count($datosReporte) ?></p>
            </div>
            <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                <p class="text-sm text-green-600 font-bold uppercase">Valor Total (Ingresos)</p>
                <p class="text-2xl font-bold text-gray-800">$<?= number_format($totalValor, 2) ?></p>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <p class="text-sm text-gray-500 font-bold uppercase">Rango Consultado</p>
                <p class="text-sm font-medium text-gray-800 mt-1"><?= date('d/m/Y', strtotime($filtros['fecha_inicio'])) ?> - <?= date('d/m/Y', strtotime($filtros['fecha_fin'])) ?></p>
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
                                <td class="py-3 px-4 whitespace-nowrap"><?= date('d/m/Y', strtotime($fila['fecha_visita'])) ?></td>
                                <td class="py-3 px-4 font-mono font-bold text-blue-600"><?= htmlspecialchars($fila['numero_remision'] ?? 'N/A') ?></td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-gray-800"><?= htmlspecialchars($fila['nombre_cliente']) ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($fila['nombre_punto']) ?></div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="text-xs font-mono bg-gray-100 px-2 py-1 rounded inline-block"><?= htmlspecialchars($fila['device_id']) ?></div>
                                </td>
                                <td class="py-3 px-4 text-gray-600 italic"><?= substr(htmlspecialchars($fila['actividades_realizadas']), 0, 50) ?>...</td>
                                <td class="py-3 px-4 text-right font-bold text-gray-700">$<?= number_format($fila['valor_servicio'], 2) ?></td>
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
    // Recibimos los datos COMPLETOS de PHP
    const datosServicios = <?= json_encode($datosExcel ?? []) ?>;

    $(document).ready(function() {
        $('.select2-search').select2({
            width: '100%',
            placeholder: '-- Todos los Técnicos --'
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

    // =========================================================
    // FUNCIÓN: EXCEL CON HOJA DE RESUMEN Y SIN CÁLCULO DE HORAS
    // =========================================================
    function exportarExcelTecnico() {
        if (typeof XLSX === 'undefined') {
            alert("Error: Librería SheetJS no cargada.");
            return;
        }
        if (datosServicios.length === 0) {
            alert("No hay datos para exportar.");
            return;
        }

        let workbook = XLSX.utils.book_new();

        // 1. AGRUPAR DATOS: Técnico -> Fecha -> Objeto con Datos
        let resumen = {};

        datosServicios.forEach(item => {
            let tecnico = item.nombre_tecnico || "Sin Nombre";
            let fecha = item.fecha_visita.split(' ')[0]; // Solo fecha YYYY-MM-DD

            // Datos de hora (asumiendo formato HH:MM:SS en BD)
            let horaEnt = item.hora_entrada;
            let horaSal = item.hora_salida;

            if (!resumen[tecnico]) resumen[tecnico] = {};

            // Inicializar fecha si no existe
            if (!resumen[tecnico][fecha]) {
                resumen[tecnico][fecha] = {
                    cantidad: 0,
                    primera_entrada: "23:59:59",
                    ultima_salida: "00:00:00"
                };
            }

            // Aumentar contador
            resumen[tecnico][fecha].cantidad++;

            // Calcular Primera Entrada (Mínima)
            if (horaEnt && horaEnt < resumen[tecnico][fecha].primera_entrada) {
                resumen[tecnico][fecha].primera_entrada = horaEnt;
            }

            // Calcular Última Salida (Máxima)
            if (horaSal && horaSal > resumen[tecnico][fecha].ultima_salida) {
                resumen[tecnico][fecha].ultima_salida = horaSal;
            }
        });

        // ---------------------------------------------------------
        // NUEVO: CREAR HOJA 1 - RESUMEN GENERAL (TABLA DE TÉCNICOS)
        // ---------------------------------------------------------
        let matrizResumenGeneral = [
            ['NOMBRE DEL TÉCNICO', 'TOTAL SERVICIOS REALIZADOS']
        ];
        let hayDatos = false;

        // Recorremos el objeto resumen para llenar la primera hoja
        for (const [nombreTecnico, fechasObj] of Object.entries(resumen)) {
            hayDatos = true;
            // Sumar todas las cantidades de todas las fechas de este técnico
            let totalTecnico = Object.values(fechasObj).reduce((a, b) => a + b.cantidad, 0);
            matrizResumenGeneral.push([nombreTecnico, totalTecnico]);
        }

        // Crear y agregar la hoja de Resumen al principio
        let wsResumen = XLSX.utils.aoa_to_sheet(matrizResumenGeneral);
        wsResumen['!cols'] = [{
            wch: 40
        }, {
            wch: 25
        }]; // Ajustar ancho de columnas
        XLSX.utils.book_append_sheet(workbook, wsResumen, "RESUMEN GENERAL");


        // ---------------------------------------------------------
        // CREAR HOJAS INDIVIDUALES POR TÉCNICO
        // ---------------------------------------------------------
        for (const [nombreTecnico, fechasObj] of Object.entries(resumen)) {
            let fechasOrdenadas = Object.keys(fechasObj).sort();
            let totalServiciosTecnico = Object.values(fechasObj).reduce((a, b) => a + b.cantidad, 0);

            // === CONSTRUIR MATRIZ EXCEL ===
            let matriz = [];

            // Encabezados (Eliminada la columna "TIEMPO TRABAJO")
            matriz.push([
                'FECHA',
                'PRIMERA ENTRADA',
                'ÚLTIMA SALIDA',
                'CANTIDAD',
                'CALIDAD',
                'OTROS',
                'KISAN',
                'CRP',
                'TOTAL SERVICIOS'
            ]);

            // Título Técnico
            matriz.push([nombreTecnico.toUpperCase(), '', '', '', '', '', '', '', totalServiciosTecnico]);

            fechasOrdenadas.forEach(fecha => {
                let dataDia = fechasObj[fecha];

                let hIn = (dataDia.primera_entrada === "23:59:59") ? "--" : dataDia.primera_entrada;
                let hOut = (dataDia.ultima_salida === "00:00:00") ? "--" : dataDia.ultima_salida;

                // NOTA: Se eliminó el cálculo de diferencia de horas aquí

                matriz.push([
                    fecha,
                    hIn, // Primera Entrada
                    hOut, // Última Salida
                    dataDia.cantidad, // Cantidad
                    '', '', '', '', // Columnas vacías para llenado manual
                    dataDia.cantidad // Total repetido
                ]);
            });

            let ws = XLSX.utils.aoa_to_sheet(matriz);

            // Ajustar anchos (Se eliminó una columna, ajustamos índices)
            ws['!cols'] = [{
                    wch: 12
                }, // Fecha
                {
                    wch: 18
                }, // Primera Entrada
                {
                    wch: 18
                }, // Última Salida
                {
                    wch: 10
                }, // Cantidad
                {
                    wch: 10
                }, {
                    wch: 10
                }, {
                    wch: 10
                }, {
                    wch: 10
                }, // Vacíos
                {
                    wch: 20
                } // Total
            ];

            // Combinar celdas del título (ajustado a 8 columnas)
            ws['!merges'] = [{
                s: {
                    r: 1,
                    c: 0
                },
                e: {
                    r: 1,
                    c: 7
                }
            }];

            let nombreHoja = nombreTecnico.replace(/[\\/?*\[\]]/g, "").substring(0, 30);
            XLSX.utils.book_append_sheet(workbook, ws, nombreHoja);
        }

        if (hayDatos) {
            let fechaHoy = new Date().toISOString().slice(0, 10).replace(/-/g, "");
            XLSX.writeFile(workbook, `Reporte_Servicios_${fechaHoy}.xlsx`);
        }
    }
</script>