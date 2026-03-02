<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/tipoNovedadEditarModelo.php';

class TipoNovedadEditarControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new TipoNovedadEditarModelo($this->db);
    }

    public function index()
    {
        $errores = [];
        $datos = [];
        $id = null;

        // --- LÓGICA PARA CAPTURAR EL ID MANUALMENTE ---
        if (isset($_POST['id_tipo_novedad'])) {
            $id = $_POST['id_tipo_novedad'];
        } 
        elseif (isset($_GET['ruta'])) {
            $partes = explode('/', rtrim($_GET['ruta'], '/'));
            if (isset($partes[1]) && is_numeric($partes[1])) {
                $id = $partes[1];
            }
        }
        elseif (isset($_GET['id'])) {
            $id = $_GET['id'];
        }

        // --- PROCESO ---

        // A. GUARDAR CAMBIOS (POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombreRaw = trim($_POST['nombre_novedad'] ?? '');
            
            // CAMBIO AQUÍ: Aplicamos Capitalize antes de guardar
            $nombre = mb_convert_case($nombreRaw, MB_CASE_TITLE, "UTF-8");
            
            $estado = $_POST['estado'] ?? '1';

            if (empty($nombre)) {
                $errores[] = "El nombre de la novedad es obligatorio.";
            }

            if (empty($errores)) {
                if ($this->modelo->actualizarTipoNovedad($id, $nombre, $estado)) {
                    header("Location: " . BASE_URL . "tipoNovedadVer");
                    exit();
                } else {
                    $errores[] = "Error: Nombre duplicado o fallo en base de datos.";
                }
            }
            // Recargamos datos para mostrar el error
            $datos = ['id_tipo_novedad' => $id, 'nombre_novedad' => $nombre, 'estado' => $estado];
        } 
        
        // B. MOSTRAR FORMULARIO (GET)
        else {
            if ($id) {
                $datos = $this->modelo->obtenerPorId($id);
                if (!$datos) {
                    header("Location: " . BASE_URL . "tipoNovedadVer");
                    exit();
                }
            } else {
                header("Location: " . BASE_URL . "tipoNovedadVer");
                exit();
            }
        }

        $titulo = "Editar Tipo de Novedad";
        $vistaContenido = "app/views/admin/tipoNovedadEditarVista.php";
        include "app/views/plantillaVista.php";
    }
}