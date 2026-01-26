<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-xl mt-10">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">📊 Centro de Reportes</h1>
        <p class="text-gray-500 mt-2">Selecciona un rango de fechas y elige qué tipo de informe necesitas.</p>
    </div>

    <div class="space-y-6 border p-6 rounded bg-gray-50">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Fecha Inicio:</label>
                <input type="date" id="fecha_inicio"
                    class="w-full p-3 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    value="<?= date('Y-m-01') ?>">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Fecha Fin:</label>
                <input type="date" id="fecha_fin"
                    class="w-full p-3 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    value="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <hr class="border-gray-200">

        <div class="flex flex-col md:flex-row justify-center gap-6 mt-6">
            <button type="button" id="btnServicios" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-6 rounded-lg shadow-md transform hover:scale-105 transition flex flex-col items-center justify-center gap-2">
                <i class="fas fa-tools text-2xl"></i>
                <span>Reporte de Servicios</span>
                <span class="text-xs font-normal opacity-80">(Por Delegaciones)</span>
            </button>

            <button type="button" id="btnNovedades" class="flex-1 bg-orange-600 hover:bg-orange-700 text-white font-bold py-4 px-6 rounded-lg shadow-md transform hover:scale-105 transition flex flex-col items-center justify-center gap-2">
                <i class="fas fa-exclamation-triangle text-2xl"></i>
                <span>Reporte de Novedades</span>
                <span class="text-xs font-normal opacity-80">(Solo incidencias)</span>
            </button>
        </div>

        <div class="text-center mt-4">
            <a href="<?= BASE_URL ?>inicio" class="text-gray-500 hover:text-gray-700 underline text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Volver al inicio
            </a>
        </div>
    </div>

    <div id="mensajeEstado" class="hidden mt-4 p-4 rounded text-center font-bold"></div>
</div>

