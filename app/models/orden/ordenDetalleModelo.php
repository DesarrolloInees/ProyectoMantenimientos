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
    // 1. CONSULTA INTELIGENTE
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
                    tm.id_tipo_maquina,
                    
                    -- CLIENTE
                    COALESCE(o.id_cliente, c_maq.id_cliente) as id_cliente,
                    COALESCE(c_directo.nombre_cliente, c_maq.nombre_cliente) as nombre_cliente,
                    
                    -- PUNTO
                    COALESCE(o.id_punto, p_maq.id_punto) as id_punto,
                    COALESCE(p_directo.nombre_punto, p_maq.nombre_punto) as nombre_punto,
                    
                    -- DELEGACIÓN
                    COALESCE(d_directo.nombre_delegacion, d_maq.nombre_delegacion) as delegacion,

                    -- MODALIDAD
                    COALESCE(o.id_modalidad, 1) as id_modalidad,
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

                    -- REPUESTOS (CONCATENADOS PARA VISUALIZACIÓN INICIAL)
                    IFNULL(
                        (SELECT GROUP_CONCAT(CONCAT(r.nombre_repuesto, ' (', osr.origen, ')') SEPARATOR ', ')
                            FROM orden_servicio_repuesto osr
                            JOIN repuesto r ON osr.id_repuesto = r.id_repuesto
                            WHERE osr.id_orden_servicio = o.id_ordenes_servicio)
                    , '') as repuestos_usados

                FROM ordenes_servicio o
                
                LEFT JOIN maquina m ON o.id_maquina = m.id_maquina
                LEFT JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                LEFT JOIN tecnico t ON o.id_tecnico = t.id_tecnico
                LEFT JOIN tipo_mantenimiento tman ON o.id_tipo_mantenimiento = tman.id_tipo_mantenimiento
                LEFT JOIN estado_maquina em ON o.id_estado_maquina = em.id_estado
                LEFT JOIN calificacion_servicio cal ON o.id_calificacion = cal.id_calificacion
                
                LEFT JOIN punto p_maq ON m.id_punto = p_maq.id_punto
                LEFT JOIN cliente c_maq ON p_maq.id_cliente = c_maq.id_cliente
                LEFT JOIN delegacion d_maq ON p_maq.id_delegacion = d_maq.id_delegacion

                LEFT JOIN cliente c_directo ON o.id_cliente = c_directo.id_cliente
                LEFT JOIN punto p_directo ON o.id_punto = p_directo.id_punto
                LEFT JOIN delegacion d_directo ON p_directo.id_delegacion = d_directo.id_delegacion
                
                WHERE o.fecha_visita = ?
                ORDER BY o.id_tecnico ASC, o.hora_entrada ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$fecha]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // 2. LISTAS BÁSICAS
    // ==========================================
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
        $stmt = $this->conn->prepare("SELECT m.id_maquina, m.device_id, tm.nombre_tipo_maquina, tm.id_tipo_maquina 
                                        FROM maquina m 
                                        JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina 
                                        WHERE m.id_punto = ? 
                                        ORDER BY m.device_id ASC");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

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

    public function obtenerModalidades()
    {
        return $this->conn->query("SELECT * FROM modalidad_operativa ORDER BY id_modalidad")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerDelegacionPorPunto($id_punto)
    {
        $stmt = $this->conn->prepare("SELECT d.nombre_delegacion 
                                        FROM punto p 
                                        JOIN delegacion d ON p.id_delegacion = d.id_delegacion 
                                        WHERE p.id_punto = ?");
        $stmt->execute([$id_punto]);
        return $stmt->fetchColumn() ?: "Sin Asignar";
    }

    public function obtenerPrecioTarifa($id_tipo_maquina, $id_tipo_mantenimiento, $id_modalidad)
    {
        $stmt = $this->conn->prepare("SELECT precio 
                                        FROM tarifa 
                                        WHERE id_tipo_maquina = ? 
                                        AND id_tipo_mantenimiento = ? 
                                        AND id_modalidad = ?
                                        AND año_vigencia = 2025
                                        LIMIT 1");
        $stmt->execute([$id_tipo_maquina, $id_tipo_mantenimiento, $id_modalidad]);
        $precio = $stmt->fetchColumn();
        return $precio ? floatval($precio) : 0;
    }

    public function obtenerListaRepuestos() {
        try {
            $sql = "SELECT id_repuesto, nombre_repuesto FROM repuesto WHERE estado = 1 ORDER BY nombre_repuesto ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo repuestos: " . $e->getMessage());
            return [];
        }
    }

    // ==========================================
    // 3. ACTUALIZACIÓN (CON REPUESTOS) ⭐
    // ==========================================
    public function actualizarOrdenFull($id, $datos)
    {
        try {
            // Iniciamos transacción: O se guarda todo bien, o no se guarda nada
            $this->conn->beginTransaction();

            // 1. ACTUALIZAR TABLA PRINCIPAL
            $sql = "UPDATE ordenes_servicio SET 
                        id_cliente = ?,
                        id_punto = ?,
                        id_maquina = ?,
                        id_modalidad = ?,
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
            $stmt->execute([
                $datos['id_cliente'],
                $datos['id_punto'],
                $datos['id_maquina'],
                $datos['id_modalidad'],
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

            // 2. ACTUALIZAR REPUESTOS
            // La estrategia es: Borrar los viejos -> Insertar los nuevos (si hay)
            
            // A. Borrar repuestos anteriores de esta orden
            $sqlDelete = "DELETE FROM orden_servicio_repuesto WHERE id_orden_servicio = ?";
            $stmtDel = $this->conn->prepare($sqlDelete);
            $stmtDel->execute([$id]);

            // B. Insertar los nuevos (si el JSON trae datos)
            if (!empty($datos['json_repuestos'])) {
                $repuestos = json_decode($datos['json_repuestos'], true);

                if (is_array($repuestos) && count($repuestos) > 0) {
                    $sqlIns = "INSERT INTO orden_servicio_repuesto (id_orden_servicio, id_repuesto, origen, cantidad) VALUES (?, ?, ?, 1)";
                    $stmtIns = $this->conn->prepare($sqlIns);

                    foreach ($repuestos as $rep) {
                        // Verificamos que traiga ID válido
                        if (!empty($rep['id'])) {
                            $stmtIns->execute([
                                $id,
                                $rep['id'],
                                $rep['origen']
                            ]);
                        }
                    }
                }
            }

            // Si todo salió bien, confirmamos los cambios
            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            // Si algo falló, deshacemos todo
            $this->conn->rollBack();
            error_log("Error actualizando orden: " . $e->getMessage());
            return false;
        }
    }
}
?>