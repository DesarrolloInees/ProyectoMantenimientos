<div class="w-full bg-white shadow-xl rounded-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">📅 Servicios Agrupados por Día</h2>
            <p class="text-gray-500 text-sm">Resumen financiero y operativo</p>
        </div>
        <a href="index.php?pagina=ordenCrear" class="bg-indigo-600 hover:bg-indigo-800 text-white font-bold py-2 px-4 rounded shadow transition">
            <i class="fas fa-plus mr-2"></i> Nuevo Reporte
        </a>
    </div>
    
    <table id="tablaDias" class="min-w-full text-sm display stripe hover" style="width:100%">
        <thead class="bg-gray-800 text-white">
            <tr>
                <th class="py-3 px-4">Fecha del Servicio</th>
                <th class="py-3 px-4 text-center">Cant. Servicios</th>
                <th class="py-3 px-4 text-right">$$ Total del Día</th>
                <th class="py-3 px-4 text-center">Acciones</th>
            </tr>
        </thead>
    </table>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
    $(document).ready(function() {
        $('#tablaDias').DataTable({
            "ajax": {
                "url": "index.php?pagina=ordenVer&accion=ajaxListar",
                "type": "POST"
            },
            "columns": [
                { 
                    "data": "fecha_visita",
                    "render": function(data) {
                        return `<span class="font-bold text-lg text-gray-700">${data}</span>`;
                    }
                },
                { 
                    "data": "cantidad_servicios",
                    "className": "text-center",
                    "render": function(data) { 
                        return `<span class="bg-blue-100 text-blue-800 py-1 px-3 rounded-full font-bold">${data}</span>`; 
                    }
                },
                { 
                    "data": "valor_total",
                    "className": "text-right font-bold text-green-700",
                    "render": function(data) { 
                        return new Intl.NumberFormat('es-CO', {style:'currency', currency:'COP'}).format(data); 
                    }
                },
                {
                    "data": null,
                    "className": "text-center",
                    "render": function(data, type, row) {
                        // Botón "Ver / Editar" que lleva al detalle
                        return `
                        <a href="index.php?pagina=ordenDetalle&fecha=${row.fecha_visita}" 
                            class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-2 px-6 rounded shadow transition hover:scale-105">
                            <i class="fas fa-eye mr-2"></i> Ver Detalle
                        </a>`;
                    }
                }
            ],
            "order": [[0, "desc"]], // Ordenar por fecha, lo más reciente primero
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
            }
        });
    });
</script>