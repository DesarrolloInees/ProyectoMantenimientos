<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/parqueadero/parqueaderoHistorialModelo.php';

class ParqueaderoHistorialControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ParqueaderoHistorialModelo($this->db);
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

        // Obtener historial de facturas del técnico
        $facturas = $this->modelo->obtenerHistorialPorTecnico($idTecnicoActual);

        // Obtener puntos activos para llenar el select de la modal de edición
        $puntos = $this->modelo->obtenerPuntosActivos();

        // Cargar Vista
        $titulo = "Historial de Parqueaderos";
        $vistaContenido = "app/views/parqueadero/parqueaderoHistorialVista.php";
        include "app/views/plantillaVista.php";
    }

    public function ajaxEditarFactura()
    {
        while (ob_get_level())
            ob_end_clean();
        header('Content-Type: application/json');

        $idUsuarioLogueado = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;
        $datosTecnico = $this->modelo->obtenerDatosTecnicoPorUsuario($idUsuarioLogueado);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $datosTecnico) {
            $idTecnicoActual = (int) $datosTecnico['id_tecnico'];
            $idFactura = isset($_POST['id_factura']) ? (int) $_POST['id_factura'] : 0;
            $fechaServicio = $_POST['fecha_servicio'] ?? '';
            $horaInicio = $_POST['hora_inicio'] ?? '';
            $horaFin = $_POST['hora_fin'] ?? '';
            $idPunto = !empty($_POST['id_punto']) ? (int) $_POST['id_punto'] : 0;
            $valorFactura = $_POST['valor_factura'] ?? 0;
            $numeroFactura = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $_POST['numero_factura'] ?? ''));

            if (empty($fechaServicio) || empty($horaInicio) || empty($horaFin) || empty($idPunto) || empty($numeroFactura) || empty($valorFactura) || $idFactura === 0) {
                echo json_encode(['success' => false, 'msj' => 'Todos los campos marcados son obligatorios.']);
                exit;
            }

            // Validar existencia y pertenencia
            $facturaActual = $this->modelo->obtenerFacturaPorIdYTecnico($idFactura, $idTecnicoActual);
            if (!$facturaActual) {
                echo json_encode(['success' => false, 'msj' => 'El registro no existe o no te pertenece.']);
                exit;
            }

            $datosActualizar = [
                'id_factura' => $idFactura,
                'id_tecnico' => $idTecnicoActual,
                'id_punto' => $idPunto,
                'fecha_servicio' => $fechaServicio,
                'hora_inicio' => $horaInicio,
                'hora_fin' => $horaFin,
                'numero_factura' => $_POST['numero_factura'],
                'valor_factura' => $valorFactura,
                'ruta_foto' => null
            ];

            // === PROCESAR NUEVA FOTO SI SE ADJUNTÓ ===
            if (isset($_FILES['foto_factura']) && $_FILES['foto_factura']['error'] === UPLOAD_ERR_OK) {

                $nombreOriginal = $datosTecnico['nombre_tecnico'];
                $nombreLimpio = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nombreOriginal);
                $nombreLimpio = preg_replace('/[^A-Za-z0-9\s_]/', '', $nombreLimpio);
                $nombreTecnicoSanitizado = strtoupper(preg_replace('/\s+/', '_', trim($nombreLimpio)));
                $mesAnio = date('Y-m', strtotime($fechaServicio));

                $carpetaSubRuta = 'app/uploads/parqueaderos/' . $nombreTecnicoSanitizado . '/' . $mesAnio . '/';
                $carpetaDestinoFisica = __DIR__ . '/../../../' . $carpetaSubRuta;

                if (!file_exists($carpetaDestinoFisica)) {
                    mkdir($carpetaDestinoFisica, 0777, true);
                }

                $extension = strtolower(pathinfo($_FILES['foto_factura']['name'], PATHINFO_EXTENSION));
                $nombreNuevo = 'PARQ_' . $nombreTecnicoSanitizado . '_' . $fechaServicio . '_FACT_' . $numeroFactura . '_EDIT_' . uniqid() . '.' . $extension;

                $rutaFinalServidor = $carpetaDestinoFisica . $nombreNuevo;
                $rutaParaBD = $carpetaSubRuta . $nombreNuevo;

                if (move_uploaded_file($_FILES['foto_factura']['tmp_name'], $rutaFinalServidor)) {
                    $datosActualizar['ruta_foto'] = $rutaParaBD;

                    // Borrar la foto antigua del servidor
                    $rutaAntiguaFisica = __DIR__ . '/../../../' . $facturaActual['ruta_foto'];
                    if (file_exists($rutaAntiguaFisica)) {
                        @unlink($rutaAntiguaFisica);
                    }
                }
            }

            if ($this->modelo->actualizarFacturaParqueadero($datosActualizar)) {
                echo json_encode(['success' => true, 'msj' => 'Factura actualizada con éxito.']);
                exit;
            }
        }

        echo json_encode(['success' => false, 'msj' => 'No se pudo actualizar el registro.']);
        exit;
    }

    public function ajaxEliminarFoto()
    {
        while (ob_get_level())
            ob_end_clean();
        header('Content-Type: application/json');

        $idUsuarioLogueado = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;
        $datosTecnico = $this->modelo->obtenerDatosTecnicoPorUsuario($idUsuarioLogueado);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $datosTecnico) {
            $idFactura = isset($_POST['id_factura']) ? (int) $_POST['id_factura'] : 0;
            $idTecnico = (int) $datosTecnico['id_tecnico'];

            $factura = $this->modelo->obtenerFacturaPorIdYTecnico($idFactura, $idTecnico);

            if ($factura) {
                // Eliminar archivo físico del disco si existe
                $rutaFisica = __DIR__ . '/../../../' . $factura['ruta_foto'];
                if (!empty($factura['ruta_foto']) && file_exists($rutaFisica)) {
                    @unlink($rutaFisica);
                }

                // Limpiar la ruta en la base de datos
                if ($this->modelo->eliminarFotoFactura($idFactura, $idTecnico)) {
                    echo json_encode(['success' => true, 'msj' => 'Foto eliminada correctamente. Recuerda adjuntar una nueva foto antes de guardar.']);
                    exit;
                }
            }
        }

        echo json_encode(['success' => false, 'msj' => 'No se pudo eliminar la imagen de la factura.']);
        exit;
    }
}