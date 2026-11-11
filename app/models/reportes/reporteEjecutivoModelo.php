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
            // AGREGAMOS: , COUNT(DISTINCT id_tecnico) as num_tecnicos
            $sql = "SELECT fecha_visita, 
                        COUNT(*) as total, 
                        COUNT(DISTINCT id_tecnico) as num_tecnicos
                FROM ordenes_servicio 
                WHERE fecha_visita BETWEEN :inicio AND :fin 
                GROUP BY fecha_visita ORDER BY fecha_visita ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
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
        } catch (PDOException $e) {
            return [];
        }
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
        } catch (PDOException $e) {
            return [];
        }
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
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getDistribucionNovedades($fecha_inicio, $fecha_fin)
    {
        try {
            // Usamos LEFT JOIN con la tabla pivote y contamos los distintos para evitar duplicados
            $sql = "SELECT 
                        COUNT(DISTINCT osn.id_orden_servicio) as con_novedad,
                        (COUNT(DISTINCT os.id_ordenes_servicio) - COUNT(DISTINCT osn.id_orden_servicio)) as sin_novedad
                    FROM ordenes_servicio os
                    LEFT JOIN orden_servicio_novedad osn ON os.id_ordenes_servicio = osn.id_orden_servicio
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
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
        } catch (PDOException $e) {
            return [];
        }
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
        } catch (PDOException $e) {
            return [];
        }
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
        } catch (PDOException $e) {
            return [];
        }
    }

    // Matriz: Tipo de Máquina por Delegación (CORREGIDA CON TUS TABLAS)
    public function getDatosMatrizTipoMaquina($fecha_inicio, $fecha_fin)
    {
        try {
            // Agregamos "AND m.estado = 1" para filtrar solo máquinas activas
            $sql = "SELECT d.nombre_delegacion, tm.nombre_tipo_maquina, COUNT(os.id_ordenes_servicio) as total
                    FROM ordenes_servicio os
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    INNER JOIN maquina m ON m.id_punto = p.id_punto 
                    INNER JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina 
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    AND m.estado = 1 
                    GROUP BY d.nombre_delegacion, tm.nombre_tipo_maquina";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Repuestos: Top usados por delegación (CORREGIDA CON TUS TABLAS)
    // Repuestos: Top usados por delegación (SOLO INEES)
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
                    
                    -- AGREGAMOS ESTA LÍNEA PARA FILTRAR SOLO INEES:
                    AND (osr.origen LIKE '%INEES%' OR osr.origen = 'INEES')

                    GROUP BY d.nombre_delegacion, r.nombre_repuesto
                    ORDER BY d.nombre_delegacion ASC, cantidad_usada DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getAllTiposMantenimiento()
    {
        try {
            $sql = "SELECT id_tipo_mantenimiento, nombre_completo FROM tipo_mantenimiento ORDER BY nombre_completo ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
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
        } catch (PDOException $e) {
            return [];
        }
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
        } catch (PDOException $e) {
            return [];
        }
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
        } catch (PDOException $e) {
            return [];
        }
    }

    // 2. CORREGIDA: Puntos Críticos (Más robusta)
    // Reemplaza tu función getPuntosConFallidos por esta:
    public function getPuntosMasVisitados($fecha_inicio, $fecha_fin, $min_visitas = 2)
    {
        try {
            $sql = "SELECT 
                    d.nombre_delegacion, 
                    p.nombre_punto, 
                    tm.nombre_tipo_maquina, 
                    COUNT(os.id_ordenes_servicio) as total_visitas
                FROM ordenes_servicio os
                INNER JOIN punto p ON os.id_punto = p.id_punto
                INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                LEFT JOIN maquina m ON p.id_punto = m.id_punto AND m.estado = 1
                LEFT JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                
                GROUP BY d.nombre_delegacion, p.id_punto, p.nombre_punto, tm.nombre_tipo_maquina
                
                -- AQUÍ ESTÁ EL FILTRO DINÁMICO
                HAVING total_visitas >= :min_visitas
                
                ORDER BY d.nombre_delegacion ASC, total_visitas DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);

            // Vinculamos el número que viene del input
            $stmt->bindParam(':min_visitas', $min_visitas, PDO::PARAM_INT);

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getPuntosConMasFallidos($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT 
                        p.nombre_punto, 
                        d.nombre_delegacion, 
                        COUNT(os.id_ordenes_servicio) as total_fallidos
                    FROM ordenes_servicio os
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    -- CAMBIO CLAVE: Unimos con tipo_mantenimiento
                    INNER JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                    
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin 
                    
                    -- FILTRO: Buscamos que el tipo de mantenimiento sea 'Fallido' o 'Visita Fallida'
                    -- Ajusta el texto '%Fallid%' si en tu BD se llama diferente (ej: 'Servicio Fallido')
                    AND tm.nombre_completo LIKE '%Fallid%' 
                    
                    GROUP BY p.id_punto, p.nombre_punto, d.nombre_delegacion 
                    
                    -- REGLA DE ORO: Más de 2 servicios (> 2)
                    HAVING total_fallidos >= 2
                    
                    ORDER BY d.nombre_delegacion ASC, total_fallidos DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Cuenta el total de servicios fallidos (tipo de mantenimiento "Fallido")
     * dentro del rango de fechas seleccionado.
     */
    public function getTotalServiciosFallidos($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT COUNT(DISTINCT os.id_ordenes_servicio) as total
                    FROM ordenes_servicio os
                    INNER JOIN tipo_mantenimiento tm
                        ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    AND UPPER(tm.nombre_completo) LIKE '%FALLID%'";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int) ($resultado['total'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function getCalificacionesServicio($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT d.nombre_delegacion, 
                        cs.nombre_calificacion, 
                        COUNT(os.id_ordenes_servicio) as total
                FROM ordenes_servicio os
                INNER JOIN calificacion_servicio cs ON os.id_calificacion = cs.id_calificacion
                INNER JOIN punto p ON os.id_punto = p.id_punto
                INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                
                -- Agrupamos por Delegación y luego por Calificación
                GROUP BY d.nombre_delegacion, cs.nombre_calificacion, cs.id_calificacion
                
                -- Ordenamos por nombre de delegación y luego por relevancia de la calificación
                ORDER BY d.nombre_delegacion ASC, cs.id_calificacion DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getKpisPorDelegacion($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT d.nombre_delegacion, 
                            -- IMPORTANTE: Agregamos DISTINCT aquí para no inflar los servicios si hay varias novedades
                            COUNT(DISTINCT os.id_ordenes_servicio) as total_servicios,
                            COUNT(DISTINCT os.id_tecnico) as total_tecnicos,
                            -- Contamos las novedades desde la tabla pivote
                            COUNT(DISTINCT osn.id_orden_servicio) as total_novedades,
                            COUNT(DISTINCT CONCAT(os.id_tecnico, '_', DATE(os.fecha_visita))) as dias_efectivos
                    FROM ordenes_servicio os
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    -- Agregamos el LEFT JOIN para las novedades
                    LEFT JOIN orden_servicio_novedad osn ON os.id_ordenes_servicio = osn.id_orden_servicio
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY d.nombre_delegacion ORDER BY total_servicios DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Función auxiliar que podría necesitar el controlador antiguo si lo usaba
    public function getAllTiposMaquina()
    {
        try {
            $sql = "SELECT nombre_tipo_maquina FROM tipo_maquina ORDER BY nombre_tipo_maquina ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }


    // Matriz: Puntos Únicos por Tipo de Máquina y Delegación
    public function getDatosMatrizPuntosPorTipo($fecha_inicio, $fecha_fin)
    {
        try {
            // COUNT(DISTINCT os.id_punto) es la clave aquí
            $sql = "SELECT d.nombre_delegacion, tm.nombre_tipo_maquina, COUNT(DISTINCT os.id_punto) as total
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
        } catch (PDOException $e) {
            return [];
        }
    }

    // Obtener distribución de estados finales (Operativo, Fuera de Servicio, etc.)
    public function getDistribucionEstados($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT em.nombre_estado, COUNT(os.id_ordenes_servicio) as total
                    FROM ordenes_servicio os
                    INNER JOIN estado_maquina em ON os.id_estado_maquina = em.id_estado
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY em.nombre_estado
                    ORDER BY total DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // 1. NUEVA: Distribución de Repuestos por Origen (INEES vs PROSEGUR)
    public function getOrigenRepuestos($fecha_inicio, $fecha_fin)
    {
        try {
            // Asumiendo que la columna se llama 'origen' en la tabla 'orden_servicio_repuesto'
            // Ojo: Ajusta 'INEES' y 'PROSEGUR' si en tu BD están escritos diferente (ej: 'Prosegur', 'Inees')
            $sql = "SELECT origen, SUM(cantidad) as total
                    FROM orden_servicio_repuesto osr
                    INNER JOIN ordenes_servicio os ON osr.id_orden_servicio = os.id_ordenes_servicio
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY origen";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }


    // Obtener desglose: Cuántos servicios de cada tipo hizo cada técnico
    public function getDesgloseMantenimientoPorTecnico($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT t.nombre_tecnico, 
                           tm.nombre_completo as tipo, 
                           COUNT(os.id_ordenes_servicio) as cantidad
                    FROM ordenes_servicio os
                    INNER JOIN tecnico t ON os.id_tecnico = t.id_tecnico
                    INNER JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY t.nombre_tecnico, tm.nombre_completo";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getListadoCostosOperativos($fecha_inicio, $fecha_fin)
    {
        try {
            $mes_inicio = date('Y-m-01', strtotime($fecha_inicio));
            $mes_fin = date('Y-m-01', strtotime($fecha_fin));

            $sql = "SELECT 
                    COALESCE(t.nombre_tecnico, u.nombre) as beneficiario,
                    COALESCE(u.cargo, 'Técnico Motorizado') as rol,
                    c.salario, c.horas_extra, c.bono_meta, 
                    c.gasolina, c.auxilio_comunicacion, c.auxilio_rodamiento,
                    (c.salario + c.horas_extra + c.bono_meta + c.gasolina + c.auxilio_comunicacion + c.auxilio_rodamiento) as subtotal
                FROM costos_operativos c
                LEFT JOIN tecnico t ON c.id_tecnico = t.id_tecnico
                LEFT JOIN usuarios u ON c.id_usuario = u.usuario_id
                WHERE c.mes_reporte BETWEEN :mes_inicio AND :mes_fin
                ORDER BY subtotal DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':mes_inicio', $mes_inicio);
            $stmt->bindParam(':mes_fin', $mes_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // NUEVO MÉTODO: Traer gastos generales (Software, Arriendos, etc.)
    public function getListadoGastosGenerales($fecha_inicio, $fecha_fin)
    {
        try {
            // Ajustamos las fechas al primer día del mes para coincidir con como guardamos (YYYY-MM-01)
            $mes_inicio = date('Y-m-01', strtotime($fecha_inicio));
            $mes_fin = date('Y-m-01', strtotime($fecha_fin));

            $sql = "SELECT categoria, concepto, valor, mes_reporte
                    FROM gastos_administrativos 
                    WHERE mes_reporte BETWEEN :mes_inicio AND :mes_fin
                    ORDER BY valor DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':mes_inicio', $mes_inicio);
            $stmt->bindParam(':mes_fin', $mes_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Devuelve las novedades agrupadas por delegación y tipo.
     * Si $soloFallidos = true, solo cuenta novedades de servicios fallidos.
     * Si $soloFallidos = false, excluye las novedades de servicios fallidos.
     */
    public function getNovedadesPorDelegacion($fecha_inicio, $fecha_fin, $soloFallidos = false)
    {
        try {
            $filtroFallido = $soloFallidos
                ? "AND UPPER(tm.nombre_completo) LIKE '%FALLID%'"
                : "AND UPPER(tm.nombre_completo) NOT LIKE '%FALLID%'";

            $sql = "SELECT
                        d.nombre_delegacion,
                        tn.nombre_novedad,
                        COUNT(DISTINCT os.id_ordenes_servicio) as total
                    FROM ordenes_servicio os
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    INNER JOIN orden_servicio_novedad osn ON os.id_ordenes_servicio = osn.id_orden_servicio
                    INNER JOIN tipo_novedad tn ON osn.id_tipo_novedad = tn.id_tipo_novedad
                    INNER JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    $filtroFallido
                    GROUP BY d.nombre_delegacion, tn.id_tipo_novedad, tn.nombre_novedad
                    ORDER BY d.nombre_delegacion ASC, total DESC, tn.nombre_novedad ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Devuelve, para el período, dos conjuntos de datos sobre novedades por cliente:
     *  - 'totales':   servicios distintos con novedad por delegación + cliente.
     *  - 'novedades': servicios distintos por delegación + cliente + tipo de novedad.
     * Ambos se usan para armar el top de clientes y sus novedades principales.
     */
    public function getClientesConMasNovedades($fecha_inicio, $fecha_fin)
    {
        $resultado = ['totales' => [], 'novedades' => []];

        try {
            $sqlTotales = "SELECT
                        IFNULL(d.nombre_delegacion, 'SIN DELEGACIÓN') AS nombre_delegacion,
                        c.id_cliente,
                        c.nombre_cliente,
                        COUNT(DISTINCT os.id_ordenes_servicio) AS total
                    FROM ordenes_servicio os
                    INNER JOIN cliente c ON os.id_cliente = c.id_cliente
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    INNER JOIN orden_servicio_novedad osn ON os.id_ordenes_servicio = osn.id_orden_servicio
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY d.nombre_delegacion, c.id_cliente, c.nombre_cliente
                    ORDER BY nombre_delegacion ASC, total DESC";

            $stmtTotales = $this->conn->prepare($sqlTotales);
            $stmtTotales->bindParam(':inicio', $fecha_inicio);
            $stmtTotales->bindParam(':fin', $fecha_fin);
            $stmtTotales->execute();
            $resultado['totales'] = $stmtTotales->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $sqlNovedades = "SELECT
                        IFNULL(d.nombre_delegacion, 'SIN DELEGACIÓN') AS nombre_delegacion,
                        c.id_cliente,
                        tn.nombre_novedad,
                        COUNT(DISTINCT os.id_ordenes_servicio) AS total
                    FROM ordenes_servicio os
                    INNER JOIN cliente c ON os.id_cliente = c.id_cliente
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    INNER JOIN orden_servicio_novedad osn ON os.id_ordenes_servicio = osn.id_orden_servicio
                    INNER JOIN tipo_novedad tn ON osn.id_tipo_novedad = tn.id_tipo_novedad
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY d.nombre_delegacion, c.id_cliente, tn.id_tipo_novedad, tn.nombre_novedad
                    ORDER BY total DESC, tn.nombre_novedad ASC";

            $stmtNovedades = $this->conn->prepare($sqlNovedades);
            $stmtNovedades->bindParam(':inicio', $fecha_inicio);
            $stmtNovedades->bindParam(':fin', $fecha_fin);
            $stmtNovedades->execute();
            $resultado['novedades'] = $stmtNovedades->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Error en getClientesConMasNovedades: " . $e->getMessage());
        }

        return $resultado;
    }

    /**
     * Calcula la meta de servicios esperados según la regla operativa:
     * día normal 6, sábado 3; con Nueva Arquitectura 3 y 2 respectivamente.
     * Solo cuenta técnicos con servicios registrados ese día y excluye
     * domingos y festivos.
     */
    public function getMetaServiciosEsperados($fecha_inicio, $fecha_fin)
    {
        $resultado = [
            'esperados' => 0,
            'dias_laborables' => 0,
            'tecnicos_dias' => 0,
            'esperados_normal' => 0,
            'esperados_nueva_arquitectura' => 0,
        ];

        try {
            $sql = "SELECT
                        os.fecha_visita,
                        os.id_tecnico,
                        MAX(
                            CASE
                                WHEN os.id_tipo_mantenimiento = 6
                                  OR UPPER(tm.nombre_completo) LIKE '%NUEVA ARQUITECTURA%'
                                THEN 1 ELSE 0
                            END
                        ) as es_nueva_arquitectura
                    FROM ordenes_servicio os
                    LEFT JOIN tipo_mantenimiento tm
                        ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY os.fecha_visita, os.id_tecnico";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $sqlFestivos = "SELECT fecha FROM dias_festivos WHERE fecha BETWEEN :inicio AND :fin";
            $stmtFestivos = $this->conn->prepare($sqlFestivos);
            $stmtFestivos->bindParam(':inicio', $fecha_inicio);
            $stmtFestivos->bindParam(':fin', $fecha_fin);
            $stmtFestivos->execute();
            $festivos = array_fill_keys($stmtFestivos->fetchAll(PDO::FETCH_COLUMN), true);

            $diasConMeta = [];

            foreach ($registros as $registro) {
                $fecha = date('Y-m-d', strtotime($registro['fecha_visita']));
                $diaSemana = (int) date('N', strtotime($fecha));

                if ($diaSemana === 7 || isset($festivos[$fecha])) {
                    continue;
                }

                $esSabado = ($diaSemana === 6);
                $esNuevaArquitectura = !empty($registro['es_nueva_arquitectura']);
                $meta = 0;

                if ($esNuevaArquitectura) {
                    $meta = $esSabado ? 2 : 3;
                    $resultado['esperados_nueva_arquitectura'] += $meta;
                } else {
                    $meta = $esSabado ? 3 : 6;
                    $resultado['esperados_normal'] += $meta;
                }

                $resultado['esperados'] += $meta;
                $resultado['tecnicos_dias']++;
                $diasConMeta[$fecha] = true;
            }

            $resultado['dias_laborables'] = count($diasConMeta);
        } catch (PDOException $e) {
            // Mantiene los valores en cero.
        }

        return $resultado;
    }

    /**
 * Obtiene el desglose de ingresos por tipo de mantenimiento
 * Retorna un array con los valores de cada tipo + repuestos
 */
public function getDesgloseIngresosPorTipo($fecha_inicio, $fecha_fin)
{
    try {
        if (empty($fecha_inicio) || empty($fecha_fin)) {
            return [];
        }

        // Query para obtener ingresos agrupados por tipo de mantenimiento
        $sql = "SELECT 
                    tm.id_tipo_mantenimiento,
                    tm.nombre_completo as tipo,
                    COALESCE(SUM(os.valor_servicio), 0) as total_ingresos,
                    COUNT(os.id_ordenes_servicio) as cantidad_servicios
                FROM ordenes_servicio os
                INNER JOIN tipo_mantenimiento tm 
                    ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                AND os.estado = 1
                GROUP BY tm.id_tipo_mantenimiento, tm.nombre_completo
                ORDER BY tm.id_tipo_mantenimiento";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':inicio', $fecha_inicio);
        $stmt->bindParam(':fin', $fecha_fin);
        $stmt->execute();
        
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Crear array asociativo para fácil acceso
        $desglose = [];
        foreach ($resultados as $row) {
            $desglose[$row['id_tipo_mantenimiento']] = [
                'tipo' => $row['tipo'],
                'total' => (float) $row['total_ingresos'],
                'cantidad' => (int) $row['cantidad_servicios']
            ];
        }
        
        return $desglose;
        
    } catch (PDOException $e) {
        error_log("Error en getDesgloseIngresosPorTipo: " . $e->getMessage());
        return [];
    }
}

    /**
     * Obtiene el total de ingresos SOLO por repuestos (sin duplicar servicios)
     */
    public function getTotalIngresosRepuestos($fecha_inicio, $fecha_fin)
    {
        try {
            if (empty($fecha_inicio) || empty($fecha_fin)) {
                return 0;
            }

            $sql = "SELECT COALESCE(SUM(osr.cantidad * osr.valor_unitario), 0) as total
                    FROM orden_servicio_repuesto osr
                    INNER JOIN ordenes_servicio os
                        ON osr.id_orden_servicio = os.id_ordenes_servicio
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    AND os.estado = 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (float) $result['total'];

        } catch (PDOException $e) {
            error_log("Error en getTotalIngresosRepuestos: " . $e->getMessage());
            return 0;
        }
    }

    // ==========================================
    // SECCIÓN 4: DETALLE PARA HOVER / EXCEL
    // ==========================================

    /**
     * Puntos más visitados para la vista web. Agrupa por punto (no por tipo de
     * máquina) para no duplicar el total de visitas.
     */
    public function getPuntosMasVisitadosWeb($fecha_inicio, $fecha_fin, $minVisitas = 2, $limite = 20, $filtros = [])
    {
        try {
            $params = [
                ':inicio' => $fecha_inicio,
                ':fin'    => $fecha_fin,
            ];

            $filtroSql = '';

            if (!empty($filtros['id_punto'])) {
                $filtroSql .= " AND p.id_punto = :id_punto";
                $params[':id_punto'] = (int) $filtros['id_punto'];
            }

            if (!empty($filtros['delegacion'])) {
                $filtroSql .= " AND d.nombre_delegacion = :delegacion";
                $params[':delegacion'] = $filtros['delegacion'];
            }

            $limiteSql = '';
            if ($limite !== null && (int) $limite > 0) {
                $limiteSql = " LIMIT :limite";
            }

            $sql = "SELECT
                        p.id_punto,
                        p.nombre_punto,
                        d.nombre_delegacion,
                        (
                            SELECT GROUP_CONCAT(DISTINCT tm2.nombre_tipo_maquina SEPARATOR ', ')
                            FROM maquina m2
                            INNER JOIN tipo_maquina tm2 ON m2.id_tipo_maquina = tm2.id_tipo_maquina
                            WHERE m2.id_punto = p.id_punto AND m2.estado = 1
                        ) AS tipos,
                        COUNT(DISTINCT os.id_ordenes_servicio) AS total_visitas
                    FROM ordenes_servicio os
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    $filtroSql
                    GROUP BY p.id_punto, p.nombre_punto, d.nombre_delegacion
                    HAVING total_visitas >= :min_visitas
                    ORDER BY total_visitas DESC
                    $limiteSql";

            $stmt = $this->conn->prepare($sql);

            foreach ($params as $clave => $valor) {
                $stmt->bindValue($clave, $valor);
            }

            $stmt->bindValue(':min_visitas', (int) $minVisitas, PDO::PARAM_INT);

            if ($limiteSql !== '') {
                $stmt->bindValue(':limite', (int) $limite, PDO::PARAM_INT);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getPuntosMasVisitadosWeb: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Detalle de servicios (una fila por orden) usado para el hover de puntos
     * y para la hoja de detalle del Excel.
     */
    public function getDetalleServiciosPorPunto($fecha_inicio, $fecha_fin, $filtros = [])
    {
        try {
            $params = [
                ':inicio' => $fecha_inicio,
                ':fin'    => $fecha_fin,
            ];

            $filtroSql = '';

            if (!empty($filtros['id_punto'])) {
                $filtroSql .= " AND p.id_punto = :id_punto";
                $params[':id_punto'] = (int) $filtros['id_punto'];
            }

            if (!empty($filtros['delegacion'])) {
                $filtroSql .= " AND d.nombre_delegacion = :delegacion";
                $params[':delegacion'] = $filtros['delegacion'];
            }

            $sql = "SELECT
                        os.id_ordenes_servicio,
                        os.fecha_visita,
                        p.id_punto,
                        p.nombre_punto,
                        d.nombre_delegacion,
                        COALESCE(c.nombre_cliente, 'Sin Cliente') AS nombre_cliente,
                        COALESCE(t.nombre_tecnico, 'SIN TÉCNICO') AS nombre_tecnico,
                        COALESCE(tm.nombre_completo, 'SIN TIPO') AS tipo_servicio,
                        GROUP_CONCAT(DISTINCT tn.nombre_novedad SEPARATOR ', ') AS novedades
                    FROM ordenes_servicio os
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    LEFT JOIN cliente c ON os.id_cliente = c.id_cliente
                    LEFT JOIN tecnico t ON os.id_tecnico = t.id_tecnico
                    LEFT JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                    LEFT JOIN orden_servicio_novedad osn ON os.id_ordenes_servicio = osn.id_orden_servicio
                    LEFT JOIN tipo_novedad tn ON osn.id_tipo_novedad = tn.id_tipo_novedad
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    $filtroSql
                    GROUP BY os.id_ordenes_servicio, os.fecha_visita, p.id_punto, p.nombre_punto,
                             d.nombre_delegacion, c.nombre_cliente, t.nombre_tecnico, tm.nombre_completo
                    ORDER BY os.fecha_visita DESC, os.id_ordenes_servicio DESC";

            $stmt = $this->conn->prepare($sql);

            foreach ($params as $clave => $valor) {
                $stmt->bindValue($clave, $valor);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getDetalleServiciosPorPunto: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Detalle de servicios con novedad (una fila por servicio + novedad).
     *
     * @param bool|null $soloFallidos true = solo fallidos, false = sin fallidos, null = todos.
     */
    public function getDetalleNovedades($fecha_inicio, $fecha_fin, $soloFallidos = null, $filtros = [])
    {
        try {
            $params = [
                ':inicio' => $fecha_inicio,
                ':fin'    => $fecha_fin,
            ];

            $filtroSql = '';

            if ($soloFallidos === true) {
                $filtroSql .= " AND UPPER(tm.nombre_completo) LIKE '%FALLID%'";
            } elseif ($soloFallidos === false) {
                $filtroSql .= " AND UPPER(tm.nombre_completo) NOT LIKE '%FALLID%'";
            }

            if (!empty($filtros['id_cliente'])) {
                $filtroSql .= " AND c.id_cliente = :id_cliente";
                $params[':id_cliente'] = (int) $filtros['id_cliente'];
            }

            if (!empty($filtros['id_punto'])) {
                $filtroSql .= " AND p.id_punto = :id_punto";
                $params[':id_punto'] = (int) $filtros['id_punto'];
            }

            if (!empty($filtros['delegacion'])) {
                $filtroSql .= " AND IFNULL(d.nombre_delegacion, 'SIN DELEGACIÓN') = :delegacion";
                $params[':delegacion'] = $filtros['delegacion'];
            }

            if (!empty($filtros['id_tipo_novedad'])) {
                $filtroSql .= " AND tn.id_tipo_novedad = :id_tipo_novedad";
                $params[':id_tipo_novedad'] = (int) $filtros['id_tipo_novedad'];
            }

            if (!empty($filtros['novedad'])) {
                $filtroSql .= " AND tn.nombre_novedad = :novedad";
                $params[':novedad'] = $filtros['novedad'];
            }

            $sql = "SELECT
                        os.id_ordenes_servicio,
                        os.fecha_visita,
                        IFNULL(d.nombre_delegacion, 'SIN DELEGACIÓN') AS nombre_delegacion,
                        c.id_cliente,
                        COALESCE(c.nombre_cliente, 'Sin Cliente') AS nombre_cliente,
                        p.id_punto,
                        p.nombre_punto,
                        COALESCE(t.nombre_tecnico, 'SIN TÉCNICO') AS nombre_tecnico,
                        COALESCE(tm.nombre_completo, 'SIN TIPO') AS tipo_servicio,
                        tn.id_tipo_novedad,
                        tn.nombre_novedad
                    FROM ordenes_servicio os
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    LEFT JOIN cliente c ON os.id_cliente = c.id_cliente
                    INNER JOIN orden_servicio_novedad osn ON os.id_ordenes_servicio = osn.id_orden_servicio
                    INNER JOIN tipo_novedad tn ON osn.id_tipo_novedad = tn.id_tipo_novedad
                    INNER JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                    LEFT JOIN tecnico t ON os.id_tecnico = t.id_tecnico
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    $filtroSql
                    ORDER BY os.fecha_visita DESC, c.nombre_cliente ASC";

            $stmt = $this->conn->prepare($sql);

            foreach ($params as $clave => $valor) {
                $stmt->bindValue($clave, $valor);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getDetalleNovedades: " . $e->getMessage());
            return [];
        }
    }

}
