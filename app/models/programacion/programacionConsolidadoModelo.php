<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

class programacionConsolidadoModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * @param string $fecha_inicio
     * @param string $fecha_fin
     * @param string $id_delegacion Opcional
     * @param string $id_tecnico    Opcional — filtrar por un técnico puntual
     */
    public function obtenerConsolidadoRutas($fecha_inicio, $fecha_fin, $id_delegacion = '', $id_tecnico = '')
    {
        $sql = "SELECT os.id_ordenes_servicio AS id_orden, os.fecha_visita, os.estado,
                    t.id_tecnico, COALESCE(t.nombre_tecnico, 'Sin Técnico') AS nombre_tecnico, 
                    COALESCE(t.codigo_ruta, 'S/R') AS codigo_ruta,
                    p.nombre_punto, p.direccion, p.zona, p.fecha_ultima_visita,
                    c.nombre_cliente, c.codigo_cliente,
                    m.nombre_municipio, d.nombre_delegacion,
                    COALESCE(mq.device_id, mq_punto.device_id, 'S/N') AS device_id,
                    COALESCE(mq.activo_operativo, mq_punto.activo_operativo, 1) AS activo_operativo
            FROM ordenes_servicio os
            LEFT JOIN tecnico t ON os.id_tecnico = t.id_tecnico
            LEFT JOIN punto p ON os.id_punto = p.id_punto
            LEFT JOIN cliente c ON os.id_cliente = c.id_cliente
            LEFT JOIN municipio m ON p.id_municipio = m.id_municipio
            LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
            LEFT JOIN maquina mq ON os.id_maquina = mq.id_maquina
            /* Fallback: Buscar la máquina directamente por el punto si id_maquina en os fue NULL */
            LEFT JOIN maquina mq_punto ON (mq_punto.id_punto = p.id_punto AND mq_punto.estado = 1)
            WHERE os.estado = 2
            AND os.fecha_visita BETWEEN :inicio AND :fin";

        $params = [
            ':inicio' => $fecha_inicio,
            ':fin' => $fecha_fin
        ];

        if (!empty($id_delegacion)) {
            $sql .= " AND p.id_delegacion = :delegacion";
            $params[':delegacion'] = $id_delegacion;
        }

        if (!empty($id_tecnico)) {
            $sql .= " AND os.id_tecnico = :tecnico";
            $params[':tecnico'] = $id_tecnico;
        }

        $sql .= " ORDER BY os.fecha_visita ASC, t.codigo_ruta ASC, p.zona ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Restaura a estado operativo (activo_operativo = 1) todas las máquinas fuera de servicio
     * que NO fueron incluidas en las órdenes de servicio programadas (estado = 2).
     */
    public function restaurarMaquinasRestantesAOperativo()
    {
        try {
            $sql = "UPDATE maquina m
                SET m.activo_operativo = 1, 
                    m.fecha_actualizacion = NOW()
                WHERE m.activo_operativo = 0 
                    AND m.estado = 1
                    AND NOT EXISTS (
                        SELECT 1 
                        FROM ordenes_servicio os 
                        WHERE os.id_punto = m.id_punto 
                        AND os.estado = 2
                    )";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();

            return [
                'status' => true,
                'filas_restauradas' => $stmt->rowCount()
            ];
        } catch (PDOException $e) {
            return [
                'status' => false,
                'msg' => $e->getMessage()
            ];
        }
    }

    public function obtenerDelegaciones()
    {
        $stmt = $this->conn->prepare("SELECT id_delegacion, nombre_delegacion FROM delegacion WHERE estado = 1 ORDER BY nombre_delegacion ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTecnicos()
    {
        $stmt = $this->conn->prepare("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarFechaOrden($idOrden, $nuevaFecha)
    {
        $sql = "UPDATE ordenes_servicio SET fecha_visita = :fecha WHERE id_ordenes_servicio = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':fecha' => $nuevaFecha, ':id' => $idOrden]);
    }
}
