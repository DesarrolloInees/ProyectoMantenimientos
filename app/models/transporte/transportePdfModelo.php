<?php
class transportePdfModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getDetalleInstalacionPdf($id)
    {
        try {
            $sql = "SELECT 
                        i.*, 
                        er.nombre_estado AS nombre_operacion, /* <-- NUEVO: Traemos el nombre real */
                        t.nombre_tecnico,
                        t.ruta_firma, 
                        tm.nombre_tipo_maquina,
                        p.nombre_punto, 
                        p.direccion as direccion_punto,
                        c.nombre_cliente,
                        d_orig.nombre_delegacion as delegacion_origen,
                        d_dest.nombre_delegacion as delegacion_destino,
                        cr.numero_remision
                    FROM instalaciones_desinstalaciones i
                    /* NUEVO JOIN CON ESTADOS_REMISION */
                    LEFT JOIN estados_remision er ON i.id_estado_operacion = er.id_estado 
                    LEFT JOIN tecnico t ON i.id_tecnico = t.id_tecnico
                    LEFT JOIN tipo_maquina tm ON i.id_tipo_maquina = tm.id_tipo_maquina
                    LEFT JOIN punto p ON i.id_punto = p.id_punto
                    LEFT JOIN cliente c ON p.id_cliente = c.id_cliente
                    LEFT JOIN delegacion d_orig ON i.id_delegacion_origen = d_orig.id_delegacion
                    LEFT JOIN delegacion d_dest ON i.id_delegacion_destino = d_dest.id_delegacion
                    LEFT JOIN control_remisiones cr ON i.id_control_remision = cr.id_control
                    WHERE i.id_instalacion = :id AND i.estado = 1
                    LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ERROR en getDetalleInstalacionPdf: " . $e->getMessage());
            return false;
        }
    }
}