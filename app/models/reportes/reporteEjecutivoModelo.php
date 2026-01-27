<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ReporteEjecutivoModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // ==========================================
    // SECCIÓN 1: DATOS GENERALES Y DASHBOARD
    // ==========================================

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
        } catch (PDOException $e) { return []; }
    }

    public function getDelegacionesIntervenidas($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT 
                        d.nombre_delegacion, 
                        COUNT(os.id_ordenes_servicio) as total,
                        COUNT(DISTINCT os.id_tecnico) as num_tecnicos
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
        } catch (PDOException $e) { return []; }
    }

    // ESTA ERA LA QUE FALTABA Y CAUSABA EL ERROR
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
        } catch (PDOException $e) { return []; }
    }

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
        } catch (PDOException $e) { return []; }
    }

    public function getDistribucionNovedades($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT SUM(CASE WHEN tiene_novedad = 1 THEN 1 ELSE 0 END) as con_novedad,
                           SUM(CASE WHEN tiene_novedad = 0 THEN 1 ELSE 0 END) as sin_novedad
                    FROM ordenes_servicio WHERE fecha_visita BETWEEN :inicio AND :fin";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }

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
        } catch (PDOException $e) { return []; }
    }

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
        } catch (PDOException $e) { return []; }
    }

    // ==========================================
    // SECCIÓN 2: FUNCIONES PARA EL PDF (MATRICES)
    // ==========================================

    // Obtener SOLO los tipos de máquina activos (para columnas dinámicas)
    public function getTiposMaquinaActivos($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT DISTINCT tm.nombre_tipo_maquina
                    FROM ordenes_servicio os
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    INNER JOIN maquina m ON p.id_punto = m.id_punto
                    INNER JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    ORDER BY tm.nombre_tipo_maquina ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }

    // Matriz: Tipo de Máquina por Delegación (CORREGIDA CON TUS TABLAS)
    public function getDatosMatrizTipoMaquina($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT d.nombre_delegacion, tm.nombre_tipo_maquina, COUNT(os.id_ordenes_servicio) as total
                    FROM ordenes_servicio os
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    INNER JOIN maquina m ON m.id_punto = p.id_punto 
                    INNER JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina 
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY d.nombre_delegacion, tm.nombre_tipo_maquina";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }

    // Repuestos: Top usados por delegación (CORREGIDA CON TUS TABLAS)
    public function getRepuestosPorDelegacion($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT d.nombre_delegacion,
                           r.nombre_repuesto as descripcion_repuesto, 
                           SUM(osr.cantidad) as cantidad_usada
                    FROM orden_servicio_repuesto osr
                    INNER JOIN ordenes_servicio os ON osr.id_orden_servicio = os.id_ordenes_servicio
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    INNER JOIN repuesto r ON osr.id_repuesto = r.id_repuesto
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY d.nombre_delegacion, r.nombre_repuesto
                    ORDER BY d.nombre_delegacion ASC, cantidad_usada DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }

    public function getAllTiposMantenimiento()
    {
        try {
            $sql = "SELECT id_tipo_mantenimiento, nombre_completo FROM tipo_mantenimiento ORDER BY nombre_completo ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }

    public function getDatosMatrizMantenimiento($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT d.nombre_delegacion, tm.nombre_completo as tipo, COUNT(os.id_ordenes_servicio) as total
                    FROM ordenes_servicio os
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    INNER JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY d.nombre_delegacion, tm.nombre_completo";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }

    // ==========================================
    // SECCIÓN 3: OTROS INDICADORES (PDF Y DASHBOARD)
    // ==========================================

    public function getProductividadDetallada($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT t.nombre_tecnico,
                        SUM(CASE WHEN DAYOFWEEK(os.fecha_visita) BETWEEN 2 AND 6 THEN 1 ELSE 0 END) as servicios_lv,
                        COUNT(DISTINCT CASE WHEN DAYOFWEEK(os.fecha_visita) BETWEEN 2 AND 6 THEN os.fecha_visita END) as dias_trabajados_lv,
                        SUM(CASE WHEN DAYOFWEEK(os.fecha_visita) = 7 THEN 1 ELSE 0 END) as servicios_sab,
                        COUNT(DISTINCT CASE WHEN DAYOFWEEK(os.fecha_visita) = 7 THEN os.fecha_visita END) as dias_trabajados_sab,
                        COUNT(os.id_ordenes_servicio) as total_general
                    FROM ordenes_servicio os
                    INNER JOIN tecnico t ON os.id_tecnico = t.id_tecnico
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY t.nombre_tecnico ORDER BY total_general DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }

    public function getServiciosPorSemana($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT YEARWEEK(fecha_visita, 1) as id_semana, MIN(fecha_visita) as fecha_inicio_semana,
                        MAX(fecha_visita) as fecha_fin_semana, COUNT(*) as total 
                    FROM ordenes_servicio 
                    WHERE fecha_visita BETWEEN :inicio AND :fin 
                    GROUP BY YEARWEEK(fecha_visita, 1) ORDER BY fecha_inicio_semana ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }

    public function getPuntosConFallidos($fecha_inicio, $fecha_fin, $minFallidos = 2) 
    {
        try {
            $sql = "SELECT p.nombre_punto, d.nombre_delegacion, COUNT(os.id_ordenes_servicio) as total_fallidos
                    FROM ordenes_servicio os
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    INNER JOIN estado_maquina em ON os.id_estado_maquina = em.id_estado
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin AND em.nombre_estado IN ('Fallido', 'No Operativo', 'Fuera de Servicio')
                    GROUP BY p.id_punto, p.nombre_punto, d.nombre_delegacion HAVING total_fallidos >= :min
                    ORDER BY total_fallidos DESC LIMIT 15";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->bindParam(':min', $minFallidos, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }

    public function getCalificacionesServicio($fecha_inicio, $fecha_fin) 
    {
         try {
            $sql = "SELECT cs.nombre_calificacion as calificacion, COUNT(os.id_ordenes_servicio) as total
                    FROM ordenes_servicio os
                    INNER JOIN calificacion_servicio cs ON os.id_calificacion = cs.id_calificacion
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY cs.nombre_calificacion, cs.id_calificacion ORDER BY cs.id_calificacion DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }

    public function getKpisPorDelegacion($fecha_inicio, $fecha_fin) 
    {
        try {
            // AGREGAMOS LA LÍNEA DE 'dias_efectivos'
            $sql = "SELECT d.nombre_delegacion, 
                            COUNT(os.id_ordenes_servicio) as total_servicios,
                            COUNT(DISTINCT os.id_tecnico) as total_tecnicos,
                            SUM(CASE WHEN os.tiene_novedad = 1 THEN 1 ELSE 0 END) as total_novedades,
                            COUNT(DISTINCT CONCAT(os.id_tecnico, '_', DATE(os.fecha_visita))) as dias_efectivos
                    FROM ordenes_servicio os
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY d.nombre_delegacion ORDER BY total_servicios DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }
    
    // Función auxiliar que podría necesitar el controlador antiguo si lo usaba
    public function getAllTiposMaquina() {
         try {
            $sql = "SELECT nombre_tipo_maquina FROM tipo_maquina ORDER BY nombre_tipo_maquina ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }
}