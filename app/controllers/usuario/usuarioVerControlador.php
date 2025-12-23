<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/usuario/usuarioVerModelo.php';

class usuarioVerControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new UsuarioVerModelo($this->db);
    }

    public function index()
    {
        $this->cargarVista();
    }

    public function cargarVista()
    {
        // 1. Obtener datos del modelo
        $listaUsuarios = $this->modelo->obtenerUsuarios();

        // 2. EMPAQUETAR LOS DATOS PARA LA VISTA
        // La vista espera una variable llamada $data['usuarios']
        $data = [
            'titulo' => 'Ver Usuarios',
            'usuarios' => $listaUsuarios
        ];

        // 3. Variables sueltas (opcional, por si tu plantilla usa $titulo aparte)
        $titulo = $data['titulo'];

        // 4. Definir y cargar vistas
        $vistaContenido = "app/views/usuario/usuarioVerVista.php";

        // Al incluir la plantilla, esta tendrá acceso a la variable $data
        include "app/views/plantillaVista.php";
    }
}
