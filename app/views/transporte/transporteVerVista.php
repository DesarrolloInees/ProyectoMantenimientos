<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* Estilos personalizados para que el Datatable encaje con Tailwind */
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        padding: 0.4rem 0.8rem;
        outline: none;
        margin-left: 0.5rem;
    }

    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
    }

    .dataTables_wrapper .dataTables_length select {
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        padding: 0.2rem 1rem 0.2rem 0.5rem;
    }

    table.dataTable.no-footer {
        border-bottom: 1px solid #e5e7eb;
    }

    table.dataTable thead th {
        background-color: #f9fafb;
        color: #374151;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        border-bottom: 2px solid #e5e7eb;
        padding: 1rem;
    }

    table.dataTable tbody td {
        vertical-align: middle;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f3f4f6;
        font-size: 0.875rem;
        color: #4b5563;
    }

    table.dataTable tbody tr:hover {
        background-color: #f8fafc !important;
    }
</style>

<div class="w-full max-w-7xl mx-auto px-2 py-4 md:py-6">

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-tools text-indigo-500 mr-2"></i> Instalaciones y Operaciones
            </h1>
            <p class="text-sm text-gray-500 mt-1">Gestiona todas las instalaciones, desinstalaciones y traslados.</p>
        </div>
        <div>
            <a href="index.php?pagina=transporteCrear" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-200 flex items-center gap-2">
                <i class="fas fa-plus"></i> Nuevo Registro
            </a>
        </div>
    </div>

    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
        <table id="tablaInstalaciones" class="display responsive nowrap w-full" style="width:100%">
            <thead>
                <tr>
                    <th class="text-center w-10">ID</th>
                    <th>Fecha Solicitud</th>
                    <th class="text-center">Operación</th>
                    <th>Técnico</th>
                    <th>Máquina (Serial / Tipo)</th>
                    <th>Destino (Cliente / Punto)</th>
                    <th class="text-center w-24">Acciones</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>

</div>

<script>
    const BASE_URL_APP = '<?= BASE_URL ?? "" ?>';
</script>

<script>
    let tabla;

    $(document).ready(function() {
        // Inicializar DataTable
        tabla = $('#tablaInstalaciones').DataTable({
            responsive: true,
            ajax: {
                url: 'index.php?pagina=transporteVer&accion=ajaxListar',
                type: 'GET'
            },
            order: [
                [0, 'desc']
            ], // Ordenar por ID descendente por defecto
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' // Traducción al español
            },
            columnDefs: [{
                    className: "text-center",
                    targets: [0, 2, 6]
                },
                {
                    orderable: false,
                    targets: [6]
                } // Deshabilitar orden en la columna de botones
            ]
        });
    });



    // Función para ver detalle (Muestra una alerta rápida, puedes hacer un modal después)
    // Función para ver detalle (Abre el PDF en nueva pestaña)
    function verDetalle(id) {
        // Reemplaza 'instalacionPdf' por el nombre que le diste a esta página en tu index o enrutador principal
        var urlPdf = 'index.php?pagina=transportePdf&id=' + id;
        window.open(urlPdf, '_blank');
    }

    // Función para eliminar con SweetAlert2
    function eliminarRegistro(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción anulará el registro #" + id + " y no se mostrará en esta lista.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Petición AJAX para eliminar
                $.ajax({
                    url: 'index.php?pagina=transporteVer&accion=eliminar',
                    type: 'POST',
                    data: {
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                title: '¡Eliminado!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonColor: '#10b981'
                            });
                            // Recargar el Datatable sin refrescar la página
                            tabla.ajax.reload(null, false);
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Hubo un problema de conexión con el servidor.', 'error');
                    }
                });
            }
        });
    }
</script>