<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class servicioEditarModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // 1. Obtener la data actual (AHORA TRAE numero_remision)
    public function obtenerDatosEdicion($idOrden)
    {
        $sql = "SELECT o.id_ordenes_servicio, o.soporte_remoto, o.numero_remision,
                        osc.numero_maquina, osc.serial_maquina, osc.serial_router,
                        osc.serial_ups, osc.pendientes, osc.administrador_punto,
                        osc.celular_encargado, osc.id_estado_inicial
                FROM ordenes_servicio o
                LEFT JOIN ordenes_servicio_complemento osc ON o.id_ordenes_servicio = osc.id_orden_servicio
                WHERE o.id_ordenes_servicio = ?";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$idOrden]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo datos edición: " . $e->getMessage());
            return false;
        }
    }

    // 2. Traer los estados para el select
    public function obtenerEstadosMaquina()
    {
        $sql = "SELECT id_estado, nombre_estado FROM estado_maquina";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- NUEVA FUNCIÓN: Obtener las evidencias que ya están guardadas ---
    public function obtenerEvidenciasPorOrden($idOrden)
    {
        $sql = "SELECT id_evidencia, tipo_evidencia, ruta_archivo, fecha_subida 
                FROM evidencia_servicio 
                WHERE id_orden_servicio = ? 
                ORDER BY FIELD(tipo_evidencia, 'antes', 'remision', 'despues', 'firma'), fecha_subida ASC";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$idOrden]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo evidencias edición: " . $e->getMessage());
            return [];
        }
    }
    
    // --- NUEVA FUNCIÓN: Guardar ruta de la imagen ---
    public function guardarEvidenciaFoto($idOrden, $tipoEvidencia, $rutaArchivo)
    {
        try {
            $sql = "INSERT INTO evidencia_servicio (id_orden_servicio, tipo_evidencia, ruta_archivo) 
                    VALUES (:id_orden, :tipo, :ruta)";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':id_orden' => $idOrden,
                ':tipo'     => $tipoEvidencia,
                ':ruta'     => $rutaArchivo
            ]);
        } catch (PDOException $e) {
            error_log("Error guardando foto desde admin: " . $e->getMessage());
            return false;
        }
    }

    // 3. Guardar o actualizar la información
    public function actualizarComplementoSoporte($datos)
    {
        try {
            $this->conn->beginTransaction();

            // A. Actualizar soporte remoto en la tabla principal
            $sql1 = "UPDATE ordenes_servicio SET soporte_remoto = :soporte_remoto WHERE id_ordenes_servicio = :id_orden";
            $stmt1 = $this->conn->prepare($sql1);
            $stmt1->execute([
                ':soporte_remoto' => $datos['soporte_remoto'],
                ':id_orden'       => $datos['id_orden_servicio']
            ]);

            // B. Insertar o actualizar en la tabla complemento
            $sql2 = "INSERT INTO ordenes_servicio_complemento (
                        id_orden_servicio, numero_maquina, serial_maquina, serial_router, 
                        serial_ups, pendientes, administrador_punto, celular_encargado, id_estado_inicial, estado
                    ) VALUES (
                        :id_orden, :num_maq, :ser_maq, :ser_rout, :ser_ups, :pendientes, :admin, :celular, :est_ini, 1
                    ) ON DUPLICATE KEY UPDATE 
                        numero_maquina = VALUES(numero_maquina),
                        serial_maquina = VALUES(serial_maquina),
                        serial_router = VALUES(serial_router),
                        serial_ups = VALUES(serial_ups),
                        pendientes = VALUES(pendientes),
                        administrador_punto = VALUES(administrador_punto),
                        celular_encargado = VALUES(celular_encargado),
                        id_estado_inicial = VALUES(id_estado_inicial)";
            
            $stmt2 = $this->conn->prepare($sql2);
            $stmt2->execute([
                ':id_orden'   => $datos['id_orden_servicio'],
                ':num_maq'    => $datos['numero_maquina'],
                ':ser_maq'    => $datos['serial_maquina'],
                ':ser_rout'   => $datos['serial_router'],
                ':ser_ups'    => $datos['serial_ups'],
                ':pendientes' => $datos['pendientes'],
                ':admin'      => $datos['administrador_punto'],
                ':celular'    => $datos['celular_encargado'],
                ':est_ini'    => $datos['id_estado_inicial']
            ]);

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error actualizando complemento: " . $e->getMessage());
            return false;
        }
    }
}