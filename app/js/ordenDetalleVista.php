<?php 
// Cabecera para que el navegador sepa que esto es Javascript
header("Content-type: application/javascript"); 
?>

<script>
    // ==========================================
// 1. DATOS MAESTROS (PHP -> JS)
// ==========================================
const catalogoRepuestos = <?= json_encode($listaRepuestos ?? []) ?>;

// Variables globales
let repuestosTemporales = [];
let paginaActual = 1;
const filasPorPagina = 6;
let totalFilas = 0;
let totalPaginas = 0;

// ==========================================
// 2. INICIALIZACIÓN
// ==========================================
document.addEventListener("DOMContentLoaded", function() {
    
    // Configurar Select2 del Modal
    $('#select_repuesto_modal').select2({
        width: '100%',
        dropdownParent: $('#modalRepuestos'),
        placeholder: "Buscar repuesto...",
        language: { noResults: () => "No encontrado" }
    });

    // Llenar Select2 una sola vez
    const select = document.getElementById('select_repuesto_modal');
    if(select) {
        catalogoRepuestos.forEach(r => {
            const option = new Option(r.nombre_repuesto, r.id_repuesto, false, false);
            select.add(option);
        });
    }

    // Cálculos iniciales
    calcularDesplazamientos();
    iniciarPaginacion();
});

// ==========================================
// 3. LÓGICA DEL MODAL DE REPUESTOS
// ==========================================

function abrirModalRepuestos(idFila) {
    document.getElementById('modal_fila_actual').value = idFila;

    // Recuperar JSON del input oculto
    const inputJson = document.getElementById(`input_json_${idFila}`);
    const valorActual = inputJson.value;

    try {
        // Si está vacío o es inválido, iniciamos array vacío
        repuestosTemporales = valorActual ? JSON.parse(valorActual) : [];
    } catch (e) {
        console.error("Error leyendo JSON de fila " + idFila, e);
        repuestosTemporales = [];
    }

    // Renderizar y mostrar
    renderizarListaVisual();
    
    // Resetear el select2
    $('#select_repuesto_modal').val(null).trigger('change');

    document.getElementById('modalRepuestos').classList.remove('hidden');
    document.getElementById('modalRepuestos').classList.add('flex');
}

function cerrarModal() {
    document.getElementById('modalRepuestos').classList.add('hidden');
    document.getElementById('modalRepuestos').classList.remove('flex');
}

function agregarRepuestoALista() {
    // Datos del Select2 (jQuery)
    const idRepuesto = $('#select_repuesto_modal').val();
    const dataSelect = $('#select_repuesto_modal').select2('data');
    const nombreRepuesto = dataSelect[0]?.text;
    const origen = document.getElementById('select_origen_modal').value;

    if (!idRepuesto) {
        alert("⚠️ Seleccione un repuesto de la lista.");
        return;
    }

    repuestosTemporales.push({
        id: idRepuesto,
        nombre: nombreRepuesto,
        origen: origen
    });

    $('#select_repuesto_modal').val(null).trigger('change');
    renderizarListaVisual();
}

function borrarRepuestoTemporal(index) {
    repuestosTemporales.splice(index, 1);
    renderizarListaVisual();
}

function renderizarListaVisual() {
    const ul = document.getElementById('lista_repuestos_visual');
    ul.innerHTML = '';

    if (repuestosTemporales.length === 0) {
        ul.innerHTML = '<li class="text-gray-400 text-center italic mt-10">No hay repuestos seleccionados.</li>';
        return;
    }

    repuestosTemporales.forEach((item, index) => {
        const colorOrigen = item.origen === 'INEES' ? 'text-blue-600 bg-blue-50' : 'text-orange-600 bg-orange-50';
        
        ul.innerHTML += `
            <li class="flex justify-between items-center bg-white p-2 mb-1 border rounded shadow-sm hover:bg-gray-50">
                <div class="text-xs flex items-center gap-2">
                    <span class="font-bold px-2 py-0.5 rounded ${colorOrigen} text-[10px]">${item.origen}</span>
                    <span class="text-gray-700">${item.nombre}</span>
                </div>
                <button type="button" onclick="borrarRepuestoTemporal(${index})" class="text-red-400 hover:text-red-600 px-2">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </li>
        `;
    });
}

