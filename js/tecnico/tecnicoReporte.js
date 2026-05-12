// ==========================================
// VARIABLES GLOBALES
// ==========================================
let repuestosSeleccionados = [];
let canvas, ctx;
let dibujando = false;
let firmaVacia = true;
let totalFotosSubidasServidor = 0;

// ---> NUEVAS VARIABLES PARA VALIDAR CADA TIPO
let totalFotosAntes = 0;
let totalFotosRemision = 0;
let totalFotosDespues = 0;

// ==========================================
// INICIALIZACIÓN CUANDO CARGA LA PÁGINA
// ==========================================
$(document).ready(function () {
    // 1. Inicializar Select2
    $('.select2-movil').select2({
        width: '100%',
        minimumResultsForSearch: 8
    });

    // 2. Eventos de cálculo de tiempo
    $('#hora_entrada, #hora_salida').on('change', calcularTiempoServicio);

    // =========================================================
    // 3. NUEVA LÓGICA DE FOTOS: SUBIDA INMEDIATA POR AJAX
    // =========================================================

    // A. Cargar las fotos que ya estén en la Base de Datos al entrar
    cargarEvidenciasExistentes();

    // B. Escuchar cuando el técnico selecciona fotos
    $('#fotos_antes, #foto_remision, #fotos_despues').on('change', function (e) {
        let files = e.target.files;
        if (files.length === 0) return;

        let tipoEvidencia = '';
        let containerPreview = '';

        if (this.id === 'fotos_antes') { tipoEvidencia = 'antes'; containerPreview = 'preview_antes'; }
        if (this.id === 'foto_remision') { tipoEvidencia = 'remision'; containerPreview = 'preview_remision'; }
        if (this.id === 'fotos_despues') { tipoEvidencia = 'despues'; containerPreview = 'preview_despues'; }

        let remision = $('select[name="numero_remision"]').val() || '';
        let idOrden = $('input[name="id_ordenes_servicio"]').val();

        // Subir cada foto seleccionada al servidor
        Array.from(files).forEach(file => {
            subirFotoAjax(file, tipoEvidencia, remision, idOrden, containerPreview);
        });

        // Limpiar el input para que pueda volver a seleccionar la misma foto si la borra
        $(this).val('');
    });


    // 4. Inicializar Modal de Repuestos
    $('#btn_abrir_repuestos').on('click', function (e) {
        e.preventDefault();
        $('#modalRepuestos').removeClass('hidden').addClass('flex');

        if (!$('#select_repuesto_modal').hasClass("select2-hidden-accessible")) {
            $('#select_repuesto_modal').select2({
                dropdownParent: $('#modalRepuestos'),
                width: '100%'
            });
        }
    });

    // 5. Inicializar el Canvas para la Firma
    canvas = document.getElementById('canvas_firma');
    if (canvas) {
        ctx = canvas.getContext('2d');
        ctx.lineWidth = 3;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#0f172a';

        // Eventos táctiles (Celulares)
        canvas.addEventListener('touchstart', iniciarDibujo, { passive: false });
        canvas.addEventListener('touchmove', dibujar, { passive: false });
        canvas.addEventListener('touchend', detenerDibujo);

        // Eventos de ratón (PC)
        canvas.addEventListener('mousedown', iniciarDibujo);
        canvas.addEventListener('mousemove', dibujar);
        canvas.addEventListener('mouseup', detenerDibujo);
        canvas.addEventListener('mouseout', detenerDibujo);
    }
});

// ==========================================
// NUEVAS FUNCIONES AJAX PARA FOTOS
// ==========================================
function subirFotoAjax(file, tipo, remision, idOrden, containerId) {
    let formData = new FormData();
    formData.append('foto', file);
    formData.append('id_orden', idOrden);
    formData.append('tipo_evidencia', tipo);
    formData.append('numero_remision', remision);

    // Crear un cuadrito de "Cargando..."
    let tempId = 'loading_' + Date.now() + Math.floor(Math.random() * 100);
    $('#' + containerId).append(`
        <div id="${tempId}" class="relative w-16 h-16 rounded-md overflow-hidden border border-gray-300 shadow-sm flex items-center justify-center bg-gray-100">
            <i class="fas fa-spinner fa-spin text-blue-500 text-xl"></i>
        </div>
    `);

    // Enviar al controlador
    fetch('index.php?pagina=tecnicoReporte&accion=ajaxSubirFotoUnica', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            $('#' + tempId).remove(); // Quitar el "Cargando"
            if (data.success) {
                cargarEvidenciasExistentes(); // Recargar todas las fotos
            } else {
                Swal.fire('Error', data.msj, 'error');
            }
        })
        .catch(err => {
            $('#' + tempId).remove();
            Swal.fire('Error', 'Fallo al subir la foto por red', 'error');
        });
}

