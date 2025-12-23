<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/controlRemisionEditarModelo.php';

class controlRemisionEditarControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ControlRemisionEditarModelo($this->db);
    }

    public function index()
    {
        $errores = [];
        $id = $_GET['id'] ?? null;

        // Validar que venga el ID
        if (!$id) {
            header("Location: " . BASE_URL . "controlRemisionVer");
            exit();
        }

        // Obtener datos actuales
        $datos = $this->modelo->obtenerRemisionPorId($id);
        $listaTecnicos = $this->modelo->obtenerTecnicos();

        if (!$datos) {
            // Si el ID no existe en BD
            header("Location: " . BASE_URL . "controlRemisionVer");
            exit();
        }

        // PROCESAR POST (GUARDAR)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $datosForm = [
                'id_control'      => $id,
                'numero_remision' => trim($_POST['numero_remision'] ?? ''),
                'id_tecnico'      => $_POST['id_tecnico'] ?? '',
                'estado'          => $_POST['estado'] ?? ''
            ];

            // Validaciones
            if (empty($datosForm['numero_remision'])) $errores[] = "El número es obligatorio.";
            if (empty($datosForm['id_tecnico'])) $errores[] = "El técnico es obligatorio.";

            if (empty($errores)) {
                $resp = $this->modelo->actualizarRemision($datosForm);

                if ($resp === true) {
                    header("Location: " . BASE_URL . "controlRemisionVer");
                    exit();
                } elseif ($resp === "DUPLICADO") {
                    $errores[] = "Ese número de remisión ya existe en el sistema.";
                } else {
                    $errores[] = "Error al actualizar en la base de datos.";
                }
            }
            // Actualizamos la variable $datos para que el formulario no pierda lo que escribió el usuario si hubo error
            $datos = array_merge($datos, $datosForm);
        }

        $titulo = "Editar Remisión";
        $vistaContenido = "app/views/admin/controlRemisionEditarVista.php";
        include "app/views/plantillaVista.php";
    }
}
