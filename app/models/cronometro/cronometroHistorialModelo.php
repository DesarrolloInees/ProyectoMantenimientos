<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

class cronometroHistorialModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function obtenerIdTecnicoPorUsuario($idUsuario)
    {
        try {
            $sql = "SELECT id_tecnico FROM tecnico WHERE usuario_id = :id_usuario AND estado = 1 LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id_usuario' => $idUsuario]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ? (int) $res['id_tecnico'] : 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function obtenerHistorialCronometro($idTecnico)
    {
        try {
            $sql = "SELECT 
                        ml.id_monitoreo,
                        ml.fecha_servicio,
                        ml.hora_inicio,
                        ml.hora_fin,
                        ml.duracion_real_minutos,
                        ml.estado_actual,
                        c.nombre_cliente,
                        p.nombre_punto,
                        tm.nombre_completo AS tipo_mantenimiento,
                        -- Calculamos la meta al vuelo si no existe la columna en tu BD
                        CASE 
                            WHEN ml.id_tipo_mantenimiento = 1 THEN 60
                            WHEN ml.id_tipo_mantenimiento = 2 THEN 120
                            WHEN ml.id_tipo_mantenimiento = 3 THEN 45
                            WHEN ml.id_tipo_mantenimiento = 4 THEN 90
                            ELSE 60 
                        END AS tiempo_estimado_minutos
                    FROM monitoreo_motorizados_live ml
                    INNER JOIN cliente c ON ml.id_cliente = c.id_cliente
                    INNER JOIN punto p ON ml.id_punto = p.id_punto
                    INNER JOIN tipo_mantenimiento tm ON ml.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                    WHERE ml.id_tecnico = :id_tecnico
                    ORDER BY ml.fecha_servicio DESC, ml.hora_inicio DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id_tecnico' => $idTecnico]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo historial cronómetro: " . $e->getMessage());
            return [];
        }
    }
}