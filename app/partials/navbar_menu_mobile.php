<?php 
// Detectamos si es técnico
$esTecnico = (isset($_SESSION['nivel_acceso']) && $_SESSION['nivel_acceso'] == 3);
?>

<?php if ($esTecnico): ?>

    <a href="<?= BASE_URL ?>inicio" 
       class="block text-gray-300 py-3 px-3 rounded hover:bg-gray-700 border-b border-gray-700 font-bold">
        <i class="fas fa-home mr-3 w-5 text-center"></i> Inicio
    </a>

    <a href="<?= BASE_URL ?>ordenMovil" 
       class="block text-white bg-blue-900/50 py-3 px-3 rounded hover:bg-blue-800 border-b border-gray-700 font-bold mt-2">
        <i class="fas fa-search mr-3 w-5 text-center text-blue-300"></i> Consultar Historial
    </a>

<?php else: ?>

    <a href="<?= BASE_URL ?>inicio" 
       class="block text-gray-300 py-3 px-3 rounded hover:bg-gray-700 border-b border-gray-700 font-bold">
        <i class="fas fa-home mr-3 w-5 text-center"></i> Inicio
    </a>

    <details class="group border-b border-gray-700">
        <summary class="flex justify-between items-center cursor-pointer list-none text-gray-300 py-3 px-3 hover:bg-gray-700 rounded select-none">
            <span class="font-bold"><i class="fas fa-cogs mr-3 w-5 text-center"></i> Operaciones</span>
            <span class="transition group-open:rotate-180">
                <i class="fas fa-chevron-down"></i>
            </span>
        </summary>
        <div class="text-gray-400 mt-2 mb-2 pl-4 bg-gray-800 rounded-lg py-2">
            <a href="<?= BASE_URL ?>ordenCrear" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded flex items-center">
                <i class="fas fa-plus-circle text-green-500 mr-2"></i> Nuevo Servicio
            </a>
            <a href="<?= BASE_URL ?>ordenVer" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded flex items-center">
                <i class="fas fa-list-alt text-blue-500 mr-2"></i> Gestionar Servicios
            </a>
            <a href="<?= BASE_URL ?>ordenDetalleBuscar" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded flex items-center">
                <i class="fa-solid fa-diamond mr-2"></i> Buscar Servicio
            </a>
            <a href="<?= BASE_URL ?>remisionesPendientes" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded flex items-center">
                <i class="fa-solid fa-traffic-light text-orange-500 mr-2"></i> Remisiones Salteadas
            </a>
        </div>
    </details>

    <details class="group border-b border-gray-700">
        <summary class="flex justify-between items-center cursor-pointer list-none text-gray-300 py-3 px-3 hover:bg-gray-700 rounded select-none">
            <span class="font-bold"><i class="fas fa-truck mr-3 w-5 text-center"></i> Logística</span>
            <span class="transition group-open:rotate-180">
                <i class="fas fa-chevron-down"></i>
            </span>
        </summary>
        <div class="text-gray-400 mt-2 mb-2 pl-4 bg-gray-800 rounded-lg py-2">
            <a href="<?= BASE_URL ?>inventarioTecnicoVer" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded flex items-center">
                <i class="fa-brands fa-centos text-yellow-600 mr-2"></i> Inv. Técnicos
            </a>
            <a href="<?= BASE_URL ?>repuestoVer" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded flex items-center">
                <i class="fa-solid fa-puzzle-piece mr-2"></i> Repuestos
            </a>
            <a href="<?= BASE_URL ?>controlRemisionVer" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded flex items-center">
                <i class="fa-solid fa-book mr-2"></i> Remisiones
            </a>
        </div>
    </details>

    <details class="group border-b border-gray-700">
        <summary class="flex justify-between items-center cursor-pointer list-none text-gray-300 py-3 px-3 hover:bg-gray-700 rounded select-none">
            <span class="font-bold"><i class="fas fa-chart-bar mr-3 w-5 text-center"></i> Reportes</span>
            <span class="transition group-open:rotate-180">
                <i class="fas fa-chevron-down"></i>
            </span>
        </summary>
        <div class="text-gray-400 mt-2 mb-2 pl-4 bg-gray-800 rounded-lg py-2">
            <a href="<?= BASE_URL ?>reporteEjecutivo" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded flex items-center">
                <i class="fa-solid fa-chart-line text-purple-600 mr-2"></i> Ejecutivo
            </a>
            <a href="<?= BASE_URL ?>ordenReporte" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded flex items-center">
                <i class="fa-solid fa-file-excel text-green-600 mr-2"></i> Excel Fechas
            </a>
            <a href="<?= BASE_URL ?>exportarExcel" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded flex items-center">
                <i class="fa-solid fa-building-user mr-2"></i> Excel Programación
            </a>
            <a href="<?= BASE_URL ?>reporteTecnico" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded flex items-center">
                <i class="fa-solid fa-book-open-reader mr-2"></i> R. Técnicos
            </a>
            <a href="<?= BASE_URL ?>reporteRepuesto" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded flex items-center">
                <i class="fa-solid fa-screwdriver-wrench mr-2"></i> R. Repuestos
            </a>
            <a href="<?= BASE_URL ?>reporteMaquinas" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded flex items-center">
                <i class="fa-solid fa-cash-register mr-2"></i> R. Máquinas
            </a>
        </div>
    </details>

    <details class="group border-b border-gray-700">
        <summary class="flex justify-between items-center cursor-pointer list-none text-gray-300 py-3 px-3 hover:bg-gray-700 rounded select-none">
            <span class="font-bold"><i class="fas fa-shield-alt mr-3 w-5 text-center"></i> Administración</span>
            <span class="transition group-open:rotate-180">
                <i class="fas fa-chevron-down"></i>
            </span>
        </summary>
        <div class="text-gray-400 mt-2 mb-2 pl-4 bg-gray-800 rounded-lg py-2 max-h-[40vh] overflow-y-auto">
            
            <p class="px-4 py-1 text-xs font-bold text-gray-500 uppercase mt-2">Personas</p>
            <a href="<?= BASE_URL ?>usuarioVer" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded">Usuarios</a>
            <a href="<?= BASE_URL ?>tecnicoVer" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded">Técnicos</a>
            <a href="<?= BASE_URL ?>clienteVer" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded">Clientes</a>

            <p class="px-4 py-1 text-xs font-bold text-gray-500 uppercase mt-2">Maestros</p>
            <a href="<?= BASE_URL ?>maquinaVer" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded">Máquinas</a>
            <a href="<?= BASE_URL ?>puntoVer" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded">Puntos</a>
            <a href="<?= BASE_URL ?>tarifaVer" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded">Tarifas</a>
            
            <p class="px-4 py-1 text-xs font-bold text-gray-500 uppercase mt-2">Configuración</p>
            <a href="<?= BASE_URL ?>tecnicoVer" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded">Administrar Técnicos</a>
            <a href="<?= BASE_URL ?>diasFestivosVer" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded">Días Festivos</a>
            <a href="<?= BASE_URL ?>informacionBaseDatos" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded">Información Base de Datos</a>
            <a href="<?= BASE_URL ?>calificacionServicioVer" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded">Tipos de Calificación</a>
            <a href="<?= BASE_URL ?>delegacionVer" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded">Tipos de Delegación</a>
            <a href="<?= BASE_URL ?>estadoMaquinaVer" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded">Tipos de Estado de Máquina</a>
            <a href="<?= BASE_URL ?>tipoMantenimientoVer" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded">Tipos de Mantenimiento</a>
            <a href="<?= BASE_URL ?>tipoMaquinaVer" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded">Tipos de Máquina</a>
            <a href="<?= BASE_URL ?>modalidadOperativaVer" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded">Tipos de Modalidad Operativa</a>
            <a href="<?= BASE_URL ?>tipoNovedadVer" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded">Tipos de Novedades</a>
            <a href="<?= BASE_URL ?>tipoUsuarioVer" class="block py-2 px-4 hover:text-white hover:bg-gray-700 rounded">Tipos de Usuarios</a>
            
            <a href="<?= BASE_URL ?>importarExcel" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 text-green-600 font-bold">Importar Excel Prosegur</a>
            <a href="<?= BASE_URL ?>importarMunicipios" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 text-green-600 font-bold">Importar Zonas Geográficas</a>
            
        </div>
    </details>

<?php endif; ?>

<div class="mt-4 mb-4">
    <a href="<?= BASE_URL ?>logout" class="block text-red-400 py-3 px-3 rounded hover:bg-red-900/30 text-center border border-red-900 font-bold transition">
        <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
    </a>
</div>