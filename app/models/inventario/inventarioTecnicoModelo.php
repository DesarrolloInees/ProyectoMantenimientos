<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

class InventarioTecnicoModelo {
    private $db;

    public function __construct($conexionDb) {
        $this->db = $conexionDb;
    }

    /**
     * Obtiene todo el inventario técnico general o filtrado por técnico
     */
    public function obtenerInventario($idTecnico = null) {
        if ($idTecnico) {
            $sql = "SELECT * FROM inventario_tecnico WHERE id_tecnico = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idTecnico]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $sql = "SELECT * FROM inventario_tecnico";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    /**
     * Puedes agregar más métodos según los campos específicos de tu tabla 
     * (por ejemplo: insertar, actualizar stock, etc.)
     */
}