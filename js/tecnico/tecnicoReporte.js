// ==========================================
// VARIABLES GLOBALES
// ==========================================
let repuestosSeleccionados = [];
let canvas, ctx;
let dibujando = false;
let firmaVacia = true;
let totalFotosSubidasServidor = 0;

let totalFotosAntes = 0;
let totalFotosRemision = 0;
let totalFotosDespues = 0;

// ==========================================
// FUNCIONES DE LA FIRMA DIGITAL (CANVAS)
// ==========================================
function obtenerPosicion(evento) {
    if (!canvas) return { x: 0, y: 0 };
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

function iniciarDibujo(e) {
    if (e.cancelable) e.preventDefault();
    dibujando = true;
    const pos = obtenerPosicion(e);
    ctx.beginPath();
    ctx.moveTo(pos.x, pos.y);
}

function dibujar(e) {
    if (!dibujando) return;
    if (e.cancelable) e.preventDefault();
    const pos = obtenerPosicion(e);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
    firmaVacia = false;
}

function detenerDibujo(e) {
    if (e && e.cancelable) e.preventDefault();
    dibujando = false;

    if (!firmaVacia && canvas) {
        const dataURL = canvas.toDataURL('image/png');
        const inputFirma = document.getElementById('firma_base64');
        if (inputFirma) {
            inputFirma.value = dataURL;
        }
    }
}

function limpiarFirma() {
    if (!canvas || !ctx) return;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    firmaVacia = true;
    const inputFirma = document.getElementById('firma_base64');
    if (inputFirma) {
        inputFirma.value = "";
    }
}

function tieneFirmaEnCanvas() {
    if (!canvas || !ctx) return false;
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const data = imageData.data;

    for (let i = 0; i < data.length; i += 4) {
        if (data[i] < 250 || data[i + 1] < 250 || data[i + 2] < 250) {
            return true;
        }
    }
    return false;
}

// ==========================================
// INICIALIZACIÓN CUANDO CARGA LA PÁGINA
// ==========================================
$(document).ready(function () {
    // 1. Inicializar Select2
    $('.select2-movil').select2({
        width: '100%',
        minimumResultsForSearch: 8
    });

    // 2. NUEVA LÓGICA: Formato militar (24h) para hora de entrada y salida
    $('#hora_entrada, #hora_salida').on('input', function () {
        // Permitir solo dígitos mientras escribe
        this.value = this.value.replace(/[^0-9:]/g, '');
    });

    $('#hora_entrada, #hora_salida').on('blur', function () {
        formatearHoraMilitar(this);
        calcularTiempoServicio();
    });

    setTimeout(function () {
        if ($('#hora_entrada').val() && $('#hora_salida').val()) {
            formatearHoraMilitar(document.getElementById('hora_entrada'));
            formatearHoraMilitar(document.getElementById('hora_salida'));
            calcularTiempoServicio();
        }
    }, 500);

    // 3. Subida de Fotos por AJAX
    cargarEvidenciasExistentes();

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

        Array.from(files).forEach(file => {
            subirFotoAjax(file, tipoEvidencia, remision, idOrden, containerPreview);
        });

        $(this).val('');
    });

    // 4. Modal de Repuestos
    $('#btn_abrir_repuestos').on('click', function (e) {
        e.preventDefault();
        $('#modalRepuestos').removeClass('hidden').addClass('flex');
        $('#aviso_stock_info').addClass('hidden');

        if (!$('#select_repuesto_modal').hasClass("select2-hidden-accessible")) {
            $('#select_repuesto_modal').select2({
                dropdownParent: $('#modalRepuestos'),
                width: '100%',
                placeholder: "Buscar por nombre o código...",
                language: {
                    noResults: function () { return "No se encontró ese repuesto"; },
                    searching: function () { return "Buscando..."; }
                }
            });
        } else {
            $('#select_repuesto_modal').val(null).trigger('change');
        }
    });

    // 4b. Mostrar info de stock al seleccionar un repuesto en el modal
    $('#select_repuesto_modal').on('change', function () {
        let optionSeleccionado = $(this).find(':selected');
        let idRep = $(this).val();
        let avisoDiv = $('#aviso_stock_info');
        let textoAviso = $('#texto_aviso_stock');

        if (!idRep) {
            avisoDiv.addClass('hidden');
            return;
        }

        let tieneInventario = optionSeleccionado.data('en-inventario');
        let stockDisponible = parseInt(optionSeleccionado.data('stock')) || 0;
        let nombreRep = optionSeleccionado.data('nombre');

        if (tieneInventario == 1) {
            avisoDiv.removeClass('hidden bg-yellow-50 border-yellow-200 text-yellow-700')
                .addClass('bg-green-50 border border-green-200 rounded-lg p-2 text-xs text-green-700');
            textoAviso.html('<i class="fas fa-check-circle mr-1"></i><strong>' + nombreRep + '</strong> — Disponible en inventario: <strong>' + stockDisponible + '</strong> unidades');
        } else {
            avisoDiv.removeClass('hidden bg-green-50 border-green-200 text-green-700')
                .addClass('bg-yellow-50 border border-yellow-200 rounded-lg p-2 text-xs text-yellow-700');
            textoAviso.html('<i class="fas fa-exclamation-triangle mr-1"></i><strong>' + nombreRep + '</strong> — No está en tu inventario. Se registrará como solicitud para gestión.');
        }
    });

    // 5. Inicializar Canvas
    canvas = document.getElementById('canvas_firma');
    if (canvas) {
        ctx = canvas.getContext('2d');
        ctx.lineWidth = 3;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#0f172a';

        canvas.addEventListener('touchstart', iniciarDibujo, { passive: false });
        canvas.addEventListener('touchmove', dibujar, { passive: false });
        canvas.addEventListener('touchend', detenerDibujo, { passive: false });
        canvas.addEventListener('touchcancel', detenerDibujo, { passive: false });

        canvas.addEventListener('mousedown', iniciarDibujo);
        canvas.addEventListener('mousemove', dibujar);
        canvas.addEventListener('mouseup', detenerDibujo);
        canvas.addEventListener('mouseout', detenerDibujo);
    }
});

