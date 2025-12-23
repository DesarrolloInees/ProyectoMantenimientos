<!-- ============================================== -->
<!-- PARTIAL: MODAL DE GESTIÓN DE REPUESTOS -->
<!-- ============================================== -->

<div id="modalRepuestos"
    class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50">

    <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg p-6 transform scale-100 transition-transform">

        <!-- HEADER -->
        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2 flex justify-between">
            <span>🛠️ Gestión de Repuestos</span>
            <button type="button"
                onclick="cerrarModal()"
                class="text-gray-400 hover:text-red-500">
                <i class="fas fa-times"></i>
            </button>
        </h3>

        <!-- ID de la fila actual -->
        <input type="hidden" id="modal_fila_actual">

        <!-- FORMULARIO DE AGREGAR -->
        <div class="space-y-4">
            <div class="flex gap-2 items-center">

                <!-- SELECT DE REPUESTOS (Select2) -->
                <div class="flex-grow w-2/3">
                    <select id="select_repuesto_modal"
                        class="w-full border rounded p-2 text-sm">
                        <option value="">- Buscar Repuesto -</option>
                    </select>
                </div>

                <!-- INPUT DE CANTIDAD -->
                <div class="w-20">
                    <input type="number"
                        id="cantidad_repuesto_modal"
                        value="1"
                        min="1"
                        class="w-full border rounded p-2 text-sm text-center font-bold bg-gray-50 h-[38px]"
                        placeholder="Cant.">
                </div>

                <!-- SELECT DE ORIGEN -->
                <div class="w-1/3">
                    <select id="select_origen_modal"
                        class="w-full border rounded p-2 text-xs bg-gray-100 font-bold text-gray-700 h-[38px]">
                        <option value="INEES">INEES</option>
                        <option value="PROSEGUR">PROSEGUR</option>
                    </select>
                </div>

                <!-- BOTÓN AGREGAR -->
                <button type="button"
                    onclick="agregarRepuestoALista()"
                    class="bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700 shadow transition h-[38px]">
                    <i class="fas fa-plus"></i>
                </button>
            </div>

            <!-- LISTA VISUAL DE REPUESTOS SELECCIONADOS -->
            <ul id="lista_repuestos_visual"
                class="border rounded p-2 h-48 overflow-y-auto bg-gray-50 text-sm">
                <li class="text-gray-400 text-center italic mt-10">
                    No hay repuestos seleccionados.
                </li>
            </ul>
        </div>

        <!-- FOOTER: BOTÓN CONFIRMAR -->
        <div class="mt-6 text-right border-t pt-4">
            <button type="button"
                onclick="guardarCambiosModal()"
                class="bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700 shadow-lg transform hover:scale-105 transition">
                <i class="fas fa-check mr-2"></i> Confirmar Cambios
            </button>
        </div>
    </div>
</div>