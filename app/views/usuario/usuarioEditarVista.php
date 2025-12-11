<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); 

// Extraer variables para facilitar la lectura
$usuario = $data['usuario'] ?? null;
$tiposUsuario = $data['tiposUsuario'] ?? [];
$errores = $data['errores'] ?? [];
?>

<div class="w-full max-w-3xl mx-auto">
    <?php if ($usuario): ?>
        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md">

            <div class="mb-6 border-b pb-4">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">
                    Editar Usuario: <span class="text-indigo-600"><?php echo htmlspecialchars($usuario['usuario']); ?></span>
                </h1>
            </div>

            <?php if (!empty($errores)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                    <p class="font-bold">Error:</p>
                    <ul class="list-disc pl-5">
                        <?php foreach ($errores as $error): ?>
                            <li><?= htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-6">
                
                <input type="hidden" name="usuario_id" value="<?php echo htmlspecialchars($usuario['usuario_id']); ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Nombre Completo</label>
                        <input type="text" name="nombre" required value="<?php echo htmlspecialchars($usuario['nombre']); ?>"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Cédula</label>
                        <input type="text" name="cedula" required value="<?php echo htmlspecialchars($usuario['cedula']); ?>"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Cargo</label>
                        <input type="text" name="cargo" required value="<?php echo htmlspecialchars($usuario['cargo']); ?>"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Celular</label>
                        <input type="tel" name="celular" required value="<?php echo htmlspecialchars($usuario['celular']); ?>"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-600">Email</label>
                        <input type="email" name="email" required value="<?php echo htmlspecialchars($usuario['email']); ?>"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Nombre de Usuario</label>
                        <input type="text" name="usuario" required value="<?php echo htmlspecialchars($usuario['usuario']); ?>"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Nueva Contraseña</label>
                        <input type="password" name="pass" placeholder="Dejar en blanco para mantener la actual"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 focus:ring-indigo-500">
                        <p class="text-xs text-gray-400 mt-1">Solo llena esto si quieres cambiarla.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600">Rol / Nivel de Acceso</label>
                        <select name="nivel_acceso" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-white">
                            <?php foreach ($tiposUsuario as $tipo): ?>
                                <option value="<?= htmlspecialchars($tipo['idTipoUsuario']); ?>" 
                                    <?= ($usuario['nivel_acceso'] == $tipo['idTipoUsuario']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($tipo['nombreTipoUsuario']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Estado</label>
                        <select name="estado" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-white">
                            <option value="activo" <?= ($usuario['estado'] == 'activo') ? 'selected' : ''; ?>>Activo</option>
                            <option value="inactivo" <?= ($usuario['estado'] == 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="pt-6 border-t flex justify-end space-x-4">
                    <a href="<?php echo BASE_URL; ?>usuarioVer" class="px-6 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg hover:bg-gray-300 transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-bold rounded-lg shadow-md hover:bg-blue-700 transition-all transform hover:scale-105">
                        Actualizar Usuario
                    </button>
                </div>
            </form>

        </div>
    <?php else: ?>
        <div class="text-center p-8 border-2 border-dashed rounded-lg bg-gray-50">
            <i class="fa-solid fa-user-xmark text-4xl text-gray-400 mb-4"></i>
            <h2 class="text-xl font-bold text-gray-700">Usuario no encontrado</h2>
            <p class="text-gray-500 mb-4">No se pudieron cargar los datos del usuario.</p>
            <a href="<?= BASE_URL ?>usuarioVer" class="text-indigo-600 hover:underline">Volver a la lista</a>
        </div>
    <?php endif; ?>
</div>