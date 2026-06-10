<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

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

    /**
     * Obtiene la delegación de un punto
     */
    public function obtenerIdDelegacionPunto($idPunto)
    {
        try {
            $sql = "SELECT id_delegacion FROM punto WHERE id_punto = :id LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id' => $idPunto]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ? (int) $res['id_delegacion'] : 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
 * Obtiene valor de un parámetro
 */
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

/**
 * Verifica si el técnico ya recibió viáticos hoy
 */
public function tecnicoYaCobroViaticosHoy($idTecnico, $fecha, $idOrdenActual = null)
{
    $sql = "SELECT COUNT(*) as total 
            FROM ordenes_servicio 
            WHERE id_tecnico = :id_tec 
                AND fecha_visita = :fecha 
                AND valor_viaticos > 0";
    if ($idOrdenActual) {
        $sql .= " AND id_ordenes_servicio != :id_orden";
    }
    $stmt = $this->conn->prepare($sql);
    $params = [':id_tec' => $idTecnico, ':fecha' => $fecha];
    if ($idOrdenActual) {
        $params[':id_orden'] = $idOrdenActual;
    }
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
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

    // --- NUEVA FUNCIÓN: Obtener evidencias ya subidas de una orden ---
    public function obtenerEvidenciasPorOrden($idOrden)
    {
        try {
            // CAMBIA "id_evidencia" POR EL NOMBRE REAL DE TU COLUMNA SI ES DIFERENTE
            $sql = "SELECT id_evidencia, tipo_evidencia, ruta_archivo 
                    FROM evidencia_servicio 
                    WHERE id_orden_servicio = :id_orden";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id_orden' => $idOrden]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo evidencias: " . $e->getMessage());
            return [];
        }
    }

    public function eliminarEvidencia($idEvidencia)
    {
        try {
            $sqlSelect = "SELECT ruta_archivo FROM evidencia_servicio WHERE id_evidencia = :id";
            $stmtSelect = $this->conn->prepare($sqlSelect);
            $stmtSelect->execute([':id' => $idEvidencia]);
            $foto = $stmtSelect->fetch(PDO::FETCH_ASSOC);

            if ($foto) {
                $sqlDelete = "DELETE FROM evidencia_servicio WHERE id_evidencia = :id";
                $stmtDelete = $this->conn->prepare($sqlDelete);
                $stmtDelete->execute([':id' => $idEvidencia]);
                
                return ['success' => true, 'ruta' => $foto['ruta_archivo']];
            }
            return ['success' => false, 'msj' => 'No se encontró la foto en la BD con ese ID.'];
        } catch (PDOException $e) {
            return ['success' => false, 'msj' => 'Error SQL al borrar: ' . $e->getMessage()];
        }
    }

    public function eliminarTodasEvidenciasOrden($idOrden)
    {
        try {
            $sql = "DELETE FROM evidencia_servicio WHERE id_orden_servicio = :id_orden";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id_orden' => $idOrden]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'msj' => 'Error SQL al vaciar: ' . $e->getMessage()];
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
        // 1. Obtener datos de la orden (fecha, punto, modalidad, tipo máquina)
        $sqlInfo = "SELECT os.fecha_visita, os.id_punto, p.id_modalidad, m.id_tipo_maquina
                    FROM ordenes_servicio os
                    LEFT JOIN punto p ON os.id_punto = p.id_punto
                    LEFT JOIN maquina m ON os.id_maquina = m.id_maquina
                    WHERE os.id_ordenes_servicio = :id_orden";
        $stmtInfo = $this->conn->prepare($sqlInfo);
        $stmtInfo->execute([':id_orden' => $datos['id_ordenes_servicio']]);
        $infoOrden = $stmtInfo->fetch(PDO::FETCH_ASSOC);

        $valorServicio = 0;
        $esFueraDelegacion = 0;
        $diasViaticos = 0;
        $valorViaticos = 0;

        if ($infoOrden) {
            // --- Calcular tarifa base (igual que en ordenCrearModelo) ---
            $anio = date('Y', strtotime($infoOrden['fecha_visita']));
            $idModalidad = $infoOrden['id_modalidad'] ?? 1;
            $idTipoMaquina = $infoOrden['id_tipo_maquina'];
            $idTipoMantenimiento = $datos['id_tipo_mantenimiento'];

            $sqlTarifa = "SELECT precio FROM tarifa 
                          WHERE id_tipo_maquina = :tipo_maq
                              AND id_tipo_mantenimiento = :tipo_manto
                              AND id_modalidad = :modalidad
                              AND año_vigencia = :anio
                          LIMIT 1";
            $stmtTarifa = $this->conn->prepare($sqlTarifa);
            $stmtTarifa->execute([
                ':tipo_maq'   => $idTipoMaquina,
                ':tipo_manto' => $idTipoMantenimiento,
                ':modalidad'  => $idModalidad,
                ':anio'       => $anio
            ]);
            $resTarifa = $stmtTarifa->fetch(PDO::FETCH_ASSOC);
            if ($resTarifa && $resTarifa['precio'] !== false) {
                $valorServicio = floatval($resTarifa['precio']);
            }

            // --- Calcular viáticos (SOLO basado en delegación, sin importar modalidad) ---
            $delegacionesPrincipales = [1, 2, 3, 4];
            $idDelegacionPunto = $this->obtenerIdDelegacionPunto($infoOrden['id_punto']);

            if ($idDelegacionPunto > 0 && !in_array($idDelegacionPunto, $delegacionesPrincipales)) {
                $esFueraDelegacion = 1;

                // Verificar si el técnico ya recibió viáticos hoy (evita doble pago)
                $yaCobroHoy = $this->tecnicoYaCobroViaticosHoy(
                    $datos['id_tecnico'],
                    $infoOrden['fecha_visita'],
                    $datos['id_ordenes_servicio']
                );

                if (!$yaCobroHoy) {
                    $diasViaticos = 1; // Puedes leerlo del formulario si permites varios días
                    $tarifaViatico = $this->obtenerValorParametro('Recargo_Servicios_Interurbanos');
                    $valorViaticos = $diasViaticos * $tarifaViatico;
                }
            }
        }

        // 2. Actualizar la orden con los valores calculados
        $sql = "UPDATE ordenes_servicio SET 
                    numero_remision = :remision,
                    id_tipo_mantenimiento = :id_tipo_manto,
                    valor_servicio = :valor_servicio,
                    es_fuera_delegacion = :es_fuera,
                    dias_viaticos = :dias_viaticos,
                    valor_viaticos = :valor_viaticos,
                    hora_entrada = :entrada,
                    hora_salida = :salida,
                    tiempo_servicio = :tiempo,
                    actividades_realizadas = :actividades,
                    id_estado_maquina = :id_estado,
                    id_calificacion = :id_calif,
                    tiene_novedad = :tiene_novedad,
                    detalle_novedad = :detalle_novedad,
                    soporte_remoto = :soporte_remoto,
                    repuestos_tecnico = :repuestos_tecnico,
                    estado = 1
                WHERE id_ordenes_servicio = :id_orden 
                AND id_tecnico = :id_tecnico";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':remision'          => $datos['numero_remision'],
            ':id_tipo_manto'     => $datos['id_tipo_mantenimiento'],
            ':valor_servicio'    => $valorServicio,
            ':es_fuera'          => $esFueraDelegacion,
            ':dias_viaticos'     => $diasViaticos,
            ':valor_viaticos'    => $valorViaticos,
            ':entrada'           => $datos['hora_entrada'],
            ':salida'            => $datos['hora_salida'],
            ':tiempo'            => $datos['tiempo_servicio'],
            ':actividades'       => $datos['actividades_realizadas'],
            ':id_estado'         => $datos['id_estado_maquina'],
            ':id_calif'          => $datos['id_calificacion'],
            ':tiene_novedad'     => $datos['tiene_novedad'],
            ':detalle_novedad'   => $datos['detalle_novedad'],
            ':soporte_remoto'    => $datos['soporte_remoto'],
            ':repuestos_tecnico' => $datos['repuestos_tecnico'],
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
            // Agregamos latitud_fin y longitud_fin al INSERT y al UPDATE
            $sql = "INSERT INTO ordenes_servicio_complemento (
                        id_orden_servicio, numero_maquina, serial_maquina, serial_router, 
                        serial_ups, pendientes, administrador_punto, celular_encargado, id_estado_inicial, 
                        latitud_fin, longitud_fin, estado
                    ) VALUES (
                        :id_orden, :num_maq, :ser_maq, :ser_rout, 
                        :ser_ups, :pendientes, :admin, :celular, :est_ini, 
                        :lat_fin, :lon_fin, 1
                    ) ON DUPLICATE KEY UPDATE 
                        numero_maquina = VALUES(numero_maquina),
                        serial_maquina = VALUES(serial_maquina),
                        serial_router = VALUES(serial_router),
                        serial_ups = VALUES(serial_ups),
                        pendientes = VALUES(pendientes),
                        administrador_punto = VALUES(administrador_punto),
                        celular_encargado = VALUES(celular_encargado),
                        id_estado_inicial = VALUES(id_estado_inicial),
                        latitud_fin = VALUES(latitud_fin),
                        longitud_fin = VALUES(longitud_fin)";

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
                ':est_ini'    => $datosComp['id_estado_inicial'],
                // ---> NUEVO: Pasamos los parámetros <---
                ':lat_fin'    => $datosComp['latitud_fin'],
                ':lon_fin'    => $datosComp['longitud_fin']
            ]);

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
