<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

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

            $idOrdenServicio = (int)$_POST['id_ordenes_servicio'];

            $datos = [
                'id_ordenes_servicio' => $idOrdenServicio,
                'id_tecnico'          => $idTecnicoActual,
                'numero_remision'     => $_POST['numero_remision'],
                'hora_entrada'        => $_POST['hora_entrada'],
                'hora_salida'         => $_POST['hora_salida'],
                'tiempo_servicio'     => $_POST['tiempo_servicio'],
                'actividades_realizadas' => $_POST['actividades_realizadas'],
                'id_estado_maquina'   => $_POST['id_estado_maquina'],
                'id_calificacion'     => !empty($_POST['id_calificacion']) ? $_POST['id_calificacion'] : null,
                'id_tipo_mantenimiento' => $_POST['id_tipo_mantenimiento'],
                'soporte_remoto'      => !empty($_POST['soporte_remoto']) ? $_POST['soporte_remoto'] : null,
                'tiene_novedad'       => isset($_POST['tiene_novedad']) ? 1 : 0,
                'id_tipo_novedad'     => !empty($_POST['id_tipo_novedad']) ? $_POST['id_tipo_novedad'] : null,
                'detalle_novedad'     => !empty($_POST['detalle_novedad']) ? $_POST['detalle_novedad'] : null,
                'repuestos_tecnico'   => !empty($_POST['json_repuestos']) ? $_POST['json_repuestos'] : null
            ];


            // ==========================================
            // NUEVO: GUARDAR DATOS COMPLEMENTARIOS (Con GPS)
            // ==========================================
            $datosComplementarios = [
                'id_orden_servicio'   => $idOrdenServicio,
                'numero_maquina'      => !empty($_POST['numero_maquina']) ? $_POST['numero_maquina'] : null,
                'serial_maquina'      => !empty($_POST['serial_maquina']) ? $_POST['serial_maquina'] : null,
                'serial_router'       => !empty($_POST['serial_router']) ? $_POST['serial_router'] : null,
                'serial_ups'          => !empty($_POST['serial_ups']) ? $_POST['serial_ups'] : null,
                'pendientes'          => !empty($_POST['pendientes']) ? $_POST['pendientes'] : null,
                'administrador_punto' => !empty($_POST['administrador_punto']) ? $_POST['administrador_punto'] : null,
                'celular_encargado'   => !empty($_POST['celular_encargado']) ? $_POST['celular_encargado'] : null,
                'id_estado_inicial'   => !empty($_POST['id_estado_inicial']) ? $_POST['id_estado_inicial'] : null,
                // ---> NUEVO: Atrapamos el GPS <---
                'latitud_fin'         => !empty($_POST['latitud_fin']) ? $_POST['latitud_fin'] : null,
                'longitud_fin'        => !empty($_POST['longitud_fin']) ? $_POST['longitud_fin'] : null
            ];
            
            $this->modelo->guardarDatosComplementarios($datosComplementarios);

            // 1. Guardamos los datos en texto de la orden
            if ($this->modelo->guardarReporteTecnico($datos)) {

                if (!empty($datos['numero_remision'])) {
                    $this->modeloMaestro->marcarRemisionComoUsada($datos['numero_remision'], $idOrdenServicio, $idTecnicoActual);
                }

                // ==========================================
                // 2. LÓGICA PARA SUBIR LAS IMÁGENES
                // ==========================================
                $remisionCarpeta = !empty($datos['numero_remision']) ? $datos['numero_remision'] : 'SIN_REMISION_' . $idOrdenServicio;

                // Definimos la nueva carpeta: uploads/imagenes_servicios/NUMERO_REMISION/
                $carpetaDestino = __DIR__ . '/../../uploads/imagenes_servicios/' . $remisionCarpeta . '/';

                // Si la carpeta no existe, la creamos con permisos de escritura
                if (!file_exists($carpetaDestino)) {
                    mkdir($carpetaDestino, 0777, true);
                }

                // Array de los 3 inputs de archivos
                $tiposFotos = [
                    'fotos_antes' => 'antes',
                    'foto_remision' => 'remision',
                    'fotos_despues' => 'despues'
                ];

                foreach ($tiposFotos as $inputName => $tipoEnum) {
                    if (isset($_FILES[$inputName]) && !empty($_FILES[$inputName]['name'][0])) {

                        $cantidadFotos = count($_FILES[$inputName]['name']);

                        for ($i = 0; $i < $cantidadFotos; $i++) {
                            if ($_FILES[$inputName]['error'][$i] === UPLOAD_ERR_OK) {

                                $nombreOriginal = $_FILES[$inputName]['name'][$i];
                                $tmpName = $_FILES[$inputName]['tmp_name'][$i];

                                $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

                                // Tomamos la remisión, o si no tiene, usamos el ID de la orden
                                $numeroParaNombre = !empty($datos['numero_remision']) ? $datos['numero_remision'] : 'ORDEN-' . $idOrdenServicio;

                                // Nombre final del archivo
                                $nombreNuevo = 'REM-' . $numeroParaNombre . '_' . $tipoEnum . '_' . uniqid() . '.jpg';

                                // Esta ruta SÍ usa el __DIR__ pero SOLO para guardar el archivo físico en Windows
                                $rutaFinalServidor = $carpetaDestino . $nombreNuevo;

                                // === ESTA ES LA CLAVE ===
                                // Esta es la ruta limpia que se va a guardar en la Base de Datos
                                $rutaParaBD = 'uploads/imagenes_servicios/' . $remisionCarpeta . '/' . $nombreNuevo;

                                if ($this->optimizarImagen($tmpName, $rutaFinalServidor, 800, 70)) {
                                    $this->modelo->guardarEvidenciaFoto($idOrdenServicio, $tipoEnum, $rutaParaBD);
                                }
                            }
                        }
                    }
                }

                // ==========================================
                // NUEVO: LÓGICA PARA GUARDAR LA FIRMA (CANVAS)
                // ==========================================
                if (!empty($_POST['firma_base64'])) {
                    $firmaTextoBase64 = $_POST['firma_base64'];
                    
                    // El texto viene como "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA..."
                    // Necesitamos quitarle la cabecera para quedarnos solo con el código de la imagen
                    $partesFirma = explode(',', $firmaTextoBase64);
                    
                    if (count($partesFirma) == 2) {
                        $firmaDecodificada = base64_decode($partesFirma[1]);
                        
                        // Generamos el nombre del archivo
                        $numeroParaNombreFirma = !empty($datos['numero_remision']) ? $datos['numero_remision'] : 'ORDEN-' . $idOrdenServicio;
                        $nombreFirma = 'REM-' . $numeroParaNombreFirma . '_firma_' . uniqid() . '.png';
                        
                        // Rutas física y de BD
                        $rutaFinalFirmaServidor = $carpetaDestino . $nombreFirma;
                        $rutaParaBDFirma = 'uploads/imagenes_servicios/' . $remisionCarpeta . '/' . $nombreFirma;
                        
                        // Guardamos el archivo físicamente en el servidor
                        if (file_put_contents($rutaFinalFirmaServidor, $firmaDecodificada)) {
                            // Guardamos en la Base de Datos usando la misma función de evidencias
                            $this->modelo->guardarEvidenciaFoto($idOrdenServicio, 'firma', $rutaParaBDFirma);
                        } else {
                            error_log("No se pudo guardar la imagen física de la firma.");
                        }
                    }
                }
                

                // ==========================================
                // 3. LÓGICA PARA GUARDAR REPUESTOS
                // ==========================================
                if (!empty($_POST['json_repuestos'])) {
                    $repuestosUsados = json_decode($_POST['json_repuestos'], true);

                    if (is_array($repuestosUsados) && count($repuestosUsados) > 0) {
                        // Limpiamos los repuestos anteriores por si acaso (aunque debería ser un insert limpio)
                        $this->modelo->limpiarRepuestosOrden($idOrdenServicio);

                        foreach ($repuestosUsados as $rep) {
                            $idRepuesto = (int)$rep['id'];
                            $cantidad = (int)$rep['cantidad'];
                            $origen = $rep['origen']; // 'INEES' o 'PROSEGUR'

                            // Guardamos en detalles_ordenes_servicio
                            $this->modelo->guardarRepuestoOrden($idOrdenServicio, $idRepuesto, $cantidad, $origen);
                        }
                    }
                }

                // ==========================================

                echo "<script>
                    alert('✅ Reporte y evidencias guardadas exitosamente.');
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
    // --- NUEVA FUNCIÓN: Comprimir y redimensionar imagen (VERSIÓN SEGURA) ---
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

        // 4. Crear recurso de imagen según el tipo, con protecciones adicionales
        try {
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
                    } else {
                        return move_uploaded_file($rutaOrigen, $rutaDestino);
                    }
                    break;
                default:
                    return move_uploaded_file($rutaOrigen, $rutaDestino);
            }

            if (!$imagenOriginal) {
                return move_uploaded_file($rutaOrigen, $rutaDestino);
            }

            // Calcular nuevas dimensiones manteniendo la proporción
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
        } catch (Exception $e) {
            error_log("Error comprimiendo imagen: " . $e->getMessage());
            // Si algo explota en el proceso, nos aseguramos de que al menos guarde la foto original
            return move_uploaded_file($rutaOrigen, $rutaDestino);
        }
    }
}
