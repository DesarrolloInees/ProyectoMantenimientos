<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<div class="w-full bg-white shadow-xl rounded-lg p-4 md:p-6">
    
    <div class="bg-gradient-to-r from-blue-800 to-blue-600 p-4 rounded-lg mb-6 flex justify-between items-center text-white shadow-md">
        <h2 class="text-xl font-bold"><i class="fas fa-calendar-day mr-2"></i> Servicios Asignados</h2>
        <div>
            <label class="block text-xs font-bold text-blue-200 uppercase mb-1">Fecha</label>
            <input type="date" id="fechaFiltro" value="<?= date('Y-m-d') ?>" 
                class="text-gray-900 border-none p-2 rounded font-bold focus:ring-2 focus:ring-blue-300">
        </div>
    </div>

    <div class="overflow-hidden">
        <table id="tablaProgramacion" class="display responsive nowrap w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                <tr>
                    <th>Cliente</th>
                    <th>Punto</th>
                    <th>Máquina / Device ID</th>
                    <th>Tipo Mantenimiento</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    var tabla = $('#tablaProgramacion').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        ajax: {
            url: 'index.php?pagina=tecnicoProgramacion&accion=ajaxObtenerProgramacion',
            type: 'POST',
            data: function(d) {
                // SOLO mandamos la fecha. El ID del usuario lo saca PHP por seguridad.
                d.fecha = $('#fechaFiltro').val();
            }
        },
        columns: [
            { data: 'nombre_cliente' },
            { data: 'nombre_punto' },
            { 
                data: null, 
                render: function(data, type, row) {
                    let tipoMaq = row.nombre_tipo_maquina ? row.nombre_tipo_maquina : 'N/A';
                    let device = row.device_id ? row.device_id : 'Sin Device ID';
                    return `<strong>${tipoMaq}</strong><br><span class="text-xs text-gray-400">${device}</span>`;
                }
            },
            { 
                data: 'tipo_mantenimiento',
                render: function(data, type, row) {
                    return data ? `<span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">${data}</span>` : 'N/A';
                }
            },
            { 
                data: null, 
                className: "text-center",
                render: function(data, type, row) {
                    return `<button onclick="abrirReporteMovil(${row.id_ordenes_servicio})" 
                            class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded shadow transition transform hover:scale-105">
                                <i class="fas fa-edit mr-1"></i> Editar
                            </button>`;
                }
            }
        ]
    });

    $('#fechaFiltro').on('change', function() {
        tabla.ajax.reload();
    });
});

function abrirReporteMovil(idOrden) {
    console.log("Abriendo orden para editar:", idOrden);
}
</script>