function cargarEvidenciasExistentes() {
    let idOrden = $('input[name="id_ordenes_servicio"]').val();
    let formData = new FormData();
    formData.append('id_orden', idOrden);

    fetch('index.php?pagina=tecnicoReporte&accion=ajaxObtenerEvidencias', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                $('#preview_antes, #preview_remision, #preview_despues').empty();

                // Reiniciamos los contadores globales
                totalFotosAntes = 0;
                totalFotosRemision = 0;
                totalFotosDespues = 0;

                data.data.forEach(foto => {
                    // OJO AQUÍ: Asegúrate de que diga foto.id_evidencia o foto.id_evidencia_servicio (según tu BD)
                    let btnDelete = `<button type="button" onclick="eliminarFotoAjax(${foto.id_evidencia})" class="absolute top-0 right-0 bg-red-600 text-white w-6 h-6 rounded-bl-md flex items-center justify-center text-xs hover:bg-red-700 opacity-90 transition"><i class="fas fa-trash"></i></button>`;
                    let imgHtml = `<div class="relative w-16 h-16 rounded-md overflow-hidden border border-gray-300 shadow-sm group">
                                <img src="/ProyectoMantenimientos/${foto.ruta_archivo}" class="w-full h-full object-cover">
                                    ${btnDelete}
                                </div>`;

                    if (foto.tipo_evidencia === 'antes') { $('#preview_antes').append(imgHtml); totalFotosAntes++; }
                    if (foto.tipo_evidencia === 'remision') { $('#preview_remision').append(imgHtml); totalFotosRemision++; }
                    if (foto.tipo_evidencia === 'despues') { $('#preview_despues').append(imgHtml); totalFotosDespues++; }
                });

                // Actualizar Badges y Contadores visuales
                actualizarBadgeFotos('#badge_fotos_antes', totalFotosAntes);
                actualizarBadgeFotos('#badge_foto_remision', totalFotosRemision);
                actualizarBadgeFotos('#badge_fotos_despues', totalFotosDespues);

                totalFotosSubidasServidor = totalFotosAntes + totalFotosRemision + totalFotosDespues;

                let totalElement = $('#total_fotos_count');
                totalElement.text(totalFotosSubidasServidor);

                if (totalFotosSubidasServidor >= 8 && totalFotosSubidasServidor <= 10) {
                    totalElement.removeClass('text-red-600 text-orange-500').addClass('text-green-600');
                } else if (totalFotosSubidasServidor > 0 && totalFotosSubidasServidor < 8) {
                    totalElement.removeClass('text-red-600 text-green-600').addClass('text-orange-500');
                } else {
                    totalElement.removeClass('text-green-600 text-orange-500').addClass('text-red-600');
                }
            }
        });
}

function actualizarBadgeFotos(selector, cantidad) {
    if (cantidad > 0) {
        $(selector).removeClass('bg-gray-200 text-gray-700').addClass('bg-indigo-100 text-indigo-800').text(cantidad + ' subidas');
    } else {
        $(selector).removeClass('bg-indigo-100 text-indigo-800').addClass('bg-gray-200 text-gray-700').text('0 subidas');
    }
}

// Para usarla desde el HTML tiene que estar en el window
window.eliminarFotoAjax = function (idEvidencia) {
    if (!confirm('¿Borrar esta foto permanentemente?')) return;

    let formData = new FormData();
    formData.append('id_evidencia', idEvidencia);

    fetch('index.php?pagina=tecnicoReporte&accion=ajaxEliminarFotoUnica', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                cargarEvidenciasExistentes();
            } else {
                // AHORA TE MOSTRARÁ EL ERROR EXACTO:
                Swal.fire('Error al borrar', data.msj, 'error');
            }
        });
};

