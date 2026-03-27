// ==========================================
// SISTEMA CENTRAL DE NOTIFICACIONES (SweetAlert2)
// ==========================================

const Notificaciones = {
    // Alerta de éxito grande
    exito: function(titulo, texto = '') {
        Swal.fire({
            icon: 'success',
            title: titulo,
            text: texto,
            confirmButtonColor: '#2563eb' // Azul de tu diseño
        });
    },

    // Alerta de error grande
    error: function(titulo, texto = '') {
        Swal.fire({
            icon: 'error',
            title: titulo,
            text: texto,
            confirmButtonColor: '#d33'
        });
    },

    // Alerta de advertencia (para cuando faltan datos)
    advertencia: function(titulo, texto = '') {
        Swal.fire({
            icon: 'warning',
            title: titulo,
            text: texto,
            confirmButtonColor: '#f59e0b' // Naranja
        });
    },

    // Notificación pequeña tipo "Toast" (ideal para el autoguardado)
    toast: function(mensaje, icono = 'success') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'bottom-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        Toast.fire({
            icon: icono,
            title: mensaje
        });
    },

    // Modal de Confirmación (Pregunta de "Sí o No")
    confirmar: function(titulo, texto, callbackConfirmacion) {
        Swal.fire({
            title: titulo,
            text: texto,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#16a34a', // Verde para aceptar
            cancelButtonColor: '#d33',     // Rojo para cancelar
            confirmButtonText: '<i class="fas fa-check"></i> Sí, guardar',
            cancelButtonText: '<i class="fas fa-times"></i> Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                callbackConfirmacion(); // Ejecuta la función si dice que sí
            }
        });
    }
};