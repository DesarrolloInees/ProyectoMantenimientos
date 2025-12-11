<?php
// controladores/usuario/usuarioCrearControlador.php

if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/usuario/usuarioCrearModelo.php';

class usuarioCrearControlador {
    
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new UsuarioCrearModelo($this->db);
    }

    /**
     * Este método maneja TANTO la carga del formulario COMO el guardado.
     * Funciona igual que tu script antiguo: el formulario se envía a sí mismo.
     */
    public function index() {
        
        $errores = [];
        $datosPrevios = []; // Para no borrar lo que escribió el usuario si hay error

        // ---------------------------------------------------------
        // 1. DETECTAR SI SE ENVIÓ EL FORMULARIO (POST)
        // ---------------------------------------------------------
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // Recolectar y limpiar datos
            $datosPrevios = [
                'nombre'       => trim($_POST['nombre'] ?? ''),
                'cedula'       => trim($_POST['cedula'] ?? ''),
                'cargo'        => trim($_POST['cargo'] ?? ''),
                'email'        => trim($_POST['email'] ?? ''),
                'celular'      => trim($_POST['celular'] ?? ''),
                'usuario'      => trim($_POST['usuario'] ?? ''),
                'pass'         => $_POST['pass'] ?? '',
                'nivel_acceso' => $_POST['nivel_acceso'] ?? ''
            ];

            // Validaciones
            if (empty($datosPrevios['usuario']) || empty($datosPrevios['pass'])) {
                $errores[] = "Usuario y contraseña son obligatorios.";
            }
            if (empty($datosPrevios['nivel_acceso'])) {
                $errores[] = "Debes seleccionar un Rol.";
            }

            // Si no hay errores de validación, intentamos guardar
            if (empty($errores)) {
                if ($this->modelo->crearUsuario($datosPrevios)) {
                    // ¡ÉXITO! Redirigimos
                    header("Location: " . BASE_URL . "usuarioVer");
                    exit();
                } else {
                    $errores[] = "Error al guardar en BD. Verifica que la Cédula o Usuario no estén repetidos.";
                }
            }
        }

        // ---------------------------------------------------------
        // 2. PREPARAR LA VISTA (GET o Error en POST)
        // ---------------------------------------------------------
        
        $titulo = "Crear Nuevo Usuario";
        
        // Obtener roles para el select
        $tiposUsuario = $this->modelo->obtenerTiposUsuario();

        if (empty($tiposUsuario)) {
            $errores[] = "¡ALERTA! No hay roles creados en la tabla 'tipousuario'.";
        }

        // Cargamos la vista. Como ya procesamos la lógica arriba,
        // pasamos los $errores y $datosPrevios a la vista.
        $vistaContenido = "app/views/usuario/usuarioCrearVista.php";
        
        // Incluimos la plantilla maestra
        include "app/views/plantillaVista.php";
    }
}