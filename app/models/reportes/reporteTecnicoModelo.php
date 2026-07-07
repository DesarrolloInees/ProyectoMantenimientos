<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ReporteTecnicoModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function obtenerTecnicos()
    {
        return $this->conn->query("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerParametro($clave)
    {
        try {
            $sql = "SELECT valor FROM parametros WHERE clave = :clave LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':clave', $clave);
            $stmt->execute();
            return $stmt->fetchColumn(); // Retorna el valor directo o false
        } catch (PDOException $e) {
            return null;
        }
    }

    public function obtenerFestivos($inicio, $fin)
{
    try {
        $sql = "SELECT fecha FROM dias_festivos WHERE fecha BETWEEN :inicio AND :fin";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':inicio', $inicio);
        $stmt->bindParam(':fin', $fin);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN); // Retorna array simple de fechas ['2024-01-01', '2024-01-08']
    } catch (PDOException $e) {
        return [];
    }
}

    public function generarReporteServicios($id_tecnico, $fecha_inicio, $fecha_fin)
    {
        try {
            // 1. OBTENER ÓRDENES DE SERVICIO NORMALES
            // Excluimos las instalaciones de aquí para que no se cuenten como servicios estándar
            $sql1 = "SELECT 
                os.id_ordenes_servicio,
                os.numero_remision,
                os.fecha_visita,
                os.hora_entrada,
                os.hora_salida,
                os.valor_servicio,
                os.actividades_realizadas,
                t.nombre_tecnico,
                c.nombre_cliente,
                p.nombre_punto,
                d.nombre_delegacion AS delegacion,
                m.device_id,
                tm.nombre_completo AS tipo_mantenimiento
                    FROM ordenes_servicio os
                    INNER JOIN tecnico t ON os.id_tecnico = t.id_tecnico
                    INNER JOIN cliente c ON os.id_cliente = c.id_cliente
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion 
                    INNER JOIN maquina m ON os.id_maquina = m.id_maquina
                    LEFT JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    AND UPPER(tm.nombre_completo) NOT LIKE '%INSTALACIÓN%' 
                    AND UPPER(tm.nombre_completo) NOT LIKE '%INSTALACION%'";

            if (!empty($id_tecnico)) {
                $sql1 .= " AND os.id_tecnico = :id_tecnico";
            }

            $stmt1 = $this->conn->prepare($sql1);
            $stmt1->bindParam(':inicio', $fecha_inicio);
            $stmt1->bindParam(':fin', $fecha_fin);
            if (!empty($id_tecnico)) {
                $stmt1->bindParam(':id_tecnico', $id_tecnico, PDO::PARAM_INT);
            }
            $stmt1->execute();
            $ordenesNormales = $stmt1->fetchAll(PDO::FETCH_ASSOC) ?: []; 

            // 2. OBTENER REMISIONES SUELTAS (Disfrazadas de servicio)
            // Aquí agregamos los estados de Instalación y Capacitación
            $sql2 = "SELECT 
                cr.id_control AS id_ordenes_servicio,
                cr.numero_remision,
                DATE(cr.fecha_uso) AS fecha_visita,
                TIME(cr.fecha_uso) AS hora_entrada,
                TIME(cr.fecha_uso) AS hora_salida,
                0 AS valor_servicio,
                er.nombre_estado AS actividades_realizadas,
                t.nombre_tecnico,
                'SIN CLIENTE (REMISIÓN)' AS nombre_cliente,
                'N/A' AS nombre_punto,
                'N/A' AS delegacion,
                'N/A' AS device_id,
                er.nombre_estado AS tipo_mantenimiento
                FROM control_remisiones cr
                INNER JOIN tecnico t ON cr.id_tecnico = t.id_tecnico
                INNER JOIN estados_remision er ON cr.id_estado = er.id_estado
                WHERE cr.fecha_uso IS NOT NULL 
                AND cr.fecha_uso != '0000-00-00 00:00:00' 
                AND cr.fecha_uso != ''
                AND DATE(cr.fecha_uso) BETWEEN :inicio AND :fin
                AND (cr.id_orden_servicio IS NULL OR cr.id_orden_servicio = 0)"; 

            if (!empty($id_tecnico)) {
                $sql2 .= " AND cr.id_tecnico = :id_tecnico";
            }

            // Agregamos las variaciones de instalación al IN
            $sql2 .= " AND UPPER(TRIM(er.nombre_estado)) IN (
                'KISAN', 'DESINSTALACIÓN', 'DESINSTALACION', 'CAMBIO DE MÁQUINA', 'CAMBIO DE MAQUINA', 
                'INSTALACIÓN FALLIDA', 'INSTALACION FALLIDA', 'DESANCLAJE', 'ANCLAJE',
                'INSTALACIÓN MÁS CAPACITACIÓN', 'INSTALACION MAS CAPACITACION',
                'INSTALACIÓN SIN CAPACITACIÓN', 'INSTALACION SIN CAPACITACION',
                'INSTALACIÓN', 'INSTALACION'
            )";

            $stmt2 = $this->conn->prepare($sql2);
            $stmt2->bindParam(':inicio', $fecha_inicio);
            $stmt2->bindParam(':fin', $fecha_fin);
            if (!empty($id_tecnico)) {
                $stmt2->bindParam(':id_tecnico', $id_tecnico, PDO::PARAM_INT);
            }
            $stmt2->execute();
            $remisionesExtra = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: []; 

            // 3. COMBINAR AMBOS ARRAYS Y ORDENAR
            $resultadoFinal = array_merge($ordenesNormales, $remisionesExtra);
            
            usort($resultadoFinal, function($a, $b) {
                if ($a['nombre_tecnico'] == $b['nombre_tecnico']) {
                    return strtotime($b['fecha_visita']) - strtotime($a['fecha_visita']);
                }
                return strcmp($a['nombre_tecnico'], $b['nombre_tecnico']);
            });

            return $resultadoFinal;

        } catch (PDOException $e) {
            error_log("Error en reporte técnico: " . $e->getMessage());
            return [];
        }
    }
}
