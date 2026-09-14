<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

class inventarioTecnicoControlador {
    private $db;
    private $modelo;

    public function __construct($conexionDb) {
        $this->db = $conexionDb;
        
        // Incluir o inicializar el modelo creado previamente
        require_once_safe(__DIR__ . '/../../models/inventario/InventarioTecnicoModelo.php');
        $this->modelo = new inventarioTecnicoModelo($this->db);
    }

    public function verInventario() {
        // Verificar nivel de acceso (por ejemplo, nivel 3 para técnico o 5 para supervisor)
        $nivel = $_SESSION['nivel_acceso'] ?? 0;
        $idUsuario = $_SESSION['usuario_id'] ?? null;

        if ($nivel < 3) {
            header("HTTP/1.1 403 Forbidden");
            die("Acceso denegado.");
        }

        // Si es un técnico (nivel 3), podemos filtrar por su ID; si es supervisor, ver todo o según convenga
        $datosInventario = ($nivel == 3) 
            ? $this->modelo->obtenerInventario($idUsuario) 
            : $this->modelo->obtenerInventario();

        // Variables disponibles para la vista
        $inventario = $datosInventario;

        // Cargar la vista correspondiente
        require_once __DIR__ . '/../../views/inventario/inventarioTecnicoVista.php';
    }
}