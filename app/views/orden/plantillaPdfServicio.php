<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Servicio #<?= $datosOrden['numero_remision'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Reglas especiales para impresión */
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .page-break { page-break-before: always; }
            .no-break { page-break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-white text-gray-800 font-sans p-8">

    <div class="flex justify-between items-center border-b-4 border-indigo-600 pb-4 mb-6">
        <div>
            <h1 class="text-3xl font-black text-indigo-700 tracking-tight">INEES</h1>
            <p class="text-sm text-gray-500 font-bold uppercase tracking-wider">Reporte Técnico de Servicio</p>
        </div>
        <div class="text-right">
            <p class="text-2xl font-bold text-gray-800">Remisión <span class="text-indigo-600">#<?= $datosOrden['numero_remision'] ?></span></p>
            <p class="text-gray-500 text-sm">Fecha: <?= date('d/m/Y', strtotime($datosOrden['fecha_visita'])) ?></p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-6 no-break">
        <div class="bg-gray-50 border border-gray-200 p-4 rounded-lg">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2">Datos del Cliente</h3>
            <p class="font-bold text-lg text-gray-800"><?= $datosOrden['nombre_cliente'] ?></p>
            <p class="text-sm text-gray-600"><i class="fas fa-map-marker-alt"></i> <?= $datosOrden['nombre_punto'] ?></p>
            <p class="text-xs text-gray-500 mt-1">Delegación: <span class="font-semibold text-gray-700"><?= $datosOrden['delegacion'] ?></span></p>
            
            <div class="mt-3 pt-3 border-t border-gray-200">
                <p class="text-xs text-gray-500 uppercase font-bold">Contacto en Sitio</p>
                <p class="text-sm text-gray-700 font-medium"><?= !empty($datosOrden['administrador_punto']) ? $datosOrden['administrador_punto'] : 'No registrado' ?> - ☎️ <?= !empty($datosOrden['celular_encargado']) ? $datosOrden['celular_encargado'] : 'N/A' ?></p>
            </div>
        </div>
        
        <div class="bg-gray-50 border border-gray-200 p-4 rounded-lg">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2">Ejecución</h3>
            <p class="font-bold text-gray-800">Técnico: <span class="text-indigo-600"><?= $datosOrden['nombre_tecnico'] ?></span></p>
            <div class="flex space-x-4 mt-2 text-sm text-gray-600">
                <p>Entrada: <span class="font-bold"><?= $datosOrden['hora_entrada'] ?></span></p>
                <p>Salida: <span class="font-bold"><?= $datosOrden['hora_salida'] ?></span></p>
            </div>
            <p class="text-xs text-gray-500 mt-1">Duración Total: <span class="font-bold text-gray-700"><?= $datosOrden['tiempo_servicio'] ?> hrs</span></p>
            
            <div class="mt-3 pt-3 border-t border-gray-200">
                <p class="text-xs text-gray-500 uppercase font-bold mb-1">Calificación Cliente</p>
                <p class="font-bold text-indigo-700 text-sm"><?= $datosOrden['nombre_calificacion'] ?: 'Pendiente' ?></p>
            </div>
        </div>
    </div>

    <div class="border border-gray-200 rounded-lg overflow-hidden mb-6 no-break">
        <div class="bg-gray-100 px-4 py-2 border-b border-gray-200">
            <h3 class="font-bold text-gray-700 text-sm uppercase">Detalles de Equipos e Inventario</h3>
        </div>
        <div class="p-4 grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-500 text-xs uppercase font-bold">Device ID / N° Máquina:</p>
                <p class="font-bold text-gray-800 mb-2"><?= $datosOrden['device_id'] ?: 'N/A' ?> <span class="text-gray-400 font-normal">|</span> <?= $datosOrden['numero_maquina'] ?: 'N/A' ?></p>
                
                <p class="text-gray-500 text-xs uppercase font-bold">Tipo / Serial Máquina:</p>
                <p class="font-bold text-gray-800 mb-2"><?= $datosOrden['nombre_tipo_maquina'] ?: 'No especificado' ?> <span class="text-gray-400 font-normal">|</span> Serial: <?= $datosOrden['serial_maquina'] ?: 'N/A' ?></p>
            </div>
            <div>
                <p class="text-gray-500 text-xs uppercase font-bold">Serial Router:</p>
                <p class="font-bold text-gray-800 mb-2"><?= $datosOrden['serial_router'] ?: 'N/A' ?></p>
                
                <p class="text-gray-500 text-xs uppercase font-bold">Serial UPS:</p>
                <p class="font-bold text-gray-800 mb-2"><?= $datosOrden['serial_ups'] ?: 'N/A' ?></p>
            </div>
        </div>
    </div>

    <div class="border border-gray-200 rounded-lg overflow-hidden mb-6 no-break">
        <div class="bg-gray-100 px-4 py-2 border-b border-gray-200 flex justify-between">
            <h3 class="font-bold text-gray-700 text-sm uppercase">Diagnóstico y Mantenimiento</h3>
            <span class="font-bold text-indigo-700 text-sm"><?= $datosOrden['tipo_servicio'] ?></span>
        </div>
        <div class="p-4 grid grid-cols-2 gap-4 text-sm bg-blue-50">
            <div>
                <p class="text-gray-500 text-xs uppercase font-bold">Estado Inicial (Diagnóstico):</p>
                <p class="font-bold text-gray-800 text-base"><?= $datosOrden['estado_inicial'] ?: 'No registrado' ?></p>
            </div>
            <div>
                <p class="text-gray-500 text-xs uppercase font-bold">Estado Final de Operación:</p>
                <p class="font-bold text-green-700 text-base"><?= $datosOrden['estado_maquina'] ?></p>
            </div>
        </div>
        <div class="p-4 border-t border-gray-200 bg-white">
            <h4 class="text-xs font-bold text-gray-500 uppercase mb-1">Actividades Realizadas</h4>
            <p class="text-sm text-gray-700 whitespace-pre-line"><?= !empty($datosOrden['observaciones']) ? $datosOrden['observaciones'] : 'Sin observaciones registradas.' ?></p>
        </div>
        
        <?php if (!empty($datosOrden['pendientes'])): ?>
        <div class="p-4 border-t border-gray-200 bg-orange-50">
            <h4 class="text-xs font-bold text-orange-600 uppercase mb-1"><i class="fas fa-exclamation-circle"></i> Pendientes / Recomendaciones</h4>
            <p class="text-sm text-gray-800 whitespace-pre-line"><?= $datosOrden['pendientes'] ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($novedades) || !empty($datosOrden['detalle_novedad'])): ?>
        <div class="p-4 border-t border-gray-200 bg-red-50 border-l-4 border-red-500">
            <h4 class="text-xs font-bold text-red-700 uppercase mb-2">⚠️ Novedades Reportadas:</h4>
            
            <?php if (!empty($novedades)): ?>
                <ul class="list-disc list-inside text-sm text-red-700 font-medium mb-2">
                    <?php foreach ($novedades as $nov): ?>
                        <li><?= htmlspecialchars($nov['nombre_novedad']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (!empty($datosOrden['detalle_novedad'])): ?>
                <p class="text-sm text-red-600 font-medium mt-1">
                    <strong>Detalle:</strong> <?= htmlspecialchars($datosOrden['detalle_novedad']) ?>
                </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($evidencias)): ?>
        <div class="page-break"></div> 
        <h2 class="text-xl font-bold text-gray-800 border-b-2 border-indigo-600 pb-2 mb-4">Evidencia Fotográfica</h2>
        
        <?php 
        // Agrupamos las fotos por su tipo
        $fotosAntes = array_filter($evidencias, fn($e) => $e['tipo_evidencia'] === 'antes');
        $fotosDurante = array_filter($evidencias, fn($e) => $e['tipo_evidencia'] === 'componentes');
        $fotosDespues = array_filter($evidencias, fn($e) => $e['tipo_evidencia'] === 'despues');
        
        $grupos = [
            'Antes del Servicio' => $fotosAntes,
            'Componentes / Durante' => $fotosDurante,
            'Después del Servicio' => $fotosDespues
        ];
        ?>

        <?php foreach ($grupos as $tituloGrupo => $fotos): ?>
            <?php if (count($fotos) > 0): ?>
                <div class="mb-6 no-break">
                    <h3 class="font-bold text-gray-600 uppercase text-sm mb-3 bg-gray-100 py-1 px-2 rounded border-l-4 border-indigo-400"><?= $tituloGrupo ?></h3>
                    <div class="grid grid-cols-3 gap-4">
                        <?php foreach ($fotos as $foto): ?>
                            <?php 
                                // 1. Sacamos la ruta de la BD y nos aseguramos de que empiece en 'uploads/'
                                $rutaEnBD = $foto['ruta_archivo'];
                                $pos = strpos($rutaEnBD, 'uploads/');
                                $rutaLimpia = ($pos !== false) ? substr($rutaEnBD, $pos) : ltrim($rutaEnBD, '/');

                                // 2. Armamos la ruta física real (subimos 2 niveles desde app/views/orden)
                                $rutaFisica = realpath(__DIR__ . '/../../' . $rutaLimpia);
                                
                                $imgSrc = '';
                                
                                // 3. Validamos que el archivo exista y NO sea una carpeta
                                if ($rutaFisica && file_exists($rutaFisica) && !is_dir($rutaFisica)) {
                                    $extension = strtolower(pathinfo($rutaFisica, PATHINFO_EXTENSION));
                                    $mime = ($extension === 'jpg') ? 'jpeg' : $extension;
                                    
                                    $data = file_get_contents($rutaFisica);
                                    $imgSrc = 'data:image/' . $mime . ';base64,' . base64_encode($data);
                                } else {
                                    // Imagen gris por defecto si falla
                                    $imgSrc = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='; 
                                }
                            ?>
                            <div class="border border-gray-300 p-1 rounded bg-white shadow-sm flex flex-col items-center justify-center">
                                <img src="<?= $imgSrc ?>" alt="Evidencia" class="w-full h-48 object-cover rounded">
                                
                                <?php if(!$rutaFisica || !file_exists($rutaFisica) || is_dir($rutaFisica)): ?>
                                    <span style="font-size: 8px; color: red; word-break: break-all; margin-top: 5px;">
                                        Error: No encontrada en <?php echo __DIR__ . '/../../' . $rutaLimpia; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

    <?php else: ?>
        <div class="bg-gray-50 border border-gray-200 p-4 rounded-lg text-center mt-6">
            <p class="text-gray-500 text-sm"><i class="fas fa-camera-slash mr-2"></i> No se adjuntaron evidencias fotográficas para este servicio.</p>
        </div>
    <?php endif; ?>

</body>
</html>