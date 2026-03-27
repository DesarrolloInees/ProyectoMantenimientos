$(document).ready(function() {
    // Tomamos el ID de la orden para que el borrador sea único de este servicio
    const idOrden = $('input[name="id_ordenes_servicio"]').val();
    if (!idOrden) return; // Si no hay orden, no hacemos nada

    const storageKey = 'borrador_orden_' + idOrden;
    let motorGuardado; // Variable para controlar el reloj del autoguardado

    // ==========================================
    // 1. FUNCIÓN PARA GUARDAR (El motor)
    // ==========================================
    function guardarBorrador() {
        let borrador = {};

        // Guardar inputs, selects y textareas
        $('#formReporteMovil').find('input:not([type="file"]), select, textarea').each(function() {
            let nombre = $(this).attr('name');
            if (nombre) {
                borrador[nombre] = $(this).val();
            }
        });

        // Guardar la lista de repuestos
        if (typeof repuestosSeleccionados !== 'undefined') {
            borrador['lista_repuestos'] = repuestosSeleccionados;
        }

        // Guardar la firma digital
        if (typeof firmaVacia !== 'undefined' && !firmaVacia) {
            const canvas = document.getElementById('canvas_firma');
            borrador['firma_canvas'] = canvas.toDataURL('image/png');
        }

        // Convertir a texto y guardar en el celular
        localStorage.setItem(storageKey, JSON.stringify(borrador));
        
        // Opcional: Mostrar un toast sutil para que sepa que se guardó
        // Notificaciones.toast('Borrador guardado auto...', 'success');
    }

    // ==========================================
    // 2. FUNCIÓN PARA ARRANCAR EL MOTOR
    // ==========================================
    function iniciarMotorGuardado() {
        // Ejecuta la función guardarBorrador cada 5000 milisegundos (5 seg)
        motorGuardado = setInterval(guardarBorrador, 5000);
    }

    // ==========================================
    // 3. FUNCIÓN PARA REVISAR Y PREGUNTAR AL INICIO
    // ==========================================
    function revisarBorradorPrevio() {
        let datosGuardados = localStorage.getItem(storageKey);
        
        if (datosGuardados) {
            // ¡HAY UN BORRADOR! Detenemos todo y le preguntamos al técnico
            Swal.fire({
                title: '¡Borrador Encontrado!',
                text: 'Tienes información sin guardar de una visita anterior a esta orden. ¿Quieres recuperarla o empezar de cero?',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#2563eb', // Azul para recuperar
                cancelButtonColor: '#d33',     // Rojo para descartar
                confirmButtonText: '<i class="fas fa-undo"></i> Sí, recuperar datos',
                cancelButtonText: '<i class="fas fa-trash"></i> No, empezar de cero',
                allowOutsideClick: false // Obliga a que tome una decisión
            }).then((result) => {
                if (result.isConfirmed) {
                    // DIJO QUE SÍ: Restauramos los datos
                    let borrador = JSON.parse(datosGuardados);

                    // A. Restaurar textos y selects
                    $.each(borrador, function(key, value) {
                        if (key !== 'lista_repuestos' && key !== 'firma_canvas') {
                            let elemento = $('[name="' + key + '"]');
                            if (elemento.length > 0) {
                                elemento.val(value);
                                if (elemento.is('select')) {
                                    elemento.trigger('change');
                                }
                            }
                        }
                    });

                    // B. Restaurar repuestos
                    if (borrador.lista_repuestos && borrador.lista_repuestos.length > 0) {
                        repuestosSeleccionados = borrador.lista_repuestos;
                        if (typeof renderizarListaRepuestos === 'function') {
                            renderizarListaRepuestos();
                        }
                    }

                    // C. Restaurar firma
                    if (borrador.firma_canvas) {
                        let canvas = document.getElementById('canvas_firma');
                        let ctx = canvas.getContext('2d');
                        let img = new Image();
                        img.onload = function() {
                            ctx.drawImage(img, 0, 0);
                            firmaVacia = false;
                        };
                        img.src = borrador.firma_canvas;
                    }

                    Notificaciones.toast('Información recuperada', 'success');
                } else {
                    // DIJO QUE NO: Destruimos el borrador viejo para limpiar todo
                    localStorage.removeItem(storageKey);
                    Notificaciones.toast('Empezando de cero...', 'info');
                }

                // IMPORTANTE: Sin importar si dijo Sí o No, AHORA SÍ arrancamos el motor
                iniciarMotorGuardado();
            });

        } else {
            // SI NO HABÍA NINGÚN BORRADOR GUARDADO
            // Arrancamos el motor inmediatamente y en silencio
            iniciarMotorGuardado();
        }
    }

    // ==========================================
    // 4. INICIAR EL PROCESO AL CARGAR LA PÁGINA
    // ==========================================
    revisarBorradorPrevio();
});

// ==========================================
// 5. FUNCIÓN PARA LIMPIAR AL FINALIZAR
// ==========================================
// Esta la usamos en tu validaciones.js cuando ya envían la orden real
function limpiarBorradorStorage() {
    const idOrden = $('input[name="id_ordenes_servicio"]').val();
    localStorage.removeItem('borrador_orden_' + idOrden);
}