<script>
    $(document).ready(function() {

        // --- FUNCIÓN AUXILIAR PARA CALCULAR HORAS (Faltaba esta) ---
        function calcularDuracion(entrada, salida) {
            if (!entrada || !salida) return "00:00";
            let start = new Date("2000-01-01 " + entrada);
            let end = new Date("2000-01-01 " + salida);
            if (end < start) end.setDate(end.getDate() + 1); // Si pasa de medianoche
            let diff = end - start;
            let hours = Math.floor(diff / 3600000);
            let minutes = Math.floor((diff % 3600000) / 60000);
            return String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
        }

        // --- BOTÓN 1: SERVICIOS ---
        $('#btnServicios').on('click', function() {
            descargarData('ajaxDescargarServicios', 'servicios');
        });

        // --- BOTÓN 2: NOVEDADES ---
        $('#btnNovedades').on('click', function() {
            descargarData('ajaxDescargarNovedades', 'novedades');
        });

        // Función Genérica de Petición AJAX
        function descargarData(accion, tipo) {
            let fInicio = $('#fecha_inicio').val();
            let fFin = $('#fecha_fin').val();
            let msg = $('#mensajeEstado');
            let btnActivo = (tipo === 'servicios') ? $('#btnServicios') : $('#btnNovedades');
            let btnInactivo = (tipo === 'servicios') ? $('#btnNovedades') : $('#btnServicios');

            // UI Loading (Bloqueamos botones)
            btnInactivo.prop('disabled', true).addClass('opacity-50');
            btnActivo.prop('disabled', true).addClass('opacity-75').html('<i class="fas fa-spinner fa-spin text-2xl"></i><span>Generando...</span>');
            msg.removeClass('hidden bg-red-100 text-red-700 bg-green-100 text-green-700').text('');

            $.ajax({
                url: '<?= BASE_URL ?>ordenReporte',
                method: 'POST',
                data: {
                    accion: accion,
                    fecha_inicio: fInicio,
                    fecha_fin: fFin
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'ok') {
                        if (response.datos.length === 0) {
                            msg.addClass('bg-red-100 text-red-700').removeClass('hidden').text('No se encontraron registros en este rango.');
                        } else {
                            // AQUÍ ESTABA EL ERROR DE NOMBRE: Ahora llamamos a la función correcta
                            if (tipo === 'servicios') {
                                generarExcelServicios(response.datos, fInicio, fFin);
                            } else {
                                generarExcelNovedades(response.datos, fInicio, fFin);
                            }
                            msg.addClass('bg-green-100 text-green-700').removeClass('hidden').text('¡Descarga iniciada! (' + response.datos.length + ' registros)');
                        }
                    } else {
                        msg.addClass('bg-red-100 text-red-700').removeClass('hidden').text(response.msg);
                    }
                },
                error: function(xhr, status, error) {
                    console.error(error);
                    msg.addClass('bg-red-100 text-red-700').removeClass('hidden').text('Error de conexión.');
                },
                complete: function() {
                    // Restaurar botones a su estado original
                    $('#btnServicios').prop('disabled', false).removeClass('opacity-50 opacity-75').html('<i class="fas fa-tools text-2xl"></i><span>Reporte de Servicios</span><span class="text-xs font-normal opacity-80">(Por Delegaciones)</span>');
                    $('#btnNovedades').prop('disabled', false).removeClass('opacity-50 opacity-75').html('<i class="fas fa-exclamation-triangle text-2xl"></i><span>Reporte de Novedades</span><span class="text-xs font-normal opacity-80">(Solo incidencias)</span>');
                }
            });
        }

        // ===============================================
        // 🔥 LÓGICA DE GENERACIÓN DE EXCEL (ADAPTADA)
        // ===============================================
        // ===============================================
        // 1. EXCEL SERVICIOS (Complejo - Por Hojas)
        // ===============================================
        // IMPORTANTE: Le cambié el nombre de generarExcelDesdeJSON a generarExcelServicios
        function generarExcelServicios(datos, inicio, fin) {
            let wb = XLSX.utils.book_new();
            let serviciosPorDelegacion = {};

            // 1. Agrupar datos por delegación
            datos.forEach(d => {
                let delegacion = d.delegacion || "SIN ASIGNAR";
                if (!serviciosPorDelegacion[delegacion]) {
                    serviciosPorDelegacion[delegacion] = [];
                }

                // Lógica de checks
                let txtServicio = (d.txt_servicio || "").toLowerCase();
                let esPrevBasico = (txtServicio.includes("basico") || txtServicio.includes("básico")) ? "X" : "";
                let esPrevProfundo = (txtServicio.includes("profundo") || txtServicio.includes("completo")) ? "X" : "";
                let esCorrectivo = (txtServicio.includes("correctivo") || txtServicio.includes("reparacion")) ? "X" : "";

                if (!esPrevBasico && !esPrevProfundo && !esCorrectivo && txtServicio.includes("preventivo")) {
                    esPrevBasico = "X";
                }

                // Calcular Duración (Ahora sí funciona porque agregamos la función arriba)
                let duracion = d.tiempo_servicio;
                if (!duracion || duracion === '00:00:00') {
                    duracion = calcularDuracion(d.hora_entrada, d.hora_salida);
                }

                serviciosPorDelegacion[delegacion].push({
                    device_id: d.device_id,
                    remision: d.numero_remision,
                    cliente: d.nombre_cliente,
                    punto: d.nombre_punto,
                    prev_basico: esPrevBasico,
                    prev_profundo: esPrevProfundo,
                    correctivo: esCorrectivo,
                    valor: parseFloat(d.valor_servicio) || 0,
                    obs: d.que_se_hizo,
                    delegacion: delegacion,
                    fecha: d.fecha_visita,
                    tecnico: d.nombre_tecnico,
                    tipo_maquina: d.nombre_tipo_maquina,
                    tipo_servicio: d.txt_servicio,
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

                let matriz = [
                    [
                        "Device_id", "Número de Remisión", "Cliente", "Nombre Punto",
                        "Preventivo Básico", "Preventivo Profundo", "Correctivo", "Tarifa",
                        "Observaciones (Qué se hizo)", "Delegación", "Fecha", "Técnico",
                        "Tipo de Máquina", "Tipo de Servicio", "Hora Entrada", "Hora Salida",
                        "Duración", "Repuestos", "Estado", "Calificación", "Modalidad"
                    ]
                ];

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

                // Formato Moneda
                const formatoContabilidad = '_-"$"* #,##0_-;-"$"* #,##0_-;-"$"* "-"??_-;-_-@_-';
                if (ws['!ref']) {
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
                        };
                        ws[cellRef].t = 'n';
                        ws[cellRef].z = formatoContabilidad;
                    }
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
            XLSX.writeFile(wb, `Reporte_Servicios_${inicio}_a_${fin}.xlsx`);
        }


        // ==========================================
        // 2. EXCEL NOVEDADES (Plano - Nuevo Orden)
        // ==========================================
        function generarExcelNovedades(datos, inicio, fin) {
            let lista = datos.map(d => ({
                "Tipo de Novedad": d.nombre_novedad,
                "Observación / Qué se hizo": d.observacion,
                "Fecha": d.fecha_visita,
                "Técnico": d.nombre_tecnico,
                "Cliente": d.nombre_cliente,
                "Punto": d.nombre_punto,
                "Delegación": d.delegacion,
                "Device ID": d.device_id,
                "Tipo Máquina": d.nombre_tipo_maquina,
                "Remisión": d.numero_remision
            }));

            let wb = XLSX.utils.book_new();
            let ws = XLSX.utils.json_to_sheet(lista);

            ws["!cols"] = [{
                    wch: 30
                }, {
                    wch: 50
                }, {
                    wch: 12
                }, {
                    wch: 20
                },
                {
                    wch: 25
                }, {
                    wch: 25
                }, {
                    wch: 15
                }, {
                    wch: 15
                },
                {
                    wch: 20
                }, {
                    wch: 12
                }
            ];

            XLSX.utils.book_append_sheet(wb, ws, "Novedades");
            XLSX.writeFile(wb, `Novedades_${inicio}_a_${fin}.xlsx`);
        }
    });
</script>