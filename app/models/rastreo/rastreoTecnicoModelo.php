<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class rastreoTecnicoModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function obtenerTecnicosActivos()
    {
        try {
            $sql = "SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function obtenerRutaTecnico($idTecnico, $fecha)
    {
        try {
            // Traemos también el nombre del técnico y su ID para agrupar las líneas
            $sql = "SELECT 
                        os.id_ordenes_servicio,
                        os.id_tecnico,
                        c.nombre_cliente,
                        p.nombre_punto,
                        os.hora_entrada,
                        os.hora_salida,
                        comp.latitud_inicio,
                        comp.longitud_inicio,
                        comp.latitud_fin,
                        comp.longitud_fin,
                        t.nombre_tecnico
                    FROM ordenes_servicio os
                    INNER JOIN ordenes_servicio_complemento comp ON os.id_ordenes_servicio = comp.id_orden_servicio
                    LEFT JOIN cliente c ON os.id_cliente = c.id_cliente
                    LEFT JOIN punto p ON os.id_punto = p.id_punto
                    LEFT JOIN tecnico t ON os.id_tecnico = t.id_tecnico
                    WHERE os.fecha_visita = :fecha
                        AND (comp.latitud_inicio IS NOT NULL OR comp.latitud_fin IS NOT NULL)";
            
            // Si no enviaron "todos", filtramos por el ID del técnico
            if ($idTecnico !== 'todos') {
                $sql .= " AND os.id_tecnico = :id_tecnico";
            }
            
            // Ordenamos primero por técnico, y luego por hora para que la ruta tenga sentido
            $sql .= " ORDER BY os.id_tecnico ASC, os.hora_entrada ASC, os.id_ordenes_servicio ASC";
            
            $stmt = $this->conn->prepare($sql);
            
            $params = [':fecha' => $fecha];
            if ($idTecnico !== 'todos') {
                $params[':id_tecnico'] = $idTecnico;
            }
            
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo ruta: " . $e->getMessage());
            return [];
        }
    }
}