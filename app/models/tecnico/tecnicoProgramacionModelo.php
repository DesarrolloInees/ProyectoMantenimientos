<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class tecnicoProgramacionModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene los servicios programados para el técnico vinculado al usuario logueado.
     *
     * @param int    $idUsuarioLogueado  ID del usuario en sesión (usuarios.usuario_id)
     * @param string $fecha             Fecha en formato Y-m-d
     * @return array
     */
    public function obtenerServiciosProgramadosTecnico(int $idUsuarioLogueado, string $fecha): array
    {
        try {
            $sql = "SELECT
                        os.id_ordenes_servicio,
                        os.fecha_visita,
                        os.estado,
                        c.nombre_cliente,
                        p.nombre_punto,
                        p.direccion        AS direccion_punto,
                        m.device_id,
                        tm.nombre_tipo_maquina,
                        tmt.nombre_completo AS tipo_mantenimiento,
                        t.nombre_tecnico
                    FROM ordenes_servicio os
                    
                    INNER JOIN tecnico t
                        ON os.id_tecnico = t.id_tecnico
                        AND t.usuario_id  = :id_usuario   
                        AND t.estado      = 1             
                    LEFT JOIN cliente c
                        ON os.id_cliente = c.id_cliente
                    LEFT JOIN punto p
                        ON os.id_punto = p.id_punto
                    LEFT JOIN maquina m
                        ON os.id_maquina = m.id_maquina
                    
                    LEFT JOIN tipo_maquina tm 
                        ON m.id_tipo_maquina = tm.id_tipo_maquina
                    LEFT JOIN tipo_mantenimiento tmt
                        ON os.id_tipo_mantenimiento = tmt.id_tipo_mantenimiento
                    WHERE os.fecha_visita = :fecha
                    AND   os.estado       = 2             
                    ORDER BY p.nombre_punto ASC, os.id_ordenes_servicio ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id_usuario' => $idUsuarioLogueado,
                ':fecha'      => $fecha,
            ]);

            error_log("PARAMS: usuario=" . $idUsuarioLogueado . " | fecha=" . $fecha);
            error_log("FILAS ENCONTRADAS: " . $stmt->rowCount());
            error_log("FILAS: " . $stmt->rowCount() . " | SQL OK");
            error_log("ERRORINFO: " . print_r($stmt->errorInfo(), true));

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("[tecnicoProgramacionModelo] Error en obtenerServiciosProgramadosTecnico: " . $e->getMessage());
            return [];
        }
    }
}
