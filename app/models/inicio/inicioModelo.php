<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

class inicioModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Total de órdenes de servicio del mes actual (basado en fecha_visita)
     * Solo órdenes activas o programadas (estado 1 o 2)
     */
    public function totalOrdenesMes()
    {
        try {
            $primerDia = date('Y-m-01');
            $ultimoDia = date('Y-m-t');

            $sql = "SELECT COUNT(*) as total 
                    FROM ordenes_servicio 
                    WHERE fecha_visita BETWEEN :inicio AND :fin
                    AND estado = 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':inicio' => $primerDia,
                ':fin'    => $ultimoDia
            ]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado ? (int)$resultado['total'] : 0;
        } catch (PDOException $e) {
            error_log("ERROR en totalOrdenesMes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Total de clientes activos (estado = 1)
     */
    public function totalClientes()
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM cliente WHERE estado = 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado ? (int)$resultado['total'] : 0;
        } catch (PDOException $e) {
            error_log("ERROR en totalClientes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Total de técnicos activos (estado = 1)
     */
    public function totalTecnicos()
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM tecnico WHERE estado = 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado ? (int)$resultado['total'] : 0;
        } catch (PDOException $e) {
            error_log("ERROR en totalTecnicos: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Resuelve el id_tecnico a partir del usuario logueado.
     * Patrón ya usado en tecnicoReporteModelo / cronometro / horaExtra.
     */
    public function obtenerIdTecnicoPorUsuario($usuario_id)
    {
        try {
            if (empty($usuario_id)) {
                return 0;
            }
            $sql = "SELECT id_tecnico FROM tecnico WHERE usuario_id = :id_usuario LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id_usuario' => $usuario_id]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ? (int)$res['id_tecnico'] : 0;
        } catch (PDOException $e) {
            error_log("ERROR en obtenerIdTecnicoPorUsuario: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Conteo de servicios del técnico por tipo de mantenimiento en un rango.
     * Usa la misma fuente que el reporte técnico (ordenes_servicio, excluyendo
     * instalaciones que se manejan como remisiones sueltas).
     * Retorna filas ['tipo_mantenimiento' => nombre, 'total' => n].
     */
    public function resumenServiciosPorTipo($id_tecnico, $fecha_inicio, $fecha_fin)
    {
        try {
            if (empty($id_tecnico)) {
                return [];
            }
            $sql = "SELECT tm.nombre_completo AS tipo_mantenimiento, COUNT(*) AS total
                    FROM ordenes_servicio os
                    LEFT JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                    WHERE os.id_tecnico = :id_tecnico
                    AND os.fecha_visita BETWEEN :inicio AND :fin
                    AND (tm.nombre_completo IS NULL
                         OR (UPPER(tm.nombre_completo) NOT LIKE '%INSTALACIÓN%'
                             AND UPPER(tm.nombre_completo) NOT LIKE '%INSTALACION%'))
                    GROUP BY tm.nombre_completo";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id_tecnico' => $id_tecnico,
                ':inicio' => $fecha_inicio,
                ':fin' => $fecha_fin
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ERROR en resumenServiciosPorTipo: " . $e->getMessage());
            return [];
        }
    }
    /*
    public function ordenesCompletadasUltimaSemana()
    {
        try {
            $sql = "SELECT COUNT(*) as total 
                    FROM ordenes_servicio 
                    WHERE id_estado_maquina = :estado_completado
                    AND fecha_visita >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':estado_completado' => 1]); // Cambia según tu catálogo
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado ? (int)$resultado['total'] : 0;
        } catch (PDOException $e) {
            error_log("ERROR en ordenesCompletadasUltimaSemana: " . $e->getMessage());
            return 0;
        }
    }
    */
}