<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<!-- Librerías de Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #mapaSeleccion { height: 600px; width: 100%; border-radius: 12px; z-index: 1; }
    .marcador-seleccionado { filter: hue-rotate(150deg); /* Cambia el color del icono por defecto a verde/azul */ }
</style>

<div class="w-full max-w-6xl mx-auto">
    <div class="bg-white p-6 rounded-xl shadow-md border border-indigo-200 mb-6">
        <h2 class="text-2xl font-bold text-indigo-800 mb-4">
            <i class="fas fa-map-marked-alt mr-2"></i> Sandbox: Asignación por Mapa
        </h2>

        <!-- Formulario Base -->
        <form method="GET" action="<?= BASE_URL ?>programacionMapa" id="formFiltros" class="mb-4">
            <input type="hidden" name="pagina" value="programacionMapa">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Delegación</label>
                    <select name="delegacion" onchange="document.getElementById('formFiltros').submit()" class="w-full border-gray-300 rounded-lg">
                        <option value="">-- Seleccione delegación --</option>
                        <?php foreach ($listaDelegaciones as $del): ?>
                            <option value="<?= $del['id_delegacion'] ?>" <?= $delegacionSeleccionada == $del['id_delegacion'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($del['nombre_delegacion']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if (!empty($delegacionSeleccionada)): ?>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Selecciona Zonas a Cargar</label>
                    <div class="border border-gray-300 rounded-lg p-2 bg-gray-50 h-32 overflow-y-auto">
                        <?php foreach ($listaZonas as $zona): ?>
                            <label class="flex items-center space-x-2 p-1 hover:bg-white cursor-pointer">
                                <input type="checkbox" name="zonas_mapa[]" value="<?= htmlspecialchars($zona) ?>" class="chk-zona rounded text-indigo-600">
                                <span class="text-sm font-semibold"><?= htmlspecialchars($zona) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($delegacionSeleccionada)): ?>
            <div class="mt-4 flex justify-between items-center">
                <button type="button" onclick="cargarPuntosAlMapa()" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-indigo-700">
                    <i class="fas fa-sync-alt mr-2"></i> Cargar al Mapa
                </button>
                <div class="text-sm font-bold text-gray-700">
                    Puntos seleccionados: <span id="contadorSeleccion" class="text-indigo-600 text-lg">0</span>
                </div>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Contenedor del Mapa -->
    <div class="bg-white p-2 rounded-xl shadow-lg border border-gray-300 relative">
        <div id="mapaSeleccion"></div>
        
        <!-- Botón Flotante para Confirmar -->
        <button type="button" onclick="confirmarSeleccion()" class="absolute bottom-6 right-6 z-[1000] bg-green-600 text-white px-6 py-3 rounded-full font-bold shadow-xl hover:bg-green-700 text-lg flex items-center transition transform hover:scale-105">
            <i class="fas fa-check-circle mr-2"></i> Guardar Ruta
        </button>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    let miMapa, marcadoresLayer;
    let puntosSeleccionados = new Set(); // Usamos Set para evitar IDs duplicados
    let delegacionActual = '<?= $delegacionSeleccionada ?>';

    // Iconos por defecto de Leaflet
    const IconoGris = new L.Icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-grey.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
    });

    const IconoVerde = new L.Icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
    });

    $(document).ready(function() {
        // Inicializar mapa centrado en Bogotá (o Colombia)
        miMapa = L.map('mapaSeleccion').setView([4.6097, -74.0817], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(miMapa);
        marcadoresLayer = L.featureGroup().addTo(miMapa);
    });

    function cargarPuntosAlMapa() {
        let zonasSeleccionadas = [];
        $('.chk-zona:checked').each(function() { zonasSeleccionadas.push($(this).val()); });

        if (zonasSeleccionadas.length === 0) {
            alert("Selecciona al menos una zona para cargar."); return;
        }

        // Limpiar mapa y selecciones previas
        marcadoresLayer.clearLayers();
        puntosSeleccionados.clear();
        actualizarContador();

        $.ajax({
            url: 'index.php?pagina=programacionMapa&accion=ajax_obtener_puntos',
            type: 'POST',
            data: { delegacion: delegacionActual, zonas: zonasSeleccionadas },
            dataType: 'json',
            success: function(res) {
                if (res.success && res.data.length > 0) {
                    dibujarPuntosDisponibles(res.data);
                } else {
                    alert("No hay puntos pendientes en estas zonas o no tienen coordenadas válidas.");
                }
            }
        });
    }

    function dibujarPuntosDisponibles(puntos) {
        let limites = L.latLngBounds();
        let puntosSinCoordenadas = 0;

        puntos.forEach(p => {
            // Validar que la latitud y longitud existan y no sean cero
            if (p.latitud && p.longitud && p.latitud != 0 && p.longitud != 0) {
                let latLng = L.latLng(p.latitud, p.longitud);
                limites.extend(latLng);

                let marker = L.marker(latLng, { icon: IconoGris, id_punto: p.id_punto }).addTo(marcadoresLayer);
                
                // Tooltip básico al pasar el mouse
                marker.bindTooltip(`<b>${p.nombre_punto}</b><br>${p.nombre_cliente}`, { direction: 'top', offset: [0, -35] });

                // Evento Clic: Alternar selección
                marker.on('click', function(e) {
                    let m = e.target;
                    let idP = m.options.id_punto;

                    if (puntosSeleccionados.has(idP)) {
                        puntosSeleccionados.delete(idP);
                        m.setIcon(IconoGris); // Deseleccionado
                    } else {
                        puntosSeleccionados.add(idP);
                        m.setIcon(IconoVerde); // Seleccionado
                    }
                    actualizarContador();
                });
            } else {
                puntosSinCoordenadas++;
            }
        });

        if (puntosSinCoordenadas > 0) {
            console.warn(`${puntosSinCoordenadas} puntos omitidos por no tener coordenadas registradas.`);
        }

        if (marcadoresLayer.getLayers().length > 0) {
            miMapa.fitBounds(limites, { padding: [40, 40] });
        }
    }

    function actualizarContador() {
        $('#contadorSeleccion').text(puntosSeleccionados.size);
    }

    function confirmarSeleccion() {
        if (puntosSeleccionados.size === 0) {
            alert("No has seleccionado ningún punto en el mapa."); return;
        }

        // Aquí transformamos el Set a un Array para enviarlo a donde queramos
        let idsSeleccionados = Array.from(puntosSeleccionados);
        
        // Simulación: Imprimir en consola y mostrar alerta. 
        // En el futuro, esto inyectará inputs en tu modal de programación semanal.
        console.log("IDs listos para programar:", idsSeleccionados);
        alert("¡Excelente! Has seleccionado " + idsSeleccionados.length + " puntos. \nIDs: " + idsSeleccionados.join(', '));
    }
</script>