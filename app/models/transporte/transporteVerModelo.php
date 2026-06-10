<?php
class transporteVerModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // --- OBTENER TODAS LAS INSTALACIONES (Para el Datatable) ---
    public function obtenerInstalaciones()
    {
        try {
            $sql = "SELECT 
                        i.id_instalacion, 
                        i.fecha_instalacion, 
                        i.categoria_servicio,
                        i.tipo_servicio_nombre,
                        i.es_maquina,
                        i.serial_maquina,
                        i.producto_otro,
                        t.nombre_tecnico, 
                        tm.nombre_tipo_maquina, 
                        c.nombre_cliente, 
                        i.cliente_destino_texto,
                        p.nombre_punto,
                        i.punto_destino_texto,
                        i.estado
                    FROM instalaciones_desinstalaciones i
                    LEFT JOIN tecnico t ON i.id_tecnico = t.id_tecnico
                    LEFT JOIN tipo_maquina tm ON i.id_tipo_maquina = tm.id_tipo_maquina
                    LEFT JOIN cliente c ON i.id_cliente_destino = c.id_cliente
                    LEFT JOIN punto p ON i.id_punto_destino = p.id_punto
                    WHERE i.estado = 1
                    ORDER BY i.id_instalacion DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ERROR en obtenerInstalaciones: " . $e->getMessage());
            return [];
        }
    }

    // --- ELIMINAR (Lógico: cambia estado a 0) ---
    public function eliminarInstalacion($id)
    {
        try {
            $sql = "UPDATE instalaciones_desinstalaciones SET estado = 0 WHERE id_instalacion = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("ERROR al eliminar instalacion: " . $e->getMessage());
            return false;
        }
    }
}