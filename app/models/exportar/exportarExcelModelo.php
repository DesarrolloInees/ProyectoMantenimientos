<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class exportarExcelModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function obtenerDatosExportacion()
    {
        try {
            $sql = "SELECT 
                        c.codigo_cliente,
                        c.nombre_cliente,
                        p.nombre_punto, 
                        p.direccion, 
                        mu.nombre_municipio,          
                        p.zona,                
                        d.nombre_delegacion, 
                        m.device_id, 
                        p.fecha_ultima_visita,
                        tm.nombre_completo as nombre_mantenimiento
                    FROM punto p
                    INNER JOIN maquina m ON p.id_punto = m.id_punto
                    INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                    INNER JOIN municipio mu ON p.id_municipio = mu.id_municipio 
                    LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    LEFT JOIN tipo_mantenimiento tm ON p.id_ultimo_tipo_mantenimiento = tm.id_tipo_mantenimiento
                    WHERE p.fecha_actualizacion IS NOT NULL
                    ORDER BY c.nombre_cliente ASC, p.nombre_punto ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en exportación: " . $e->getMessage());
            return [];
        }
    }
}
