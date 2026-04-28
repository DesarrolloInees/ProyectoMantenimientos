<?php
// app/models/reportes/reporteDevolucionModelo.php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ReporteDevolucionModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene el listado detallado de repuestos usados que requieren ser devueltos
     */
    public function obtenerRepuestosParaDevolver($id_tecnico = '', $fecha_inicio = '', $fecha_fin = '')
    {
        try {
            $sql = "SELECT 
                        t.nombre_tecnico,
                        r.nombre_repuesto,
                        r.codigo_referencia,
                        osr.cantidad,
                        os.fecha_visita,
                        os.numero_remision,
                        c.nombre_cliente,
                        p.nombre_punto,
                        d.nombre_delegacion -- <--- NUEVO CAMPO: Delegación
                    FROM orden_servicio_repuesto osr
                    INNER JOIN repuesto r ON osr.id_repuesto = r.id_repuesto
                    INNER JOIN ordenes_servicio os ON osr.id_orden_servicio = os.id_ordenes_servicio
                    INNER JOIN tecnico t ON os.id_tecnico = t.id_tecnico
                    INNER JOIN cliente c ON os.id_cliente = c.id_cliente
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion -- <--- NUEVO JOIN
                    WHERE r.requiere_devolucion = 1"; 

            if (!empty($id_tecnico)) {
                $sql .= " AND os.id_tecnico = :id_tecnico";
            }
            if (!empty($fecha_inicio) && !empty($fecha_fin)) {
                $sql .= " AND os.fecha_visita BETWEEN :inicio AND :fin";
            }

            $sql .= " ORDER BY os.fecha_visita DESC, t.nombre_tecnico ASC";

            $stmt = $this->conn->prepare($sql);

            if (!empty($id_tecnico)) $stmt->bindParam(':id_tecnico', $id_tecnico);
            if (!empty($fecha_inicio)) {
                $stmt->bindParam(':inicio', $fecha_inicio);
                $stmt->bindParam(':fin', $fecha_fin);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en ReporteDevolucionModelo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene la lista de técnicos para el filtro
     */
    public function obtenerTecnicos()
    {
        $stmt = $this->conn->query("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}