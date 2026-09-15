<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    .rastreo-wrapper {
        background: #f8fafc;
        padding: 1.5rem;R
        border-radius: 12px;
    }

    .filtros-container {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        margin-bottom: 1.5rem;
        display: flex;
        gap: 1rem;
        align-items: flex-end;
        flex-wrap: wrap;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
        min-width: 250px;
    }

    .form-group label {
        font-size: 0.8rem;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
    }

    .form-control {
        padding: 0.6rem;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        outline: none;
        font-size: 0.95rem;
    }

    .btn-buscar {
        background: #2563eb;
        color: white;
        border: none;
        padding: 0.65rem 1.5rem;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
        transition: background 0.2s;
        height: 42px;
    }

    .btn-buscar:hover {
        background: #1d4ed8;
    }

    .btn-excel {
        background: #16a34a;
        color: white;
        border: none;
        padding: 0.65rem 1.5rem;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
        transition: background 0.2s;
        height: 42px;
    }

    .btn-excel:hover {
        background: #15803d;
    }

    .btn-excel:disabled {
        background: #86efac;
        cursor: not-allowed;
    }

    #mapaRastreo {
        height: 650px;
        width: 100%;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        z-index: 1;
    }

    /* Popups y Tooltips */
    .popup-custom strong {
        color: #1e3a5f;
        font-size: 1.1rem;
        display: block;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 5px;
        margin-bottom: 5px;
    }

    .popup-custom p {
        margin: 3px 0;
        font-size: 0.9rem;
    }

    .badge-estado {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: bold;
        color: white;
    }

    .bg-verde {
        background: #16a34a;
    }

    .bg-rojo {
        background: #dc2626;
    }

    .tec-tooltip {
        font-weight: bold;
        color: #1e3a5f;
        border: 1px solid #2563eb;
    }

    /* Ajuste Select2 */
    .select2-container .select2-selection--single {
        height: 42px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 40px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
    }

    .tramo-tooltip {
        background-color: #1e293b;
        color: white;
        border: none;
        border-radius: 4px;
        font-weight: 500;
        padding: 4px 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    /* La flechita del tooltip */
    .leaflet-tooltip.tramo-tooltip::before {
        border-top-color: #1e293b;
    }
    
</style>

<div class="rastreo-wrapper">
    <div class="filtros-container">
        <div class="form-group">
            <label>Fecha del recorrido</label>
            <input type="date" id="fechaRuta" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group" style="flex-grow: 1;">
            <label>Técnico</label>
            <select id="tecnicoRuta" class="form-control">
                <option value="todos">-- TODOS LOS TÉCNICOS --</option>
                <?php foreach ($tecnicos as $tec): ?>
                    <option value="<?= $tec['id_tecnico'] ?>"><?= htmlspecialchars($tec['nombre_tecnico']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn-buscar" onclick="cargarRuta()"><i class="fas fa-search"></i> Trazar Ruta</button>
        <button class="btn-excel" id="btnExcelPrimer" onclick="exportarExcelPrimerServicio()"><i class="fas fa-file-excel"></i> <span id="txtBtnExcelPrimer">Excel Primer Servicio</span></button>
    </div>

    <div id="panelInfoRuta" style="display: none; background: #fff; padding: 1rem; margin-bottom: 1rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #16a34a;">
        <h4 style="margin: 0; color: #1e3a5f; font-size: 1.1rem;"><i class="fas fa-route"></i> Resumen de Ruta</h4>
        <div id="resumenKilometros" style="margin-top: 0.5rem; font-size: 1rem; color: #475569;">
        </div>
    </div>

    <div id="mapaRastreo"></div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    let miMapa;
    let marcadoresLayer;
    let lineasLayer;

    // Últimos datos trazados: se reutilizan para el Excel (mismo lineamiento que exportarExcelVista / excel-export.js)
    let ultimaRuta = [];
    let ultimaFecha = '';

    // Paleta de colores para cuando eligen "Todos los técnicos"
    const coloresLineas = ['#2563eb', '#dc2626', '#16a34a', '#d97706', '#7c3aed', '#db2777', '#0891b2', '#ea580c'];

    $(document).ready(function() {
        // Inicializar Select2
        $('#tecnicoRuta').select2();

        miMapa = L.map('mapaRastreo').setView([4.6097, -74.0817], 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(miMapa);

        marcadoresLayer = L.layerGroup().addTo(miMapa);
        lineasLayer = L.layerGroup().addTo(miMapa);
    });

    function cargarRuta() {
        let idTecnico = $('#tecnicoRuta').val();
        let fecha = $('#fechaRuta').val();

        if (!idTecnico) {
            alert("Por favor seleccione un técnico.");
            return;
        }

        marcadoresLayer.clearLayers();
        lineasLayer.clearLayers();

        $.ajax({
            url: 'index.php?pagina=rastreoTecnico&accion=ajaxObtenerRuta',
            type: 'POST',
            data: {
                id_tecnico: idTecnico,
                fecha: fecha
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    ultimaRuta = res.data || [];
                    ultimaFecha = fecha;
                    dibujarRuta(res.data);
                } else {
                    alert(res.msj);
                    miMapa.setView([4.6097, -74.0817], 12);
                }
            },
            error: function() {
                alert("Error de conexión al obtener la ruta.");
            }
        });
    }

    function dibujarRuta(datos) {
        let limites = L.latLngBounds();
        // Vamos a agrupar los puntos por técnico para trazar líneas separadas
        let puntosPorTecnico = {};
        let colorIndex = 0;
        let kilometrosPorTecnico = {}; // Objeto para guardar la distancia de cada técnico

        const iconoInicio = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        const iconoFin = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        datos.forEach((servicio, index) => {
            let numServicio = index + 1;
            let nomTecnico = servicio.nombre_tecnico;

            if (!puntosPorTecnico[nomTecnico]) {
                puntosPorTecnico[nomTecnico] = [];
                kilometrosPorTecnico[nomTecnico] = 0; // Inicializar distancia
            }

            // 1. PIN VERDE (INICIO)
            if (servicio.latitud_inicio && servicio.longitud_inicio) {
                let latLngInicio = L.latLng(servicio.latitud_inicio, servicio.longitud_inicio); // Usamos L.latLng
                puntosPorTecnico[nomTecnico].push(latLngInicio);
                limites.extend(latLngInicio);

                let infoInicio = `<div class="popup-custom">
                                    <strong>#${numServicio} - ${servicio.nombre_cliente}</strong>
                                    <p><i class="fas fa-user-cog"></i> <b>${nomTecnico}</b></p>
                                    <p><i class="fas fa-store"></i> ${servicio.nombre_punto}</p>
                                    <p><span class="badge-estado bg-verde">INICIO SERVICIO</span></p>
                                    <p><i class="far fa-clock"></i> Hora entrada: ${servicio.hora_entrada || 'N/A'}</p>
                                </div>`;

                L.marker(latLngInicio, { icon: iconoInicio })
                    .bindPopup(infoInicio)
                    .bindTooltip(nomTecnico, {
                        className: 'tec-tooltip',
                        direction: 'top',
                        offset: [0, -40]
                    })
                    .addTo(marcadoresLayer);
            }

            // 2. PIN ROJO (FIN) CON DESPLAZAMIENTO ANTICHOQUE
            if (servicio.latitud_fin && servicio.longitud_fin) {
                let lonAjustada = parseFloat(servicio.longitud_fin) + 0.00010;
                let latLngFin = L.latLng(servicio.latitud_fin, lonAjustada); // Usamos L.latLng

                puntosPorTecnico[nomTecnico].push(latLngFin);
                limites.extend(latLngFin);

                let infoFin = `<div class="popup-custom">
                                    <strong>#${numServicio} - ${servicio.nombre_cliente}</strong>
                                    <p><i class="fas fa-user-cog"></i> <b>${nomTecnico}</b></p>
                                    <p><i class="fas fa-store"></i> ${servicio.nombre_punto}</p>
                                    <p><span class="badge-estado bg-rojo">FIN SERVICIO</span></p>
                                    <p><i class="far fa-clock"></i> Hora salida: ${servicio.hora_salida || 'N/A'}</p>
                                </div>`;

                L.marker(latLngFin, { icon: iconoFin })
                    .bindPopup(infoFin)
                    .bindTooltip(nomTecnico, {
                        className: 'tec-tooltip',
                        direction: 'top',
                        offset: [0, -40]
                    })
                    .addTo(marcadoresLayer);
            }
        });

        // Ocultar el panel de inicio y preparar el HTML del resumen
        let resumenHTML = '';
        let totalGeneralKM = 0;

        // 3. Dibujar las líneas y calcular la distancia por tramo
        for (const tec in puntosPorTecnico) {
            let coordenadas = puntosPorTecnico[tec];
            let distanciaTecnicoMetros = 0;

            if (coordenadas.length > 1) {
                let colorSeleccionado = coloresLineas[colorIndex % coloresLineas.length];

                // Recorremos los puntos de 2 en 2 para crear tramos individuales
                for (let i = 0; i < coordenadas.length - 1; i++) {
                    let puntoA = coordenadas[i];
                    let puntoB = coordenadas[i+1];
                    
                    // Calculamos la distancia solo de este segmento
                    let distanciaSegmentoMetros = puntoA.distanceTo(puntoB);
                    let distanciaSegmentoKM = (distanciaSegmentoMetros / 1000).toFixed(2);
                    
                    // Sumamos al total del técnico
                    distanciaTecnicoMetros += distanciaSegmentoMetros;

                    // Dibujamos la línea SOLO para este segmento
                    let segmentoLinea = L.polyline([puntoA, puntoB], {
                        color: colorSeleccionado,
                        weight: 4,
                        opacity: 0.8,
                        dashArray: '10, 10'
                    });

                    // Agregamos el texto emergente (tooltip) al pasar el cursor
                    segmentoLinea.bindTooltip(`<b>Tramo:</b> ${distanciaSegmentoKM} km`, {
                        sticky: true, // Hace que el tooltip siga al puntero del mouse
                        className: 'tramo-tooltip'
                    });

                    segmentoLinea.addTo(lineasLayer);
                }

                colorIndex++; // Siguiente color
            }

            // Convertir el total a kilómetros y redondear
            let distanciaKM = (distanciaTecnicoMetros / 1000).toFixed(2);
            totalGeneralKM += parseFloat(distanciaKM);
            
            // Construir el HTML para el panel de resumen
            resumenHTML += `<div style="margin-bottom: 5px;">
                                <strong>${tec}:</strong> ${distanciaKM} km
                            </div>`;
        }

        // Mostrar el resumen
        if (Object.keys(puntosPorTecnico).length > 0) {
            let idTecnicoSeleccionado = $('#tecnicoRuta').val();
            
            // Si hay varios técnicos mostramos el total general al final
            if (idTecnicoSeleccionado === 'todos') {
                resumenHTML += `<hr style="margin: 8px 0; border: 0; border-top: 1px solid #e2e8f0;">
                                <div><strong>Total Recorrido (Todos):</strong> ${totalGeneralKM.toFixed(2)} km</div>`;
            }

            $('#resumenKilometros').html(resumenHTML);
            $('#panelInfoRuta').show();

            // 4. Centrar mapa automático
            miMapa.fitBounds(limites, {
                padding: [50, 50]
            });
        } else {
            $('#panelInfoRuta').hide();
        }
    }

    // ─────────────────────────────────────────────────────────────
    // REPORTE EXCEL: Primer servicio por técnico + detalle de inicios
    // Lineamiento: SheetJS global (plantillaVista.php) igual que
    // exportarExcelVista.php / js/programacion/excel-export.js
    // Hoja 1 "Primer Servicio": 1 fila por técnico (el de la hora mínima)
    // Hoja 2 "Detalle": todos los inicios del día
    // Columnas: Fecha | Técnico | Punto | Hora Llegada | Observaciones (vacía para diligenciar)
    // ─────────────────────────────────────────────────────────────
    function exportarExcelPrimerServicio() {
        if (typeof XLSX === "undefined") {
            alert("Error: Librería SheetJS no cargada.");
            return;
        }

        if (!ultimaRuta || ultimaRuta.length === 0) {
            alert("Primero trace una ruta con el botón 'Trazar Ruta'.");
            return;
        }

        const btn = document.getElementById('btnExcelPrimer');
        const txt = document.getElementById('txtBtnExcelPrimer');
        const txtOriginal = txt ? txt.innerHTML : '';

        if (btn) btn.disabled = true;
        if (txt) txt.innerHTML = "<i class='fas fa-spinner fa-spin'></i> Generando...";

        try {
            // Los datos ya vienen ORDER BY id_tecnico, hora_entrada, así que
            // el primer registro con hora_entrada de cada técnico es su primer servicio.
            // Agrupamos defensivamente por si el orden cambia.
            const porTecnico = {};
            ultimaRuta.forEach(function(s) {
                const key = s.id_tecnico || s.nombre_tecnico || 'SIN_TECNICO';
                if (!porTecnico[key]) porTecnico[key] = [];
                porTecnico[key].push(s);
            });

            const horaAMinutos = function(h) {
                if (!h) return 99999;
                const partes = String(h).substring(0, 5).split(':');
                if (partes.length < 2) return 99999;
                const hh = parseInt(partes[0], 10);
                const mm = parseInt(partes[1], 10);
                if (isNaN(hh) || isNaN(mm)) return 99999;
                return hh * 60 + mm;
            };

            const normalizarHora = function(h) {
                if (!h) return '';
                return String(h).substring(0, 5); // "08:00:00" -> "08:00"
            };

            const filaExcel = function(s, fecha) {
                return {
                    "Fecha": fecha || s.fecha_visita || '',
                    "Técnico": s.nombre_tecnico || '',
                    "Cliente": s.nombre_cliente || '',
                    "Punto": s.nombre_punto || '',
                    "Hora Llegada": normalizarHora(s.hora_entrada),
                    "Observaciones": ''
                };
            };

            const resumen = [];
            const detalle = [];

            Object.keys(porTecnico).sort().forEach(function(key) {
                const servicios = porTecnico[key].slice().sort(function(a, b) {
                    return horaAMinutos(a.hora_entrada) - horaAMinutos(b.hora_entrada);
                });

                // Detalle: todos los inicios ordenados por hora
                servicios.forEach(function(s) {
                    detalle.push(filaExcel(s, ultimaFecha));
                });

                // Resumen: solo el primero con hora de entrada registrada
                const primero = servicios.find(function(s) { return s.hora_entrada; }) || servicios[0];
                if (primero) resumen.push(filaExcel(primero, ultimaFecha));
            });

            if (resumen.length === 0) {
                alert("No hay horas de entrada registradas para exportar.");
                return;
            }

            const wb = XLSX.utils.book_new();
            const wsResumen = XLSX.utils.json_to_sheet(resumen);
            const wsDetalle = XLSX.utils.json_to_sheet(detalle);

            const anchos = [
                { wch: 12 }, // Fecha
                { wch: 30 }, // Técnico
                { wch: 30 }, // Cliente
                { wch: 30 }, // Punto
                { wch: 14 }, // Hora Llegada
                { wch: 40 }  // Observaciones
            ];
            wsResumen['!cols'] = anchos;
            wsDetalle['!cols'] = anchos;

            XLSX.utils.book_append_sheet(wb, wsResumen, "Primer Servicio");
            XLSX.utils.book_append_sheet(wb, wsDetalle, "Detalle");

            const nombreArchivo = "Primer_Servicio_" + (ultimaFecha || new Date().toISOString().slice(0, 10)) + ".xlsx";
            XLSX.writeFile(wb, nombreArchivo);
        } catch (error) {
            console.error("Error al generar Excel:", error);
            alert("Hubo un error al generar el Excel.");
        } finally {
            if (btn) btn.disabled = false;
            if (txt) txt.innerHTML = txtOriginal || "Excel Primer Servicio";
        }
    }
</script>