<?php
// app/models/programacion/programacionCrearModelo.php

if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

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
            1 => 'lunes', 2 => 'martes', 3 => 'miercoles',
            4 => 'jueves', 5 => 'viernes', 6 => 'sabado'
        ];

        // Obtener todos los puntos (Caché inicial)
        $puntosPorZona = [];
        foreach ($calendario as $dia => $config) {
            if (empty($config['zonas'])) continue;
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
                if (empty($calendario[$nombreDia])) continue;

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
                    if ($asignadosCount >= $maxServicios) break;

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

            // Preparar INSERT con los campos mínimos necesarios
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

                    // Obtener datos adicionales del punto (cliente, máquina, modalidad)
                    $datosAdicionales = $this->obtenerDatosComplementariosOrden($servicio['id_punto']);

                    if (!$datosAdicionales) {
                        $errores[] = "Punto {$servicio['id_punto']}: No se encontraron datos complementarios";
                        continue;
                    }

                    try {
                        $stmt->execute([
                            ':id_punto' => $servicio['id_punto'],
                            ':id_tecnico' => $servicio['id_tecnico'],
                            ':id_cliente' => $datosAdicionales['id_cliente'],
                            ':id_maquina' => $datosAdicionales['id_maquina'],
                            ':id_modalidad' => $datosAdicionales['id_modalidad'],
                            ':fecha_visita' => $servicio['fecha_visita']
                        ]);
                        $count++;
                    } catch (PDOException $e) {
                        // Si hay error (ej: duplicado), registrar pero continuar
                        $errores[] = "Error en punto {$servicio['id_punto']}: " . $e->getMessage();
                    }
                }
            }

            $this->conn->commit();

            $msg = "Se crearon $count órdenes programadas.";
            if (!empty($errores)) {
                $msg .= " Errores: " . count($errores);
            }

            return [
                "status" => true,
                "count" => $count,
                "msg" => $msg,
                "errores" => $errores
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ["status" => false, "msg" => $e->getMessage()];
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
                    (SELECT m.id_maquina 
                        FROM maquina m 
                        WHERE m.id_punto = p.id_punto 
                        AND m.estado = 1 
                        LIMIT 1) as id_maquina
                FROM punto p
                WHERE p.id_punto = :id_punto";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_punto' => $id_punto]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Auxiliar para obtener información de puntos
    public function obtenerInfoPuntos($ids)
    {
        if (empty($ids)) return [];

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
}
