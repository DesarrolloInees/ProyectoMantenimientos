<?php 
// Detectamos si es técnico
$esTecnico = (isset($_SESSION['nivel_acceso']) && $_SESSION['nivel_acceso'] == 3);
?>

<?php if ($esTecnico): ?>

    <a href="<?= BASE_URL ?>inicio" 
       class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition">
        <i class="fas fa-home mr-1"></i> Inicio
    </a>

    <a href="<?= BASE_URL ?>ordenMovil" 
       class="text-gray-300 hover:bg-blue-600 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition ml-2">
        <i class="fas fa-search mr-1"></i> Consultar Historial
    </a>

<?php else: ?>

    <a href="<?= BASE_URL ?>inicio" 
       class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition">
        <i class="fas fa-home mr-1"></i> Inicio
    </a>

    <div class="relative group h-full flex items-center ml-2">
        <button class="text-gray-300 group-hover:bg-gray-700 group-hover:text-white px-3 py-2 rounded-md text-sm font-medium inline-flex items-center transition">
            <span>Operaciones</span>
            <i class="fas fa-chevron-down ml-2 text-xs opacity-75"></i>
        </button>
        <div class="absolute left-0 top-12 w-56 bg-white rounded-md shadow-lg py-1 hidden group-hover:block border border-gray-200 z-[9999]">
            <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50">Gestión de Servicios</div>
            
            <a href="<?= BASE_URL ?>ordenCrear" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 border-l-4 border-transparent hover:border-blue-500">
                <i class="fas fa-plus-circle w-5 text-center mr-1 text-green-500"></i> Nuevo Servicio
            </a>
            <a href="<?= BASE_URL ?>ordenVer" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 border-l-4 border-transparent hover:border-blue-500">
                <i class="fas fa-list-alt w-5 text-center mr-1 text-blue-500"></i> Gestionar Servicios
            </a>
            <a href="<?= BASE_URL ?>ordenDetalleBuscar" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 border-l-4 border-transparent hover:border-blue-500">
                <i class="fa-solid fa-diamond w-5 text-center mr-1"></i> Buscar Servicio
            </a>
            <a href="<?= BASE_URL ?>remisionesPendientes" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 border-l-4 border-transparent hover:border-blue-500">
                <i class="fa-solid fa-traffic-light w-5 text-center mr-1 text-orange-500"></i> Remisiones Salteadas
            </a>
        </div>
    </div>

    <div class="relative group h-full flex items-center ml-2">
        <button class="text-gray-300 group-hover:bg-gray-700 group-hover:text-white px-3 py-2 rounded-md text-sm font-medium inline-flex items-center transition">
            <span>Logística</span>
            <i class="fas fa-chevron-down ml-2 text-xs opacity-75"></i>
        </button>
        <div class="absolute left-0 top-12 w-60 bg-white rounded-md shadow-lg py-1 hidden group-hover:block border border-gray-200 z-[9999]">
            <a href="<?= BASE_URL ?>inventarioTecnicoVer" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 border-l-4 border-transparent hover:border-blue-500">
                <i class="fa-brands fa-centos w-5 text-center mr-1 text-yellow-600"></i> Inventario Técnicos
            </a>
            <a href="<?= BASE_URL ?>repuestoVer" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 border-l-4 border-transparent hover:border-blue-500">
                <i class="fa-solid fa-puzzle-piece w-5 text-center mr-1"></i> Gestión Repuestos
            </a>
            <a href="<?= BASE_URL ?>controlRemisionVer" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 border-l-4 border-transparent hover:border-blue-500">
                <i class="fa-solid fa-book w-5 text-center mr-1"></i> Admin. Remisiones
            </a>
        </div>
    </div>

    <div class="relative group h-full flex items-center ml-2">
        <button class="text-gray-300 group-hover:bg-gray-700 group-hover:text-white px-3 py-2 rounded-md text-sm font-medium inline-flex items-center transition">
            <span>Reportes</span>
            <i class="fas fa-chevron-down ml-2 text-xs opacity-75"></i>
        </button>
        <div class="absolute left-0 top-12 w-64 bg-white rounded-md shadow-lg py-1 hidden group-hover:block border border-gray-200 z-[9999]">
            <a href="<?= BASE_URL ?>reporteEjecutivo" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 border-l-4 border-transparent hover:border-blue-500">
                <i class="fa-solid fa-chart-line w-5 text-center mr-1 text-purple-600"></i> Reporte Ejecutivo
            </a>
            <div class="border-t border-gray-100 my-1"></div>
            <a href="<?= BASE_URL ?>ordenReporte" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 border-l-4 border-transparent hover:border-blue-500">
                <i class="fa-solid fa-file-excel w-5 text-center mr-1 text-green-600"></i> Excel por Fechas
            </a>
            <a href="<?= BASE_URL ?>exportarExcel" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 border-l-4 border-transparent hover:border-blue-500">
                <i class="fa-solid fa-building-user w-5 text-center mr-1"></i> Excel Programación
            </a>
            <a href="<?= BASE_URL ?>reporteTecnico" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 border-l-4 border-transparent hover:border-blue-500">
                <i class="fa-solid fa-book-open-reader w-5 text-center mr-1"></i> R. Técnicos
            </a>
            <a href="<?= BASE_URL ?>reporteRepuesto" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 border-l-4 border-transparent hover:border-blue-500">
                <i class="fa-solid fa-screwdriver-wrench w-5 text-center mr-1"></i> R. Repuestos
            </a>
            <a href="<?= BASE_URL ?>reporteMaquinas" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 border-l-4 border-transparent hover:border-blue-500">
                <i class="fa-solid fa-cash-register w-5 text-center mr-1"></i> R. Máquinas
            </a>
        </div>
    </div>

    <div class="relative group h-full flex items-center ml-2">
        <button class="text-gray-300 group-hover:bg-gray-700 group-hover:text-white px-3 py-2 rounded-md text-sm font-medium inline-flex items-center transition">
            <span>Administración</span>
            <i class="fas fa-chevron-down ml-2 text-xs opacity-75"></i>
        </button>
        <div class="absolute right-0 md:left-auto md:right-auto top-12 w-80 bg-white rounded-md shadow-lg py-1 hidden group-hover:block border border-gray-200 z-[9999] max-h-[80vh] overflow-y-auto">
            
            <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50">Personas & Accesos</div>
            <a href="<?= BASE_URL ?>usuarioVer" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Usuarios y Accesos</a>
            <a href="<?= BASE_URL ?>tecnicoVer" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Técnicos</a>
            <a href="<?= BASE_URL ?>clienteVer" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Clientes</a>
            
            <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50 mt-2">Maestros del Sistema</div>
            <a href="<?= BASE_URL ?>maquinaVer" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Máquinas</a>
            <a href="<?= BASE_URL ?>puntoVer" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Puntos</a>
            <a href="<?= BASE_URL ?>tarifaVer" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Tarifas</a>
            
            <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50 mt-2">Configuraciones Varias</div>
            <a href="<?= BASE_URL ?>tecnicoVer" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Administrar Técnicos</a>
            <a href="<?= BASE_URL ?>diasFestivosVer" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Días Festivos</a>
            <a href="<?= BASE_URL ?>informacionBaseDatos" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Información Base de Datos</a>
            <a href="<?= BASE_URL ?>calificacionServicioVer" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Típos de Calificación</a>
            <a href="<?= BASE_URL ?>delegacionVer" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Típos de Delegación</a>
            <a href="<?= BASE_URL ?>estadoMaquinaVer" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Típos de Estado de Máquina</a>
            <a href="<?= BASE_URL ?>tipoMantenimientoVer" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Tipos Mantenimiento</a>
            <a href="<?= BASE_URL ?>tipoMaquinaVer" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Tipos de Máquina</a>
            <a href="<?= BASE_URL ?>modalidadOperativaVer" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Típos de Modalidad Operativa</a>
            <a href="<?= BASE_URL ?>tipoNovedadVer" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Tipos de Novedades</a>
            <a href="<?= BASE_URL ?>tipoUsuarioVer" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Tipos Usuario</a>
            <a href="<?= BASE_URL ?>importarExcel" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 text-green-600 font-bold">Importar Excel Prosegur</a>
            <a href="<?= BASE_URL ?>importarMunicipios" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 text-green-600 font-bold">Importar Zonas Geográficas</a>
        </div>
    </div>

<?php endif; ?>