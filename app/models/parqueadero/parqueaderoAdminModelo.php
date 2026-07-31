<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

class ParqueaderoAdminModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Obtener todos los técnicos activos para el select del filtro
    public function obtenerTecnicos()
    {
        try {
            $sql = "SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo técnicos: " . $e->getMessage());
            return [];
        }
    }

    // Obtener facturas con filtros dinámicos
    public function obtenerFacturasAdmin($fechaInicio = null, $fechaFin = null, $idTecnico = null)
    {
        try {
            $sql = "SELECT fp.id_factura_parqueadero, fp.fecha_servicio, fp.hora_inicio, fp.hora_fin, 
                            fp.numero_factura, fp.valor_factura, fp.ruta_foto, fp.fecha_registro,
                            t.nombre_tecnico, p.nombre_punto 
                    FROM facturas_parqueadero fp
                    INNER JOIN tecnico t ON fp.id_tecnico = t.id_tecnico
                    INNER JOIN punto p ON fp.id_punto = p.id_punto
                    WHERE fp.estado = 1";

            $params = [];

            if (!empty($fechaInicio)) {
                $sql .= " AND fp.fecha_servicio >= :fecha_inicio";
                $params[':fecha_inicio'] = $fechaInicio;
            }

            if (!empty($fechaFin)) {
                $sql .= " AND fp.fecha_servicio <= :fecha_fin";
                $params[':fecha_fin'] = $fechaFin;
            }

            if (!empty($idTecnico)) {
                $sql .= " AND fp.id_tecnico = :id_tecnico";
                $params[':id_tecnico'] = $idTecnico;
            }

            $sql .= " ORDER BY fp.fecha_servicio DESC, fp.id_factura_parqueadero DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo facturas para admin: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerReporteParqueaderos($filtros)
    {
        $condiciones = ["1=1"]; // Base para ir concatenando
        $params = [];

        // Filtro por mes y año
        if (!empty($filtros['mes']) && !empty($filtros['anio'])) {
            $condiciones[] = "MONTH(fecha_entrada) = ? AND YEAR(fecha_entrada) = ?";
            $params[] = $filtros['mes'];
            $params[] = $filtros['anio'];
        }

        // Filtro por punto
        if (!empty($filtros['id_punto'])) {
            $condiciones[] = "id_punto = ?";
            $params[] = $filtros['id_punto'];
        }

        // Filtro por técnico
        if (!empty($filtros['id_tecnico'])) {
            $condiciones[] = "id_tecnico = ?";
            $params[] = $filtros['id_tecnico'];
        }

        $where = implode(" AND ", $condiciones);

        $sql = "SELECT p.*, t.nombre_tecnico, pu.nombre_punto, f.ruta_foto 
            FROM parqueaderos p
            LEFT JOIN tecnico t ON p.id_tecnico = t.id_tecnico
            LEFT JOIN punto pu ON p.id_punto = pu.id_punto
            LEFT JOIN fotos_parqueadero f ON p.id_ticket = f.id_ticket
            WHERE $where
            ORDER BY p.fecha_entrada DESC";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error: " . $e->getMessage());
            return [];
        }
    }
}