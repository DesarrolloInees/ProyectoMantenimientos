<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

class horaExtraHistorialModelo
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

    public function obtenerHistorialHorasExtraPorTecnico($idTecnico)
    {
        try {
            $sql = "SELECT 
                        rhe.id_registro_he,
                        rhe.fecha_reporte,
                        rhe.hora_inicio,
                        rhe.hora_fin,
                        rhe.total_horas,
                        rhe.justificacion_tecnico,
                        rhe.observacion_supervisor,
                        ea.nombre_estado AS estado_nombre,
                        c.nombre_cliente,
                        p.nombre_punto,
                        (
                            SELECT GROUP_CONCAT(ehe.ruta_archivo SEPARATOR '||')
                            FROM evidencia_horas_extra ehe
                            WHERE ehe.id_registro_he = rhe.id_registro_he
                        ) AS rutas_fotos
                    FROM registro_horas_extra rhe
                    INNER JOIN estados_aprobacion_he ea ON rhe.id_estado_aprobacion = ea.id_estado
                    LEFT JOIN cliente c ON rhe.id_cliente = c.id_cliente
                    LEFT JOIN punto p ON rhe.id_punto = p.id_punto
                    WHERE rhe.id_tecnico = :id_tecnico
                    ORDER BY rhe.fecha_reporte DESC, rhe.id_registro_he DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id_tecnico' => $idTecnico]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo historial de horas extra: " . $e->getMessage());
            return [];
        }
    }
}