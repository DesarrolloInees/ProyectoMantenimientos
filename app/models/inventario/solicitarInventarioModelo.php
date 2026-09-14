<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class solicitarInventarioModelo
{
    private $conn;

    public function __construct($conexionObj)
    {
        $this->conn = $conexionObj;
    }

    // Obtener datos del técnico asociado al usuario logueado
    public function obtenerTecnicoPorUsuarioId($usuarioId)
    {
        try {
            $sql = "SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE usuario_id = :uid LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':uid', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Cargar lista de repuestos activos
    public function obtenerRepuestos()
    {
        try {
            $sql = "SELECT id_repuesto, nombre_repuesto, codigo_referencia FROM repuesto ORDER BY nombre_repuesto ASC";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Guardar una nueva solicitud de inventario en la base de datos
    public function guardarSolicitud($usuarioId, $solicitanteNombre, $observaciones, $repuestos, $cantidades)
    {
        try {
            $this->conn->beginTransaction();

            $sql = "INSERT INTO solicitudes_inventario (id_usuario, solicitante_nombre, estado, observaciones) 
                    VALUES (:uid, :nombre, 'Pendiente', :obs)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':uid' => $usuarioId,
                ':nombre' => $solicitanteNombre,
                ':obs' => $observaciones
            ]);

            $idSolicitud = $this->conn->lastInsertId();

            $sqlDetalle = "INSERT INTO solicitud_inventario_detalle (id_solicitud, id_repuesto, cantidad) 
                           VALUES (:id_sol, :id_rep, :cant)";
            $stmtDetalle = $this->conn->prepare($sqlDetalle);

            foreach ($repuestos as $i => $idRepuesto) {
                $cant = intval($cantidades[$i] ?? 1);
                if (!empty($idRepuesto) && $cant > 0) {
                    $stmtDetalle->execute([
                        ':id_sol' => $idSolicitud,
                        ':id_rep' => $idRepuesto,
                        ':cant' => $cant
                    ]);
                }
            }

            $this->conn->commit();
            return $idSolicitud;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error al guardar solicitud de inventario: " . $e->getMessage());
            return false;
        }
    }

    // Obtener listado de solicitudes con sus detalles
    public function obtenerSolicitudes($idUsuario = null)
    {
        try {
            $sql = "SELECT s.*, 
                    (SELECT COUNT(*) FROM solicitud_inventario_detalle d WHERE d.id_solicitud = s.id_solicitud) as total_items
                    FROM solicitudes_inventario s ";
            
            if ($idUsuario !== null) {
                $sql .= " WHERE s.id_usuario = :uid ";
            }

            $sql .= " ORDER BY s.fecha_solicitud DESC";

            $stmt = $this->conn->prepare($sql);
            if ($idUsuario !== null) {
                $stmt->bindParam(':uid', $idUsuario, PDO::PARAM_INT);
            }
            $stmt->execute();
            $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Cargar los repuestos para cada solicitud
            foreach ($solicitudes as &$sol) {
                $sqlDet = "SELECT d.*, r.nombre_repuesto, r.codigo_referencia 
                            FROM solicitud_inventario_detalle d
                            JOIN repuesto r ON d.id_repuesto = r.id_repuesto
                            WHERE d.id_solicitud = :id_sol";
                $stmtDet = $this->conn->prepare($sqlDet);
                $stmtDet->execute([':id_sol' => $sol['id_solicitud']]);
                $sol['detalles'] = $stmtDet->fetchAll(PDO::FETCH_ASSOC);
            }

            return $solicitudes;

        } catch (PDOException $e) {
            error_log("Error al obtener solicitudes: " . $e->getMessage());
            return [];
        }
    }

    // Actualizar el estado de una solicitud ('Pendiente', 'En gestión', 'Finalizado')
    public function actualizarEstadoSolicitud($idSolicitud, $nuevoEstado)
    {
        $estadosValidos = ['Pendiente', 'En gestión', 'Finalizado'];
        if (!in_array($nuevoEstado, $estadosValidos)) {
            return false;
        }

        try {
            $sql = "UPDATE solicitudes_inventario SET estado = :estado WHERE id_solicitud = :id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':estado' => $nuevoEstado,
                ':id' => $idSolicitud
            ]);
        } catch (PDOException $e) {
            error_log("Error al actualizar estado de solicitud: " . $e->getMessage());
            return false;
        }
    }
}
