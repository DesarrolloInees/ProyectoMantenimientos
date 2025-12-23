<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class TipoUsuarioVerModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function obtenerTipos()
    {
        $sql = "SELECT * FROM tipousuario ORDER BY idTipoUsuario ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminarTipo($id)
    {
        // INTENTO DE BORRADO FÍSICO
        // Si el rol está siendo usado por un usuario, esto fallará (y está bien que falle)
        $sql = "DELETE FROM tipousuario WHERE idTipoUsuario = :id";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
