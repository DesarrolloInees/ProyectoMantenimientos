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

    // --- 4. PUNTOS POR CLIENTE (CORREGIDO: Lógica Modalidad) ---
    public function obtenerPuntosPorCliente($idCliente)
    {
        try {
            // CAMBIO: Ahora obtenemos id_modalidad directamente de la tabla PUNTO
            // Hacemos un LEFT JOIN con modalidad_operativa por si quieres mostrar el nombre (Urbano/Interurbano)
            $sql = "SELECT p.id_punto, p.nombre_punto, p.codigo_1, 
                            COALESCE(p.id_modalidad, 1) as id_modalidad, -- Si es null, asume 1 (Urbano)
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

    // --- 6. CONSULTAR TARIFA (CON VALIDACIÓN DE EXISTENCIA) ---
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

            // 🔥 AQUÍ ESTÁ EL TRUCO:
            // Si $res es false, significa que NO HAY TARIFA CREADA -> Devolvemos -1
            if ($res === false) {
                return -1;
            }

            // Si existe (incluso si es 0), devolvemos el precio
            return $res['precio'];
        } catch (PDOException $e) {
            error_log("Error en consultarTarifa: " . $e->getMessage());
            return -1; // Ante error de conexión, también asumimos error
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
        $sql = "SELECT id_repuesto, nombre_repuesto FROM repuesto WHERE estado = 1 ORDER BY nombre_repuesto ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- 10: GUARDAR ORDEN (MODIFICADO CON VIÁTICOS) ---
    public function guardarOrden($datos)
    {
        try {
            $this->conn->beginTransaction();

            // =============================================================
            // 1. LÓGICA DE VIÁTICOS INTELIGENTE (SOLO 1 COBRO POR DÍA)
            // =============================================================
            $esFueraDelegacion = 0;
            $diasViaticos = 0;
            $valorViaticos = 0; // Por defecto 0

            // IDs de las bases principales (NO COBRAN)
            $delegacionesPrincipales = [1, 2, 3, 4];

            // Obtenemos la delegación del PUNTO
            $idDelegacionPunto = $this->obtenerIdDelegacionPunto($datos['id_punto']);

            // A. ¿ESTAMOS EN ZONA FORÁNEA?
            if ($idDelegacionPunto > 0 && !in_array($idDelegacionPunto, $delegacionesPrincipales)) {

                $esFueraDelegacion = 1;

                // B. REVISAR SI YA COBRÓ VIÁTICOS HOY (Para este técnico y esta fecha)
                // Buscamos si existe AL MENOS UNA orden de este técnico, en esta fecha, que tenga valor > 0
                $sqlCheck = "SELECT count(*) as total 
                             FROM ordenes_servicio 
                             WHERE id_tecnico = :id_tec 
                               AND fecha_visita = :fecha 
                               AND valor_viaticos > 0";

                $stmtCheck = $this->conn->prepare($sqlCheck);
                $stmtCheck->execute([
                    ':id_tec' => $datos['id_tecnico'],
                    ':fecha'  => $datos['fecha']
                ]);
                $yaCobroHoy = $stmtCheck->fetch(PDO::FETCH_ASSOC)['total'];

                // C. DECISIÓN FINAL
                if ($yaCobroHoy > 0) {
                    // Ya hay un servicio pago hoy. Este va GRATIS de viáticos.
                    $diasViaticos = 0;
                    $valorViaticos = 0;
                } else {
                    // Es el primero del día. ¡A este se le carga la tarifa!
                    $diasViaticos = isset($datos['dias_viaticos']) ? intval($datos['dias_viaticos']) : 1;
                    $tarifa = $this->obtenerValorParametro('Recargo_Servicios_Interurbanos');
                    $valorViaticos = $diasViaticos * $tarifa;
                }
            }
            // =============================================================


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

            // 3. INSERTAR
            $sql = "INSERT INTO ordenes_servicio 
                    (id_cliente, id_punto, id_modalidad, numero_remision, fecha_visita, id_maquina, id_tecnico, id_tipo_mantenimiento, 
                     valor_servicio, es_fuera_delegacion, dias_viaticos, valor_viaticos, 
                     hora_entrada, hora_salida, tiempo_servicio, id_estado_maquina, id_calificacion, actividades_realizadas) 
                    VALUES 
                    (:id_cliente, :id_punto, :id_modalidad, :remision, :fecha, :id_maquina, :id_tecnico, :id_manto, 
                     :valor, :es_fuera, :dias, :val_viaticos, 
                     :entrada, :salida, :tiempo, :id_estado, :id_calif, :actividades)";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id_cliente' => $datos['id_cliente'],
                ':id_punto'   => $datos['id_punto'],
                ':id_modalidad' => $datos['id_modalidad'],
                ':remision'   => $datos['remision'],
                ':fecha'      => $datos['fecha'],
                ':id_maquina' => $datos['id_maquina'],
                ':id_tecnico' => $datos['id_tecnico'], // ¡Ya no se nos olvida!
                ':id_manto'   => $datos['tipo_servicio'],
                ':valor'      => $datos['valor'],

                // NUEVOS VALORES CALCULADOS
                ':es_fuera'     => $esFueraDelegacion,
                ':dias'         => $diasViaticos,
                ':val_viaticos' => $valorViaticos,

                ':entrada'    => $datos['hora_entrada'],
                ':salida'     => $datos['hora_salida'],
                ':tiempo'     => $tiempoCalculado,
                ':id_estado'  => $datos['estado'],
                ':id_calif'   => $datos['calif'],
                ':actividades' => $datos['obs']
            ]);

            $idOrden = $this->conn->lastInsertId();

            // =================================================================================
            // 🔥 CORRECCIÓN CRÍTICA: PASAR TAMBIÉN EL ID TÉCNICO
            // =================================================================================
            if (!empty($datos['remision'])) {
                // Le pasamos el numero, el id de la orden Y EL ID DEL TÉCNICO
                $this->marcarRemisionComoUsada($datos['remision'], $idOrden, $datos['id_tecnico']);
            }
            // =================================================================================

            // =================================================================================

            // =================================================================================
            // NUEVO BLOQUE: ACTUALIZAR EL PUNTO AQUÍ MISMO
            // =================================================================================
            // Usamos los mismos datos que llegaron en $datos
            $this->actualizarInfoMantenimientoPunto($datos['id_punto']);
            // =================================================================================

            // --- B. PROCESAR REPUESTOS ---
            if (!empty($datos['json_repuestos'])) {
                $repuestos = json_decode($datos['json_repuestos'], true);

                if (is_array($repuestos) && count($repuestos) > 0) {
                    $sqlRep = "INSERT INTO orden_servicio_repuesto 
                (id_orden_servicio, id_repuesto, origen, cantidad) 
                VALUES (?, ?, ?, ?)";
                    $stmtRep = $this->conn->prepare($sqlRep);

                    foreach ($repuestos as $rep) {
                        if (isset($rep['id']) && isset($rep['origen'])) {
                            // Validación: Si no viene cantidad, asumimos 1
                            $cant = isset($rep['cantidad']) && $rep['cantidad'] > 0 ? $rep['cantidad'] : 1;

                            // 1. INSERTAR EN TABLA INTERMEDIA
                            $stmtRep->execute([
                                $idOrden,
                                $rep['id'],
                                $rep['origen'],
                                $cant
                            ]);

                            // 2. 🔥 NUEVO: DESCONTAR DEL INVENTARIO AQUÍ MISMO (Dentro de la transacción)
                            // Solo si el origen es 'INEES' (o la lógica que uses para tu inventario propio)
                            if ($rep['origen'] === 'INEES') {
                                $this->descontarDelInventario(
                                    $datos['id_tecnico'],
                                    $rep['id'],
                                    $cant
                                );
                            }
                        }
                    }
                }
            }

            $this->conn->commit();
            return $idOrden;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            // Esto detendrá el código y te mostrará el error exacto en la pantalla
            die("ERROR SQL: " . $e->getMessage());
            return false;
        }
    }

    // --- 11. ACTUALIZAR MODALIDAD DEL PUNTO (AJAX SILENCIOSO) ---
    public function actualizarModalidadPunto($idPunto, $idModalidad)
    {
        try {
            $sql = "UPDATE punto SET id_modalidad = :modalidad WHERE id_punto = :id_punto";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':modalidad' => $idModalidad,
                ':id_punto'  => $idPunto
            ]);
        } catch (PDOException $e) {
            error_log("Error actualizando modalidad punto: " . $e->getMessage());
            return false;
        }
    }

    // --- NUEVA FUNCIÓN: ACTUALIZAR FECHA Y TIPO EN PUNTO ---
    public function actualizarInfoMantenimientoPunto($idPunto)
    {
        try {
            // Esta consulta busca la fecha MÁXIMA registrada para ese punto en TODAS las órdenes.
            // No confía ciegamente en la que acabas de meter, sino en la historia real.
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

    // 1. Obtener las remisiones que el técnico tiene libres
    // 1. Obtener las remisiones que el técnico tiene libres
    public function obtenerRemisionesDisponibles($idTecnico)
    {
        // CAMBIO: Usamos una subconsulta para obtener el ID de 'DISPONIBLE'
        // y filtramos por 'id_estado' en lugar de 'estado'
        $sql = "SELECT id_control, numero_remision 
                FROM control_remisiones 
                WHERE id_tecnico = ? 
                AND id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'DISPONIBLE' LIMIT 1)
                ORDER BY CAST(numero_remision AS UNSIGNED) ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$idTecnico]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. Método auxiliar para "Quemar" la remisión (ACTUALIZADO PARA DUPLICADOS)
    // 2. Método para "Quemar" la remisión
    public function marcarRemisionComoUsada($numeroRemision, $idOrden, $idTecnico)
    {
        // CAMBIO: 
        // 1. SET id_estado = (Subconsulta para buscar el ID de 'USADA')
        // 2. Ya no usamos SET estado = 'USADA'
        $sql = "UPDATE control_remisiones 
                SET id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'USADA' LIMIT 1), 
                    id_orden_servicio = ?, 
                    fecha_uso = NOW() 
                WHERE numero_remision = ? AND id_tecnico = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$idOrden, $numeroRemision, $idTecnico]);
    }

    public function verificarRemisionDisponible($numeroRemision, $idTecnico)
    {
        try {
            // CAMBIO: Hacemos JOIN con estados_remision para obtener el 'nombre_estado'
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
                return [
                    'disponible' => false,
                    'mensaje' => 'Remisión no encontrada en el sistema'
                ];
            }

            // AHORA SÍ podemos comparar con texto porque trajimos 'nombre_estado'
            if ($resultado['nombre_estado'] === 'USADA') {
                return [
                    'disponible' => false,
                    'mensaje' => 'Remisión ya fue usada en orden #' . $resultado['id_orden_servicio']
                ];
            }

            // Opcional: Validar si está ANULADA o ELIMINADA
            if ($resultado['nombre_estado'] === 'ANULADA' || $resultado['nombre_estado'] === 'ELIMINADO') {
                return [
                    'disponible' => false,
                    'mensaje' => 'Esta remisión está ' . $resultado['nombre_estado']
                ];
            }

            return [
                'disponible' => true,
                'mensaje' => 'Remisión disponible'
            ];
        } catch (PDOException $e) {
            error_log("Error verificando remisión: " . $e->getMessage());
            return [
                'disponible' => false,
                'mensaje' => 'Error al verificar remisión'
            ];
        }
    }
    // Obtener lista simple de fechas festivas
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

    // --- NUEVO: OBTENER INVENTARIO ESPECÍFICO DE UN TÉCNICO (Con Cantidades Reales) ---
    public function obtenerInventarioTecnico($idTecnico)
    {
        try {
            // Traemos solo lo que tiene cantidad > 0 y está activo
            $sql = "SELECT i.id_repuesto, r.nombre_repuesto, r.codigo_referencia, i.cantidad_actual 
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

    // --- NUEVO: DESCONTAR DEL INVENTARIO (Al guardar la orden) ---
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

    // --- AUXILIAR: Obtener ID Delegación del Punto ---
    private function obtenerIdDelegacionPunto($idPunto)
    {
        try {
            // Asegúrate que tu tabla punto tenga la columna id_delegacion
            $sql = "SELECT id_delegacion FROM punto WHERE id_punto = :id LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id' => $idPunto]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ? intval($res['id_delegacion']) : 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    // --- AUXILIAR: Obtener valor del parametro ---
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
}
