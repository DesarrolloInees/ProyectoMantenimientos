<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ordenDetalleModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // ==========================================
    // 1. CONSULTA INTELIGENTE (Prioriza lo guardado en la orden)
    // ==========================================
    // ==========================================
    // 1. CONSULTA INTELIGENTE (CORREGIDA: Sin Municipios)
    // ==========================================
    public function obtenerServiciosPorFecha($fecha)
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
                    o.id_maquina,
                    m.device_id,
                    tm.nombre_tipo_maquina,
                    
                    -- CLIENTE (Prioriza el de la orden, si no, el de la máquina)
                    COALESCE(o.id_cliente, c_maq.id_cliente) as id_cliente,
                    COALESCE(c_directo.nombre_cliente, c_maq.nombre_cliente) as nombre_cliente,
                    
                    -- PUNTO (Prioriza el de la orden)
                    COALESCE(o.id_punto, p_maq.id_punto) as id_punto,
                    COALESCE(p_directo.nombre_punto, p_maq.nombre_punto) as nombre_punto,
                    
                    -- DELEGACIÓN (Ahora viene de la tabla delegacion vinculada al punto)
                    COALESCE(d_directo.nombre_delegacion, d_maq.nombre_delegacion) as delegacion,

                    -- ZONA / MODALIDAD (Ahora viene directo de la orden)
                    CASE 
                        WHEN o.id_modalidad = 1 THEN 'URBANO'
                        WHEN o.id_modalidad = 2 THEN 'INTERURBANO'
                        ELSE 'NO DEFINIDO'
                    END as tipo_zona,

                    -- TÉCNICO Y DEMÁS
                    o.id_tecnico, t.nombre_tecnico,
                    o.id_tipo_mantenimiento as id_manto, tman.nombre_completo as tipo_servicio,
                    o.id_estado_maquina as id_estado, em.nombre_estado as estado_maquina,
                    o.id_calificacion as id_calif, cal.nombre_calificacion,

                    -- REPUESTOS
                    IFNULL(
                        (SELECT GROUP_CONCAT(CONCAT(r.nombre_repuesto, ' (', osr.origen, ')') SEPARATOR ', ')
                            FROM orden_servicio_repuesto osr
                            JOIN repuesto r ON osr.id_repuesto = r.id_repuesto
                            WHERE osr.id_orden_servicio = o.id_ordenes_servicio)
                    , 'Sin Repuestos') as repuestos_usados

                FROM ordenes_servicio o
                
                -- Joins básicos
                LEFT JOIN maquina m ON o.id_maquina = m.id_maquina
                LEFT JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                LEFT JOIN tecnico t ON o.id_tecnico = t.id_tecnico
                LEFT JOIN tipo_mantenimiento tman ON o.id_tipo_mantenimiento = tman.id_tipo_mantenimiento
                LEFT JOIN estado_maquina em ON o.id_estado_maquina = em.id_estado
                LEFT JOIN calificacion_servicio cal ON o.id_calificacion = cal.id_calificacion
                
                -- RUTA 1: DATOS VIA MÁQUINA (Para respaldar si falta en la orden)
                LEFT JOIN punto p_maq ON m.id_punto = p_maq.id_punto
                LEFT JOIN cliente c_maq ON p_maq.id_cliente = c_maq.id_cliente
                LEFT JOIN delegacion d_maq ON p_maq.id_delegacion = d_maq.id_delegacion

                -- RUTA 2: DATOS DIRECTOS DE LA ORDEN (Lo ideal)
                LEFT JOIN cliente c_directo ON o.id_cliente = c_directo.id_cliente
                LEFT JOIN punto p_directo ON o.id_punto = p_directo.id_punto
                LEFT JOIN delegacion d_directo ON p_directo.id_delegacion = d_directo.id_delegacion
                
                WHERE o.fecha_visita = ?
                ORDER BY o.numero_remision ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$fecha]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ... (Mantén aquí las funciones de obtenerTodosLosClientes, PuntosPorCliente, etc. que te pasé antes) ...
    public function obtenerTodosLosClientes()
    {
        return $this->conn->query("SELECT id_cliente, nombre_cliente FROM cliente WHERE estado = 1 ORDER BY nombre_cliente ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerPuntosPorCliente($id)
    {
        $stmt = $this->conn->prepare("SELECT id_punto, nombre_punto FROM punto WHERE id_cliente = ? ORDER BY nombre_punto ASC");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerMaquinasPorPunto($id)
    {
        $stmt = $this->conn->prepare("SELECT m.id_maquina, m.device_id, tm.nombre_tipo_maquina FROM maquina m JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina WHERE m.id_punto = ? ORDER BY m.device_id ASC");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // ... (Listas estáticas de técnicos, estados, etc) ...
    public function obtenerTodosLosTecnicos()
    {
        return $this->conn->query("SELECT * FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico")->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerTiposMantenimiento()
    {
        return $this->conn->query("SELECT * FROM tipo_mantenimiento")->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerEstados()
    {
        return $this->conn->query("SELECT * FROM estado_maquina")->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerCalificaciones()
    {
        return $this->conn->query("SELECT * FROM calificacion_servicio")->fetchAll(PDO::FETCH_ASSOC);
    }


    // ==========================================
    // 2. ACTUALIZACIÓN (Ahora guardamos Cliente y Punto)
    // ==========================================
    public function actualizarOrdenFull($id, $datos)
    {
        $sql = "UPDATE ordenes_servicio SET 
                    id_cliente = ?,            -- <--- NUEVO
                    id_punto = ?,              -- <--- NUEVO
                    id_maquina = ?,
                    numero_remision = ?, 
                    id_tecnico = ?, 
                    id_tipo_mantenimiento = ?, 
                    id_estado_maquina = ?, 
                    id_calificacion = ?, 
                    hora_entrada = ?, 
                    hora_salida = ?, 
                    tiempo_servicio = ?,
                    valor_servicio = ?,
                    actividades_realizadas = ?,
                    fecha_visita = ?
                WHERE id_ordenes_servicio = ?";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $datos['id_cliente'], // <--- Enviamos el cliente seleccionado
            $datos['id_punto'],   // <--- Enviamos el punto seleccionado
            $datos['id_maquina'],
            $datos['remision'],
            $datos['id_tecnico'],
            $datos['id_manto'],
            $datos['id_estado'],
            $datos['id_calif'],
            $datos['entrada'],
            $datos['salida'],
            $datos['tiempo'],
            $datos['valor'],
            $datos['obs'],
            $datos['fecha_individual'],
            $id
        ]);
    }
}
