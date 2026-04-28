<?php
// app/controllers/admin/repuestoCrearControlador.php

if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/repuestoCrearModelo.php';

class repuestoCrearControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new RepuestoCrearModelo($this->db);
    }

    public function index()
    {
        $errores = [];
        $datosPrevios = [];

        // 1. DETECTAR SI SE ENVIÓ EL FORMULARIO (POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Recolectar datos (Agregamos requiere_devolucion)
            $datosPrevios = [
                'nombre_repuesto'     => trim($_POST['nombre_repuesto'] ?? ''),
                'codigo_referencia'   => trim($_POST['codigo_referencia'] ?? ''),
                'estado'              => $_POST['estado'] ?? '1',
                'requiere_devolucion' => $_POST['requiere_devolucion'] ?? '0'
            ];

            // Validaciones básicas
            if (empty($datosPrevios['nombre_repuesto'])) {
                $errores[] = "El nombre del repuesto es obligatorio.";
            }

            // Guardar
            if (empty($errores)) {
                if ($this->modelo->crearRepuesto($datosPrevios)) {
                    header("Location: " . BASE_URL . "repuestoVer");
                    exit();
                } else {
                    $errores[] = "Error al guardar en BD. Verifica que no exista un código de referencia duplicado.";
                }
            }
        }

        // 2. PREPARAR LA VISTA
        $titulo = "Crear Nuevo Repuesto";
        $vistaContenido = "app/views/admin/repuestoCrearVista.php";
        include "app/views/plantillaVista.php";
    }
}