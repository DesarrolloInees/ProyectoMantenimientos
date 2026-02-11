<?php
// app/models/orden/ordenMovilModelo.php

if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ordenMovilModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function obtenerClientes()
    {
        $sql = "SELECT id_cliente, nombre_cliente FROM cliente WHERE estado = 1 ORDER BY nombre_cliente ASC";
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPuntosPorCliente($idCliente)
    {
        $sql = "SELECT id_punto, nombre_punto FROM punto WHERE id_cliente = ? ORDER BY nombre_punto ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$idCliente]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Consulta optimizada para tarjetas
    public function buscarServicios($idCliente, $idPunto)
    {
        // Limitamos a los últimos 20 servicios
        $sql = "SELECT 
                    o.id_ordenes_servicio,
                    o.fecha_visita,
                    o.actividades_realizadas as que_se_hizo,
                    
                    t.nombre_tecnico,
                    tm.nombre_completo as tipo_servicio,
                    
                    (
                        SELECT GROUP_CONCAT(
                            CONCAT(r.nombre_repuesto, ' (x', osr.cantidad, ')') 
                            SEPARATOR ', '
                        )
                        FROM orden_servicio_repuesto osr
                        JOIN repuesto r ON osr.id_repuesto = r.id_repuesto
                        WHERE osr.id_orden_servicio = o.id_ordenes_servicio
                    ) as repuestos

                FROM ordenes_servicio o
                LEFT JOIN tecnico t ON o.id_tecnico = t.id_tecnico
                LEFT JOIN tipo_mantenimiento tm ON o.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                
                LEFT JOIN maquina m ON o.id_maquina = m.id_maquina
                
                WHERE (o.id_punto = ? OR m.id_punto = ?)
                AND (o.id_cliente = ?)

                -- 🔥 CAMBIO AQUÍ: Excluir ID 4 (FALLIDO) y ID 5 (GARANTIA)
                AND o.id_tipo_mantenimiento NOT IN (4, 5)
                
                ORDER BY o.fecha_visita DESC
                LIMIT 20";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$idPunto, $idPunto, $idCliente]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}