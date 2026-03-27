<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    .rastreo-wrapper {
        background: #f8fafc;
        padding: 1.5rem;
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
</script>