<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.tailwindcss.com"></script>

<style>
    body {
        background-color: #f1f5f9;
        padding-bottom: 30px;
    }
</style>

<!-- HEADER FIJO MÓVIL -->
<div class="bg-blue-800 text-white p-4 shadow-md sticky top-0 z-40 flex items-center justify-between gap-3">
    <div class="flex items-center gap-3">
        <button onclick="window.history.back();"
            class="text-white bg-blue-700 hover:bg-blue-600 p-2 rounded-full w-10 h-10 flex items-center justify-center transition">
            <i class="fas fa-arrow-left"></i>
        </button>
        <div>
            <h1 class="font-bold text-lg leading-tight">Mis Servicios</h1>
            <p class="text-blue-200 text-xs">Historial de tiempos registrados</p>
        </div>
    </div>
    <!-- BOTÓN PARA IR A CREAR UN NUEVO SERVICIO -->
    <a href="index.php?pagina=cronometroCrear"
        class="bg-green-500 hover:bg-green-600 text-white pl-3 pr-4 py-2 rounded-full flex items-center gap-2 transition shadow font-bold text-sm"
        title="Iniciar Nuevo Servicio">
        <i class="fas fa-plus"></i>
        <span>Nuevo</span>
    </a>
</div>

<div class="max-w-lg mx-auto p-3 mt-2 space-y-4">
    <?php if (empty($historialServicios)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center mt-10">
            <i class="fas fa-stopwatch text-gray-300 text-5xl mb-3"></i>
            <h2 class="text-gray-500 font-bold text-lg">Sin servicios</h2>
            <p class="text-gray-400 text-sm mt-1">Aún no has registrado tiempos de servicio.</p>
        </div>
    <?php else: ?>
        <?php foreach ($historialServicios as $srv): ?>
            <?php
                // Definir colores según el estado y el rendimiento de tiempo
                $estadoTexto = htmlspecialchars($srv['estado_actual']);
                $enProgreso = ($estadoTexto === 'En Progreso');

                if ($enProgreso) {
                    $badgeColor = 'bg-amber-100 text-amber-700 border-amber-200';
                    $iconoEstado = 'fa-spinner fa-spin';
                    $cardRing = 'ring-1 ring-amber-200';
                } else {
                    $badgeColor = 'bg-gray-100 text-gray-600 border-gray-200';
                    $iconoEstado = 'fa-check-circle';
                    $cardRing = 'ring-1 ring-gray-100';
                }

                // Cálculo de eficiencia solo si está finalizado
                $rendimientoClase = 'text-gray-700';
                $rendimientoBg = 'bg-gray-50';
                $rendimientoIcono = '';
                $atiempo = true;

                if ($estadoTexto === 'Finalizado' && $srv['duracion_real_minutos'] !== null) {
                    $atiempo = ($srv['duracion_real_minutos'] <= $srv['tiempo_estimado_minutos']);
                    if ($atiempo) {
                        $rendimientoClase = 'text-green-600';
                        $rendimientoBg = 'bg-green-50';
                        $rendimientoIcono = '<i class="fas fa-tachometer-alt mr-1"></i>';
                    } else {
                        $rendimientoClase = 'text-red-500';
                        $rendimientoBg = 'bg-red-50';
                        $rendimientoIcono = '<i class="fas fa-exclamation-triangle mr-1"></i>';
                    }
                }
            ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 <?= $cardRing ?> overflow-hidden">

                <!-- Header de la tarjeta -->
                <div class="bg-gray-50 px-4 py-2.5 border-b border-gray-100 flex justify-between items-center">
                    <span class="text-xs font-bold text-gray-500">
                        <i class="far fa-calendar-alt mr-1 text-blue-600"></i>
                        <?= date('d/m/Y', strtotime($srv['fecha_servicio'])) ?>
                    </span>
                    <span class="px-2.5 py-1 rounded-full border text-[10px] font-bold uppercase flex items-center gap-1 <?= $badgeColor ?>">
                        <i class="fas <?= $iconoEstado ?>"></i> <?= $estadoTexto ?>
                    </span>
                </div>

                <div class="p-4 space-y-3">
                    <!-- Ubicación -->
                    <div>
                        <span class="block text-[10px] font-bold text-blue-600 uppercase leading-none">
                            <?= htmlspecialchars($srv['nombre_cliente']) ?>
                        </span>
                        <h3 class="font-bold text-gray-800 text-sm mt-1 flex items-start gap-1.5">
                            <i class="fas fa-map-marker-alt text-red-500 mt-0.5"></i>
                            <span><?= htmlspecialchars($srv['nombre_punto']) ?></span>
                        </h3>
                    </div>

                    <!-- Tipo de Servicio -->
                    <div class="bg-blue-50 p-2 rounded-lg border border-blue-100 flex items-center gap-2">
                        <i class="fas fa-tools text-blue-500"></i>
                        <span class="text-xs font-bold text-blue-800 uppercase"><?= htmlspecialchars($srv['tipo_mantenimiento']) ?></span>
                    </div>

                    <!-- Métricas de horario -->
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="bg-gray-50 p-2.5 rounded-lg border border-gray-100">
                            <span class="block text-gray-400 uppercase font-bold text-[9px]">Inicio</span>
                            <span class="text-gray-700 font-semibold">
                                <i class="far fa-clock text-gray-400 mr-1"></i> <?= date('h:i A', strtotime($srv['hora_inicio'])) ?>
                            </span>
                        </div>
                        <div class="bg-gray-50 p-2.5 rounded-lg border border-gray-100">
                            <span class="block text-gray-400 uppercase font-bold text-[9px]">Fin</span>
                            <span class="text-gray-700 font-semibold">
                                <?php if ($srv['hora_fin']): ?>
                                    <i class="fas fa-flag-checkered text-gray-400 mr-1"></i> <?= date('h:i A', strtotime($srv['hora_fin'])) ?>
                                <?php else: ?>
                                    <span class="text-amber-600 inline-flex items-center gap-1">
                                        <span class="relative flex h-2 w-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                                        </span>
                                        Corriendo...
                                    </span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Rendimiento de Tiempo (Solo si está finalizado) -->
                    <?php if ($estadoTexto === 'Finalizado'): ?>
                    <div class="<?= $rendimientoBg ?> p-3 rounded-lg border border-gray-100 flex justify-between items-center">
                        <div>
                            <span class="block text-gray-400 uppercase font-bold text-[9px] mb-0.5">Meta: <?= $srv['tiempo_estimado_minutos'] ?> min</span>
                            <span class="text-sm font-bold <?= $rendimientoClase ?>">
                                <?= $rendimientoIcono ?> Real: <?= $srv['duracion_real_minutos'] ?> min
                            </span>
                        </div>
                        <?php if (!$atiempo): ?>
                            <span class="text-[10px] font-bold text-red-500 uppercase bg-red-100 px-2.5 py-1 rounded-full">Retrasado</span>
                        <?php else: ?>
                            <span class="text-[10px] font-bold text-green-600 uppercase bg-green-100 px-2.5 py-1 rounded-full">A tiempo</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>