<style>
    .reporte-container {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        max-width: 900px;
        width: 100%;
        overflow: hidden;
        margin: 0 auto;
    }

    .reporte-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        text-align: center;
    }

    .reporte-header h1 {
        font-size: 24px;
        margin-bottom: 8px;
    }

    .reporte-header p {
        opacity: 0.9;
        font-size: 14px;
    }

    .reporte-content {
        padding: 30px;
    }

    .reporte-section {
        margin-bottom: 30px;
    }

    .reporte-section-title {
        font-size: 17px;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #667eea;
    }

    .reporte-date-inputs {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 20px;
    }

    .reporte-input-group {
        display: flex;
        flex-direction: column;
    }

    .reporte-input-group label {
        font-size: 14px;
        font-weight: 500;
        color: #34495e;
        margin-bottom: 8px;
    }

    .reporte-input-group input[type="date"] {
        padding: 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s;
    }

    .reporte-input-group input[type="date"]:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .reporte-sections-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 15px;
    }

    .reporte-card {
        border: 2px solid #e0e0e0;
        border-radius: 12px;
        padding: 15px;
        transition: all 0.3s;
        cursor: pointer;
        position: relative;
    }

    .reporte-card:hover {
        border-color: #667eea;
        background: #f8f9ff;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
    }

    .reporte-card.selected {
        border-color: #667eea;
        background: #f0f3ff;
    }

    .reporte-card input[type="checkbox"] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .reporte-card-header {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
    }

    .reporte-checkbox-custom {
        width: 22px;
        height: 22px;
        border: 2px solid #667eea;
        border-radius: 6px;
        margin-right: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
        flex-shrink: 0;
    }

    .reporte-card.selected .reporte-checkbox-custom {
        background: #667eea;
    }

    .reporte-checkbox-custom::after {
        content: '✓';
        color: white;
        font-size: 14px;
        font-weight: bold;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .reporte-card.selected .reporte-checkbox-custom::after {
        opacity: 1;
    }

    .reporte-card-title {
        font-weight: 600;
        color: #2c3e50;
        font-size: 14px;
        flex: 1;
    }

    .reporte-card-desc {
        font-size: 12px;
        color: #7f8c8d;
        line-height: 1.4;
    }

    .reporte-select-all-btn {
        background: #ecf0f1;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #34495e;
        cursor: pointer;
        transition: all 0.3s;
        margin-bottom: 15px;
    }

    .reporte-select-all-btn:hover {
        background: #d5dbdb;
    }

    .reporte-actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 2px solid #ecf0f1;
    }

    .reporte-btn {
        flex: 1;
        padding: 14px;
        border: none;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-decoration: none;
    }

    .reporte-btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }

    .reporte-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        color: white;
    }

    .reporte-btn-secondary {
        background: #ecf0f1;
        color: #34495e;
    }

    .reporte-btn-secondary:hover {
        background: #d5dbdb;
        color: #34495e;
    }

    .reporte-alert {
        background: #fff3cd;
        border: 1px solid #ffc107;
        color: #856404;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 13px;
        display: none;
    }

    .reporte-alert.show {
        display: block;
    }
</style>

