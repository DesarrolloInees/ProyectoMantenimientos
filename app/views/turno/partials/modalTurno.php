<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

/**
 * Partial del módulo "Marcar Entrada" (solo rol técnico).
 * Se incluye desde app/views/plantillaVista.php, así el modal queda
 * disponible en TODAS las pantallas del técnico (la tarjeta del inicio
 * y el botón del navbar lo abren con Turno.abrirModal()).
 *
 * $turnoHoy es opcional: si la vista que se está pintando lo trae (Inicio),
 * el modal y la tarjeta arrancan con los datos del día; si no, el JS los
 * consulta por AJAX.
 */
$turnoInicial = isset($turnoHoy) && $turnoHoy ? $turnoHoy : null;
$idCarpeta = 'modalTurno';
?>

<div id="<?= $idCarpeta ?>"
    class="fixed inset-0 bg-black bg-opacity-60 hidden z-[10000] justify-center items-center p-4 overflow-y-auto"
    role="dialog" aria-modal="true" aria-labelledby="turnoTitulo">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden my-8">

        <!-- Encabezado -->
        <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-5 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3 text-white">
                <div class="bg-white/20 p-2 rounded-full">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div>
                    <h3 id="turnoTitulo" class="font-bold leading-tight">Marcar entrada</h3>
                    <p class="text-emerald-100 text-[11px]">Registra el día y la hora en que entraste</p>
                </div>
            </div>
            <button type="button" onclick="Turno.cerrarModal()"
                class="text-white/80 hover:text-white bg-white/10 hover:bg-white/20 w-9 h-9 rounded-full transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Cuerpo -->
        <form id="turnoFormulario" class="p-5 space-y-4" novalidate>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="turnoFecha" class="block text-xs font-bold text-gray-600 uppercase mb-1">
                        Día
                    </label>
                    <input type="date" id="turnoFecha" name="fecha" required
                        class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label for="turnoHora" class="block text-xs font-bold text-gray-600 uppercase mb-1">
                        Hora de entrada
                        <span class="text-emerald-600 font-normal normal-case">(24 h)</span>
                    </label>
                    <input type="text" id="turnoHora" name="hora_entrada" required
                        inputmode="numeric" pattern="[0-9]*" maxlength="5" autocomplete="off"
                        placeholder="1300"
                        class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-center text-lg font-bold tracking-widest outline-none focus:border-emerald-500">
                    <p class="text-[10px] text-gray-400 mt-1 text-center">
                        Escribe solo los números: <b>1300</b> = 13:00
                    </p>
                </div>
            </div>

            <div>
                <label for="turnoNovedad" class="block text-xs font-bold text-gray-600 uppercase mb-1">
                    Novedad <span class="text-gray-400 font-normal normal-case">(opcional)</span>
                </label>
                <textarea id="turnoNovedad" name="novedad" rows="3" maxlength="1000"
                    placeholder="Ej: entré 20 minutos tarde porque tuve una novedad en la ruta..."
                    class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm outline-none focus:border-emerald-500 resize-none"></textarea>
            </div>

            <!-- Ubicación -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 flex items-start gap-2">
                <i class="fas fa-map-marker-alt text-emerald-600 mt-0.5"></i>
                <div class="flex-1">
                    <p class="text-[11px] font-bold text-gray-600 uppercase">Ubicación</p>
                    <p id="turnoGeoEstado" class="text-xs text-gray-500">Obteniendo ubicación...</p>
                </div>
                <button type="button" onclick="Turno.capturarUbicacion(true)"
                    class="text-[11px] text-emerald-700 hover:text-emerald-800 font-bold underline">
                    Reintentar
                </button>
            </div>

            <input type="hidden" id="turnoLatitud" name="latitud" value="">
            <input type="hidden" id="turnoLongitud" name="longitud" value="">

            <div id="turnoAlerta" class="hidden rounded-lg px-3 py-2 text-sm"></div>

            <div class="flex gap-2 pt-1">
                <button type="button" onclick="Turno.cerrarModal()"
                    class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 rounded-lg transition text-sm">
                    Cancelar
                </button>
                <button type="submit" id="turnoBtnGuardar"
                    class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 rounded-lg transition text-sm shadow flex items-center justify-center gap-2">
                    <i class="fas fa-check"></i> Guardar entrada
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    window.TurnoConfig = {
        urlGuardar: '<?= BASE_URL ?>index.php?pagina=turno&accion=guardar',
        urlEstado: '<?= BASE_URL ?>index.php?pagina=turno&accion=estadoHoy',
        hoy: '<?= date('Y-m-d') ?>',
        turnoInicial: <?= json_encode($turnoInicial, JSON_UNESCAPED_UNICODE) ?>
    };
</script>
