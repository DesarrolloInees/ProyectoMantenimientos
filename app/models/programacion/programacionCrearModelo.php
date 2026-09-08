<?php
// app/models/programacion/programacionCrearModelo.php

if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

class programacionCrearModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // ===================================
    // PASO 1: DELEGACIONES Y ZONAS
    // ===================================

    public function obtenerDelegaciones()
    {
        $stmt = $this->conn->prepare("SELECT id_delegacion, nombre_delegacion FROM delegacion WHERE estado = 1 ORDER BY nombre_delegacion ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener clientes que tienen puntos en una delegación
     */
    public function obtenerClientesPorDelegacion($id_delegacion)
    {
        $sql = "SELECT DISTINCT c.id_cliente, c.nombre_cliente, c.codigo_cliente,
                       COUNT(p.id_punto) as total_puntos,
                       SUM(CASE WHEN (p.fecha_ultima_visita IS NULL OR DATEDIFF(NOW(), p.fecha_ultima_visita) >= 30) THEN 1 ELSE 0 END) as puntos_pendientes
                FROM cliente c
                INNER JOIN punto p ON c.id_cliente = p.id_cliente
                WHERE p.id_delegacion = :id_delegacion
                  AND p.estado = 1
                  AND c.estado = 1
                GROUP BY c.id_cliente, c.nombre_cliente, c.codigo_cliente
                HAVING puntos_pendientes > 0
                ORDER BY c.nombre_cliente ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_delegacion' => $id_delegacion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerZonasPorDelegacion($id_delegacion)
    {
        $sql = "SELECT DISTINCT zona FROM punto WHERE id_delegacion = :id_delegacion AND zona IS NOT NULL AND zona != '' ORDER BY zona ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_delegacion' => $id_delegacion]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function obtenerTecnicos()
    {
        $stmt = $this->conn->prepare("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ===================================
    // PASO 2: PUNTOS POR ZONA
    // ===================================

    /**
     * Obtener puntos pendientes de una zona específica
     * Filtrado por clientes seleccionados
     */
    public function obtenerPuntosPorZona($id_delegacion, $zona, $clientes_ids = [])
    {
        $sql = "SELECT p.id_punto, p.nombre_punto, p.zona, p.fecha_ultima_visita, 
                        c.nombre_cliente, m.nombre_municipio,
                        DATEDIFF(NOW(), p.fecha_ultima_visita) as dias_sin_visita
                FROM punto p
                INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                LEFT JOIN municipio m ON p.id_municipio = m.id_municipio
                WHERE p.estado = 1 
                AND p.id_delegacion = :delegacion
                AND p.zona = :zona
                AND (p.fecha_ultima_visita IS NULL OR DATEDIFF(NOW(), p.fecha_ultima_visita) >= 30)
                
                /* --- FILTRO CORREGIDO --- */
                /* Ignorar puntos que ya tienen una orden PROGRAMADA (Estado 2) */
                AND NOT EXISTS (
                    SELECT 1 
                    FROM ordenes_servicio os 
                    WHERE os.id_punto = p.id_punto 
                    AND os.estado = 2  /* <--- AQUÍ FILTRAMOS EL ESTADO 2 */
                )";

        $params = [
            ':delegacion' => $id_delegacion,
            ':zona' => $zona
        ];

        // Filtro de clientes
        if (!empty($clientes_ids) && is_array($clientes_ids)) {
            $placeholders = [];
            foreach ($clientes_ids as $k => $cliente_id) {
                $key = ':cliente' . $k;
                $placeholders[] = $key;
                $params[$key] = $cliente_id;
            }
            $sql .= " AND p.id_cliente IN (" . implode(',', $placeholders) . ")";
        }

        $sql .= " ORDER BY p.fecha_ultima_visita ASC, m.nombre_municipio ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Contar puntos pendientes por zona
     * Filtrado por clientes seleccionados
     */
    public function contarPuntosPorZona($id_delegacion, $clientes_ids = [])
    {
        $sql = "SELECT p.zona, COUNT(*) as total
                FROM punto p
                WHERE p.estado = 1 
                AND p.id_delegacion = :delegacion
                AND (p.fecha_ultima_visita IS NULL OR DATEDIFF(NOW(), p.fecha_ultima_visita) >= 30)
                /* Ignorar puntos que ya tienen una orden PROGRAMADA (Estado 2) */
                AND NOT EXISTS (
                    SELECT 1 
                    FROM ordenes_servicio os 
                    WHERE os.id_punto = p.id_punto 
                    AND os.estado = 2
                )";

        $params = [':delegacion' => $id_delegacion];

        // Filtro de clientes
        if (!empty($clientes_ids) && is_array($clientes_ids)) {
            $placeholders = [];
            foreach ($clientes_ids as $k => $cliente_id) {
                $key = ':cliente' . $k;
                $placeholders[] = $key;
                $params[$key] = $cliente_id;
            }
            $sql .= " AND p.id_cliente IN (" . implode(',', $placeholders) . ")";
        }

        $sql .= " GROUP BY p.zona ORDER BY p.zona ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        $resultado = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $resultado[$row['zona']] = $row['total'];
        }
        return $resultado;
    }

    // ===================================
    // PASO 3: GENERAR PROGRAMACIÓN SEMANAL (CORREGIDA)
    // ===================================

    public function generarProgramacionSemanal($configuracion)
    {
        $propuesta = [];

        // 1. ARRAY DE CONTROL: Lista negra de puntos ya usados
        $idsAsignados = [];

        $fechaInicio = new DateTime($configuracion['fecha_inicio']);
        $calendario = $configuracion['calendario'];
        $semanas = intval($configuracion['semanas']);
        $maxServicios = intval($configuracion['max_servicios_dia']);
        $clientesIds = $configuracion['clientes_ids'] ?? [];

        $diasSemana = [
            1 => 'lunes',
            2 => 'martes',
            3 => 'miercoles',
            4 => 'jueves',
            5 => 'viernes',
            6 => 'sabado'
        ];

        // Obtener todos los puntos (Caché inicial)
        $puntosPorZona = [];
        foreach ($calendario as $dia => $config) {
            if (empty($config['zonas']))
                continue;
            foreach ($config['zonas'] as $zona) {
                if (!isset($puntosPorZona[$zona])) {
                    // AQUÍ SÍ FUNCIONA $this-> PORQUE ESTAMOS EN EL MODELO
                    $puntos = $this->obtenerPuntosPorZona(
                        $configuracion['id_delegacion'],
                        $zona,
                        $clientesIds
                    );
                    $puntosPorZona[$zona] = $puntos;
                }
            }
        }

        // --- INICIO DE LOS CICLOS ---
        for ($semana = 0; $semana < $semanas; $semana++) {
            foreach ($diasSemana as $numeroDia => $nombreDia) {
                if (empty($calendario[$nombreDia]))
                    continue;

                $configDia = $calendario[$nombreDia];
                $idTecnico = $configDia['id_tecnico'];
                $zonasDelDia = $configDia['zonas'];

                $diasDesdeInicio = ($semana * 7) + ($numeroDia - 1);
                $fechaActual = clone $fechaInicio;
                $fechaActual->modify("+{$diasDesdeInicio} days");

                $puntosCandidatos = [];
                foreach ($zonasDelDia as $zona) {
                    if (isset($puntosPorZona[$zona])) {
                        $puntosCandidatos = array_merge($puntosCandidatos, $puntosPorZona[$zona]);
                    }
                }

                $asignadosCount = 0;

                foreach ($puntosCandidatos as $punto) {
                    if ($asignadosCount >= $maxServicios)
                        break;

                    $idPunto = $punto['id_punto'];

                    // 2. VERIFICACIÓN: Si ya se usó, saltar
                    if (in_array($idPunto, $idsAsignados)) {
                        continue;
                    }

                    $propuesta[] = [
                        'id_punto' => $idPunto,
                        'id_tecnico' => $idTecnico,
                        'fecha_visita' => $fechaActual->format('Y-m-d'),
                        'zona' => $punto['zona'],
                        'nombre_punto' => $punto['nombre_punto'],
                        'nombre_cliente' => $punto['nombre_cliente'],
                        'es_sabado_fallido' => false
                    ];

                    // 3. BLOQUEO: Agregar a lista negra
                    $idsAsignados[] = $idPunto;

                    $asignadosCount++;
                }
            }
        }

        return $propuesta;
    }



    // ===================================
    // GUARDADO
    // ===================================

    /**
     * Guardar programación definitiva en ordenes_servicio
     * Estado 0 = PROGRAMADA (pendiente de ejecutar)
     * Estado 1 = EJECUTADA (completada con datos)
     */
    public function guardarProgramacionDefinitiva($listaServicios)
    {
        try {
            $this->conn->beginTransaction();

            $sql = "INSERT INTO ordenes_servicio (
                    id_punto, 
                    id_tecnico, 
                    id_cliente, 
                    id_maquina, 
                    id_modalidad,
                    fecha_visita, 
                    estado, 
                    created_at
                ) VALUES (
                    :id_punto, 
                    :id_tecnico, 
                    :id_cliente, 
                    :id_maquina, 
                    :id_modalidad,
                    :fecha_visita, 
                    2, 
                    NOW()
                )";

            $stmt = $this->conn->prepare($sql);
            $count = 0;
            $errores = [];

            foreach ($listaServicios as $servicio) {
                if (!empty($servicio['id_punto']) && !empty($servicio['id_tecnico']) && !empty($servicio['fecha_visita'])) {

                    $datos = $this->obtenerDatosComplementariosOrden($servicio['id_punto']);

                    if (!$datos) {
                        $errores[] = "Error con el punto ID {$servicio['id_punto']}: no existe en la base de datos.";
                        continue;
                    }

                    try {
                        $stmt->execute([
                            ':id_punto' => $servicio['id_punto'],
                            ':id_tecnico' => $servicio['id_tecnico'],
                            ':id_cliente' => $datos['id_cliente'],
                            ':id_maquina' => !empty($datos['id_maquina']) ? $datos['id_maquina'] : null,
                            ':id_modalidad' => !empty($datos['id_modalidad']) ? $datos['id_modalidad'] : null,
                            ':fecha_visita' => $servicio['fecha_visita']
                        ]);
                        $count++;
                    } catch (PDOException $e) {
                        // Guarda el error técnico directo de MySQL para diagnosticar
                        $errores[] = "Error guardando Punto {$servicio['id_punto']}: " . $e->getMessage();
                    }
                }
            }

            // Si se lograron insertar filas, confirmamos la transacción
            if ($count > 0) {
                $this->conn->commit();
                return [
                    "status" => true,
                    "count" => $count,
                    "msg" => "Se crearon $count órdenes programadas.",
                    "errores" => $errores
                ];
            } else {
                $this->conn->rollBack();
                return [
                    "status" => false,
                    "msg" => "No se pudo insertar ninguna orden. " . implode(" | ", $errores)
                ];
            }

        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return ["status" => false, "msg" => "Error de BD: " . $e->getMessage()];
        }
    }

    /**
     * Obtener datos complementarios para crear la orden
     * (Cliente, Máquina principal, Modalidad del punto)
     */
    private function obtenerDatosComplementariosOrden($id_punto)
    {
        $sql = "SELECT 
                p.id_cliente,
                p.id_modalidad,
                COALESCE(
                    (SELECT m.id_maquina 
                        FROM maquina m 
                        WHERE m.id_punto = p.id_punto 
                        AND m.estado = 1 
                        ORDER BY m.id_maquina ASC 
                    LIMIT 1), 
                    0
                ) as id_maquina
            FROM punto p
            WHERE p.id_punto = :id_punto";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_punto' => $id_punto]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Auxiliar para obtener información de puntos
    public function obtenerInfoPuntos($ids)
    {
        if (empty($ids))
            return [];

        $in = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "SELECT p.id_punto, p.nombre_punto, p.zona, c.nombre_cliente 
                FROM punto p
                INNER JOIN cliente c ON p.id_cliente = c.id_cliente 
                WHERE p.id_punto IN ($in)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($ids);

        $resultado = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $resultado[$row['id_punto']] = $row;
        }

        return $resultado;
    }


    // ===================================
    // EXPORTAR A EXCEL (DESPUÉS DE GUARDAR)
    // ===================================

    public function obtenerDatosProgramacionExcel($listaServicios)
    {
        if (empty($listaServicios))
            return [];

        // 1. Obtener IDs únicos de puntos para consultar
        $idsPuntos = array_unique(array_column($listaServicios, 'id_punto'));
        $in = str_repeat('?,', count($idsPuntos) - 1) . '?';

        // Consultamos toda la data requerida
        $sql = "SELECT p.id_punto, c.codigo_cliente, c.nombre_cliente, p.nombre_punto, 
                        p.direccion, m.nombre_municipio, p.zona, d.nombre_delegacion, 
                        (SELECT mq.device_id FROM maquina mq WHERE mq.id_punto = p.id_punto AND mq.estado = 1 ORDER BY mq.id_maquina ASC LIMIT 1) as device_id,
                        p.fecha_ultima_visita,
                        (SELECT tm.nombre_completo 
                        FROM ordenes_servicio os 
                        LEFT JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento 
                        WHERE os.id_punto = p.id_punto AND os.estado = 1 
                        ORDER BY os.fecha_visita DESC LIMIT 1) as nombre_mantenimiento
                FROM punto p
                LEFT JOIN cliente c ON p.id_cliente = c.id_cliente
                LEFT JOIN municipio m ON p.id_municipio = m.id_municipio
                LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                WHERE p.id_punto IN ($in)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute(array_values($idsPuntos));

        $resultados = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $resultados[$row['id_punto']] = $row;
        }

        // 2. Obtener nombres de los técnicos asignados
        $idsTecnicos = array_unique(array_column($listaServicios, 'id_tecnico'));
        $nombresTecnicos = [];
        if (!empty($idsTecnicos)) {
            $inTec = str_repeat('?,', count($idsTecnicos) - 1) . '?';
            $stmtTec = $this->conn->prepare("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE id_tecnico IN ($inTec)");
            $stmtTec->execute(array_values($idsTecnicos));
            while ($rowTec = $stmtTec->fetch(PDO::FETCH_ASSOC)) {
                $nombresTecnicos[$rowTec['id_tecnico']] = $rowTec['nombre_tecnico'];
            }
        }

        // 3. Unir la info consultada con la fecha y el técnico programado
        $datosExcel = [];
        foreach ($listaServicios as $servicio) {
            $idPunto = $servicio['id_punto'];
            if (isset($resultados[$idPunto])) {
                $fila = $resultados[$idPunto];
                $fila['fecha_visita_programada'] = $servicio['fecha_visita'];
                $fila['tecnico_asignado'] = $nombresTecnicos[$servicio['id_tecnico']] ?? 'Desconocido';
                $datosExcel[] = $fila;
            }
        }

        // 4. Ordenar por fecha programada ascendente
        usort($datosExcel, function ($a, $b) {
            return strtotime($a['fecha_visita_programada']) - strtotime($b['fecha_visita_programada']);
        });

        return $datosExcel;
    }

    // ===================================
    // MAQUINAS FUERA DE SERVICIO
    // ===================================

    /**
     * Obtener todas las maquinas fuera de servicio con info del punto
     */
    public function obtenerMaquinasFueraDeServicio()
    {
        $sql = "SELECT m.id_maquina, m.device_id,
                        p.id_punto, p.nombre_punto, p.zona, p.direccion,
                        c.nombre_cliente, c.codigo_cliente,
                        tm.nombre_tipo_maquina
                FROM maquina m
                INNER JOIN punto p ON m.id_punto = p.id_punto
                INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                INNER JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                WHERE m.activo_operativo = 0 AND m.estado = 1 AND p.estado = 1
                /* NUEVA VALIDACIÓN: Ocultar si ya está programado */
                AND NOT EXISTS (
                    SELECT 1 
                    FROM ordenes_servicio os 
                    WHERE os.id_punto = p.id_punto 
                    AND os.estado = 2
                )
                ORDER BY p.zona ASC, c.nombre_cliente ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener puntos aledaños permitiendo una o múltiples zonas (separadas por comas)
     * Forzado: Muestra SIEMPRE los puntos inoperativos sin importar los 30 días
     */
    public function obtenerPuntosAledanios($id_punto, $zonas, $id_delegacion = null)
    {
        if (!is_array($zonas)) {
            $zonas = array_map('trim', explode(',', $zonas));
        }
        $zonas = array_filter($zonas);

        if (empty($zonas))
            return [];

        $placeholdersZona = [];
        $params = [];
        foreach ($zonas as $k => $z) {
            $key = ':zona_' . $k;
            $placeholdersZona[] = $key;
            $params[$key] = $z;
        }

        $sql = "SELECT p.id_punto, p.nombre_punto, p.direccion, p.zona, p.fecha_ultima_visita,
                        c.nombre_cliente, c.codigo_cliente,
                        m.nombre_municipio,
                        (SELECT mq.device_id FROM maquina mq WHERE mq.id_punto = p.id_punto AND mq.estado = 1 ORDER BY mq.id_maquina ASC LIMIT 1) as device_id,
                        (SELECT tm.nombre_tipo_maquina FROM maquina mq2 
                        INNER JOIN tipo_maquina tm ON mq2.id_tipo_maquina = tm.id_tipo_maquina 
                        WHERE mq2.id_punto = p.id_punto AND mq2.estado = 1 ORDER BY mq2.id_maquina ASC LIMIT 1) as tipo_maquina,
                        CASE WHEN EXISTS (SELECT 1 FROM maquina mq3 WHERE mq3.id_punto = p.id_punto AND mq3.activo_operativo = 0 AND mq3.estado = 1) THEN 1 ELSE 0 END as fuera_de_servicio,
                        DATEDIFF(NOW(), p.fecha_ultima_visita) as dias_sin_visita
                FROM punto p
                INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                LEFT JOIN municipio m ON p.id_municipio = m.id_municipio
                WHERE p.estado = 1
                AND p.zona IN (" . implode(',', $placeholdersZona) . ")
                
                /* EXCEPCIÓN: Si está fuera de servicio entra directo, si no, valida los 30 días */
                AND (
                    EXISTS (SELECT 1 FROM maquina mq4 WHERE mq4.id_punto = p.id_punto AND mq4.activo_operativo = 0 AND mq4.estado = 1)
                    OR p.fecha_ultima_visita IS NULL 
                    OR DATEDIFF(NOW(), p.fecha_ultima_visita) >= 30
                )
                
                /* No mostrar si ya está programado en estado 2 */
                AND NOT EXISTS (
                    SELECT 1 
                    FROM ordenes_servicio os 
                    WHERE os.id_punto = p.id_punto 
                    AND os.estado = 2
                )";

        if (!empty($id_punto) && $id_punto > 0) {
            $sql .= " AND p.id_punto != :id_punto";
            $params[':id_punto'] = $id_punto;
        }

        // Ordenar PRIMERO los que están fuera de servicio para que salgan arriba en el modal
        $sql .= " ORDER BY fuera_de_servicio DESC, p.fecha_ultima_visita ASC, c.nombre_cliente ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Restaurar una maquina puntual a operativo por device_id
     */
    public function restaurarMaquinaOperativo($deviceId)
    {
        try {
            $sql = "UPDATE maquina SET activo_operativo = 1, fecha_actualizacion = NOW() WHERE device_id = :dev AND estado = 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':dev' => $deviceId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Restaurar maquinas a operativo para puntos que no fueron programados
     * (puntos de la zona que tienen maquina fuera de servicio pero no se seleccionaron)
     */
    public function restaurarOperativoNoProgramados($puntosSeleccionados)
    {
        try {
            $this->conn->beginTransaction();

            // Obtener todos los puntos con maquina fuera de servicio
            $sqlObtener = "SELECT m.id_maquina, m.device_id, m.id_punto
                           FROM maquina m
                           WHERE m.activo_operativo = 0 AND m.estado = 1";
            $stmtObtener = $this->conn->prepare($sqlObtener);
            $stmtObtener->execute();
            $todasInactivas = $stmtObtener->fetchAll(PDO::FETCH_ASSOC);

            $restauradas = 0;
            $sqlRestaurar = "UPDATE maquina SET activo_operativo = 1, fecha_actualizacion = NOW() WHERE id_maquina = :id";

            foreach ($todasInactivas as $maq) {
                if (!in_array($maq['id_punto'], $puntosSeleccionados)) {
                    $stmtRestaurar = $this->conn->prepare($sqlRestaurar);
                    $stmtRestaurar->execute([':id' => $maq['id_maquina']]);
                    $restauradas++;
                }
            }

            $this->conn->commit();
            return ['status' => true, 'restauradas' => $restauradas];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['status' => false, 'msg' => $e->getMessage()];
        }
    }
}
