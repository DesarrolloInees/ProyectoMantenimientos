<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/puntoCrearModelo.php';

class puntoCrearControlador {
    
    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new PuntoCrearModelo($this->db);
    }

    public function index() {
        $errores = [];
        $datos = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'nombre_punto' => trim($_POST['nombre_punto'] ?? ''),
                'direccion'    => trim($_POST['direccion'] ?? ''),
                'codigo_1'     => trim($_POST['codigo_1'] ?? ''),
                'codigo_2'     => trim($_POST['codigo_2'] ?? ''),
                'id_municipio' => $_POST['id_municipio'] ?? '',
                'id_delegacion'=> $_POST['id_delegacion'] ?? '',
                'id_modalidad' => $_POST['id_modalidad'] ?? '',
                'id_cliente'   => $_POST['id_cliente'] ?? ''
            ];

            // Validaciones
            if (empty($datos['nombre_punto'])) $errores[] = "El nombre del punto es obligatorio.";
            if (empty($datos['id_cliente'])) $errores[] = "Debes asignar un cliente.";
            if (empty($datos['id_municipio'])) $errores[] = "Debes seleccionar un municipio.";
            if (empty($datos['id_modalidad'])) $errores[] = "La modalidad es obligatoria.";

            if (empty($errores)) {
                if ($this->modelo->crearPunto($datos)) {
                    header("Location: " . BASE_URL . "puntoVer");
                    exit();
                } else {
                    $errores[] = "Error al guardar en BD.";
                }
            }
        }

        // Cargar listas
        $listaClientes = $this->modelo->obtenerClientes();
        $listaMunicipios = $this->modelo->obtenerMunicipios();
        $listaDelegaciones = $this->modelo->obtenerDelegaciones();
        $listaModalidades = $this->modelo->obtenerModalidades();

        $titulo = "Crear Punto";
        $vistaContenido = "app/views/admin/puntoCrearVista.php";
        include "app/views/plantillaVista.php";
    }
}