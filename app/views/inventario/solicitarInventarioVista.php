<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    /* Estilos Select2 */
    .select2-container .select2-selection--single {
        height: 42px !important;
        padding-top: 6px !important;
        border-color: #d1d5db !important;
        border-radius: 0.5rem !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        top: 6px !important;
    }
</style>

<div class="w-full px-4 md:px-6">
    <div class="bg-white p-6 md:p-8 rounded-xl shadow-lg border border-gray-200 max-w-5xl mx-auto">

        <div class="mb-6 border-b pb-4 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-clipboard-list text-purple-600 mr-3"></i> Solicitud de Inventario / Repuestos
                </h1>
                <p class="text-gray-500 mt-1 text-sm">Registra y solicita repuestos requeridos directamente a logística y almacén.</p>
            </div>
            <div class="flex items-center gap-2">
                <?php if (!isset($_SESSION['nivel_acceso']) || (int)$_SESSION['nivel_acceso'] !== 3): ?>
                    <a href="<?= BASE_URL ?>solicitarinventario" class="text-xs bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold px-3 py-2 rounded-lg transition flex items-center gap-1 border border-indigo-200">
                        <i class="fas fa-list"></i> Ver Tablero
                    </a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>inicio" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times text-xl"></i>
                </a>
            </div>
        </div>

        <?php if (!empty($mensajeExito)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center justify-between">
                <div>
                    <i class="fas fa-check-circle mr-2 text-lg"></i> <?= $mensajeExito ?>
                </div>
                <a href="<?= BASE_URL ?><?= (isset($_SESSION['nivel_acceso']) && (int)$_SESSION['nivel_acceso'] !== 3) ? 'solicitarinventario' : 'inicio' ?>" 
                   class="text-xs bg-green-200 hover:bg-green-300 text-green-800 font-bold px-3 py-1.5 rounded-lg transition">
                    <?= (isset($_SESSION['nivel_acceso']) && (int)$_SESSION['nivel_acceso'] !== 3) ? 'Ir al Tablero' : 'Volver al Inicio' ?>
                </a>
            </div>
        <?php endif; ?>

        <?php if (!empty($errores)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                <p class="font-bold">Hubo errores en la solicitud:</p>
                <ul class="list-disc list-inside ml-4 text-sm mt-1">
                    <?php foreach ($errores as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>solicitarinventario" method="POST" id="formSolicitarInventario">
            <input type="hidden" name="accion" value="crearSolicitud">

            <!-- Datos del Solicitante -->
            <div class="bg-purple-50 p-4 rounded-lg mb-6 border border-purple-100 shadow-sm">
                <label class="block text-sm font-bold text-purple-900 mb-1">
                    <i class="fas fa-user-check text-purple-600 mr-1"></i> Técnico / Usuario Solicitante
                </label>
                <p class="text-xs text-purple-600 mb-2">Usuario activo en la sesión del sistema (No modificable).</p>

                <div class="w-full bg-white border border-purple-200 text-purple-900 font-bold text-base rounded-lg px-4 py-3 cursor-not-allowed select-none shadow-inner flex items-center justify-between">
                    <span><i class="fas fa-id-badge text-purple-500 mr-2"></i> <?= htmlspecialchars($nombreUsuarioLogueado ?? 'Usuario del Sistema') ?></span>
                    <span class="text-xs bg-purple-100 text-purple-700 px-2.5 py-1 rounded-full uppercase tracking-wider font-semibold">Sesión Activa</span>
                </div>
            </div>

            <!-- Correos de Notificación Corporativos -->
            <div class="bg-gray-50 p-4 rounded-lg mb-6 border border-gray-200 shadow-sm">
                <label class="block text-sm font-bold text-gray-700 mb-1">
                    <i class="fas fa-envelope text-indigo-500 mr-1"></i> Correos de Notificación Corporativos
                </label>
                <p class="text-xs text-gray-500 mb-2">Al registrar la solicitud, se enviará una notificación automática por correo electrónico a las siguientes direcciones:</p>
                <input type="text" readonly disabled
                    value="almacen@inees.co, administrativo@inees.co, desarrollo@inees.co, desarrollo2@inees.co, operaciones@inees.co, auxadministrativo@inees.co"
                    class="w-full bg-gray-100 border border-gray-300 text-gray-700 text-xs font-mono rounded-lg px-3 py-2.5 cursor-not-allowed select-none">
            </div>

            <!-- Detalle de Repuestos Solicitados -->
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">
                    <i class="fas fa-boxes-stacked text-indigo-500 mr-1"></i> Repuestos o Insumos Solicitados
                </label>

                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-xs text-gray-500 uppercase border-b bg-gray-50">
                            <th class="py-2.5 px-3 w-2/3 font-semibold">Repuesto / Insumo</th>
                            <th class="py-2.5 px-3 w-28 text-center font-semibold">Cantidad</th>
                            <th class="py-2.5 px-3 w-10 text-center"></th>
                        </tr>
                    </thead>
                    <tbody id="contenedor-filas">
                    </tbody>
                </table>
            </div>

            <div class="flex justify-center mb-6">
                <button type="button" onclick="agregarFila()" class="text-sm bg-purple-50 hover:bg-purple-100 text-purple-700 font-semibold py-2.5 px-5 rounded-full border border-purple-200 transition flex items-center shadow-sm">
                    <i class="fas fa-plus-circle text-purple-600 mr-2"></i> Agregar otro repuesto
                </button>
            </div>

            <!-- Observaciones Adicionales -->
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-1">
                    <i class="fas fa-comment-alt text-gray-500 mr-1"></i> Observaciones o Notas Adicionales (Opcional)
                </label>
                <textarea name="observaciones" rows="3" placeholder="Ingresa detalles sobre la urgencia, motivo o especificaciones del repuesto..."
                    class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500"></textarea>
            </div>

            <!-- Botones de Acción -->
            <div class="pt-6 border-t border-gray-100 flex justify-end space-x-3">
                <a href="<?= BASE_URL ?>inicio" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50 transition">
                    Cancelar
                </a>
                <button type="submit" class="px-8 py-3 bg-purple-600 text-white font-bold rounded-lg shadow-md hover:bg-purple-700 transform hover:-translate-y-1 transition-all flex items-center">
                    <i class="fas fa-paper-plane mr-2"></i> Finalizar Solicitud
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const listaRepuestosGlobal = <?= json_encode($listaRepuestos) ?>;
</script>

<script>
    $(document).ready(function() {
        // Agregar la primera fila automáticamente al cargar
        agregarFila();
    });

    function agregarFila() {
        let opciones = '<option value="">- Buscar Repuesto -</option>';
        listaRepuestosGlobal.forEach(r => {
            let codigo = r.codigo_referencia ? `(${r.codigo_referencia})` : '';
            opciones += `<option value="${r.id_repuesto}">${r.nombre_repuesto} ${codigo}</option>`;
        });

        let idUnico = Date.now() + Math.floor(Math.random() * 1000);

        let html = `
            <tr class="border-b border-gray-100 fila-repuesto">
                <td class="py-2 pr-2">
                    <select name="repuestos[]" id="sel_${idUnico}" class="w-full select2-dinamico" required>
                        ${opciones}
                    </select>
                </td>
                <td class="py-2 px-2">
                    <input type="number" name="cantidades[]" min="1" value="1" required
                        class="w-full h-[42px] border border-gray-300 rounded-lg text-center font-bold text-purple-700">
                </td>
                <td class="py-2 text-center">
                    <button type="button" onclick="eliminarFila(this)" class="text-red-400 hover:text-red-600 p-2">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#contenedor-filas').append(html);

        $(`#sel_${idUnico}`).select2({
            width: '100%',
            language: {
                noResults: () => "Sin resultados"
            }
        });
    }

    function eliminarFila(btn) {
        if ($('#contenedor-filas tr').length > 1) {
            $(btn).closest('tr').remove();
        } else {
            alert("Debe haber al menos un repuesto en la solicitud.");
        }
    }
</script>
