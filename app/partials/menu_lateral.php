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

<p class="px-6 py-2 text-xs text-gray-500 uppercase font-bold mt-4">Configuración</p>

<a href="<?= BASE_URL ?>usuarioVer"
   class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-yellow-500 transition flex items-center">
    <i class="fas fa-key mr-3 w-6 text-center"></i> Administrar Usuarios
</a>

<a href="<?= BASE_URL ?>repuestoVer"
   class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-yellow-500 transition flex items-center">
    <i class="fas fa-key mr-3 w-6 text-center"></i> Crear Repuestos
</a>

<p class="px-6 py-2 text-xs text-gray-500 uppercase font-bold mt-4">Cuenta</p>

<div class="mt-2">
    <a href="<?= BASE_URL ?>logout"
       class="block py-3 px-6 hover:bg-red-900 text-red-200 border-l-4 border-transparent hover:border-red-500 transition flex items-center">
        <i class="fas fa-sign-out-alt mr-3 w-6 text-center"></i> Cerrar Sesión
    </a>
</div>