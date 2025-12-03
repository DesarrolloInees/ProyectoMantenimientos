<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ordenVerModelo {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function listarOrdenesPorFecha() {
        // SQL LIMPIO: Solo Fecha, Cantidad y Dinero.
        $sql = "SELECT 
                    o.fecha_visita,
                    COUNT(o.id_ordenes_servicio) as cantidad_servicios,
                    IFNULL(SUM(o.valor_servicio), 0) as valor_total
                FROM ordenes_servicio o
                GROUP BY o.fecha_visita
                ORDER BY o.fecha_visita DESC";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error SQL: " . $e->getMessage());
            return [];
        }
    }
}
?>