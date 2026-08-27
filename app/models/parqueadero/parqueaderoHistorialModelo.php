<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

class ParqueaderoHistorialModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function obtenerIdTecnicoPorUsuario($idUsuario)
    {
        try {
            $sql = "SELECT id_tecnico FROM tecnico WHERE usuario_id = :id_usuario AND estado = 1 LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id_usuario' => $idUsuario]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ? (int) $res['id_tecnico'] : 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function obtenerDatosTecnicoPorUsuario($idUsuario)
    {
        try {
            $sql = "SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE usuario_id = :id_usuario AND estado = 1 LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id_usuario' => $idUsuario]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function obtenerPuntosActivos()
    {
        try {
            $sql = "SELECT id_punto, nombre_punto FROM punto WHERE estado = 1 ORDER BY nombre_punto ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function obtenerHistorialPorTecnico($idTecnico)
    {
        try {
            $sql = "SELECT fp.id_factura_parqueadero, fp.id_punto, fp.fecha_servicio, fp.hora_inicio, fp.hora_fin, 
                            fp.numero_factura, fp.valor_factura, fp.ruta_foto, p.nombre_punto 
                    FROM facturas_parqueadero fp
                    INNER JOIN punto p ON fp.id_punto = p.id_punto
                    WHERE fp.id_tecnico = :id_tecnico AND fp.estado = 1
                    ORDER BY fp.fecha_servicio DESC, fp.id_factura_parqueadero DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id_tecnico' => $idTecnico]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo historial de parqueaderos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerFacturaPorIdYTecnico($idFactura, $idTecnico)
    {
        try {
            $sql = "SELECT * FROM facturas_parqueadero 
                    WHERE id_factura_parqueadero = :id_factura AND id_tecnico = :id_tecnico AND estado = 1 LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id_factura' => $idFactura,
                ':id_tecnico' => $idTecnico
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    public function actualizarFacturaParqueadero($datos)
    {
        try {
            $sql = "UPDATE facturas_parqueadero 
                    SET id_punto = :id_punto,
                        fecha_servicio = :fecha_servicio,
                        hora_inicio = :hora_inicio,
                        hora_fin = :hora_fin,
                        numero_factura = :numero_factura,
                        valor_factura = :valor_factura";

            $params = [
                ':id_punto' => $datos['id_punto'],
                ':fecha_servicio' => $datos['fecha_servicio'],
                ':hora_inicio' => $datos['hora_inicio'],
                ':hora_fin' => $datos['hora_fin'],
                ':numero_factura' => $datos['numero_factura'],
                ':valor_factura' => $datos['valor_factura'],
                ':id_factura' => $datos['id_factura'],
                ':id_tecnico' => $datos['id_tecnico']
            ];

            // Si envió una nueva foto, actualizar la ruta en BD
            if (!empty($datos['ruta_foto'])) {
                $sql .= ", ruta_foto = :ruta_foto";
                $params[':ruta_foto'] = $datos['ruta_foto'];
            }

            $sql .= " WHERE id_factura_parqueadero = :id_factura AND id_tecnico = :id_tecnico";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error actualizando factura parqueadero: " . $e->getMessage());
            return false;
        }
    }

    public function eliminarFotoFactura($idFactura, $idTecnico)
    {
        try {
            $sql = "UPDATE facturas_parqueadero 
                SET ruta_foto = '' 
                WHERE id_factura_parqueadero = :id_factura AND id_tecnico = :id_tecnico";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':id_factura' => $idFactura,
                ':id_tecnico' => $idTecnico
            ]);
        } catch (PDOException $e) {
            error_log("Error al eliminar foto de parqueadero: " . $e->getMessage());
            return false;
        }
    }
}