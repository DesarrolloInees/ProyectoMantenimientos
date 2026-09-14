<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

$nombreUsuario = $usuario['nombre'] ?? 'Usuario';
$rolUsuario = $usuario['rol_nombre'] ?? 'Técnico / Supervisor';
$fechaHoy = date('d \d\e F, Y');
$horaActual = date('H:i');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario Técnico</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Contenedor Principal -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        <!-- Encabezado -->
        <div class="rounded-2xl bg-gradient-to-r from-indigo-700 via-blue-600 to-indigo-800 p-6 shadow-xl text-white">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex items-center gap-4">
                    <div class="bg-white/20 p-3.5 rounded-xl backdrop-blur-sm border border-white/20">
                        <i class="fa-solid fa-gears text-3xl text-indigo-100"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold tracking-tight">Inventario Técnico</h1>
                        <p class="text-indigo-100 text-sm mt-0.5">Control y gestión de repuestos y stock asignado</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="<?= BASE_URL ?>inicio" class="bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-xl text-sm font-medium transition backdrop-blur-sm border border-white/10 flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i> Volver al Panel
                    </a>
                </div>
            </div>
        </div>

        <!-- Tabla de Inventario -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h3 class="font-bold text-gray-800 text-lg">Listado de Elementos</h3>
                    <p class="text-xs text-gray-500">Total de registros encontrados: <?= count($inventario) ?></p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/70 text-gray-400 text-xs uppercase tracking-wider border-b border-gray-100">
                            <th class="py-4 px-6 font-semibold">ID / Código</th>
                            <th class="py-4 px-6 font-semibold">Descripción / Repuesto</th>
                            <th class="py-4 px-6 font-semibold">Cantidad / Stock</th>
                            <th class="py-4 px-6 font-semibold">Detalles / Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-600">
                        <?php if (!empty($inventario)): ?>
                            <?php foreach ($inventario as $item): ?>
                                <tr class="hover:bg-indigo-50/30 transition">
                                    <td class="py-4 px-6 font-mono font-medium text-gray-800">
                                        <?= htmlspecialchars($item['id'] ?? $item['id_inventario'] ?? 'N/A') ?>
                                    </td>
                                    <td class="py-4 px-6 font-medium text-gray-900">
                                        <?= htmlspecialchars($item['nombre'] ?? $item['descripcion'] ?? $item['repuesto'] ?? 'Sin descripción') ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700">
                                            <?= htmlspecialchars($item['cantidad'] ?? $item['stock'] ?? '0') ?> unidades
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-gray-500 text-xs">
                                        <?= htmlspecialchars($item['observaciones'] ?? $item['estado'] ?? 'Sin observaciones') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-12 text-gray-400">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <i class="fa-solid fa-box-open text-4xl text-gray-300"></i>
                                        <p class="text-sm font-medium">No hay elementos registrados en el inventario técnico.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>