// ==========================================
// FUNCIONES DE TIEMPO
// ==========================================
function calcularTiempoServicio() {
    let hEntrada = $('#hora_entrada').val();
    let hSalida = $('#hora_salida').val();
    if (hEntrada && hSalida) {
        let entrada = new Date("1970-01-01T" + hEntrada + ":00");
        let salida = new Date("1970-01-01T" + hSalida + ":00");
        if (salida < entrada) salida.setDate(salida.getDate() + 1);
        let diffMs = salida - entrada;
        let diffHrs = Math.floor((diffMs % 86400000) / 3600000);
        let diffMins = Math.round(((diffMs % 86400000) % 3600000) / 60000);
        let total = diffHrs.toString().padStart(2, '0') + ":" + diffMins.toString().padStart(2, '0');
        $('#tiempo_servicio').val(total);
        $('#tiempo_total_display').text(total + " hrs");
    }
}

// ==========================================
// FUNCIONES DEL MODAL DE REPUESTOS
// ==========================================
function cerrarModalRepuestos() {
    $('#modalRepuestos').addClass('hidden').removeClass('flex');
    $('#select_repuesto_modal').val(null).trigger('change');
    $('#cantidad_repuesto_modal').val(1);
}

function agregarRepuesto() {
    let selectElement = $('#select_repuesto_modal');
    let idRep = selectElement.val();
    let optionSeleccionado = selectElement.find('option:selected');

    if (!idRep) {
        alert("⚠️ Seleccione un repuesto de la lista.");
        return;
    }

    let nombreLimpio = optionSeleccionado.data('nombre');
    let origen = $('#select_origen_modal').val();
    let cant = parseInt($('#cantidad_repuesto_modal').val()) || 1;

    if (cant <= 0) {
        alert("⚠️ La cantidad debe ser mayor a 0.");
        return;
    }

    let indexExiste = repuestosSeleccionados.findIndex(r => r.id === idRep && r.origen === origen);

    if (indexExiste !== -1) {
        repuestosSeleccionados[indexExiste].cantidad += cant;
    } else {
        repuestosSeleccionados.push({
            id: idRep,
            nombre: nombreLimpio,
            origen: origen,
            cantidad: cant
        });
    }

    renderizarListaRepuestos();
    cerrarModalRepuestos();
}

function renderizarListaRepuestos() {
    let ul = $('#lista_repuestos_agregados');
    ul.empty();
    let totalItems = 0;

    repuestosSeleccionados.forEach((item, index) => {
        totalItems += item.cantidad;
        let bgBadge = item.origen === 'INEES' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800';

        ul.append(`
            <li class="flex justify-between items-center bg-white p-2 border border-gray-200 rounded shadow-sm">
                <div class="flex items-center gap-2 overflow-hidden w-full">
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded ${bgBadge} border border-opacity-20 flex-shrink-0" style="min-width:60px; text-align:center">${item.origen}</span>
                    <span class="text-xs text-gray-700 font-medium truncate flex-grow">${item.nombre}</span>
                    <span class="bg-gray-800 text-white text-[11px] px-2 py-0.5 rounded-full font-bold flex-shrink-0">x${item.cantidad}</span>
                </div>
                <button type="button" onclick="borrarRepuesto(${index})" class="text-red-400 hover:text-red-600 px-3 ml-2 text-lg transition">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </li>
        `);
    });

    $('#badge_repuestos').text(totalItems);
    $('#json_repuestos').val(JSON.stringify(repuestosSeleccionados));
}

function borrarRepuesto(index) {
    repuestosSeleccionados.splice(index, 1);
    renderizarListaRepuestos();
}

// ==========================================
// FUNCIONES DE LA FIRMA DIGITAL (CANVAS)
// ==========================================
function obtenerPosicion(evento) {
    const rect = canvas.getBoundingClientRect();
    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;

    let clientX = evento.clientX;
    let clientY = evento.clientY;

    if (evento.touches && evento.touches.length > 0) {
        clientX = evento.touches[0].clientX;
        clientY = evento.touches[0].clientY;
    }

    return {
        x: (clientX - rect.left) * scaleX,
        y: (clientY - rect.top) * scaleY
    };
}

const iniciarDibujo = (e) => {
    e.preventDefault();
    dibujando = true;
    const pos = obtenerPosicion(e);
    ctx.beginPath();
    ctx.moveTo(pos.x, pos.y);
};

const dibujar = (e) => {
    if (!dibujando) return;
    e.preventDefault();
    const pos = obtenerPosicion(e);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
    firmaVacia = false;
};

const detenerDibujo = (e) => {
    e.preventDefault();
    dibujando = false;
};

function limpiarFirma() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    firmaVacia = true;
    document.getElementById('firma_base64').value = "";
}


