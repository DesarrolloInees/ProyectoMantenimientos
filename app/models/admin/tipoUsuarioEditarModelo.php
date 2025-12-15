<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class TipoUsuarioEditarModelo
{
    private $conn;

    public function __construct(PDO $db) { $this->conn = $db; }

    public function obtenerTipoPorId($id)
    {
        $sql = "SELECT * FROM tipousuario WHERE idTipoUsuario = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function editarTipo($id, $nombre)
    {
        try {
            $sql = "UPDATE tipousuario SET nombreTipoUsuario = :nombre WHERE idTipoUsuario = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}