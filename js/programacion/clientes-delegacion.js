/**
 * js/programacion/clientes-delegacion.js
 * PASO 1: selección de delegación y clientes a incluir en la programación.
 */
(function () {
    function seleccionarTodosClientes(seleccionar) {
        Prog.util.qsa('.checkbox-cliente').forEach(cb => { cb.checked = seleccionar; });
    }

    window.seleccionarTodosClientes = seleccionarTodosClientes;
})();