function guardarCambiosModal() {
    const idFila = document.getElementById('modal_fila_actual').value;

    // 1. Guardar en el input oculto (ESTO ES LO QUE SE ENVÍA AL SERVIDOR)
    const inputJson = document.getElementById(`input_json_${idFila}`);
    inputJson.value = JSON.stringify(repuestosTemporales);

    // 2. Feedback visual en el botón
    const btnTexto = document.getElementById(`btn_texto_${idFila}`);
    const cantidad = repuestosTemporales.length;

    if (cantidad > 0) {
        btnTexto.innerText = `${cantidad} Items`;
        btnTexto.parentElement.classList.add('bg-blue-100', 'border-blue-400', 'text-blue-800');
    } else {
        btnTexto.innerText = "Gest. Repuestos";
        btnTexto.parentElement.classList.remove('bg-blue-100', 'border-blue-400', 'text-blue-800');
    }

    cerrarModal();
}

// ==========================================
// 4. CÁLCULOS Y AJAX
// ==========================================

function actualizarTarifa(idFila) {
    const inputValor = document.getElementById(`input_valor_${idFila}`);
    const selectMaquina = document.getElementById(`sel_maq_${idFila}`);
    const selectServicio = document.getElementById(`sel_servicio_${idFila}`);
    const selectModalidad = document.getElementById(`sel_modalidad_${idFila}`);

    if (!selectMaquina || !selectServicio || !selectModalidad) return;

    const opcionMaquina = selectMaquina.options[selectMaquina.selectedIndex];
    const idTipoMaquina = opcionMaquina ? opcionMaquina.getAttribute('data-idtipomaquina') : '';
    const idTipoMantenimiento = selectServicio.value;
    const idModalidad = selectModalidad.value;

    if (!idTipoMaquina || !idTipoMantenimiento) return;

    const formData = new FormData();
    formData.append('accion', 'ajaxObtenerPrecio');
    formData.append('id_tipo_maquina', idTipoMaquina);
    formData.append('id_tipo_mantenimiento', idTipoMantenimiento);
    formData.append('id_modalidad', idModalidad);

    fetch('index.php?pagina=ordenDetalle', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            let precio = data.precio || 0;
            inputValor.value = new Intl.NumberFormat('es-CO').format(precio);
            inputValor.style.backgroundColor = "#bbf7d0"; // Flash verde
            setTimeout(() => inputValor.style.backgroundColor = "", 500);
        })
        .catch(err => console.error(err));
}

function calcularDesplazamientos() {
        console.clear();
        console.log("--- INICIANDO CÁLCULO DE DESPLAZAMIENTOS (CORREGIDO) ---");

        let filas = Array.from(document.querySelectorAll('.fila-servicio'));

        // 1. Extraer datos crudos
        let datosCrudos = filas.map(fila => {
            let idFila = fila.id.replace('fila_', '');
            let selectTecnico = fila.querySelector(`select[name^="servicios"][name$="[id_tecnico]"]`);
            let tecnicoVal = selectTecnico ? selectTecnico.value : "0";

            let entrada = document.getElementById(`hora_entrada_${idFila}`).value;
            let salida = document.getElementById(`hora_salida_${idFila}`).value;

            return {
                idFila: idFila,
                tecnico: parseInt(tecnicoVal) || 0,
                horaEntradaTexto: entrada,
                horaSalidaTexto: salida,
                minutosEntrada: horaAMinutos(entrada),
                minutosSalida: horaAMinutos(salida)
            };
        });

        // ⭐⭐⭐ FILTRO NUEVO: ELIMINAR DUPLICADOS ⭐⭐⭐
        // Usamos un Mapa para dejar solo una copia de cada ID
        let datosUnicos = [];
        const map = new Map();
        for (const item of datosCrudos) {
            if (!map.has(item.idFila)) {
                map.set(item.idFila, true); // Marcamos como visto
                datosUnicos.push(item); // Lo guardamos
            }
        }
        let datos = datosUnicos;
        // ⭐⭐⭐ FIN DEL FILTRO ⭐⭐⭐

        // 2. Ordenar datos
        datos.sort((a, b) => {
            if (a.tecnico !== b.tecnico) return a.tecnico - b.tecnico;
            let minA = a.minutosEntrada !== null ? a.minutosEntrada : 99999;
            let minB = b.minutosEntrada !== null ? b.minutosEntrada : 99999;
            return minA - minB;
        });

        // 3. Comparar (El resto sigue igual...)
        for (let i = 0; i < datos.length; i++) {
            // ... (toda tu lógica de comparación que ya tenías)
            let actual = datos[i];
            let span = document.getElementById(`desplazamiento_${actual.idFila}`);
            if (!span) continue;

            // ... Pega aquí el resto del código del `for` que te pasé antes ...
            // (Si quieres te lo copio completo abajo para que solo sea copiar y pegar)

            // Reset visual
            span.className = "text-[10px] font-bold block";
            span.innerText = "-";

            if (i === 0 || datos[i - 1].tecnico !== actual.tecnico) {
                span.innerText = "00:00"; // Inicio
                span.classList.add("text-gray-400");
                continue;
            }

            let previo = datos[i - 1];

            if (actual.minutosEntrada === null || previo.minutosSalida === null) {
                span.innerText = "--";
                continue;
            }

            let diff = actual.minutosEntrada - previo.minutosSalida;

            if (diff < 0) {
                span.innerText = "Err H.";
                span.classList.add("text-red-500", "font-bold");
            } else {
                let h = Math.floor(diff / 60);
                let m = diff % 60;
                span.innerText = (h > 0 ? `${h}h ` : "") + `${m}m`; // Formato corto

                if (diff > 60) {
                    span.classList.add("text-red-600", "bg-red-100", "px-1", "rounded");
                } else {
                    span.classList.add("text-green-600");
                }
            }
        }
    }

    function horaAMinutos(hora) {
    if (!hora) return null;
    let partes = hora.split(':');
    if (partes.length < 2) return null;
    return (parseInt(partes[0]) * 60) + parseInt(partes[1]);
}

