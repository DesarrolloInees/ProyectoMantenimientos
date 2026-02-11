<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<div class="w-full max-w-6xl mx-auto">
    <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-200">

        <div class="mb-8 border-b pb-4 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-800"><i class="fas fa-calendar-alt text-indigo-600 mr-2"></i> Generador de Rutas</h1>
                <p class="text-gray-500 mt-1">Configura los parámetros para la asignación masiva de servicios.</p>
            </div>
            <div class="hidden md:block bg-indigo-50 p-3 rounded-full">
                <i class="fas fa-cogs text-3xl text-indigo-500"></i>
            </div>
        </div>

        <?php if (!empty($errores)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                <p class="font-bold">Por favor corrige los siguientes errores:</p>
                <ul class="list-disc list-inside text-sm mt-1"><?php foreach ($errores as $e) echo "<li>$e</li>"; ?></ul>
            </div>
        <?php endif; ?>

        <form id="formProgramacion" action="<?= BASE_URL ?>programacionCrear?accion=previsualizar" method="POST">

    <div class="bg-blue-50 p-6 rounded-lg border border-blue-100 shadow-sm">
        <h3 class="text-lg font-bold text-blue-800 mb-4"><i class="fas fa-map-marked-alt mr-2"></i> 1. Zona de Operación</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Delegación Principal</label>
                <select name="id_delegacion" id="id_delegacion" onchange="cargarDatosDinamicos()" class="w-full px-4 py-2 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                    <option value="">-- Seleccione Delegación --</option>
                    <?php foreach ($listaDelegaciones as $d): ?>
                        <option value="<?= $d['id_delegacion'] ?>"><?= $d['nombre_delegacion'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">
                    Zonas Específicas / Localidades 
                    <span class="text-xs font-normal text-gray-500">(Puedes marcar varias)</span>
                </label>
                <div id="contenedor_zonas" class="bg-white border border-gray-300 rounded-lg p-3 h-32 overflow-y-auto grid grid-cols-2 gap-2">
                    <p class="text-gray-400 text-xs col-span-2 p-2">Selecciona una delegación para cargar las localidades...</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-800"><i class="fas fa-users-cog mr-2"></i> 2. Asignación de Rutas (Técnicos)</h3>
            <div class="text-xs text-indigo-600 bg-indigo-100 px-2 py-1 rounded">
                <i class="fas fa-info-circle"></i> Los resaltados pertenecen a la delegación seleccionada
            </div>
        </div>

        <div id="contenedor_tecnicos" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 max-h-60 overflow-y-auto p-2">
            <p class="text-gray-400 text-sm col-span-4 text-center py-4">Esperando selección de zona...</p>
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
        <h3 class="text-lg font-bold text-gray-800 mb-4"><i class="fas fa-sliders-h mr-2"></i> 3. Reglas de Programación</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase">Rango de Fechas</label>
                <div class="flex space-x-2 mt-1">
                    <input type="date" name="fecha_inicio" required class="w-full border rounded p-2 text-sm">
                    <input type="date" name="fecha_fin" required class="w-full border rounded p-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase">Gestión de Carga</label>
                <div class="mt-2 space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="usar_sabados_buffer" checked class="form-checkbox text-green-600 h-4 w-4">
                        <span class="ml-2 text-sm text-gray-700">Usar Sábados para sobrantes (Buffer)</span>
                    </label>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-700">Meta L-V:</span>
                        <input type="number" name="meta_diaria" value="8" class="w-16 border rounded p-1 text-center text-sm">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase">Filtros de Servicio</label>
                <div class="mt-2 space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="solo_correctivos" class="form-checkbox text-red-600 h-4 w-4">
                        <span class="ml-2 text-sm text-gray-700 font-bold">Solo Correctivos</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="incluir_nunca_visitados" class="form-checkbox text-blue-600 h-4 w-4">
                        <span class="ml-2 text-sm text-gray-700">Incluir puntos nuevos (Sin fecha)</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end pt-4">
        <button type="submit" class="bg-indigo-700 hover:bg-indigo-900 text-white text-lg font-bold py-3 px-8 rounded-lg shadow-xl transform transition hover:-translate-y-1">
            Generar Previsualización <i class="fas fa-arrow-right ml-2"></i>
        </button>
    </div>
</form>

<script>
function cargarDatosDinamicos() {
    const delegacionId = document.getElementById('id_delegacion').value;
    const divZonas = document.getElementById('contenedor_zonas');
    const divTecnicos = document.getElementById('contenedor_tecnicos');

    if (!delegacionId) return;

    // UI de carga
    divZonas.innerHTML = '<div class="col-span-2 text-center text-blue-500"><i class="fas fa-spinner fa-spin"></i> Buscando localidades...</div>';
    divTecnicos.innerHTML = '<div class="col-span-4 text-center text-blue-500"><i class="fas fa-spinner fa-spin"></i> Cargando técnicos...</div>';

    const formData = new FormData();
    formData.append('id_delegacion', delegacionId);
    
    // Acción para el controlador
    formData.append('accion', 'cargarDatosAuxiliares'); 

    fetch('<?= BASE_URL ?>programacionCrear', { 
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) { throw new Error("Error de red: " + response.status); }
        return response.text(); 
    })
    .then(texto => {
        try {
            return JSON.parse(texto); 
        } catch (e) {
            console.error("El servidor devolvió HTML en vez de JSON:", texto);
            throw new Error("Respuesta inválida del servidor");
        }
    })
    .then(data => {
        console.log("Datos recibidos:", data); 

        // 1. RENDERIZAR ZONAS (Se mantiene igual)
        divZonas.innerHTML = '';
        if (data.zonas && data.zonas.length > 0) {
            divZonas.innerHTML += `
                <label class="flex items-center p-1 col-span-2 border-b mb-1 cursor-pointer w-full hover:bg-gray-50">
                    <input type="checkbox" onchange="toggleTodos(this, 'check_zona')" class="form-checkbox text-gray-600 rounded">
                    <span class="ml-2 text-xs font-bold uppercase text-gray-600">Seleccionar Todas</span>
                </label>
            `;
            data.zonas.forEach(z => {
                if(z.zona) {
                    divZonas.innerHTML += `
                        <label class="flex items-center space-x-2 cursor-pointer hover:bg-blue-50 p-1 rounded transition">
                            <input type="checkbox" name="zonas[]" value="${z.zona}" class="check_zona form-checkbox text-blue-600 h-4 w-4 rounded">
                            <span class="text-xs text-gray-700 truncate font-medium" title="${z.zona}">${z.zona}</span>
                        </label>
                    `;
                }
            });
        } else {
            divZonas.innerHTML = '<div class="col-span-2 p-3 text-center bg-gray-50 rounded border border-dashed border-gray-300"><p class="text-xs text-gray-500">Esta delegación no tiene zonas registradas.</p></div>';
        }

        // 2. RENDERIZAR TÉCNICOS (CORREGIDO: Ya no se seleccionan solos)
        divTecnicos.innerHTML = '';
        if (data.tecnicos && data.tecnicos.length > 0) {
            data.tecnicos.forEach(tec => {
                // Mantenemos el color azulito para saber que son de la zona, pero NO el check
                const claseBg = tec.sugerido ? 'bg-indigo-50 border-indigo-200 ring-1 ring-indigo-100' : 'bg-white border-gray-200';
                const badge = tec.sugerido ? '<span class="ml-auto text-[10px] bg-indigo-100 text-indigo-700 px-1 rounded font-bold">ZONA</span>' : '';
                
                divTecnicos.innerHTML += `
                    <div class="${claseBg} p-2 rounded-lg border hover:shadow-md transition duration-200 group">
                        <label class="flex items-start cursor-pointer w-full select-none">
                            <input type="checkbox" name="tecnicos[]" value="${tec.id_tecnico}" class="mt-1 form-checkbox text-indigo-600 h-4 w-4 rounded border-gray-300 focus:ring-indigo-500">
                            <div class="ml-2 w-full">
                                <div class="flex items-center justify-between">
                                    <span class="block text-sm font-bold text-gray-800 group-hover:text-indigo-700">${tec.etiqueta_tecnico}</span>
                                    ${badge}
                                </div>
                                <span class="block text-[10px] text-gray-400 mt-0.5">ID Interno: ${tec.id_tecnico}</span>
                            </div>
                        </label>
                    </div>
                `;
            });
        } else {
            divTecnicos.innerHTML = '<div class="col-span-4 p-4 text-center bg-red-50 rounded border border-red-100"><p class="text-sm text-red-500"><i class="fas fa-exclamation-circle mr-1"></i> No se encontraron técnicos activos.</p></div>';
        }
    })
    .catch(error => {
        console.error("Error JS:", error);
        divZonas.innerHTML = '<p class="text-red-500 text-xs text-center col-span-2">Error de conexión.</p>';
        divTecnicos.innerHTML = '<p class="text-red-500 text-sm text-center col-span-4">No se pudo cargar la lista.</p>';
    });
}

function toggleTodos(source, className) {
    const checkboxes = document.querySelectorAll('.' + className);
    checkboxes.forEach(cb => cb.checked = source.checked);
}
</script>