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
                o.tiene_novedad,
                o.id_tipo_novedad,
                o.detalle_novedad,
                
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

                -- ⭐⭐ CORRECCIÓN CRÍTICA: AGREGAR CANTIDAD AL TEXTO GENERADO POR SQL ⭐⭐
                    IFNULL(
                        (SELECT GROUP_CONCAT(
                        CONCAT(
                            r.nombre_repuesto, 
            
                -- 1. Agregar el Origen
                    CASE 
                        WHEN osr.origen = 'PROSEGUR' THEN ' (PROSEGUR)'
                        WHEN osr.origen = 'INEES' THEN ' (INEES)'
                        ELSE CONCAT(' (', osr.origen, ')')
                    END,

                -- 2. Agregar la Cantidad SOLO si es mayor a 1
                    CASE 
                        WHEN osr.cantidad > 1 THEN CONCAT(' (x', osr.cantidad, ')')
                        ELSE ''
                    END
                    )
                    ORDER BY r.nombre_repuesto
                        SEPARATOR ', ')
                    FROM orden_servicio_repuesto osr
                    JOIN repuesto r ON osr.id_repuesto = r.id_repuesto
                    WHERE osr.id_orden_servicio = o.id_ordenes_servicio)
                    , '') as repuestos_texto
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
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 🔧 PROCESAR LOS RESULTADOS
        foreach ($resultados as &$row) {
            // YA NO convertimos texto a JSON. 
            // Hacemos una consulta REAL para obtener ID, Cantidad y Origen correctamente.

            $idOrden = $row['id_ordenes_servicio'];

            $sqlRep = "SELECT 
                r.id_repuesto as id, 
                r.nombre_repuesto as nombre, 
                osr.origen, 
                osr.cantidad 
                FROM orden_servicio_repuesto osr
                JOIN repuesto r ON osr.id_repuesto = r.id_repuesto
                WHERE osr.id_orden_servicio = ?";

            $stmtRep = $this->conn->prepare($sqlRep);
            $stmtRep->execute([$idOrden]);
            $listaRepuestos = $stmtRep->fetchAll(PDO::FETCH_ASSOC);

            // Guardamos el JSON estructura real
            $row['repuestos_json'] = json_encode($listaRepuestos);
        }

        return $resultados;
    }

    // ⭐⭐ FUNCIÓN PARA CONVERTIR TEXTO A JSON (MÁS ROBUSTA) ⭐⭐
    private function convertirTextoAJSON($texto)
    {
        $arrayRepuestos = [];

        if (empty($texto) || trim($texto) === '') {
            return '[]';
        }

        $palabrasIgnorar = ['NO', 'NINGUNO', 'NINGUNA', 'SIN REPUESTOS', 'N/A', 'NA', '.', '-', '0', 'VACIO', ''];

        // Truco para proteger los paréntesis de origen
        $textoTemp = str_replace(' (PROSEGUR)', '_(PROSEGUR)', $texto);
        $textoTemp = str_replace(' (INEES)', '_(INEES)', $textoTemp);

        $items = explode(',', $textoTemp);

        foreach ($items as $item) {
            // Restaurar origen
            $item = str_replace('_(PROSEGUR)', ' (PROSEGUR)', $item);
            $item = str_replace('_(INEES)', ' (INEES)', $item);
            $itemLimpio = trim($item);

            if (empty($itemLimpio) || in_array(strtoupper($itemLimpio), $palabrasIgnorar)) {
                continue;
            }

            $cantidad = 1; // Default

            // 1. DETECTAR CANTIDAD (xN)
            if (preg_match('/\(x(\d+)\)$/i', $itemLimpio, $matches)) {
                $cantidad = intval($matches[1]);
                // Quitamos el (x3) del nombre para buscar limpio el ID
                $itemLimpio = trim(preg_replace('/\(x\d+\)$/i', '', $itemLimpio));
            }

            $origen = 'INEES';
            $nombre = $itemLimpio;

            // 2. DETECTAR ORIGEN
            if (stripos($itemLimpio, '(PROSEGUR)') !== false) {
                $origen = 'PROSEGUR';
                $nombre = trim(str_ireplace('(PROSEGUR)', '', $itemLimpio));
            } elseif (stripos($itemLimpio, '(INEES)') !== false) {
                $origen = 'INEES';
                $nombre = trim(str_ireplace('(INEES)', '', $itemLimpio));
            }

            $idRepuesto = $this->buscarIdRepuestoPorNombre($nombre);

            $arrayRepuestos[] = [
                'id' => $idRepuesto,
                'nombre' => $nombre,
                'origen' => $origen,
                'cantidad' => $cantidad // Guardamos la cantidad
            ];
        }

        return json_encode($arrayRepuestos, JSON_UNESCAPED_UNICODE);
    }

    // ⭐⭐ BUSCAR ID DE REPUESTO POR NOMBRE ⭐⭐
    private function buscarIdRepuestoPorNombre($nombre)
    {
        try {
            if (empty($nombre)) {
                return '';
            }

            // Primero, buscar coincidencia exacta (case insensitive)
            $sql = "SELECT id_repuesto FROM repuesto WHERE LOWER(nombre_repuesto) = LOWER(?) LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$nombre]);
            $id = $stmt->fetchColumn();

            if ($id) {
                return $id;
            }

            // Si no hay coincidencia exacta, buscar aproximada
            $sql = "SELECT id_repuesto FROM repuesto WHERE nombre_repuesto LIKE ? LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(["%$nombre%"]);
            $id = $stmt->fetchColumn();

            return $id ?: '';
        } catch (Exception $e) {
            error_log("Error buscando repuesto '{$nombre}': " . $e->getMessage());
            return '';
        }
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

    // ==========================================
    // CAMBIO: AHORA RECIBE EL AÑO COMO PARÁMETRO
    // ==========================================
    public function obtenerPrecioTarifa($id_tipo_maquina, $id_tipo_mantenimiento, $id_modalidad, $anio)
    {
        // Si no llega año, usamos el actual por seguridad
        $anioVigencia = $anio ? $anio : date('Y');

        // 🔥 CAMBIO IMPORTANTE: Quitamos fetchColumn y usamos fetch para validar existencia
        $sql = "SELECT precio 
                FROM tarifa 
                WHERE id_tipo_maquina = ? 
                AND id_tipo_mantenimiento = ? 
                AND id_modalidad = ?
                AND año_vigencia = ? 
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $id_tipo_maquina,
            $id_tipo_mantenimiento,
            $id_modalidad,
            $anioVigencia
        ]);

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        // 🛑 Si no existe registro -> Retornamos -1
        if ($fila === false) {
            return -1;
        }

        // ✅ Si existe (aunque sea 0) -> Retornamos el precio
        return floatval($fila['precio']);
    }

    public function obtenerListaRepuestos()
    {
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
    // 3. ACTUALIZACIÓN (SOLO DATOS GENERALES Y REMISIÓN)
    // ==========================================
    public function actualizarOrdenFull($id, $datos)
    {
        try {
            // Iniciamos transacción
            $this->conn->beginTransaction();

            // -----------------------------------------------------------------
            // 🕵️ PASO 0: GESTIÓN INTELIGENTE DE REMISIONES (CORREGIDO PARA NUEVA BD)
            // -----------------------------------------------------------------
            $sqlCheck = "SELECT numero_remision, id_tecnico FROM ordenes_servicio WHERE id_ordenes_servicio = ?";
            $stmtCheck = $this->conn->prepare($sqlCheck);
            $stmtCheck->execute([$id]);
            $actual = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            // Verificamos si cambió el Número de Remisión O el Técnico
            if ($actual && ($actual['numero_remision'] != $datos['remision'] || $actual['id_tecnico'] != $datos['id_tecnico'])) {

                // A. LIBERAR LA VIEJA
                // CAMBIO: Usamos id_estado con subconsulta para 'DISPONIBLE'
                $sqlLiberar = "UPDATE control_remisiones 
                               SET id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'DISPONIBLE' LIMIT 1), 
                                   id_orden_servicio = NULL, 
                                   fecha_uso = NULL 
                               WHERE id_orden_servicio = ?";
                $this->conn->prepare($sqlLiberar)->execute([$id]);

                // B. OCUPAR LA NUEVA
                if (!empty($datos['remision'])) {
                    // CAMBIO: Usamos id_estado con subconsulta para 'USADA'
                    $sqlOcupar = "UPDATE control_remisiones 
                                  SET id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'USADA' LIMIT 1), 
                                      id_orden_servicio = ?, 
                                      fecha_uso = ? 
                                  WHERE numero_remision = ? AND id_tecnico = ?";

                    $fechaUso = $datos['fecha_individual'] . ' ' . ($datos['entrada'] ?: '00:00:00');
                    $this->conn->prepare($sqlOcupar)->execute([
                        $id,
                        $fechaUso,
                        $datos['remision'],
                        $datos['id_tecnico']
                    ]);
                }
            }
            // -----------------------------------------------------------------

            // ---------------------------------------------------------
            // 1. ACTUALIZAR TABLA PRINCIPAL (ORDENES) - (ESTO NO CAMBIA)
            // ---------------------------------------------------------
            $sql = "UPDATE ordenes_servicio SET 
                        id_cliente = ?, id_punto = ?, id_maquina = ?, id_modalidad = ?,
                        numero_remision = ?, id_tecnico = ?, id_tipo_mantenimiento = ?, 
                        id_estado_maquina = ?, id_calificacion = ?, hora_entrada = ?, 
                        hora_salida = ?, tiempo_servicio = ?, valor_servicio = ?,
                        actividades_realizadas = ?, tiene_novedad = ?, fecha_visita = ?
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
                $datos['tiene_novedad'] ?? 0,
                $datos['fecha_individual'],
                $id
            ]);

            // 3. ACTUALIZAR INFO MANTENIMIENTO EN PUNTO
            $this->actualizarInfoMantenimientoPunto($datos['id_punto']);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error actualizando orden: " . $e->getMessage());
            return false;
        }
    }

    // --- 4. FUNCIÓN AUXILIAR PARA ACTUALIZAR INFO EN PUNTO ---
    private function actualizarInfoMantenimientoPunto($idPunto)
    {
        try {
            // Busca la orden más reciente (por fecha y ID) de este punto
            // y actualiza la tabla 'punto' con esa información.
            $sql = "UPDATE punto p
                    JOIN (
                        SELECT id_punto, fecha_visita, id_tipo_mantenimiento
                        FROM ordenes_servicio
                        WHERE id_punto = :id_punto_b
                        ORDER BY fecha_visita DESC, id_ordenes_servicio DESC
                        LIMIT 1
                    ) AS ultima_real ON p.id_punto = ultima_real.id_punto
                    SET p.fecha_ultima_visita = ultima_real.fecha_visita,
                        p.id_ultimo_tipo_mantenimiento = ultima_real.id_tipo_mantenimiento
                    WHERE p.id_punto = :id_punto_a";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id_punto_b' => $idPunto, // Para el subquery
                ':id_punto_a' => $idPunto  // Para el where principal
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Error actualizando info mantenimiento punto (Smart): " . $e->getMessage());
            return false;
        }
    }

    public function obtenerFestivos()
    {
        try {
            $sql = "SELECT fecha FROM dias_festivos ORDER BY fecha ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            // Devuelve un array plano: ["2025-01-01", "2025-01-06", ...]
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return [];
        }
    }



    // ==========================================
    // 4. GESTIÓN DE INVENTARIO (NUEVO)
    // ==========================================

    // A. Consultar qué tiene el técnico en su maleta
    public function obtenerStockPorTecnico($idTecnico)
    {
        $sql = "SELECT 
                    i.id_repuesto, 
                    r.nombre_repuesto, 
                    i.cantidad_actual 
                FROM inventario_tecnico i
                JOIN repuesto r ON i.id_repuesto = r.id_repuesto
                WHERE i.id_tecnico = ? AND i.cantidad_actual > 0 AND i.estado = 1
                ORDER BY r.nombre_repuesto ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$idTecnico]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Obtener los repuestos que YA estaban guardados en una orden específica
    public function obtenerRepuestosDeOrden($idOrden)
    {
        $sql = "SELECT id_repuesto as id, cantidad 
                FROM orden_servicio_repuesto 
                WHERE id_orden_servicio = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$idOrden]);
        // Devuelve algo como: [['id'=>1, 'cantidad'=>2], ['id'=>5, 'cantidad'=>1]]
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    // ==========================================
    // 5. BÚSQUEDA AVANZADA (PARA VISTA INDIVIDUAL)
    // ==========================================
    public function buscarOrdenesFiltros($filtros)
    {
        // NOTA: Usamos EL MISMO SELECT gigante que arriba para que no falten campos
        $sql = "SELECT 
                o.id_ordenes_servicio,
                o.numero_remision,
                o.fecha_visita,
                o.hora_entrada,
                o.hora_salida,
                o.tiempo_servicio,
                o.valor_servicio,
                o.actividades_realizadas as que_se_hizo,
                o.tiene_novedad,
                
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

                -- TEXTO REPUESTOS
                IFNULL(
                    (SELECT GROUP_CONCAT(
                        CONCAT(r.nombre_repuesto, ' (', osr.origen, ')', IF(osr.cantidad>1, CONCAT(' x', osr.cantidad), ''))
                        SEPARATOR ', ')
                    FROM orden_servicio_repuesto osr
                    JOIN repuesto r ON osr.id_repuesto = r.id_repuesto
                    WHERE osr.id_orden_servicio = o.id_ordenes_servicio)
                , '') as repuestos_texto

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
                
                WHERE 1=1 ";
                // Empezamos la parte de los filtros despues del WHERE 1=1
        $params = [];

        // Filtro por Remisión
        if (!empty($filtros['remision'])) {
            $sql .= " AND o.numero_remision LIKE ?";
            $params[] = "%" . $filtros['remision'] . "%";
        }

        // Filtro por Cliente
        if (!empty($filtros['id_cliente'])) {
            $sql .= " AND (o.id_cliente = ? OR c_maq.id_cliente = ?)";
            $params[] = $filtros['id_cliente'];
            $params[] = $filtros['id_cliente'];
        }

        // Filtro por Punto
        if (!empty($filtros['id_punto'])) {
            $sql .= " AND (o.id_punto = ? OR m.id_punto = ?)";
            $params[] = $filtros['id_punto'];
            $params[] = $filtros['id_punto'];
        }

        // --- NUEVOS FILTROS (LO QUE PEDISTE) ---

        // 1. Rango de Fechas (Desde - Hasta)
        if (!empty($filtros['fecha_inicio'])) {
            $sql .= " AND o.fecha_visita >= ?";
            $params[] = $filtros['fecha_inicio'];
        }

        if (!empty($filtros['fecha_fin'])) {
            // 🔥 TRUCO: Concatenamos la hora final del día para que incluya todo ese día
            $sql .= " AND o.fecha_visita <= ?";
            $params[] = $filtros['fecha_fin'] . ' 23:59:59'; 
        }

        // 2. Delegación (Bogotá, Medellín, etc.)
        // Verificamos tanto en el punto directo como en el punto de la máquina
        if (!empty($filtros['id_delegacion'])) {
            $sql .= " AND (p_directo.id_delegacion = ? OR p_maq.id_delegacion = ?)";
            $params[] = $filtros['id_delegacion'];
            $params[] = $filtros['id_delegacion'];
        }

        // Ordenar y Limitar (Ojo: Si buscas por fecha, a veces querrás ver más de 50)
        $sql .= " ORDER BY o.fecha_visita DESC, o.id_ordenes_servicio DESC LIMIT 100";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ... (Mantén el foreach que procesa el JSON de repuestos igual) ...
        foreach ($resultados as &$row) {
            $idOrden = $row['id_ordenes_servicio'];
            // ... (tu lógica de repuestos json) ...
            $sqlRep = "SELECT r.id_repuesto as id, r.nombre_repuesto as nombre, osr.origen, osr.cantidad 
                        FROM orden_servicio_repuesto osr
                        JOIN repuesto r ON osr.id_repuesto = r.id_repuesto
                        WHERE osr.id_orden_servicio = ?";
            $stmtRep = $this->conn->prepare($sqlRep);
            $stmtRep->execute([$idOrden]);
            $row['repuestos_json'] = json_encode($stmtRep->fetchAll(PDO::FETCH_ASSOC));
        }

        return $resultados;
    }

    // ==========================================
    // 6. GESTIÓN TIEMPO REAL (AJAX PURO)
    // ==========================================

    // A. AGREGAR REPUESTO (Descuenta stock y guarda en orden)
    // A. AGREGAR REPUESTO (CORREGIDO: Solo descuenta si es INEES)
    public function agregarRepuestoRealTime($idOrden, $idRepuesto, $cantidad, $origen, $idTecnico)
    {
        try {
            $this->conn->beginTransaction();

            $nuevoStockVisual = 0; // Por defecto

            // =========================================================
            // 🔥 PROTECCIÓN: SOLO TOCAMOS INVENTARIO SI ES INEES
            // =========================================================
            if ($origen === 'INEES') {
                // 1. Verificar Stock Disponible
                $sqlStock = "SELECT cantidad_actual FROM inventario_tecnico 
                                WHERE id_tecnico = ? AND id_repuesto = ? FOR UPDATE";
                $stmt = $this->conn->prepare($sqlStock);
                $stmt->execute([$idTecnico, $idRepuesto]);
                $stockActual = $stmt->fetchColumn();

                if ($stockActual === false || $stockActual < $cantidad) {
                    $this->conn->rollBack();
                    return ['status' => 'error', 'msg' => 'Stock insuficiente en la maleta del técnico.'];
                }

                // 2. Descontar del Inventario Técnico
                $sqlUpdInv = "UPDATE inventario_tecnico 
                                SET cantidad_actual = cantidad_actual - ? 
                                WHERE id_tecnico = ? AND id_repuesto = ?";
                $this->conn->prepare($sqlUpdInv)->execute([$cantidad, $idTecnico, $idRepuesto]);

                $nuevoStockVisual = $stockActual - $cantidad;
            }
            // =========================================================

            // 3. Agregar o Actualizar en la Orden (ESTO SIEMPRE SE HACE)
            $sqlCheck = "SELECT cantidad FROM orden_servicio_repuesto 
                         WHERE id_orden_servicio = ? AND id_repuesto = ? AND origen = ?";
            $stmtCheck = $this->conn->prepare($sqlCheck);
            $stmtCheck->execute([$idOrden, $idRepuesto, $origen]);
            $cantOrden = $stmtCheck->fetchColumn();

            if ($cantOrden !== false) {
                // Sumar
                $sqlUpdOrden = "UPDATE orden_servicio_repuesto 
                                SET cantidad = cantidad + ? 
                                WHERE id_orden_servicio = ? AND id_repuesto = ? AND origen = ?";
                $this->conn->prepare($sqlUpdOrden)->execute([$cantidad, $idOrden, $idRepuesto, $origen]);
            } else {
                // Insertar
                $sqlInsOrden = "INSERT INTO orden_servicio_repuesto 
                                (id_orden_servicio, id_repuesto, origen, cantidad) 
                                VALUES (?, ?, ?, ?)";
                $this->conn->prepare($sqlInsOrden)->execute([$idOrden, $idRepuesto, $origen, $cantidad]);
            }

            $this->conn->commit();

            // Devolvemos el stock nuevo solo si era INEES, sino devolvemos null o algo neutral
            return ['status' => 'ok', 'msg' => 'Agregado correctamente', 'nuevo_stock' => $nuevoStockVisual];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['status' => 'error', 'msg' => 'Error BD: ' . $e->getMessage()];
        }
    }

    // B. ELIMINAR REPUESTO (CORREGIDO: Solo devuelve stock si es INEES)
    public function eliminarRepuestoRealTime($idOrden, $idRepuesto, $origen, $idTecnico)
    {
        try {
            $this->conn->beginTransaction();

            // 1. Obtener cantidad a borrar
            $sqlGet = "SELECT cantidad FROM orden_servicio_repuesto 
                        WHERE id_orden_servicio = ? AND id_repuesto = ? AND origen = ?";
            $stmt = $this->conn->prepare($sqlGet);
            $stmt->execute([$idOrden, $idRepuesto, $origen]);
            $cantidad = $stmt->fetchColumn();

            if (!$cantidad) {
                $this->conn->rollBack();
                return ['status' => 'error', 'msg' => 'El repuesto no existe en esta orden.'];
            }

            // =========================================================
            // 🔥 PROTECCIÓN: SOLO DEVOLVEMOS AL TÉCNICO SI ES INEES
            // =========================================================
            if ($origen === 'INEES') {
                $sqlDev = "INSERT INTO inventario_tecnico (id_tecnico, id_repuesto, cantidad_actual, estado) 
                            VALUES (?, ?, ?, 1) 
                            ON DUPLICATE KEY UPDATE cantidad_actual = cantidad_actual + ?";
                $this->conn->prepare($sqlDev)->execute([$idTecnico, $idRepuesto, $cantidad, $cantidad]);
            }
            // =========================================================

            // 3. Borrar de la Orden (ESTO SIEMPRE SE HACE)
            $sqlDel = "DELETE FROM orden_servicio_repuesto 
                        WHERE id_orden_servicio = ? AND id_repuesto = ? AND origen = ?";
            $this->conn->prepare($sqlDel)->execute([$idOrden, $idRepuesto, $origen]);

            $this->conn->commit();
            return ['status' => 'ok', 'msg' => 'Repuesto eliminado de la orden'];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['status' => 'error', 'msg' => 'Error BD: ' . $e->getMessage()];
        }
    }

    // B. Descontar del inventario (Lógica Segura)
    public function descontarStock($idTecnico, $idRepuesto, $cantidad)
    {
        // 1. Verificamos si tiene saldo suficiente
        $sqlCheck = "SELECT cantidad_actual FROM inventario_tecnico 
                    WHERE id_tecnico = ? AND id_repuesto = ?";
        $stmtCheck = $this->conn->prepare($sqlCheck);
        $stmtCheck->execute([$idTecnico, $idRepuesto]);
        $actual = $stmtCheck->fetchColumn();

        if ($actual !== false && $actual >= $cantidad) {
            // 2. Si tiene, descontamos
            $sqlUpd = "UPDATE inventario_tecnico 
                        SET cantidad_actual = cantidad_actual - ? 
                        WHERE id_tecnico = ? AND id_repuesto = ?";
            $stmtUpd = $this->conn->prepare($sqlUpd);
            return $stmtUpd->execute([$cantidad, $idTecnico, $idRepuesto]);
        }
        return false; // No tenía stock suficiente
    }

    // C. Devolver al inventario (Si borras un repuesto de la orden, se le devuelve al técnico)
    public function devolverStock($idTecnico, $idRepuesto, $cantidad)
    {
        $sql = "INSERT INTO inventario_tecnico (id_tecnico, id_repuesto, cantidad_actual, estado)
                VALUES (?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE cantidad_actual = cantidad_actual + ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$idTecnico, $idRepuesto, $cantidad, $cantidad]);
    }

    // A. Obtener lista de tipos de novedad para el Select
    public function obtenerTiposNovedad()
    {
        return $this->conn->query("SELECT * FROM tipo_novedad WHERE estado = 1 ORDER BY nombre_novedad ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    // B. Guardar la novedad desde el Modal (AJAX)
    // 🗑️ Quitamos el parámetro $detalle de la función
    public function guardarNovedadOrden($idOrden, $idTipoNovedad)
    {
        try {
            // SQL simplificado: solo actualiza el ID del tipo
            $sql = "UPDATE ordenes_servicio 
                SET tiene_novedad = 1, 
                    id_tipo_novedad = ?
                    -- Ya no tocamos detalle_novedad
                WHERE id_ordenes_servicio = ?";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$idTipoNovedad, $idOrden]);
        } catch (Exception $e) {
            return false;
        }
    }

    // C. Eliminar novedad (Si se arrepienten)
    // En ordenDetalleModelo.php

    public function eliminarNovedadOrden($idOrden)
    {
        try {
            $sql = "UPDATE ordenes_servicio 
                SET tiene_novedad = 0, 
                    id_tipo_novedad = NULL, 
                    detalle_novedad = NULL 
                WHERE id_ordenes_servicio = ?";

            // ⚠️ IMPORTANTE: Debe ser 'prepare', NO 'query'
            $stmt = $this->conn->prepare($sql);

            // Ejecutamos pasando el ID
            return $stmt->execute([$idOrden]);
        } catch (Exception $e) {
            // Tip: Descomenta esto si quieres ver el error real en los logs de PHP
            // error_log("Error al eliminar novedad: " . $e->getMessage());
            return false;
        }
    }


    // A. NUEVA FUNCIÓN: Obtener lista de delegaciones
    public function obtenerDelegaciones()
    {
        return $this->conn->query("SELECT id_delegacion, nombre_delegacion FROM delegacion ORDER BY nombre_delegacion ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
}
