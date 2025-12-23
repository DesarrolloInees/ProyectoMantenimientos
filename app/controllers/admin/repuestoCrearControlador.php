<?php
// app/controllers/admin/repuestoCrearControlador.php

if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

// IMPORTANTE: Como estamos dentro de la carpeta 'admin', salimos 2 niveles (../../) para llegar a la raíz de 'app'
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

        // Instanciamos el modelo (asegúrate de que la clase en el archivo del modelo se llame así)
        $this->modelo = new RepuestoCrearModelo($this->db);
    }

    /**
     * Muestra el formulario y procesa el guardado en la misma función (POST).
     */
    public function index()
    {

        $errores = [];
        $datosPrevios = []; // Para repoblar el form si hay error

        // ---------------------------------------------------------
        // 1. DETECTAR SI SE ENVIÓ EL FORMULARIO (POST)
        // ---------------------------------------------------------
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Recolectar datos
            $datosPrevios = [
                'nombre_repuesto'   => trim($_POST['nombre_repuesto'] ?? ''),
                'codigo_referencia' => trim($_POST['codigo_referencia'] ?? ''),
                'estado'            => $_POST['estado'] ?? '1' // Por defecto Activo (1)
            ];

            // Validaciones básicas
            if (empty($datosPrevios['nombre_repuesto'])) {
                $errores[] = "El nombre del repuesto es obligatorio.";
            }

            // Si no hay errores, intentamos guardar
            if (empty($errores)) {
                // Llamamos al modelo
                if ($this->modelo->crearRepuesto($datosPrevios)) {
                    // ¡ÉXITO! Redirigimos a la lista (ajusta la ruta si se llama diferente)
                    header("Location: " . BASE_URL . "repuestoVer");
                    exit();
                } else {
                    $errores[] = "Error al guardar en BD. Verifica que no exista un código de referencia duplicado.";
                }
            }
        }

        // ---------------------------------------------------------
        // 2. PREPARAR LA VISTA (GET o Error en POST)
        // ---------------------------------------------------------

        $titulo = "Crear Nuevo Repuesto";

        // Ruta apuntando a la carpeta admin
        $vistaContenido = "app/views/admin/repuestoCrearVista.php";

        // Incluimos la plantilla maestra
        include "app/views/plantillaVista.php";
    }
}
