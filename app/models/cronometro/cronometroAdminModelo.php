<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

class cronometroAdminModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

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

    public function obtenerServiciosAdmin($fechaInicio = null, $fechaFin = null, $idTecnico = null, $estadoActual = null)
    {
        try {
            $sql = "SELECT 
                        ml.*,
                        t.nombre_tecnico,
                        c.nombre_cliente,
                        p.nombre_punto,
                        tm.nombre_completo AS tipo_mantenimiento
                    FROM monitoreo_motorizados_live ml
                    INNER JOIN tecnico t ON ml.id_tecnico = t.id_tecnico
                    INNER JOIN cliente c ON ml.id_cliente = c.id_cliente
                    INNER JOIN punto p ON ml.id_punto = p.id_punto
                    INNER JOIN tipo_mantenimiento tm ON ml.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                    WHERE 1=1";

            $params = [];

            if (!empty($fechaInicio)) {
                $sql .= " AND ml.fecha_servicio >= :fecha_inicio";
                $params[':fecha_inicio'] = $fechaInicio;
            }

            if (!empty($fechaFin)) {
                $sql .= " AND ml.fecha_servicio <= :fecha_fin";
                $params[':fecha_fin'] = $fechaFin;
            }

            if (!empty($idTecnico)) {
                $sql .= " AND ml.id_tecnico = :id_tecnico";
                $params[':id_tecnico'] = $idTecnico;
            }

            if (!empty($estadoActual)) {
                $sql .= " AND ml.estado_actual = :estado_actual";
                $params[':estado_actual'] = $estadoActual;
            }

            // Los "En Progreso" salen primero, luego ordenado por fecha y hora
            $sql .= " ORDER BY CASE WHEN ml.estado_actual = 'En Progreso' THEN 0 ELSE 1 END, ml.fecha_servicio DESC, ml.hora_inicio DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo monitoreo admin: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerCatalogoTiempos()
    {
        try {
            $sql = "SELECT valor FROM parametros WHERE clave = 'tiempos_mantenimiento_minutos' LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($res && !empty($res['valor'])) {
                $json = json_decode($res['valor'], true);
                return is_array($json) ? $json : [];
            }
            return [];
        } catch (PDOException $e) {
            error_log("Error obtenerCatalogoTiempos: " . $e->getMessage());
            return [];
        }
    }
}