function validarYActualizar() {
    console.log('=== VALIDANDO EDICIÓN ===');

    // 1. Validar la firma solo si es obligatoria. 
    // En edición, si el input no tiene nada pero ya había firma en la BD, lo dejamos pasar.
    if (canvas && ctx) {
        if (tieneFirmaEnCanvas()) {
            $('#firma_base64').val(canvas.toDataURL('image/png'));
        }
    }

    // Validaciones estándar
    let tiempo = $('#tiempo_servicio').val();
    if (tiempo === '00:00' || !tiempo) tiempo = calcularTiempoServicio();
    if (tiempo === '00:00') {
        alert('⚠️ Selecciona la hora de entrada y salida.');
        return false;
    }

    if (!$('select[name="id_tipo_mantenimiento"]').val()) { alert('⚠️ Selecciona el Tipo de Servicio.'); return false; }
    if (!$('select[name="numero_remision"]').val()) { alert('⚠️ Selecciona un número de remisión.'); return false; }
    if (!$('select[name="id_estado_maquina"]').val()) { alert('⚠️ Selecciona el Estado Final.'); return false; }
    if (!$('textarea[name="actividades_realizadas"]').val().trim()) { alert('⚠️ Describe las actividades.'); return false; }

    // Advertencia: Correctivo sin repuestos
    let textoTipoEdit = $('select[name="id_tipo_mantenimiento"] option:selected').text().toUpperCase().trim();
    if (textoTipoEdit.includes('CORRECTIVO') && repuestosSeleccionados.length === 0) {
        if (!confirm('⚠️ ¡ADVERTENCIA!\n\nEste servicio es Mantenimiento CORRECTIVO pero NO has agregado repuestos.\nEsto es inusual.\n\n¿Estás seguro de guardar sin repuestos?')) {
            return false;
        }
    }

    if (!confirm('¿Estás seguro de guardar estos cambios?')) return false;

    const btnGuardar = document.querySelector('button[onclick="validarYActualizar()"]');
    const textoOriginalBtn = btnGuardar.innerHTML;
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ACTUALIZANDO...';

    const form = document.getElementById('formReporteMovil');
    let formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.indexOf("application/json") === -1) throw new Error("SESION_EXPIRADA");
        if (!response.ok) throw new Error("ERROR_SERVIDOR");
        return response.json();
    })
    .then(data => {
        if (data.success) {
            Swal.fire('¡Actualizado!', data.msj, 'success').then(() => {
                window.location.href = 'index.php?pagina=tecnicoProgramacion';
            });
        } else {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = textoOriginalBtn;
            Swal.fire('Error', data.msj, 'error');
        }
    })
    .catch(error => {
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = textoOriginalBtn;
        if (error.message === "SESION_EXPIRADA") {
            Swal.fire('Sesión Expirada', 'Tu sesión se cerró. Abre una nueva pestaña, inicia sesión y vuelve a intentar.', 'warning');
        } else {
            Swal.fire('Error de Red', 'Fallo de conexión. Verifica tu internet.', 'error');
        }
    });
}