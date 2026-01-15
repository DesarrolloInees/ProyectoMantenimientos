<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-xl mt-10">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">📊 Generador de Reportes Excel</h1>
        <p class="text-gray-500 mt-2">Selecciona el rango de fechas para descargar el consolidado.</p>
    </div>

    <form id="formReporte" class="space-y-6 border p-6 rounded bg-gray-50">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Fecha Inicio:</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio"
                    class="w-full p-3 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    value="<?= date('Y-m-01') ?>" required>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Fecha Fin:</label>
                <input type="date" id="fecha_fin" name="fecha_fin"
                    class="w-full p-3 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    value="<?= date('Y-m-d') ?>" required>
            </div>
        </div>

        <div class="flex justify-center gap-4 mt-6">
            <a href="<?= BASE_URL ?>inicio" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded transition">
                <i class="fas fa-arrow-left mr-2"></i> Volver
            </a>

            <button type="submit" id="btnDescargar" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded shadow-lg transform hover:scale-105 transition flex items-center">
                <i class="fas fa-file-excel mr-2 text-xl"></i>
                <span>Descargar Reporte</span>
            </button>
        </div>
    </form>

    <div id="mensajeEstado" class="hidden mt-4 p-4 rounded text-center font-bold"></div>
</div>

<script>
    $(document).ready(function() {

        // --- IMPORTAR LÓGICA DE DURACIÓN ---
        // (Pequeña función auxiliar para calcular horas si no vienen de la BD)
        function calcularDuracion(entrada, salida) {
            if (!entrada || !salida) return "00:00";
            let start = new Date("2000-01-01 " + entrada);
            let end = new Date("2000-01-01 " + salida);
            if (end < start) end.setDate(end.getDate() + 1);
            let diff = end - start;
            let hours = Math.floor(diff / 3600000);
            let minutes = Math.floor((diff % 3600000) / 60000);
            return String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
        }

        $('#formReporte').on('submit', function(e) {
            e.preventDefault();

            let fInicio = $('#fecha_inicio').val();
            let fFin = $('#fecha_fin').val();
            let btn = $('#btnDescargar');
            let msg = $('#mensajeEstado');

            // UI Loading
            btn.prop('disabled', true).addClass('opacity-50').html('<i class="fas fa-spinner fa-spin mr-2"></i> Procesando...');
            msg.removeClass('hidden bg-red-100 text-red-700 bg-green-100 text-green-700').text('');

            $.ajax({
                // ANTES DECÍA: url: '<?= BASE_URL ?>ordenDetalle',
                // AHORA DEBE DECIR (Para que llame a tu nuevo archivo):
                url: '<?= BASE_URL ?>ordenReporte',

                method: 'POST',
                data: {
                    accion: 'ajaxDescargarReporte', // Esto coincide con el IF del controlador
                    fecha_inicio: fInicio,
                    fecha_fin: fFin
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'ok') {
                        if (response.datos.length === 0) {
                            msg.addClass('bg-red-100 text-red-700').removeClass('hidden').text('No se encontraron registros en este rango.');
                        } else {
                            // 🔥 AQUÍ GENERAMOS EL EXCEL
                            generarExcelDesdeJSON(response.datos, fInicio, fFin);
                            msg.addClass('bg-green-100 text-green-700').removeClass('hidden').text('¡Reporte generado con éxito! (' + response.datos.length + ' registros)');
                        }
                    } else {
                        msg.addClass('bg-red-100 text-red-700').removeClass('hidden').text(response.msg);
                    }
                },
                error: function() {
                    msg.addClass('bg-red-100 text-red-700').removeClass('hidden').text('Error de conexión con el servidor.');
                },
                complete: function() {
                    btn.prop('disabled', false).removeClass('opacity-50').html('<i class="fas fa-file-excel mr-2 text-xl"></i> <span>Descargar Reporte</span>');
                }
            });
        });

        // ===============================================
        // 🔥 LÓGICA DE GENERACIÓN DE EXCEL (ADAPTADA)
        // ===============================================
        function generarExcelDesdeJSON(datos, inicio, fin) {
            let wb = XLSX.utils.book_new();
            let serviciosPorDelegacion = {};

            // 1. Agrupar datos por delegación
            datos.forEach(d => {
                let delegacion = d.delegacion || "SIN ASIGNAR";
                if (!serviciosPorDelegacion[delegacion]) {
                    serviciosPorDelegacion[delegacion] = [];
                }

                // Lógica de checks (Basico/Profundo/Correctivo)
                let txtServicio = (d.tipo_servicio || "").toLowerCase();
                let esPrevBasico = (txtServicio.includes("basico") || txtServicio.includes("básico")) ? "X" : "";
                let esPrevProfundo = (txtServicio.includes("profundo") || txtServicio.includes("completo")) ? "X" : "";
                let esCorrectivo = (txtServicio.includes("correctivo") || txtServicio.includes("reparacion")) ? "X" : "";

                // Fallback si dice solo preventivo
                if (!esPrevBasico && !esPrevProfundo && !esCorrectivo && txtServicio.includes("preventivo")) {
                    esPrevBasico = "X";
                }

                // Calcular Duración si viene vacía de BD
                let duracion = d.tiempo_servicio;
                if (!duracion || duracion === '00:00:00') {
                    duracion = calcularDuracion(d.hora_entrada, d.hora_salida);
                }

                // Preparar fila limpia
                serviciosPorDelegacion[delegacion].push({
                    device_id: d.device_id,
                    remision: d.numero_remision,
                    cliente: d.nombre_cliente,
                    punto: d.nombre_punto,
                    prev_basico: esPrevBasico,
                    prev_profundo: esPrevProfundo,
                    correctivo: esCorrectivo,
                    valor: parseFloat(d.valor_servicio) || 0,
                    obs: d.que_se_hizo, // Usamos 'que_se_hizo' como observación o puedes agregar columna Novedad
                    delegacion: delegacion,
                    fecha: d.fecha_visita,
                    tecnico: d.nombre_tecnico,
                    tipo_maquina: d.nombre_tipo_maquina,
                    tipo_servicio: d.tipo_servicio,
                    hora_entrada: d.hora_entrada,
                    hora_salida: d.hora_salida,
                    duracion: duracion,
                    repuestos: d.repuestos_texto,
                    estado: d.estado_maquina,
                    calificacion: d.nombre_calificacion,
                    modalidad: d.tipo_zona
                });
            });

            // 2. Crear hojas por delegación
            for (let del in serviciosPorDelegacion) {
                let filas = serviciosPorDelegacion[del];

                // Encabezados
                let matriz = [
                    [
                        "Device_id", "Número de Remisión", "Cliente", "Nombre Punto",
                        "Preventivo Básico", "Preventivo Profundo", "Correctivo", "Tarifa",
                        "Observaciones (Qué se hizo)", "Delegación", "Fecha", "Técnico",
                        "Tipo de Máquina", "Tipo de Servicio", "Hora Entrada", "Hora Salida",
                        "Duración", "Repuestos", "Estado", "Calificación", "Modalidad"
                    ]
                ];

                // Datos
                filas.forEach(f => {
                    matriz.push([
                        f.device_id, f.remision, f.cliente, f.punto,
                        f.prev_basico, f.prev_profundo, f.correctivo, f.valor,
                        f.obs, f.delegacion, f.fecha, f.tecnico,
                        f.tipo_maquina, f.tipo_servicio, f.hora_entrada, f.hora_salida,
                        f.duracion, f.repuestos, f.estado, f.calificacion, f.modalidad
                    ]);
                });

                let ws = XLSX.utils.aoa_to_sheet(matriz);

                // 3. Formato Moneda (Columna H -> índice 7)
                const formatoContabilidad = '_-"$"* #,##0_-;-"$"* #,##0_-;-"$"* "-"??_-;-_-@_-';
                const range = XLSX.utils.decode_range(ws['!ref']);
                const colTarifa = 7;

                for (let R = range.s.r + 1; R <= range.e.r; ++R) {
                    let cellRef = XLSX.utils.encode_cell({
                        c: colTarifa,
                        r: R
                    });
                    if (!ws[cellRef]) ws[cellRef] = {
                        t: 'n',
                        v: 0
                    }; // Crear celda si no existe
                    ws[cellRef].t = 'n';
                    ws[cellRef].z = formatoContabilidad;
                }

                // Ancho columnas
                ws["!cols"] = [{
                        wch: 15
                    }, {
                        wch: 12
                    }, {
                        wch: 25
                    }, {
                        wch: 25
                    },
                    {
                        wch: 8
                    }, {
                        wch: 8
                    }, {
                        wch: 8
                    }, {
                        wch: 12
                    },
                    {
                        wch: 40
                    }, {
                        wch: 15
                    }, {
                        wch: 12
                    }, {
                        wch: 20
                    },
                    {
                        wch: 15
                    }, {
                        wch: 20
                    }, {
                        wch: 10
                    }, {
                        wch: 10
                    },
                    {
                        wch: 10
                    }, {
                        wch: 30
                    }, {
                        wch: 15
                    }, {
                        wch: 15
                    }, {
                        wch: 15
                    }
                ];

                let nombreHoja = del.replace(/[:\\/?*\[\]]/g, "").substring(0, 30) || "Data";
                XLSX.utils.book_append_sheet(wb, ws, nombreHoja);
            }

            // 4. Descargar
            XLSX.writeFile(wb, `Reporte_Servicios_${inicio}_a_${fin}.xlsx`);
        }
    });
</script>