<div class="reporte-container">

    <div class="reporte-header">
        <h1>📊 Configurar Reporte Ejecutivo</h1>
        <p>Selecciona el período y las secciones que deseas incluir</p>
    </div>

    <div class="reporte-content">
        <form id="reportForm" method="GET" action="<?= BASE_URL ?>generarReporte" target="_blank">

            <div class="reporte-section">
                <div class="reporte-section-title">📅 Período del Reporte</div>
                <div class="reporte-date-inputs">
                    <div class="reporte-input-group">
                        <label>Fecha Inicio</label>
                        <input type="date" name="inicio" id="fechaInicio" required>
                    </div>
                    <div class="reporte-input-group">
                        <label>Fecha Fin</label>
                        <input type="date" name="fin" id="fechaFin" required>
                    </div>
                </div>
            </div>

            <div id="reporteAlert" class="reporte-alert">
                ⚠️ Por favor selecciona al menos una sección para generar el reporte
            </div>

            <div class="reporte-section">
                <div class="reporte-section-title">📋 Secciones del Reporte</div>
                <button type="button" class="reporte-select-all-btn" onclick="reporteToggleAll()">
                    ✓ Seleccionar / Deseleccionar Todo
                </button>

                <div class="reporte-sections-grid">

                    <div class="reporte-card selected" onclick="reporteToggleSection(this)">
                        <input type="checkbox" name="secciones[]" value="portada" checked>
                        <div class="reporte-card-header">
                            <div class="reporte-checkbox-custom"></div>
                            <div class="reporte-card-title">📄 Portada</div>
                        </div>
                        <div class="reporte-card-desc">Resumen ejecutivo con KPIs principales</div>
                    </div>

                    <div class="reporte-card selected" onclick="reporteToggleSection(this)">
                        <input type="checkbox" name="secciones[]" value="delegaciones" checked>
                        <div class="reporte-card-header">
                            <div class="reporte-checkbox-custom"></div>
                            <div class="reporte-card-title">🏢 Delegaciones</div>
                        </div>
                        <div class="reporte-card-desc">Top delegaciones intervenidas</div>
                    </div>

                    <div class="reporte-card selected" onclick="reporteToggleSection(this)">
                        <input type="checkbox" name="secciones[]" value="tendencias" checked>
                        <div class="reporte-card-header">
                            <div class="reporte-checkbox-custom"></div>
                            <div class="reporte-card-title">📈 Tendencias</div>
                        </div>
                        <div class="reporte-card-desc">Evolución diaria de servicios</div>
                    </div>

                    <div class="reporte-card selected" onclick="reporteToggleSection(this)">
                        <input type="checkbox" name="secciones[]" value="mantenimiento" checked>
                        <div class="reporte-card-header">
                            <div class="reporte-checkbox-custom"></div>
                            <div class="reporte-card-title">🔧 Mantenimiento</div>
                        </div>
                        <div class="reporte-card-desc">Tipos de servicio y estados finales</div>
                    </div>

                    <div class="reporte-card selected" onclick="reporteToggleSection(this)">
                        <input type="checkbox" name="secciones[]" value="maquinas" checked>
                        <div class="reporte-card-header">
                            <div class="reporte-checkbox-custom"></div>
                            <div class="reporte-card-title">🏧 Tipos de Máquinas</div>
                        </div>
                        <div class="reporte-card-desc">Tipos de máquina por delegación</div>
                    </div>

                    <div class="reporte-card selected" onclick="reporteToggleSection(this)">
                        <input type="checkbox" name="secciones[]" value="estados" checked>
                        <div class="reporte-card-header">
                            <div class="reporte-checkbox-custom"></div>
                            <div class="reporte-card-title">✅ Estados Finales</div>
                        </div>
                        <div class="reporte-card-desc">Gráfica de operatividad y fallas</div>
                    </div>

                    <div class="reporte-card selected" onclick="reporteToggleSection(this)">
                        <input type="checkbox" name="secciones[]" value="puntos_atendidos" checked>
                        <div class="reporte-card-header">
                            <div class="reporte-checkbox-custom"></div>
                            <div class="reporte-card-title">📍 Cobertura Puntos</div>
                        </div>
                        <div class="reporte-card-desc">Matriz de puntos únicos atendidos</div>
                    </div>

                    <div class="reporte-card selected" onclick="reporteToggleSection(this)">
                        <input type="checkbox" name="secciones[]" value="puntos_fallidos" checked>
                        <div class="reporte-card-header">
                            <div class="reporte-checkbox-custom"></div>
                            <div class="reporte-card-title">⚠️ Puntos Más Visitados</div>
                        </div>
                        <div class="reporte-card-desc">
                            Puntos con mayor frecuencia de servicios.
                            <div style="margin-top: 10px; padding-top: 8px; border-top: 1px dashed #ccc; display: flex; align-items: center; gap: 5px;">
                                <label style="font-size: 11px; font-weight: bold; color: #555;">Mín. Visitas:</label>
                                <input type="number"
                                    name="min_visitas"
                                    value="2"
                                    min="1"
                                    style="width: 50px; padding: 2px 5px; border: 1px solid #999; border-radius: 4px; font-size: 12px;"
                                    onclick="event.stopPropagation()">
                            </div>
                        </div>
                    </div>

                    <div class="reporte-card selected" onclick="reporteToggleSection(this)">
                        <input type="checkbox" name="secciones[]" value="repuestos" checked>
                        <div class="reporte-card-header">
                            <div class="reporte-checkbox-custom"></div>
                            <div class="reporte-card-title">🔩 Repuestos</div>
                        </div>
                        <div class="reporte-card-desc">Gestión y origen de repuestos</div>
                    </div>

                    <div class="reporte-card selected" onclick="reporteToggleSection(this)">
                        <input type="checkbox" name="secciones[]" value="calificaciones" checked>
                        <div class="reporte-card-header">
                            <div class="reporte-checkbox-custom"></div>
                            <div class="reporte-card-title">⭐ Calificaciones</div>
                        </div>
                        <div class="reporte-card-desc">Satisfacción del cliente</div>
                    </div>

                    <div class="reporte-card selected" onclick="reporteToggleSection(this)">
                        <input type="checkbox" name="secciones[]" value="puntos_mas_fallidos" checked>
                        <div class="reporte-card-header">
                            <div class="reporte-checkbox-custom"></div>
                            <div class="reporte-card-title">⚠️ Puntos Fallidos</div>
                        </div>
                        <div class="reporte-card-desc">Puntos con servicios fallidos (2+ fallas)</div>
                    </div>

                    <div class="reporte-card selected" onclick="reporteToggleSection(this)">
                        <input type="checkbox" name="secciones[]" value="tecnicos" checked>
                        <div class="reporte-card-header">
                            <div class="reporte-checkbox-custom"></div>
                            <div class="reporte-card-title">👷 Técnicos</div>
                        </div>
                        <div class="reporte-card-desc">Productividad del equipo técnico</div>
                    </div>

                    <div class="reporte-card selected" onclick="reporteToggleSection(this)">
                        <input type="checkbox" name="secciones[]" value="costos" checked>
                        <div class="reporte-card-header">
                            <div class="reporte-checkbox-custom"></div>
                            <div class="reporte-card-title">🪙 Costos Operación</div>
                        </div>
                        <div class="reporte-card-desc">Costos Operación Motorizados</div>
                    </div>

                    <div class="reporte-card selected" onclick="reporteToggleSection(this)">
                        <input type="checkbox" name="secciones[]" value="balance" checked>
                        <div class="reporte-card-header">
                            <div class="reporte-checkbox-custom"></div>
                            <div class="reporte-card-title">💵 Balance Ingresos, Egresos</div>
                        </div>
                        <div class="reporte-card-desc">Resta de Egresos e Ingresos</div>
                    </div>

                </div>
            </div>

            <div class="reporte-actions">
                <button type="button" class="reporte-btn reporte-btn-secondary" onclick="window.history.back()">
                    ← Cancelar
                </button>
                <button type="submit" class="reporte-btn reporte-btn-primary">
                    📄 Generar Reporte PDF
                </button>
            </div>

        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const urlParams = new URLSearchParams(window.location.search);
        const inicioParam = urlParams.get('inicio');
        const finParam = urlParams.get('fin');

        if (inicioParam && finParam) {
            document.getElementById('fechaInicio').value = inicioParam;
            document.getElementById('fechaFin').value = finParam;
        } else {
            const hoy = new Date();
            const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
            const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
            document.getElementById('fechaInicio').valueAsDate = primerDia;
            document.getElementById('fechaFin').valueAsDate = ultimoDia;
        }
    });

    function reporteToggleSection(card) {
        card.classList.toggle('selected');
        const checkbox = card.querySelector('input[type="checkbox"]');
        checkbox.checked = card.classList.contains('selected');
    }

    function reporteToggleAll() {
        const cards = document.querySelectorAll('.reporte-card');
        const allSelected = Array.from(cards).every(card => card.classList.contains('selected'));
        cards.forEach(card => {
            if (allSelected) {
                card.classList.remove('selected');
                card.querySelector('input[type="checkbox"]').checked = false;
            } else {
                card.classList.add('selected');
                card.querySelector('input[type="checkbox"]').checked = true;
            }
        });
    }

    document.getElementById('reportForm').addEventListener('submit', function (e) {
        const checkboxes = document.querySelectorAll('input[name="secciones[]"]:checked');
        if (checkboxes.length === 0) {
            e.preventDefault();
            document.getElementById('reporteAlert').classList.add('show');
            setTimeout(() => {
                document.getElementById('reporteAlert').classList.remove('show');
            }, 3000);
        }
    });
</script>