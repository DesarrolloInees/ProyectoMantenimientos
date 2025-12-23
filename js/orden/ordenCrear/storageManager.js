// ==========================================
// GESTOR DE AUTO-GUARDADO Y RECUPERACIÓN
// ==========================================

/**
 * Guardar progreso en localStorage
 */
function guardarProgresoLocal() {
    if (window.AppConfig.ignorarCambios || window.AppConfig.enviandoFormulario) return;

    const filas = [];
    const filasHTML = document.querySelectorAll('#contenedorFilas tr');

    filasHTML.forEach(tr => {
        const idFila = tr.id.replace('fila_', '');

        const filaData = {
            id: idFila,
            remision: tr.querySelector(`select[name="filas[${idFila}][remision]"]`)?.value || '',
            id_cliente: $(`#select_cliente_${idFila}`).val(),
            id_punto: $(`#select_punto_${idFila}`).val(),
            id_maquina: $(`#select_maquina_${idFila}`).val(),
            modalidad: document.getElementById(`select_modalidad_${idFila}`)?.value,
            id_tecnico: $(`#select_tecnico_${idFila}`).val(),
            tipo_servicio: $(`#select_servicio_${idFila}`).val(),
            hora_in: document.getElementById(`in_${idFila}`)?.value,
            hora_out: document.getElementById(`out_${idFila}`)?.value,
            valor: tr.querySelector(`input[name="filas[${idFila}][valor]"]`)?.value,
            estado: $(`#select_estado_${idFila}`).val(),
            calif: $(`#select_calif_${idFila}`).val(),
            obs: tr.querySelector(`textarea[name="filas[${idFila}][obs]"]`)?.value
        };
        filas.push(filaData);
    });

    const datosGlobales = {
        fecha: new Date().getTime(),
        filas: filas,
        repuestos: window.AppConfig.almacenRepuestos
    };

    localStorage.setItem(window.AppConfig.CLAVE_GUARDADO, JSON.stringify(datosGlobales));
    console.log('💾 Auto-guardado completado');

    // 🔔 NOTIFICACIÓN de auto-guardado (solo cada 5 veces para no saturar)
    if (!window._contadorAutoGuardado) window._contadorAutoGuardado = 0;
    window._contadorAutoGuardado++;

    if (window._contadorAutoGuardado % 5 === 0) {
        window.CrearNotificaciones.notificarAutoGuardado();
    }
}

/**
 * Verificar y restaurar borrador
 */