// Funciones AJAX Puntos y Maquinas
function cargarPuntos(idFila, idCliente) {
    let selPunto = document.getElementById(`sel_punto_${idFila}`);
    selPunto.innerHTML = '<option>Cargando...</option>';
    
    let fd = new FormData();
    fd.append('accion', 'ajaxObtenerPuntos');
    fd.append('id_cliente', idCliente);

    fetch('index.php?pagina=ordenDetalle', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            selPunto.innerHTML = '<option value="">- Seleccione -</option>';
            data.forEach(p => {
                selPunto.innerHTML += `<option value="${p.id_punto}" data-full="${p.nombre_punto}">${p.nombre_punto}</option>`;
            });
            if (data.length > 0) {
                selPunto.value = data[0].id_punto;
                cargarMaquinas(idFila, data[0].id_punto);
            }
        });
}

function cargarMaquinas(idFila, idPunto) {
    let selMaq = document.getElementById(`sel_maq_${idFila}`);
    selMaq.innerHTML = '<option>Cargando...</option>';

    // Delegación (AJAX 1)
    let divDel = document.getElementById(`td_delegacion_${idFila}`);
    let fdDel = new FormData();
    fdDel.append('accion', 'ajaxObtenerDelegacion');
    fdDel.append('id_punto', idPunto);
    fetch('index.php?pagina=ordenDetalle', { method: 'POST', body: fdDel })
        .then(r => r.json())
        .then(d => { if (divDel) divDel.innerText = d.delegacion; });

    // Máquinas (AJAX 2)
    let fd = new FormData();
    fd.append('accion', 'ajaxObtenerMaquinas');
    fd.append('id_punto', idPunto);
    fetch('index.php?pagina=ordenDetalle', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            selMaq.innerHTML = '<option value="">- Seleccione -</option>';
            data.forEach(m => {
                selMaq.innerHTML += `<option value="${m.id_maquina}" data-tipo="${m.nombre_tipo_maquina}" data-idtipomaquina="${m.id_tipo_maquina}">
                        ${m.device_id} (${m.nombre_tipo_maquina})
                    </option>`;
            });
            if (data.length > 0) {
                selMaq.value = data[0].id_maquina;
                actualizarTipoMaquina(idFila);
                actualizarTarifa(idFila);
            }
        });
}

function actualizarTipoMaquina(idFila) {
    let selMaq = document.getElementById(`sel_maq_${idFila}`);
    let divTipo = document.getElementById(`td_tipomaq_${idFila}`);
    if (selMaq.selectedIndex >= 0) {
        let opt = selMaq.options[selMaq.selectedIndex];
        if (divTipo) divTipo.innerText = opt.getAttribute('data-tipo') || '';
    }
}


