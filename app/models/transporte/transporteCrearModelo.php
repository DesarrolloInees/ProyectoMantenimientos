<?php
class transporteCrearModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // --- 1. TÉCNICOS ---
    public function obtenerTecnicos()
    {
        try {
            $sql = "SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ERROR en obtenerTecnicos (instalacion): " . $e->getMessage());
            return [];
        }
    }

    // --- 2. DELEGACIONES ---
    public function obtenerDelegaciones()
    {
        try {
            $sql = "SELECT id_delegacion, nombre_delegacion FROM delegacion WHERE estado = 1 ORDER BY nombre_delegacion ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ERROR en obtenerDelegaciones: " . $e->getMessage());
            return [];
        }
    }

    // --- 3. TIPOS DE MÁQUINA ---
    public function obtenerTiposMaquina()
    {
        try {
            $sql = "SELECT id_tipo_maquina, nombre_tipo_maquina FROM tipo_maquina WHERE estado = 1 ORDER BY nombre_tipo_maquina ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ERROR en obtenerTiposMaquina: " . $e->getMessage());
            return [];
        }
    }

    // --- 4. TIPOS DE SERVICIO DE INSTALACIÓN ---
    public function obtenerTiposServicio()
    {
        try {
            $sql = "SELECT id_tipo_servicio, nombre_servicio FROM tipo_servicio_instalacion WHERE estado = 1 ORDER BY nombre_servicio ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ERROR en obtenerTiposServicio: " . $e->getMessage());
            return [];
        }
    }

    // --- 5. CLIENTES ---
    public function obtenerClientes()
    {
        try {
            $sql = "SELECT id_cliente, nombre_cliente FROM cliente WHERE estado = 1 ORDER BY nombre_cliente ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ERROR en obtenerClientes (instalacion): " . $e->getMessage());
            return [];
        }
    }

    // --- 6. PUNTOS POR CLIENTE ---
    public function obtenerPuntosPorCliente($idCliente)
    {
        try {
            $sql = "SELECT id_punto, nombre_punto, direccion 
                    FROM punto 
                    WHERE id_cliente = :id_cliente AND estado = 1 
                    ORDER BY nombre_punto ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id_cliente', $idCliente, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ERROR en obtenerPuntosPorCliente (instalacion): " . $e->getMessage());
            return [];
        }
    }

    // --- 7. DETALLE DEL PUNTO (nombre + dirección) ---
    public function obtenerDetallePunto($idPunto)
    {
        try {
            $sql = "SELECT p.nombre_punto, p.direccion, c.nombre_cliente
                    FROM punto p
                    INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                    WHERE p.id_punto = :id_punto AND p.estado = 1
                    LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id_punto', $idPunto, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ERROR en obtenerDetallePunto: " . $e->getMessage());
            return null;
        }
    }

    // --- 8. REMISIONES DISPONIBLES DEL TÉCNICO ---
    public function obtenerRemisionesDisponibles($idTecnico)
    {
        try {
            // Se quitó el CAST a UNSIGNED y se cambió a un INNER JOIN para mayor seguridad
            $sql = "SELECT cr.id_control, cr.numero_remision 
                    FROM control_remisiones cr
                    INNER JOIN estados_remision er ON cr.id_estado = er.id_estado
                    WHERE cr.id_tecnico = :id_tecnico 
                    AND UPPER(er.nombre_estado) LIKE '%DISPONIBLE%'
                    ORDER BY cr.numero_remision ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id_tecnico', $idTecnico, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ERROR en obtenerRemisionesDisponibles (instalacion): " . $e->getMessage());
            return [];
        }
    }

    // --- 9. DIRECCIÓN DE ORIGEN (desde parámetros) ---
    public function obtenerDireccionOrigen()
    {
        try {
            $sql = "SELECT valor FROM parametros WHERE clave = 'direccion_origen_instalacion' LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ? $res['valor'] : 'No configurada';
        } catch (PDOException $e) {
            error_log("ERROR en obtenerDireccionOrigen: " . $e->getMessage());
            return 'No configurada';
        }
    }

    // --- 10. GUARDAR INSTALACIÓN/DESINSTALACIÓN ---
    public function guardarInstalacion($datos)
    {
        try {
            $this->conn->beginTransaction();

            $sql = "INSERT INTO instalaciones_desinstalaciones 
                    (
                        tipo_operacion,
                        fecha_solicitud,
                        fecha_ejecucion,
                        id_control_remision,
                        id_maquina,
                        serial_maquina,
                        id_tipo_maquina,
                        id_tecnico,
                        id_delegacion_origen,
                        id_delegacion_destino,
                        id_punto,
                        id_tipo_servicio,
                        valor_servicio,
                        comentarios,
                        estado,
                        created_at
                    ) VALUES (
                        :tipo_operacion,
                        :fecha_solicitud,
                        :fecha_ejecucion,
                        :id_control_remision,
                        :id_maquina,
                        :serial_maquina,
                        :id_tipo_maquina,
                        :id_tecnico,
                        :id_delegacion_origen,
                        :id_delegacion_destino,
                        :id_punto,
                        :id_tipo_servicio,
                        :valor_servicio,
                        :comentarios,
                        1,
                        NOW()
                    )";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':tipo_operacion'       => $datos['tipo_operacion'],
                ':fecha_solicitud'      => $datos['fecha_solicitud'],
                ':fecha_ejecucion'      => !empty($datos['fecha_ejecucion']) ? $datos['fecha_ejecucion'] : null,
                ':id_control_remision'  => !empty($datos['id_control_remision']) ? intval($datos['id_control_remision']) : null,
                ':id_maquina'           => null, // Se fuerza null ya que no se usa device_id
                ':serial_maquina'       => $datos['serial_maquina'] ?? null,
                ':id_tipo_maquina'      => intval($datos['id_tipo_maquina']),
                ':id_tecnico'           => intval($datos['id_tecnico']),
                ':id_delegacion_origen' => intval($datos['id_delegacion_origen']),
                ':id_delegacion_destino'=> !empty($datos['id_delegacion_destino']) ? intval($datos['id_delegacion_destino']) : null,
                ':id_punto'             => !empty($datos['id_punto']) ? intval($datos['id_punto']) : null,
                ':id_tipo_servicio'     => !empty($datos['id_tipo_servicio']) ? intval($datos['id_tipo_servicio']) : null,
                ':valor_servicio'       => floatval($datos['valor_servicio'] ?? 0),
                ':comentarios'          => $datos['comentarios'] ?? null,
            ]);

            $idInstalacion = $this->conn->lastInsertId();

            if (!empty($datos['id_control_remision'])) {
                $this->marcarRemisionUsada($datos['id_control_remision'], $idInstalacion, $datos['id_tecnico']);
            }

            $this->conn->commit();
            return $idInstalacion;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("ERROR guardando instalación: " . $e->getMessage() . " | Datos: " . json_encode($datos));
            return false;
        }
    }

    // --- 11. MARCAR REMISIÓN COMO USADA ---
    private function marcarRemisionUsada($idControl, $idInstalacion, $idTecnico)
    {
        try {
            $sql = "UPDATE control_remisiones 
                    SET id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'USADA' LIMIT 1),
                        fecha_uso = NOW()
                    WHERE id_control = :id_control AND id_tecnico = :id_tecnico";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id_control' => $idControl,
                ':id_tecnico' => $idTecnico
            ]);
        } catch (PDOException $e) {
            error_log("ERROR marcando remisión usada (instalacion): " . $e->getMessage());
        }
    }
}