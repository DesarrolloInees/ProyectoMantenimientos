// ==========================================
// VARIABLES GLOBALES
// ==========================================
let repuestosSeleccionados = [];
let canvas, ctx;
let dibujando = false;
let firmaVacia = true;

// ==========================================
// INICIALIZACIÓN CUANDO CARGA LA PÁGINA
// ==========================================
$(document).ready(function() {
    // 1. Inicializar Select2
    $('.select2-movil').select2({
        width: '100%',
        minimumResultsForSearch: 8
    });

    // 2. Eventos de cálculo de tiempo
    $('#hora_entrada, #hora_salida').on('change', calcularTiempoServicio);

    // 3. Eventos de los inputs de fotos
    $('#fotos_antes, #foto_remision, #fotos_despues').on('change', function() {
        let numFiles = this.files ? this.files.length : 0;
        let targetBadge = '';
        let targetPreview = '';

        if (this.id === 'fotos_antes') {
            targetBadge = '#badge_fotos_antes';
            targetPreview = '#preview_antes';
        }
        if (this.id === 'foto_remision') {
            targetBadge = '#badge_foto_remision';
            targetPreview = '#preview_remision';
        }
        if (this.id === 'fotos_despues') {
            targetBadge = '#badge_fotos_despues';
            targetPreview = '#preview_despues';
        }

        if (numFiles > 0) {
            $(targetBadge).removeClass('bg-gray-200 text-gray-700').addClass('bg-indigo-100 text-indigo-800').text(numFiles + ' seleccionadas');
        } else {
            $(targetBadge).removeClass('bg-indigo-100 text-indigo-800').addClass('bg-gray-200 text-gray-700').text('0 seleccionadas');
        }

        let previewContainer = $(targetPreview);
        previewContainer.empty();

        if (this.files) {
            $.each(this.files, function(index, file) {
                if (file.type.match('image.*')) {
                    let reader = new FileReader();
                    reader.onload = function(e) {
                        let imgHtml = '<div class="relative w-16 h-16 rounded-md overflow-hidden border border-gray-300 shadow-sm">' +
                            '<img src="' + e.target.result + '" class="w-full h-full object-cover"></div>';
                        previewContainer.append(imgHtml);
                    }
                    reader.readAsDataURL(file);
                }
            });
        }
        calcularTotalFotos();
    });

    // 4. Inicializar Modal de Repuestos
    $('#btn_abrir_repuestos').on('click', function(e) {
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
// FUNCIONES DE TIEMPO Y FOTOS
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

function calcularTotalFotos() {
    let fAntes = document.getElementById('fotos_antes').files.length;
    let fRemision = document.getElementById('foto_remision').files.length; 
    let fDespues = document.getElementById('fotos_despues').files.length;
    let total = fAntes + fRemision + fDespues;
    let totalElement = $('#total_fotos_count');
    totalElement.text(total);

    if (total >= 8 && total <= 10) {
        totalElement.removeClass('text-red-600 text-orange-500').addClass('text-green-600');
    } else if (total > 0 && total < 8) {
        totalElement.removeClass('text-red-600 text-green-600').addClass('text-orange-500');
    } else {
        totalElement.removeClass('text-green-600 text-orange-500').addClass('text-red-600');
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

// ==========================================
// FUNCIÓN FINAL DE ENVÍO
// ==========================================
function validarYEnviar() {
    let form = document.getElementById('formReporteMovil');
    let fAntes = document.getElementById('fotos_antes').files.length;
    let fRemision = document.getElementById('foto_remision').files.length; 
    let fDespues = document.getElementById('fotos_despues').files.length;
    let totalFotos = fAntes + fRemision + fDespues;

    // Validar fotos
    if (totalFotos < 8 || totalFotos > 10) {
        alert("⚠️ Por favor seleccione entre 8 y 10 fotos en total de evidencia.\nActualmente ha seleccionado: " + totalFotos);
        return false;
    }

    // Validar firma
    if (firmaVacia) {
        alert("⚠️ El cliente debe firmar el reporte antes de guardar.");
        return false;
    } else {
        document.getElementById('firma_base64').value = canvas.toDataURL('image/png');
    }

    if (form.checkValidity()) {
        // ---> NUEVO: Borramos la memoria local justo antes de enviar el formulario
        if (typeof limpiarBorradorStorage === 'function') {
            limpiarBorradorStorage(); 
        }
        
        form.submit();
    } else {
        form.reportValidity();
    }
}