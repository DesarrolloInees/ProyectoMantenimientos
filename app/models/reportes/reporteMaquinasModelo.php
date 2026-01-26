<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ReporteMaquinasModelo
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // 1. Para llenar el select
    public function obtenerListaTipos()
    {
        try {
            $stmt = $this->db->prepare("SELECT id_tipo_maquina, nombre_tipo_maquina FROM tipo_maquina WHERE estado = 1 ORDER BY nombre_tipo_maquina ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // 2. La consulta filtrada por ID
    public function obtenerMaquinasPorTipo($id_tipo_maquina)
    {
        try {
            $sql = "SELECT 
                        d.nombre_delegacion,
                        m.device_id,
                        tm.nombre_tipo_maquina,
                        p.nombre_punto,
                        p.direccion,
                        p.fecha_ultima_visita
                    FROM maquina m
                    INNER JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                    INNER JOIN punto p ON m.id_punto = p.id_punto
                    LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    WHERE m.id_tipo_maquina = :id_tipo
                    ORDER BY d.nombre_delegacion ASC, p.nombre_punto ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id_tipo', $id_tipo_maquina);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error Reporte Maquinas: " . $e->getMessage());
            return [];
        }
    }
}