<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class TipoUsuarioCrearModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function crearTipo($nombre)
    {
        try {
            $sql = "INSERT INTO tipousuario (nombreTipoUsuario) VALUES (:nombre)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error crear rol: " . $e->getMessage());
            return false;
        }
    }
}
