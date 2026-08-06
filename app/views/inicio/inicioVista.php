<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

$nivel = $_SESSION['nivel_acceso'] ?? 0;
$nombreUsuario = $usuario['nombre'] ?? 'colaborador';
$rolUsuario = $usuario['rol_nombre'] ?? 'Usuario';
$fechaHoy = date('d \d\e F, Y');
$horaActual = date('H:i');
?>

<?php if ($nivel == 3): ?>
    <!-- Panel Técnico (Vista Principal) -->
    <div class="min-h-[80vh] flex flex-col items-center justify-center px-4 py-8">
        <div class="w-full max-w-lg rounded-2xl shadow-xl overflow-hidden bg-white border border-gray-100">
            
            <!-- Encabezado -->
            <div class="bg-gradient-to-br from-indigo-600 via-blue-600 to-indigo-700 p-6 text-center text-white relative">
                <div class="inline-block bg-white/20 p-3.5 rounded-full mb-3 backdrop-blur-sm border border-white/20 shadow-inner">
                    <i class="fas fa-user-astronaut text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold tracking-tight">¡Hola, <?= htmlspecialchars($nombreUsuario) ?>!</h1>
                <p class="text-indigo-100 text-sm font-medium mt-0.5"><?= htmlspecialchars($rolUsuario) ?></p>
                <div class="mt-3 inline-flex items-center gap-1.5 px-3 py-1 bg-black/20 backdrop-blur-sm rounded-full text-xs text-indigo-100">
                    <i class="far fa-clock"></i>
                    <span><?= $fechaHoy ?> · <?= $horaActual ?></span>
                </div>
            </div>

            <!-- Menú de Opciones -->
            <div class="p-6 space-y-3">
                <p class="text-center text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">¿Qué necesitas hacer hoy?</p>
                
                <!-- Programación Servicios -->
                <a href="<?= BASE_URL ?>tecnicoProgramacion" 
                   class="group flex items-center gap-4 bg-gray-50/80 hover:bg-indigo-50/50 p-3.5 rounded-xl border border-gray-200 hover:border-indigo-400 transition-all hover:shadow-md">
                    <div class="bg-indigo-100 p-3 rounded-lg group-hover:bg-indigo-600 transition shrink-0">
                        <i class="fa-solid fa-envelope text-indigo-600 group-hover:text-white text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-800 text-sm group-hover:text-indigo-700 transition">Programación Servicios</h3>
                        <p class="text-xs text-gray-500">Ver y gestionar servicios agendados</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-300 group-hover:text-indigo-600 group-hover:translate-x-0.5 transition"></i>
                </a>

                <!-- Consultar Historial -->
                <a href="<?= BASE_URL ?>ordenMovil" 
                   class="group flex items-center gap-4 bg-gray-50/80 hover:bg-indigo-50/50 p-3.5 rounded-xl border border-gray-200 hover:border-indigo-400 transition-all hover:shadow-md">
                    <div class="bg-indigo-100 p-3 rounded-lg group-hover:bg-indigo-600 transition shrink-0">
                        <i class="fas fa-search text-indigo-600 group-hover:text-white text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-800 text-sm group-hover:text-indigo-700 transition">Consultar Historial</h3>
                        <p class="text-xs text-gray-500">Buscar por cliente, punto o máquina</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-300 group-hover:text-indigo-600 group-hover:translate-x-0.5 transition"></i>
                </a>

                <!-- Historial de Parqueaderos -->
                <a href="<?= BASE_URL ?>parqueaderoHistorial" 
                   class="group flex items-center gap-4 bg-gray-50/80 hover:bg-indigo-50/50 p-3.5 rounded-xl border border-gray-200 hover:border-indigo-400 transition-all hover:shadow-md">
                    <div class="bg-indigo-100 p-3 rounded-lg group-hover:bg-indigo-600 transition shrink-0">
                        <i class="fa-solid fa-square-parking text-indigo-600 group-hover:text-white text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-800 text-sm group-hover:text-indigo-700 transition">Historial de Parqueaderos</h3>
                        <p class="text-xs text-gray-500">Consultar registros y recibos de parqueo</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-300 group-hover:text-indigo-600 group-hover:translate-x-0.5 transition"></i>
                </a>

                <!-- Historial de Horas Extra -->
                <a href="<?= BASE_URL ?>horaExtraHistorial" 
                   class="group flex items-center gap-4 bg-gray-50/80 hover:bg-indigo-50/50 p-3.5 rounded-xl border border-gray-200 hover:border-indigo-400 transition-all hover:shadow-md">
                    <div class="bg-indigo-100 p-3 rounded-lg group-hover:bg-indigo-600 transition shrink-0">
                        <i class="fa-solid fa-clock text-indigo-600 group-hover:text-white text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-800 text-sm group-hover:text-indigo-700 transition">Historial Horas Extra</h3>
                        <p class="text-xs text-gray-500">Revisar reporte y registros de asistencia</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-300 group-hover:text-indigo-600 group-hover:translate-x-0.5 transition"></i>
                </a>

                <!-- Logout -->
                <div class="pt-4 text-center">
                    <a href="<?= BASE_URL ?>logout" class="text-xs font-medium text-gray-400 hover:text-red-500 inline-flex items-center gap-1.5 transition">
                        <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                    </a>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($nivel == 4): ?>
    <!-- Panel Funcionario Prosegur (claro) -->
    <div class="min-h-[80vh] flex flex-col items-center justify-center px-4 py-8">
        <div class="w-full max-w-md rounded-2xl shadow-2xl overflow-hidden bg-white">
            <div class="bg-gradient-to-br from-emerald-600 to-teal-500 p-6 text-center text-white">
                <div class="inline-block bg-white/20 p-3 rounded-full mb-3">
                    <i class="fa-solid fa-person-military-pointing fa-3x"></i>
                </div>
                <h1 class="text-2xl font-bold">Bienvenido, <?= htmlspecialchars($nombreUsuario) ?></h1>
                <p class="text-emerald-100 text-sm"><?= htmlspecialchars($rolUsuario) ?></p>
                <p class="text-emerald-100 text-xs mt-1"><?= $fechaHoy ?> · <?= $horaActual ?></p>
            </div>
            <div class="p-6 space-y-4">
                <a href="<?= BASE_URL ?>serviciosPdf" 
                    class="group flex items-center gap-4 bg-white p-4 rounded-xl border border-emerald-100 hover:border-emerald-500 transition-all">
                    <div class="bg-emerald-50 p-3 rounded-full group-hover:bg-emerald-600">
                        <i class="fas fa-file-pdf text-emerald-600 group-hover:text-white text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-800">Consultar Historial PDF</h3>
                        <p class="text-xs text-gray-500">Descarga de reportes</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </a>
                <div class="pt-4 text-center">
                    <a href="<?= BASE_URL ?>logout" class="text-sm text-gray-400 hover:text-red-500 inline-flex items-center gap-1">
                        <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                    </a>
                </div>
            </div>
        </div>
    </div>

    Aquí tienes la reorganización del panel para Supervisor de Motorizados.

