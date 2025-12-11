<?php

if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

// 1. IMPORTAR ARCHIVOS NECESARIOS (Sin esto, PHP no encuentra las clases)
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/orden/ordenVerModelo.php';
class ordenVerControlador {
    private $modelo;
    private $db;

    public function __construct()
    {
        // 2. CORRECCIÓN: Usamos la clase 'Conexion' (no 'db')
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();

        // 3. Instanciamos el modelo pasándole la conexión activa
        $this->modelo = new ordenVerModelo($this->db);
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