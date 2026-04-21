<div class="w-full bg-white shadow-xl rounded-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">📄 Generación de PDFs por Servicio</h2>
            <p class="text-gray-500 text-sm">Listado individual de órdenes para exportación a PDF</p>
        </div>
    </div>

    <table id="tablaPdf" class="min-w-full text-sm display stripe hover" style="width:100%">
        <thead class="bg-gray-800 text-white">
            <tr>
                <th class="py-3 px-4 text-center">ID</th>
                <th class="py-3 px-4 text-center">Remisión</th>
                <th class="py-3 px-4 text-center">Fecha Visita</th>
                <th class="py-3 px-4">Cliente / Punto</th>
                <th class="py-3 px-4">Técnico</th>
                <th class="py-3 px-4 text-center">Acciones</th>
            </tr>
        </thead>
    </table>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<script>
    $(document).ready(function() {
        $('#tablaPdf').DataTable({
            responsive: true, // <--- AGREGAR ESTA LÍNEA
            "ajax": {
                // Apunta al controlador nuevo
                "url": "index.php?pagina=serviciosPdf&accion=ajaxListar",
                "type": "POST"
            },
            "columns": [{
                    "data": "id_ordenes_servicio",
                    "className": "text-center font-bold text-gray-600"
                },
                {
                    "data": "numero_remision",
                    "className": "text-center font-bold text-indigo-600"
                },
                {
                    "data": "fecha_visita",
                    "className": "text-center"
                },
                {
                    "data": null,
                    "render": function(data, type, row) {
                        return `<div class="font-bold text-gray-800">${row.nombre_cliente}</div>
                                <div class="text-xs text-gray-500">${row.nombre_punto}</div>`;
                    }
                },
                {
                    "data": "nombre_tecnico"
                },
                {
                    "data": null,
                    "className": "text-center align-middle",
                    // Dentro de tu columns render de DataTables:
                    render: function(data, type, row) {
                        const urlPdf = `index.php?pagina=pdfServicio&accion=generar&id=${row.id_ordenes_servicio}`;
                        const urlEditar = `index.php?pagina=servicioEditar&accion=index&id=${row.id_ordenes_servicio}`;

                        return `
                            <div class="flex flex-col gap-2">
                                <a href="${urlPdf}" target="_blank" class="bg-red-600 hover:bg-red-800 text-white font-bold py-1 px-3 rounded shadow text-xs text-center inline-block">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </a>
                                <a href="${urlEditar}" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-1 px-3 rounded shadow text-xs text-center inline-block">
                                    <i class="fas fa-edit"></i> Completar
                                </a>
                            </div>
                        `;
                    }
                }
            ],
            "order": [
                [2, "desc"], // Ordenar por fecha descendente
                [0, "desc"] // Luego por ID descendente
            ],
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
            }
        });
    });
</script>