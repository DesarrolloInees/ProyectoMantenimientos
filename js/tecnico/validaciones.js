// ==========================================
// VALIDACIONES Y CAPTURA DE GPS
// ==========================================

// ==========================================
// FUNCIÓN FINAL DE ENVÍO (Versión Infalible)
// ==========================================
function validarYEnviar() {
    console.log("🚀 Iniciando validación de reporte...");
    
    let form = document.getElementById('formReporteMovil');
    
    // Contamos las fotos que el técnico VE en la pantalla
    let fAntes = $('#preview_antes img').length;
    let fRemision = $('#preview_remision img').length;
    let fDespues = $('#preview_despues img').length;
    let total = fAntes + fRemision + fDespues;

    console.log("📊 Conteo de fotos -> Antes: " + fAntes + " | Remisión: " + fRemision + " | Después: " + fDespues);

    // VALIDACIÓN DE LA REMISIÓN
    if (fRemision === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Falta la Remisión',
            text: 'No detectamos la foto de la remisión. Por favor, tómale la foto antes de guardar.'
        });
        return false;
    }

    // VALIDACIÓN DEL TOTAL (8 a 10)
    if (total < 8 || total > 10) {
        Swal.fire({
            icon: 'warning',
            title: 'Cantidad de fotos incorrecta',
            text: "Debes tener entre 8 y 10 fotos. Actualmente tienes: " + total
        });
        return false;
    }

    // VALIDACIÓN DE FIRMA
    // La variable 'firmaVacia' viene de tecnicoReporte.js
    if (typeof firmaVacia !== 'undefined' && firmaVacia) {
        Swal.fire('Atención', 'El cliente debe firmar el reporte.', 'warning');
        return false;
    }

    // Si todo está OK, capturamos el GPS y enviamos
    // Esta función está en este mismo archivo abajo
    capturarGPSyEnviar(form);
}

// ==========================================
// MOTOR DE UBICACIÓN
// ==========================================
function capturarGPSyEnviar(form) {
    // Mostramos estado de carga
    Swal.fire({
        title: 'Obteniendo ubicación...',
        text: 'Por favor encienda el GPS y conceda los permisos si el navegador se lo pide.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Verificamos si el celular soporta GPS
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            // EXITO: Tenemos las coordenadas
            function(position) {
                // Metemos las coordenadas en los inputs ocultos
                document.getElementById('latitud_fin').value = position.coords.latitude;
                document.getElementById('longitud_fin').value = position.coords.longitude;

                // Borramos autoguardado
                if (typeof limpiarBorradorStorage === 'function') {
                    limpiarBorradorStorage(); 
                }

                // Cambiamos el mensaje para que sepa que está subiendo fotos
                Swal.fire({
                    title: 'Guardando Reporte...',
                    text: 'Subiendo evidencias y cerrando servicio, no cierre la ventana.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // AHORA SÍ, ENVIAMOS A PHP
                form.submit();
            },
            // ERROR: No dio permiso, tiene el GPS apagado, etc.
            function(error) {
                let msjError = "Error desconocido.";
                if(error.code == 1) msjError = "Denegaste el permiso de ubicación. Debes permitirlo para guardar.";
                if(error.code == 2) msjError = "No se pudo obtener la señal GPS. Intenta salir a un lugar despejado.";
                if(error.code == 3) msjError = "Se agotó el tiempo para obtener la ubicación.";
                
                Notificaciones.error("GPS Obligatorio", msjError);
            },
            // Opciones del GPS (Alta precisión)
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    } else {
        Notificaciones.error("Incompatible", "Tu navegador o celular no soporta geolocalización.");
    }
}