async function verificarYRestaurar() {
    const borrador = localStorage.getItem(window.AppConfig.CLAVE_GUARDADO);

    if (!borrador) {
        iniciarLimpio();
        return;
    }

    let datos;
    try {
        datos = JSON.parse(borrador);
    } catch (e) {
        console.error('Error parseando borrador:', e);
        iniciarLimpio();
        return;
    }

    if (!datos.filas || datos.filas.length === 0) {
        iniciarLimpio();
        return;
    }

    const confirmar = confirm(
        `📂 RECUPERACIÓN DE DATOS\n\nHay un reporte pendiente con ${datos.filas.length} servicios.\n¿Quieres recuperarlos?`
    );

    if (!confirmar) {
        localStorage.removeItem(window.AppConfig.CLAVE_GUARDADO);
        iniciarLimpio();
        return;
    }

    // Iniciar restauración
    window.AppConfig.ignorarCambios = true;
    const btnSubmit = document.querySelector('button[type="submit"]');
    const textoOriginal = btnSubmit?.innerHTML;

    try {
        if (btnSubmit) {
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> RECUPERANDO DATOS...';
            btnSubmit.disabled = true;
        }

        // Limpiar todo
        document.getElementById('contenedorFilas').innerHTML = '';
        window.AppConfig.contadorFilas = 0;
        window.AppConfig.almacenRepuestos = datos.repuestos || {};

        // Restaurar fila por fila
        for (const fila of datos.filas) {
            window.FilaManager.agregarFila();
            const idActual = window.AppConfig.contadorFilas;

            console.log(`Recuperando fila ${idActual}...`);

            // Remisión y Técnico
            if (fila.id_tecnico) {
                $(`#select_tecnico_${idActual}`).val(fila.id_tecnico).trigger('change.select2');

                if (fila.remision) {
                    await window.AjaxUtils.cargarRemisiones(idActual, fila.id_tecnico);
                    setTimeout(() => {
                        $(`#select_remision_${idActual}`).val(fila.remision).trigger('change.select2');
                    }, 200);
                }
            }

            // Cliente
            if (fila.id_cliente) {
                $(`#select_cliente_${idActual}`).val(fila.id_cliente).trigger('change.select2');
            }

            // Punto
            if (fila.id_cliente && fila.id_punto) {
                await window.AjaxUtils.cargarPuntos(idActual, fila.id_cliente);
                const selPunto = document.getElementById(`select_punto_${idActual}`);
                if (selPunto) selPunto.value = fila.id_punto;
                $(`#select_punto_${idActual}`).trigger('change.select2');
            }

            // Máquina
            if (fila.id_punto && fila.id_maquina) {
                await window.AjaxUtils.cargarMaquinas(idActual, fila.id_punto);
                const selMaq = document.getElementById(`select_maquina_${idActual}`);
                if (selMaq) {
                    selMaq.value = fila.id_maquina;
                    window.FilaManager.rellenarDeviceId(idActual, fila.id_maquina);
                }
                $(`#select_maquina_${idActual}`).trigger('change.select2');
            }

            // Resto de campos
            if (fila.modalidad) {
                $(`#select_modalidad_${idActual}`).val(fila.modalidad).trigger('change');
            }

            if (fila.tipo_servicio) {
                $(`#select_servicio_${idActual}`).val(fila.tipo_servicio).trigger('change.select2');
            }

            // Horas
            const inEl = document.getElementById(`in_${idActual}`);
            const outEl = document.getElementById(`out_${idActual}`);
            if (inEl) inEl.value = fila.hora_in;
            if (outEl) outEl.value = fila.hora_out;
            window.TimeManager.calcTiempo(idActual);

            // Valor
            if (fila.valor) {
                const valEl = document.querySelector(`input[name="filas[${idActual}][valor]"]`);
                if (valEl) valEl.value = fila.valor;
            }

            // Estados
            if (fila.estado) $(`#select_estado_${idActual}`).val(fila.estado).trigger('change');
            if (fila.calif) $(`#select_calif_${idActual}`).val(fila.calif).trigger('change');

            // Observaciones
            const obsEl = document.querySelector(`textarea[name="filas[${idActual}][obs]"]`);
            if (obsEl) obsEl.value = fila.obs;

            // Repuestos
            window.RepuestosManager.actualizarBotonRepuestos(idActual);
            const jsonInput = document.getElementById(`json_rep_${idActual}`);
            if (jsonInput && window.AppConfig.almacenRepuestos[idActual]) {
                jsonInput.value = JSON.stringify(window.AppConfig.almacenRepuestos[idActual]);
            }
        }

        console.log("✅ Restauración completada");

        // 🔔 NOTIFICACIÓN de borrador recuperado
        window.CrearNotificaciones.notificarBorradorRecuperado(datos.filas.length);

    } catch (error) {
        console.error("Error en restauración:", error);
        alert("Ocurrió un error recuperando algunos datos. Revisa la información cargada.");
    } finally {
        window.AppConfig.ignorarCambios = false;
        if (btnSubmit) {
            btnSubmit.innerHTML = textoOriginal;
            btnSubmit.disabled = false;
        }
    }
}

/**
 * Iniciar con filas limpias
 */
function iniciarLimpio() {
    console.log("Iniciando limpio con 3 filas...");
    for (let i = 0; i < 3; i++) {
        window.FilaManager.agregarFila();
    }
}

/**
 * Configurar eventos de guardado
 */
function configurarAutoGuardado() {
    // Auto-guardado cada 4 segundos
    setInterval(guardarProgresoLocal, 4000);

    // Limpiar al enviar
    const form = document.getElementById('formServicios');
    if (form) {
        form.addEventListener('submit', function () {
            console.log("Enviando formulario... Deteniendo auto-guardado.");
            window.AppConfig.enviandoFormulario = true;
            localStorage.removeItem(window.AppConfig.CLAVE_GUARDADO);
        });
    }
}

// Exportar
window.StorageManager = {
    guardarProgresoLocal,
    verificarYRestaurar,
    iniciarLimpio,
    configurarAutoGuardado
};