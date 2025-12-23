<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';

class controlRemisionEliminarControlador
{

    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
    }

    public function index()
    {
        $id = $_GET['id'] ?? null;

        if ($id) {
            // Verificamos estado actual
            $sqlCheck = "SELECT estado FROM control_remisiones WHERE id_control = :id";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->bindParam(':id', $id);
            $stmtCheck->execute();
            $data = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            // Solo permitimos "eliminar" si está DISPONIBLE o ANULADA (no si ya fue USADA)
            if ($data && ($data['estado'] == 'DISPONIBLE' || $data['estado'] == 'ANULADA')) {

                // --- BORRADO LÓGICO ---
                $sql = "UPDATE control_remisiones SET estado = 'ELIMINADO' WHERE id_control = :id";

                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
            }
        }

        header("Location: " . BASE_URL . "controlRemisionVer");
        exit();
    }
}
