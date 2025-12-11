<?php if (!defined(constant_name: 'ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

<style>
    .dataTables_length select,
    .dataTables_filter input {
        background-color: white !important;
        color: #374151 !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        margin: 0 0.5rem;
    }

    .dataTables_length label,
    .dataTables_filter label {
        color: #4b5563 !important;
        font-weight: 500;
        display: flex;
        align-items: center;
        white-space: nowrap;
    }

    #usuariosTable tbody tr {
        background-color: white !important;
        color: #374151 !important;
    }

    #usuariosTable tbody tr:hover {
        background-color: #f9fafb !important;
    }

    .dataTables_info {
        color: #6b7280 !important;
    }

    .dataTables_paginate .paginate_button {
        color: #4b5563 !important;
        background-color: #f3f4f6 !important;
        border: 1px solid #d1d5db;
    }

    .dataTables_paginate .paginate_button.current,
    .dataTables_paginate .paginate_button:hover {
        background-color: #4f46e5 !important;
        color: white !important;
        border-color: #4f46e5 !important;
    }

    /* Contenedores de los controles (superior e inferior) para que sean responsive */
    .dataTables_wrapper>div:first-child,
    .dataTables_wrapper>div:last-of-type {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
        margin-top: 1.5rem;
    }
</style>

<div class="w-full max-w-7xl mx-auto">
    <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 border-b pb-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Gestión de Usuarios</h1>
                <p class="text-gray-500 mt-1">Crea, edita o elimina los usuarios del sistema.</p>
            </div>
            <a href="<?php echo BASE_URL; ?>usuarioCrear" class="mt-4 sm:mt-0 px-5 py-2.5 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transition-all duration-300 transform hover:scale-105 flex items-center space-x-2">
                <i class="fa-solid fa-user-plus"></i>
                <span>Crear Nuevo Usuario</span>
            </a>
        </div>

        <?php if (!empty($data['usuarios'])): ?>
            <div class="overflow-x-auto">
                <table id="usuariosTable" class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-800 uppercase bg-gray-100">
                        <tr>
                            <th class="py-3 px-4">ID</th>
                            <th class="py-3 px-4">Nombre</th>
                            <th class="py-3 px-4">Cédula</th>
                            <th class="py-3 px-4">Cargo</th>
                            <th class="py-3 px-4">Rol</th>
                            <th class="py-3 px-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['usuarios'] as $usuario): ?>
                            <tr class="border-b">
                                <td class="py-4 px-4 font-medium text-gray-900"><?php echo htmlspecialchars(string: $usuario['usuario_id']); ?></td>
                                <td class="py-4 px-4"><?php echo htmlspecialchars(string: $usuario['nombre']); ?></td>
                                <td class="py-4 px-4"><?php echo htmlspecialchars(string: $usuario['cedula']); ?></td>
                                <td class="py-4 px-4"><?php echo htmlspecialchars(string: $usuario['cargo']); ?></td>
                                <td class="py-4 px-4"><?php echo htmlspecialchars(string: $usuario['rol']); ?></td>
                                <td class="py-4 px-4 text-center whitespace-nowrap">
                                    <div class="flex justify-center items-center space-x-2">
                                        <a href="<?php echo BASE_URL . 'usuarioEditar/' . htmlspecialchars(string: $usuario['usuario_id']); ?>" class="p-2 w-9 h-9 flex items-center justify-center bg-yellow-400 text-white rounded-full hover:bg-yellow-500 transition-colors" title="Editar">
                                            <i class="fa-solid fa-user-pen"></i>
                                        </a>
                                        <button data-modal-trigger data-id="<?php echo htmlspecialchars(string: $usuario['usuario_id']); ?>" class="p-2 w-9 h-9 flex items-center justify-center bg-red-600 text-white rounded-full hover:bg-red-700 transition-colors" title="Eliminar">
                                            <i class="fa-solid fa-user-minus"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center p-8 border-2 border-dashed rounded-lg">
                <i class="fa-solid fa-users-slash text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-500 font-medium">No hay usuarios activos para mostrar.</p>
                <p class="text-gray-400 text-sm mt-1">¡Crea el primer usuario para empezar!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="confirmModal" class="fixed inset-0 z-50 overflow-y-auto hidden flex items-center justify-center bg-black bg-opacity-60 transition-opacity duration-300">
    <div class="bg-white rounded-lg p-6 w-full max-w-sm mx-auto shadow-xl transform transition-all duration-300 scale-95">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fa-solid fa-triangle-exclamation text-xl text-red-600"></i>
            </div>
            <h2 class="text-xl font-bold my-4 text-gray-800">Confirmar Eliminación</h2>
            <p class="text-gray-600 mb-6">¿Estás seguro de que quieres eliminar este usuario? Esta acción no se puede deshacer.</p>
        </div>
        <div class="flex justify-center space-x-4">
            <button data-modal-close class="px-6 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 font-medium transition-colors">Cancelar</button>
            <a id="confirmButton" href="#" class="px-6 py-2 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition-colors">Sí, eliminar</a>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script>
    // Esto pasa la variable de PHP a JavaScript
    const BASE_URL = "<?php echo BASE_URL; ?>";
</script>
<script src="<?php echo BASE_URL; ?>js/usuario/usuarioVer.js"></script>