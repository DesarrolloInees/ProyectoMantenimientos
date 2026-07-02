<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/tecnico/tecnicoReporteModelo.php';
require_once __DIR__ . '/../../models/orden/ordenCrearModelo.php';

class tecnicoReporteControlador
{
    private $modelo;
    private $modeloMaestro;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new tecnicoReporteModelo($this->db);
        $this->modeloMaestro = new ordenCrearModels($this->db);
    }

    public function index()
    {
        $idUsuarioLogueado = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;
        $idOrden = isset($_GET['orden']) ? (int) $_GET['orden'] : 0;

        if ($idOrden === 0 || $idUsuarioLogueado === 0) {
            echo "<script>alert('Orden no válida o sesión expirada.'); window.history.back();</script>";
            return;
        }

        // --- SOLUCIÓN AQUÍ: Obtenemos el id_tecnico real ---
        $idTecnicoActual = $this->modelo->obtenerIdTecnicoPorUsuario($idUsuarioLogueado);

        if ($idTecnicoActual === 0) {
            echo "<script>alert('Tu usuario no está vinculado a un perfil de técnico.'); window.history.back();</script>";
            return;
        }

        // 1. Obtener datos de la orden (pasando el idTecnicoActual)
        $orden = $this->modelo->obtenerDetalleOrden($idOrden, $idTecnicoActual);

        if (!$orden) {
            echo "<script>alert('La orden no existe o ya fue ejecutada.'); window.location.href='index.php?pagina=tecnicoProgramacion';</script>";
            return;
        }

        // 2. Obtener listas para los selects (pasando el idTecnicoActual)
        $remisiones = $this->modelo->obtenerRemisionesTecnico($idTecnicoActual);
        $estados = $this->modeloMaestro->obtenerEstadosMaquina();
        $calificaciones = $this->modeloMaestro->obtenerCalificaciones();
        // --- AQUÍ ESTÁ EL CAMBIO: Usamos la nueva función del modelo técnico ---
        $tiposManto = $this->modelo->obtenerTiposMantenimientoTecnico();
        // --- NUEVO: Traemos el inventario físico del técnico ---
        $inventario = $this->modelo->obtenerInventarioTecnico($idTecnicoActual);

        // 🔥 GUARDAMOS LA FECHA DE APERTURA EN SESIÓN
        $_SESSION['fecha_apertura_orden_' . $idOrden] = date('Y-m-d H:i:s');

        // También la pasamos al frontend como hidden
        $fechaApertura = $_SESSION['fecha_apertura_orden_' . $idOrden];

        // 3. Cargar Vista
        $titulo = "Atender Servicio";
        $vistaContenido = "app/views/tecnico/tecnicoReporteVista.php";
        include "app/views/plantillaVista.php";
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idUsuarioLogueado = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;
            $idTecnicoActual = $this->modelo->obtenerIdTecnicoPorUsuario($idUsuarioLogueado);

            $idOrdenServicio = (int) $_POST['id_ordenes_servicio'];

            // 🔥 LOG PARA DEPURAR
            error_log("=== GUARDAR REPORTE ===");
            error_log("id_cliente recibido: " . ($_POST['id_cliente'] ?? 'NULL'));
            error_log("id_punto recibido: " . ($_POST['id_punto'] ?? 'NULL'));
            error_log("id_orden: " . $idOrdenServicio);

            // 🔥 VALIDACIÓN: Si no llegan, obtenerlos de la orden existente
            $idCliente = $_POST['id_cliente'] ?? null;
            $idPunto = $_POST['id_punto'] ?? null;

            if (empty($idCliente) || empty($idPunto)) {
                // Obtener los datos actuales de la orden
                $ordenActual = $this->modelo->obtenerDetalleOrden($idOrdenServicio, $idTecnicoActual);
                if ($ordenActual) {
                    $idCliente = $idCliente ?: $ordenActual['id_cliente'];
                    $idPunto = $idPunto ?: $ordenActual['id_punto'];
                    error_log("Usando valores de la orden existente: cliente=$idCliente, punto=$idPunto");
                }
            }

            // 🔥 VALIDACIÓN FINAL: Si sigue vacío, mostrar error
            if (empty($idCliente) || empty($idPunto)) {
                echo "<script>
                alert('❌ Error: No se puede determinar el cliente o punto del servicio. Contacte a soporte.');
                window.history.back();
            </script>";
                return;
            }

            // Validar que el cliente exista en la BD
            $sqlCheck = "SELECT id_cliente FROM cliente WHERE id_cliente = :id";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([':id' => $idCliente]);
            if (!$stmtCheck->fetch()) {
                echo "<script>
                alert('❌ Error: El cliente con ID $idCliente no existe en la base de datos.');
                window.history.back();
            </script>";
                return;
            }

            // 🔥 PASO 1: Validar modificación
            $fechaApertura = $_POST['fecha_apertura'] ?? null;
            $fechaUltimaModificacion = $this->modelo->obtenerUltimaModificacion($idOrdenServicio);

            if ($fechaApertura && $fechaUltimaModificacion) {
                if (strtotime($fechaUltimaModificacion) > strtotime($fechaApertura)) {
                    echo "<script>
                    alert('⚠️ El servicio fue modificado por otro usuario mientras estabas en campo.');
                    window.history.back();
                </script>";
                    return;
                }
            }

            // 🔥 PASO 2: Guardar la fecha de modificación antes de actualizar
            $this->modelo->actualizarFechaModificacion($idOrdenServicio);

            $datos = [
                'id_ordenes_servicio' => $idOrdenServicio,
                'id_tecnico' => $idTecnicoActual,
                'id_cliente' => $_POST['id_cliente'] ?? null,
                'id_punto' => $_POST['id_punto'] ?? null,
                'numero_remision' => $_POST['numero_remision'],
                'hora_entrada' => $_POST['hora_entrada'],
                'hora_salida' => $_POST['hora_salida'],
                'tiempo_servicio' => $_POST['tiempo_servicio'],
                'actividades_realizadas' => $_POST['actividades_realizadas'],
                'id_estado_maquina' => $_POST['id_estado_maquina'],
                'id_calificacion' => !empty($_POST['id_calificacion']) ? $_POST['id_calificacion'] : null,
                'id_tipo_mantenimiento' => $_POST['id_tipo_mantenimiento'],
                'soporte_remoto' => !empty($_POST['soporte_remoto']) ? $_POST['soporte_remoto'] : null,
                'tiene_novedad' => isset($_POST['tiene_novedad']) ? 1 : 0,
                'id_tipo_novedad' => !empty($_POST['id_tipo_novedad']) ? $_POST['id_tipo_novedad'] : null,
                'detalle_novedad' => !empty($_POST['detalle_novedad']) ? $_POST['detalle_novedad'] : null,
                'repuestos_tecnico' => !empty($_POST['json_repuestos']) ? $_POST['json_repuestos'] : null
            ];

            // ==========================================
            // GUARDAR DATOS COMPLEMENTARIOS (Con GPS)
            // ==========================================
            $datosComplementarios = [
                'id_orden_servicio' => $idOrdenServicio,
                'numero_maquina' => !empty($_POST['numero_maquina']) ? $_POST['numero_maquina'] : null,
                'serial_maquina' => !empty($_POST['serial_maquina']) ? $_POST['serial_maquina'] : null,
                'serial_router' => !empty($_POST['serial_router']) ? $_POST['serial_router'] : null,
                'serial_ups' => !empty($_POST['serial_ups']) ? $_POST['serial_ups'] : null,
                'pendientes' => !empty($_POST['pendientes']) ? $_POST['pendientes'] : null,
                'administrador_punto' => !empty($_POST['administrador_punto']) ? $_POST['administrador_punto'] : null,
                'celular_encargado' => !empty($_POST['celular_encargado']) ? $_POST['celular_encargado'] : null,
                'id_estado_inicial' => !empty($_POST['id_estado_inicial']) ? $_POST['id_estado_inicial'] : null,
                'latitud_fin' => !empty($_POST['latitud_fin']) ? $_POST['latitud_fin'] : null,
                'longitud_fin' => !empty($_POST['longitud_fin']) ? $_POST['longitud_fin'] : null
            ];

            $this->modelo->guardarDatosComplementarios($datosComplementarios);

            // ==========================================
            // 1. GUARDAMOS EL REPORTE EN LA BD
            // ==========================================
            if ($this->modelo->guardarReporteTecnico($datos)) {

                // Si hay remisión, la marcamos como usada
                if (!empty($datos['numero_remision'])) {
                    $this->modeloMaestro->marcarRemisionComoUsada($datos['numero_remision'], $idOrdenServicio, $idTecnicoActual);
                }

                $remisionCarpeta = !empty($datos['numero_remision']) ? $datos['numero_remision'] : 'SIN_REMISION_' . $idOrdenServicio;

                // CORRECCIÓN: Quitamos el "app/" de la ruta física
                $carpetaDestino = __DIR__ . '/../../uploads/imagenes_servicios/' . $remisionCarpeta . '/';
                if (!file_exists($carpetaDestino)) {
                    mkdir($carpetaDestino, 0777, true);
                }

                // ==========================================
                // LÓGICA PARA GUARDAR LA FIRMA (CANVAS)
                // ==========================================
                if (!empty($_POST['firma_base64'])) {
                    $firmaTextoBase64 = $_POST['firma_base64'];

                    $partesFirma = explode(',', $firmaTextoBase64);

                    if (count($partesFirma) == 2) {
                        $firmaDecodificada = base64_decode($partesFirma[1]);

                        $numeroParaNombreFirma = !empty($datos['numero_remision']) ? $datos['numero_remision'] : 'ORDEN-' . $idOrdenServicio;
                        $nombreFirma = 'REM-' . $numeroParaNombreFirma . '_firma_' . uniqid() . '.png';

                        $rutaFinalFirmaServidor = $carpetaDestino . $nombreFirma;

                        // CORRECCIÓN: Quitamos el "app/" para la base de datos
                        $rutaParaBDFirma = 'uploads/imagenes_servicios/' . $remisionCarpeta . '/' . $nombreFirma;

                        if (file_put_contents($rutaFinalFirmaServidor, $firmaDecodificada)) {
                            $this->modelo->guardarEvidenciaFoto($idOrdenServicio, 'firma', $rutaParaBDFirma);
                        } else {
                            error_log("No se pudo guardar la imagen física de la firma.");
                        }
                    }
                }

                // ==========================================
                // LÓGICA PARA GUARDAR REPUESTOS
                // ==========================================
                if (!empty($_POST['json_repuestos'])) {
                    $repuestosUsados = json_decode($_POST['json_repuestos'], true);

                    if (is_array($repuestosUsados) && count($repuestosUsados) > 0) {
                        $this->modelo->limpiarRepuestosOrden($idOrdenServicio);

                        foreach ($repuestosUsados as $rep) {
                            $idRepuesto = (int) $rep['id'];
                            $cantidad = (int) $rep['cantidad'];
                            $origen = $rep['origen'];

                            $this->modelo->guardarRepuestoOrden($idOrdenServicio, $idRepuesto, $cantidad, $origen);
                        }
                    }
                }

                echo "<script>
                    alert('✅ Reporte finalizado exitosamente.');
                    window.location.href = 'index.php?pagina=tecnicoProgramacion';
                </script>";
            } else {
                echo "<script>
                    alert('❌ Error al guardar el reporte.');
                    window.history.back();
                </script>";
            }
        }
    }


    // ==========================================
    // ENDPOINTS AJAX PARA IMÁGENES INDIVIDUALES
    // ==========================================

    public function ajaxObtenerEvidencias()
    {
        while (ob_get_level())
            ob_end_clean();
        header('Content-Type: application/json');

        $idOrden = isset($_POST['id_orden']) ? (int) $_POST['id_orden'] : 0;
        $evidencias = $this->modelo->obtenerEvidenciasPorOrden($idOrden);

        // Calculamos la URL base del proyecto (ej. http://localhost/ProyectoMantenimientos/app/)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        // Obtenemos la ruta hasta la carpeta 'app' (suponiendo que el script está dentro de app/controllers/...)
        $scriptPath = dirname($_SERVER['SCRIPT_NAME']); // Ej: /ProyectoMantenimientos/app/controllers
        // Subimos dos niveles para llegar a la raíz del proyecto? No, queremos incluir 'app'
        // La carpeta 'app' está a nivel del script? En realidad, si el script está en app/controllers/tecnico/..., entonces:
        // /ProyectoMantenimientos/app/controllers/tecnico -> subimos 3 niveles para llegar a la raíz? Mejor construimos manualmente.
        // Opción más segura: asumir que la carpeta 'app' está en la raíz del proyecto.
        // Usaremos una variable de entorno o constante BASE_URL si la tienes definida globalmente.
        // Si no, la construimos así:
        $baseUrl = $protocol . $host . '/ProyectoMantenimientos/app/'; // Ajusta el nombre de tu proyecto si es diferente

        foreach ($evidencias as &$ev) {
            // La ruta guardada en BD: "uploads/imagenes_servicios/..."
            // La URL final debe ser: http://localhost/ProyectoMantenimientos/app/uploads/imagenes_servicios/...
            $ev['ruta_archivo'] = $baseUrl . $ev['ruta_archivo'];
        }

        echo json_encode(['success' => true, 'data' => $evidencias]);
        exit;
    }

    public function ajaxVerificarModificacion()
    {
        while (ob_get_level())
            ob_end_clean();
        header('Content-Type: application/json');

        $idOrden = $_POST['id_orden'] ?? 0;
        $fechaApertura = $_POST['fecha_apertura'] ?? null;

        if ($idOrden && $fechaApertura) {
            $fechaUltimaModificacion = $this->modelo->obtenerUltimaModificacion($idOrden);
            $fueModificado = $fechaUltimaModificacion && strtotime($fechaUltimaModificacion) > strtotime($fechaApertura);
            echo json_encode(['fue_modificado' => $fueModificado]);
        } else {
            echo json_encode(['fue_modificado' => false]);
        }
        exit;
    }

    public function ajaxSubirFotoUnica()
    {
        while (ob_get_level())
            ob_end_clean();
        header('Content-Type: application/json');

        $idOrden = isset($_POST['id_orden']) ? (int) $_POST['id_orden'] : 0;
        $tipoEvidencia = isset($_POST['tipo_evidencia']) ? $_POST['tipo_evidencia'] : '';
        $remision = !empty($_POST['numero_remision']) ? $_POST['numero_remision'] : 'SIN_REMISION_' . $idOrden;

        // 1. Verificar si la imagen llegó al servidor (A veces se corta si pesa más que el post_max_size)
        if (!isset($_FILES['foto'])) {
            echo json_encode(['success' => false, 'msj' => 'No se recibió la foto. Verifica los límites de php.ini (post_max_size).']);
            exit;
        }

        // 2. Verificar errores nativos de PHP al subir archivos
        if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $errores = [
                1 => 'La foto supera el upload_max_filesize de php.ini.',
                2 => 'La foto supera el MAX_FILE_SIZE del formulario HTML.',
                3 => 'La foto se subió parcialmente (se cortó el internet).',
                4 => 'No se seleccionó ninguna foto.',
                6 => 'Falta la carpeta temporal en el servidor.',
                7 => 'No se pudo escribir la foto en el disco del servidor.',
                8 => 'Una extensión de PHP detuvo la subida de la foto.'
            ];
            $codigoError = $_FILES['foto']['error'];
            $mensaje = isset($errores[$codigoError]) ? $errores[$codigoError] : 'Error desconocido de subida (Código: ' . $codigoError . ')';

            echo json_encode(['success' => false, 'msj' => $mensaje]);
            exit;
        }

        // 3. Proceso normal si no hay errores
        if ($idOrden > 0) {
            // CORRECCIÓN: Quitamos el "app/" de la ruta física
            $carpetaDestino = __DIR__ . '/../../uploads/imagenes_servicios/' . $remision . '/';

            // Verificamos si podemos crear la carpeta
            if (!file_exists($carpetaDestino)) {
                if (!mkdir($carpetaDestino, 0777, true)) {
                    echo json_encode(['success' => false, 'msj' => 'No se pudo crear la carpeta en el servidor. Verifica los permisos de escritura.']);
                    exit;
                }
            }

            $nombreOriginal = $_FILES['foto']['name'];
            $tmpName = $_FILES['foto']['tmp_name'];
            // En la función ajaxSubirFotoUnica de tecnicoReporteControlador.php:
            $nombreNuevo = 'REM-' . $remision . '_TEC_' . $tipoEvidencia . '_' . uniqid() . '.jpg';

            $rutaFinalServidor = $carpetaDestino . $nombreNuevo;

            // CORRECCIÓN: Quitamos el "app/" para la BD
            $rutaParaBD = 'uploads/imagenes_servicios/' . $remision . '/' . $nombreNuevo;

            // Intentamos optimizar y guardar
            if ($this->optimizarImagen($tmpName, $rutaFinalServidor, 800, 70)) {
                $guardadoBD = $this->modelo->guardarEvidenciaFoto($idOrden, $tipoEvidencia, $rutaParaBD);
                if ($guardadoBD) {
                    echo json_encode(['success' => true, 'msj' => 'Foto subida correctamente', 'ruta' => $rutaParaBD]);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'msj' => 'La foto se guardó en el servidor, pero falló el insert en la Base de Datos.']);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'msj' => 'Fallo al comprimir o mover la imagen a la carpeta final.']);
                exit;
            }
        }

        echo json_encode(['success' => false, 'msj' => 'ID de orden no válido.']);
        exit;
    }

    public function ajaxEliminarFotoUnica()
    {
        while (ob_get_level())
            ob_end_clean();
        header('Content-Type: application/json');

        $idEvidencia = isset($_POST['id_evidencia']) ? (int) $_POST['id_evidencia'] : 0;

        if ($idEvidencia > 0) {
            $resultado = $this->modelo->eliminarEvidencia($idEvidencia);

            if ($resultado['success']) {
                $rutaFisica = __DIR__ . '/../../' . $resultado['ruta'];
                // Borramos el archivo físico si aún existe (con @ para evitar warnings si ya lo borraste a mano)
                if (file_exists($rutaFisica)) {
                    @unlink($rutaFisica);
                }
                echo json_encode(['success' => true]);
                exit;
            } else {
                // AQUÍ: Mandamos el error exacto a la pantalla
                echo json_encode(['success' => false, 'msj' => $resultado['msj']]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'msj' => 'El ID de la evidencia llegó vacío al servidor.']);
        exit;
    }

    public function ajaxLimpiarEvidenciasOrden()
    {
        while (ob_get_level())
            ob_end_clean();
        header('Content-Type: application/json');

        $idOrden = isset($_POST['id_orden']) ? (int) $_POST['id_orden'] : 0;

        if ($idOrden > 0) {
            // 1. Buscamos y borramos los archivos físicos
            $evidencias = $this->modelo->obtenerEvidenciasPorOrden($idOrden);
            foreach ($evidencias as $ev) {
                $rutaFisica = __DIR__ . '/../../' . $ev['ruta_archivo'];
                if (file_exists($rutaFisica)) {
                    @unlink($rutaFisica);
                }
            }

            // 2. Borramos de la BD
            $resultado = $this->modelo->eliminarTodasEvidenciasOrden($idOrden);
            if ($resultado['success']) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'msj' => $resultado['msj']]);
            }
            exit;
        }
        echo json_encode(['success' => false, 'msj' => 'ID de orden inválido.']);
        exit;
    }

    private function optimizarImagen($rutaOrigen, $rutaDestino, $anchoMaximo = 800, $calidad = 70)
    {
        // 1. Aumentamos temporalmente la memoria de PHP. 
        // Las fotos de iPhone/Android modernas consumen mucha RAM al descomprimirse.
        ini_set('memory_limit', '256M');

        // 2. Validamos si el servidor tiene instalada la librería GD para procesar imágenes
        if (!extension_loaded('gd')) {
            error_log("Falta librería GD en el servidor. Guardando imagen original.");
            return move_uploaded_file($rutaOrigen, $rutaDestino);
        }

        // 3. Obtenemos información de la imagen silenciosamente (con @ para evitar warnings si el archivo es raro)
        $info = @getimagesize($rutaOrigen);
        if (!$info) {
            return move_uploaded_file($rutaOrigen, $rutaDestino);
        }

        $mime = $info['mime'];
        $anchoOriginal = $info[0];
        $altoOriginal = $info[1];

        // Cargar imagen según tipo
        $imagenOriginal = null;
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $imagenOriginal = @imagecreatefromjpeg($rutaOrigen);
                break;
            case 'image/png':
                $imagenOriginal = @imagecreatefrompng($rutaOrigen);
                break;
            case 'image/gif':
                $imagenOriginal = @imagecreatefromgif($rutaOrigen);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $imagenOriginal = @imagecreatefromwebp($rutaOrigen);
                }
                break;
            default:
                return move_uploaded_file($rutaOrigen, $rutaDestino);
        }

        if (!$imagenOriginal) {
            return move_uploaded_file($rutaOrigen, $rutaDestino);
        }

        // ==========================================
        // 🔥 CORRECCIÓN DE ORIENTACIÓN POR EXIF
        // ==========================================
        if (function_exists('exif_read_data') && ($mime == 'image/jpeg' || $mime == 'image/jpg')) {
            $exif = @exif_read_data($rutaOrigen);
            if ($exif && isset($exif['Orientation'])) {
                $orientacion = $exif['Orientation'];
                switch ($orientacion) {
                    case 3: // 180 grados
                        $imagenOriginal = imagerotate($imagenOriginal, 180, 0);
                        break;
                    case 6: // 90 grados a la derecha (necesita rotar -90)
                        $imagenOriginal = imagerotate($imagenOriginal, -90, 0);
                        // Intercambiar ancho y alto tras rotación
                        $temp = $anchoOriginal;
                        $anchoOriginal = $altoOriginal;
                        $altoOriginal = $temp;
                        break;
                    case 8: // 90 grados a la izquierda (necesita rotar +90)
                        $imagenOriginal = imagerotate($imagenOriginal, 90, 0);
                        $temp = $anchoOriginal;
                        $anchoOriginal = $altoOriginal;
                        $altoOriginal = $temp;
                        break;
                }
            }
        }

        // ==========================================
        // FORZAR ORIENTACIÓN VERTICAL (opcional)
        // Si después de corregir EXIF la imagen sigue apaisada (ancho > alto)
        // la rotamos 90 grados para que quede vertical.
        // ==========================================
        if ($anchoOriginal > $altoOriginal) {
            $imagenOriginal = imagerotate($imagenOriginal, 90, 0);
            $temp = $anchoOriginal;
            $anchoOriginal = $altoOriginal;
            $altoOriginal = $temp;
        }

        // Redimensionar manteniendo proporción
        if ($anchoOriginal > $anchoMaximo) {
            $ratio = $anchoMaximo / $anchoOriginal;
            $nuevoAncho = $anchoMaximo;
            $nuevoAlto = round($altoOriginal * $ratio);
        } else {
            $nuevoAncho = $anchoOriginal;
            $nuevoAlto = $altoOriginal;
        }

        // Crear lienzo nuevo
        $imagenRedimensionada = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

        // Mantener transparencia si es PNG
        if ($mime == 'image/png') {
            imagealphablending($imagenRedimensionada, false);
            imagesavealpha($imagenRedimensionada, true);
            $colorTransparente = imagecolorallocatealpha($imagenRedimensionada, 255, 255, 255, 127);
            imagefill($imagenRedimensionada, 0, 0, $colorTransparente);
        }

        // Copiar, redimensionar y guardar como JPG
        imagecopyresampled($imagenRedimensionada, $imagenOriginal, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $anchoOriginal, $altoOriginal);
        $exito = imagejpeg($imagenRedimensionada, $rutaDestino, $calidad);

        // Liberar memoria
        imagedestroy($imagenOriginal);
        imagedestroy($imagenRedimensionada);

        return $exito;
    }
}