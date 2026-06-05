<?php
// ⚠️ IMPORTANTE: Verifica que esta sea tu variable de sesión real del login
// Si en tu login guardas el rol en $_SESSION['rol'], cámbialo aquí abajo:
$rolActual = isset($_SESSION['nivel_acceso']) ? (int)$_SESSION['nivel_acceso'] : 0;
?>

<div class="w-full bg-white shadow-xl rounded-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">📅 Servicios Agrupados por Día</h2>
            <p class="text-gray-500 text-sm">Resumen financiero y operativo por delegación</p>
        </div>
        <a href="index.php?pagina=ordenCrear" class="bg-indigo-600 hover:bg-indigo-800 text-white font-bold py-2 px-4 rounded shadow transition">
            <i class="fas fa-plus mr-2"></i> Nuevo Reporte
        </a>
    </div>

    <table id="tablaDias" class="min-w-full text-sm display stripe hover" style="width:100%">
        <thead class="bg-gray-800 text-white">
            <tr>
                <th class="py-3 px-4">Fecha</th>
                <th class="py-3 px-4 w-1/3">Desglose por Delegación</th>
                <th class="py-3 px-4 text-center">Total Serv.</th>
                <th class="py-3 px-4 text-right">$$ Total Día</th>
                <th class="py-3 px-4 text-center">Acciones</th>
            </tr>
        </thead>
    </table>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
    // Capturamos el rol que nos manda PHP
    const ROL_USUARIO = <?= $rolActual ?>;
    
    // Esto te ayudará a ti a depurar en F12 -> Consola
    console.log("El rol detectado por el sistema es: ", ROL_USUARIO);

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
                        return `<div class="font-bold text-lg text-gray-700 text-center border p-2 rounded bg-gray-50">${data}</div>`;
                    }
                },
                {
                    "data": "detalles_delegacion",
                    "render": function(data) {
                        let html = '<ul class="text-xs text-left space-y-1">';
                        
                        data.forEach(function(det) {
                            let htmlPrecio = "";
                            
                            // Lógica de visualización: Solo muestra el precio si NO ES ROL 5
                            if (ROL_USUARIO !== 5) {
                                let precioFmt = new Intl.NumberFormat('es-CO', {
                                    style: 'currency', currency: 'COP', maximumFractionDigits: 0
                                }).format(det.valor);
                                
                                htmlPrecio = `<span class='text-green-600 font-medium ml-1'>${precioFmt}</span>`;
                            }

                            html += `
                            <li class='flex justify-between border-b border-gray-100 pb-1 items-center'>
                                <span class='font-bold text-gray-600 w-1/3 truncate' title='${det.nombre}'>${det.nombre}:</span>
                                <span class='flex items-center space-x-1'>
                                    <span class='bg-orange-100 text-orange-800 px-1.5 py-0.5 rounded border border-orange-200' title='Técnicos'>
                                        <i class='fas fa-user-wrench text-[10px] mr-1'></i>${det.num_tecnicos}
                                    </span>
                                    <span class='bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200' title='Servicios'>
                                        ${det.cant} serv.
                                    </span> 
                                    ${htmlPrecio}
                                </span>
                            </li>`;
                        });
                        
                        html += '</ul>';
                        return html;
                    }
                },
                {
                    "data": "cantidad_total",
                    "className": "text-center align-middle",
                    "render": function(data) {
                        return `<span class="bg-blue-100 text-blue-800 text-base py-1 px-3 rounded-full font-bold">${data}</span>`;
                    }
                },
                {
                    "data": "valor_total_dia",
                    "className": "text-right align-middle text-base",
                    "render": function(data) {
                        // Si es el Supervisor Motorizado (Rol 5), le mostramos un candado en vez del precio
                        if (ROL_USUARIO === 5) {
                            return `<span class="text-gray-400 text-sm font-semibold bg-gray-100 px-2 py-1 rounded"><i class="fas fa-lock mr-1"></i>No Disponible</span>`;
                        }
                        
                        // Para todos los demás roles, mostramos el dinero
                        let valorFormateado = new Intl.NumberFormat('es-CO', {
                            style: 'currency', currency: 'COP', maximumFractionDigits: 0
                        }).format(data);
                        
                        return `<span class="font-bold text-green-700">${valorFormateado}</span>`;
                    }
                },
                {
                    "data": null,
                    "className": "text-center align-middle",
                    "render": function(data, type, row) {
                        return `
                        <a href="<?= BASE_URL ?>ordenDetalle/${row.fecha_visita}" 
                            class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-2 px-6 rounded shadow transition hover:scale-105">
                            <i class="fas fa-eye mr-2"></i> Ver Detalle
                        </a>`;
                    }
                }
            ],
            "order": [[0, "desc"]],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
            }
        });
    });
</script>