Se agrupó la información por secciones temáticas (Gestión Operativa, Repuestos e Inventario, y Reportes & Exportaciones) y se corrigió el grid a 3 columnas adaptativas (lg:grid-cols-3), evitando filas descompensadas. También se incluyó la opción de Buscar Servicio (ordenDetalleBuscar) y se eliminaron todos los caracteres invisibles \u00a0.

PHP
<?php elseif ($nivel == 5): ?>
    <!-- Panel Supervisor Motorizados -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">

        <!-- Encabezado de bienvenida -->
        <div class="rounded-2xl bg-gradient-to-r from-amber-700 via-orange-600 to-amber-600 p-6 shadow-xl text-white">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <i class="fa-solid fa-truck-fast text-3xl text-amber-200"></i>
                        <h1 class="text-3xl font-bold tracking-tight">Panel Supervisor Motorizados</h1>
                    </div>
                    <p class="text-orange-100 text-sm">Gestión de rutas, técnicos y operatividad móvil</p>
                    <div class="mt-3 flex items-center gap-2 text-xs bg-black/20 backdrop-blur-sm px-3 py-1.5 rounded-full w-fit">
                        <i class="fas fa-user-circle"></i>
                        <span><?= htmlspecialchars($nombreUsuario) ?> (<?= htmlspecialchars($rolUsuario) ?>)</span>
                    </div>
                </div>
                <div class="text-left md:text-right bg-white/10 backdrop-blur-sm p-3 rounded-xl border border-white/10">
                    <p class="text-2xl font-mono font-bold tracking-wider"><?= $horaActual ?></p>
                    <p class="text-xs text-orange-100 opacity-90"><?= $fechaHoy ?></p>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 1: Gestión Operativa -->
        <div>
            <div class="flex items-center gap-2 mb-4">
                <i class="fas fa-tasks text-amber-600 text-lg"></i>
                <h2 class="text-lg font-bold text-gray-800 uppercase tracking-wide">Gestión Operativa & Servicios</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                
                <!-- Revisar Servicios -->
                <a href="<?= BASE_URL ?>ordenVer" class="group bg-white rounded-xl shadow-sm hover:shadow-md p-5 transition-all hover:-translate-y-1 border border-gray-200 hover:border-amber-400 flex items-start gap-4">
                    <div class="bg-amber-100 p-3.5 rounded-xl group-hover:bg-amber-600 transition shrink-0">
                        <i class="fa-solid fa-clipboard-list text-xl text-amber-700 group-hover:text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-base group-hover:text-amber-600 transition">Revisar Servicios</h3>
                        <p class="text-xs text-gray-500 mt-1">Monitorear órdenes y estado de servicios asignados.</p>
                    </div>
                </a>

                <!-- Buscar Servicio -->
                <a href="<?= BASE_URL ?>ordenDetalleBuscar" class="group bg-white rounded-xl shadow-sm hover:shadow-md p-5 transition-all hover:-translate-y-1 border border-gray-200 hover:border-amber-400 flex items-start gap-4">
                    <div class="bg-amber-100 p-3.5 rounded-xl group-hover:bg-amber-600 transition shrink-0">
                        <i class="fa-solid fa-magnifying-glass text-xl text-amber-700 group-hover:text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-base group-hover:text-amber-600 transition">Buscar Servicio</h3>
                        <p class="text-xs text-gray-500 mt-1">Búsqueda rápida y detallada de órdenes específicas.</p>
                    </div>
                </a>

                <!-- Administrar Remisiones -->
                <a href="<?= BASE_URL ?>controlRemisionVer" class="group bg-white rounded-xl shadow-sm hover:shadow-md p-5 transition-all hover:-translate-y-1 border border-gray-200 hover:border-amber-400 flex items-start gap-4">
                    <div class="bg-amber-100 p-3.5 rounded-xl group-hover:bg-amber-600 transition shrink-0">
                        <i class="fa-solid fa-ticket text-xl text-amber-700 group-hover:text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-base group-hover:text-amber-600 transition">Administrar Remisiones</h3>
                        <p class="text-xs text-gray-500 mt-1">Control y asignación de remisiones operativas.</p>
                    </div>
                </a>

            </div>
        </div>

        <!-- SECCIÓN 2: Repuestos e Inventarios -->
        <div>
            <div class="flex items-center gap-2 mb-4">
                <i class="fas fa-boxes-stacked text-orange-600 text-lg"></i>
                <h2 class="text-lg font-bold text-gray-800 uppercase tracking-wide">Repuestos e Inventario</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

                <!-- Ver Repuestos -->
                <a href="<?= BASE_URL ?>repuestoVer" class="group bg-white rounded-xl shadow-sm hover:shadow-md p-5 transition-all hover:-translate-y-1 border border-gray-200 hover:border-orange-400 flex items-start gap-4">
                    <div class="bg-orange-100 p-3.5 rounded-xl group-hover:bg-orange-600 transition shrink-0">
                        <i class="fa-solid fa-puzzle-piece text-xl text-orange-700 group-hover:text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-base group-hover:text-orange-600 transition">Ver Repuestos</h3>
                        <p class="text-xs text-gray-500 mt-1">Consulta del catálogo y stock general de repuestos.</p>
                    </div>
                </a>

                <!-- Administrar Repuestos -->
                <a href="<?= BASE_URL ?>inventarioTecnicoVer" class="group bg-white rounded-xl shadow-sm hover:shadow-md p-5 transition-all hover:-translate-y-1 border border-gray-200 hover:border-orange-400 flex items-start gap-4">
                    <div class="bg-orange-100 p-3.5 rounded-xl group-hover:bg-orange-600 transition shrink-0">
                        <i class="fa-solid fa-gears text-xl text-orange-700 group-hover:text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-base group-hover:text-orange-600 transition">Inventario Técnicos</h3>
                        <p class="text-xs text-gray-500 mt-1">Gestión de repuestos asignados a la flota motorizada.</p>
                    </div>
                </a>

            </div>
        </div>

        <!-- SECCIÓN 3: Reportes y Exportaciones -->
        <div>
            <div class="flex items-center gap-2 mb-4">
                <i class="fas fa-chart-pie text-emerald-600 text-lg"></i>
                <h2 class="text-lg font-bold text-gray-800 uppercase tracking-wide">Reportes & Descargas Excel</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

                <!-- Reporte de Técnicos -->
                <a href="<?= BASE_URL ?>reporteTecnico" class="group bg-white rounded-xl shadow-sm hover:shadow-md p-5 transition-all hover:-translate-y-1 border border-gray-200 hover:border-emerald-400 flex items-start gap-4">
                    <div class="bg-emerald-100 p-3.5 rounded-xl group-hover:bg-emerald-600 transition shrink-0">
                        <i class="fa-solid fa-chart-line text-xl text-emerald-700 group-hover:text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-base group-hover:text-emerald-600 transition">Reporte de Técnicos</h3>
                        <p class="text-xs text-gray-500 mt-1">Métricas de rendimiento y productividad de la flota.</p>
                    </div>
                </a>

                <!-- Reporte Ejecutivo -->
                <a href="<?= BASE_URL ?>reporteEjecutivo" class="group bg-white rounded-xl shadow-sm hover:shadow-md p-5 transition-all hover:-translate-y-1 border border-gray-200 hover:border-emerald-400 flex items-start gap-4">
                    <div class="bg-emerald-100 p-3.5 rounded-xl group-hover:bg-emerald-600 transition shrink-0">
                        <i class="fa-solid fa-chart-pie text-xl text-emerald-700 group-hover:text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-base group-hover:text-emerald-600 transition">Reporte Ejecutivo</h3>
                        <p class="text-xs text-gray-500 mt-1">Resumen general y consolidado de operaciones.</p>
                    </div>
                </a>

                <!-- Excel Programación -->
                <a href="<?= BASE_URL ?>exportarExcel" class="group bg-white rounded-xl shadow-sm hover:shadow-md p-5 transition-all hover:-translate-y-1 border border-gray-200 hover:border-emerald-400 flex items-start gap-4">
                    <div class="bg-emerald-100 p-3.5 rounded-xl group-hover:bg-emerald-600 transition shrink-0">
                        <i class="fa-solid fa-file-excel text-xl text-emerald-700 group-hover:text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-base group-hover:text-emerald-600 transition">Excel Programación</h3>
                        <p class="text-xs text-gray-500 mt-1">Descargar programación de rutas motorizadas.</p>
                    </div>
                </a>

                <!-- Excel Servicios -->
                <a href="<?= BASE_URL ?>ordenReporte" class="group bg-white rounded-xl shadow-sm hover:shadow-md p-5 transition-all hover:-translate-y-1 border border-gray-200 hover:border-emerald-400 flex items-start gap-4">
                    <div class="bg-emerald-100 p-3.5 rounded-xl group-hover:bg-emerald-600 transition shrink-0">
                        <i class="fa-solid fa-file-csv text-xl text-emerald-700 group-hover:text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-base group-hover:text-emerald-600 transition">Excel Servicios</h3>
                        <p class="text-xs text-gray-500 mt-1">Reporte detallado por tipo de mantenimiento.</p>
                    </div>
                </a>

                <!-- Reporte Horas Extra -->
                <a href="<?= BASE_URL ?>asistencia" class="group bg-white rounded-xl shadow-sm hover:shadow-md p-5 transition-all hover:-translate-y-1 border border-gray-200 hover:border-emerald-400 flex items-start gap-4">
                    <div class="bg-emerald-100 p-3.5 rounded-xl group-hover:bg-emerald-600 transition shrink-0">
                        <i class="fa-solid fa-clock text-xl text-emerald-700 group-hover:text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-base group-hover:text-emerald-600 transition">Reporte Horas Extra</h3>
                        <p class="text-xs text-gray-500 mt-1">Descargar planilla y registros de horas extra.</p>
                    </div>
                </a>

            </div>
        </div>

        <!-- Footer con logout -->
        <div class="text-center pt-6 border-t border-gray-200">
            <a href="<?= BASE_URL ?>logout" class="inline-flex items-center gap-2 text-gray-500 hover:text-red-600 font-medium transition">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </a>
        </div>

    </div>


