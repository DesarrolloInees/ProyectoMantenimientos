<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

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
    public function obtenerServiciosProgramadosTecnico(int $idUsuarioLogueado, string $fecha, string $estado = 'pendientes'): array
    {
        try {
            // Determinar estado según filtro
            $estadoBD = ($estado === 'pendientes') ? 2 : 1; // 2=Programado, 1=Finalizado

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
                    t.nombre_tecnico,
                    os.hora_entrada,
                    os.hora_salida,
                    os.tiempo_servicio,
                    os.actividades_realizadas
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
                AND   os.estado       = :estado
                ORDER BY p.nombre_punto ASC, os.id_ordenes_servicio ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id_usuario' => $idUsuarioLogueado,
                ':fecha' => $fecha,
                ':estado' => $estadoBD
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("[tecnicoProgramacionModelo] Error en obtenerServiciosProgramadosTecnico: " . $e->getMessage());
            return [];
        }
    }



    public function obtenerDetalleServicioCompleto($idOrden, $idUsuario)
    {
        try {
            $sql = "SELECT 
                    os.id_ordenes_servicio,
                    os.fecha_visita,
                    os.hora_entrada,
                    os.hora_salida,
                    os.tiempo_servicio,
                    os.actividades_realizadas,
                    os.valor_servicio,
                    c.nombre_cliente,
                    p.nombre_punto,
                    p.direccion,
                    m.device_id,
                    tm.nombre_tipo_maquina,
                    tmt.nombre_completo AS tipo_mantenimiento,
                    em.nombre_estado AS estado_final,
                    cs.nombre_calificacion AS calificacion,
                    os.soporte_remoto,
                    os.detalle_novedad
                FROM ordenes_servicio os
                INNER JOIN tecnico t ON os.id_tecnico = t.id_tecnico AND t.usuario_id = :id_usuario
                LEFT JOIN cliente c ON os.id_cliente = c.id_cliente
                LEFT JOIN punto p ON os.id_punto = p.id_punto
                LEFT JOIN maquina m ON os.id_maquina = m.id_maquina
                LEFT JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                LEFT JOIN tipo_mantenimiento tmt ON os.id_tipo_mantenimiento = tmt.id_tipo_mantenimiento
                LEFT JOIN estado_maquina em ON os.id_estado_maquina = em.id_estado
                LEFT JOIN calificacion_servicio cs ON os.id_calificacion = cs.id_calificacion
                WHERE os.id_ordenes_servicio = :id_orden";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id_usuario' => $idUsuario,
                ':id_orden' => $idOrden
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obtenerDetalleServicioCompleto: " . $e->getMessage());
            return false;
        }
    }

    // ── OBTENER LISTAS PARA LOS SELECTS DEL MODAL ──

    public function obtenerClientesActivos(): array
    {
        try {
            $sql = "SELECT id_cliente, nombre_cliente FROM cliente WHERE estado = 1 ORDER BY nombre_cliente ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obtenerClientesActivos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerPuntosPorCliente(int $idCliente): array
    {
        try {
            $sql = "SELECT id_punto, nombre_punto, direccion FROM punto WHERE id_cliente = :id_cliente AND estado = 1 ORDER BY nombre_punto ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id_cliente' => $idCliente]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obtenerPuntosPorCliente: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerMaquinasPorPunto(int $idPunto): array
    {
        try {
            $sql = "SELECT m.id_maquina, m.device_id, tm.nombre_tipo_maquina 
                    FROM maquina m 
                    INNER JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                    WHERE m.id_punto = :id_punto AND m.estado = 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id_punto' => $idPunto]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obtenerMaquinasPorPunto: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerTiposMantenimiento(): array
    {
        try {
            $sql = "SELECT id_tipo_mantenimiento, nombre_completo FROM tipo_mantenimiento WHERE estado = 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obtenerTiposMantenimiento: " . $e->getMessage());
            return [];
        }
    }

    // ── GUARDAR GPS DE INICIO ──
    public function iniciarServicioGPS(int $idOrden, $lat, $lon): bool
    {
        try {
            $sql = "INSERT INTO ordenes_servicio_complemento 
                    (id_orden_servicio, latitud_inicio, longitud_inicio, estado) 
                    VALUES (:id_orden, :lat, :lon, 1)
                    ON DUPLICATE KEY UPDATE 
                    latitud_inicio = IFNULL(latitud_inicio, VALUES(latitud_inicio)), 
                    longitud_inicio = IFNULL(longitud_inicio, VALUES(longitud_inicio))";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':id_orden' => $idOrden,
                ':lat' => $lat,
                ':lon' => $lon
            ]);
        } catch (PDOException $e) {
            error_log("Error iniciarServicioGPS: " . $e->getMessage());
            return false;
        }
    }

    // ── GUARDAR EL SERVICIO EXTRA ──

    public function guardarServicioExtra(int $idUsuarioLogueado, array $datos)
    {
        try {
            // 1. Obtener ID del técnico
            $sqlTec = "SELECT id_tecnico FROM tecnico WHERE usuario_id = :uid";
            $stmtTec = $this->conn->prepare($sqlTec);
            $stmtTec->execute([':uid' => $idUsuarioLogueado]);
            $tecnico = $stmtTec->fetch(PDO::FETCH_ASSOC);

            if (!$tecnico)
                return ['success' => false, 'msj' => 'No se encontró el perfil de técnico.'];

            // 2. Obtener la modalidad del punto seleccionado
            $sqlPunto = "SELECT id_modalidad FROM punto WHERE id_punto = :id_punto";
            $stmtPunto = $this->conn->prepare($sqlPunto);
            $stmtPunto->execute([':id_punto' => $datos['id_punto']]);
            $punto = $stmtPunto->fetch(PDO::FETCH_ASSOC);

            // Si el punto no tiene modalidad, enviamos 1 (Urbano) por defecto para que no estalle la BD
            $idModalidad = ($punto && !empty($punto['id_modalidad'])) ? $punto['id_modalidad'] : 1;

            // 3. Insertar la orden
            $sql = "INSERT INTO ordenes_servicio 
                    (id_cliente, id_punto, id_modalidad, id_maquina, id_tecnico, id_tipo_mantenimiento, fecha_visita, estado) 
                    VALUES 
                    (:cliente, :punto, :modalidad, :maquina, :tecnico, :tipo_mantenimiento, :fecha, 2)";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':cliente' => $datos['id_cliente'],
                ':punto' => $datos['id_punto'],
                ':modalidad' => $idModalidad,
                ':maquina' => $datos['id_maquina'],
                ':tecnico' => $tecnico['id_tecnico'],
                ':tipo_mantenimiento' => $datos['id_tipo_mantenimiento'],
                ':fecha' => $datos['fecha_visita']
            ]);

            return ['success' => true];

        } catch (PDOException $e) {
            // Retornamos el error exacto de SQL para verlo en pantalla
            error_log("Error guardando extra: " . $e->getMessage());
            return ['success' => false, 'msj' => $e->getMessage()];
        }
    }



    /**
     * Elimina un servicio programado verificando que pertenezca al técnico logueado.
     */
    public function eliminarOrdenServicio(int $idOrden, int $idUsuarioLogueado): bool
    {
        try {
            // 1. Obtener el id_tecnico del usuario actual
            $sqlTec = "SELECT id_tecnico FROM tecnico WHERE usuario_id = :uid";
            $stmtTec = $this->conn->prepare($sqlTec);
            $stmtTec->execute([':uid' => $idUsuarioLogueado]);
            $tecnico = $stmtTec->fetch(PDO::FETCH_ASSOC);

            if (!$tecnico)
                return false;

            // 2. Eliminar la orden (Solo si está en estado 2 = Programado)
            $sql = "DELETE FROM ordenes_servicio 
                    WHERE id_ordenes_servicio = :id_orden 
                    AND id_tecnico = :id_tecnico 
                    AND estado = 2";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':id_orden' => $idOrden,
                ':id_tecnico' => $tecnico['id_tecnico']
            ]);
        } catch (PDOException $e) {
            error_log("[tecnicoProgramacionModelo] Error eliminando orden: " . $e->getMessage());
            return false;
        }
    }


}
