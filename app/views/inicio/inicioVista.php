<?php 
// Verificamos el rol (Asumiendo que 'nivel_acceso' es la variable de sesión para el rol)
$esTecnico = (isset($_SESSION['nivel_acceso']) && $_SESSION['nivel_acceso'] == 3);
?>

<?php if ($esTecnico): ?>

    <div class="min-h-[80vh] flex flex-col items-center justify-center px-4">
        
        <div class="bg-white w-full max-w-sm rounded-3xl shadow-2xl overflow-hidden border border-gray-100">
            
            <div class="bg-gradient-to-br from-indigo-600 to-blue-500 p-8 text-center text-white">
                <div class="mb-4 inline-block bg-white/20 p-4 rounded-full backdrop-blur-sm">
                    <i class="fas fa-user-astronaut fa-3x"></i>
                </div>
                <h1 class="text-2xl font-bold">¡Hola! 👋</h1>
                <p class="text-indigo-100 text-sm mt-1">
                    <?= date('d \d\e F, Y') ?>
                </p>
            </div>

            <div class="p-8 space-y-6">
                
                <p class="text-gray-500 text-center text-sm mb-4">
                    ¿Qué deseas hacer hoy?
                </p>

                <a href="<?= BASE_URL ?>ordenMovil" 
                    class="group block w-full bg-white border-2 border-indigo-100 hover:border-indigo-500 rounded-2xl p-6 text-center shadow-sm hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                    
                    <div class="bg-indigo-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-indigo-600 transition-colors duration-300">
                        <i class="fas fa-search text-2xl text-indigo-600 group-hover:text-white transition-colors"></i>
                    </div>
                    
                    <h3 class="text-lg font-bold text-gray-800 group-hover:text-indigo-700">Consultar Historial</h3>
                    <p class="text-xs text-gray-400 mt-1">Buscar servicios anteriores por cliente y punto.</p>
                </a>

                <a href="<?= BASE_URL ?>logout" class="block w-full text-center text-gray-400 text-sm hover:text-red-500 font-medium py-2">
                    <i class="fas fa-sign-out-alt mr-1"></i> Cerrar Sesión
                </a>

            </div>
        </div>

    </div>

