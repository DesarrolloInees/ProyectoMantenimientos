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
            // Traemos también el id_cliente asociado al punto para poder precargar los selects
            $sql = "SELECT i.*, p.id_cliente, p.direccion as direccion_punto
                    FROM instalaciones_desinstalaciones i
                    LEFT JOIN punto p ON i.id_punto = p.id_punto
                    WHERE i.id_instalacion = :id AND i.estado = 1
                    LIMIT 1";
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
    public function obtenerDelegaciones() {
        $stmt = $this->conn->prepare("SELECT id_delegacion, nombre_delegacion FROM delegacion WHERE estado = 1 ORDER BY nombre_delegacion ASC");
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
    public function obtenerDetallePunto($idPunto) {
        $stmt = $this->conn->prepare("SELECT direccion FROM punto WHERE id_punto = :id_punto LIMIT 1");
        $stmt->bindParam(':id_punto', $idPunto, PDO::PARAM_INT);
        $stmt->execute(); return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function obtenerDireccionOrigen() {
        $stmt = $this->conn->prepare("SELECT valor FROM parametros WHERE clave = 'direccion_origen_instalacion' LIMIT 1");
        $stmt->execute(); $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ? $res['valor'] : 'No configurada';
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
            // Cambiamos tipo_operacion por id_estado_operacion
            $sql = "UPDATE instalaciones_desinstalaciones SET 
                        id_estado_operacion = :id_estado_operacion,
                        fecha_solicitud = :fecha_solicitud,
                        fecha_ejecucion = :fecha_ejecucion,
                        id_control_remision = :id_control_remision,
                        serial_maquina = :serial_maquina,
                        id_tipo_maquina = :id_tipo_maquina,
                        id_tecnico = :id_tecnico,
                        id_delegacion_origen = :id_delegacion_origen,
                        id_delegacion_destino = :id_delegacion_destino,
                        id_punto = :id_punto,
                        valor_servicio = :valor_servicio,
                        comentarios = :comentarios,
                        incluye_capacitacion = :incluye_capacitacion,
                        tema_capacitacion = :tema_capacitacion,
                        cantidad_asistentes = :cantidad_asistentes,
                        horas_capacitacion = :horas_capacitacion
                    WHERE id_instalacion = :id_instalacion";

            $stmt = $this->conn->prepare($sql);
            $resultado = $stmt->execute([
                ':id_estado_operacion'  => intval($datos['id_estado_operacion']),
                ':fecha_solicitud'      => $datos['fecha_solicitud'],
                ':fecha_ejecucion'      => !empty($datos['fecha_ejecucion']) ? $datos['fecha_ejecucion'] : null,
                ':id_control_remision'  => !empty($datos['id_control_remision']) ? intval($datos['id_control_remision']) : null,
                ':serial_maquina'       => $datos['serial_maquina'] ?? null,
                ':id_tipo_maquina'      => intval($datos['id_tipo_maquina']),
                ':id_tecnico'           => intval($datos['id_tecnico']),
                ':id_delegacion_origen' => intval($datos['id_delegacion_origen']),
                ':id_delegacion_destino'=> !empty($datos['id_delegacion_destino']) ? intval($datos['id_delegacion_destino']) : null,
                ':id_punto'             => !empty($datos['id_punto']) ? intval($datos['id_punto']) : null,
                ':valor_servicio'       => floatval($datos['valor_servicio'] ?? 0),
                ':comentarios'          => $datos['comentarios'] ?? null,
                ':incluye_capacitacion' => intval($datos['incluye_capacitacion'] ?? 0),
                ':tema_capacitacion'    => $datos['tema_capacitacion'] ?? null,
                ':cantidad_asistentes'  => !empty($datos['cantidad_asistentes']) ? intval($datos['cantidad_asistentes']) : null,
                ':horas_capacitacion'   => !empty($datos['horas_capacitacion']) ? floatval($datos['horas_capacitacion']) : null,
                ':id_instalacion'       => intval($datos['id_instalacion'])
            ]);

            // Actualizar la remisión enviando el ID del estado
            if ($resultado && !empty($datos['id_control_remision'])) {
                $this->actualizarEstadoRemision(
                    $datos['id_control_remision'], 
                    $datos['id_tecnico'], 
                    $datos['id_estado_operacion'], 
                    $datos['id_instalacion']
                );
            }

            return $resultado;

        } catch (PDOException $e) {
            error_log("ERROR actualizando instalación: " . $e->getMessage());
            return false;
        }
    }

    // --- ACTUALIZAR ESTADO DE LA REMISIÓN AL EDITAR ---
    private function actualizarEstadoRemision($idControl, $idTecnico, $idEstadoOperacion, $idInstalacion)
    {
        try {
            // Se quitó la subconsulta, inyectamos el ID directamente
            $sql = "UPDATE control_remisiones 
                    SET id_estado = :id_estado_operacion,
                        id_instalacion = :id_instalacion
                    WHERE id_control = :id_control AND id_tecnico = :id_tecnico";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id_estado_operacion' => $idEstadoOperacion,
                ':id_instalacion'      => $idInstalacion,
                ':id_control'          => $idControl,
                ':id_tecnico'          => $idTecnico
            ]);
        } catch (PDOException $e) {
            error_log("ERROR actualizando estado de remisión (editar): " . $e->getMessage());
        }
    }
}