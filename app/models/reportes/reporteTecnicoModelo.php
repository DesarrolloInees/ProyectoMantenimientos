<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ReporteTecnicoModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function obtenerTecnicos()
    {
        return $this->conn->query("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function generarReporteServicios($id_tecnico, $fecha_inicio, $fecha_fin)
    {
        try {
            // AGREGAMOS os.hora_entrada Y os.hora_salida AL SELECT
            $sql = "SELECT 
                        os.id_ordenes_servicio,
                        os.numero_remision,
                        os.fecha_visita,
                        os.hora_entrada,  -- << NUEVO
                        os.hora_salida,   -- << NUEVO
                        os.valor_servicio,
                        os.actividades_realizadas,
                        t.nombre_tecnico,
                        c.nombre_cliente,
                        p.nombre_punto,
                        m.device_id,
                        tm.nombre_completo AS tipo_mantenimiento
                    FROM ordenes_servicio os
                    INNER JOIN tecnico t ON os.id_tecnico = t.id_tecnico
                    INNER JOIN cliente c ON os.id_cliente = c.id_cliente
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    INNER JOIN maquina m ON os.id_maquina = m.id_maquina
                    LEFT JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin";

            if (!empty($id_tecnico)) {
                $sql .= " AND os.id_tecnico = :id_tecnico";
            }

            $sql .= " ORDER BY t.nombre_tecnico ASC, os.fecha_visita DESC, os.hora_entrada ASC"; // Ordenamos por hora también

            $stmt = $this->conn->prepare($sql);

            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);

            if (!empty($id_tecnico)) {
                $stmt->bindParam(':id_tecnico', $id_tecnico, PDO::PARAM_INT);
            }

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en reporte técnico: " . $e->getMessage());
            return [];
        }
    }
}
