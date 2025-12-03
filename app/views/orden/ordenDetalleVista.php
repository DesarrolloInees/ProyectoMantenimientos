<div class="bg-white p-4 rounded shadow-lg w-full">

    <div class="flex flex-wrap justify-between items-center mb-4">
        <div>
            <h2 class="text-xl font-bold text-gray-800">🛠️ Edición Maestra de Servicios</h2>
            <p class="text-sm text-blue-600 font-bold">Fecha Lote: <?= $_GET['fecha'] ?></p>
        </div>

        <div class="space-x-2">
            <button type="button" onclick="exportarExcelLimpio()" class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700 shadow">
                <i class="fas fa-file-excel mr-2"></i> Excel Limpio
            </button>
            <a href="index.php?pagina=ordenVer" class="bg-gray-500 text-white px-4 py-2 rounded font-bold hover:bg-gray-600">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <form action="index.php?pagina=ordenDetalle&accion=guardarCambios" method="POST">
        <input type="hidden" name="fecha_origen" value="<?= $_GET['fecha'] ?>">

        <div class="overflow-x-auto shadow-inner border rounded" style="max-height: 80vh;">
            <table class="min-w-max text-xs bg-white border-collapse" id="tablaEdicion">
                <thead class="bg-gray-800 text-white uppercase tracking-wider sticky top-0 z-10">
                    <tr>
                        <th class="p-2 border bg-indigo-900 w-40">1. Cliente</th>
                        <th class="p-2 border bg-indigo-900 w-40">2. Punto</th>
                        <th class="p-2 border bg-indigo-900 w-32">3. Máquina (Device)</th>

                        <th class="p-2 border bg-blue-900">4. Remisión</th>
                        <th class="p-2 border w-32">5. Servicio</th>
                        <th class="p-2 border w-24">6. Valor</th>
                        <th class="p-2 border w-64">7. ¿Qué se hizo?</th>
                        <th class="p-2 border bg-gray-700">8. Deleg.</th>
                        <th class="p-2 border w-24">9. Fecha</th>
                        <th class="p-2 border w-32">10. Técnico</th>
                        <th class="p-2 border bg-gray-700">11. Tipo Maq</th>
                        <th class="p-2 border">12. Entra</th>
                        <th class="p-2 border">13. Sale</th>
                        <th class="p-2 border">14. Duración</th>
                        <th class="p-2 border w-32">15. Repuestos</th>
                        <th class="p-2 border w-24">16. Estado</th>
                        <th class="p-2 border w-24">17. Calif</th>
                        <th class="p-2 border bg-gray-700">18. Zona</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($servicios)): ?>
                        <tr>
                            <td colspan="18" class="p-4 text-center text-red-500">No hay datos.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($servicios as $s): ?>

                            <?php $idFila = $s['id_ordenes_servicio']; ?>

                            <tr class="hover:bg-blue-50 transition">

                                <td class="p-1">
                                    <select name="servicios[<?= $idFila ?>][id_cliente]" 
                                            onchange="cargarPuntos(<?= $idFila ?>, this.value)" 
                                            class="w-full border rounded p-1 font-bold text-indigo-900">
                                        <?php foreach ($listaClientes as $c): ?>
                                            <option value="<?= $c['id_cliente'] ?>" <?= $c['id_cliente'] == $s['id_cliente'] ? 'selected' : '' ?>>
                                                <?= $c['nombre_cliente'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                                <!-- COLUMNA 2: PUNTO -->
                                <td class="p-1">
                                    <select
                                        id="sel_punto_<?= $idFila ?>"
                                        name="servicios[<?= $idFila ?>][id_punto]"
                                        onchange="cargarMaquinas(<?= $idFila ?>, this.value)"
                                        class="w-full border rounded p-1">
                                        <option value="<?= $s['id_punto'] ?? '' ?>" selected><?= $s['nombre_punto'] ?></option>
                                    </select>
                                </td>

                                <td class="p-1">
                                    <select id="sel_maq_<?= $idFila ?>" 
                                            name="servicios[<?= $idFila ?>][id_maquina]" 
                                            onchange="actualizarTipoMaquina(<?= $idFila ?>)"
                                            class="w-full border rounded p-1 font-mono text-blue-600 font-bold">
                                        <!-- Guardamos el tipo de máquina en un atributo data-tipo para leerlo con JS -->
                                        <option value="<?= $s['id_maquina'] ?>" data-tipo="<?= $s['nombre_tipo_maquina'] ?>" selected>
                                            <?= $s['device_id'] ?>
                                        </option>
                                    </select>
                                </td>

                                <td class="p-1"><input type="text" name="servicios[<?= $idFila ?>][remision]" value="<?= $s['numero_remision'] ?>" class="w-20 border rounded text-center text-blue-800 font-bold"></td>

                                <td class="p-1">
                                    <select name="servicios[<?= $idFila ?>][id_manto]" class="w-full border rounded p-1">
                                        <?php foreach ($listaMantos as $m): ?>
                                            <option value="<?= $m['id_tipo_mantenimiento'] ?>" <?= $m['id_tipo_mantenimiento'] == $s['id_manto'] ? 'selected' : '' ?>>
                                                <?= $m['nombre_completo'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                                <td class="p-1"><input type="text" name="servicios[<?= $idFila ?>][valor]" value="<?= number_format($s['valor_servicio'], 0, ',', '.') ?>" class="w-24 border rounded text-right font-bold text-green-700"></td>

                                <td class="p-1"><textarea name="servicios[<?= $idFila ?>][obs]" rows="2" class="w-full border rounded text-[10px]"><?= $s['que_se_hizo'] ?></textarea></td>

                                <!-- DELEGACIÓN: Le ponemos ID para actualizarla -->
                                <td class="p-2 bg-gray-50 text-[10px]" id="td_delegacion_<?= $idFila ?>">
                                    <?= $s['delegacion'] ?>
                                </td>

                                <td class="p-1"><input type="date" name="servicios[<?= $idFila ?>][fecha_individual]" value="<?= $s['fecha_visita'] ?>" class="w-full border rounded"></td>

                                <td class="p-1">
                                    <select name="servicios[<?= $idFila ?>][id_tecnico]" class="w-full border rounded p-1">
                                        <?php foreach ($listaTecnicos as $t): ?>
                                            <option value="<?= $t['id_tecnico'] ?>" <?= $t['id_tecnico'] == $s['id_tecnico'] ? 'selected' : '' ?>>
                                                <?= $t['nombre_tecnico'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                                <!-- TIPO MAQUINA: Le ponemos ID para actualizarlo -->
                                <td class="p-2 bg-gray-50 text-[10px]" id="td_tipomaq_<?= $idFila ?>">
                                    <?= $s['nombre_tipo_maquina'] ?>
                                </td>

                                <td class="p-1"><input type="time" name="servicios[<?= $idFila ?>][entrada]" value="<?= $s['hora_entrada'] ?>" class="w-full border rounded"></td>
                                <td class="p-1"><input type="time" name="servicios[<?= $idFila ?>][salida]" value="<?= $s['hora_salida'] ?>" class="w-full border rounded"></td>

                                <td class="p-2 text-center font-bold"><?= $s['tiempo_servicio'] ?></td>

                                <td class="p-2 text-[9px]"><?= $s['repuestos_usados'] ?></td>

                                <td class="p-1">
                                    <select name="servicios[<?= $idFila ?>][id_estado]" class="w-full border rounded p-1 text-[10px]">
                                        <?php foreach ($listaEstados as $e): ?>
                                            <option value="<?= $e['id_estado'] ?>" <?= $e['id_estado'] == $s['id_estado'] ? 'selected' : '' ?>>
                                                <?= $e['nombre_estado'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                                <td class="p-1">
                                    <select name="servicios[<?= $idFila ?>][id_calif]" class="w-full border rounded p-1 text-[10px]">
                                        <?php foreach ($listaCalifs as $c): ?>
                                            <option value="<?= $c['id_calificacion'] ?>" <?= $c['id_calificacion'] == $s['id_calif'] ? 'selected' : '' ?>>
                                                <?= $c['nombre_calificacion'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                                <td class="p-2 bg-gray-50 text-[10px]"><?= $s['tipo_zona'] ?></td>

                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-4 text-center pb-8 sticky bottom-0 bg-white border-t p-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-3 px-10 rounded-full shadow-xl">
                <i class="fas fa-save mr-2"></i> GUARDAR TODO
            </button>
        </div>
    </form>
</div>

<script>
    function cargarPuntos(idFila, idCliente) {
        const selectPunto = document.getElementById(`sel_punto_${idFila}`);
        const selectMaq = document.getElementById(`sel_maq_${idFila}`);

        selectPunto.innerHTML = '<option value="">Cargando...</option>';
        selectMaq.innerHTML = '<option value="">(Seleccione Punto)</option>';

        const formData = new FormData();
        formData.append('accion', 'ajaxObtenerPuntos');
        formData.append('id_cliente', idCliente);

        fetch('index.php?pagina=ordenDetalle', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                selectPunto.innerHTML = '<option value="">- Seleccione Punto -</option>';
                data.forEach(p => {
                    selectPunto.innerHTML += `<option value="${p.id_punto}">${p.nombre_punto}</option>`;
                });
            });
    }

    function cargarMaquinas(idFila, idPunto) {
        // 1. CARGAR MÁQUINAS
        const selectMaq = document.getElementById(`sel_maq_${idFila}`);
        selectMaq.innerHTML = '<option value="">Cargando...</option>';

        const formData = new FormData();
        formData.append('accion', 'ajaxObtenerMaquinas');
        formData.append('id_punto', idPunto);

        fetch('index.php?pagina=ordenDetalle', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                selectMaq.innerHTML = '<option value="">- Seleccione Máquina -</option>';
                data.forEach(m => {
                    // Guardamos el tipo de máquina en data-tipo para leerlo luego
                    selectMaq.innerHTML += `<option value="${m.id_maquina}" data-tipo="${m.nombre_tipo_maquina}">${m.device_id} (${m.nombre_tipo_maquina})</option>`;
                });
            });

        // 2. ACTUALIZAR DELEGACIÓN AUTOMÁTICAMENTE
        actualizarDelegacion(idFila, idPunto);
    }

    // --- NUEVA FUNCIÓN: Actualizar celda de delegación ---
    function actualizarDelegacion(idFila, idPunto) {
        const celdaDelegacion = document.getElementById(`td_delegacion_${idFila}`);
        celdaDelegacion.innerText = '...';

        const fd = new FormData();
        fd.append('accion', 'ajaxObtenerDelegacion');
        fd.append('id_punto', idPunto);

        fetch('index.php?pagina=ordenDetalle', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                celdaDelegacion.innerText = data.delegacion || 'No Definida';
                // Opcional: ponerla en negrita para mostrar cambio
                celdaDelegacion.classList.add('font-bold', 'text-blue-600');
            });
    }

    // --- NUEVA FUNCIÓN: Actualizar celda de Tipo de Máquina ---
    function actualizarTipoMaquina(idFila) {
        const selectMaq = document.getElementById(`sel_maq_${idFila}`);
        const celdaTipo = document.getElementById(`td_tipomaq_${idFila}`);
        
        // Obtenemos el texto del atributo data-tipo de la opción seleccionada
        const opcionSeleccionada = selectMaq.options[selectMaq.selectedIndex];
        const tipo = opcionSeleccionada.getAttribute('data-tipo');
        
        if(tipo) {
            celdaTipo.innerText = tipo;
            celdaTipo.classList.add('font-bold', 'text-green-600');
        }
    }

    function exportarExcelLimpio() {
    // 1. OBTENER TODAS LAS FILAS DE LA TABLA
    let tabla = document.getElementById("tablaEdicion");
    let filas = Array.from(tabla.querySelectorAll('tbody tr'));
    
    // 2. AGRUPAR POR DELEGACIÓN
    let serviciosPorDelegacion = {};
    
    filas.forEach((fila, index) => {
        // Extraer datos de cada columna
        let celdas = fila.querySelectorAll('td');
        
        if(celdas.length === 0) return; // Saltar filas vacías
        
        // Extraer valores según el ORDEN ORIGINAL de tu tabla
        let datos = {
            // Orden original en la tabla HTML:
            cliente: obtenerTexto(celdas[0]),      // Col 1 en HTML
            punto: obtenerTexto(celdas[1]),        // Col 2 en HTML
            maquina: obtenerTexto(celdas[2]),      // Col 3 en HTML
            remision: obtenerTexto(celdas[3]),     // Col 4 en HTML
            servicio: obtenerTexto(celdas[4]),     // Col 5 en HTML (Tipo Servicio)
            valor: obtenerTexto(celdas[5]),        // Col 6 en HTML (Tarifa)
            queSeHizo: obtenerTexto(celdas[6]),    // Col 7 en HTML
            delegacion: celdas[7].innerText.trim(), // Col 8 en HTML ⭐
            fecha: obtenerTexto(celdas[8]),        // Col 9 en HTML
            tecnico: obtenerTexto(celdas[9]),      // Col 10 en HTML
            tipoMaquina: celdas[10].innerText.trim(), // Col 11 en HTML
            horaEntrada: obtenerTexto(celdas[11]), // Col 12 en HTML
            horaSalida: obtenerTexto(celdas[12]),  // Col 13 en HTML
            duracion: celdas[13].innerText.trim(), // Col 14 en HTML
            repuestos: celdas[14].innerText.trim(), // Col 15 en HTML
            estado: obtenerTexto(celdas[15]),      // Col 16 en HTML
            calificacion: obtenerTexto(celdas[16]), // Col 17 en HTML
            zona: celdas[17].innerText.trim()      // Col 18 en HTML (Modalidad)
        };
        
        // Agrupar por delegación
        let delegacion = datos.delegacion || 'SIN_ASIGNAR';
        
        if(!serviciosPorDelegacion[delegacion]) {
            serviciosPorDelegacion[delegacion] = [];
        }
        
        serviciosPorDelegacion[delegacion].push(datos);
    });
    
    // 3. CREAR EL LIBRO DE EXCEL CON MÚLTIPLES HOJAS
    let workbook = XLSX.utils.book_new();
    
    // 4. CREAR UNA HOJA POR CADA DELEGACIÓN
    for(let delegacion in serviciosPorDelegacion) {
        let servicios = serviciosPorDelegacion[delegacion];
        
        // Crear array de datos para la hoja CON EL ORDEN SOLICITADO
        let datosHoja = [
            // ⭐ ENCABEZADOS EN EL ORDEN QUE PEDISTE
            [
                'Número de Remisión',        // 1
                'Cliente',                   // 2
                'Nombre Punto',              // 3
                'Tarifa',                    // 4
                'Observaciones',             // 5
                'Delegación',                // 6
                'Fecha',                     // 7
                'Técnico',                   // 8
                'Tipo de Máquina',           // 9
                'Tipo de Servicio',          // 10
                'Hora Entrada',              // 11
                'Hora Salida',               // 12
                'Duración',                  // 13
                'Repuestos',                 // 14
                'Estado de la Máquina',      // 15
                'Calificación del Servicio', // 16
                'Modalidad Operativa'        // 17
            ]
        ];
        
        // ⭐ AGREGAR FILAS DE SERVICIOS EN EL ORDEN SOLICITADO
        servicios.forEach(s => {
            datosHoja.push([
                s.remision,      // 1. Número de remisión
                s.cliente,       // 2. Cliente
                s.punto,         // 3. Nombre Punto
                s.valor,         // 4. Tarifa
                s.queSeHizo,     // 5. Observaciones
                s.delegacion,    // 6. Delegación
                s.fecha,         // 7. Fecha
                s.tecnico,       // 8. Técnico
                s.tipoMaquina,   // 9. Tipo de Máquina
                s.servicio,      // 10. Tipo de Servicio
                s.horaEntrada,   // 11. Hora Entrada
                s.horaSalida,    // 12. Hora Salida
                s.duracion,      // 13. Duración
                s.repuestos,     // 14. Repuestos
                s.estado,        // 15. Estado de la Máquina
                s.calificacion,  // 16. Calificación del Servicio
                s.zona           // 17. Modalidad Operativa (URBANO/INTERURBANO)
            ]);
        });
        
        // Crear la hoja
        let worksheet = XLSX.utils.aoa_to_sheet(datosHoja);
        
        // Ajustar ancho de columnas para mejor visualización
        worksheet['!cols'] = [
            {wch: 15}, // Remisión
            {wch: 25}, // Cliente
            {wch: 25}, // Punto
            {wch: 12}, // Tarifa
            {wch: 40}, // Observaciones
            {wch: 15}, // Delegación
            {wch: 12}, // Fecha
            {wch: 20}, // Técnico
            {wch: 18}, // Tipo Máquina
            {wch: 20}, // Tipo Servicio
            {wch: 12}, // Hora Entrada
            {wch: 12}, // Hora Salida
            {wch: 10}, // Duración
            {wch: 30}, // Repuestos
            {wch: 20}, // Estado
            {wch: 20}, // Calificación
            {wch: 15}  // Modalidad
        ];
        
        // Limpiar nombre de la hoja (Excel no permite ciertos caracteres)
        let nombreHoja = delegacion.substring(0, 31) // Máximo 31 caracteres
                                    .replace(/[:\\\/\?\*\[\]]/g, '_'); // Quitar caracteres especiales
        
        // Agregar hoja al libro
        XLSX.utils.book_append_sheet(workbook, worksheet, nombreHoja);
    }
    
    // 5. DESCARGAR EL ARCHIVO
    let fecha = "<?= $_GET['fecha'] ?>";
    XLSX.writeFile(workbook, `Reporte_Delegaciones_${fecha}.xlsx`);
    
    alert(`✅ Excel generado con ${Object.keys(serviciosPorDelegacion).length} hojas (delegaciones)`);
}

// FUNCIÓN AUXILIAR: Extraer texto de inputs/selects/textareas
function obtenerTexto(celda) {
    let input = celda.querySelector('input');
    let textarea = celda.querySelector('textarea');
    let select = celda.querySelector('select');
    
    if(input) return input.value;
    if(textarea) return textarea.value;
    if(select) return select.options[select.selectedIndex].text;
    
    return celda.innerText.trim();
}
</script>