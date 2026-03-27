// ==========================================
// VALIDACIONES Y CAPTURA DE GPS
// ==========================================

function validarYEnviar() {
    let form = document.getElementById('formReporteMovil');
    
    // 1. Contar Fotos
    let fAntes = document.getElementById('fotos_antes').files.length;
    let fRemision = document.getElementById('foto_remision').files.length; 
    let fDespues = document.getElementById('fotos_despues').files.length;
    let totalFotos = fAntes + fRemision + fDespues;

    // LA FOTO DE LA REMISIÓN ES SAGRADA
    if (fRemision === 0) {
        Notificaciones.error("Falta la Remisión", "La foto de la remisión física es OBLIGATORIA. Por favor, tómale la foto antes de guardar el reporte.");
        return false;
    }

    if (totalFotos < 8 || totalFotos > 10) {
        Notificaciones.advertencia("Cantidad de Evidencias", `Por favor seleccione entre 8 y 10 fotos en total. Actualmente ha seleccionado: ${totalFotos}`);
        return false;
    }

    // 2. Validar Firma Digital
    if (typeof firmaVacia !== 'undefined' && firmaVacia) {
        Notificaciones.advertencia("Firma Requerida", "El cliente o encargado debe firmar el reporte en el recuadro antes de guardar.");
        return false;
    }

    // 3. Validar HTML5
    if (!form.checkValidity()) {
        form.reportValidity(); 
        Notificaciones.error("Campos Incompletos", "Por favor, llene todos los campos obligatorios marcados en el formulario.");
        return false;
    }

    document.getElementById('firma_base64').value = canvas.toDataURL('image/png');

    // 4. Modal de Confirmación
    Notificaciones.confirmar(
        "¿Finalizar y Guardar?", 
        "Asegúrese de que la información es correcta. Se capturará su ubicación GPS actual para finalizar el servicio.", 
        function() {
            // SI DIJO QUE SÍ, INICIAMOS LA CAPTURA GPS
            capturarGPSyEnviar(form);
        }
    );
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