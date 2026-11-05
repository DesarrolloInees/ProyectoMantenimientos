<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

class serviciosPdfModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    private function obtenerSelectServicios()
    {
        return "SELECT
                    o.id_ordenes_servicio,
                    o.numero_remision,
                    o.fecha_visita,
                    o.id_tecnico,
                    t.nombre_tecnico,
                    COALESCE(c_directo.id_cliente, c_maq.id_cliente) AS id_cliente,
                    COALESCE(c_directo.nombre_cliente, c_maq.nombre_cliente, 'SIN CLIENTE') AS nombre_cliente,
                    COALESCE(p_directo.id_punto, p_maq.id_punto) AS id_punto,
                    COALESCE(p_directo.nombre_punto, p_maq.nombre_punto, 'SIN PUNTO') AS nombre_punto,
                    COALESCE(d_directo.id_delegacion, d_maq.id_delegacion) AS id_delegacion,
                    COALESCE(d_directo.nombre_delegacion, d_maq.nombre_delegacion, 'SIN ASIGNAR') AS nombre_delegacion,
                    o.id_tipo_mantenimiento,
                    tman.nombre_completo AS tipo_servicio
                FROM ordenes_servicio o
                LEFT JOIN tecnico t ON o.id_tecnico = t.id_tecnico
                LEFT JOIN tipo_mantenimiento tman ON o.id_tipo_mantenimiento = tman.id_tipo_mantenimiento
                
                LEFT JOIN cliente c_directo ON o.id_cliente = c_directo.id_cliente
                LEFT JOIN punto p_directo ON o.id_punto = p_directo.id_punto
                LEFT JOIN delegacion d_directo ON p_directo.id_delegacion = d_directo.id_delegacion
                
                LEFT JOIN maquina m ON o.id_maquina = m.id_maquina
                LEFT JOIN punto p_maq ON m.id_punto = p_maq.id_punto
                LEFT JOIN cliente c_maq ON p_maq.id_cliente = c_maq.id_cliente
                LEFT JOIN delegacion d_maq ON p_maq.id_delegacion = d_maq.id_delegacion";
    }

    private function construirCondicionesServicios($idRol, $mesesRestriccion, $filtros, &$params)
    {
        $condiciones = ["o.estado = 1"];

        if ($idRol == 4 && $mesesRestriccion > 0) {
            $condiciones[] = "o.fecha_visita <= DATE_SUB(CURDATE(), INTERVAL ? MONTH)";
            $params[] = (int) $mesesRestriccion;
        }

        $fechaInicio = trim((string) ($filtros['fecha_inicio'] ?? ''));
        $fechaFin = trim((string) ($filtros['fecha_fin'] ?? ''));

        if ($fechaInicio !== '' && $fechaFin !== '') {
            $condiciones[] = "o.fecha_visita BETWEEN ? AND ?";
            $params[] = $fechaInicio;
            $params[] = $fechaFin;
        } elseif ($fechaInicio !== '') {
            $condiciones[] = "o.fecha_visita >= ?";
            $params[] = $fechaInicio;
        } elseif ($fechaFin !== '') {
            $condiciones[] = "o.fecha_visita <= ?";
            $params[] = $fechaFin;
        }

        if (!empty($filtros['id_cliente'])) {
            $condiciones[] = "COALESCE(c_directo.id_cliente, c_maq.id_cliente) = ?";
            $params[] = (int) $filtros['id_cliente'];
        }

        if (!empty($filtros['id_punto'])) {
            $condiciones[] = "COALESCE(p_directo.id_punto, p_maq.id_punto) = ?";
            $params[] = (int) $filtros['id_punto'];
        }

        if (!empty($filtros['id_tecnico'])) {
            $condiciones[] = "o.id_tecnico = ?";
            $params[] = (int) $filtros['id_tecnico'];
        }

        if (!empty($filtros['id_delegacion'])) {
            $condiciones[] = "COALESCE(d_directo.id_delegacion, d_maq.id_delegacion) = ?";
            $params[] = (int) $filtros['id_delegacion'];
        }

        if (!empty($filtros['id_tipo_mantenimiento'])) {
            $condiciones[] = "o.id_tipo_mantenimiento = ?";
            $params[] = (int) $filtros['id_tipo_mantenimiento'];
        }

        return implode(' AND ', $condiciones);
    }

    public function listarServiciosParaPdf($idRol = null, $mesesRestriccion = 0, $filtros = [], $limite = 1000)
    {
        $params = [];
        $condicionWhere = $this->construirCondicionesServicios($idRol, $mesesRestriccion, $filtros, $params);
        $limite = max(1, (int) $limite);

        $sql = $this->obtenerSelectServicios() . "
                WHERE $condicionWhere
                ORDER BY o.fecha_visita DESC, o.id_ordenes_servicio DESC
                LIMIT $limite";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error SQL en serviciosPdfModelo: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerServiciosPorIds($ids, $idRol = null, $mesesRestriccion = 0)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $ids), fn($id) => $id > 0)));

        if (empty($ids)) {
            return [];
        }

        $params = [];
        $condicionWhere = $this->construirCondicionesServicios($idRol, $mesesRestriccion, [], $params);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $condicionWhere .= " AND o.id_ordenes_servicio IN ($placeholders)";
        $params = array_merge($params, $ids);

        $sql = $this->obtenerSelectServicios() . "
                WHERE $condicionWhere
                ORDER BY o.fecha_visita DESC, o.id_ordenes_servicio DESC";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error SQL en obtenerServiciosPorIds: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerOpcionesFiltro()
    {
        $opciones = [
            'clientes' => [],
            'tecnicos' => [],
            'delegaciones' => [],
            'tipos' => []
        ];

        try {
            $opciones['clientes'] = $this->conn->query(
                "SELECT id_cliente, nombre_cliente FROM cliente WHERE estado = 1 ORDER BY nombre_cliente ASC"
            )->fetchAll(PDO::FETCH_ASSOC);

            $opciones['tecnicos'] = $this->conn->query(
                "SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC"
            )->fetchAll(PDO::FETCH_ASSOC);

            $opciones['delegaciones'] = $this->conn->query(
                "SELECT id_delegacion, nombre_delegacion FROM delegacion WHERE estado = 1 ORDER BY nombre_delegacion ASC"
            )->fetchAll(PDO::FETCH_ASSOC);

            $opciones['tipos'] = $this->conn->query(
                "SELECT id_tipo_mantenimiento, nombre_completo FROM tipo_mantenimiento WHERE estado = 1 ORDER BY nombre_completo ASC"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo opciones de filtro: " . $e->getMessage());
        }

        return $opciones;
    }

    public function obtenerPuntos($idCliente = null)
    {
        $sql = "SELECT id_punto, id_cliente, nombre_punto FROM punto WHERE estado = 1";
        $params = [];

        if (!empty($idCliente)) {
            $sql .= " AND id_cliente = ?";
            $params[] = (int) $idCliente;
        }

        $sql .= " ORDER BY nombre_punto ASC LIMIT 5000";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo puntos: " . $e->getMessage());
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
                    t.ruta_firma,
                    
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
    public function obtenerNovedadesOrden($idOrden)
    {
        // Asumiendo que tu tabla de tipos de novedad se llama 'tipo_novedad' 
        // y el campo descriptivo se llama 'nombre_novedad' (ajusta si se llama diferente).
        $sql = "SELECT tn.nombre_novedad 
                FROM orden_servicio_novedad osn
                INNER JOIN tipo_novedad tn ON osn.id_tipo_novedad = tn.id_tipo_novedad
                WHERE osn.id_orden_servicio = ?";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$idOrden]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo novedades para PDF: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerParametro($clave)
    {
        $sql = "SELECT valor FROM parametros WHERE clave = ?";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$clave]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado ? $resultado['valor'] : null;
        } catch (PDOException $e) {
            error_log("Error obteniendo parámetro: " . $e->getMessage());
            return null;
        }
    }

    public function puedeVerOrden($idOrden, $idRol, $mesesRestriccion)
    {
        // Si no es rol 4 o no hay restricción, pasa directo
        if ($idRol != 4 || $mesesRestriccion <= 0) {
            return true;
        }

        $sql = "SELECT fecha_visita FROM ordenes_servicio WHERE id_ordenes_servicio = ?";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$idOrden]);
            $orden = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$orden || empty($orden['fecha_visita'])) {
                return false; // Si no existe la orden, bloqueamos por seguridad
            }

            // Validar la fecha
            $fechaVisita = new DateTime($orden['fecha_visita']);
            $fechaPermitida = new DateTime("- $mesesRestriccion months");

            // Solo retorna true si la fecha de visita es igual o anterior a la fecha permitida
            return $fechaVisita <= $fechaPermitida;

        } catch (PDOException $e) {
            error_log("Error validando acceso a orden: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerRepuestosOrden($idOrden)
    {
        // Traemos los repuestos cruzando con la tabla 'repuesto'
        $sql = "SELECT r.nombre_repuesto, osr.origen, osr.cantidad 
                FROM orden_servicio_repuesto osr
                INNER JOIN repuesto r ON osr.id_repuesto = r.id_repuesto
                WHERE osr.id_orden_servicio = ?";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$idOrden]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo repuestos para PDF: " . $e->getMessage());
            return [];
        }
    }
}