// ==========================================
// FUNCIONES AJAX PARA FOTOS
// ==========================================
function subirFotoAjax(file, tipo, remision, idOrden, containerId) {
    if (!navigator.onLine) {
        Swal.fire('Sin conexión', 'No puedes subir fotos sin internet. Busca señal para continuar.', 'warning');
        return;
    }
    let formData = new FormData();
    formData.append('foto', file);
    formData.append('id_orden', idOrden);
    formData.append('tipo_evidencia', tipo);
    formData.append('numero_remision', remision);

    let tempId = 'loading_' + Date.now() + Math.floor(Math.random() * 100);
    $('#' + containerId).append(`
        <div id="${tempId}" class="relative w-16 h-16 rounded-md overflow-hidden border border-gray-300 shadow-sm flex items-center justify-center bg-gray-100">
            <i class="fas fa-spinner fa-spin text-blue-500 text-xl"></i>
        </div>
    `);

    fetch('index.php?pagina=tecnicoReporte&accion=ajaxSubirFotoUnica', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            $('#' + tempId).remove();
            if (data.success) {
                cargarEvidenciasExistentes();
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
                totalFotosAntes = 0;
                totalFotosRemision = 0;
                totalFotosDespues = 0;

                data.data.forEach(foto => {
                    let rutaImagen = foto.ruta_archivo;
                    let btnDelete = `<button type="button" onclick="eliminarFotoAjax(${foto.id_evidencia})" class="absolute top-0 right-0 bg-red-600 text-white w-6 h-6 rounded-bl-md flex items-center justify-center text-xs hover:bg-red-700 opacity-90 transition"><i class="fas fa-trash"></i></button>`;
                    let imgHtml = `<div class="relative w-16 h-16 rounded-md overflow-hidden border border-gray-300 shadow-sm group">
                                    <img src="${rutaImagen}" class="w-full h-full object-cover">
                                    ${btnDelete}
                                </div>`;

                    if (foto.tipo_evidencia === 'antes') { $('#preview_antes').append(imgHtml); totalFotosAntes++; }
                    if (foto.tipo_evidencia === 'remision') { $('#preview_remision').append(imgHtml); totalFotosRemision++; }
                    if (foto.tipo_evidencia === 'despues') { $('#preview_despues').append(imgHtml); totalFotosDespues++; }
                });

                actualizarBadgeFotos('#badge_fotos_antes', totalFotosAntes);
                actualizarBadgeFotos('#badge_foto_remision', totalFotosRemision);
                actualizarBadgeFotos('#badge_fotos_despues', totalFotosDespues);
                totalFotosSubidasServidor = totalFotosAntes + totalFotosRemision + totalFotosDespues;
                $('#total_fotos_count').text(totalFotosSubidasServidor);
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
                Swal.fire('Error al borrar', data.msj, 'error');
            }
        });
};

// ==========================================
// FUNCIONES DE FORMATO Y CÁLCULO DE TIEMPO
// ==========================================
function formatearHoraMilitar(input) {
    if (!input) return;

    let valor = input.value.replace(/\D/g, ''); // Quitar cualquier caracter no numérico

    if (valor.length > 4) {
        valor = valor.substring(0, 4);
    }

    if (valor.length === 4) {
        let horas = parseInt(valor.substring(0, 2), 10);
        let minutos = parseInt(valor.substring(2, 4), 10);

        if (horas > 23) horas = 23;
        if (minutos > 59) minutos = 59;

        let hStr = horas.toString().padStart(2, '0');
        let mStr = minutos.toString().padStart(2, '0');

        input.value = `${hStr}:${mStr}`;
    } else if (valor.length === 3) {
        let horas = "0" + valor.substring(0, 1);
        let minutos = valor.substring(1, 3);
        if (parseInt(minutos, 10) > 59) minutos = "59";
        input.value = `${horas}:${minutos}`;
    }
}

