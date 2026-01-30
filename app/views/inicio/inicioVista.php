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