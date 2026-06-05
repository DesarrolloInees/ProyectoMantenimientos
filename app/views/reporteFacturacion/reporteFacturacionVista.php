<div class="card shadow-lg border-0 rounded-lg mt-4">
    <div class="card-header bg-dark text-white p-4">
        <h3 class="mb-0"><i class="fas fa-filter text-info mr-2"></i> Generador Avanzado de Reportes</h3>
    </div>
    <div class="card-body p-5">

        <form action="index.php" method="GET" target="_blank">
            <input type="hidden" name="pagina" value="reporteFacturacion">
            <input type="hidden" name="accion" value="generarPdfFiltrado">

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold">Fecha Inicio</label>
                    <input type="date" class="form-control" name="f_ini">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold">Fecha Fin</label>
                    <input type="date" class="form-control" name="f_fin">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold">Categoría</label>
                    <select class="form-control" name="categoria">
                        <option value="">Todas</option>
                        <option value="MQ">Máquinas (MQ)</option>
                        <option value="RP">Repuestos (RP)</option>
                        <option value="SE">Servicios (SE)</option>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold">Estado</label>
                    <select class="form-control" name="estado">
                        <option value="">Todos</option>
                        <option value="FACTURADO">Facturado</option>
                        <option value="PENDIENTE">Pendiente</option>
                        <option value="REFACTURADA">Refacturada</option>
                        <option value="ANULADA">Anulada</option>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold">Precio Mínimo ($)</label>
                    <input type="number" class="form-control" name="p_min" placeholder="Ej: 50000">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold">Precio Máximo ($)</label>
                    <input type="number" class="form-control" name="p_max" placeholder="Ej: 5000000">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="font-weight-bold">N° Cotización / Remisión</label>
                    <input type="text" class="form-control" name="ref" placeholder="Ej: COT750 o REM7274">
                </div>
            </div>

            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary btn-lg px-5 rounded-pill shadow">
                    <i class="fas fa-file-pdf mr-2"></i> CREAR REPORTE PDF
                </button>
            </div>
        </form>

    </div>
</div>