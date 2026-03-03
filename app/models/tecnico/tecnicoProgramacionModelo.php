<?php
class tecnicoProgramacionModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // --- OBTENER PROGRAMACIÓN DEL TÉCNICO VINCULADO AL USUARIO LOGUEADO ---
    public function obtenerServiciosProgramadosTecnico($idUsuarioLogueado, $fecha)
    {
        try {
            // 🔥 CORRECCIÓN CLAVE: Agregamos INNER JOIN tecnico t ON os.id_tecnico = t.id_tecnico
            // y filtramos por t.id_usuario = :id_usuario
            $sql = "SELECT 
                        os.id_ordenes_servicio,
                        os.fecha_visita,
                        c.nombre_cliente,
                        p.nombre_punto,
                        m.device_id,
                        tm.nombre_tipo_maquina,
                        tmt.nombre_completo AS tipo_mantenimiento
                    FROM ordenes_servicio os
                    INNER JOIN tecnico t ON os.id_tecnico = t.id_tecnico /* Conectamos con el Técnico */
                    LEFT JOIN cliente c ON os.id_cliente = c.id_cliente  
                    LEFT JOIN punto p ON os.id_punto = p.id_punto        
                    LEFT JOIN maquina m ON os.id_maquina = m.id_maquina
                    LEFT JOIN tipo_maquina tm ON os.id_tipo_maquina = tm.id_tipo_maquina
                    LEFT JOIN tipo_mantenimiento tmt ON os.id_tipo_mantenimiento = tmt.id_tipo_mantenimiento
                    WHERE os.fecha_visita = :fecha 
                    AND u.usuario_id = :usuario_id /* 🔥 Filtramos por el usuario de la sesión */
                    AND os.estado = 2
                    ORDER BY p.nombre_punto ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':fecha' => $fecha,
                ':id_usuario' => $idUsuarioLogueado
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerServiciosProgramadosTecnico: " . $e->getMessage());
            return [];
        }
    }
}
?>