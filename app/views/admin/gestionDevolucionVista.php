<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">

<style>
    .dataTables_length select,
    .dataTables_filter input {
        background-color: white !important;
        border: 1px solid #d1d5db !important;
        padding: 0.5rem;
        border-radius: 0.5rem;
    }

    .dataTables_wrapper {
        padding: 1rem 0;
    }


    /* Reutilizamos los estilos pro que ya definimos antes */
    .dataTables_length select,
    .dataTables_filter input {
        background-color: white !important;
        color: #374151 !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        margin: 0 0.5rem;
    }

    #tablaRecepcion tbody tr {
        background-color: white !important;
    }

    #tablaRecepcion tbody tr:hover {
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

<div class="w-full px-4 md:px-6 pb-20">
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-8">
        <div class="mb-6 border-b pb-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-box-open text-blue-600 mr-2"></i> Recepción de Repuestos
                </h1>
                <p class="text-gray-500 text-sm">Selecciona los repuestos que el técnico acaba de entregar en sede.</p>
            </div>
        </div>

        <form action="<?= BASE_URL ?>gestionDevolucion" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1">Filtrar por Técnico</label>
                <select name="id_tecnico" class="select2-tecnico w-full border border-gray-300 rounded-lg">
                    <option value="">-- Ver Todos los Técnicos --</option>
                    <?php foreach ($tecnicos as $t): ?>
                        <option value="<?= $t['id_tecnico'] ?>" <?= (isset($filtroTecnico) && $filtroTecnico == $t['id_tecnico']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['nombre_tecnico']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="w-full py-2 px-4 bg-gray-800 text-white font-bold rounded-lg shadow hover:bg-gray-900 transition-all">
                    <i class="fas fa-filter mr-2"></i> Filtrar
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 relative">
        <div class="overflow-x-auto">
            <table id="tablaRecepcion" class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-700 uppercase text-xs">
                    <tr>
                        <th class="py-3 px-4 text-center w-10">
                            <input type="checkbox" id="checkTodos" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded cursor-pointer">
                        </th>
                        <th class="py-3 px-4">Técnico</th>
                        <th class="py-3 px-4">Repuesto / Código</th>
                        <th class="py-3 px-4">Cant.</th>
                        <th class="py-3 px-4">Remisión / Fecha</th>
                        <th class="py-3 px-4">Ubicación</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($datosPendientes)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-8 text-gray-400 font-bold">
                                🎉 ¡Excelente! No hay repuestos pendientes por devolver.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($datosPendientes as $d): ?>
                            <tr class="hover:bg-blue-50 transition-colors fila-recepcion">
                                <td class="py-3 px-4 text-center">
                                    <input type="checkbox" class="check-item w-4 h-4 text-blue-600 border-gray-300 rounded cursor-pointer" 
                                        data-orden="<?= $d['id_orden_servicio'] ?>" 
                                        data-repuesto="<?= $d['id_repuesto'] ?>">
                                </td>
                                <td class="py-3 px-4 font-bold text-gray-800"><?= htmlspecialchars($d['nombre_tecnico']) ?></td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-blue-700"><?= htmlspecialchars($d['nombre_repuesto']) ?></div>
                                    <div class="text-xs text-gray-400 font-mono"><?= htmlspecialchars($d['codigo_referencia'] ?? 'S/R') ?></div>
                                </td>
                                <td class="py-3 px-4 text-center font-black text-lg"><?= $d['cantidad'] ?></td>
                                <td class="py-3 px-4">
                                    <div class="font-bold"><?= htmlspecialchars($d['numero_remision']) ?></div>
                                    <div class="text-xs text-gray-500"><?= date('d/m/Y', strtotime($d['fecha_visita'])) ?></div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="text-xs font-bold text-gray-700"><?= htmlspecialchars($d['nombre_cliente']) ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($d['nombre_punto']) ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!empty($datosPendientes)): ?>
<div class="fixed bottom-6 right-6 z-50">
    <button type="button" id="btnProcesarSeleccion" onclick="procesarDevoluciones()" 
        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-8 rounded-full shadow-2xl transform hover:scale-105 transition-all flex items-center gap-2 opacity-50 cursor-not-allowed" disabled>
        <i class="fas fa-check-double text-xl"></i>
        <span id="contadorSeleccion">Marcar Seleccionados (0)</span>
    </button>
</div>
<?php endif; ?>


<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>

<script>
    $(document).ready(function() {
        // Iniciar Select2
        $('.select2-tecnico').select2();

        // Iniciar DataTable (Sin ordenar la primera columna para que no interfiera con los checkbox)
        if ($('#tablaRecepcion tbody tr').length > 0 && !$('#tablaRecepcion td[colspan]').length) {
            $('#tablaRecepcion').DataTable({
                language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
                columnDefs: [ { orderable: false, targets: 0 } ],
                order: [[1, 'asc']]
            });
        }

        // Lógica de Checkbox Múltiple
        $('#checkTodos').on('click', function() {
            // Selecciona solo los checkbox visibles en la página actual del DataTable
            var isChecked = $(this).prop('checked');
            $('.check-item:visible').prop('checked', isChecked);
            actualizarBoton();
        });

        // Actualizar contador si seleccionan uno por uno
        $(document).on('click', '.check-item', function() {
            actualizarBoton();
        });
    });

    // Función para activar/desactivar el botón flotante
    function actualizarBoton() {
        var seleccionados = $('.check-item:checked').length;
        var btn = $('#btnProcesarSeleccion');
        var texto = $('#contadorSeleccion');

        if (seleccionados > 0) {
            btn.removeClass('opacity-50 cursor-not-allowed').addClass('hover:scale-105');
            btn.prop('disabled', false);
            texto.text('Marcar Seleccionados (' + seleccionados + ')');
        } else {
            btn.addClass('opacity-50 cursor-not-allowed').removeClass('hover:scale-105');
            btn.prop('disabled', true);
            texto.text('Marcar Seleccionados (0)');
            $('#checkTodos').prop('checked', false);
        }
    }

    // Enviar los datos por AJAX
    async function procesarDevoluciones() {
        var repuestosArray = [];
        
        // Recolectar datos
        $('.check-item:checked').each(function() {
            repuestosArray.push({
                id_orden: $(this).data('orden'),
                id_repuesto: $(this).data('repuesto')
            });
        });

        if (repuestosArray.length === 0) return;

        if (!confirm('¿Estás seguro de marcar estos ' + repuestosArray.length + ' repuestos como recibidos en sede?')) {
            return;
        }

        // Bloquear botón temporalmente
        var btn = $('#btnProcesarSeleccion');
        var htmlOriginal = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
        btn.prop('disabled', true);

        try {
            const formData = new FormData();
            formData.append('items', JSON.stringify(repuestosArray));

            const response = await fetch('<?= BASE_URL ?>gestionDevolucion/ajaxProcesarDevolucion', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.status === 'ok') {
                alert(data.msg);
                window.location.reload(); // Recargar para limpiar la tabla
            } else {
                alert(data.msg);
                window.location.reload(); 
            }
        } catch (error) {
            console.error("Error:", error);
            alert("❌ Ocurrió un error al intentar procesar las devoluciones.");
            btn.html(htmlOriginal);
            btn.prop('disabled', false);
        }
    }
</script>