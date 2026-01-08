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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // CAMBIO: Recibimos un array. Si no llega nada, es un array vacío.
            $idsMaquinas = $_POST['ids_maquinas'] ?? [];
            $anio = $_POST['año_vigencia'] ?? date('Y');
            $precios = $_POST['precios'] ?? [];

            // Validamos que sea un array y tenga datos
            if (empty($idsMaquinas) || !is_array($idsMaquinas)) {
                $errores[] = "Debes seleccionar al menos una Máquina.";
            }

            if (empty($precios)) $errores[] = "No has ingresado ningún precio.";

            if (empty($errores)) {
                // Pasamos el array de IDs al modelo
                if ($this->modelo->guardarTarifasMasivas($idsMaquinas, $anio, $precios)) {
                    // Contamos cuántas máquinas se actualizaron para el mensaje
                    $cantidad = count($idsMaquinas);
                    echo "<script>alert('¡Tarifas guardadas correctamente para $cantidad máquinas!'); window.location.href='" . BASE_URL . "tarifaVer';</script>";
                    exit();
                } else {
                    $errores[] = "Hubo un error al guardar. Revisa el log de errores.";
                }
            }
        }

        // Cargar datos vista
        $listaMaquinas = $this->modelo->obtenerTiposMaquina();
        $listaMantenimientos = $this->modelo->obtenerTiposMantenimiento();
        $listaModalidades = $this->modelo->obtenerModalidades();

        $titulo = "Crear Tarifas Masivas";
        $vistaContenido = "app/views/admin/tarifaCrearVista.php";
        include "app/views/plantillaVista.php";
    }
    public function verificarMaquinas()
    {
        // Solo respondemos si nos envían el año
        if (isset($_GET['anio'])) {
            $anio = $_GET['anio'];
            $maquinasOcupadas = $this->modelo->obtenerMaquinasConTarifa($anio);

            // Devolvemos la lista en formato JSON para que JS la entienda
            header('Content-Type: application/json');
            echo json_encode($maquinasOcupadas);
            exit(); // Terminamos aquí para no cargar toda la vista HTML
        }
    }
}
