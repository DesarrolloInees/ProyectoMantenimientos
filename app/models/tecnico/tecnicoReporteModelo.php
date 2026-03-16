<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class tecnicoReporteModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // --- NUEVA FUNCIÓN: Traduce el usuario_id de la sesión al id_tecnico ---
    public function obtenerIdTecnicoPorUsuario($idUsuario)
    {
        try {
            $sql = "SELECT id_tecnico FROM tecnico WHERE usuario_id = :id_usuario LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id_usuario' => $idUsuario]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ? (int)$res['id_tecnico'] : 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Traer todos los detalles de la orden para mostrarlos al técnico
    public function obtenerDetalleOrden($idOrden, $idTecnico)
    {
        try {
            $sql = "SELECT 
                        os.id_ordenes_servicio,
                        os.fecha_visita,
                        c.nombre_cliente,
                        p.nombre_punto,
                        p.direccion,
                        m.device_id,
                        tm.nombre_tipo_maquina,
                        tmt.nombre_completo AS tipo_mantenimiento,
                        os.id_estado_maquina,
                        os.actividades_realizadas
                    FROM ordenes_servicio os
                    LEFT JOIN cliente c ON os.id_cliente = c.id_cliente
                    LEFT JOIN punto p ON os.id_punto = p.id_punto
                    LEFT JOIN maquina m ON os.id_maquina = m.id_maquina
                    LEFT JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                    LEFT JOIN tipo_mantenimiento tmt ON os.id_tipo_mantenimiento = tmt.id_tipo_mantenimiento
                    WHERE os.id_ordenes_servicio = :id_orden 
                    AND os.id_tecnico = :id_tecnico 
                    AND os.estado = 2 LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id_orden' => $idOrden,
                ':id_tecnico' => $idTecnico
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obtenerDetalleOrden: " . $e->getMessage());
            return false;
        }
    }

    // Traer las remisiones físicas disponibles del técnico
    public function obtenerRemisionesTecnico($idTecnico)
    {
        try {
            $sql = "SELECT numero_remision 
                    FROM control_remisiones 
                    WHERE id_tecnico = :id_tecnico 
                    AND id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'DISPONIBLE' LIMIT 1)
                    ORDER BY CAST(numero_remision AS UNSIGNED) ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id_tecnico' => $idTecnico]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }


    // --- NUEVA FUNCIÓN: Traer Tipos de Mantenimiento (Ocultando Garantía - ID 5) ---
    public function obtenerTiposMantenimientoTecnico()
    {
        try {
            $sql = "SELECT id_tipo_mantenimiento, nombre_completo 
                    FROM tipo_mantenimiento 
                    WHERE estado = 1 AND id_tipo_mantenimiento != 5";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }


    // --- NUEVA FUNCIÓN: Obtener SOLO el inventario de este técnico ---
    public function obtenerInventarioTecnico($idTecnico)
    {
        try {
            $sql = "SELECT i.id_repuesto, r.nombre_repuesto, i.cantidad_actual 
                    FROM inventario_tecnico i
                    INNER JOIN repuesto r ON i.id_repuesto = r.id_repuesto
                    WHERE i.id_tecnico = :id_tec AND i.estado = 1 AND i.cantidad_actual > 0
                    ORDER BY r.nombre_repuesto ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id_tec' => $idTecnico]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // --- NUEVA FUNCIÓN: Guardar ruta de la imagen ---
    public function guardarEvidenciaFoto($idOrden, $tipoEvidencia, $rutaArchivo)
    {
        try {
            $sql = "INSERT INTO evidencia_servicio (id_orden_servicio, tipo_evidencia, ruta_archivo) 
                    VALUES (:id_orden, :tipo, :ruta)";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':id_orden' => $idOrden,
                ':tipo'     => $tipoEvidencia,
                ':ruta'     => $rutaArchivo
            ]);
        } catch (PDOException $e) {
            error_log("Error guardando foto: " . $e->getMessage());
            return false;
        }
    }


    // --- NUEVA FUNCIÓN: Limpiar repuestos antes de guardar (por seguridad) ---
    public function limpiarRepuestosOrden($idOrden)
    {
        try {
            // Cambiamos el nombre de la tabla a la correcta
            $sql = "DELETE FROM orden_servicio_repuesto WHERE id_orden_servicio = :id_orden";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':id_orden' => $idOrden]);
        } catch (PDOException $e) {
            error_log("Error limpiando repuestos: " . $e->getMessage());
            return false;
        }
    }

    // --- NUEVA FUNCIÓN: Guardar un repuesto usado con su precio real ---
    public function guardarRepuestoOrden($idOrden, $idRepuesto, $cantidad, $origen)
    {
        try {
            // Usamos un INSERT ... SELECT para jalar el valor_venta directamente de la tabla repuesto
            $sql = "INSERT INTO orden_servicio_repuesto (id_orden_servicio, id_repuesto, origen, cantidad, valor_unitario) 
                    SELECT :id_orden, :id_repuesto, :origen, :cantidad, valor_venta
                    FROM repuesto
                    WHERE id_repuesto = :id_repuesto";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':id_orden' => $idOrden,
                ':id_repuesto' => $idRepuesto,
                ':cantidad' => $cantidad,
                ':origen' => $origen
            ]);
        } catch (PDOException $e) {
            error_log("Error guardando repuesto: " . $e->getMessage());
            return false;
        }
    }



    // Guardar la gestión del técnico (UPDATE con cálculo de tarifa automático)
    public function guardarReporteTecnico($datos)
    {
        try {
            // ==========================================================
            // 1. OBTENER DATOS PARA CALCULAR LA TARIFA (Silencioso)
            // ==========================================================
            $sqlInfo = "SELECT os.fecha_visita, p.id_modalidad, m.id_tipo_maquina
                        FROM ordenes_servicio os
                        LEFT JOIN punto p ON os.id_punto = p.id_punto
                        LEFT JOIN maquina m ON os.id_maquina = m.id_maquina
                        WHERE os.id_ordenes_servicio = :id_orden";
            $stmtInfo = $this->conn->prepare($sqlInfo);
            $stmtInfo->execute([':id_orden' => $datos['id_ordenes_servicio']]);
            $infoOrden = $stmtInfo->fetch(PDO::FETCH_ASSOC);

            $valorServicio = 0;

            if ($infoOrden) {
                // Sacamos el año de la visita
                $anio = !empty($infoOrden['fecha_visita']) ? date('Y', strtotime($infoOrden['fecha_visita'])) : date('Y');
                $idModalidad = !empty($infoOrden['id_modalidad']) ? $infoOrden['id_modalidad'] : 1; // 1 = Urbano por defecto

                // Consultamos el precio exacto
                $sqlTarifa = "SELECT precio FROM tarifa 
                            WHERE id_tipo_maquina = :tipo_maq
                                AND id_tipo_mantenimiento = :tipo_manto
                                AND id_modalidad = :modalidad
                                AND año_vigencia = :anio
                            LIMIT 1";
                $stmtTarifa = $this->conn->prepare($sqlTarifa);
                $stmtTarifa->execute([
                    ':tipo_maq'   => $infoOrden['id_tipo_maquina'],
                    ':tipo_manto' => $datos['id_tipo_mantenimiento'],
                    ':modalidad'  => $idModalidad,
                    ':anio'       => $anio
                ]);

                $resTarifa = $stmtTarifa->fetch(PDO::FETCH_ASSOC);
                if ($resTarifa && $resTarifa['precio'] !== false) {
                    $valorServicio = $resTarifa['precio'];
                }
            }
            // ==========================================================

            // ==========================================================
            // 2. AHORA SÍ, ACTUALIZAMOS TODO EN LA ORDEN
            // ==========================================================
            $sql = "UPDATE ordenes_servicio SET 
                        numero_remision = :remision,
                        id_tipo_mantenimiento = :id_tipo_manto,
                        valor_servicio = :valor_servicio,
                        hora_entrada = :entrada,
                        hora_salida = :salida,
                        tiempo_servicio = :tiempo,
                        actividades_realizadas = :actividades,
                        id_estado_maquina = :id_estado,
                        id_calificacion = :id_calif,
                        tiene_novedad = :tiene_novedad,
                        id_tipo_novedad = :id_tipo_novedad,
                        detalle_novedad = :detalle_novedad,
                        soporte_remoto = :soporte_remoto,
                        repuestos_tecnico = :repuestos_tecnico, /* NUEVO CAMPO AÑADIDO A LA SENTENCIA */
                        estado = 1 /* Pasa a estado ejecutado/revisión */
                    WHERE id_ordenes_servicio = :id_orden 
                    AND id_tecnico = :id_tecnico";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':remision'          => $datos['numero_remision'],
                ':id_tipo_manto'     => $datos['id_tipo_mantenimiento'],
                ':valor_servicio'    => $valorServicio,
                ':entrada'           => $datos['hora_entrada'],
                ':salida'            => $datos['hora_salida'],
                ':tiempo'            => $datos['tiempo_servicio'],
                ':actividades'       => $datos['actividades_realizadas'],
                ':id_estado'         => $datos['id_estado_maquina'],
                ':id_calif'          => $datos['id_calificacion'],
                ':tiene_novedad'     => $datos['tiene_novedad'],
                ':id_tipo_novedad'   => $datos['id_tipo_novedad'],
                ':detalle_novedad'   => $datos['detalle_novedad'],
                ':soporte_remoto'    => $datos['soporte_remoto'],
                ':repuestos_tecnico' => $datos['repuestos_tecnico'], /* NUEVO DATO BINDEADO */
                ':id_orden'          => $datos['id_ordenes_servicio'],
                ':id_tecnico'        => $datos['id_tecnico']
            ]);
        } catch (PDOException $e) {
            error_log("Error guardarReporteTecnico: " . $e->getMessage());
            return false;
        }
    }

    // --- NUEVA FUNCIÓN: Guardar datos complementarios de la orden (Relación 1 a 1) ---
    public function guardarDatosComplementarios($datosComp)
    {
        try {
            // Usamos VALUES(columna) para no repetir los bind parameters de PDO, esto evita errores silenciosos
            $sql = "INSERT INTO ordenes_servicio_complemento (
                        id_orden_servicio, numero_maquina, serial_maquina, serial_router, 
                        serial_ups, pendientes, administrador_punto, celular_encargado, id_estado_inicial, estado
                    ) VALUES (
                        :id_orden, :num_maq, :ser_maq, :ser_rout, 
                        :ser_ups, :pendientes, :admin, :celular, :est_ini, 1
                    ) ON DUPLICATE KEY UPDATE 
                        numero_maquina = VALUES(numero_maquina),
                        serial_maquina = VALUES(serial_maquina),
                        serial_router = VALUES(serial_router),
                        serial_ups = VALUES(serial_ups),
                        pendientes = VALUES(pendientes),
                        administrador_punto = VALUES(administrador_punto),
                        celular_encargado = VALUES(celular_encargado),
                        id_estado_inicial = VALUES(id_estado_inicial)";

            $stmt = $this->conn->prepare($sql);
            
            $resultado = $stmt->execute([
                ':id_orden'   => $datosComp['id_orden_servicio'],
                ':num_maq'    => $datosComp['numero_maquina'],
                ':ser_maq'    => $datosComp['serial_maquina'],
                ':ser_rout'   => $datosComp['serial_router'],
                ':ser_ups'    => $datosComp['serial_ups'],
                ':pendientes' => $datosComp['pendientes'],
                ':admin'      => $datosComp['administrador_punto'],
                ':celular'    => $datosComp['celular_encargado'],
                ':est_ini'    => $datosComp['id_estado_inicial']
            ]);

            // Si falla, lo escribimos en el log de errores de PHP para saber exactamente por qué
            if (!$resultado) {
                error_log("Fallo SQL Complemento: " . print_r($stmt->errorInfo(), true));
            }

            return $resultado;

        } catch (PDOException $e) {
            error_log("Error guardarDatosComplementarios Excepcion: " . $e->getMessage());
            return false;
        }
    }

}
