<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/horaExtra/horaExtraCrearModelo.php';

class horaExtraCrearControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new horaExtraCrearModelo($this->db);
    }

    public function index()
    {
        $idUsuarioLogueado = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;

        if ($idUsuarioLogueado === 0) {
            echo "<script>alert('Sesión expirada.'); window.location.href='index.php';</script>";
            return;
        }

        $datosTecnico = $this->modelo->obtenerDatosTecnicoPorUsuario($idUsuarioLogueado);

        if (!$datosTecnico || empty($datosTecnico['id_tecnico'])) {
            echo "<script>alert('Tu usuario no está vinculado a un perfil de técnico.'); window.history.back();</script>";
            return;
        }

        $clientes = $this->modelo->obtenerClientesActivos();
        $puntos = $this->modelo->obtenerPuntosActivos();

        $titulo = "Reportar Horas Extra";
        $vistaContenido = "app/views/horaExtra/horaExtraCrearVista.php";
        include "app/views/plantillaVista.php";
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idUsuarioLogueado = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;
            $datosTecnico = $this->modelo->obtenerDatosTecnicoPorUsuario($idUsuarioLogueado);

            if (!$datosTecnico) {
                echo "<script>alert('Error de sesión del técnico.'); window.history.back();</script>";
                return;
            }

            $idTecnicoActual = $datosTecnico['id_tecnico'];
            $idCliente = !empty($_POST['id_cliente']) ? (int) $_POST['id_cliente'] : null;
            $idPunto = !empty($_POST['id_punto']) ? (int) $_POST['id_punto'] : null;
            $fechaReporte = $_POST['fecha_reporte'] ?? '';
            $horaInicio = $_POST['hora_inicio'] ?? '';
            $horaFin = $_POST['hora_fin'] ?? '';
            $justificacion = trim($_POST['justificacion'] ?? '');

            if (empty($fechaReporte) || empty($horaInicio) || empty($horaFin) || empty($justificacion) || empty($idPunto)) {
                echo "<script>alert('⚠️ Por favor completa todos los campos requeridos.'); window.history.back();</script>";
                return;
            }

            // === CÁLCULO SEGURO EN BACKEND ===
            $inicio = new DateTime($horaInicio);
            $fin = new DateTime($horaFin);
            if ($fin < $inicio) {
                $fin->modify('+1 day');
            }
            $diferencia = $inicio->diff($fin);
            $totalHorasDecimal = round($diferencia->h + ($diferencia->i / 60), 2);

            $datosGuardar = [
                'id_tecnico' => $idTecnicoActual,
                'id_cliente' => $idCliente,
                'id_punto'   => $idPunto,
                'fecha_reporte' => $fechaReporte,
                'hora_inicio'   => $horaInicio,
                'hora_fin'      => $horaFin,
                'total_horas'   => $totalHorasDecimal,
                'justificacion_tecnico' => $justificacion
            ];

            $idRegistroHE = $this->modelo->guardarHoraExtra($datosGuardar);

            if ($idRegistroHE) {
                // === PROCESAR ARCHIVOS SUBIDOS ===
                if (isset($_FILES['evidencias']) && count($_FILES['evidencias']['name']) > 0) {
                    
                    $nombreLimpio = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $datosTecnico['nombre_tecnico']);
                    $nombreLimpio = preg_replace('/[^A-Za-z0-9\s_]/', '', $nombreLimpio);
                    $nombreTecnicoSanitizado = strtoupper(preg_replace('/\s+/', '_', trim($nombreLimpio)));
                    $mesAnio = date('Y-m', strtotime($fechaReporte));

                    $carpetaSubRuta = 'app/uploads/horas_extra/' . $nombreTecnicoSanitizado . '/' . $mesAnio . '/';
                    $carpetaDestinoFisica = __DIR__ . '/../../../' . $carpetaSubRuta;

                    if (!file_exists($carpetaDestinoFisica)) {
                        mkdir($carpetaDestinoFisica, 0777, true);
                    }

                    $totalFotos = count($_FILES['evidencias']['name']);
                    for ($i = 0; $i < $totalFotos; $i++) {
                        if ($_FILES['evidencias']['error'][$i] === UPLOAD_ERR_OK) {
                            $tmpName = $_FILES['evidencias']['tmp_name'][$i];
                            $nombreNuevo = 'HE_' . $idRegistroHE . '_FOTO_' . uniqid() . '.jpg';
                            $rutaFinalServidor = $carpetaDestinoFisica . $nombreNuevo;
                            $rutaParaBD = $carpetaSubRuta . $nombreNuevo;

                            if ($this->optimizarImagen($tmpName, $rutaFinalServidor, 1000, 75)) {
                                $this->modelo->guardarEvidenciaFoto($idRegistroHE, $rutaParaBD);
                            }
                        }
                    }
                }

                echo "<script>
                    alert('✅ Horas extra registradas con éxito.');
                    window.location.href = 'index.php?pagina=inicio';
                </script>";
            } else {
                echo "<script>alert('❌ Error al guardar el registro.'); window.history.back();</script>";
            }
        }
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

        if (!$imagenOriginal) return move_uploaded_file($rutaOrigen, $rutaDestino);

        if (function_exists('exif_read_data') && ($mime == 'image/jpeg' || $mime == 'image/jpg')) {
            $exif = @exif_read_data($rutaOrigen);
            if ($exif && isset($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 3: $imagenOriginal = imagerotate($imagenOriginal, 180, 0); break;
                    case 6:
                        $imagenOriginal = imagerotate($imagenOriginal, -90, 0);
                        $temp = $anchoOriginal; $anchoOriginal = $altoOriginal; $altoOriginal = $temp;
                        break;
                    case 8:
                        $imagenOriginal = imagerotate($imagenOriginal, 90, 0);
                        $temp = $anchoOriginal; $anchoOriginal = $altoOriginal; $altoOriginal = $temp;
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
}