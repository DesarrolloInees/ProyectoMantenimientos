<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ControlRemisionVerModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function listarRemisiones()
    {
        // Agregamos WHERE cr.estado != 'ELIMINADO'
        $sql = "SELECT 
                    cr.id_control,
                    cr.numero_remision,
                    cr.estado,
                    cr.fecha_asignacion,
                    t.nombre_tecnico
                FROM control_remisiones cr
                INNER JOIN tecnico t ON cr.id_tecnico = t.id_tecnico
                WHERE cr.estado != 'ELIMINADO' 
                ORDER BY cr.id_control DESC";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}