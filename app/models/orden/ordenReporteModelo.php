<?php
// app/models/orden/ordenReporteModelo.php

if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ordenReporteModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function obtenerServiciosPorRango($fechaInicio, $fechaFin)
    {
        $sql = "SELECT 
                o.id_ordenes_servicio,
                o.numero_remision,
                o.fecha_visita,
                o.hora_entrada,
                o.hora_salida,
                o.tiempo_servicio,
                o.valor_servicio,
                o.actividades_realizadas as que_se_hizo,
                
                -- MÁQUINA
                m.device_id,
                tm.nombre_tipo_maquina,
                
                -- CLIENTE (Prioridad: Directo en orden, sino el de la máquina)
                COALESCE(c_directo.nombre_cliente, c_maq.nombre_cliente) as nombre_cliente,
                
                -- PUNTO
                COALESCE(p_directo.nombre_punto, p_maq.nombre_punto) as nombre_punto,
                
                -- DELEGACIÓN
                COALESCE(d_directo.nombre_delegacion, d_maq.nombre_delegacion) as delegacion,

                -- ZONA/MODALIDAD
                CASE 
                    WHEN o.id_modalidad = 1 THEN 'URBANO'
                    WHEN o.id_modalidad = 2 THEN 'INTERURBANO'
                    ELSE 'NO DEFINIDO'
                END as tipo_zona,

                -- TÉCNICO Y ESTADO
                t.nombre_tecnico,
                tman.nombre_completo as tipo_servicio,
                em.nombre_estado as estado_maquina,
                cal.nombre_calificacion,

                -- REPUESTOS (Concatenados en una sola celda para el Excel)
                IFNULL(
                    (SELECT GROUP_CONCAT(
                        CONCAT(r.nombre_repuesto, ' (', osr.origen, ')', IF(osr.cantidad>1, CONCAT(' x', osr.cantidad), ''))
                        SEPARATOR ', ')
                    FROM orden_servicio_repuesto osr
                    JOIN repuesto r ON osr.id_repuesto = r.id_repuesto
                    WHERE osr.id_orden_servicio = o.id_ordenes_servicio)
                , '') as repuestos_texto

                FROM ordenes_servicio o
            
                -- JOINS PARA DATOS DE MÁQUINA
                LEFT JOIN maquina m ON o.id_maquina = m.id_maquina
                LEFT JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                
                -- JOINS PARA DATOS DE ORDEN
                LEFT JOIN tecnico t ON o.id_tecnico = t.id_tecnico
                LEFT JOIN tipo_mantenimiento tman ON o.id_tipo_mantenimiento = tman.id_tipo_mantenimiento
                LEFT JOIN estado_maquina em ON o.id_estado_maquina = em.id_estado
                LEFT JOIN calificacion_servicio cal ON o.id_calificacion = cal.id_calificacion
                
                -- JOINS RELACIONADOS A LA MÁQUINA (INFO BASE)
                LEFT JOIN punto p_maq ON m.id_punto = p_maq.id_punto
                LEFT JOIN cliente c_maq ON p_maq.id_cliente = c_maq.id_cliente
                LEFT JOIN delegacion d_maq ON p_maq.id_delegacion = d_maq.id_delegacion

                -- JOINS RELACIONADOS A LA ORDEN DIRECTA (Por si cambiaron la máquina de punto)
                LEFT JOIN cliente c_directo ON o.id_cliente = c_directo.id_cliente
                LEFT JOIN punto p_directo ON o.id_punto = p_directo.id_punto
                LEFT JOIN delegacion d_directo ON p_directo.id_delegacion = d_directo.id_delegacion
                
                -- FILTRO POR RANGO DE FECHAS
                WHERE o.fecha_visita BETWEEN ? AND ?
                
                -- ORDEN MEJORADO: Delegación -> Fecha -> Técnico -> Hora
                ORDER BY 
                    COALESCE(d_directo.nombre_delegacion, d_maq.nombre_delegacion) ASC,
                    o.fecha_visita ASC,
                    t.nombre_tecnico ASC,
                    o.hora_entrada ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$fechaInicio, $fechaFin]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}