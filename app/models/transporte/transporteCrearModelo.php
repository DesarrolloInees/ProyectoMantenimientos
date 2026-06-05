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
            error_log("ERROR en obtenerTecnicos: " . $e->getMessage());
            return [];
        }
    }

    // --- 2. TIPOS DE MÁQUINA ---
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

    // --- 3. CLIENTES ---
    public function obtenerClientes()
    {
        try {
            $sql = "SELECT id_cliente, nombre_cliente FROM cliente WHERE estado = 1 ORDER BY nombre_cliente ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ERROR en obtenerClientes: " . $e->getMessage());
            return [];
        }
    }

    // --- 4. PUNTOS POR CLIENTE ---
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
            error_log("ERROR en obtenerPuntosPorCliente: " . $e->getMessage());
            return [];
        }
    }

    // --- 5. REMISIONES DISPONIBLES ---
    public function obtenerRemisionesDisponibles($idTecnico)
    {
        try {
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
            error_log("ERROR en obtenerRemisionesDisponibles: " . $e->getMessage());
            return [];
        }
    }

    // --- 6. GUARDAR INSTALACIÓN/DESINSTALACIÓN ---
    public function guardarInstalacion($datos)
    {
        try {
            $this->conn->beginTransaction();

            $sql = "INSERT INTO instalaciones_desinstalaciones 
                (
                    id_tecnico, id_control_remision, fecha_instalacion, 
                    categoria_servicio, tipo_servicio_nombre, notas, descripcion_inees,
                    lugar_recogida, fecha_recogida, es_maquina, id_tipo_maquina, serial_maquina, producto_otro,
                    id_cliente_origen, cliente_origen_texto, id_punto_origen, punto_origen_texto,
                    id_cliente_destino, cliente_destino_texto, id_punto_destino, punto_destino_texto,
                    valor_servicio, foto_remision, foto_maquina, foto_chazos, estado, created_at
                ) VALUES (
                    :id_tecnico, :id_control_remision, :fecha_instalacion, 
                    :categoria_servicio, :tipo_servicio_nombre, :notas, :descripcion_inees,
                    :lugar_recogida, :fecha_recogida, :es_maquina, :id_tipo_maquina, :serial_maquina, :producto_otro,
                    :id_cliente_origen, :cliente_origen_texto, :id_punto_origen, :punto_origen_texto,
                    :id_cliente_destino, :cliente_destino_texto, :id_punto_destino, :punto_destino_texto,
                    :valor_servicio, :foto_remision, :foto_maquina, :foto_chazos, 1, NOW()
                )";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id_tecnico'            => intval($datos['id_tecnico']),
                ':id_control_remision'   => !empty($datos['id_control_remision']) ? intval($datos['id_control_remision']) : null,
                ':fecha_instalacion'     => !empty($datos['fecha_instalacion']) ? $datos['fecha_instalacion'] : null,
                
                ':categoria_servicio'    => $datos['categoria_servicio'] ?? null,
                ':tipo_servicio_nombre'  => $datos['tipo_servicio_nombre'] ?? null,
                ':notas'                 => $datos['notas'] ?? null,
                ':descripcion_inees'     => $datos['descripcion_inees'] ?? null,
                
                ':lugar_recogida'        => $datos['lugar_recogida'] ?? null,
                ':fecha_recogida'        => !empty($datos['fecha_recogida']) ? $datos['fecha_recogida'] : null,
                ':es_maquina'            => intval($datos['es_maquina']),
                ':id_tipo_maquina'       => !empty($datos['id_tipo_maquina']) ? intval($datos['id_tipo_maquina']) : null,
                ':serial_maquina'        => $datos['serial_maquina'] ?? null,
                ':producto_otro'         => $datos['producto_otro'] ?? null,
                
                ':id_cliente_origen'     => !empty($datos['id_cliente_origen']) ? intval($datos['id_cliente_origen']) : null,
                ':cliente_origen_texto'  => $datos['cliente_origen_texto'] ?? null,
                ':id_punto_origen'       => !empty($datos['id_punto_origen']) ? intval($datos['id_punto_origen']) : null,
                ':punto_origen_texto'    => $datos['punto_origen_texto'] ?? null,
                
                ':id_cliente_destino'    => !empty($datos['id_cliente_destino']) ? intval($datos['id_cliente_destino']) : null,
                ':cliente_destino_texto' => $datos['cliente_destino_texto'] ?? null,
                ':id_punto_destino'      => !empty($datos['id_punto_destino']) ? intval($datos['id_punto_destino']) : null,
                ':punto_destino_texto'   => $datos['punto_destino_texto'] ?? null,
                
                ':valor_servicio'        => floatval($datos['valor_servicio'] ?? 0),
                
                ':foto_remision'         => $datos['foto_remision'] ?? null,
                ':foto_maquina'          => $datos['foto_maquina'] ?? null,
                ':foto_chazos'           => $datos['foto_chazos'] ?? null
            ]);

            $idInstalacion = $this->conn->lastInsertId();

            if (!empty($datos['id_control_remision'])) {
                $this->marcarRemisionUsada($datos['id_control_remision'], $idInstalacion, $datos['id_tecnico']);
            }

            $this->conn->commit();
            return $idInstalacion;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("ERROR guardando instalación: " . $e->getMessage());
            return false;
        }
    }

    // --- 7. MARCAR REMISIÓN COMO USADA ---
    private function marcarRemisionUsada($idControl, $idInstalacion, $idTecnico)
    {
        // NOTA: Reemplaza el "2" por el ID real del estado "USADA" en tu tabla estados_remision
        $idEstadoUsada = 2; 

        $sql = "UPDATE control_remisiones 
                SET id_estado = :id_estado,
                    fecha_uso = NOW(),
                    id_instalacion = :id_instalacion
                WHERE id_control = :id_control AND id_tecnico = :id_tecnico";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id_estado' => $idEstadoUsada,
            ':id_instalacion' => $idInstalacion,
            ':id_control' => $idControl,
            ':id_tecnico' => $idTecnico
        ]);
    }
}