<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<div class="w-full max-w-5xl mx-auto">
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">

        <div class="mb-6 border-b pb-4">
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-map-marker-alt text-red-600 mr-2"></i> Nuevo Punto de Atención</h1>
            <p class="text-gray-500 mt-1">Registra la ubicación donde se prestará el servicio.</p>
        </div>

        <?php if (!empty($errores)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <ul class="list-disc list-inside text-sm"><?php foreach ($errores as $e) echo "<li>$e</li>"; ?></ul>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>puntoCrear" method="POST" class="space-y-6">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-1">Nombre del Punto <span class="text-red-500">*</span></label>
                    <input type="text" name="nombre_punto" required placeholder="Ej: Sucursal Centro Comercial Andino"
                        value="<?= htmlspecialchars($datos['nombre_punto'] ?? '') ?>"
                        class="w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-red-500">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Cliente / Empresa <span class="text-red-500">*</span></label>
                    <select name="id_cliente" required class="w-full px-3 py-3 border border-gray-300 rounded-lg bg-white">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($listaClientes as $c): ?>
                            <option value="<?= $c['id_cliente'] ?>" <?= ($datos['id_cliente'] ?? '') == $c['id_cliente'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre_cliente']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Modalidad <span class="text-red-500">*</span></label>
                    <select name="id_modalidad" required class="w-full px-3 py-3 border border-gray-300 rounded-lg bg-white">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($listaModalidades as $m): ?>
                            <option value="<?= $m['id_modalidad'] ?>" <?= ($datos['id_modalidad'] ?? '') == $m['id_modalidad'] ? 'selected' : '' ?>><?= htmlspecialchars($m['nombre_modalidad']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Municipio <span class="text-red-500">*</span></label>
                    <select name="id_municipio" required class="w-full px-3 py-3 border border-gray-300 rounded-lg bg-white">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($listaMunicipios as $mun): ?>
                            <option value="<?= $mun['id_municipio'] ?>" <?= ($datos['id_municipio'] ?? '') == $mun['id_municipio'] ? 'selected' : '' ?>><?= htmlspecialchars($mun['nombre_municipio']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Delegación (Opcional)</label>
                    <select name="id_delegacion" class="w-full px-3 py-3 border border-gray-300 rounded-lg bg-white">
                        <option value="">-- Ninguna --</option>
                        <?php foreach ($listaDelegaciones as $d): ?>
                            <option value="<?= $d['id_delegacion'] ?>" <?= ($datos['id_delegacion'] ?? '') == $d['id_delegacion'] ? 'selected' : '' ?>><?= htmlspecialchars($d['nombre_delegacion']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-1">Dirección Física</label>
                    <input type="text" name="direccion" placeholder="Ej: Calle 85 # 11-53"
                        value="<?= htmlspecialchars($datos['direccion'] ?? '') ?>"
                        class="w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Código Interno 1</label>
                    <input type="text" name="codigo_1" value="<?= htmlspecialchars($datos['codigo_1'] ?? '') ?>" class="w-full px-3 py-3 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Código Interno 2</label>
                    <input type="text" name="codigo_2" value="<?= htmlspecialchars($datos['codigo_2'] ?? '') ?>" class="w-full px-3 py-3 border border-gray-300 rounded-lg">
                </div>
            </div>

            <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
                <a href="<?= BASE_URL ?>puntoVer" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="px-8 py-3 bg-red-600 text-white font-bold rounded-lg shadow-md hover:bg-red-700 transform hover:-translate-y-1 transition-all">Guardar Punto</button>
            </div>
        </form>
    </div>
</div>