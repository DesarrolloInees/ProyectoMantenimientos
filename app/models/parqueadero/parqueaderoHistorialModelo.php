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

    public function obtenerHistorialPorTecnico($idTecnico)
    {
        try {
            $sql = "SELECT fp.id_factura_parqueadero, fp.fecha_servicio, fp.hora_inicio, fp.hora_fin, 
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
}