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

        // CORRECCIÓN: Se obtiene la información completa como Array
        $datosTecnico = $this->modelo->obtenerDatosTecnicoPorUsuario($idUsuarioLogueado);

        if (!$datosTecnico || empty($datosTecnico['id_tecnico'])) {
            echo "<script>alert('Tu usuario no está vinculado a un perfil de técnico.'); window.history.back();</script>";
            return;
        }

        // Obtener datos para los selects
        $puntos = $this->modelo->obtenerPuntosActivos();

        // Cargar Vista
        $titulo = "Registrar Parqueadero";
        $vistaContenido = "app/views/parqueadero/parqueaderoCrearVista.php";
        include "app/views/plantillaVista.php";
    }

    // Procesa el formulario y guarda la imagen
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

            // --- CORRECCIÓN DE NOMBRES Y ESPACIOS ---
            $nombreOriginal = $datosTecnico['nombre_tecnico'];

            // 1. Quitar tildes y caracteres especiales dejando solo letras y espacios
            $nombreLimpio = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nombreOriginal);
            $nombreLimpio = preg_replace('/[^A-Za-z0-9\s_]/', '', $nombreLimpio);

            // 2. Convertir múltiples espacios en un solo guion bajo
            $nombreTecnicoSanitizado = strtoupper(preg_replace('/\s+/', '_', trim($nombreLimpio)));

            // Validar subida de archivo
            if (!isset($_FILES['foto_factura']) || $_FILES['foto_factura']['error'] !== UPLOAD_ERR_OK) {
                echo "<script>alert('Error al subir la imagen de la factura.'); window.history.back();</script>";
                return;
            }

            $fechaServicio = $_POST['fecha_servicio'];
            $numeroFactura = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $_POST['numero_factura']));
            $mesAnio = date('Y-m', strtotime($fechaServicio));

            // 1. Ruta relativa para guardar en la BD (como lo verá la URL web)
            $carpetaSubRuta = 'app/uploads/parqueaderos/' . $nombreTecnicoSanitizado . '/' . $mesAnio . '/';

            // 2. Ruta física real en el disco del servidor (sube 3 niveles desde controllers/parqueadero hasta la raíz del proyecto)
            $carpetaDestinoFisica = __DIR__ . '/../../../' . $carpetaSubRuta;

            if (!file_exists($carpetaDestinoFisica)) {
                mkdir($carpetaDestinoFisica, 0777, true);
            }

            $extension = strtolower(pathinfo($_FILES['foto_factura']['name'], PATHINFO_EXTENSION));
            $nombreNuevo = 'PARQ_' . $nombreTecnicoSanitizado . '_' . $fechaServicio . '_FACT_' . $numeroFactura . '.' . $extension;

            $rutaFinalServidor = $carpetaDestinoFisica . $nombreNuevo;
            $rutaParaBD = $carpetaSubRuta . $nombreNuevo;

            if (move_uploaded_file($_FILES['foto_factura']['tmp_name'], $rutaFinalServidor)) {

                $datos = [
                    'id_tecnico' => $idTecnicoActual,
                    'id_punto' => $_POST['id_punto'],
                    'fecha_servicio' => $fechaServicio,
                    'hora_inicio' => $_POST['hora_inicio'],
                    'hora_fin' => $_POST['hora_fin'],
                    'numero_factura' => $_POST['numero_factura'],
                    'valor_factura' => $_POST['valor_factura'],
                    'ruta_foto' => $rutaParaBD
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
                echo "<script>alert('❌ Error al mover el archivo.'); window.history.back();</script>";
            }
        }
    }
}