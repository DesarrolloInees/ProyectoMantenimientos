<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/horaExtra/horaExtraHistorialModelo.php';

class horaExtraHistorialControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new horaExtraHistorialModelo($this->db);
    }

    public function index()
    {
        $idUsuarioLogueado = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;

        if ($idUsuarioLogueado === 0) {
            echo "<script>alert('Sesión expirada.'); window.location.href='index.php';</script>";
            return;
        }

        $idTecnicoActual = $this->modelo->obtenerIdTecnicoPorUsuario($idUsuarioLogueado);

        if ($idTecnicoActual === 0) {
            echo "<script>alert('Tu usuario no está vinculado a un perfil de técnico.'); window.history.back();</script>";
            return;
        }

        $reportesHE = $this->modelo->obtenerHistorialHorasExtraPorTecnico($idTecnicoActual);

        $titulo = "Mis Horas Extra";
        $vistaContenido = "app/views/horaExtra/horaExtraHistorialVista.php";
        include "app/views/plantillaVista.php";
    }

    public function ajaxEditarRegistro()
    {
        while (ob_get_level())
            ob_end_clean();
        header('Content-Type: application/json');

        $idUsuarioLogueado = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;

        // Obtener la información completa del técnico
        $datosTecnico = $this->modelo->obtenerDatosTecnicoPorUsuario($idUsuarioLogueado);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $datosTecnico) {
            $idTecnicoActual = (int) $datosTecnico['id_tecnico'];
            $idRegistro = isset($_POST['id_registro']) ? (int) $_POST['id_registro'] : 0;
            $fechaReporte = isset($_POST['fecha_reporte']) ? trim($_POST['fecha_reporte']) : '';
            $horaInicio = isset($_POST['hora_inicio']) ? trim($_POST['hora_inicio']) : '';
            $horaFin = isset($_POST['hora_fin']) ? trim($_POST['hora_fin']) : '';
            $justificacion = isset($_POST['justificacion_tecnico']) ? trim($_POST['justificacion_tecnico']) : '';

            $registroActual = $this->modelo->obtenerRegistroPorIdYTecnico($idRegistro, $idTecnicoActual);
            if (!$registroActual) {
                echo json_encode(['success' => false, 'msj' => 'El registro no existe o no te pertenece.']);
                exit;
            }

            if ((int) $registroActual['id_estado_aprobacion'] === 2) {
                echo json_encode(['success' => false, 'msj' => 'No puedes editar un registro que ya fue APROBADO.']);
                exit;
            }

            // Cálculo de horas
            $start = new DateTime($fechaReporte . ' ' . $horaInicio);
            $end = new DateTime($fechaReporte . ' ' . $horaFin);
            if ($end <= $start) {
                $end->modify('+1 day');
            }
            $intervalo = $start->diff($end);
            $totalHoras = round($intervalo->h + ($intervalo->i / 60), 2);

            $actualizado = $this->modelo->actualizarHoraExtraTecnico(
                $idRegistro,
                $idTecnicoActual,
                $fechaReporte,
                $horaInicio,
                $horaFin,
                $totalHoras,
                $justificacion
            );

            if ($actualizado) {
                // PROCESAR NUEVAS FOTOS ADJUNTAS
                if (isset($_FILES['nuevas_evidencias']) && count($_FILES['nuevas_evidencias']['name']) > 0) {

                    $nombreLimpio = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $datosTecnico['nombre_tecnico'] ?? 'TECNICO');
                    $nombreLimpio = preg_replace('/[^A-Za-z0-9\s_]/', '', $nombreLimpio);
                    $nombreTecnicoSanitizado = strtoupper(preg_replace('/\s+/', '_', trim($nombreLimpio)));
                    $mesAnio = date('Y-m', strtotime($fechaReporte));

                    $carpetaSubRuta = 'app/uploads/horas_extra/' . $nombreTecnicoSanitizado . '/' . $mesAnio . '/';
                    $carpetaDestinoFisica = __DIR__ . '/../../../' . $carpetaSubRuta;

                    if (!file_exists($carpetaDestinoFisica)) {
                        mkdir($carpetaDestinoFisica, 0777, true);
                    }

                    $totalFotos = count($_FILES['nuevas_evidencias']['name']);
                    for ($i = 0; $i < $totalFotos; $i++) {
                        if ($_FILES['nuevas_evidencias']['error'][$i] === UPLOAD_ERR_OK) {
                            $tmpName = $_FILES['nuevas_evidencias']['tmp_name'][$i];
                            $nombreNuevo = 'HE_' . $idRegistro . '_FOTO_' . uniqid() . '.jpg';
                            $rutaFinalServidor = $carpetaDestinoFisica . $nombreNuevo;
                            $rutaParaBD = $carpetaSubRuta . $nombreNuevo;

                            if ($this->optimizarImagen($tmpName, $rutaFinalServidor, 1000, 75)) {
                                $this->modelo->guardarEvidenciaFoto($idRegistro, $rutaParaBD);
                            }
                        }
                    }
                }

                echo json_encode(['success' => true, 'msj' => 'Registro y evidencias actualizados correctamente.']);
                exit;
            }
        }

        echo json_encode(['success' => false, 'msj' => 'No se pudo guardar la información.']);
        exit;
    }

    private function optimizarImagen($rutaOrigen, $rutaDestino, $anchoMaximo = 1000, $calidad = 75)
    {
        ini_set('memory_limit', '256M');
        if (!extension_loaded('gd')) {
            return move_uploaded_file($rutaOrigen, $rutaDestino);
        }

        $info = @getimagesize($rutaOrigen);
        if (!$info) {
            return move_uploaded_file($rutaOrigen, $rutaDestino);
        }

        $mime = $info['mime'];
        $anchoOriginal = $info[0];
        $altoOriginal = $info[1];

        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $imagenOriginal = @imagecreatefromjpeg($rutaOrigen);
                break;
            case 'image/png':
                $imagenOriginal = @imagecreatefrompng($rutaOrigen);
                break;
            case 'image/webp':
                $imagenOriginal = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($rutaOrigen) : null;
                break;
            default:
                return move_uploaded_file($rutaOrigen, $rutaDestino);
        }

        if (!$imagenOriginal)
            return move_uploaded_file($rutaOrigen, $rutaDestino);

        if (function_exists('exif_read_data') && ($mime == 'image/jpeg' || $mime == 'image/jpg')) {
            $exif = @exif_read_data($rutaOrigen);
            if ($exif && isset($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 3:
                        $imagenOriginal = imagerotate($imagenOriginal, 180, 0);
                        break;
                    case 6:
                        $imagenOriginal = imagerotate($imagenOriginal, -90, 0);
                        $temp = $anchoOriginal;
                        $anchoOriginal = $altoOriginal;
                        $altoOriginal = $temp;
                        break;
                    case 8:
                        $imagenOriginal = imagerotate($imagenOriginal, 90, 0);
                        $temp = $anchoOriginal;
                        $anchoOriginal = $altoOriginal;
                        $altoOriginal = $temp;
                        break;
                }
            }
        }

        if ($anchoOriginal > $anchoMaximo) {
            $ratio = $anchoMaximo / $anchoOriginal;
            $nuevoAncho = $anchoMaximo;
            $nuevoAlto = round($altoOriginal * $ratio);
        } else {
            $nuevoAncho = $anchoOriginal;
            $nuevoAlto = $altoOriginal;
        }

        $imagenRedimensionada = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
        imagecopyresampled($imagenRedimensionada, $imagenOriginal, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $anchoOriginal, $altoOriginal);
        $exito = imagejpeg($imagenRedimensionada, $rutaDestino, $calidad);

        imagedestroy($imagenOriginal);
        imagedestroy($imagenRedimensionada);
        return $exito;
    }

    public function ajaxEliminarEvidencia()
    {
        while (ob_get_level())
            ob_end_clean();
        header('Content-Type: application/json');

        $idUsuarioLogueado = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;
        $datosTecnico = $this->modelo->obtenerDatosTecnicoPorUsuario($idUsuarioLogueado);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $datosTecnico) {
            $idEvidencia = isset($_POST['id_evidencia']) ? (int) $_POST['id_evidencia'] : 0;
            $idRegistro = isset($_POST['id_registro']) ? (int) $_POST['id_registro'] : 0;

            // Validar propiedad del registro
            $registroActual = $this->modelo->obtenerRegistroPorIdYTecnico($idRegistro, (int) $datosTecnico['id_tecnico']);
            if (!$registroActual) {
                echo json_encode(['success' => false, 'msj' => 'El registro no te pertenece.']);
                exit;
            }

            if ((int) $registroActual['id_estado_aprobacion'] === 2) {
                echo json_encode(['success' => false, 'msj' => 'No puedes eliminar imágenes de un registro APROBADO.']);
                exit;
            }

            $evidencia = $this->modelo->obtenerEvidenciaPorId($idEvidencia, $idRegistro);
            if ($evidencia) {
                // Intentar borrar archivo físico si existe
                $rutaFisica = __DIR__ . '/../../../' . $evidencia['ruta_archivo'];
                if (file_exists($rutaFisica)) {
                    @unlink($rutaFisica);
                }

                // Eliminar de base de datos
                if ($this->modelo->eliminarEvidenciaFoto($idEvidencia, $idRegistro)) {
                    echo json_encode(['success' => true, 'msj' => 'Imagen eliminada correctamente.']);
                    exit;
                }
            }
        }

        echo json_encode(['success' => false, 'msj' => 'No se pudo eliminar la imagen.']);
        exit;
    }
}

