<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

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
                o.valor_viaticos,
                o.dias_viaticos,
                o.es_fuera_delegacion,
                o.actividades_realizadas as que_se_hizo,
                o.tiene_novedad,
                o.detalle_novedad,
                o.repuestos_tecnico,
                
                -- MULTIPLES NOVEDADES (NOMBRES Y IDS)
                IFNULL(
                    (SELECT GROUP_CONCAT(tn.nombre_novedad SEPARATOR ', ')
                    FROM orden_servicio_novedad osn
                    JOIN tipo_novedad tn ON osn.id_tipo_novedad = tn.id_tipo_novedad
                    WHERE osn.id_orden_servicio = o.id_ordenes_servicio)
                , '') as nombres_novedades,
                
                IFNULL(
                    (SELECT GROUP_CONCAT(osn.id_tipo_novedad SEPARATOR ',')
                    FROM orden_servicio_novedad osn
                    WHERE osn.id_orden_servicio = o.id_ordenes_servicio)
                , '') as ids_novedades,
                
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
                COALESCE(p_directo.id_delegacion, p_maq.id_delegacion) as id_delegacion,

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

        $idsOrdenes = array_column($resultados, 'id_ordenes_servicio');

        if (!empty($idsOrdenes)) {
            $placeholders = implode(',', array_fill(0, count($idsOrdenes), '?'));

            $sqlRepTodos = "SELECT osr.id_orden_servicio, r.id_repuesto as id, r.nombre_repuesto as nombre, osr.origen, osr.cantidad 
                            FROM orden_servicio_repuesto osr
                            JOIN repuesto r ON osr.id_repuesto = r.id_repuesto
                            WHERE osr.id_orden_servicio IN ($placeholders)";

            $stmtRepTodos = $this->conn->prepare($sqlRepTodos);
            $stmtRepTodos->execute($idsOrdenes);
            $todosLosRepuestos = $stmtRepTodos->fetchAll(PDO::FETCH_ASSOC);

            $repuestosAgrupados = [];
            foreach ($todosLosRepuestos as $rep) {
                $idO = $rep['id_orden_servicio'];
                unset($rep['id_orden_servicio']);
                $repuestosAgrupados[$idO][] = $rep;
            }

            foreach ($resultados as &$row) {
                $idOrden = $row['id_ordenes_servicio'];
                $row['repuestos_json'] = isset($repuestosAgrupados[$idOrden]) ? json_encode($repuestosAgrupados[$idOrden]) : '[]';
            }
        } else {
            foreach ($resultados as &$row) {
                $row['repuestos_json'] = '[]';
            }
        }

        return $resultados;
    }

    private function convertirTextoAJSON($texto)
    {
        $arrayRepuestos = [];

        if (empty($texto) || trim($texto) === '') {
            return '[]';
        }

        $palabrasIgnorar = ['NO', 'NINGUNO', 'NINGUNA', 'SIN REPUESTOS', 'N/A', 'NA', '.', '-', '0', 'VACIO', ''];
        $textoTemp = str_replace(' (PROSEGUR)', '_(PROSEGUR)', $texto);
        $textoTemp = str_replace(' (INEES)', '_(INEES)', $textoTemp);
        $items = explode(',', $textoTemp);

        foreach ($items as $item) {
            $item = str_replace('_(PROSEGUR)', ' (PROSEGUR)', $item);
            $item = str_replace('_(INEES)', ' (INEES)', $item);
            $itemLimpio = trim($item);

            if (empty($itemLimpio) || in_array(strtoupper($itemLimpio), $palabrasIgnorar)) {
                continue;
            }

            $cantidad = 1;
            if (preg_match('/\(x(\d+)\)$/i', $itemLimpio, $matches)) {
                $cantidad = intval($matches[1]);
                $itemLimpio = trim(preg_replace('/\(x\d+\)$/i', '', $itemLimpio));
            }

            $origen = 'INEES';
            $nombre = $itemLimpio;

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
                'cantidad' => $cantidad
            ];
        }

        return json_encode($arrayRepuestos, JSON_UNESCAPED_UNICODE);
    }

    private function buscarIdRepuestoPorNombre($nombre)
    {
        try {
            if (empty($nombre))
                return '';

            $sql = "SELECT id_repuesto FROM repuesto WHERE LOWER(nombre_repuesto) = LOWER(?) LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$nombre]);
            $id = $stmt->fetchColumn();

            if ($id)
                return $id;

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
        $sql = "SELECT 
                    id_tecnico, 
                    estado,
                    CASE 
                        WHEN estado = 1 THEN nombre_tecnico 
                        ELSE CONCAT(nombre_tecnico, ' (INACTIVO)') 
                    END as nombre_tecnico 
                FROM tecnico 
                ORDER BY estado DESC, nombre_tecnico ASC";

        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
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

    public function obtenerPrecioTarifa($id_tipo_maquina, $id_tipo_mantenimiento, $id_modalidad, $anio)
    {
        $anioVigencia = $anio ? $anio : date('Y');

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

        if ($fila === false) {
            return -1;
        }

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
            return [];
        }
    }

    public function obtenerRemisionesDisponiblesPorTecnico($idTecnico, $remisionActual = null)
    {
        $sql = "SELECT numero_remision 
                FROM control_remisiones 
                WHERE id_tecnico = ? 
                AND id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'DISPONIBLE' LIMIT 1)
                ORDER BY CAST(numero_remision AS UNSIGNED) ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$idTecnico]);
        $disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($remisionActual)) {
            $yaEsta = array_filter($disponibles, fn($r) => $r['numero_remision'] == $remisionActual);
            if (empty($yaEsta)) {
                array_unshift($disponibles, ['numero_remision' => $remisionActual]);
            }
        }

        return $disponibles;
    }

    private function obtenerValorParametro($clave)
    {
        try {
            $sql = "SELECT valor FROM parametros WHERE clave = :clave LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':clave' => $clave]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ? floatval($res['valor']) : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    private function obtenerIdDelegacionPunto($idPunto)
    {
        try {
            $sql = "SELECT id_delegacion FROM punto WHERE id_punto = :id LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id' => $idPunto]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ? intval($res['id_delegacion']) : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    // ==========================================
    // 🔥 ACTUALIZACIÓN: RECIBE EL ROL PARA SEGURIDAD
    // ==========================================
    public function actualizarOrdenFull($id, $datos, $rolUsuario = 0)
    {
        try {
            $this->conn->beginTransaction();

            $datos['entrada'] = $this->normalizarHora($datos['entrada'] ?? '');
            $datos['salida'] = $this->normalizarHora($datos['salida'] ?? '');

            $sqlCheck = "SELECT numero_remision, id_tecnico, id_punto, fecha_visita, id_maquina, id_tipo_mantenimiento, valor_servicio, id_modalidad FROM ordenes_servicio WHERE id_ordenes_servicio = ?";
            $stmtCheck = $this->conn->prepare($sqlCheck);
            $stmtCheck->execute([$id]);
            $actual = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            $nuevaRemision = $datos['remision'] ?? '';
            $nuevoTecnico = $datos['id_tecnico'] ?? $actual['id_tecnico'];
            $nuevoPunto = $datos['id_punto'] ?? $actual['id_punto'];
            $nuevaFecha = $datos['fecha_individual'] ?? $actual['fecha_visita'] ?? date('Y-m-d');
            $fechaUso = $nuevaFecha . ' ' . ($datos['entrada'] ?? '00:00:00');

            if ($actual && ($actual['numero_remision'] != $nuevaRemision || $actual['id_tecnico'] != $nuevoTecnico)) {
                $sqlLiberar = "UPDATE control_remisiones 
                                SET id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'DISPONIBLE' LIMIT 1), 
                                    id_orden_servicio = NULL, 
                                    fecha_uso = NULL 
                                WHERE id_orden_servicio = ?";
                $this->conn->prepare($sqlLiberar)->execute([$id]);

                if (!empty($nuevaRemision)) {
                    $sqlOcupar = "UPDATE control_remisiones 
                                    SET id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'USADA' LIMIT 1), 
                                        id_orden_servicio = ?, 
                                        fecha_uso = ? 
                                    WHERE numero_remision = ? AND id_tecnico = ?";

                    $this->conn->prepare($sqlOcupar)->execute([
                        $id,
                        $fechaUso,
                        $nuevaRemision,
                        $nuevoTecnico
                    ]);
                }
            }

            $delegacionesPrincipales = [1, 2, 3, 4];
            $idDelegacionPunto = $this->obtenerIdDelegacionPunto($nuevoPunto);

            $idModalidad = isset($datos['id_modalidad']) ? intval($datos['id_modalidad']) : ($actual['id_modalidad'] ?? 1);

            $esFueraDelegacion = 0;
            $diasViaticos = 0;
            $valorViaticos = 0;

            if ($idModalidad == 2) {
                if ($idDelegacionPunto > 0 && !in_array($idDelegacionPunto, $delegacionesPrincipales)) {
                    $esFueraDelegacion = 1;

                    $sqlCheckViat = "SELECT COUNT(*) as total
                                    FROM ordenes_servicio 
                                    WHERE id_tecnico = ? 
                                        AND fecha_visita = ? 
                                        AND valor_viaticos > 0 
                                        AND id_ordenes_servicio != ?";
                    $stmtViat = $this->conn->prepare($sqlCheckViat);
                    $stmtViat->execute([$nuevoTecnico, $nuevaFecha, $id]);
                    $yaCobroHoy = $stmtViat->fetch(PDO::FETCH_ASSOC)['total'];

                    if ($yaCobroHoy == 0) {
                        $diasViaticos = isset($datos['dias_viaticos']) ? intval($datos['dias_viaticos']) : 1;
                        $tarifaViat = $this->obtenerValorParametro('Recargo_Servicios_Interurbanos');
                        $valorViaticos = $diasViaticos * $tarifaViat;
                    }
                }
            }

            // =======================================================
            // 🛡️ LÓGICA DE PRECIO BLINDADA EN EL BACKEND
            // =======================================================
            $valorServicio = $datos['valor'] ?? $actual['valor_servicio'];

            $idMaqUso = $datos['id_maquina'] ?? $actual['id_maquina'];
            $idMantoUso = $datos['id_manto'] ?? $actual['id_tipo_mantenimiento'];

            $cambioPunto = ($actual['id_punto'] != $nuevoPunto);
            $cambioFecha = ($actual['fecha_visita'] != $nuevaFecha);
            $cambioModalidad = ($actual['id_modalidad'] != $idModalidad);
            $cambioMaquina = ($actual['id_maquina'] != $idMaqUso);
            $cambioManto = ($actual['id_tipo_mantenimiento'] != $idMantoUso);

            // 🔥 Si es Rol 5, forzamos al servidor a ignorar lo que envió la vista y calcular todo él mismo
            if ($cambioPunto || $cambioFecha || $cambioModalidad || $cambioMaquina || $cambioManto || $rolUsuario === 5) {

                $stmtTM = $this->conn->prepare("SELECT id_tipo_maquina FROM maquina WHERE id_maquina = ?");
                $stmtTM->execute([$idMaqUso]);
                $idTipoMaq = $stmtTM->fetchColumn();

                $anioUso = date('Y', strtotime($nuevaFecha));
                $precioLimpio = $this->obtenerPrecioTarifa($idTipoMaq, $idMantoUso, $idModalidad, $anioUso);

                if ($precioLimpio !== -1) {
                    $valorServicio = $precioLimpio;
                } else {
                    // Si no existe tarifa y es rol 5 pero NO hizo cambios que alteren precio, dejamos la actual
                    if ($rolUsuario === 5 && !$cambioPunto && !$cambioFecha && !$cambioModalidad && !$cambioMaquina && !$cambioManto) {
                        $valorServicio = $actual['valor_servicio'];
                    } else {
                        $valorServicio = $precioLimpio;
                    }
                }
            }

            // -----------------------------------------------------------------
            // UPDATE TABLA PRINCIPAL
            // -----------------------------------------------------------------
            $sql = "UPDATE ordenes_servicio SET 
                        id_cliente = ?, id_punto = ?, id_maquina = ?, id_modalidad = ?,
                        numero_remision = ?, id_tecnico = ?, id_tipo_mantenimiento = ?, 
                        id_estado_maquina = ?, id_calificacion = ?, hora_entrada = ?, 
                        hora_salida = ?, tiempo_servicio = ?, valor_servicio = ?,
                        actividades_realizadas = ?, tiene_novedad = ?,
                        es_fuera_delegacion = ?, dias_viaticos = ?, valor_viaticos = ?,
                        fecha_visita = ?
                    WHERE id_ordenes_servicio = ?";

            $stmt = $this->conn->prepare($sql);

            $ejecucion = $stmt->execute([
                $datos['id_cliente'] ?? null,
                $nuevoPunto,
                $datos['id_maquina'] ?? null,
                $idModalidad,
                $nuevaRemision,
                $nuevoTecnico,
                $datos['id_manto'] ?? null,
                $datos['id_estado'] ?? null,
                $datos['id_calif'] ?? null,
                $datos['entrada'] ?? '00:00',
                $datos['salida'] ?? '00:00',
                $datos['tiempo'] ?? '00:00',
                $valorServicio,         // 🔥 Precio 100% verificado por backend
                $datos['obs'] ?? '',
                $datos['tiene_novedad'] ?? 0,
                $esFueraDelegacion,
                $diasViaticos,
                $valorViaticos,
                $nuevaFecha,
                $id
            ]);

            if (!$ejecucion) {
                throw new Exception("Error al ejecutar UPDATE en ordenes_servicio");
            }

            if (!empty($nuevoPunto)) {
                $this->actualizarInfoMantenimientoPunto($nuevoPunto);
            }

            if ($actual['id_tecnico'] != $nuevoTecnico) {
                $sqlUpdDevTech = "UPDATE control_devolucion_repuestos SET id_tecnico = ? WHERE id_orden_servicio = ?";
                $this->conn->prepare($sqlUpdDevTech)->execute([$nuevoTecnico, $id]);
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("CRITICAL ERROR actualizarOrdenFull: " . $e->getMessage());
            return false;
        }
    }

    private function actualizarInfoMantenimientoPunto($idPunto)
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
            return false;
        }
    }

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

    public function obtenerRepuestosDeOrden($idOrden)
    {
        $sql = "SELECT id_repuesto as id, cantidad 
                FROM orden_servicio_repuesto 
                WHERE id_orden_servicio = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$idOrden]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarOrdenesFiltros($filtros)
    {
        $sql = "SELECT 
                o.id_ordenes_servicio,
                o.numero_remision,
                o.fecha_visita,
                o.hora_entrada,
                o.hora_salida,
                o.tiempo_servicio,
                o.valor_servicio,
                o.valor_viaticos,
                o.dias_viaticos,
                o.es_fuera_delegacion,
                o.actividades_realizadas as que_se_hizo,
                o.tiene_novedad,
                o.detalle_novedad,
                o.repuestos_tecnico,
                
                IFNULL(
                    (SELECT GROUP_CONCAT(tn.nombre_novedad SEPARATOR ', ')
                    FROM orden_servicio_novedad osn
                    JOIN tipo_novedad tn ON osn.id_tipo_novedad = tn.id_tipo_novedad
                    WHERE osn.id_orden_servicio = o.id_ordenes_servicio)
                , '') as nombres_novedades,
                
                IFNULL(
                    (SELECT GROUP_CONCAT(osn.id_tipo_novedad SEPARATOR ',')
                    FROM orden_servicio_novedad osn
                    WHERE osn.id_orden_servicio = o.id_ordenes_servicio)
                , '') as ids_novedades,

                o.id_maquina,
                m.device_id,
                tm.nombre_tipo_maquina,
                tm.id_tipo_maquina,
                
                COALESCE(o.id_cliente, c_maq.id_cliente) as id_cliente,
                COALESCE(c_directo.nombre_cliente, c_maq.nombre_cliente) as nombre_cliente,
                
                COALESCE(o.id_punto, p_maq.id_punto) as id_punto,
                COALESCE(p_directo.nombre_punto, p_maq.nombre_punto) as nombre_punto,
                
                COALESCE(d_directo.nombre_delegacion, d_maq.nombre_delegacion) as delegacion,
                COALESCE(p_directo.id_delegacion, p_maq.id_delegacion) as id_delegacion,

                COALESCE(o.id_modalidad, 1) as id_modalidad,
                CASE 
                    WHEN o.id_modalidad = 1 THEN 'URBANO'
                    WHEN o.id_modalidad = 2 THEN 'INTERURBANO'
                    ELSE 'NO DEFINIDO'
                END as tipo_zona,

                o.id_tecnico, t.nombre_tecnico,
                o.id_tipo_mantenimiento as id_manto, tman.nombre_completo as tipo_servicio,
                o.id_estado_maquina as id_estado, em.nombre_estado as estado_maquina,
                o.id_calificacion as id_calif, cal.nombre_calificacion,

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

        $params = [];

        if (!empty($filtros['remision'])) {
            $sql .= " AND o.numero_remision LIKE ?";
            $params[] = "%" . $filtros['remision'] . "%";
        }

        if (!empty($filtros['id_cliente'])) {
            $sql .= " AND (o.id_cliente = ? OR c_maq.id_cliente = ?)";
            $params[] = $filtros['id_cliente'];
            $params[] = $filtros['id_cliente'];
        }

        if (!empty($filtros['id_punto'])) {
            $sql .= " AND (o.id_punto = ? OR m.id_punto = ?)";
            $params[] = $filtros['id_punto'];
            $params[] = $filtros['id_punto'];
        }

        if (!empty($filtros['fecha_inicio'])) {
            $sql .= " AND o.fecha_visita >= ?";
            $params[] = $filtros['fecha_inicio'];
        }

        if (!empty($filtros['fecha_fin'])) {
            $sql .= " AND o.fecha_visita <= ?";
            $params[] = $filtros['fecha_fin'] . ' 23:59:59';
        }

        if (!empty($filtros['id_delegacion'])) {
            $sql .= " AND (p_directo.id_delegacion = ? OR p_maq.id_delegacion = ?)";
            $params[] = $filtros['id_delegacion'];
            $params[] = $filtros['id_delegacion'];
        }

        $sql .= " ORDER BY o.fecha_visita DESC, o.id_ordenes_servicio DESC LIMIT 100";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $idsOrdenes = array_column($resultados, 'id_ordenes_servicio');

        if (!empty($idsOrdenes)) {
            $placeholders = implode(',', array_fill(0, count($idsOrdenes), '?'));

            $sqlRepTodos = "SELECT osr.id_orden_servicio, r.id_repuesto as id, r.nombre_repuesto as nombre, osr.origen, osr.cantidad 
                            FROM orden_servicio_repuesto osr
                            JOIN repuesto r ON osr.id_repuesto = r.id_repuesto
                            WHERE osr.id_orden_servicio IN ($placeholders)";

            $stmtRepTodos = $this->conn->prepare($sqlRepTodos);
            $stmtRepTodos->execute($idsOrdenes);
            $todosLosRepuestos = $stmtRepTodos->fetchAll(PDO::FETCH_ASSOC);

            $repuestosAgrupados = [];
            foreach ($todosLosRepuestos as $rep) {
                $idO = $rep['id_orden_servicio'];
                unset($rep['id_orden_servicio']);
                $repuestosAgrupados[$idO][] = $rep;
            }

            foreach ($resultados as &$row) {
                $idOrden = $row['id_ordenes_servicio'];
                $row['repuestos_json'] = isset($repuestosAgrupados[$idOrden]) ? json_encode($repuestosAgrupados[$idOrden]) : '[]';
            }
        } else {
            foreach ($resultados as &$row) {
                $row['repuestos_json'] = '[]';
            }
        }

        return $resultados;
    }

    public function agregarRepuestoRealTime($idOrden, $idRepuesto, $cantidad, $origen, $idTecnico)
    {
        try {
            $this->conn->beginTransaction();

            $nuevoStockVisual = 0;

            if ($origen === 'INEES') {
                $sqlStock = "SELECT cantidad_actual FROM inventario_tecnico 
                                WHERE id_tecnico = ? AND id_repuesto = ? FOR UPDATE";
                $stmt = $this->conn->prepare($sqlStock);
                $stmt->execute([$idTecnico, $idRepuesto]);
                $stockActual = $stmt->fetchColumn();

                if ($stockActual === false || $stockActual < $cantidad) {
                    $this->conn->rollBack();
                    return ['status' => 'error', 'msg' => 'Stock insuficiente en la maleta del técnico.'];
                }

                $sqlUpdInv = "UPDATE inventario_tecnico 
                                SET cantidad_actual = cantidad_actual - ? 
                                WHERE id_tecnico = ? AND id_repuesto = ?";
                $this->conn->prepare($sqlUpdInv)->execute([$cantidad, $idTecnico, $idRepuesto]);

                $nuevoStockVisual = $stockActual - $cantidad;
            }

            $sqlCheck = "SELECT cantidad FROM orden_servicio_repuesto 
                            WHERE id_orden_servicio = ? AND id_repuesto = ? AND origen = ?";
            $stmtCheck = $this->conn->prepare($sqlCheck);
            $stmtCheck->execute([$idOrden, $idRepuesto, $origen]);
            $cantOrden = $stmtCheck->fetchColumn();

            if ($cantOrden !== false) {
                $sqlUpdOrden = "UPDATE orden_servicio_repuesto 
                                SET cantidad = cantidad + ? 
                                WHERE id_orden_servicio = ? AND id_repuesto = ? AND origen = ?";
                $this->conn->prepare($sqlUpdOrden)->execute([$cantidad, $idOrden, $idRepuesto, $origen]);
            } else {
                $sqlInsOrden = "INSERT INTO orden_servicio_repuesto 
                                (id_orden_servicio, id_repuesto, origen, cantidad) 
                                VALUES (?, ?, ?, ?)";
                $this->conn->prepare($sqlInsOrden)->execute([$idOrden, $idRepuesto, $origen, $cantidad]);
            }

            $stmtCheckDev = $this->conn->prepare("SELECT requiere_devolucion FROM repuesto WHERE id_repuesto = ?");
            $stmtCheckDev->execute([$idRepuesto]);
            $reqDev = $stmtCheckDev->fetchColumn();

            if ($reqDev == 1) {
                $sqlCheckCD = "SELECT cantidad FROM control_devolucion_repuestos WHERE id_orden_servicio = ? AND id_repuesto = ?";
                $stmtCD = $this->conn->prepare($sqlCheckCD);
                $stmtCD->execute([$idOrden, $idRepuesto]);
                $cantCD = $stmtCD->fetchColumn();

                if ($cantCD !== false) {
                    $sqlUpdCD = "UPDATE control_devolucion_repuestos SET cantidad = cantidad + ? WHERE id_orden_servicio = ? AND id_repuesto = ?";
                    $this->conn->prepare($sqlUpdCD)->execute([$cantidad, $idOrden, $idRepuesto]);
                } else {
                    $sqlInsCD = "INSERT INTO control_devolucion_repuestos (id_tecnico, id_orden_servicio, id_repuesto, cantidad, estado_devolucion) VALUES (?, ?, ?, ?, 'Pendiente')";
                    $this->conn->prepare($sqlInsCD)->execute([$idTecnico, $idOrden, $idRepuesto, $cantidad]);
                }
            }

            $this->conn->commit();
            return ['status' => 'ok', 'msg' => 'Agregado correctamente', 'nuevo_stock' => $nuevoStockVisual];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['status' => 'error', 'msg' => 'Error BD: ' . $e->getMessage()];
        }
    }

    public function eliminarRepuestoRealTime($idOrden, $idRepuesto, $origen, $idTecnico)
    {
        try {
            $this->conn->beginTransaction();

            $sqlGet = "SELECT cantidad FROM orden_servicio_repuesto 
                        WHERE id_orden_servicio = ? AND id_repuesto = ? AND origen = ?";
            $stmt = $this->conn->prepare($sqlGet);
            $stmt->execute([$idOrden, $idRepuesto, $origen]);
            $cantidad = $stmt->fetchColumn();

            if (!$cantidad) {
                $this->conn->rollBack();
                return ['status' => 'error', 'msg' => 'El repuesto no existe en esta orden.'];
            }

            if ($origen === 'INEES') {
                $sqlDev = "INSERT INTO inventario_tecnico (id_tecnico, id_repuesto, cantidad_actual, estado) 
                            VALUES (?, ?, ?, 1) 
                            ON DUPLICATE KEY UPDATE cantidad_actual = cantidad_actual + ?";
                $this->conn->prepare($sqlDev)->execute([$idTecnico, $idRepuesto, $cantidad, $cantidad]);
            }

            $sqlDel = "DELETE FROM orden_servicio_repuesto 
                        WHERE id_orden_servicio = ? AND id_repuesto = ? AND origen = ?";
            $this->conn->prepare($sqlDel)->execute([$idOrden, $idRepuesto, $origen]);

            $stmtCheckDev = $this->conn->prepare("SELECT requiere_devolucion FROM repuesto WHERE id_repuesto = ?");
            $stmtCheckDev->execute([$idRepuesto]);
            $reqDev = $stmtCheckDev->fetchColumn();

            if ($reqDev == 1) {
                $sqlUpdCD = "UPDATE control_devolucion_repuestos SET cantidad = cantidad - ? WHERE id_orden_servicio = ? AND id_repuesto = ?";
                $this->conn->prepare($sqlUpdCD)->execute([$cantidad, $idOrden, $idRepuesto]);

                $sqlCleanCD = "DELETE FROM control_devolucion_repuestos WHERE id_orden_servicio = ? AND id_repuesto = ? AND cantidad <= 0";
                $this->conn->prepare($sqlCleanCD)->execute([$idOrden, $idRepuesto]);
            }

            $this->conn->commit();
            return ['status' => 'ok', 'msg' => 'Repuesto eliminado de la orden'];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['status' => 'error', 'msg' => 'Error BD: ' . $e->getMessage()];
        }
    }

    public function descontarStock($idTecnico, $idRepuesto, $cantidad)
    {
        $sqlCheck = "SELECT cantidad_actual FROM inventario_tecnico 
                    WHERE id_tecnico = ? AND id_repuesto = ?";
        $stmtCheck = $this->conn->prepare($sqlCheck);
        $stmtCheck->execute([$idTecnico, $idRepuesto]);
        $actual = $stmtCheck->fetchColumn();

        if ($actual !== false && $actual >= $cantidad) {
            $sqlUpd = "UPDATE inventario_tecnico 
                        SET cantidad_actual = cantidad_actual - ? 
                        WHERE id_tecnico = ? AND id_repuesto = ?";
            $stmtUpd = $this->conn->prepare($sqlUpd);
            return $stmtUpd->execute([$cantidad, $idTecnico, $idRepuesto]);
        }
        return false;
    }

    public function devolverStock($idTecnico, $idRepuesto, $cantidad)
    {
        $sql = "INSERT INTO inventario_tecnico (id_tecnico, id_repuesto, cantidad_actual, estado)
                VALUES (?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE cantidad_actual = cantidad_actual + ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$idTecnico, $idRepuesto, $cantidad, $cantidad]);
    }

    public function obtenerTiposNovedad()
    {
        return $this->conn->query("SELECT * FROM tipo_novedad WHERE estado = 1 ORDER BY nombre_novedad ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardarNovedadesOrden($idOrden, $arrayNovedades)
    {
        try {
            $this->conn->beginTransaction();

            $sqlDel = "DELETE FROM orden_servicio_novedad WHERE id_orden_servicio = ?";
            $this->conn->prepare($sqlDel)->execute([$idOrden]);

            if (!empty($arrayNovedades) && is_array($arrayNovedades)) {
                $sqlIns = "INSERT INTO orden_servicio_novedad (id_orden_servicio, id_tipo_novedad) VALUES (?, ?)";
                $stmtIns = $this->conn->prepare($sqlIns);

                foreach ($arrayNovedades as $idNov) {
                    $stmtIns->execute([$idOrden, $idNov]);
                }
            }

            $tieneNovedad = empty($arrayNovedades) ? 0 : 1;
            $sqlUpd = "UPDATE ordenes_servicio SET tiene_novedad = ? WHERE id_ordenes_servicio = ?";
            $this->conn->prepare($sqlUpd)->execute([$tieneNovedad, $idOrden]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function eliminarNovedadOrden($idOrden)
    {
        try {
            $this->conn->beginTransaction();

            $sqlDel = "DELETE FROM orden_servicio_novedad WHERE id_orden_servicio = ?";
            $this->conn->prepare($sqlDel)->execute([$idOrden]);

            $sqlUpd = "UPDATE ordenes_servicio 
                SET tiene_novedad = 0, 
                    detalle_novedad = NULL 
                WHERE id_ordenes_servicio = ?";
            $this->conn->prepare($sqlUpd)->execute([$idOrden]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function obtenerDelegaciones()
    {
        return $this->conn->query("SELECT id_delegacion, nombre_delegacion FROM delegacion ORDER BY nombre_delegacion ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTodasRemisionesDisponibles()
    {
        $sql = "SELECT id_tecnico, numero_remision 
                FROM control_remisiones 
                WHERE id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'DISPONIBLE' LIMIT 1) 
                ORDER BY CAST(numero_remision AS UNSIGNED) ASC";

        $resultados = $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $agrupado = [];
        foreach ($resultados as $r) {
            $agrupado[$r['id_tecnico']][] = ['numero_remision' => $r['numero_remision']];
        }
        return $agrupado;
    }

    private function normalizarHora(?string $valor): string
    {
        if (empty(trim($valor ?? '')))
            return '00:00:00';
        $valor = trim($valor);
        if (preg_match('/^(\d{1,2}):(\d{2})/', $valor, $m)) {
            $h = min(23, (int) $m[1]);
            $min = min(59, (int) $m[2]);
            return sprintf('%02d:%02d:00', $h, $min);
        }
        $nums = preg_replace('/\D/', '', $valor);
        if (!$nums)
            return '00:00:00';
        $nums = str_pad($nums, 4, '0', STR_PAD_RIGHT);
        $nums = substr($nums, 0, 4);
        $h = min(23, (int) substr($nums, 0, 2));
        $min = min(59, (int) substr($nums, 2, 2));
        return sprintf('%02d:%02d:00', $h, $min);
    }
}