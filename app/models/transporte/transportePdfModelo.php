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
                        t.nombre_tecnico,
                        t.ruta_firma, 
                        tm.nombre_tipo_maquina,
                        co.nombre_cliente AS cliente_origen_nombre,
                        po.nombre_punto AS punto_origen_nombre,
                        po.direccion AS direccion_origen,
                        cd.nombre_cliente AS cliente_destino_nombre,
                        pd.nombre_punto AS punto_destino_nombre,
                        pd.direccion AS direccion_destino,
                        cr.numero_remision
                    FROM instalaciones_desinstalaciones i
                    LEFT JOIN tecnico t ON i.id_tecnico = t.id_tecnico
                    LEFT JOIN tipo_maquina tm ON i.id_tipo_maquina = tm.id_tipo_maquina
                    LEFT JOIN cliente co ON i.id_cliente_origen = co.id_cliente
                    LEFT JOIN punto po ON i.id_punto_origen = po.id_punto
                    LEFT JOIN cliente cd ON i.id_cliente_destino = cd.id_cliente
                    LEFT JOIN punto pd ON i.id_punto_destino = pd.id_punto
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