function calcularTiempoServicio() {
    let hEntrada = $('#hora_entrada').val();
    let hSalida = $('#hora_salida').val();
    let regexHora = /^([01]\d|2[0-3]):([0-5]\d)$/;

    if (regexHora.test(hEntrada) && regexHora.test(hSalida)) {
        let partesEntrada = hEntrada.split(':');
        let partesSalida = hSalida.split(':');

        let minutosEntrada = parseInt(partesEntrada[0], 10) * 60 + parseInt(partesEntrada[1], 10);
        let minutosSalida = parseInt(partesSalida[0], 10) * 60 + parseInt(partesSalida[1], 10);

        if (minutosSalida < minutosEntrada) {
            minutosSalida += 1440; // Manejo de cambio de día (medianoche)
        }

        let diferenciaMinutos = minutosSalida - minutosEntrada;
        let horas = Math.floor(diferenciaMinutos / 60);
        let minutos = diferenciaMinutos % 60;

        let total = String(horas).padStart(2, '0') + ':' + String(minutos).padStart(2, '0');

        $('#tiempo_servicio').val(total);
        $('#tiempo_total_display').text(total + ' hrs');

        return total;
    } else {
        $('#tiempo_servicio').val('00:00');
        $('#tiempo_total_display').text('00:00 hrs');
        return '00:00';
    }
}

// ==========================================
// FUNCIONES DEL MODAL DE REPUESTOS
// ==========================================
function cerrarModalRepuestos() {
    $('#modalRepuestos').addClass('hidden').removeClass('flex');
    $('#select_repuesto_modal').val(null).trigger('change');
    $('#cantidad_repuesto_modal').val(1);
    $('#aviso_stock_info').addClass('hidden');
}

function agregarRepuesto() {
    let selectElement = $('#select_repuesto_modal');
    let idRep = selectElement.val();
    let optionSeleccionado = selectElement.find('option:selected');

    if (!idRep) {
        alert("Seleccione un repuesto de la lista.");
        return;
    }

    let nombreLimpio = optionSeleccionado.data('nombre');
    let origen = $('#select_origen_modal').val();
    let cant = parseInt($('#cantidad_repuesto_modal').val()) || 1;
    let tieneInventario = optionSeleccionado.data('en-inventario');
    let stockDisponible = parseInt(optionSeleccionado.data('stock')) || 0;

    if (cant <= 0) {
        alert("La cantidad debe ser mayor a 0.");
        return;
    }

    // Validar stock solo si tiene inventario
    if (tieneInventario == 1 && stockDisponible > 0) {
        let yaAgregadas = repuestosSeleccionados
            .filter(r => r.id == idRep)
            .reduce((sum, r) => sum + parseInt(r.cantidad), 0);

        let totalNecesario = yaAgregadas + cant;

        if (totalNecesario > stockDisponible) {
            Swal.fire({
                title: 'Stock insuficiente',
                html: 'Solo tienes <strong>' + stockDisponible + '</strong> unidades en inventario.<br>Ya agregaste <strong>' + yaAgregadas + '</strong> y estás intentando agregar <strong>' + cant + ' más</strong>.',
                icon: 'warning',
                confirmButtonText: 'Entendido'
            });
            return;
        }
    }

    let indexExiste = repuestosSeleccionados.findIndex(r => r.id === idRep && r.origen === origen);

    if (indexExiste !== -1) {
        repuestosSeleccionados[indexExiste].cantidad += cant;
    } else {
        repuestosSeleccionados.push({
            id: idRep,
            nombre: nombreLimpio,
            origen: origen,
            cantidad: cant,
            enInventario: (tieneInventario == 1)
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

        let badgeInventario = item.enInventario
            ? '<span class="bg-green-100 text-green-700 text-[10px] font-bold px-1 py-0.5 rounded ml-1 flex-shrink-0"><i class="fas fa-check"></i> Stock</span>'
            : '<span class="bg-yellow-100 text-yellow-700 text-[10px] font-bold px-1 py-0.5 rounded ml-1 flex-shrink-0"><i class="fas fa-exclamation"></i> Sin stock</span>';

        ul.append(`
            <li class="flex justify-between items-center bg-white p-2 border border-gray-200 rounded shadow-sm">
                <div class="flex items-center gap-1 overflow-hidden w-full">
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded ${bgBadge} border border-opacity-20 flex-shrink-0" style="min-width:60px; text-align:center">${item.origen}</span>
                    <span class="text-xs text-gray-700 font-medium truncate flex-grow">${item.nombre}</span>
                    ${badgeInventario}
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