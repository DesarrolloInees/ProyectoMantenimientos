// ==========================================
// VALIDACIONES Y CAPTURA DE GPS
// ==========================================

// Definir constante para el ID de Fallido (según tu BD es 4)
const ID_TIPO_FALLIDO = 4;

// ==========================================
// FUNCIÓN FINAL DE ENVÍO (Versión Infalible con soporte para Fallido)
// ==========================================
// ==========================================
// VALIDACIÓN Y ENVÍO DEL FORMULARIO (VERSIÓN MEJORADA)
// ==========================================
function validarYEnviar() {
    console.log('=== VALIDANDO FORMULARIO ===');

    // 🔥 PASO 0: Verificar y capturar la firma del canvas
    console.log('🔍 Verificando firma...');

    // Verificar si hay algo dibujado en el canvas
    let hayFirmaEnCanvas = false;
    if (canvas && ctx) {
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const data = imageData.data;

        for (let i = 0; i < data.length; i += 4) {
            // Si encuentra un píxel que no sea blanco (con margen)
            if (data[i] < 250 || data[i + 1] < 250 || data[i + 2] < 250) {
                hayFirmaEnCanvas = true;
                break;
            }
        }
    }

    console.log('¿Hay firma en canvas?', hayFirmaEnCanvas);

    // Si hay firma en el canvas pero el campo está vacío, actualizarlo
    if (hayFirmaEnCanvas) {
        const dataURL = canvas.toDataURL('image/png');
        $('#firma_base64').val(dataURL);
        console.log('✅ Firma capturada del canvas, longitud:', dataURL.length);
    }

    // Obtener el valor actualizado del campo
    let firmaBase64 = $('#firma_base64').val();
    console.log('Longitud de firma en campo:', firmaBase64 ? firmaBase64.length : 0);

    // 🔥 VALIDACIÓN MEJORADA: Verificar tanto el canvas como el campo
    if (!hayFirmaEnCanvas && !firmaBase64) {
        alert('⚠️ Por favor, realiza la firma del cliente en el cuadro de arriba.');
        return false;
    }

    // Si el canvas tiene firma pero el campo está vacío (por si acaso)
    if (hayFirmaEnCanvas && !firmaBase64) {
        const dataURL = canvas.toDataURL('image/png');
        $('#firma_base64').val(dataURL);
        firmaBase64 = dataURL;
        console.log('✅ Firma capturada forzadamente');
    }

    // 1. Verificar que el tiempo se haya calculado
    let tiempo = $('#tiempo_servicio').val();
    console.log('Tiempo actual:', tiempo);

    if (tiempo === '00:00') {
        // Intentar calcular nuevamente
        tiempo = calcularTiempoServicio();
        console.log('Tiempo recalculado:', tiempo);

        // Si sigue en 00:00, preguntar al usuario
        if (tiempo === '00:00') {
            let horaEntrada = $('#hora_entrada').val();
            let horaSalida = $('#hora_salida').val();

            if (horaEntrada && horaSalida) {
                // Si hay horas pero el cálculo falló, forzar recalculo
                tiempo = calcularTiempoServicio();
            } else {
                alert('⚠️ Por favor, selecciona la hora de entrada y salida antes de guardar.');
                return false;
            }
        }
    }

    // 2. Validar que se haya seleccionado un tipo de mantenimiento
    let tipoManto = $('select[name="id_tipo_mantenimiento"]').val();
    if (!tipoManto) {
        alert('⚠️ Por favor, selecciona el Tipo de Servicio.');
        return false;
    }

    // 3. Validar que se haya seleccionado una remisión
    let remision = $('select[name="numero_remision"]').val();
    if (!remision) {
        alert('⚠️ Por favor, selecciona un número de remisión.');
        return false;
    }

    // 4. Validar que se hayan subido fotos (mínimo 8)
    let totalFotos = parseInt($('#total_fotos_count').text()) || 0;
    if (totalFotos < 8) {
        if (!confirm('⚠️ Solo has subido ' + totalFotos + ' fotos. Se recomiendan mínimo 8 fotos (Antes, Remisión, Después). ¿Deseas continuar?')) {
            return false;
        }
    }

    // 5. Validar que se haya seleccionado estado final
    let estadoFinal = $('select[name="id_estado_maquina"]').val();
    if (!estadoFinal) {
        alert('⚠️ Por favor, selecciona el Estado Final de la máquina.');
        return false;
    }

    // 6. Validar actividades realizadas
    let actividades = $('textarea[name="actividades_realizadas"]').val().trim();
    if (!actividades) {
        alert('⚠️ Por favor, describe las actividades realizadas.');
        return false;
    }

    // 7. Mostrar resumen antes de guardar
    let resumen = '📋 Resumen del Servicio:\n\n';
    resumen += '🕐 Tiempo: ' + $('#tiempo_total_display').text() + '\n';
    resumen += '📸 Fotos: ' + totalFotos + '\n';
    resumen += '🔧 Tipo: ' + $('select[name="id_tipo_mantenimiento"] option:selected').text() + '\n';
    resumen += '📦 Remisión: ' + remision + '\n';
    resumen += '✅ Estado Final: ' + $('select[name="id_estado_maquina"] option:selected').text() + '\n';

    if (!confirm(resumen + '\n¿Guardar este servicio?')) {
        return false;
    }

    // 8. BLOQUEAR EL BOTÓN (Previene doble envío por "dedo rápido")
    const btnGuardar = document.querySelector('button[onclick="validarYEnviar()"]');
    const textoOriginalBtn = btnGuardar.innerHTML;
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> PROCESANDO...';

    // 9. Iniciar el proceso: GPS -> Fetch (Sin recargar página)
    capturarGPSyEnviarFuerte(document.getElementById('formReporteMovil'), btnGuardar, textoOriginalBtn);
    return true;
}

