<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';

class TipoNovedadEliminarControlador
{
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
    }

    public function index()
    {
        $id = null;

        // --- LÓGICA MANUAL PARA OBTENER EL ID DE LA URL ---
        if (isset($_GET['ruta'])) {
            $partes = explode('/', rtrim($_GET['ruta'], '/'));
            if (isset($partes[1]) && is_numeric($partes[1])) {
                $id = $partes[1];
            }
        } elseif (isset($_GET['id'])) {
            $id = $_GET['id'];
        }

        if ($id) {
            // Opción A: Desactivar (Recomendado)
            $sql = "UPDATE tipo_novedad SET estado = 0 WHERE id_tipo_novedad = :id";
            
            // Opción B: Eliminar Físicamente (Si prefieres borrarlo del todo, descomenta esta y comenta la de arriba)
            // $sql = "DELETE FROM tipo_novedad WHERE id_tipo_novedad = :id";

            try {
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
            } catch (PDOException $e) {
                // Si falla (ej: llave foránea), podríamos redirigir con error, 
                // pero por simplicidad redirigimos al ver.
                error_log("Error al eliminar tipo novedad: " . $e->getMessage());
            }
        }

        // Redirigir siempre a la lista
        header("Location: " . BASE_URL . "tipoNovedadVer");
        exit();
    }
}