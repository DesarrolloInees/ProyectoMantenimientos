<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

class programacionConsolidadoModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * @param string $fecha_inicio
     * @param string $fecha_fin
     * @param string $id_delegacion Opcional
     * @param string $id_tecnico    Opcional — filtrar por un técnico puntual
     */
    public function obtenerConsolidadoRutas($fecha_inicio, $fecha_fin, $id_delegacion = '', $id_tecnico = '')
    {
        $sql = "SELECT os.id_ordenes_servicio AS id_orden, os.fecha_visita, os.estado,
                    t.id_tecnico, COALESCE(t.nombre_tecnico, 'Sin Técnico') AS nombre_tecnico, 
                    COALESCE(t.codigo_ruta, 'S/R') AS codigo_ruta,
                    os.id_punto,
                    COALESCE(os.id_cliente, p.id_cliente) AS id_cliente,
                    p.id_cliente AS id_cliente_punto,
                    p.nombre_punto, p.direccion, p.zona, p.fecha_ultima_visita,
                    c.nombre_cliente, c.codigo_cliente,
                    m.nombre_municipio, d.nombre_delegacion,
                    COALESCE(os.id_maquina, mq.id_maquina,
                        (SELECT m1.id_maquina FROM maquina m1
                          WHERE m1.id_punto = p.id_punto AND m1.estado = 1
                          ORDER BY m1.id_maquina ASC LIMIT 1)) AS id_maquina,
                    COALESCE(os.id_modalidad, p.id_modalidad, 1) AS id_modalidad,
                    COALESCE(mq.device_id,
                        (SELECT m2.device_id FROM maquina m2
                          WHERE m2.id_punto = p.id_punto AND m2.estado = 1
                          ORDER BY m2.id_maquina ASC LIMIT 1), 'S/N') AS device_id,
                    COALESCE(mq.activo_operativo,
                        (SELECT m3.activo_operativo FROM maquina m3
                          WHERE m3.id_punto = p.id_punto AND m3.estado = 1
                          ORDER BY m3.id_maquina ASC LIMIT 1), 1) AS activo_operativo
            FROM ordenes_servicio os
            LEFT JOIN tecnico t ON os.id_tecnico = t.id_tecnico
            LEFT JOIN punto p ON os.id_punto = p.id_punto
            LEFT JOIN cliente c ON os.id_cliente = c.id_cliente
            LEFT JOIN municipio m ON p.id_municipio = m.id_municipio
            LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
            LEFT JOIN maquina mq ON os.id_maquina = mq.id_maquina
            WHERE os.estado = 2
            AND os.fecha_visita BETWEEN :inicio AND :fin";

        $params = [
            ':inicio' => $fecha_inicio,
            ':fin' => $fecha_fin
        ];

        if (!empty($id_delegacion)) {
            $sql .= " AND p.id_delegacion = :delegacion";
            $params[':delegacion'] = $id_delegacion;
        }

        if (!empty($id_tecnico)) {
            $sql .= " AND os.id_tecnico = :tecnico";
            $params[':tecnico'] = $id_tecnico;
        }

        $sql .= " ORDER BY os.fecha_visita ASC, t.codigo_ruta ASC, p.zona ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Restaura a estado operativo (activo_operativo = 1) todas las máquinas fuera de servicio
     * que NO fueron incluidas en las órdenes de servicio programadas (estado = 2).
     */
    public function restaurarMaquinasRestantesAOperativo()
    {
        try {
            $sql = "UPDATE maquina m
                SET m.activo_operativo = 1, 
                    m.fecha_actualizacion = NOW()
                WHERE m.activo_operativo = 0 
                    AND m.estado = 1
                    AND NOT EXISTS (
                        SELECT 1 
                        FROM ordenes_servicio os 
                        WHERE os.id_punto = m.id_punto 
                        AND os.estado = 2
                    )";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();

            return [
                'status' => true,
                'filas_restauradas' => $stmt->rowCount()
            ];
        } catch (PDOException $e) {
            return [
                'status' => false,
                'msg' => $e->getMessage()
            ];
        }
    }

    public function obtenerDelegaciones()
    {
        $stmt = $this->conn->prepare("SELECT id_delegacion, nombre_delegacion FROM delegacion WHERE estado = 1 ORDER BY nombre_delegacion ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTecnicos()
    {
        $stmt = $this->conn->prepare("SELECT id_tecnico, nombre_tecnico,
                                             COALESCE(codigo_ruta, 'S/R') AS codigo_ruta
                                      FROM tecnico
                                      WHERE estado = 1
                                      ORDER BY nombre_tecnico ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerModalidades()
    {
        $stmt = $this->conn->prepare("SELECT id_modalidad, nombre_modalidad
                                      FROM modalidad_operativa
                                      ORDER BY nombre_modalidad ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarFechaOrden($idOrden, $nuevaFecha)
    {
        $sql = "UPDATE ordenes_servicio SET fecha_visita = :fecha WHERE id_ordenes_servicio = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':fecha' => $nuevaFecha, ':id' => $idOrden]);
    }

    /**
     * Elimina una orden programada (estado = 2) de ordenes_servicio.
     * No toca órdenes ya ejecutadas ni otras tablas.
     */
    public function eliminarOrdenProgramada($idOrden)
    {
        try {
            $idOrden = (int) $idOrden;
            if ($idOrden <= 0) {
                return ['status' => false, 'msg' => 'Identificador de orden inválido.'];
            }

            $sql = "DELETE FROM ordenes_servicio
                    WHERE id_ordenes_servicio = :id
                      AND estado = 2";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id' => $idOrden]);

            if ($stmt->rowCount() > 0) {
                return ['status' => true, 'msg' => 'Orden eliminada de la programación.'];
            }

            return [
                'status' => false,
                'msg' => 'No se encontró una orden programada con ese identificador, o ya no está en estado programado.'
            ];
        } catch (PDOException $e) {
            return ['status' => false, 'msg' => 'No se pudo eliminar la orden: ' . $e->getMessage()];
        }
    }

    /**
     * Actualiza una orden programada (estado = 2) en la base de datos.
     * Permite modificar: punto, técnico, fecha, cliente, máquina y modalidad.
     */
    public function actualizarOrden($idOrden, $idPunto, $idTecnico, $idCliente, $idMaquina, $idModalidad, $fechaVisita)
    {
        try {
            $idOrden = (int) $idOrden;
            $idPunto = (int) $idPunto;
            $idTecnico = (int) $idTecnico;
            $idCliente = (int) $idCliente;
            $idMaquina = ($idMaquina !== '' && $idMaquina !== null) ? (int) $idMaquina : 0;
            $idModalidad = ($idModalidad !== '' && $idModalidad !== null) ? (int) $idModalidad : 0;
            $fechaVisita = trim(strip_tags((string) $fechaVisita));

            if ($idOrden <= 0) {
                return ['status' => false, 'msg' => 'Identificador de orden inválido.'];
            }

            if ($idPunto <= 0 || $idTecnico <= 0 || $idCliente <= 0) {
                return ['status' => false, 'msg' => 'Debe seleccionar técnico, cliente y punto.'];
            }

            $fechaObj = DateTime::createFromFormat('Y-m-d', $fechaVisita);
            if (!$fechaObj || $fechaObj->format('Y-m-d') !== $fechaVisita) {
                return ['status' => false, 'msg' => 'La fecha de visita no es válida.'];
            }

            $checkSql = "SELECT id_ordenes_servicio, id_punto FROM ordenes_servicio
                         WHERE id_ordenes_servicio = :id AND estado = 2";
            $checkStmt = $this->conn->prepare($checkSql);
            $checkStmt->execute([':id' => $idOrden]);
            $ordenActual = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$ordenActual) {
                return ['status' => false, 'msg' => 'No se encontró una orden programada válida para editar.'];
            }

            // El punto debe existir, estar activo y pertenecer al cliente seleccionado
            $sqlPunto = "SELECT p.id_punto, p.id_cliente, p.nombre_punto, p.zona, p.direccion,
                                COALESCE(p.id_modalidad, 1) AS id_modalidad
                         FROM punto p
                         WHERE p.id_punto = :id_punto AND p.estado = 1";
            $stmtPunto = $this->conn->prepare($sqlPunto);
            $stmtPunto->execute([':id_punto' => $idPunto]);
            $punto = $stmtPunto->fetch(PDO::FETCH_ASSOC);

            if (!$punto) {
                return ['status' => false, 'msg' => 'El punto seleccionado no existe o está inactivo.'];
            }

            // El cliente real siempre es el dueño del punto (evita combinaciones inconsistentes)
            $idCliente = (int) $punto['id_cliente'];

            // Solo se valida duplicado cuando la orden cambia de punto: los datos
            // históricos pueden tener el mismo punto programado más de una vez.
            if ((int) $ordenActual['id_punto'] !== $idPunto) {
                $sqlDuplicado = "SELECT COUNT(*) AS total
                                 FROM ordenes_servicio
                                 WHERE id_punto = :id_punto
                                   AND estado = 2
                                   AND id_ordenes_servicio <> :id";
                $stmtDuplicado = $this->conn->prepare($sqlDuplicado);
                $stmtDuplicado->execute([':id_punto' => $idPunto, ':id' => $idOrden]);
                $duplicado = $stmtDuplicado->fetch(PDO::FETCH_ASSOC);

                if ($duplicado && (int) $duplicado['total'] > 0) {
                    return ['status' => false, 'msg' => 'Ese punto ya tiene otra orden programada. Elimine o mueva la otra orden primero.'];
                }
            }

            // Máquina: validar que pertenezca al punto; si no se envió, usar la primera activa
            if ($idMaquina > 0) {
                $stmtMaq = $this->conn->prepare("SELECT id_maquina FROM maquina
                                                 WHERE id_maquina = :id AND id_punto = :id_punto AND estado = 1");
                $stmtMaq->execute([':id' => $idMaquina, ':id_punto' => $idPunto]);
                if ($stmtMaq->rowCount() === 0) {
                    $idMaquina = 0;
                }
            }

            if ($idMaquina <= 0) {
                $stmtMaq = $this->conn->prepare("SELECT id_maquina FROM maquina
                                                 WHERE id_punto = :id_punto AND estado = 1
                                                 ORDER BY id_maquina ASC LIMIT 1");
                $stmtMaq->execute([':id_punto' => $idPunto]);
                $maquina = $stmtMaq->fetch(PDO::FETCH_ASSOC);
                $idMaquina = $maquina ? (int) $maquina['id_maquina'] : 0;
            }

            if ($idModalidad <= 0) {
                $idModalidad = (int) ($punto['id_modalidad'] ?? 1);
            }

            $sql = "UPDATE ordenes_servicio SET 
                        id_punto = :id_punto, 
                        id_tecnico = :id_tecnico, 
                        id_cliente = :id_cliente, 
                        id_maquina = :id_maquina, 
                        id_modalidad = :id_modalidad,
                        fecha_visita = :fecha_visita 
                    WHERE id_ordenes_servicio = :id 
                      AND estado = 2";

            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                ':id_punto' => $idPunto,
                ':id_tecnico' => $idTecnico,
                ':id_cliente' => $idCliente,
                ':id_maquina' => $idMaquina > 0 ? $idMaquina : null,
                ':id_modalidad' => $idModalidad,
                ':fecha_visita' => $fechaVisita,
                ':id' => $idOrden
            ]);

            if (!$result) {
                return ['status' => false, 'msg' => 'No se pudo actualizar la orden.'];
            }

            // rowCount() devuelve 0 cuando se guardaron los mismos valores: también es un éxito.
            return [
                'status' => true,
                'msg' => 'Orden actualizada correctamente.',
                'datos' => $this->obtenerOrdenParaEdicion($idOrden)
            ];
        } catch (PDOException $e) {
            return ['status' => false, 'msg' => 'Error de base de datos: ' . $e->getMessage()];
        }
    }

    /**
     * Obtiene clientes con puntos pendientes para el modal de edición
     */
    public function obtenerClientesConPuntos($id_punto_excluir = 0)
    {
        $sql = "SELECT DISTINCT c.id_cliente, c.nombre_cliente, c.codigo_cliente,
                       p.id_punto, p.nombre_punto, p.zona, p.direccion, m.nombre_municipio,
                       p.fecha_ultima_visita,
                       COALESCE(mq.device_id, 'N/A') as device_id,
                       COALESCE(mq.activo_operativo, 1) as activo_operativo
                FROM cliente c
                INNER JOIN punto p ON c.id_cliente = p.id_cliente
                LEFT JOIN municipio m ON p.id_municipio = m.id_municipio
                LEFT JOIN maquina mq ON p.id_punto = mq.id_punto AND mq.estado = 1
                WHERE c.estado = 1 AND p.estado = 1
                    AND NOT EXISTS (
                        SELECT 1 FROM ordenes_servicio os 
                        WHERE os.id_punto = p.id_punto AND os.estado = 2
                    )";

        if ($id_punto_excluir > 0) {
            $sql .= " AND p.id_punto != :excluir_punto";
        }

        $sql .= " ORDER BY c.nombre_cliente ASC, p.nombre_punto ASC";

        $stmt = $this->conn->prepare($sql);
        if ($id_punto_excluir > 0) {
            $stmt->execute([':excluir_punto' => $id_punto_excluir]);
        } else {
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve los datos "planos" de una orden programada, con el mismo formato
     * que usa la tabla del consolidado (sirve para refrescar la fila vía AJAX).
     */
    private function obtenerOrdenParaEdicion($idOrden)
    {
        $sql = "SELECT os.id_ordenes_servicio AS id_orden, os.fecha_visita, os.estado,
                       os.id_punto,
                       COALESCE(os.id_cliente, p.id_cliente) AS id_cliente,
                       COALESCE(os.id_modalidad, p.id_modalidad, 1) AS id_modalidad,
                       COALESCE(os.id_maquina, mq.id_maquina,
                           (SELECT m1.id_maquina FROM maquina m1
                             WHERE m1.id_punto = p.id_punto AND m1.estado = 1
                             ORDER BY m1.id_maquina ASC LIMIT 1)) AS id_maquina,
                       t.id_tecnico, COALESCE(t.nombre_tecnico, 'Sin Técnico') AS nombre_tecnico,
                       COALESCE(t.codigo_ruta, 'S/R') AS codigo_ruta,
                       p.nombre_punto, p.direccion, p.zona, p.fecha_ultima_visita,
                       m.nombre_municipio, d.nombre_delegacion,
                       c.nombre_cliente, c.codigo_cliente,
                       COALESCE(mq.device_id,
                           (SELECT m2.device_id FROM maquina m2
                             WHERE m2.id_punto = p.id_punto AND m2.estado = 1
                             ORDER BY m2.id_maquina ASC LIMIT 1), 'S/N') AS device_id,
                       COALESCE(mq.activo_operativo,
                           (SELECT m3.activo_operativo FROM maquina m3
                             WHERE m3.id_punto = p.id_punto AND m3.estado = 1
                             ORDER BY m3.id_maquina ASC LIMIT 1), 1) AS activo_operativo
                FROM ordenes_servicio os
                LEFT JOIN tecnico t ON os.id_tecnico = t.id_tecnico
                LEFT JOIN punto p ON os.id_punto = p.id_punto
                LEFT JOIN municipio m ON p.id_municipio = m.id_municipio
                LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                LEFT JOIN cliente c ON COALESCE(os.id_cliente, p.id_cliente) = c.id_cliente
                LEFT JOIN maquina mq ON os.id_maquina = mq.id_maquina
                WHERE os.id_ordenes_servicio = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $idOrden]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ?: null;
    }

    /**
     * Clientes activos que tengan al menos un punto activo.
     * Incluye clientes cuyos puntos ya están programados (se edita una orden existente).
     */
    public function obtenerClientesParaEditar()
    {
        $sql = "SELECT DISTINCT c.id_cliente, c.nombre_cliente, c.codigo_cliente
                FROM cliente c
                INNER JOIN punto p ON p.id_cliente = c.id_cliente
                WHERE c.estado = 1 AND p.estado = 1
                ORDER BY c.nombre_cliente ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Puntos de un cliente para el modal de edición.
     * Marca con ya_programado = 1 los puntos que ya tienen otra orden programada,
     * e incluye la máquina/modalidad sugeridas para autocompletar el formulario.
     */
    public function obtenerPuntosPorCliente($idCliente, $idPuntoActual = 0, $idOrden = 0)
    {
        try {
            $idCliente = (int) $idCliente;
            $idOrden = (int) $idOrden;

            if ($idCliente <= 0) {
                return [];
            }

            $sql = "SELECT p.id_punto, p.nombre_punto, p.zona, p.direccion, p.fecha_ultima_visita,
                           COALESCE(p.id_modalidad, 1) AS id_modalidad,
                           COALESCE(mo.nombre_modalidad, 'Sin modalidad') AS nombre_modalidad,
                           (SELECT m1.id_maquina FROM maquina m1
                             WHERE m1.id_punto = p.id_punto AND m1.estado = 1
                             ORDER BY m1.id_maquina ASC LIMIT 1) AS id_maquina,
                           COALESCE((SELECT m2.device_id FROM maquina m2
                             WHERE m2.id_punto = p.id_punto AND m2.estado = 1
                             ORDER BY m2.id_maquina ASC LIMIT 1), 'S/N') AS device_id,
                           COALESCE((SELECT m3.activo_operativo FROM maquina m3
                             WHERE m3.id_punto = p.id_punto AND m3.estado = 1
                             ORDER BY m3.id_maquina ASC LIMIT 1), 1) AS activo_operativo,
                           CASE WHEN EXISTS (
                               SELECT 1 FROM ordenes_servicio os
                               WHERE os.id_punto = p.id_punto
                                 AND os.estado = 2
                                 AND os.id_ordenes_servicio <> :id_orden
                           ) THEN 1 ELSE 0 END AS ya_programado
                    FROM punto p
                    LEFT JOIN modalidad_operativa mo ON mo.id_modalidad = p.id_modalidad
                    WHERE p.id_cliente = :id_cliente AND p.estado = 1
                    ORDER BY p.nombre_punto ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id_orden' => $idOrden,
                ':id_cliente' => $idCliente
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error en obtenerPuntosPorCliente: ' . $e->getMessage());
            return [];
        }
    }


    /**
     * Obtiene los clientes asociados a un punto específico
     */
    public function obtenerClientesPorPunto($id_punto, $id_cliente_excluir = 0)
    {
        $sql = "SELECT DISTINCT c.id_cliente, c.nombre_cliente, c.codigo_cliente
                FROM cliente c
                INNER JOIN punto p ON c.id_cliente = p.id_cliente
                WHERE p.id_punto = :id_punto
                  AND c.estado = 1 AND p.estado = 1";

        $params = [':id_punto' => $id_punto];

        if ($id_cliente_excluir > 0) {
            $sql .= " AND c.id_cliente != :excluir_cliente";
            $params[':excluir_cliente'] = $id_cliente_excluir;
        }

        $sql .= " ORDER BY c.nombre_cliente ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