// ==========================================
// 5. EXCEL LIMPIO (CORREGIDO)
// ==========================================
function exportarExcelLimpio() {
    if (typeof XLSX === 'undefined') {
        alert("Error: Librería SheetJS no cargada.");
        return;
    }

    let tabla = document.getElementById("tablaEdicion");
    let filas = Array.from(tabla.querySelectorAll('tbody tr'));
    let serviciosPorDelegacion = {};

    filas.forEach((fila, index) => {
        let celdas = fila.querySelectorAll('td');

        // Si la fila es de "No hay datos", ignorar
        if (celdas.length < 14) return;

        // ... (Lógica de extracción de textos igual que antes) ...
        let delegacionTxt = obtenerTextoDeDiv(celdas[1]);
        let tipoMaqTxt = obtenerTextoDeDiv(celdas[6]);

        // Lógica Preventivo/Correctivo
        let txtServicio = obtenerTexto(celdas[4]).toLowerCase();
        let esPrevBasico = txtServicio.includes('basico') || txtServicio.includes('básico');
        let esPrevProfundo = txtServicio.includes('profundo') || txtServicio.includes('completo');
        let esCorrectivo = txtServicio.includes('correctivo') || txtServicio.includes('reparacion');
        if (!esPrevBasico && !esPrevProfundo && !esCorrectivo && txtServicio.includes('preventivo')) esPrevBasico = true;

        // Duración y Repuestos
        let entrada = obtenerValorInput(celdas[10]);
        let salida = obtenerValorInput(celdas[11]);
        let duracionCalc = calcularDuracion(entrada, salida);

        // ⭐ CAPTURAR DESPLAZAMIENTO (Está en la columna índice 12)
        let desplazamientoTxt = celdas[12].innerText.trim();

        // ⭐ NUEVO: Limpiar "Err H." en Desplazamiento
        if (desplazamientoTxt.includes("Err H.")) {
            desplazamientoTxt = "";
        }

        let txtRepuestos = "";
        if (celdas[13]) {
            txtRepuestos = celdas[13].getAttribute('data-full') || celdas[13].innerText.trim();
        }

        // ⭐ NUEVO: Limpiar "Gest. Repuestos"
        if (txtRepuestos.includes("Gest. Repuestos")) {
            txtRepuestos = "";
        }

        // Limpieza estándar (sin, no, ningun, n/a)
        if (txtRepuestos.match(/(sin|no|ningun|n\/a)/i)) txtRepuestos = "";

        let datos = {
            device_id: obtenerTextoSelect(celdas[6]),
            remision: obtenerValorInput(celdas[9]),
            cliente: obtenerTextoSelect(celdas[0]),
            punto: obtenerTextoSelect(celdas[1]),
            esPrevBasico: esPrevBasico ? "X" : "",
            esPrevProfundo: esPrevProfundo ? "X" : "",
            esCorrectivo: esCorrectivo ? "X" : "",
            valor: obtenerValorInput(celdas[8]),
            obs: obtenerValorTextArea(celdas[7]),
            delegacion: delegacionTxt || "SIN ASIGNAR",
            fecha: obtenerValorInput(celdas[2]),
            tecnico: obtenerTextoSelect(celdas[3]),
            tipoMaquina: tipoMaqTxt,
            servicio: obtenerTextoSelect(celdas[4]),
            horaEntrada: entrada,
            horaSalida: salida,
            duracion: duracionCalc,
            desplazamiento: desplazamientoTxt, // Ya viene limpio si tenía Err H.
            repuestos: txtRepuestos,           // Ya viene limpio si tenía Gest. Repuestos
            estado: obtenerTextoSelect(celdas[14], 0),
            calificacion: obtenerTextoSelect(celdas[14], 1),
            modalidad: obtenerTextoSelect(celdas[5])
        };

        let keyDel = datos.delegacion;
        if (!serviciosPorDelegacion[keyDel]) {
            serviciosPorDelegacion[keyDel] = [];
        }
        serviciosPorDelegacion[keyDel].push(datos);
    });

    // CREAR EXCEL
    let workbook = XLSX.utils.book_new();
    let hayDatos = Object.keys(serviciosPorDelegacion).length > 0;

    if (!hayDatos) {
        alert("No hay datos válidos para exportar.");
        return;
    }

    for (let delegacion in serviciosPorDelegacion) {
        let lista = serviciosPorDelegacion[delegacion];

        // ⭐ AGREGAMOS LA COLUMNA 'Desplazamiento' EN LA MATRIZ
        let matriz = [
            [
                'Device_id', 'Número de Remisión', 'Cliente', 'Nombre Punto',
                'Preventivo Básico', 'Preventivo Profundo', 'Correctivo',
                'Tarifa', 'Observaciones', 'Delegación', 'Fecha', 'Técnico',
                'Tipo de Máquina', 'Tipo de Servicio', 'Hora Entrada', 'Hora Salida',
                'Duración', 'Desplazamiento', 'Repuestos', 'Estado de la Máquina',
                'Calificación del Servicio', 'Modalidad Operativa'
            ]
        ];

        lista.forEach(d => {
            matriz.push([
                d.device_id, d.remision, d.cliente, d.punto,
                d.esPrevBasico, d.esPrevProfundo, d.esCorrectivo,
                d.valor, d.obs, d.delegacion, d.fecha, d.tecnico,
                d.tipoMaquina, d.servicio, d.horaEntrada, d.horaSalida,
                d.duracion, d.desplazamiento, d.repuestos, d.estado,
                d.calificacion, d.modalidad
            ]);
        });

        let ws = XLSX.utils.aoa_to_sheet(matriz);
        
        // Ajustamos anchos de columna
        ws['!cols'] = [
            { wch: 15 }, { wch: 12 }, { wch: 25 }, { wch: 25 },
            { wch: 8 },  { wch: 8 },  { wch: 8 },  { wch: 12 },
            { wch: 35 }, { wch: 15 }, { wch: 12 }, { wch: 20 },
            { wch: 15 }, { wch: 20 }, { wch: 10 }, { wch: 10 },
            { wch: 10 }, { wch: 12 }, { wch: 30 }, { wch: 15 }, 
            { wch: 15 }, { wch: 15 }
        ];

        let nombreHoja = delegacion.replace(/[:\\/?*\[\]]/g, "").substring(0, 30) || "Hoja1";
        XLSX.utils.book_append_sheet(workbook, ws, nombreHoja);
    }

    // 5. DESCARGAR EL ARCHIVO
    let fecha = "<?= $_GET['fecha'] ?>";
    XLSX.writeFile(workbook, `${fecha}.xlsx`);
}

