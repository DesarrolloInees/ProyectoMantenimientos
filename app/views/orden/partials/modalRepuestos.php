<div id="modalRepuestos"
    class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50">

    <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg p-6 transform scale-100 transition-transform flex flex-col max-h-[90vh]">

        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2 flex justify-between shrink-0">
            <span>🛠️ Gestión de Repuestos</span>
            <button type="button"
                onclick="cerrarModal()"
                class="text-gray-400 hover:text-red-500">
                <i class="fas fa-times"></i>
            </button>
        </h3>

        <input type="hidden" id="modal_fila_actual">

        <div class="overflow-y-auto pr-2 space-y-4 flex-grow">
            
            <div id="contenedor_sugerencias_tecnico" class="hidden mb-4 bg-orange-50 border border-orange-200 rounded-md p-3">
                <div class="flex items-center gap-2 text-orange-700 font-bold text-sm mb-2 border-b border-orange-200 pb-1">
                    <i class="fas fa-mobile-alt"></i>
                    Reportado por el técnico desde la app:
                </div>
                <ul id="lista_sugerencias_tecnico" class="space-y-1 text-xs">
                    </ul>
            </div>

            <div class="flex gap-2 items-center">

                <div class="flex-1 min-w-0">
                    <select id="select_repuesto_modal"
                        class="w-full border rounded p-2 text-sm">
                        <option value="">- Buscar Repuesto -</option>
                    </select>
                </div>

                <div class="w-20 flex-shrink-0">
                    <input type="number"
                        id="cantidad_repuesto_modal"
                        value="1"
                        min="1"
                        class="w-full border rounded p-2 text-sm text-center font-bold bg-gray-50 h-[38px]"
                        placeholder="Cant.">
                </div>

                <div class="w-32 flex-shrink-0">
                    <select id="select_origen_modal"
                        class="w-full border rounded p-2 text-xs bg-gray-100 font-bold text-gray-700 h-[38px]">
                        <option value="INEES">INEES</option>
                        <option value="PROSEGUR">PROSEGUR</option>
                    </select>
                </div>

                <button type="button"
                    onclick="agregarRepuestoALista()"
                    class="bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700 shadow transition h-[38px] flex-shrink-0">
                    <i class="fas fa-plus"></i>
                </button>
            </div>

            <ul id="lista_repuestos_visual"
                class="border rounded p-2 min-h-[120px] bg-gray-50 text-sm">
                <li class="text-gray-400 text-center italic mt-10">
                    No hay repuestos seleccionados.
                </li>
            </ul>
        </div>

        <div class="mt-4 text-right border-t pt-4 shrink-0">
            <button type="button"
                onclick="guardarCambiosModal()"
                class="bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700 shadow-lg transform hover:scale-105 transition">
                <i class="fas fa-check mr-2"></i> Confirmar Cambios
            </button>
        </div>
    </div>
</div>