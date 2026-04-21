<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/orden/servicioEditarModelo.php';

class servicioEditarControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new servicioEditarModelo($this->db);
    }

    public function index()
    {
        $idOrden = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($idOrden === 0) {
            echo "<script>alert('ID de orden no válido.'); window.history.back();</script>";
            return;
        }

        $datosOrden = $this->modelo->obtenerDatosEdicion($idOrden);
        $estados = $this->modelo->obtenerEstadosMaquina();
        // --- NUEVA LÍNEA: Traemos las fotos ya existentes ---
        $evidencias = $this->modelo->obtenerEvidenciasPorOrden($idOrden);

        if (!$datosOrden) {
            echo "<script>alert('La orden no existe.'); window.history.back();</script>";
            return;
        }

        $titulo = "Editar Complementos de Orden";
        $vistaContenido = "app/views/orden/servicioEditarVista.php";
        include "app/views/plantillaVista.php";
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $idOrdenServicio = (int)$_POST['id_ordenes_servicio'];
            $numeroRemision = $_POST['numero_remision'] ?? '';

            $datos = [
                'id_orden_servicio'   => $idOrdenServicio,
                'soporte_remoto'      => !empty($_POST['soporte_remoto']) ? $_POST['soporte_remoto'] : null,
                'numero_maquina'      => !empty($_POST['numero_maquina']) ? $_POST['numero_maquina'] : null,
                'serial_maquina'      => !empty($_POST['serial_maquina']) ? $_POST['serial_maquina'] : null,
                'serial_router'       => !empty($_POST['serial_router']) ? $_POST['serial_router'] : null,
                'serial_ups'          => !empty($_POST['serial_ups']) ? $_POST['serial_ups'] : null,
                'pendientes'          => !empty($_POST['pendientes']) ? $_POST['pendientes'] : null,
                'administrador_punto' => !empty($_POST['administrador_punto']) ? $_POST['administrador_punto'] : null,
                'celular_encargado'   => !empty($_POST['celular_encargado']) ? $_POST['celular_encargado'] : null,
                'id_estado_inicial'   => !empty($_POST['id_estado_inicial']) ? $_POST['id_estado_inicial'] : null,
            ];

            if ($this->modelo->actualizarComplementoSoporte($datos)) {

                // ==========================================
                // LÓGICA PARA SUBIR IMÁGENES ADICIONALES
                // ==========================================
                $remisionCarpeta = !empty($numeroRemision) ? $numeroRemision : 'SIN_REMISION_' . $idOrdenServicio;
                $carpetaDestino = __DIR__ . '/../../uploads/imagenes_servicios/' . $remisionCarpeta . '/';

                if (!file_exists($carpetaDestino)) {
                    mkdir($carpetaDestino, 0777, true);
                }

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

                                $numeroParaNombre = !empty($numeroRemision) ? $numeroRemision : 'ORDEN-' . $idOrdenServicio;
                                // Agregamos "ADMIN-" al nombre para saber que se subió desde este módulo
                                $nombreNuevo = 'REM-' . $numeroParaNombre . '_ADMIN_' . $tipoEnum . '_' . uniqid() . '.jpg';

                                $rutaFinalServidor = $carpetaDestino . $nombreNuevo;
                                $rutaParaBD = 'uploads/imagenes_servicios/' . $remisionCarpeta . '/' . $nombreNuevo;

                                if ($this->optimizarImagen($tmpName, $rutaFinalServidor, 800, 70)) {
                                    $this->modelo->guardarEvidenciaFoto($idOrdenServicio, $tipoEnum, $rutaParaBD);
                                }
                            }
                        }
                    }
                }

                echo "<script>
                    alert('✅ Información y/o imágenes guardadas exitosamente.');
                    window.location.href = 'index.php?pagina=serviciosPdf';
                </script>";
            } else {
                echo "<script>
                    alert('❌ Error al actualizar la información.');
                    window.history.back();
                </script>";
            }
        }
    }

    // --- FUNCIÓN DE OPTIMIZACIÓN (Igual a la del técnico) ---
    private function optimizarImagen($rutaOrigen, $rutaDestino, $anchoMaximo = 800, $calidad = 70)
    {
        ini_set('memory_limit', '256M');

        if (!extension_loaded('gd')) {
            return move_uploaded_file($rutaOrigen, $rutaDestino);
        }

        $info = @getimagesize($rutaOrigen);
        if (!$info) return move_uploaded_file($rutaOrigen, $rutaDestino);

        $mime = $info['mime'];
        $anchoOriginal = $info[0];
        $altoOriginal = $info[1];

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

            if (!$imagenOriginal) return move_uploaded_file($rutaOrigen, $rutaDestino);

            if ($anchoOriginal > $anchoMaximo) {
                $ratio = $anchoMaximo / $anchoOriginal;
                $nuevoAncho = $anchoMaximo;
                $nuevoAlto = round($altoOriginal * $ratio);
            } else {
                $nuevoAncho = $anchoOriginal;
                $nuevoAlto = $altoOriginal;
            }

            $imagenRedimensionada = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

            if ($mime == 'image/png') {
                imagealphablending($imagenRedimensionada, false);
                imagesavealpha($imagenRedimensionada, true);
                $colorTransparente = imagecolorallocatealpha($imagenRedimensionada, 255, 255, 255, 127);
                imagefill($imagenRedimensionada, 0, 0, $colorTransparente);
            }

            imagecopyresampled($imagenRedimensionada, $imagenOriginal, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $anchoOriginal, $altoOriginal);
            $exito = imagejpeg($imagenRedimensionada, $rutaDestino, $calidad);

            imagedestroy($imagenOriginal);
            imagedestroy($imagenRedimensionada);

            return $exito;
        } catch (Exception $e) {
            return move_uploaded_file($rutaOrigen, $rutaDestino);
        }
    }
}
