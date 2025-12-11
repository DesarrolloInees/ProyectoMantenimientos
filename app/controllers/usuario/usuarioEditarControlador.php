<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/usuario/usuarioEditarModelo.php';

class usuarioEditarControlador {
    
    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new UsuarioEditarModelo($this->db);
    }

    public function index() {
        
        $errores = [];
        $mensaje = "";
        
        // --- 1. PROCESAR FORMULARIO (GUARDAR) ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            $id_usuario = $_POST['usuario_id'] ?? null; // ID Oculto en el form
            
            $datos = [
                'nombre'       => trim($_POST['nombre'] ?? ''),
                'cedula'       => trim($_POST['cedula'] ?? ''),
                'cargo'        => trim($_POST['cargo'] ?? ''),
                'email'        => trim($_POST['email'] ?? ''),
                'celular'      => trim($_POST['celular'] ?? ''),
                'usuario'      => trim($_POST['usuario'] ?? ''),
                'pass'         => $_POST['pass'] ?? '', // Puede venir vacío
                'nivel_acceso' => $_POST['nivel_acceso'] ?? '',
                'estado'       => $_POST['estado'] ?? 'activo'
            ];

            if ($this->modelo->editarUsuario($id_usuario, $datos)) {
                // Redirigir al listado si todo salió bien
                header("Location: " . BASE_URL . "usuarioVer");
                exit();
            } else {
                $errores[] = "Error al actualizar. Posible duplicado de Cédula o Usuario.";
            }
        }

        // --- 2. CARGAR DATOS PARA LA VISTA (GET) ---
        
        // Intentar obtener ID de la URL (gracias al Router)
        // Si venimos de un POST fallido, usamos el ID del POST. Si es GET, usamos $_GET['id']
        $id_para_cargar = $_POST['usuario_id'] ?? ($_GET['id'] ?? null);

        if (!$id_para_cargar) {
            header("Location: " . BASE_URL . "usuarioVer");
            exit();
        }

        // Obtener info del usuario de la BD
        $usuarioInfo = $this->modelo->obtenerUsuarioPorId($id_para_cargar);
        $roles = $this->modelo->obtenerTiposUsuario();

        if (!$usuarioInfo) {
            // Si el ID no existe en BD
            $errores[] = "El usuario solicitado no existe.";
        }

        // --- 3. PREPARAR VISTA ---
        $data = [
            'titulo'       => 'Editar Usuario',
            'usuario'      => $usuarioInfo,
            'tiposUsuario' => $roles,
            'errores'      => $errores
        ];

        // Cargar vista
        $vistaContenido = "app/views/usuario/usuarioEditarVista.php";
        include "app/views/plantillaVista.php";
    }
}
?>