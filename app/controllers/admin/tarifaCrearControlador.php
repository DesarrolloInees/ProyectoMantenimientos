<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/tarifaCrearModelo.php';

class tarifaCrearControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new TarifaCrearModelo($this->db);
    }

    public function index()
    {
        $errores = [];

        // 1. Procesar Formulario Masivo
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $idMaquina = $_POST['id_tipo_maquina'] ?? '';
            $anio = $_POST['año_vigencia'] ?? date('Y');
            // Aquí recibimos la matriz: precios[id_manto][id_modalidad]
            $precios = $_POST['precios'] ?? [];

            if (empty($idMaquina)) $errores[] = "Selecciona una Máquina.";
            if (empty($precios)) $errores[] = "No has ingresado ningún precio.";

            if (empty($errores)) {
                // Llamamos a la nueva función masiva
                if ($this->modelo->guardarTarifasMasivas($idMaquina, $anio, $precios)) {
                    // Redirigir con éxito
                    echo "<script>alert('¡Tarifas guardadas correctamente!'); window.location.href='" . BASE_URL . "tarifaVer';</script>";
                    exit();
                } else {
                    $errores[] = "Hubo un error al guardar. Verifica que no existan duplicados.";
                }
            }
        }

        // 2. Cargar datos para la vista
        $listaMaquinas = $this->modelo->obtenerTiposMaquina();
        $listaMantenimientos = $this->modelo->obtenerTiposMantenimiento();
        $listaModalidades = $this->modelo->obtenerModalidades();

        $titulo = "Crear Tarifas Masivas";
        $vistaContenido = "app/views/admin/tarifaCrearVista.php";
        include "app/views/plantillaVista.php";
    }
}
