<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/parqueadero/parqueaderoCrearModelo.php';

class ParqueaderoCrearControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ParqueaderoCrearModelo($this->db);
    }

    // Renderiza el formulario
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

        $puntos = $this->modelo->obtenerPuntosActivos();

        $titulo = "Registrar Parqueadero";
        $vistaContenido = "app/views/parqueadero/parqueaderoCrearVista.php";
        include "app/views/plantillaVista.php";
    }

    // Procesa el formulario y optimiza la imagen respetando su orientación
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

            $nombreOriginal = $datosTecnico['nombre_tecnico'];
            $nombreLimpio = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nombreOriginal);
            $nombreLimpio = preg_replace('/[^A-Za-z0-9\s_]/', '', $nombreLimpio);
            $nombreTecnicoSanitizado = strtoupper(preg_replace('/\s+/', '_', trim($nombreLimpio)));

            if (!isset($_FILES['foto_factura']) || $_FILES['foto_factura']['error'] !== UPLOAD_ERR_OK) {
                echo "<script>alert('Error al subir la imagen de la factura.'); window.history.back();</script>";
                return;
            }

            $fechaServicio = $_POST['fecha_servicio'];
            $numeroFactura = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $_POST['numero_factura']));
            $mesAnio = date('Y-m', strtotime($fechaServicio));

            $carpetaSubRuta = 'app/uploads/parqueaderos/' . $nombreTecnicoSanitizado . '/' . $mesAnio . '/';
            $carpetaDestinoFisica = __DIR__ . '/../../../' . $carpetaSubRuta;

            if (!file_exists($carpetaDestinoFisica)) {
                mkdir($carpetaDestinoFisica, 0777, true);
            }

            $nombreNuevo = 'PARQ_' . $nombreTecnicoSanitizado . '_' . $fechaServicio . '_FACT_' . $numeroFactura . '.jpg';

            $rutaFinalServidor = $carpetaDestinoFisica . $nombreNuevo;
            $rutaParaBD = $carpetaSubRuta . $nombreNuevo;

            // Optimización conservando orientación inteligente
            if ($this->optimizarImagen($_FILES['foto_factura']['tmp_name'], $rutaFinalServidor, 1200, 80)) {

                $datos = [
                    'id_tecnico'     => $idTecnicoActual,
                    'id_punto'       => $_POST['id_punto'],
                    'fecha_servicio' => $fechaServicio,
                    'hora_inicio'    => $_POST['hora_inicio'],
                    'hora_fin'       => $_POST['hora_fin'],
                    'numero_factura' => $_POST['numero_factura'],
                    'valor_factura'  => $_POST['valor_factura'],
                    'ruta_foto'      => $rutaParaBD
                ];

                if ($this->modelo->guardarFactura($datos)) {
                    echo "<script>
                        alert('✅ Factura registrada correctamente.');
                        window.location.href = 'index.php?pagina=parqueaderoHistorial';
                    </script>";
                } else {
                    @unlink($rutaFinalServidor);
                    echo "<script>alert('❌ Error al guardar en BD.'); window.history.back();</script>";
                }
            } else {
                echo "<script>alert('❌ Error al procesar la imagen.'); window.history.back();</script>";
            }
        }
    }

    /**
     * Procesa y comprime la imagen:
     * 1. Ajusta la orientación real del sensor usando los metadatos EXIF.
     * 2. No fuerza la rotación a vertical, permitiendo diagramas/fotos horizontales.
     * 3. Escala proporcionalmente y guarda en formato JPG optimizado.
     */
    private function optimizarImagen($rutaOrigen, $rutaDestino, $anchoMaximo = 1200, $calidad = 80)
    {
        ini_set('memory_limit', '256M');
        if (!extension_loaded('gd')) {
            return move_uploaded_file($rutaOrigen, $rutaDestino);
        }

        $info = @getimagesize($rutaOrigen);
        if (!$info) {
            return move_uploaded_file($rutaOrigen, $rutaDestino);
        }

        $mime          = $info['mime'];
        $anchoOriginal = $info[0];
        $altoOriginal  = $info[1];

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

        if (!$imagenOriginal) {
            return move_uploaded_file($rutaOrigen, $rutaDestino);
        }

        // Corregir metadatos EXIF reales del sensor del teléfono
        if (function_exists('exif_read_data') && ($mime === 'image/jpeg' || $mime === 'image/jpg')) {
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

        // Redimensionar proporcionalmente
        if ($anchoOriginal > $anchoMaximo) {
            $ratio      = $anchoMaximo / $anchoOriginal;
            $nuevoAncho = $anchoMaximo;
            $nuevoAlto  = round($altoOriginal * $ratio);
        } else {
            $nuevoAncho = $anchoOriginal;
            $nuevoAlto  = $altoOriginal;
        }

        $imagenRedimensionada = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
        imagecopyresampled($imagenRedimensionada, $imagenOriginal, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $anchoOriginal, $altoOriginal);

        $exito = imagejpeg($imagenRedimensionada, $rutaDestino, $calidad);

        imagedestroy($imagenOriginal);
        imagedestroy($imagenRedimensionada);

        return $exito;
    }
}