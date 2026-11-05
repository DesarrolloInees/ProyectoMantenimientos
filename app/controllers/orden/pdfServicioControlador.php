<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/orden/ordenDetalleModelo.php';
require_once __DIR__ . '/../../models/orden/serviciosPdfModelo.php';
require_once __DIR__ . '/../../helpers/servicioPdfGenerator.php';

class PdfServicioControlador
{
    private $modelo;
    private $db;
    private $pdfGenerator;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new serviciosPdfModelo($this->db);
        $this->pdfGenerator = new ServicioPdfGenerator();
    }

    public function generar()
    {
        $idOrden = $_GET['id'] ?? null;

        if (!$idOrden) {
            die("Error: ID de orden no proporcionado.");
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $idRolUsuario = isset($_SESSION['nivel_acceso']) ? $_SESSION['nivel_acceso'] : null;

        if ($idRolUsuario == 4 && $idOrden) {
            $paramValor = $this->modelo->obtenerParametro('meses_restriccion_prosegur');
            $mesesRestriccion = $paramValor ? (int) $paramValor : 1;

            if (!$this->modelo->puedeVerOrden($idOrden, $idRolUsuario, $mesesRestriccion)) {
                die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'>
                        <h2 style='color:red;'>Acceso Denegado</h2>
                        <p>No tienes los permisos suficientes para ver esta orden de servicio porque aún está en periodo de restricción.</p>
                    </div>");
            }
        }

        $resultado = $this->pdfGenerator->generarPdf($this->modelo, $idOrden);

        if (!$resultado['ok']) {
            echo "<h1>Error generando PDF del Servicio</h1><p>" . htmlspecialchars($resultado['error'], ENT_QUOTES, 'UTF-8') . "</p>";
            die();
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $resultado['nombreArchivo'] . '"');
        header('Content-Length: ' . strlen($resultado['contenido']));

        echo $resultado['contenido'];
        exit;
    }
}
