<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/puntoEditarModelo.php';

class puntoEditarControlador {
    
    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new PuntoEditarModelo($this->db);
    }

    public function index() {
        $id = $_GET['id'] ?? $_POST['id_punto'] ?? null;
        if (!$id) { header("Location: " . BASE_URL . "puntoVer"); exit(); }

        $errores = [];
        $datos = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'nombre_punto' => trim($_POST['nombre_punto']),
                'direccion' => trim($_POST['direccion']),
                'codigo_1' => trim($_POST['codigo_1']),
                'codigo_2' => trim($_POST['codigo_2']),
                'id_municipio' => $_POST['id_municipio'],
                'id_delegacion' => $_POST['id_delegacion'],
                'id_modalidad' => $_POST['id_modalidad'],
                'id_cliente' => $_POST['id_cliente'],
                'estado' => $_POST['estado']
            ];

            if ($this->modelo->editarPunto($id, $datos)) {
                header("Location: " . BASE_URL . "puntoVer");
                exit();
            } else {
                $errores[] = "Error al actualizar.";
            }
        }

        if (empty($datos)) {
            $datos = $this->modelo->obtenerPuntoPorId($id);
            if (!$datos) { header("Location: " . BASE_URL . "puntoVer"); exit(); }
        }

        $listaClientes = $this->modelo->obtenerClientes();
        $listaMunicipios = $this->modelo->obtenerMunicipios();
        $listaDelegaciones = $this->modelo->obtenerDelegaciones();
        $listaModalidades = $this->modelo->obtenerModalidades();

        $titulo = "Editar Punto";
        $vistaContenido = "app/views/admin/puntoEditarVista.php";
        include "app/views/plantillaVista.php";
    }
}