function obtenerTextoSelect(celda, index = 0) {
        if (!celda) return "";
        let selects = celda.querySelectorAll('select');

        if (selects && selects[index]) {
            let sel = selects[index];
            if (sel.selectedIndex >= 0) {
                let opcion = sel.options[sel.selectedIndex];
                // TRUCO: Si existe data-full, úsalo. Si no, usa el texto normal.
                return opcion.getAttribute('data-full') || opcion.text.trim();
            }
        }
        return celda.innerText.trim();
    }

    // Obtener value de input
    function obtenerValorInput(celda) {
        if (!celda) return ""; // BLINDAJE
        let input = celda.querySelector('input');
        return input ? input.value : "";
    }

    // Obtener value de textarea
    function obtenerValorTextArea(celda) {
        if (!celda) return ""; // BLINDAJE
        let txt = celda.querySelector('textarea');
        return txt ? txt.value : "";
    }

    // Obtener texto general
    function obtenerTexto(celda) {
        if (!celda) return ""; // BLINDAJE: ESTO CORRIGE TU ERROR ESPECÍFICO
        let el = celda.querySelector('input, textarea, select');
        if (el) {
            if (el.tagName === 'SELECT') return el.options[el.selectedIndex].text;
            return el.value;
        }
        return celda.innerText.trim();
    }

    // Obtener texto de un DIV oculto
    function obtenerTextoDeDiv(celda) {
        if (!celda) return "";
        let div = celda.querySelector('div');
        return div ? div.innerText.trim() : "";
    }

    function calcularDuracion(e, s) {
        if (!e || !s) return "";
        let mE = horaAMinutos(e);
        let mS = horaAMinutos(s);
        let diff = mS - mE;
        if (diff < 0) diff += 1440;
        let h = Math.floor(diff / 60);
        let m = (diff % 60).toString().padStart(2, '0');
        return `${h}:${m}`;
    }

    // ==========================================
    // 7. PAGINACIÓN
    // ==========================================
    function iniciarPaginacion() {
        const filas = document.querySelectorAll('#tablaEdicion tbody tr');
        if (filas.length <= 1 && filas[0].innerText.includes("No hay datos")) return;

        totalFilas = filas.length;
        document.getElementById('totalRegistros').innerText = totalFilas;
        totalPaginas = Math.ceil(totalFilas / filasPorPagina);
        mostrarPagina(paginaActual);
    }

    function cambiarPagina(dir) {
        let nueva = paginaActual + dir;
        if (nueva > 0 && nueva <= totalPaginas) {
            paginaActual = nueva;
            mostrarPagina(paginaActual);
        }
    }

    function mostrarPagina(pag) {
        let filas = document.querySelectorAll('#tablaEdicion tbody tr');
        let inicio = (pag - 1) * filasPorPagina;
        let fin = inicio + filasPorPagina;
        filas.forEach((tr, i) => {
            tr.style.display = (i >= inicio && i < fin) ? 'table-row' : 'none';
        });
        document.getElementById('indicadorPagina').innerText = `${pag} / ${totalPaginas}`;
        let finM = fin > totalFilas ? totalFilas : fin;
        document.getElementById('infoPagina').innerText = `${inicio + 1} - ${finM} de ${totalFilas}`;
    }
</script>