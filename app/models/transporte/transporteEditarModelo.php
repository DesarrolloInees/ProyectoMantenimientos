<?php
class transporteEditarModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // --- OBTENER LA INSTALACIÓN POR ID ---
    public function obtenerInstalacionPorId($id)
    {
        try {
            $sql = "SELECT * FROM instalaciones_desinstalaciones 
                    WHERE id_instalacion = :id AND estado = 1 LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ERROR en obtenerInstalacionPorId: " . $e->getMessage());
            return false;
        }
    }

    // --- DEPENDENCIAS BÁSICAS ---
    public function obtenerTecnicos() {
        $stmt = $this->conn->prepare("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC");
        $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerTiposMaquina() {
        $stmt = $this->conn->prepare("SELECT id_tipo_maquina, nombre_tipo_maquina FROM tipo_maquina WHERE estado = 1 ORDER BY nombre_tipo_maquina ASC");
        $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerClientes() {
        $stmt = $this->conn->prepare("SELECT id_cliente, nombre_cliente FROM cliente WHERE estado = 1 ORDER BY nombre_cliente ASC");
        $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerPuntosPorCliente($idCliente) {
        $stmt = $this->conn->prepare("SELECT id_punto, nombre_punto, direccion FROM punto WHERE id_cliente = :id_cliente AND estado = 1 ORDER BY nombre_punto ASC");
        $stmt->bindParam(':id_cliente', $idCliente, PDO::PARAM_INT);
        $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- REMISIONES (Disponibles + La actual asignada) ---
    public function obtenerRemisionesDisponibles($idTecnico, $idActual = 0)
    {
        try {
            $sql = "SELECT cr.id_control, cr.numero_remision 
                    FROM control_remisiones cr
                    INNER JOIN estados_remision er ON cr.id_estado = er.id_estado
                    WHERE cr.id_tecnico = :id_tecnico 
                    AND (UPPER(er.nombre_estado) LIKE '%DISPONIBLE%' OR cr.id_control = :id_actual)
                    ORDER BY cr.numero_remision ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id_tecnico', $idTecnico, PDO::PARAM_INT);
            $stmt->bindParam(':id_actual', $idActual, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ERROR en obtenerRemisionesDisponibles: " . $e->getMessage());
            return [];
        }
    }

    // --- ACTUALIZAR REGISTRO ---
    public function actualizarInstalacion($datos)
    {
        try {
            $sql = "UPDATE instalaciones_desinstalaciones SET 
                        id_tecnico = :id_tecnico,
                        id_control_remision = :id_control_remision,
                        fecha_instalacion = :fecha_instalacion,
                        categoria_servicio = :categoria_servicio,
                        tipo_servicio_nombre = :tipo_servicio_nombre,
                        notas = :notas,
                        descripcion_inees = :descripcion_inees,
                        lugar_recogida = :lugar_recogida,
                        fecha_recogida = :fecha_recogida,
                        es_maquina = :es_maquina,
                        id_tipo_maquina = :id_tipo_maquina,
                        serial_maquina = :serial_maquina,
                        producto_otro = :producto_otro,
                        id_cliente_origen = :id_cliente_origen,
                        cliente_origen_texto = :cliente_origen_texto,
                        id_punto_origen = :id_punto_origen,
                        punto_origen_texto = :punto_origen_texto,
                        id_cliente_destino = :id_cliente_destino,
                        cliente_destino_texto = :cliente_destino_texto,
                        id_punto_destino = :id_punto_destino,
                        punto_destino_texto = :punto_destino_texto,
                        valor_servicio = :valor_servicio
                    WHERE id_instalacion = :id_instalacion";

            $stmt = $this->conn->prepare($sql);
            $resultado = $stmt->execute([
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
                ':id_instalacion'        => intval($datos['id_instalacion'])
            ]);

            // Si cambiaron la remisión, marcar la nueva como usada
            if ($resultado && !empty($datos['id_control_remision'])) {
                $this->actualizarEstadoRemision($datos['id_control_remision'], $datos['id_tecnico'], $datos['id_instalacion']);
            }

            return $resultado;

        } catch (PDOException $e) {
            error_log("ERROR actualizando instalación: " . $e->getMessage());
            return false;
        }
    }

    private function actualizarEstadoRemision($idControl, $idTecnico, $idInstalacion)
    {
        try {
            $idEstadoUsada = 2; // ID de Estado "Usada"
            $sql = "UPDATE control_remisiones 
                    SET id_estado = :id_estado, id_instalacion = :id_instalacion, fecha_uso = NOW()
                    WHERE id_control = :id_control AND id_tecnico = :id_tecnico";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id_estado' => $idEstadoUsada,
                ':id_instalacion' => $idInstalacion,
                ':id_control' => $idControl,
                ':id_tecnico' => $idTecnico
            ]);
        } catch (PDOException $e) {
            error_log("ERROR actualizando estado de remisión (editar): " . $e->getMessage());
        }
    }
}