<?php else: ?>

    <div class="max-w-7xl mx-auto">

        <div class="mb-8 flex flex-col md:flex-row justify-between items-center bg-gradient-to-r from-blue-900 to-blue-700 p-6 rounded-xl shadow-lg text-white">
            <div>
                <h1 class="text-3xl font-bold">¡Hola, bienvenido de nuevo! 👋</h1>
                <p class="text-blue-100 mt-2 text-sm">Panel de Control General del Sistema de Gestión de Servicios.</p>
            </div>
            <div class="mt-4 md:mt-0 text-right">
                <p class="text-2xl font-bold"><?= date('H:i') ?></p>
                <p class="text-sm opacity-80"><?= date('d \d\e F, Y') ?></p>
            </div>
        </div>

        <h2 class="text-xl font-bold text-gray-700 mb-4 border-b pb-2"><i class="fas fa-rocket text-blue-600 mr-2"></i> Operaciones Rápidas</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">

            <a href="<?= BASE_URL ?>ordenCrear" class="group relative bg-gradient-to-br from-blue-600 to-blue-800 rounded-xl shadow-lg p-6 text-white overflow-hidden transform hover:-translate-y-1 transition duration-300">
                <div class="absolute right-0 top-0 opacity-10 transform translate-x-4 -translate-y-4 group-hover:scale-110 transition duration-500">
                    <i class="fas fa-plus-circle fa-6x"></i>
                </div>
                <div class="relative z-10">
                    <h3 class="text-2xl font-bold mb-2">Crear Nuevos Servicios</h3>
                    <p class="text-blue-100 text-sm mb-4">Registrar un nuevo servicio de mantenimiento.</p>
                    <span class="inline-block bg-white text-blue-700 font-bold px-4 py-2 rounded-lg text-sm group-hover:bg-blue-50 transition">
                        Acceder <i class="fas fa-arrow-right ml-1"></i>
                    </span>
                </div>
            </a>

            <a href="<?= BASE_URL ?>ordenVer" class="group bg-white rounded-xl shadow-md p-6 border border-gray-100 hover:border-blue-300 transform hover:-translate-y-1 transition duration-300">
                <div class="flex items-center mb-3 text-blue-600">
                    <div class="bg-blue-50 p-3 rounded-lg group-hover:bg-blue-600 group-hover:text-white transition">
                        <i class="fas fa-list-alt fa-2x"></i>
                    </div>
                    <h3 class="ml-4 text-xl font-bold text-gray-800">Gestionar Órdenes de Servicios</h3>
                </div>
                <p class="text-gray-500 text-sm">Ver listado completo, editar estados y asignar técnicos.</p>
            </a>

            <a href="<?= BASE_URL ?>clienteVer" class="group bg-white rounded-xl shadow-md p-6 border border-gray-100 hover:border-purple-300 transform hover:-translate-y-1 transition duration-300">
                <div class="flex items-center mb-3 text-purple-600">
                    <div class="bg-purple-50 p-3 rounded-lg group-hover:bg-purple-600 group-hover:text-white transition">
                        <i class="fa-regular fa-user fa-2x"></i>
                    </div>
                    <h3 class="ml-4 text-xl font-bold text-gray-800">Base de Clientes</h3>
                </div>
                <p class="text-gray-500 text-sm">Administrar la información de clientes y sus sedes.</p>
            </a>
        </div>

        <h2 class="text-xl font-bold text-gray-700 mb-4 border-b pb-2"><i class="fas fa-chart-pie text-indigo-600 mr-2"></i> Centro de Reportes</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">

            <a href="<?= BASE_URL ?>reporteEjecutivo" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition flex items-center border-l-4 border-indigo-500">
                <div class="bg-indigo-100 text-indigo-600 p-3 rounded-lg mr-4">
                    <i class="fa-solid fa-chart-line fa-xl"></i>
                </div>
                <div>
                    <h4 class="font-bold text-gray-800">Reporte Ejecutivo</h4>
                    <p class="text-xs text-gray-500">KPIs y gráficas gerenciales</p>
                </div>
            </a>

            <a href="<?= BASE_URL ?>reporteTecnico" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition flex items-center border-l-4 border-cyan-500">
                <div class="bg-cyan-100 text-cyan-600 p-3 rounded-lg mr-4">
                    <i class="fa-solid fa-book-open-reader fa-xl"></i>
                </div>
                <div>
                    <h4 class="font-bold text-gray-800">Reporte Técnico</h4>
                    <p class="text-xs text-gray-500">Productividad y servicios</p>
                </div>
            </a>

            <a href="<?= BASE_URL ?>reporteRepuesto" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition flex items-center border-l-4 border-teal-500">
                <div class="bg-teal-100 text-teal-600 p-3 rounded-lg mr-4">
                    <i class="fa-solid fa-screwdriver-wrench fa-xl"></i>
                </div>
                <div>
                    <h4 class="font-bold text-gray-800">Reporte Repuestos</h4>
                    <p class="text-xs text-gray-500">Consumo de materiales</p>
                </div>
            </a>

        </div>

        <h2 class="text-xl font-bold text-gray-700 mb-4 border-b pb-2"><i class="fas fa-cogs text-gray-600 mr-2"></i> Configuración Rápida</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">

            <a href="<?= BASE_URL ?>usuarioVer" class="flex flex-col items-center justify-center p-4 bg-gray-50 rounded-lg hover:bg-white hover:shadow-md transition border border-gray-200 text-center">
                <i class="fas fa-key text-yellow-600 mb-2 text-xl"></i>
                <span class="text-xs font-bold text-gray-600">Usuarios</span>
            </a>

            <a href="<?= BASE_URL ?>tecnicoVer" class="flex flex-col items-center justify-center p-4 bg-gray-50 rounded-lg hover:bg-white hover:shadow-md transition border border-gray-200 text-center">
                <i class="fa-solid fa-user-gear text-blue-600 mb-2 text-xl"></i>
                <span class="text-xs font-bold text-gray-600">Técnicos</span>
            </a>

            <a href="<?= BASE_URL ?>maquinaVer" class="flex flex-col items-center justify-center p-4 bg-gray-50 rounded-lg hover:bg-white hover:shadow-md transition border border-gray-200 text-center">
                <i class="fa-solid fa-cash-register text-green-600 mb-2 text-xl"></i>
                <span class="text-xs font-bold text-gray-600">Máquinas</span>
            </a>

            <a href="<?= BASE_URL ?>puntoVer" class="flex flex-col items-center justify-center p-4 bg-gray-50 rounded-lg hover:bg-white hover:shadow-md transition border border-gray-200 text-center">
                <i class="fa-solid fa-city text-purple-600 mb-2 text-xl"></i>
                <span class="text-xs font-bold text-gray-600">Puntos</span>
            </a>

            <a href="<?= BASE_URL ?>repuestoVer" class="flex flex-col items-center justify-center p-4 bg-gray-50 rounded-lg hover:bg-white hover:shadow-md transition border border-gray-200 text-center">
                <i class="fa-solid fa-puzzle-piece text-orange-600 mb-2 text-xl"></i>
                <span class="text-xs font-bold text-gray-600">Repuestos</span>
            </a>

            <a href="<?= BASE_URL ?>tarifaVer" class="flex flex-col items-center justify-center p-4 bg-gray-50 rounded-lg hover:bg-white hover:shadow-md transition border border-gray-200 text-center">
                <i class="fa-solid fa-money-bill text-emerald-600 mb-2 text-xl"></i>
                <span class="text-xs font-bold text-gray-600">Tarifas</span>
            </a>

        </div>
    </div>

<?php endif; ?>