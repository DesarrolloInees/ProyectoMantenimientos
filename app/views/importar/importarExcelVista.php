<div class="row justify-content-center">

    <div class="col-md-10">

        <div class="card card-primary card-outline shadow-lg">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-file-excel mr-2 text-success"></i>
                    Importación Masiva de Instalaciones
                </h3>
            </div>

            <div class="card-body">

                <div class="alert alert-info alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-info"></i> Instrucciones:</h5>
                    <ul class="mb-0 small">
                        <li>El archivo debe ser formato <b>.xlsx</b> (Excel).</li>
                        <li>El sistema busca la hoja llamada <b>"INSTALADAS CT"</b> (si no existe, toma la primera).</li>
                        <li>Se valida automáticamente por <b>Device ID</b>. Si ya existe, se omite.</li>
                        <li>Clientes y Puntos nuevos se crean automáticamente.</li>
                    </ul>
                </div>

                <form id="formImportar" action="index.php?pagina=importarExcel&accion=procesar" method="POST" enctype="multipart/form-data">

                    <div class="form-group text-center p-5 border-dashed rounded" style="border: 2px dashed #ced4da; background-color: #f8f9fa;">
                        <label for="archivo_excel" style="cursor: pointer; width: 100%;">
                            <i class="fas fa-cloud-upload-alt fa-4x text-primary mb-3"></i>
                            <h5 class="text-muted">Arrastra tu archivo aquí o haz clic para buscar</h5>
                            <span id="nombre_archivo" class="badge badge-secondary p-2 mt-2 d-none">Ningún archivo seleccionado</span>
                        </label>
                        <input type="file" class="d-none" id="archivo_excel" name="archivo_excel" accept=".xlsx, .xls" required onchange="mostrarNombreArchivo()">
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-success btn-lg px-5 font-weight-bold shadow" onclick="activarLoading()">
                            <i class="fas fa-upload mr-2"></i> ANALIZAR E IMPORTAR
                        </button>
                    </div>

                </form>
            </div>

            <div class="card-footer text-muted text-center">
                <small>Sistema de Validación Inteligente - C2D</small>
            </div>

            <div class="overlay dark" id="loadingOverlay" style="display: none;">
                <i class="fas fa-3x fa-sync-alt fa-spin"></i>
                <div class="text-bold pt-2">Procesando archivo, esto puede tardar unos segundos...</div>
            </div>
        </div>
    </div>

    <?php if (isset($resultados) && isset($listaNuevos)): ?>

        <div class="col-md-10 mt-4">

            <h4 class="text-center mb-3 text-dark"><i class="fas fa-clipboard-list"></i> Resultado de la Importación</h4>

            <div class="row">
                <div class="col-md-4">
                    <div class="info-box bg-success">
                        <span class="info-box-icon"><i class="fas fa-check"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Nuevos Insertados</span>
                            <span class="info-box-number"><?= $resultados['insertados'] ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box bg-warning">
                        <span class="info-box-icon"><i class="fas fa-history"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Ya Existían (Omitidos)</span>
                            <span class="info-box-number"><?= $resultados['omitidos'] ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box bg-danger">
                        <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Errores</span>
                            <span class="info-box-number"><?= $resultados['errores'] ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-md-6">
                    <div class="info-box bg-secondary">
                        <span class="info-box-icon"><i class="fas fa-power-off"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Máquinas Inactivadas (No estaban en Excel)</span>
                            <span class="info-box-number"><?= $resultados['bajas_maquinas'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-box bg-secondary">
                        <span class="info-box-icon"><i class="fas fa-store-slash"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Puntos Inactivados (Sin máquinas hoy)</span>
                            <span class="info-box-number"><?= $resultados['bajas_puntos'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (count($listaNuevos) > 0): ?>
                <div class="card card-success card-outline">
                    <div class="card-header">
                        <h3 class="card-title">✅ Detalle de Máquinas Nuevas Agregadas</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body p-0 table-responsive" style="max-height: 400px;">
                        <table class="table table-striped table-hover table-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Device ID</th>
                                    <th>Cliente</th>
                                    <th>Punto</th>
                                    <th>Ciudad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listaNuevos as $index => $item): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td class="font-weight-bold text-primary"><?= htmlspecialchars($item['device_id']) ?></td>
                                        <td><?= htmlspecialchars($item['cliente']) ?></td>
                                        <td><?= htmlspecialchars($item['punto']) ?></td>
                                        <td><?= htmlspecialchars($item['ciudad']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <?php if ($resultados['insertados'] == 0 && $resultados['errores'] == 0): ?>
                    <div class="alert alert-warning text-center">
                        <h5><i class="icon fas fa-info-circle"></i> Sin Novedades</h5>
                        Todas las máquinas del archivo ya se encuentran registradas en la base de datos.
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="text-center mb-5">
                <a href="index.php?pagina=importarExcel" class="btn btn-secondary shadow">
                    <i class="fas fa-broom mr-1"></i> Limpiar Pantalla / Nueva Carga
                </a>
            </div>

        </div>
    <?php endif; ?>

</div>

<script>
    // 1. Mostrar nombre del archivo
    function mostrarNombreArchivo() {
        var input = document.getElementById('archivo_excel');
        var label = document.getElementById('nombre_archivo');

        if (input.files && input.files.length > 0) {
            label.textContent = input.files[0].name;
            label.classList.remove('d-none');
            label.classList.add('d-inline-block');
            label.classList.replace('badge-secondary', 'badge-primary');
        }
    }

    // 2. Activar Loading
    function activarLoading() {
        var input = document.getElementById('archivo_excel');
        if (input.files.length > 0) {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }
    }
</script>

<style>
    .overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.85);
        z-index: 50;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        border-radius: 0.25rem;
    }

    .border-dashed:hover {
        background-color: #e2e6ea !important;
        border-color: #adb5bd !important;
        transition: 0.3s;
    }
</style>