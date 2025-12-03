<?php
class ordenVerControlador {
    private $modelo;

    public function __construct() {
        $database = new Database();
        $this->modelo = new ordenVerModelo($database->getConnection());
    }

    // AGREGA ESTA FUNCIÓN AQUÍ 👇
    // Sirve de puente: si el router busca "index", lo manda a "cargarVista"
    public function index() {
        $this->cargarVista();
    }

    public function cargarVista() {
        $titulo = "Historial por Días";
        $vistaContenido = "app/views/orden/ordenVerVista.php";
        include "app/views/plantillaVista.php";
    }

    public function ajaxListar() {
        ob_clean();
        header('Content-Type: application/json');
        // Llamamos al método por fecha
        $datos = $this->modelo->listarOrdenesPorFecha();
        echo json_encode(['data' => $datos], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
?>