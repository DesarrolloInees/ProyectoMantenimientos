<!-- ============================================== -->
<!-- PARTIAL: FILA DE SERVICIO                    -->
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
                    data-full="<?= htmlspecialchars($c['nombre_cliente'], ENT_QUOTES) ?>"
                    <?= $c['id_cliente'] == $s['id_cliente'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars(substr($c['nombre_cliente'], 0, 20), ENT_QUOTES) ?>...
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
                data-full="<?= htmlspecialchars($s['nombre_punto'], ENT_QUOTES) ?>" selected>
                <?= htmlspecialchars(substr($s['nombre_punto'], 0, 20), ENT_QUOTES) ?>...
            </option>
        </select>
        <!-- Delegación visible en tabla -->
        <div id="td_delegacion_<?= $idFila ?>" class="text-[9px] text-gray-400 mt-0.5">
            <?= htmlspecialchars($s['delegacion'] ?? 'Sin asignar', ENT_QUOTES) ?>
        </div>
    </td>

    <!-- 3. FECHA -->
    <td class="p-1">
        <input type="date"
            name="servicios[<?= $idFila ?>][fecha_individual]"
            value="<?= $s['fecha_visita'] ?>"
            class="w-full border rounded text-[10px] p-1">
    </td>

    <!-- 4. TÉCNICO -->
    <td class="p-1 bg-indigo-50">
        <select name="servicios[<?= $idFila ?>][id_tecnico]"
            id="sel_tecnico_<?= $idFila ?>"
            class="w-full border rounded p-1 font-bold text-indigo-800 text-xs"
            onchange="calcularDesplazamientos(); cargarRemisiones(<?= $idFila ?>, this.value)">
            <?php foreach ($listaTecnicos as $t): ?>
                <option value="<?= $t['id_tecnico'] ?>"
                    <?= $t['id_tecnico'] == $s['id_tecnico'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($t['nombre_tecnico'], ENT_QUOTES) ?>
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
                    <?= htmlspecialchars($m['nombre_completo'], ENT_QUOTES) ?>
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
                    <?= htmlspecialchars($mod['nombre_modalidad'], ENT_QUOTES) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </td>

    <!-- 7. MÁQUINA -->
    <td class="p-1 bg-blue-50">
        <select id="sel_maq_<?= $idFila ?>"
            name="servicios[<?= $idFila ?>][id_maquina]"
            onchange="actualizarTipoMaquina(<?= $idFila ?>); actualizarTarifa(<?= $idFila ?>)"
            class="w-full border rounded p-1 font-mono text-blue-600 font-bold text-xs">
            <option value="<?= $s['id_maquina'] ?>"
                data-tipo="<?= htmlspecialchars($s['nombre_tipo_maquina'], ENT_QUOTES) ?>"
                data-idtipomaquina="<?= $s['id_tipo_maquina'] ?>"
                selected>
                <?= htmlspecialchars($s['device_id'], ENT_QUOTES) ?>
            </option>
        </select>
        <div id="td_tipomaq_<?= $idFila ?>" class="text-[9px] text-gray-400 mt-0.5">
            <?= htmlspecialchars($s['nombre_tipo_maquina'], ENT_QUOTES) ?>
        </div>
    </td>

    <!-- 8. OBSERVACIONES -->
    <td class="p-1 bg-blue-50 border-r-4 border-blue-200">
        <textarea name="servicios[<?= $idFila ?>][obs]"
            rows="3"
            class="w-full border rounded text-xs p-1 shadow-inner focus:bg-white transition"><?= ($s['que_se_hizo']) ?></textarea>
    </td>

    <!-- 9. NOVEDAD -->
    <td class="border p-1 text-center align-middle relative">
    <?php
    // 🔥 CORRECCIÓN: Usamos ids_novedades en lugar del viejo id_tipo_novedad
    $tieneNovedad = (isset($s['tiene_novedad']) && $s['tiene_novedad'] == 1) ||
                    (!empty($s['ids_novedades']));
                    
    // Mantenemos tus colores y animación intactos
    $claseIcono = $tieneNovedad ? 'text-red-600 animate-pulse' : 'text-gray-300 hover:text-yellow-500';
    
    // Mejoramos el tooltip para que muestre el nombre de las novedades si existen
    $tooltipTexto = ($tieneNovedad && !empty($s['nombres_novedades'])) 
                    ? $s['nombres_novedades'] 
                    : ($tieneNovedad ? 'Tiene Novedad Reportada' : 'Reportar Novedad');
    ?>
    
    <button type="button"
        onclick="abrirModalNovedad(<?= $idFila ?>)"
        id="btn-nov-<?= $idFila ?>"
        class="text-lg transition-colors duration-200 <?= $claseIcono ?>"
        title="<?= $tooltipTexto ?>">
        <i class="fas fa-exclamation-triangle"></i>
    </button>
    
    <input type="hidden" id="hdn-tiene-<?= $idFila ?>" name="servicios[<?= $idFila ?>][tiene_novedad]" value="<?= $tieneNovedad ? 1 : 0 ?>">
    
    <input type="hidden" id="hdn-tipo-<?= $idFila ?>" name="servicios[<?= $idFila ?>][id_tipo_novedad]" value="<?= $s['ids_novedades'] ?? '' ?>">
</td>

    <!-- 10. VALOR -->
    <td class="p-1 bg-green-50">
        <input type="text"
            name="servicios[<?= $idFila ?>][valor]"
            id="input_valor_<?= $idFila ?>"
            value="<?= number_format($s['valor_servicio'], 0, ',', '.') ?>"
            class="w-full border rounded text-right font-bold text-green-700 text-sm p-1">

        <!-- Viáticos: visible solo si aplica, editable para ajuste manual -->
        <?php $valorViaticos = floatval($s['valor_viaticos'] ?? 0); ?>
        <input type="hidden"
            id="viaticos_<?= $idFila ?>"
            name="servicios[<?= $idFila ?>][valor_viaticos]"
            value="<?= $valorViaticos ?>">

        <?php if ($valorViaticos > 0): ?>
            <div class="text-[9px] text-orange-600 font-bold mt-0.5 flex items-center gap-1"
                title="Recargo por desplazamiento interurbano">
                <i class="fas fa-road"></i>
                <span>+<?= number_format($valorViaticos, 0, ',', '.') ?></span>
            </div>
        <?php endif; ?>
    </td>

    <!-- 11. REPUESTOS -->
    <td class="p-1 bg-gray-50 text-center align-middle relative">
        <?php
        $jsonRepuestos   = $s['repuestos_json'] ?? '[]';
        $textoRepuestos  = $s['repuestos_texto'] ?? '';
        $arrayRepuestos  = json_decode($jsonRepuestos, true) ?: [];
        $cantidadRepuestos = 0;
        foreach ($arrayRepuestos as $rep) {
            $cantidadRepuestos += isset($rep['cantidad']) ? intval($rep['cantidad']) : 1;
        }

        // Leer sugerencias del técnico
        $jsonSugeridos = $s['repuestos_tecnico'] ?? '[]';
        if(empty($jsonSugeridos)) $jsonSugeridos = '[]';
        $arraySugeridos = json_decode($jsonSugeridos, true) ?: [];
        $haySugerencias = count($arraySugeridos) > 0;
        ?>

        <div class="relative w-full">
            <button type="button"
                onclick="abrirModalRepuestos(<?= $idFila ?>)"
                class="<?= $cantidadRepuestos > 0 ? 'bg-blue-100 text-blue-800 border-blue-300' : 'bg-white border-gray-300 text-gray-400' ?> border hover:bg-blue-50 text-[10px] px-2 py-1 rounded w-full shadow-sm transition flex items-center justify-center gap-1">
                <i class="fas fa-tools text-blue-500"></i>
                <span id="btn_texto_<?= $idFila ?>">
                    <?= $cantidadRepuestos > 0 ? $cantidadRepuestos . ' Items' : 'Sin Rep.' ?>
                </span>
            </button>

            <?php if($haySugerencias): ?>
                <div class="absolute -top-2 -right-2 bg-orange-500 text-white text-[8px] font-bold px-1.5 py-0.5 rounded-full shadow-sm border border-white animate-pulse" title="El técnico reportó repuestos desde la app">
                    <i class="fas fa-bell"></i>
                </div>
            <?php endif; ?>
        </div>

        <input type="hidden"
            name="servicios[<?= $idFila ?>][json_repuestos]"
            id="input_json_<?= $idFila ?>"
            value='<?= htmlspecialchars($jsonRepuestos, ENT_QUOTES, 'UTF-8') ?>'>

        <input type="hidden"
            id="input_db_<?= $idFila ?>"
            value='<?= htmlspecialchars($textoRepuestos, ENT_QUOTES, 'UTF-8') ?>'>

        <input type="hidden"
            id="input_sugerido_<?= $idFila ?>"
            value='<?= htmlspecialchars($jsonSugeridos, ENT_QUOTES, 'UTF-8') ?>'>
    </td>

    <!-- 12. REMISIÓN -->
    <td class="p-1">
        <select name="servicios[<?= $idFila ?>][remision]"
            id="sel_remision_<?= $idFila ?>"
            class="w-full border rounded p-1 text-[10px] font-mono text-gray-700"
            data-remision-original="<?= htmlspecialchars($s['numero_remision'] ?? '', ENT_QUOTES) ?>"
            title="Remisión asignada al técnico">
            <?php if (!empty($s['numero_remision'])): ?>
                <option value="<?= htmlspecialchars($s['numero_remision'], ENT_QUOTES) ?>" selected>
                    <?= htmlspecialchars($s['numero_remision'], ENT_QUOTES) ?> ✓ (actual)
                </option>
            <?php else: ?>
                <option value="">- Sin remisión -</option>
            <?php endif; ?>
            <!-- Las disponibles se cargan por AJAX al iniciar y al cambiar técnico -->
        </select>
        <?php if (empty($s['numero_remision'])): ?>
            <div class="text-[8px] text-red-400 mt-0.5">Sin asignar</div>
        <?php endif; ?>
    </td>

    <!-- 13. HORA ENTRADA -->
    <td class="p-1">
        <input type="time"
            name="servicios[<?= $idFila ?>][entrada]"
            id="hora_entrada_<?= $idFila ?>"
            value="<?= $s['hora_entrada'] ?>"
            onchange="calcularDesplazamientos()"
            class="w-full border rounded text-xs p-1">
    </td>

    <!-- 14. HORA SALIDA -->
    <td class="p-1">
        <input type="time"
            name="servicios[<?= $idFila ?>][salida]"
            id="hora_salida_<?= $idFila ?>"
            value="<?= $s['hora_salida'] ?>"
            onchange="calcularDesplazamientos()"
            class="w-full border rounded text-xs p-1">
    </td>

    <!-- 15. DESPLAZAMIENTO -->
    <td class="p-1 bg-orange-50 text-center align-middle">
        <span id="desplazamiento_<?= $idFila ?>"
            class="text-[10px] font-bold text-gray-400">-</span>
    </td>

    <!-- 16. ESTADO/CALIFICACIÓN -->
    <td class="p-1">
        <select name="servicios[<?= $idFila ?>][id_estado]"
            class="w-full text-[9px] border mb-1 rounded p-0.5">
            <?php foreach ($listaEstados as $e): ?>
                <option value="<?= $e['id_estado'] ?>"
                    <?= $e['id_estado'] == $s['id_estado'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($e['nombre_estado'], ENT_QUOTES) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="servicios[<?= $idFila ?>][id_calif]"
            class="w-full text-[9px] border rounded p-0.5">
            <?php foreach ($listaCalifs as $c): ?>
                <option value="<?= $c['id_calificacion'] ?>"
                    <?= $c['id_calificacion'] == $s['id_calif'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['nombre_calificacion'], ENT_QUOTES) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </td>

</tr>