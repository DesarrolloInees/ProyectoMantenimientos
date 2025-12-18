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

    // --- 6. CONSULTAR TARIFA (DINÁMICA POR AÑO) ---
    // Ahora pedimos la fecha para saber qué año cobrar
    public function consultarTarifa($idTipoMaq, $idTipoManto, $idModalidad, $fechaVisita)
    {
        try {
            // 1. Extraer el AÑO de la fecha de visita. 
            // Si la fecha viene vacía, usamos el año actual del servidor por defecto.
            $anio = !empty($fechaVisita) ? date('Y', strtotime($fechaVisita)) : date('Y');

            $sql = "SELECT precio FROM tarifa 
                    WHERE id_tipo_maquina = :tipo_maq
                        AND id_tipo_mantenimiento = :tipo_manto
                        AND id_modalidad = :modalidad
                        AND año_vigencia = :anio  -- 🔥 AQUÍ ESTÁ LA MAGIA
                    LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':tipo_maq', $idTipoMaq, PDO::PARAM_INT);
            $stmt->bindParam(':tipo_manto', $idTipoManto, PDO::PARAM_INT);
            $stmt->bindParam(':modalidad', $idModalidad, PDO::PARAM_INT);
            $stmt->bindParam(':anio', $anio, PDO::PARAM_INT); // Pasamos el año calculado

            $stmt->execute();
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            return $res ? $res['precio'] : 0;
        } catch (PDOException $e) {
            error_log("Error en consultarTarifa: " . $e->getMessage());
            return 0;
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

    // --- 10: GUARDAR ORDEN (CORREGIDO: Con cálculo de tiempo) ---
    public function guardarOrden($datos)
    {
        try {
            $this->conn->beginTransaction();

            // 1. CALCULAR TIEMPO DE SERVICIO EN PHP (Más seguro)
            $tiempoCalculado = "00:00";
            if (!empty($datos['hora_entrada']) && !empty($datos['hora_salida'])) {
                try {
                    $d1 = new DateTime($datos['hora_entrada']);
                    $d2 = new DateTime($datos['hora_salida']);

                    // Si la salida es menor que la entrada (ej: 11pm a 1am), sumamos un día
                    if ($d2 < $d1) {
                        $d2->modify('+1 day');
                    }

                    $intervalo = $d1->diff($d2);
                    $tiempoCalculado = $intervalo->format('%H:%I'); // Formato 02:30
                } catch (Exception $e) {
                    $tiempoCalculado = "00:00"; // Fallback por si las horas vienen mal
                }
            }

            // 2. INSERT ACTUALIZADO
            // Agregamos 'tiempo_servicio' en las columnas y ':tiempo' en los values
            $sql = "INSERT INTO ordenes_servicio 
                    (id_cliente, id_punto, id_modalidad, numero_remision, fecha_visita, id_maquina, id_tecnico, id_tipo_mantenimiento, 
                        valor_servicio, hora_entrada, hora_salida, tiempo_servicio, id_estado_maquina, id_calificacion, actividades_realizadas) 
                    VALUES 
                    (:id_cliente, :id_punto, :id_modalidad, :remision, :fecha, :id_maquina, :id_tecnico, :id_manto, 
                        :valor, :entrada, :salida, :tiempo, :id_estado, :id_calif, :actividades)";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id_cliente' => $datos['id_cliente'],
                ':id_punto'   => $datos['id_punto'],
                'id_modalidad' => $datos['id_modalidad'],
                ':remision'   => $datos['remision'],
                ':fecha'      => $datos['fecha'],
                ':id_maquina' => $datos['id_maquina'],
                ':id_tecnico' => $datos['id_tecnico'] ?? 1,
                ':id_manto'   => $datos['tipo_servicio'],
                ':valor'      => $datos['valor'],
                ':entrada'    => $datos['hora_entrada'],
                ':salida'     => $datos['hora_salida'],
                ':tiempo'     => $tiempoCalculado, // <--- AQUÍ GUARDAMOS EL CÁLCULO
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

                            $stmtRep->execute([
                                $idOrden,
                                $rep['id'],
                                $rep['origen'],
                                $cant // 🔥 AQUÍ PASAMOS LA CANTIDAD REAL
                            ]);
                        }
                    }
                }
            }

            $this->conn->commit();
            return $idOrden;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error guardarOrden: " . $e->getMessage());
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
    public function obtenerRemisionesDisponibles($idTecnico)
    {
        $sql = "SELECT id_control, numero_remision 
            FROM control_remisiones 
            WHERE id_tecnico = ? AND estado = 'DISPONIBLE' 
            ORDER BY numero_remision ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$idTecnico]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. Método auxiliar para "Quemar" la remisión (ACTUALIZADO PARA DUPLICADOS)
    public function marcarRemisionComoUsada($numeroRemision, $idOrden, $idTecnico)
    {
        // 🔥 AGREGAMOS "AND id_tecnico = ?" PARA QUE NO QUEME LA DEL OTRO MAN
        $sql = "UPDATE control_remisiones 
                SET estado = 'USADA', 
                    id_orden_servicio = ?, 
                    fecha_uso = NOW() 
                WHERE numero_remision = ? AND id_tecnico = ?";

        $stmt = $this->conn->prepare($sql);
        // Ojo al orden de los parámetros: Orden, Numero, Tecnico
        $stmt->execute([$idOrden, $numeroRemision, $idTecnico]);
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
}
