<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class serviciosPdfModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function listarServiciosParaPdf()
    {
        // Consulta optimizada para traer solo lo esencial para el listado
        $sql = "SELECT 
                    o.id_ordenes_servicio,
                    o.numero_remision,
                    o.fecha_visita,
                    t.nombre_tecnico,
                    COALESCE(c_directo.nombre_cliente, c_maq.nombre_cliente, 'SIN CLIENTE') as nombre_cliente,
                    COALESCE(p_directo.nombre_punto, p_maq.nombre_punto, 'SIN PUNTO') as nombre_punto
                FROM ordenes_servicio o
                LEFT JOIN tecnico t ON o.id_tecnico = t.id_tecnico
                
                -- Relaciones para sacar el cliente y punto (directo o por máquina)
                LEFT JOIN cliente c_directo ON o.id_cliente = c_directo.id_cliente
                LEFT JOIN punto p_directo ON o.id_punto = p_directo.id_punto
                
                LEFT JOIN maquina m ON o.id_maquina = m.id_maquina
                LEFT JOIN punto p_maq ON m.id_punto = p_maq.id_punto
                LEFT JOIN cliente c_maq ON p_maq.id_cliente = c_maq.id_cliente
                
                ORDER BY o.fecha_visita DESC, o.id_ordenes_servicio DESC
                LIMIT 1000"; // Límite por precaución para no saturar si hay muchos registros

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error SQL en serviciosPdfModelo: " . $e->getMessage());
            return [];
        }
    }

    // ==========================================
    // FUNCIONES PARA GENERACIÓN DE PDF
    // ==========================================

    public function obtenerDatosCompletosOrden($idOrden)
    {
        $sql = "SELECT 
                    o.id_ordenes_servicio, o.numero_remision, o.fecha_visita,
                    o.hora_entrada, o.hora_salida, o.tiempo_servicio,
                    o.actividades_realizadas as observaciones, o.tiene_novedad, o.detalle_novedad,
                    
                    t.nombre_tecnico,
                    
                    COALESCE(c_directo.nombre_cliente, c_maq.nombre_cliente, 'SIN CLIENTE') as nombre_cliente,
                    COALESCE(p_directo.nombre_punto, p_maq.nombre_punto, 'SIN PUNTO') as nombre_punto,
                    COALESCE(d_directo.nombre_delegacion, d_maq.nombre_delegacion, 'SIN ASIGNAR') as delegacion,
                    
                    m.device_id,
                    tm.nombre_tipo_maquina,
                    
                    tman.nombre_completo as tipo_servicio,
                    em.nombre_estado as estado_maquina,
                    cal.nombre_calificacion,

                    -- ==========================================
                    -- NUEVOS CAMPOS COMPLEMENTARIOS
                    -- ==========================================
                    osc.numero_maquina,
                    osc.serial_maquina,
                    osc.serial_router,
                    osc.serial_ups,
                    osc.pendientes,
                    osc.administrador_punto,
                    osc.celular_encargado,
                    em_ini.nombre_estado as estado_inicial,
                    
                    CASE 
                        WHEN o.id_modalidad = 1 THEN 'URBANO'
                        WHEN o.id_modalidad = 2 THEN 'INTERURBANO'
                        ELSE 'NO DEFINIDO'
                    END as modalidad
                    
                FROM ordenes_servicio o
                LEFT JOIN tecnico t ON o.id_tecnico = t.id_tecnico
                LEFT JOIN maquina m ON o.id_maquina = m.id_maquina
                LEFT JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                LEFT JOIN tipo_mantenimiento tman ON o.id_tipo_mantenimiento = tman.id_tipo_mantenimiento
                LEFT JOIN estado_maquina em ON o.id_estado_maquina = em.id_estado
                LEFT JOIN calificacion_servicio cal ON o.id_calificacion = cal.id_calificacion
                
                -- JOIN PARA LOS DATOS NUEVOS
                LEFT JOIN ordenes_servicio_complemento osc ON o.id_ordenes_servicio = osc.id_orden_servicio
                LEFT JOIN estado_maquina em_ini ON osc.id_estado_inicial = em_ini.id_estado
                
                -- Cruce para Punto y Cliente (si viene de máquina)
                LEFT JOIN punto p_maq ON m.id_punto = p_maq.id_punto
                LEFT JOIN cliente c_maq ON p_maq.id_cliente = c_maq.id_cliente
                LEFT JOIN delegacion d_maq ON p_maq.id_delegacion = d_maq.id_delegacion
                
                -- Cruce para Punto y Cliente (si es directo en la orden)
                LEFT JOIN punto p_directo ON o.id_punto = p_directo.id_punto
                LEFT JOIN cliente c_directo ON o.id_cliente = c_directo.id_cliente
                LEFT JOIN delegacion d_directo ON p_directo.id_delegacion = d_directo.id_delegacion
                
                WHERE o.id_ordenes_servicio = ?";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$idOrden]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo datos para PDF: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerEvidenciasOrden($idOrden)
    {
        $sql = "SELECT tipo_evidencia, ruta_archivo, fecha_subida 
                FROM evidencia_servicio 
                WHERE id_orden_servicio = ?
                ORDER BY 
                    FIELD(tipo_evidencia, 'antes', 'componentes', 'despues'), 
                    fecha_subida ASC";
                    
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$idOrden]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo evidencias para PDF: " . $e->getMessage());
            return [];
        }
    }
}