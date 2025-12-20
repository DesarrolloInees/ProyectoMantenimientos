<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ReporteEjecutivoModelo
{
    private $conn;

    public function __construct(PDO $db) { $this->conn = $db; }

    // 1. Servicios por día
    public function getServiciosPorDia($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT fecha_visita, COUNT(*) as total 
                    FROM ordenes_servicio 
                    WHERE fecha_visita BETWEEN :inicio AND :fin 
                    GROUP BY fecha_visita ORDER BY fecha_visita ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getServiciosPorDia: " . $e->getMessage());
            return [];
        }
    }

    // 2. Delegaciones Intervenidas
    public function getDelegacionesIntervenidas($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT d.nombre_delegacion, COUNT(os.id_ordenes_servicio) as total
                    FROM ordenes_servicio os
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY d.nombre_delegacion 
                    ORDER BY total DESC LIMIT 10";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getDelegacionesIntervenidas: " . $e->getMessage());
            return [];
        }
    }

    // 3. Horas trabajadas vs Servicios (Por Técnico)
    public function getHorasVsServicios($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT t.nombre_tecnico, 
                           COUNT(os.id_ordenes_servicio) as total_servicios,
                           SUM(TIME_TO_SEC(os.tiempo_servicio))/3600 as total_horas
                    FROM ordenes_servicio os
                    INNER JOIN tecnico t ON os.id_tecnico = t.id_tecnico
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY t.nombre_tecnico
                    ORDER BY total_servicios DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getHorasVsServicios: " . $e->getMessage());
            return [];
        }
    }

    // 4. Por Tipo de Mantenimiento
    public function getPorTipoMantenimiento($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT tm.nombre_completo as tipo, COUNT(*) as total
                    FROM ordenes_servicio os
                    INNER JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY tm.nombre_completo";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getPorTipoMantenimiento: " . $e->getMessage());
            return [];
        }
    }

    // 5. Novedades vs Normales
    public function getDistribucionNovedades($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT 
                        SUM(CASE WHEN tiene_novedad = 1 THEN 1 ELSE 0 END) as con_novedad,
                        SUM(CASE WHEN tiene_novedad = 0 THEN 1 ELSE 0 END) as sin_novedad
                    FROM ordenes_servicio 
                    WHERE fecha_visita BETWEEN :inicio AND :fin";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getDistribucionNovedades: " . $e->getMessage());
            return [];
        }
    }

    // 6. Servicios Fallidos vs Exitosos (Estados)
    public function getServiciosFallidos($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT em.nombre_estado, COUNT(*) as total 
                    FROM ordenes_servicio os
                    INNER JOIN estado_maquina em ON os.id_estado_maquina = em.id_estado
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY em.nombre_estado";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getServiciosFallidos: " . $e->getMessage());
            return [];
        }
    }

    // 7. Repuestos Inees vs Prosegur (Comparativa)
    public function getComparativaRepuestos($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT osr.origen, SUM(osr.cantidad) as total
                    FROM orden_servicio_repuesto osr
                    INNER JOIN ordenes_servicio os ON osr.id_orden_servicio = os.id_ordenes_servicio
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY osr.origen";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getComparativaRepuestos: " . $e->getMessage());
            return [];
        }
    }

    // 8. Puntos más visitados (más de 3 servicios)
    public function getPuntosMasVisitados($fecha_inicio, $fecha_fin, $minServicios = 3)
    {
        try {
            $sql = "SELECT p.nombre_punto, d.nombre_delegacion, COUNT(os.id_ordenes_servicio) as total
                    FROM ordenes_servicio os
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY p.id_punto, p.nombre_punto, d.nombre_delegacion
                    HAVING total >= :min
                    ORDER BY total DESC
                    LIMIT 15";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->bindParam(':min', $minServicios, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getPuntosMasVisitados: " . $e->getMessage());
            return [];
        }
    }

    // 9. Puntos con más servicios fallidos (más de 2)
    public function getPuntosConFallidos($fecha_inicio, $fecha_fin, $minFallidos = 2)
    {
        try {
            $sql = "SELECT p.nombre_punto, d.nombre_delegacion, COUNT(os.id_ordenes_servicio) as total_fallidos
                    FROM ordenes_servicio os
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    INNER JOIN estado_maquina em ON os.id_estado_maquina = em.id_estado
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    AND em.nombre_estado IN ('Fallido', 'No Operativo', 'Fuera de Servicio')
                    GROUP BY p.id_punto, p.nombre_punto, d.nombre_delegacion
                    HAVING total_fallidos >= :min
                    ORDER BY total_fallidos DESC
                    LIMIT 15";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->bindParam(':min', $minFallidos, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getPuntosConFallidos: " . $e->getMessage());
            return [];
        }
    }

    // 10. Calificaciones del servicio
    public function getCalificacionesServicio($fecha_inicio, $fecha_fin)
    {
        try {
            // Hacemos JOIN con la tabla nueva para sacar el nombre real
            // Asumimos que ordenes_servicio tiene una columna 'id_calificacion'
            $sql = "SELECT 
                        cs.nombre_calificacion as calificacion,
                        COUNT(os.id_ordenes_servicio) as total
                    FROM ordenes_servicio os
                    INNER JOIN calificacion_servicio cs ON os.id_calificacion = cs.id_calificacion
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY cs.nombre_calificacion, cs.id_calificacion
                    ORDER BY cs.id_calificacion DESC"; 
            
            // Nota: Ordenamos por ID DESC para que salga primero la calificación más alta (5, luego 4, etc.)

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getCalificacionesServicio: " . $e->getMessage());
            return [];
        }
    }

    // 11. Tipos de máquina atendidos por delegación
    public function getTiposMaquinaPorDelegacion($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT d.nombre_delegacion, tm.nombre_tipo_maquina, COUNT(os.id_ordenes_servicio) as total
                    FROM ordenes_servicio os
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    INNER JOIN maquina m ON p.id_maquina = m.id_maquina
                    INNER JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY d.nombre_delegacion, tm.nombre_tipo_maquina
                    ORDER BY d.nombre_delegacion, total DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getTiposMaquinaPorDelegacion: " . $e->getMessage());
            return [];
        }
    }

    // 12. Servicios fallidos por delegación (CORREGIDO: Solo Tipo Mantenimiento ID 4)
    public function getServicesFallidosPorDelegacion($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT d.nombre_delegacion, COUNT(os.id_ordenes_servicio) as total_fallidos
                    FROM ordenes_servicio os
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    
                    /* --- FILTRO EXACTO SOLICITADO --- */
                    AND os.id_tipo_mantenimiento = 4 
                    /* -------------------------------- */
                    
                    GROUP BY d.nombre_delegacion
                    ORDER BY total_fallidos DESC"; // Quitamos LIMIT para que no se esconda nada si son pocos

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getServicesFallidosPorDelegacion: " . $e->getMessage());
            return [];
        }
    }
}
?>