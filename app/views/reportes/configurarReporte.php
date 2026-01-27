<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Reporte Ejecutivo</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 900px;
            width: 100%;
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .content {
            padding: 30px;
        }

        .section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .date-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .input-group {
            display: flex;
            flex-direction: column;
        }

        .input-group label {
            font-size: 14px;
            font-weight: 500;
            color: #34495e;
            margin-bottom: 8px;
        }

        .input-group input {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .input-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .sections-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .section-card {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 15px;
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
        }

        .section-card:hover {
            border-color: #667eea;
            background: #f8f9ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }

        .section-card.selected {
            border-color: #667eea;
            background: #f0f3ff;
        }

        .section-card input[type="checkbox"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .section-card-header {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .checkbox-custom {
            width: 22px;
            height: 22px;
            border: 2px solid #667eea;
            border-radius: 6px;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .section-card.selected .checkbox-custom {
            background: #667eea;
        }

        .checkbox-custom::after {
            content: '✓';
            color: white;
            font-size: 14px;
            font-weight: bold;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .section-card.selected .checkbox-custom::after {
            opacity: 1;
        }

        .section-card-title {
            font-weight: 600;
            color: #2c3e50;
            font-size: 15px;
            flex: 1;
        }

        .section-card-desc {
            font-size: 12px;
            color: #7f8c8d;
            line-height: 1.4;
        }

        .select-all-btn {
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

        .select-all-btn:hover {
            background: #d5dbdb;
        }

        .actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #ecf0f1;
        }

        .btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        .btn-secondary {
            background: #ecf0f1;
            color: #34495e;
        }

        .btn-secondary:hover {
            background: #d5dbdb;
        }

        .alert {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            display: none;
        }

        .alert.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Configurar Reporte Ejecutivo</h1>
            <p>Selecciona el período y las secciones que deseas incluir</p>
        </div>

        <div class="content">
            <form id="reportForm" method="GET" action="<?= BASE_URL ?>generarReporte" target="_blank">
                
                <div class="section">
                    <div class="section-title">📅 Período del Reporte</div>
                    <div class="date-inputs">
                        <div class="input-group">
                            <label>Fecha Inicio</label>
                            <input type="date" name="inicio" id="fechaInicio" required>
                        </div>
                        <div class="input-group">
                            <label>Fecha Fin</label>
                            <input type="date" name="fin" id="fechaFin" required>
                        </div>
                    </div>
                </div>

                <div id="alert" class="alert">
                    ⚠️ Por favor selecciona al menos una sección para generar el reporte
                </div>

                <div class="section">
                    <div class="section-title">📋 Secciones del Reporte</div>
                    <button type="button" class="select-all-btn" onclick="toggleAll()">
                        ✓ Seleccionar / Deseleccionar Todo
                    </button>
                    
                    <div class="sections-grid">
                        <div class="section-card selected" onclick="toggleSection(this)">
                            <input type="checkbox" name="secciones[]" value="portada" checked>
                            <div class="section-card-header">
                                <div class="checkbox-custom"></div>
                                <div class="section-card-title">📄 Portada</div>
                            </div>
                            <div class="section-card-desc">Resumen ejecutivo con KPIs principales</div>
                        </div>

                        <div class="section-card selected" onclick="toggleSection(this)">
                            <input type="checkbox" name="secciones[]" value="tendencias" checked>
                            <div class="section-card-header">
                                <div class="checkbox-custom"></div>
                                <div class="section-card-title">📈 Tendencias</div>
                            </div>
                            <div class="section-card-desc">Evolución diaria de servicios</div>
                        </div>

                        <div class="section-card selected" onclick="toggleSection(this)">
                            <input type="checkbox" name="secciones[]" value="mantenimiento" checked>
                            <div class="section-card-header">
                                <div class="checkbox-custom"></div>
                                <div class="section-card-title">🔧 Mantenimiento</div>
                            </div>
                            <div class="section-card-desc">Tipos de servicio y estados finales</div>
                        </div>

                        <div class="section-card selected" onclick="toggleSection(this)">
                            <input type="checkbox" name="secciones[]" value="maquinas" checked>
                            <div class="section-card-header">
                                <div class="checkbox-custom"></div>
                                <div class="section-card-title">🏧 Matriz Máquinas</div>
                            </div>
                            <div class="section-card-desc">Tipos de máquina por delegación</div>
                        </div>

                        <div class="section-card selected" onclick="toggleSection(this)">
                            <input type="checkbox" name="secciones[]" value="delegaciones" checked>
                            <div class="section-card-header">
                                <div class="checkbox-custom"></div>
                                <div class="section-card-title">🏢 Delegaciones</div>
                            </div>
                            <div class="section-card-desc">Top delegaciones intervenidas</div>
                        </div>

                        <div class="section-card selected" onclick="toggleSection(this)">
                            <input type="checkbox" name="secciones[]" value="tecnicos" checked>
                            <div class="section-card-header">
                                <div class="checkbox-custom"></div>
                                <div class="section-card-title">👷 Técnicos</div>
                            </div>
                            <div class="section-card-desc">Productividad del equipo técnico</div>
                        </div>

                        <div class="section-card selected" onclick="toggleSection(this)">
                            <input type="checkbox" name="secciones[]" value="repuestos" checked>
                            <div class="section-card-header">
                                <div class="checkbox-custom"></div>
                                <div class="section-card-title">🔩 Repuestos</div>
                            </div>
                            <div class="section-card-desc">Gestión y origen de repuestos</div>
                        </div>

                        <div class="section-card selected" onclick="toggleSection(this)">
                            <input type="checkbox" name="secciones[]" value="puntos_fallidos" checked>
                            <div class="section-card-header">
                                <div class="checkbox-custom"></div>
                                <div class="section-card-title">⚠️ Puntos Críticos</div>
                            </div>
                            <div class="section-card-desc">Puntos con servicios fallidos (2+ fallas)</div>
                        </div>

                        <div class="section-card selected" onclick="toggleSection(this)">
                            <input type="checkbox" name="secciones[]" value="calificaciones" checked>
                            <div class="section-card-header">
                                <div class="checkbox-custom"></div>
                                <div class="section-card-title">⭐ Calificaciones</div>
                            </div>
                            <div class="section-card-desc">Satisfacción del cliente</div>
                        </div>

                    </div>
                </div>

                <div class="actions">
                    <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                        ← Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        📄 Generar Reporte PDF
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Establecer fechas por defecto o desde la URL
        document.addEventListener('DOMContentLoaded', function() {
            // Obtener parámetros de la URL
            const urlParams = new URLSearchParams(window.location.search);
            const inicioParam = urlParams.get('inicio');
            const finParam = urlParams.get('fin');
            
            // Si vienen fechas en la URL, usarlas
            if (inicioParam && finParam) {
                document.getElementById('fechaInicio').value = inicioParam;
                document.getElementById('fechaFin').value = finParam;
            } else {
                // Si no, usar el mes actual
                const hoy = new Date();
                const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
                const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
                
                document.getElementById('fechaInicio').valueAsDate = primerDia;
                document.getElementById('fechaFin').valueAsDate = ultimoDia;
            }
        });

        function toggleSection(card) {
            card.classList.toggle('selected');
            const checkbox = card.querySelector('input[type="checkbox"]');
            checkbox.checked = card.classList.contains('selected');
        }

        function toggleAll() {
            const cards = document.querySelectorAll('.section-card');
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

        document.getElementById('reportForm').addEventListener('submit', function(e) {
            const checkboxes = document.querySelectorAll('input[name="secciones[]"]:checked');
            if (checkboxes.length === 0) {
                e.preventDefault();
                document.getElementById('alert').classList.add('show');
                setTimeout(() => {
                    document.getElementById('alert').classList.remove('show');
                }, 3000);
            }
        });
    </script>
</body>
</html>