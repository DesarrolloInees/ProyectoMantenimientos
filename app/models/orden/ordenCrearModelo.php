<?php
class ordenCrearModels
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // --- 1. CLIENTES ---
    public function obtenerClientes()
    {
        try {
            $sql = "SELECT id_cliente, nombre_cliente FROM cliente WHERE estado = 1 ORDER BY nombre_cliente ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ERROR en obtenerClientes: " . $e->getMessage());
            return [];
        }
    }

    // --- 2. TIPOS DE MANTENIMIENTO ---
    public function obtenerTiposMantenimiento()
    {
        $sql = "SELECT id_tipo_mantenimiento, nombre_completo FROM tipo_mantenimiento WHERE estado = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- 3. TÉCNICOS ---
    public function obtenerTecnicos()
    {
        $sql = "SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- 4. PUNTOS POR CLIENTE ---
    public function obtenerPuntosPorCliente($idCliente)
    {
        try {
            $sql = "SELECT p.id_punto, p.nombre_punto, p.codigo_1, 
                            COALESCE(p.id_modalidad, 1) as id_modalidad,
                            mo.nombre_modalidad
                    FROM punto p
                    LEFT JOIN modalidad_operativa mo ON p.id_modalidad = mo.id_modalidad
                    WHERE p.id_cliente = :id_cliente AND p.estado = 1 
                    ORDER BY p.nombre_punto ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id_cliente', $idCliente, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerPuntosPorCliente: " . $e->getMessage());
            return [];
        }
    }

    // --- 5. MÁQUINAS POR PUNTO ---
    public function obtenerMaquinasPorPunto($idPunto)
    {
        try {
            $sql = "SELECT m.id_maquina, m.device_id, m.id_tipo_maquina, tm.nombre_tipo_maquina 
                    FROM maquina m 
                    JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina 
                    WHERE m.id_punto = :id_punto AND m.estado = 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id_punto', $idPunto, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerMaquinasPorPunto: " . $e->getMessage());
            return [];
        }
    }

    // --- 6. CONSULTAR TARIFA ---
    public function consultarTarifa($idTipoMaq, $idTipoManto, $idModalidad, $fechaVisita)
    {
        try {
            $anio = !empty($fechaVisita) ? date('Y', strtotime($fechaVisita)) : date('Y');

            $sql = "SELECT precio FROM tarifa 
                    WHERE id_tipo_maquina = :tipo_maq
                        AND id_tipo_mantenimiento = :tipo_manto
                        AND id_modalidad = :modalidad
                        AND año_vigencia = :anio
                    LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':tipo_maq', $idTipoMaq, PDO::PARAM_INT);
            $stmt->bindParam(':tipo_manto', $idTipoManto, PDO::PARAM_INT);
            $stmt->bindParam(':modalidad', $idModalidad, PDO::PARAM_INT);
            $stmt->bindParam(':anio', $anio, PDO::PARAM_INT);

            $stmt->execute();
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($res === false) {
                return -1;
            }

            return $res['precio'];
        } catch (PDOException $e) {
            error_log("Error en consultarTarifa: " . $e->getMessage());
            return -1;
        }
    }

    // --- 7: OBTENER ESTADOS ---
    public function obtenerEstadosMaquina()
    {
        $sql = "SELECT id_estado, nombre_estado FROM estado_maquina ORDER BY id_estado ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- 8: OBTENER CALIFICACIONES ---
    public function obtenerCalificaciones()
    {
        $sql = "SELECT id_calificacion, nombre_calificacion FROM calificacion_servicio ORDER BY id_calificacion ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- 9: OBTENER LISTA DE REPUESTOS ---
    public function obtenerListaRepuestos()
    {
        $sql = "SELECT id_repuesto, 
                        CONCAT(
                            '[', 
                            COALESCE(NULLIF(codigo_referencia, ''), 'SIN CÓDIGO'), 
                            '] ', 
                            nombre_repuesto
                        ) AS nombre_repuesto 
                FROM repuesto 
                WHERE estado = 1 
                ORDER BY nombre_repuesto ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- 10: GUARDAR ORDEN (AQUÍ ESTÁ LA MAGIA DE LA DEVOLUCIÓN) ---
    public function guardarOrden($datos)
    {
        try {
            $this->conn->beginTransaction();

            // 1. LÓGICA DE VIÁTICOS INTELIGENTE
            $esFueraDelegacion = 0;
            $diasViaticos = 0;
            $valorViaticos = 0;

            $delegacionesPrincipales = [1, 2, 3, 4];
            $idDelegacionPunto = $this->obtenerIdDelegacionPunto($datos['id_punto']);

            if ($idDelegacionPunto > 0 && !in_array($idDelegacionPunto, $delegacionesPrincipales)) {
                $esFueraDelegacion = 1;

                $sqlCheck = "SELECT count(*) as total 
                            FROM ordenes_servicio 
                            WHERE id_tecnico = :id_tec 
                                AND fecha_visita = :fecha 
                                AND valor_viaticos > 0";

                if (!empty($datos['id_orden_previa'])) {
                    $sqlCheck .= " AND id_ordenes_servicio != " . intval($datos['id_orden_previa']);
                }

                $stmtCheck = $this->conn->prepare($sqlCheck);
                $stmtCheck->execute([
                    ':id_tec' => $datos['id_tecnico'],
                    ':fecha' => $datos['fecha']
                ]);
                $yaCobroHoy = $stmtCheck->fetch(PDO::FETCH_ASSOC)['total'];

                if ($yaCobroHoy > 0) {
                    $diasViaticos = 0;
                    $valorViaticos = 0;
                } else {
                    $diasViaticos = isset($datos['dias_viaticos']) ? intval($datos['dias_viaticos']) : 1;
                    $tarifa = $this->obtenerValorParametro('Recargo_Servicios_Interurbanos');
                    $valorViaticos = $diasViaticos * $tarifa;
                }
            }

            // 2. CALCULAR TIEMPO
            $tiempoCalculado = "00:00";
            if (!empty($datos['hora_entrada']) && !empty($datos['hora_salida'])) {
                try {
                    $d1 = new DateTime($datos['hora_entrada']);
                    $d2 = new DateTime($datos['hora_salida']);
                    if ($d2 < $d1) {
                        $d2->modify('+1 day');
                    }
                    $intervalo = $d1->diff($d2);
                    $tiempoCalculado = $intervalo->format('%H:%I');
                } catch (Exception $e) {
                    $tiempoCalculado = "00:00";
                }
            }

            // 3. DECISIÓN: ¿ACTUALIZAR O INSERTAR?
            $idOrden = 0;

            if (!empty($datos['id_orden_previa'])) {
                $sql = "UPDATE ordenes_servicio SET 
                            id_modalidad = :id_modalidad,
                            numero_remision = :remision,
                            id_maquina = :id_maquina,
                            id_tipo_mantenimiento = :id_manto,
                            valor_servicio = :valor,
                            es_fuera_delegacion = :es_fuera,
                            dias_viaticos = :dias,
                            valor_viaticos = :val_viaticos,
                            hora_entrada = :entrada,
                            hora_salida = :salida,
                            tiempo_servicio = :tiempo,
                            id_estado_maquina = :id_estado,
                            id_calificacion = :id_calif,
                            actividades_realizadas = :actividades,
                            estado = 1 
                        WHERE id_ordenes_servicio = :id_orden";

                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    ':id_orden' => $datos['id_orden_previa'],
                    ':id_modalidad' => $datos['id_modalidad'],
                    ':remision' => $datos['remision'],
                    ':id_maquina' => $datos['id_maquina'],
                    ':id_manto' => $datos['tipo_servicio'],
                    ':valor' => $datos['valor'],
                    ':es_fuera' => $esFueraDelegacion,
                    ':dias' => $diasViaticos,
                    ':val_viaticos' => $valorViaticos,
                    ':entrada' => $datos['hora_entrada'],
                    ':salida' => $datos['hora_salida'],
                    ':tiempo' => $tiempoCalculado,
                    ':id_estado' => $datos['estado'],
                    ':id_calif' => $datos['calif'],
                    ':actividades' => $datos['obs']
                ]);

                $idOrden = $datos['id_orden_previa'];
            } else {
                $sql = "INSERT INTO ordenes_servicio 
                        (id_cliente, id_punto, id_modalidad, numero_remision, fecha_visita, id_maquina, id_tecnico, id_tipo_mantenimiento, 
                        valor_servicio, es_fuera_delegacion, dias_viaticos, valor_viaticos, 
                        hora_entrada, hora_salida, tiempo_servicio, id_estado_maquina, id_calificacion, actividades_realizadas, estado, created_at) 
                        VALUES 
                        (:id_cliente, :id_punto, :id_modalidad, :remision, :fecha, :id_maquina, :id_tecnico, :id_manto, 
                        :valor, :es_fuera, :dias, :val_viaticos, 
                        :entrada, :salida, :tiempo, :id_estado, :id_calif, :actividades, 1, NOW())";

                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    ':id_cliente' => $datos['id_cliente'],
                    ':id_punto' => $datos['id_punto'],
                    ':id_modalidad' => $datos['id_modalidad'],
                    ':remision' => $datos['remision'],
                    ':fecha' => $datos['fecha'],
                    ':id_maquina' => $datos['id_maquina'],
                    ':id_tecnico' => $datos['id_tecnico'],
                    ':id_manto' => $datos['tipo_servicio'],
                    ':valor' => $datos['valor'],
                    ':es_fuera' => $esFueraDelegacion,
                    ':dias' => $diasViaticos,
                    ':val_viaticos' => $valorViaticos,
                    ':entrada' => $datos['hora_entrada'],
                    ':salida' => $datos['hora_salida'],
                    ':tiempo' => $tiempoCalculado,
                    ':id_estado' => $datos['estado'],
                    ':id_calif' => $datos['calif'],
                    ':actividades' => $datos['obs']
                ]);

                $idOrden = $this->conn->lastInsertId();
            }

            // ACCIONES POSTERIORES
            if (!empty($datos['remision'])) {
                $this->marcarRemisionComoUsada($datos['remision'], $idOrden, $datos['id_tecnico']);
            }

            $this->actualizarInfoMantenimientoPunto($datos['id_punto']);

            // 3. PROCESAR REPUESTOS Y DEVOLUCIONES
            if (!empty($datos['json_repuestos'])) {
                $repuestos = json_decode($datos['json_repuestos'], true);

                if (is_array($repuestos) && count($repuestos) > 0) {

                    // Limpiamos los previos si era actualización
                    if (!empty($datos['id_orden_previa'])) {
                        $stmtDel = $this->conn->prepare("DELETE FROM orden_servicio_repuesto WHERE id_orden_servicio = ?");
                        $stmtDel->execute([$idOrden]);

                        // 🔥 NUEVO: Limpiamos devoluciones previas
                        $stmtDelDev = $this->conn->prepare("DELETE FROM control_devolucion_repuestos WHERE id_orden_servicio = ?");
                        $stmtDelDev->execute([$idOrden]);
                    }

                    $sqlRep = "INSERT INTO orden_servicio_repuesto (id_orden_servicio, id_repuesto, origen, cantidad) VALUES (?, ?, ?, ?)";
                    $stmtRep = $this->conn->prepare($sqlRep);

                    // 🔥 NUEVO: Preparar consultas para las devoluciones
                    $stmtCheckDev = $this->conn->prepare("SELECT requiere_devolucion FROM repuesto WHERE id_repuesto = ?");
                    $sqlDev = "INSERT INTO control_devolucion_repuestos (id_tecnico, id_orden_servicio, id_repuesto, cantidad, estado_devolucion) VALUES (?, ?, ?, ?, 'Pendiente')";
                    $stmtDev = $this->conn->prepare($sqlDev);

                    foreach ($repuestos as $rep) {
                        if (isset($rep['id']) && isset($rep['origen'])) {
                            $cant = isset($rep['cantidad']) && $rep['cantidad'] > 0 ? $rep['cantidad'] : 1;

                            // Guardamos uso normal
                            $stmtRep->execute([$idOrden, $rep['id'], $rep['origen'], $cant]);

                            if (empty($datos['id_orden_previa']) && $rep['origen'] === 'INEES') {
                                $this->descontarDelInventario($datos['id_tecnico'], $rep['id'], $cant);
                            }

                            // 🔥 NUEVO: Verificar si exige devolución
                            $stmtCheckDev->execute([$rep['id']]);
                            $reqDev = $stmtCheckDev->fetchColumn();

                            if ($reqDev == 1) {
                                // Lo mandamos a la tabla de control
                                $stmtDev->execute([$datos['id_tecnico'], $idOrden, $rep['id'], $cant]);
                            }
                        }
                    }
                }
            }

            $this->conn->commit();
            return $idOrden;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("ERROR SQL fila fallida. Datos: " . json_encode($datos) . " | Error real: " . $e->getMessage());
            return false;
        }
    }

    // --- 11. ACTUALIZAR MODALIDAD DEL PUNTO ---
    public function actualizarModalidadPunto($idPunto, $idModalidad)
    {
        try {
            $sql = "UPDATE punto SET id_modalidad = :modalidad WHERE id_punto = :id_punto";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':modalidad' => $idModalidad,
                ':id_punto' => $idPunto
            ]);
        } catch (PDOException $e) {
            error_log("Error actualizando modalidad punto: " . $e->getMessage());
            return false;
        }
    }

    // --- 12. ACTUALIZAR INFO DE MANTENIMIENTO EN PUNTO (Fecha Última Visita y Tipo Último Mantenimiento) ---
    public function actualizarInfoMantenimientoPunto($idPunto)
    {
        try {
            $sql = "UPDATE punto p
                    JOIN (
                        SELECT id_punto, fecha_visita, id_tipo_mantenimiento
                        FROM ordenes_servicio
                        WHERE id_punto = :id_punto_b
                            AND id_tipo_mantenimiento != 4 
                        ORDER BY fecha_visita DESC, id_ordenes_servicio DESC
                        LIMIT 1
                    ) AS ultima_real ON p.id_punto = ultima_real.id_punto
                    SET p.fecha_ultima_visita = ultima_real.fecha_visita,
                        p.id_ultimo_tipo_mantenimiento = ultima_real.id_tipo_mantenimiento
                    WHERE p.id_punto = :id_punto_a";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id_punto_b' => $idPunto,
                ':id_punto_a' => $idPunto
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Error actualizando info mantenimiento punto (Smart): " . $e->getMessage());
            return false;
        }
    }

    // --- 13. OBTENER REMISIONES DISPONIBLES POR TÉCNICO (Solo las que estén en estado 'DISPONIBLE') ---
    public function obtenerRemisionesDisponibles($idTecnico)
    {
        $sql = "SELECT id_control, numero_remision 
                FROM control_remisiones 
                WHERE id_tecnico = ? 
                AND id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'DISPONIBLE' LIMIT 1)
                ORDER BY CAST(numero_remision AS UNSIGNED) ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$idTecnico]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- 14. MARCAR REMISIÓN COMO USADA ---
    public function marcarRemisionComoUsada($numeroRemision, $idOrden, $idTecnico)
    {
        $sql = "UPDATE control_remisiones 
                SET id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'USADA' LIMIT 1), 
                    id_orden_servicio = ?, 
                    fecha_uso = NOW() 
                WHERE numero_remision = ? AND id_tecnico = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$idOrden, $numeroRemision, $idTecnico]);
    }

    // --- 15. VERIFICAR SI LA REMISIÓN ESTÁ REALMENTE DISPONIBLE (No solo por ID, sino que su estado sea 'DISPONIBLE') ---
    public function verificarRemisionDisponible($numeroRemision, $idTecnico)
    {
        try {
            $sql = "SELECT e.nombre_estado, cr.id_orden_servicio 
                    FROM control_remisiones cr
                    INNER JOIN estados_remision e ON cr.id_estado = e.id_estado
                    WHERE cr.numero_remision = :remision 
                    AND cr.id_tecnico = :tecnico 
                    LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':remision' => $numeroRemision,
                ':tecnico' => $idTecnico
            ]);

            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$resultado) {
                return ['disponible' => false, 'mensaje' => 'Remisión no encontrada en el sistema'];
            }

            if ($resultado['nombre_estado'] === 'USADA') {
                return ['disponible' => false, 'mensaje' => 'Remisión ya fue usada en orden #' . $resultado['id_orden_servicio']];
            }

            if ($resultado['nombre_estado'] === 'ANULADA' || $resultado['nombre_estado'] === 'ELIMINADO') {
                return ['disponible' => false, 'mensaje' => 'Esta remisión está ' . $resultado['nombre_estado']];
            }

            return ['disponible' => true, 'mensaje' => 'Remisión disponible'];
        } catch (PDOException $e) {
            error_log("Error verificando remisión: " . $e->getMessage());
            return ['disponible' => false, 'mensaje' => 'Error al verificar remisión'];
        }
    }

    // --- 16. OBTENER DÍAS FESTIVOS (Para Validación en el Frontend) ---
    public function obtenerFestivos()
    {
        try {
            $sql = "SELECT fecha FROM dias_festivos ORDER BY fecha ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return [];
        }
    }

    // --- 17. OBTENER INVENTARIO DEL TÉCNICO (Solo los repuestos con cantidad > 0) ---
    public function obtenerInventarioTecnico($idTecnico)
    {
        try {
            $sql = "SELECT i.id_repuesto, 
                            CASE 
                                WHEN r.codigo_referencia IS NOT NULL AND r.codigo_referencia != '' 
                                THEN CONCAT(r.codigo_referencia, ' - ', r.nombre_repuesto)
                                ELSE r.nombre_repuesto
                            END AS nombre_repuesto, 
                            r.codigo_referencia, 
                            i.cantidad_actual 
                    FROM inventario_tecnico i
                    INNER JOIN repuesto r ON i.id_repuesto = r.id_repuesto
                    WHERE i.id_tecnico = :id_tec AND i.estado = 1 AND i.cantidad_actual > 0
                    ORDER BY r.nombre_repuesto ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id_tec', $idTecnico);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // --- 18. DESCONTAR DEL INVENTARIO DEL TÉCNICO (Cuando se usa un repuesto del inventario propio) ---
    public function descontarDelInventario($idTecnico, $idRepuesto, $cantidad)
    {
        try {
            $sql = "UPDATE inventario_tecnico 
                    SET cantidad_actual = cantidad_actual - :cant 
                    WHERE id_tecnico = :id_tec AND id_repuesto = :id_rep";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':cant', $cantidad);
            $stmt->bindParam(':id_tec', $idTecnico);
            $stmt->bindParam(':id_rep', $idRepuesto);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error descontando inventario: " . $e->getMessage());
        }
    }

    // --- AUXILIAR: Obtener delegación del punto (Para lógica de viáticos) ---
    private function obtenerIdDelegacionPunto($idPunto)
    {
        try {
            $sql = "SELECT id_delegacion FROM punto WHERE id_punto = :id LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id' => $idPunto]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ? intval($res['id_delegacion']) : 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    // --- AUXILIAR: Obtener valor de un parámetro por su clave (Para lógica de viáticos) ---
    private function obtenerValorParametro($clave)
    {
        try {
            $sql = "SELECT valor FROM parametros WHERE clave = :clave LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':clave' => $clave]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ? floatval($res['valor']) : 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    // --- 19. OBTENER PROGRAMACIÓN DIARIA (Órdenes programadas para una fecha específica, con detalles completos) ---
    public function obtenerProgramacionDiaria($fecha)
    {
        try {
            $sql = "SELECT 
                        os.id_ordenes_servicio, 
                        os.id_cliente, 
                        os.id_punto, 
                        os.id_tecnico, 
                        os.id_maquina, 
                        os.id_tipo_mantenimiento, 
                        os.id_modalidad,
                        os.fecha_visita,
                        c.nombre_cliente,
                        p.nombre_punto,
                        t.nombre_tecnico,
                        m.device_id,
                        tm.nombre_tipo_maquina
                    FROM ordenes_servicio os
                    INNER JOIN cliente c ON os.id_cliente = c.id_cliente
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    INNER JOIN tecnico t ON os.id_tecnico = t.id_tecnico
                    LEFT JOIN maquina m ON os.id_maquina = m.id_maquina
                    LEFT JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                    WHERE os.fecha_visita = :fecha 
                    AND os.estado = 2 
                    ORDER BY t.nombre_tecnico ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':fecha' => $fecha]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function actualizarFechaModificacion($idOrden)
    {
        // Usar archivo JSON o sesión
        $this->actualizarFechaModificacionJSON($idOrden);
    }

    private function actualizarFechaModificacionJSON($idOrden)
    {
        $file = __DIR__ . '/../temp/modificaciones.json';
        $data = [];
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
        }
        $data[$idOrden] = date('Y-m-d H:i:s');
        file_put_contents($file, json_encode($data));
    }
}
