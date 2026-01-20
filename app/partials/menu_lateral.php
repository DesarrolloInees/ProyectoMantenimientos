<?php
// Asegúrate de que no haya espacios antes de este bloque PHP si es necesario
?>

<a href="<?= BASE_URL ?>inicio"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-blue-500 transition flex items-center">
    <i class="fas fa-home mr-3 w-6 text-center"></i> Inicio
</a>

<p class="px-6 py-2 text-xs text-gray-500 uppercase font-bold mt-4">Gestión de Servicios</p>

<a href="<?= BASE_URL ?>ordenCrear"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-green-500 transition flex items-center">
    <i class="fas fa-plus-circle mr-3 w-6 text-center"></i> Nueva Orden
</a>

<a href="<?= BASE_URL ?>ordenVer"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-blue-500 transition flex items-center">
    <i class="fas fa-list-alt mr-3 w-6 text-center"></i> Gestión Órdenes
</a>

<a href="<?= BASE_URL ?>ordenDetalleBuscar"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-blue-500 transition flex items-center">
    <i class="fa-solid fa-diamond mr-3 w-6 text-center"></i> Buscar Orden Individual
</a>

<a href="<?= BASE_URL ?>ordenReporte"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-blue-500 transition flex items-center">
    <i class="fa-solid fa-file-excel mr-3 w-6 text-center"></i> Exportar Excel por fechas
</a>

<a href="<?= BASE_URL ?>exportarExcel"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-blue-500 transition flex items-center"> 
    <i class="fa-solid fa-building-user mr-3 w-6 text-center"></i> Exportar Excel Programación
</a>


<a href="<?= BASE_URL ?>reporteTecnico"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-blue-500 transition flex items-center">
    <i class="fa-solid fa-book-open-reader mr-3 w-6 text-center"></i> Reporte Tecnicos
</a>

<a href="<?= BASE_URL ?>reporteRepuesto"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-blue-500 transition flex items-center">
    <i class="fa-solid fa-screwdriver-wrench mr-3 w-6 text-center"></i> Reporte Repuestos
</a>

<a href="<?= BASE_URL ?>reporteEjecutivo"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-blue-500 transition flex items-center">
    <i class="fa-solid fa-chart-line mr-3 w-6 text-center"></i> Reporte Ejecutivo
</a>

<p class="px-6 py-2 text-xs text-gray-500 uppercase font-bold mt-4">Cuenta</p>

<div class="mt-2">
    <a href="<?= BASE_URL ?>logout"
        class="block py-3 px-6 hover:bg-red-900 text-red-200 border-l-4 border-transparent hover:border-red-500 transition flex items-center">
        <i class="fas fa-sign-out-alt mr-3 w-6 text-center"></i> Cerrar Sesión
    </a>
</div>

<p class="px-6 py-2 text-xs text-gray-500 uppercase font-bold mt-4">Configuración</p>

<a href="<?= BASE_URL ?>usuarioVer"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-yellow-500 transition flex items-center">
    <i class="fas fa-key mr-3 w-6 text-center"></i> Administrar Usuarios
</a>

<a href="<?= BASE_URL ?>inventarioTecnicoVer"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-yellow-500 transition flex items-center">
    <i class="fa-brands fa-centos mr-3 w-6 text-center"></i> Administrar Inventario Repuestos Técnicos
</a>

<a href="<?= BASE_URL ?>repuestoVer"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-yellow-500 transition flex items-center">
    <i class="fa-solid fa-puzzle-piece mr-3 w-6 text-center"></i> Crear Repuestos
</a>

<a href="<?= BASE_URL ?>controlRemisionVer"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-yellow-500 transition flex items-center">
    <i class="fa-solid fa-book mr-3 w-6 text-center"></i> Administrar Remisiones
</a>

<a href="<?= BASE_URL ?>clienteVer"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-yellow-500 transition flex items-center">
    <i class="fa-regular fa-user mr-3 w-6 text-center"></i> Administrar Clientes
</a>

<a href="<?= BASE_URL ?>tipoMaquinaVer"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-yellow-500 transition flex items-center">
    <i class="fa-solid fa-dharmachakra mr-3 w-6 text-center"></i> Administrar Tipos de Máquinas
</a>

<a href="<?= BASE_URL ?>tipoMantenimientoVer"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-yellow-500 transition flex items-center">
    <i class="fa-solid fa-screwdriver-wrench mr-3 w-6 text-center"></i> Administrar Tipos de Mantenimiento
</a>

<a href="<?= BASE_URL ?>calificacionServicioVer"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-yellow-500 transition flex items-center">
    <i class="fa-regular fa-face-grin mr-3 w-6 text-center"></i> Administrar Tipos de Calificacion
</a>

<a href="<?= BASE_URL ?>delegacionVer"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-yellow-500 transition flex items-center">
    <i class="fa-solid fa-building mr-3 w-6 text-center"></i> Administrar Tipos de Delegación
</a>

<a href="<?= BASE_URL ?>estadoMaquinaVer"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-yellow-500 transition flex items-center">
    <i class="fa-brands fa-bilibili mr-3 w-6 text-center"></i> Administrar Tipos de Estado de Máquina
</a>

<a href="<?= BASE_URL ?>modalidadOperativaVer"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-yellow-500 transition flex items-center">
    <i class="fa-solid fa-building mr-3 w-6 text-center"></i> Administrar Modalidad Operativa
</a>

<a href="<?= BASE_URL ?>tecnicoVer"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-yellow-500 transition flex items-center">
    <i class="fa-solid fa-user mr-3 w-6 text-center"></i> Administrar Tecnicos
</a>

<a href="<?= BASE_URL ?>tipoUsuarioVer"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-yellow-500 transition flex items-center">
    <i class="fa-solid fa-user mr-3 w-6 text-center"></i> Administrar Tipos Usuarios
</a>

<a href="<?= BASE_URL ?>tarifaVer"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-yellow-500 transition flex items-center">
    <i class="fa-solid fa-money-bill mr-3 w-6 text-center"></i> Administrar Tarifas
</a>

<a href="<?= BASE_URL ?>puntoVer"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-yellow-500 transition flex items-center">
    <i class="fa-solid fa-city mr-3 w-6 text-center"></i> Administrar Puntos
</a>

<a href="<?= BASE_URL ?>maquinaVer"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-yellow-500 transition flex items-center">
    <i class="fa-solid fa-cash-register mr-3 w-6 text-center"></i> Administrar Máquinas
</a>

<a href="<?= BASE_URL ?>diasFestivosVer"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-yellow-500 transition flex items-center">
    <i class="fa-solid fa-calendar-day mr-3 w-6 text-center"></i> Administrar Días Festivos
</a>
<a href="<?= BASE_URL ?>importarExcel"
    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-yellow-500 transition flex items-center">
    <i class="fa-solid fa-file-excel mr-3 w-6 text-center"></i> Importar Excel Prosegur
</a>