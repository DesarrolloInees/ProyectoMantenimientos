<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

class cronometroCrearModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function obtenerDatosTecnicoPorUsuario($idUsuario)
    {
        try {
            $sql = "SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE usuario_id = :id_usuario AND estado = 1 LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id_usuario' => $idUsuario]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obtenerDatosTecnico: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerClientesActivos()
    {
        try {
            $sql = "SELECT id_cliente, nombre_cliente FROM cliente WHERE estado = 1 ORDER BY nombre_cliente ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function obtenerPuntosActivos()
    {
        try {
            $sql = "SELECT id_punto, nombre_punto, id_cliente FROM punto WHERE estado = 1 ORDER BY nombre_punto ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function obtenerTiposMantenimiento()
    {
        try {
            $sql = "SELECT id_tipo_mantenimiento, nombre_completo FROM tipo_mantenimiento WHERE estado = 1 ORDER BY nombre_completo ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function obtenerServicioEnProgreso($idTecnico)
    {
        try {
            $sql = "SELECT ml.*, c.nombre_cliente, p.nombre_punto, tm.nombre_completo as tipo_mantenimiento 
                    FROM monitoreo_motorizados_live ml
                    INNER JOIN cliente c ON ml.id_cliente = c.id_cliente
                    INNER JOIN punto p ON ml.id_punto = p.id_punto
                    INNER JOIN tipo_mantenimiento tm ON ml.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                    WHERE ml.id_tecnico = :id_tecnico AND ml.estado_actual = 'En Progreso' 
                    ORDER BY ml.id_monitoreo DESC LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id_tecnico' => $idTecnico]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obtenerServicioEnProgreso: " . $e->getMessage());
            return false;
        }
    }

    public function iniciarCronometro($datos)
    {
        try {
            $sql = "INSERT INTO monitoreo_motorizados_live 
                (id_tecnico, id_cliente, id_punto, id_tipo_mantenimiento, fecha_servicio, hora_inicio) 
                VALUES (:id_tecnico, :id_cliente, :id_punto, :id_tipo_mantenimiento, :fecha, :hora_inicio)";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':id_tecnico' => $datos['id_tecnico'],
                ':id_cliente' => $datos['id_cliente'],
                ':id_punto' => $datos['id_punto'],
                ':id_tipo_mantenimiento' => $datos['id_tipo_mantenimiento'],
                ':fecha' => $datos['fecha_servicio'],
                ':hora_inicio' => $datos['hora_inicio']
            ]);
        } catch (PDOException $e) {
            error_log("Error iniciarCronometro: " . $e->getMessage());
            return false;
        }
    }

    // Reemplaza la función finalizarCronometro con esta:
    public function finalizarCronometro($idMonitoreo, $horaFin, $minutosReales, $idTipoMantenimientoFinal, $servicioModificado, $justificacion)
    {
        try {
            $sql = "UPDATE monitoreo_motorizados_live 
                SET estado_actual = 'Finalizado', 
                    hora_fin = :hora_fin, 
                    duracion_real_minutos = :duracion,
                    id_tipo_mantenimiento = :id_tipo,
                    servicio_modificado = :modificado,
                    justificacion_retraso = :justificacion
                WHERE id_monitoreo = :id_monitoreo";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':hora_fin' => $horaFin,
                ':duracion' => $minutosReales,
                ':id_tipo' => $idTipoMantenimientoFinal,
                ':modificado' => $servicioModificado,
                ':justificacion' => $justificacion,
                ':id_monitoreo' => $idMonitoreo
            ]);
        } catch (PDOException $e) {
            error_log("Error finalizarCronometro: " . $e->getMessage());
            return false;
        }
    }


    public function actualizarTipoMantenimientoLive($idMonitoreo, $nuevoTipo)
    {
        try {
            // Solo actualiza SI Y SOLO SI aún no se ha modificado (servicio_modificado = 0)
            $sql = "UPDATE monitoreo_motorizados_live 
                    SET id_tipo_mantenimiento = :id_tipo, 
                        servicio_modificado = 1 
                    WHERE id_monitoreo = :id_monitoreo AND servicio_modificado = 0";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id_tipo' => $nuevoTipo,
                ':id_monitoreo' => $idMonitoreo
            ]);

            // Retorna true si se afectó al menos 1 fila
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error actualizarTipoMantenimientoLive: " . $e->getMessage());
            return false;
        }
    }
}