<?php else: ?>
    <!-- Dashboard Administrador (claro) -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Encabezado -->
        <div class="mb-8 rounded-2xl bg-gradient-to-r from-slate-800 to-slate-900 p-6 shadow-xl text-white">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Dashboard</h1>
                    <p class="text-blue-200 text-sm mt-1">Panel de Control General · Gestión de Servicios</p>
                    <div class="mt-2 flex items-center gap-2 text-sm">
                        <i class="fas fa-user-circle"></i>
                        <span><?= htmlspecialchars($nombreUsuario) ?> (<?= htmlspecialchars($rolUsuario) ?>)</span>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-3xl font-mono font-bold"><?= $horaActual ?></p>
                    <p class="text-sm opacity-80"><?= $fechaHoy ?></p>
                </div>
            </div>
        </div>

        <!-- Tarjetas de estadísticas (solo 3 ahora) -->
        <?php if (!empty($estadisticas)): ?>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-10">
            <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-blue-500 hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Órdenes este mes</p>
                        <p class="text-3xl font-bold text-gray-800"><?= number_format($estadisticas['ordenes_mes']) ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-clipboard-list text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-green-500 hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Clientes activos</p>
                        <p class="text-3xl font-bold text-gray-800"><?= number_format($estadisticas['clientes']) ?></p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-users text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-purple-500 hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Técnicos activos</p>
                        <p class="text-3xl font-bold text-gray-800"><?= number_format($estadisticas['tecnicos']) ?></p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-user-cog text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-8 rounded">
            <p class="text-yellow-700"><i class="fas fa-info-circle mr-2"></i> No se pudieron cargar estadísticas. Verifica las tablas.</p>
        </div>
        <?php endif; ?>

        <!-- Operaciones Rápidas (igual que antes) -->
        <h2 class="text-xl font-bold text-gray-700 mb-4 border-b pb-2 flex items-center gap-2">
            <i class="fas fa-rocket text-blue-500"></i> Operaciones Rápidas
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <a href="<?= BASE_URL ?>ordenCrear" class="group relative bg-gradient-to-br from-blue-600 to-indigo-700 rounded-xl shadow-lg p-6 text-white overflow-hidden transform hover:-translate-y-1 transition duration-300">
                <div class="absolute right-0 top-0 opacity-10 text-7xl group-hover:scale-110 transition">+</div>
                <div class="relative z-10">
                    <i class="fas fa-plus-circle text-3xl mb-2 block"></i>
                    <h3 class="text-xl font-bold">Crear Servicio</h3>
                    <p class="text-blue-100 text-sm mt-1">Nueva orden de mantenimiento</p>
                </div>
            </a>
            <a href="<?= BASE_URL ?>ordenVer" class="group bg-white rounded-xl shadow-md p-6 border border-gray-100 hover:border-blue-300 transition">
                <div class="flex items-center gap-4">
                    <div class="bg-blue-50 p-3 rounded-lg group-hover:bg-blue-600 transition">
                        <i class="fas fa-list-alt text-blue-600 group-hover:text-white text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Gestionar Órdenes</h3>
                        <p class="text-gray-500 text-sm">Listado, estados y asignaciones</p>
                    </div>
                </div>
            </a>
            <a href="<?= BASE_URL ?>clienteVer" class="group bg-white rounded-xl shadow-md p-6 border border-gray-100 hover:border-purple-300 transition">
                <div class="flex items-center gap-4">
                    <div class="bg-purple-50 p-3 rounded-lg group-hover:bg-purple-600 transition">
                        <i class="fa-regular fa-user text-purple-600 group-hover:text-white text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Base de Clientes</h3>
                        <p class="text-gray-500 text-sm">Información y sedes</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Reportes -->
        <h2 class="text-xl font-bold text-gray-700 mb-4 border-b pb-2 flex items-center gap-2">
            <i class="fas fa-chart-pie text-indigo-500"></i> Centro de Reportes
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-12">
            <?php $reportes = [
                ['url'=>'reporteEjecutivo', 'icon'=>'fa-chart-line', 'color'=>'indigo', 'titulo'=>'Reporte Ejecutivo', 'desc'=>'KPIs y gráficas'],
                ['url'=>'reporteTecnico', 'icon'=>'fa-book-open-reader', 'color'=>'cyan', 'titulo'=>'Reporte Técnico', 'desc'=>'Productividad'],
                ['url'=>'reporteRepuesto', 'icon'=>'fa-screwdriver-wrench', 'color'=>'teal', 'titulo'=>'Reporte Repuestos', 'desc'=>'Consumo de materiales']
            ]; ?>
            <?php foreach($reportes as $r): ?>
            <a href="<?= BASE_URL . $r['url'] ?>" class="bg-white rounded-xl shadow p-4 hover:shadow-lg transition flex items-center gap-4 border-l-4 border-<?= $r['color'] ?>-500">
                <div class="bg-<?= $r['color'] ?>-100 p-3 rounded-full">
                    <i class="fas <?= $r['icon'] ?> text-<?= $r['color'] ?>-600 text-xl"></i>
                </div>
                <div>
                    <h4 class="font-bold text-gray-800"><?= $r['titulo'] ?></h4>
                    <p class="text-xs text-gray-500"><?= $r['desc'] ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Configuración rápida -->
        <h2 class="text-xl font-bold text-gray-700 mb-4 border-b pb-2 flex items-center gap-2">
            <i class="fas fa-cogs text-gray-500"></i> Configuración Rápida
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <?php $modulos = [
                ['url'=>'usuarioVer', 'icon'=>'fa-key', 'color'=>'yellow', 'texto'=>'Usuarios'],
                ['url'=>'tecnicoVer', 'icon'=>'fa-user-gear', 'color'=>'blue', 'texto'=>'Técnicos'],
                ['url'=>'maquinaVer', 'icon'=>'fa-cash-register', 'color'=>'green', 'texto'=>'Máquinas'],
                ['url'=>'puntoVer', 'icon'=>'fa-city', 'color'=>'purple', 'texto'=>'Puntos'],
                ['url'=>'repuestoVer', 'icon'=>'fa-puzzle-piece', 'color'=>'orange', 'texto'=>'Repuestos'],
                ['url'=>'tarifaVer', 'icon'=>'fa-money-bill', 'color'=>'emerald', 'texto'=>'Tarifas']
            ]; ?>
            <?php foreach($modulos as $m): ?>
            <a href="<?= BASE_URL . $m['url'] ?>" class="flex flex-col items-center p-4 bg-gray-50 rounded-xl hover:bg-white transition border border-gray-200 text-center group">
                <i class="fas <?= $m['icon'] ?> text-<?= $m['color'] ?>-600 text-2xl mb-2 group-hover:scale-110 transition"></i>
                <span class="text-xs font-semibold text-gray-700"><?= $m['texto'] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>