// ==========================================
// EL MOTOR PERFECTO: GPS + FETCH ANTI-CAÍDAS
// ==========================================
function capturarGPSyEnviarFuerte(form, btnGuardar, textoOriginalBtn) {
    Swal.fire({
        title: 'Cerrando Servicio...',
        text: 'Capturando coordenadas finales y subiendo datos...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    // 1. Validar conexión a internet ANTES de intentar enviar
    if (!navigator.onLine) {
        restaurarBoton(btnGuardar, textoOriginalBtn);
        Swal.fire('Sin Conexión', 'No tienes internet en este momento. Los datos están a salvo en el borrador. Busca señal e intenta guardar nuevamente.', 'warning');
        return;
    }

    // 2. Obtener GPS
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function (position) {
                document.getElementById('latitud_fin').value = position.coords.latitude;
                document.getElementById('longitud_fin').value = position.coords.longitude;

                // 3. Enviar al servidor por FETCH
                ejecutarEnvioFetch(form, btnGuardar, textoOriginalBtn);
            },
            function (error) {
                // Si falla el GPS, advertimos pero podemos decidir dejarlo enviar sin GPS si es urgente (depende de tu regla de negocio). 
                // Por ahora lo bloqueamos:
                restaurarBoton(btnGuardar, textoOriginalBtn);
                Swal.fire("GPS Obligatorio", "No se pudo obtener la ubicación. Sal a un lugar despejado y asegúrate de tener el GPS encendido.", "error");
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    } else {
        ejecutarEnvioFetch(form, btnGuardar, textoOriginalBtn); // Si el cel es prehistórico y no tiene GPS, intenta enviar igual
    }
}

function ejecutarEnvioFetch(form, btnGuardar, textoOriginalBtn) {
    let formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        body: formData
    })
        .then(async response => {
            // Validación de Sesión Expirada: Si el servidor devuelve la página de login en vez de JSON
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.indexOf("application/json") === -1) {
                throw new Error("SESION_EXPIRADA");
            }

            if (!response.ok) throw new Error("ERROR_SERVIDOR");
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // EXITO TOTAL: Borramos el guardado automático
                if (typeof limpiarBorradorStorage === 'function') limpiarBorradorStorage();

                Swal.fire('¡Éxito!', data.msj, 'success').then(() => {
                    window.location.href = 'index.php?pagina=tecnicoProgramacion';
                });
            } else {
                restaurarBoton(btnGuardar, textoOriginalBtn);
                Swal.fire('Error del Servidor', data.msj, 'error');
            }
        })
        .catch(error => {
            restaurarBoton(btnGuardar, textoOriginalBtn);

            if (error.message === "SESION_EXPIRADA") {
                Swal.fire('Sesión Expirada', 'Tu sesión se cerró por inactividad. Abre una nueva pestaña, inicia sesión nuevamente, vuelve a esta pestaña y dale a Guardar. ¡Tus datos están a salvo en el autoguardado!', 'warning');
            } else {
                Swal.fire('Fallo de Red', 'La señal de internet falló justo al enviar. Tus datos están guardados en el borrador. Intenta presionar Guardar de nuevo.', 'error');
            }
        });
}

function restaurarBoton(btn, textoOriginal) {
    btn.disabled = false;
    btn.innerHTML = textoOriginal;
}
