<!-- Carga de FontAwesome y Google Fonts para mejor tipografía -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* Estilos personalizados para darle "Alma y Vida" */
    .asistencia-container {
        font-family: 'Inter', sans-serif;
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
    }

    .card-modern {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .card-header-modern {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        color: white;
        padding: 15px 20px;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .empleado-header {
        background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
        border-bottom: 2px solid #2a5298;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .table-modern {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
    }

    .table-modern thead th {
        background-color: #344767;
        color: #ffffff;
        font-weight: 500;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        padding: 12px;
        border: none;
    }

    .table-modern tbody tr {
        transition: all 0.2s ease;
    }

    .table-modern tbody tr:hover {
        background-color: #f8f9fa;
        transform: scale(1.002);
    }

    .table-modern td {
        vertical-align: middle;
        padding: 8px 12px;
        border-bottom: 1px solid #e9ecef;
    }

    .input-modern {
        border: 1px solid #ced4da;
        border-radius: 6px;
        padding: 6px 10px;
        font-size: 0.9rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        width: 100%;
    }

    .input-modern:focus {
        border-color: #2a5298;
        box-shadow: 0 0 0 0.2rem rgba(42, 82, 152, 0.25);
        outline: none;
    }

    .input-calc {
        background-color: #e9ecef;
        font-weight: 600;
        color: #495057;
        text-align: center;
    }

    .row-festivo {
        background-color: #fff3cd !important;
    }

    .badge-cargo {
        background-color: #e0e7ff;
        color: #3730a3;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .btn-gradient {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 600;
        transition: transform 0.2s;
    }

    .btn-gradient:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(17, 153, 142, 0.3);
        color: white;
    }
</style>

<div class="asistencia-container">
    <div class="card card-modern">
        <div class="card-header-modern">
            <h5 class="mb-0"><i class="fas fa-fingerprint me-2"></i>
                <?= $titulo ?? 'Procesador de Asistencias (Huellero + Servicios)' ?></h5>
        </div>
        <div class="card-body">
            <!-- Formulario de Subida -->
            <form id="formHuellero" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label for="archivo_huellero" class="form-label fw-bold text-secondary">Sube el archivo CSV del
                            reloj biométrico</label>
                        <input type="file" class="form-control form-control-lg" name="archivo_huellero"
                            id="archivo_huellero" accept=".csv" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100 py-2" id="btnProcesar"
                            style="background-color: #2a5298; border:none; border-radius: 8px;">
                            <i class="fas fa-cogs"></i> Procesar Datos
                        </button>
                    </div>
                </div>
            </form>

            <div id="alertaMensaje" class="alert d-none shadow-sm rounded" role="alert"></div>

            <!-- Contenedor de Edición en Vivo -->
            <div id="editorContainer" class="d-none">
                <hr class="my-4">
                <div
                    class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded shadow-sm border-start border-4 border-success">
                    <div>
                        <h4 class="text-success mb-1"><i class="fas fa-edit"></i> Edición de Nómina en Vivo</h4>
                        <p class="text-muted small mb-0">Los cálculos de horas se actualizan automáticamente. Puedes
                            sobrescribir cualquier valor antes de exportar.</p>
                    </div>
                    <button id="btnGuardarDescargar" class="btn btn-gradient">
                        <i class="fas fa-file-excel fs-5 me-2"></i> Exportar Consolidado
                    </button>
                </div>

                <!-- Aquí se inyectarán las tablas -->
                <div id="tablasEmpleados"></div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const urlProcesar = 'index.php?pagina=asistencia&accion=procesarArchivo';
        const urlGuardar = 'index.php?pagina=asistencia&accion=guardarEdicion';
        const urlDescargar = 'index.php?pagina=asistencia&accion=descargarExcel';

        // CONSTANTES DE LEY COLOMBIANA
        const TASA_HED = 0.25, TASA_HEN = 0.75, TASA_HEDDF = 1.00, TASA_HENDF = 1.50, DIVISOR_MES = 210;

        // --- UTILIDADES DE TIEMPO MATEMÁTICO ---
        function strToMins(t) {
            if (!t || t.includes('Falta')) return null;
            let [h, m] = t.split(':').map(Number);
            if (isNaN(h) || isNaN(m)) return null;
            return h * 60 + m;
        }

        function minsToStr(m) {
            if (m === null || isNaN(m) || m < 0) return "";
            let h = Math.floor(m / 60);
            let mins = Math.round(m % 60);
            return `${h.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
        }

        function formatoPlata(num) {
            return "$" + Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // --- MOTOR DE CÁLCULO PRINCIPAL ---
        function recalcularFila(tr, config) {
            const inEnt = tr.querySelector('.in-ent').value;
            const inSal = tr.querySelector('.in-sal').value;
            const esFestivo = tr.dataset.festivo === '1';
            const diaSemana = new Date(tr.dataset.fecha + 'T00:00:00').getDay(); // 0=Dom, 6=Sab

            const minEnt = strToMins(inEnt);
            let minSal = strToMins(inSal);

            // Referencias a celdas UI
            const outTotal = tr.querySelector('.out-total');
            const inHed = tr.querySelector('.in-hed');
            const inHen = tr.querySelector('.in-hen');
            const outValD = tr.querySelector('.out-val-d');
            const outValN = tr.querySelector('.out-val-n');

            if (minEnt === null || minSal === null) {
                outTotal.value = ''; inHed.value = ''; inHen.value = '';
                outValD.innerText = '$0'; outValN.innerText = '$0';
                return;
            }

            if (minSal < minEnt) minSal += 24 * 60; // Si cruza medianoche

            // 1. Efectuar el inicio del turno
            let inicioTurnoMins = config.inicioTurno === 'REAL' ? minEnt : (strToMins(config.inicioTurno) || minEnt);
            let efectivoEnt = Math.max(minEnt, inicioTurnoMins);

            // 2. Total trabajado (sin almuerzo restado, asumimos jornada continua según tu regla)
            let trabajadoMins = Math.max(0, minSal - efectivoEnt);
            outTotal.value = minsToStr(trabajadoMins);

            // 3. Límite del día
            let limiteMins = (diaSemana > 0 && diaSemana < 6) ? strToMins(config.lv) : strToMins(config.sab);

            // 4. Extras
            let extrasBruto = Math.max(0, trabajadoMins - limiteMins);
            let topeMins = strToMins(config.tope);
            let extrasTopeadas = Math.min(extrasBruto, topeMins);

            // 5. Distribución Diurna / Nocturna
            let nocturnaBase = strToMins(config.nocturna);
            let extrasNocturnas = Math.max(0, Math.min(extrasTopeadas, Math.max(0, minSal - nocturnaBase)));
            let extrasDiurnas = Math.max(0, extrasTopeadas - extrasNocturnas);

            // Escribir en inputs (permitiendo que el usuario los modifique después si quiere)
            // Solo sobrescribimos si el input no ha sido tocado manualmente (simplificado: siempre sobrescribe al cambiar entradas)
            inHed.value = minsToStr(extrasDiurnas);
            inHen.value = minsToStr(extrasNocturnas);

            calcularDineroFila(tr, config, esFestivo, extrasDiurnas, extrasNocturnas);
        }

        // Calcula el valor $ basado en las horas del input (por si el usuario lo editó a mano)
        function calcularDineroFila(tr, config, esFestivo, minD, minN) {
            let valHora = config.salario / DIVISOR_MES;

            let precioD = (minD / 60) * valHora * (esFestivo ? TASA_HEDDF : TASA_HED);
            let precioN = (minN / 60) * valHora * (esFestivo ? TASA_HENDF : TASA_HEN);

            tr.querySelector('.out-val-d').innerText = formatoPlata(precioD);
            tr.querySelector('.out-val-n').innerText = formatoPlata(precioN);
        }

        // --- MANEJO DE EVENTOS EN VIVO ---
        document.addEventListener('input', function (e) {
            if (!e.target.matches('.in-ent, .in-sal, .in-hed, .in-hen, .cfg-salario, .cfg-lv, .cfg-sab, .cfg-tope, .cfg-turno, .cfg-noct')) return;

            const card = e.target.closest('.empleado-card');

            // Construir config actual del empleado
            const config = {
                salario: parseFloat(card.querySelector('.cfg-salario').value) || 0,
                lv: card.querySelector('.cfg-lv').value,
                sab: card.querySelector('.cfg-sab').value,
                tope: card.querySelector('.cfg-tope').value,
                turno: card.querySelector('.cfg-turno').value,
                inicioTurno: card.querySelector('.cfg-turno').value.toUpperCase(), // puede ser 'REAL'
                nocturna: card.querySelector('.cfg-noct').value
            };

            // Si el cambio fue en un input global (salario, horario), recalcular toda la tabla
            if (e.target.classList.contains('cfg-input')) {
                card.querySelectorAll('tbody tr').forEach(tr => recalcularFila(tr, config));
            }
            // Si el cambio fue en horas extra a mano, solo recalcular el dinero
            else if (e.target.matches('.in-hed, .in-hen')) {
                const tr = e.target.closest('tr');
                const minD = strToMins(tr.querySelector('.in-hed').value) || 0;
                const minN = strToMins(tr.querySelector('.in-hen').value) || 0;
                calcularDineroFila(tr, config, tr.dataset.festivo === '1', minD, minN);
            }
            // Si el cambio fue en entrada/salida, recalcular la fila
            else {
                recalcularFila(e.target.closest('tr'), config);
            }
        });

        // --- RENDERIZADO DE TABLAS ---
        document.getElementById('formHuellero').addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('btnProcesar');
            const alert = document.getElementById('alertaMensaje');

            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            alert.classList.add('d-none');
            document.getElementById('editorContainer').classList.add('d-none');

            try {
                const resp = await fetch(urlProcesar, { method: 'POST', body: new FormData(this) });
                const data = JSON.parse(await resp.text());

                if (data.exito) {
                    renderizar(data.datos);
                    document.getElementById('editorContainer').classList.remove('d-none');
                } else {
                    alert.innerHTML = data.error; alert.classList.remove('d-none');
                }
            } catch (err) {
                alert.innerHTML = "Error crítico. Revisa F12."; alert.classList.remove('d-none');
            } finally {
                btn.innerHTML = '<i class="fas fa-upload"></i> Procesar';
            }
        });

        function renderizar(registros) {
            const contenedor = document.getElementById('tablasEmpleados');
            contenedor.innerHTML = '';

            // Agrupar
            const emps = {};
            registros.forEach(r => {
                if (!emps[r.nombre]) emps[r.nombre] = { cargo: r.cargo, registros: [] };
                emps[r.nombre].registros.push(r);
            });

            for (const [nombre, info] of Object.entries(emps)) {
                let esTecnico = info.cargo.toUpperCase().includes('TÉCNICO') || info.cargo.toUpperCase().includes('TECNICO');
                let defTurno = esTecnico ? 'REAL' : '07:00';

                let html = `
            <div class="empleado-card">
                <div class="empleado-header">
                    <h5 class="m-0"><i class="fas fa-user-tie me-2"></i>${nombre} <span class="badge-cargo ms-2">${info.cargo}</span></h5>
                </div>
                
                <!-- BARRA DE CONFIGURACIÓN DEL EMPLEADO (COMO EL ENCABEZADO DEL EXCEL) -->
                <div class="config-bar">
                    <div><i class="fas fa-money-bill-wave text-success"></i> Básico: <input type="number" class="config-input config-input-money cfg-input cfg-salario" value="1300000"></div>
                    <div><i class="fas fa-clock text-primary"></i> L-V: <input type="time" class="config-input cfg-input cfg-lv" value="09:00"></div>
                    <div>Sáb: <input type="time" class="config-input cfg-input cfg-sab" value="04:00"></div>
                    <div>Lím. Ext: <input type="time" class="config-input cfg-input cfg-tope" value="02:00"></div>
                    <div>Ini. Turno: <input type="text" class="config-input cfg-input cfg-turno" value="${defTurno}" title="Escribe REAL o una hora (07:00)"></div>
                    <div>Ini. Noct: <input type="time" class="config-input cfg-input cfg-noct" value="19:00"></div>
                </div>

                <div class="table-responsive">
                    <table class="table-nomina">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th class="col-hora">Entrada</th>
                                <th class="col-hora">Salida</th>
                                <th>Total Hrs</th>
                                <th class="col-extra">H.E. Diurna</th>
                                <th class="col-extra">H.E. Noct</th>
                                <th class="col-dinero">$ H.E. Diurna</th>
                                <th class="col-dinero">$ H.E. Noct</th>
                                <th>Serv</th>
                                <th>Novedades</th>
                            </tr>
                        </thead>
                        <tbody>`;

                info.registros.forEach(r => {
                    let clase = r.dom_fest == 1 ? 'row-festivo' : '';
                    html += `
                            <tr class="${clase}" data-id="${r.id}" data-fecha="${r.fecha_raw}" data-festivo="${r.dom_fest}">
                                <td class="text-start fw-bold">
                                    ${r.fecha_formateada} ${r.dom_fest == 1 ? '<i class="fas fa-star text-warning" title="Dominical/Festivo"></i>' : ''}
                                </td>
                                <td><input type="time" class="input-grid in-ent" value="${r.entrada_valor}"></td>
                                <td><input type="time" class="input-grid in-sal" value="${r.salida_valor}"></td>
                                <td><input type="text" class="input-grid input-readonly out-total" readonly></td>
                                
                                <td class="col-extra"><input type="time" class="input-grid in-hed"></td>
                                <td class="col-extra"><input type="time" class="input-grid in-hen"></td>
                                
                                <td class="col-dinero out-val-d">$0</td>
                                <td class="col-dinero out-val-n">$0</td>
                                
                                <td><input type="number" class="input-grid in-servicios" value="${r.servicios}"></td>
                                <td><input type="text" class="input-grid in-nov" value="${r.novedades}"></td>
                            </tr>`;
                });

                html += `</tbody></table></div></div>`;
                contenedor.innerHTML += html;
            }

            // Simular un evento input en los salarios para que se auto-calculen todas las tablas al cargar
            document.querySelectorAll('.cfg-salario').forEach(inp => {
                inp.dispatchEvent(new Event('input', { bubbles: true }));
            });
        }

        // --- RECOLECTAR Y ENVIAR AL SERVIDOR ---
        document.getElementById('btnGuardarDescargar').addEventListener('click', async function () {
            this.disabled = true; this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

            let payload = { salarios: {}, registros: [] };

            document.querySelectorAll('.empleado-card').forEach(card => {
                let nombre = card.querySelector('h5').innerText.trim().replace(card.querySelector('.badge-cargo').innerText, '').trim();
                payload.salarios[nombre] = card.querySelector('.cfg-salario').value;

                card.querySelectorAll('tbody tr').forEach(tr => {
                    payload.registros.push({
                        id: tr.dataset.id,
                        entrada_valor: tr.querySelector('.in-ent').value,
                        salida_valor: tr.querySelector('.in-sal').value,
                        // Si vas a capturar la edición manual de extras para el Excel backend:
                        hed_manual: tr.querySelector('.in-hed').value,
                        hen_manual: tr.querySelector('.in-hen').value,
                        servicios: tr.querySelector('.in-servicios').value,
                        novedades: tr.querySelector('.in-nov').value
                    });
                });
            });

            const fd = new URLSearchParams(); fd.append('datos', JSON.stringify(payload));

            try {
                const r = await fetch(urlGuardar, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: fd });
                const resp = JSON.parse(await r.text());
                if (resp.exito) window.location.href = urlDescargar;
                else alert(resp.error);
            } catch (e) {
                alert('Error enviando datos');
            } finally {
                this.disabled = false; this.innerHTML = '<i class="fas fa-file-excel me-2"></i> Confirmar y Descargar Excel';
            }
        });
    });
</script>