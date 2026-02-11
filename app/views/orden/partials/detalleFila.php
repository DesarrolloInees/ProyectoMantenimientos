<!-- ============================================== -->
<!-- PARTIAL: FILA DE SERVICIO -->
<!-- Variables disponibles: $s (servicio), $idFila -->
<!-- ============================================== -->

<tr class="hover:bg-blue-50 transition fila-servicio"
    data-idtecnico="<?= $s['id_tecnico'] ?>"
    data-idtipomaquina="<?= $s['id_tipo_maquina'] ?>"
    id="fila_<?= $idFila ?>">

    <!-- 1. CLIENTE -->
    <td class="p-1">
        <select name="servicios[<?= $idFila ?>][id_cliente]"
            onchange="cargarPuntos(<?= $idFila ?>, this.value)"
            class="select2-cliente w-full border rounded p-1 text-[10px]">
            <?php foreach ($listaClientes as $c): ?>
                <option value="<?= $c['id_cliente'] ?>"
                    data-full="<?= $c['nombre_cliente'] ?>"
                    <?= $c['id_cliente'] == $s['id_cliente'] ? 'selected' : '' ?>>
                    <?= substr($c['nombre_cliente'], 0, 20) ?>...
                </option>
            <?php endforeach; ?>
        </select>
    </td>

    <!-- 2. PUNTO -->
    <td class="p-1">
        <select id="sel_punto_<?= $idFila ?>"
            name="servicios[<?= $idFila ?>][id_punto]"
            onchange="cargarMaquinas(<?= $idFila ?>, this.value)"
            class="select2-punto w-full border rounded p-1 text-[10px]">
            <option value="<?= $s['id_punto'] ?? '' ?>"
                data-full="<?= $s['nombre_punto'] ?>" selected>
                <?= substr($s['nombre_punto'], 0, 20) ?>...
            </option>
        </select>
        <div id="td_delegacion_<?= $idFila ?>" class="hidden"><?= $s['delegacion'] ?></div>
    </td>

    <!-- 3. FECHA -->
    <td class="p-1">
        <input type="date"
            name="servicios[<?= $idFila ?>][fecha_individual]"
            value="<?= $s['fecha_visita'] ?>"
            class="w-full border rounded text-[10px]">
    </td>

    <!-- 4. TÉCNICO -->
    <td class="p-1 bg-indigo-50">
        <select name="servicios[<?= $idFila ?>][id_tecnico]"
            class="w-full border rounded p-1 font-bold text-indigo-800"
            onchange="calcularDesplazamientos()">
            <?php foreach ($listaTecnicos as $t): ?>
                <option value="<?= $t['id_tecnico'] ?>"
                    <?= $t['id_tecnico'] == $s['id_tecnico'] ? 'selected' : '' ?>>
                    <?= $t['nombre_tecnico'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </td>

    <!-- 5. SERVICIO -->
    <td class="p-1 bg-blue-50 border-l-4 border-blue-200">
        <select name="servicios[<?= $idFila ?>][id_manto]"
            id="sel_servicio_<?= $idFila ?>"
            onchange="actualizarTarifa(<?= $idFila ?>)"
            class="w-full border rounded p-1 font-bold text-blue-900 text-xs">
            <?php foreach ($listaMantos as $m): ?>
                <option value="<?= $m['id_tipo_mantenimiento'] ?>"
                    <?= $m['id_tipo_mantenimiento'] == $s['id_manto'] ? 'selected' : '' ?>>
                    <?= $m['nombre_completo'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </td>

    <!-- 6. ZONA/MODALIDAD -->
    <td class="p-1 bg-blue-50">
        <select name="servicios[<?= $idFila ?>][id_modalidad]"
            id="sel_modalidad_<?= $idFila ?>"
            onchange="actualizarTarifa(<?= $idFila ?>)"
            class="w-full border rounded p-1 font-bold text-gray-700 text-xs">
            <?php foreach ($listaModalidades as $mod): ?>
                <option value="<?= $mod['id_modalidad'] ?>"
                    <?= $mod['id_modalidad'] == $s['id_modalidad'] ? 'selected' : '' ?>>
                    <?= $mod['nombre_modalidad'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </td>

    <!-- 7. MÁQUINA -->
    <td class="p-1 bg-blue-50">
        <select id="sel_maq_<?= $idFila ?>"
            name="servicios[<?= $idFila ?>][id_maquina]"
            onchange="actualizarTipoMaquina(<?= $idFila ?>)"
            class="w-full border rounded p-1 font-mono text-blue-600 font-bold text-xs">
            <option value="<?= $s['id_maquina'] ?>"
                data-tipo="<?= $s['nombre_tipo_maquina'] ?>"
                data-idtipomaquina="<?= $s['id_tipo_maquina'] ?>"
                selected>
                <?= $s['device_id'] ?>
            </option>
        </select>
        <div id="td_tipomaq_<?= $idFila ?>" class="text-[9px] text-gray-400">
            <?= $s['nombre_tipo_maquina'] ?>
        </div>
    </td>

    <!-- 8. OBSERVACIONES -->
    <td class="p-1 bg-blue-50 border-r-4 border-blue-200">
        <textarea name="servicios[<?= $idFila ?>][obs]"
            rows="3"
            class="w-full border rounded text-xs p-1 shadow-inner focus:bg-white transition"><?= $s['que_se_hizo'] ?></textarea>
    </td>

    <!-- 9. NOVEDAD -->
    <td class="border p-1 text-center align-middle relative">
    <?php 
        $tieneNovedad = (isset($s['tiene_novedad']) && $s['tiene_novedad'] == 1) || 
                        (!empty($s['id_tipo_novedad']) && $s['id_tipo_novedad'] > 0);
        
        $claseIcono = $tieneNovedad ? 'text-red-600 animate-pulse' : 'text-gray-300 hover:text-yellow-500'; 
    ?>

    <button type="button" 
            onclick="abrirModalNovedad(<?= $idFila ?>)"
            id="btn-nov-<?= $idFila ?>"
            class="text-lg transition-colors duration-200 <?= $claseIcono ?>"
            title="<?= $tieneNovedad ? 'Tiene Novedad Reportada' : 'Reportar Novedad' ?>">
        <i class="fas fa-exclamation-triangle"></i>
    </button>

    <input type="hidden" id="hdn-tiene-<?= $idFila ?>" name="servicios[<?= $idFila ?>][tiene_novedad]" value="<?= $tieneNovedad ? 1 : 0 ?>">
    <input type="hidden" id="hdn-tipo-<?= $idFila ?>" name="servicios[<?= $idFila ?>][id_tipo_novedad]" value="<?= $s['id_tipo_novedad'] ?? '' ?>">
    
    </td>

    <!-- 10. VALOR -->
    <td class="p-1 bg-green-50">
        <input type="text"
            name="servicios[<?= $idFila ?>][valor]"
            id="input_valor_<?= $idFila ?>"
            value="<?= number_format($s['valor_servicio'], 0, ',', '.') ?>"
            class="w-full border rounded text-right font-bold text-green-700 text-sm">
            <input type="hidden" id="viaticos_<?= $idFila ?>" value="<?= $s['valor_viaticos'] ?? 0 ?>">
    </td>

    <!-- 11. REPUESTOS -->
    <td class="p-1 bg-gray-50 text-center align-middle">
        <?php
        $jsonRepuestos = $s['repuestos_json'] ?? '[]';
        $textoRepuestos = $s['repuestos_texto'] ?? '';

        // Decodificar JSON
        $arrayRepuestos = json_decode($jsonRepuestos, true) ?: [];

        // Sumar cantidades reales
        $cantidadRepuestos = 0;
        foreach ($arrayRepuestos as $rep) {
            $cant = isset($rep['cantidad']) ? intval($rep['cantidad']) : 1;
            $cantidadRepuestos += $cant;
        }
        ?>

        <button type="button"
            onclick="abrirModalRepuestos(<?= $idFila ?>)"
            class="bg-white border border-gray-300 hover:bg-blue-50 text-gray-700 text-[10px] px-2 py-1 rounded w-full shadow-sm transition flex items-center justify-center gap-1">
            <i class="fas fa-tools text-blue-500"></i>
            <span id="btn_texto_<?= $idFila ?>">
                <?= $cantidadRepuestos > 0 ? $cantidadRepuestos . ' Items' : '0 Items' ?>
            </span>
        </button>

        <!-- JSON para el modal -->
        <input type="hidden"
            name="servicios[<?= $idFila ?>][json_repuestos]"
            id="input_json_<?= $idFila ?>"
            value='<?= htmlspecialchars($jsonRepuestos, ENT_QUOTES, 'UTF-8') ?>'>

        <!-- Texto para compatibilidad -->
        <input type="hidden"
            id="input_db_<?= $idFila ?>"
            value='<?= htmlspecialchars($textoRepuestos, ENT_QUOTES, 'UTF-8') ?>'>
    </td>

    <!-- 12. REMISIÓN -->
    <td class="p-1">
        <input type="text"
            name="servicios[<?= $idFila ?>][remision]"
            value="<?= $s['numero_remision'] ?>"
            class="w-16 border rounded text-center text-[10px]">
    </td>

    <!-- 13. HORA ENTRADA -->
    <td class="p-1">
        <input type="time"
            name="servicios[<?= $idFila ?>][entrada]"
            id="hora_entrada_<?= $idFila ?>"
            value="<?= $s['hora_entrada'] ?>"
            onchange="calcularDesplazamientos()"
            class="w-full border rounded text-xs">
    </td>

    <!-- 14. HORA SALIDA -->
    <td class="p-1">
        <input type="time"
            name="servicios[<?= $idFila ?>][salida]"
            id="hora_salida_<?= $idFila ?>"
            value="<?= $s['hora_salida'] ?>"
            onchange="calcularDesplazamientos()"
            class="w-full border rounded text-xs">
    </td>

    <!-- 15. DESPLAZAMIENTO -->
    <td class="p-1 bg-orange-50 text-center align-middle">
        <span id="desplazamiento_<?= $idFila ?>"
            class="text-[10px] font-bold text-gray-400">-</span>
    </td>

    <!-- 16. ESTADO/CALIFICACIÓN -->
    <td class="p-1">
        <select name="servicios[<?= $idFila ?>][id_estado]"
            class="w-full text-[9px] border mb-1">
            <?php foreach ($listaEstados as $e): ?>
                <option value="<?= $e['id_estado'] ?>"
                    <?= $e['id_estado'] == $s['id_estado'] ? 'selected' : '' ?>>
                    <?= $e['nombre_estado'] ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="servicios[<?= $idFila ?>][id_calif]"
            class="w-full text-[9px] border">
            <?php foreach ($listaCalifs as $c): ?>
                <option value="<?= $c['id_calificacion'] ?>"
                    <?= $c['id_calificacion'] == $s['id_calif'] ? 'selected' : '' ?>>
                    <?= $c['nombre_calificacion'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </td>

</tr>