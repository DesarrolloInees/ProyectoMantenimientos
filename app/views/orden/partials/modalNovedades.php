<div id="modalNovedades" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-11/12 md:w-1/3 p-6 transform transition-all scale-100">

        <div class="flex justify-between items-center mb-4 border-b pb-2">
            <h3 class="text-xl font-bold text-red-600">
                <i class="fas fa-exclamation-triangle mr-2"></i> Reportar Novedad
            </h3>
            <button onclick="cerrarModalNovedad()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form id="formNovedad">
            <input type="hidden" id="nov_id_orden" name="id_orden">

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Tipo de Novedad:</label>
                <select id="nov_tipo" class="w-full border p-2 rounded bg-gray-50 focus:outline-none focus:border-red-500">
                    <option value="">-- Seleccione el motivo --</option>
                </select>
            </div>

            <div class="flex justify-between mt-6">
                <button type="button" onclick="eliminarNovedad()"
                    class="text-red-500 hover:text-red-700 font-bold text-sm underline">
                    Quitar Novedad
                </button>

                <div class="space-x-2">
                    <button type="button" onclick="cerrarModalNovedad()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                        Cancelar
                    </button>
                    <button type="button" onclick="guardarNovedad()" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded shadow">
                        Guardar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>