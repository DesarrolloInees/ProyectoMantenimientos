<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class panelSupervisorModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function obtenerServiciosDelDia()
    {
        try {
            // Traemos los servicios de hoy. Ajusta los nombres de las tablas y campos si es necesario.
            $sql = "SELECT 
                        o.id_ordenes_servicio,
                        o.numero_remision,
                        o.fecha_visita,
                        o.id_estado_maquina, -- Asegúrate de que este sea el campo que indica si está pendiente, en ruta, etc.
                        COALESCE(t.nombre_tecnico, 'Sin Asignar') as nombre_tecnico,
                        COALESCE(c.nombre_cliente, 'SIN CLIENTE') as nombre_cliente,
                        COALESCE(p.nombre_punto, 'SIN PUNTO') as nombre_punto
                    FROM ordenes_servicio o
                    LEFT JOIN tecnico t ON o.id_tecnico = t.id_tecnico
                    LEFT JOIN cliente c ON o.id_cliente = c.id_cliente
                    LEFT JOIN punto p ON o.id_punto = p.id_punto
                    WHERE DATE(o.fecha_visita) = CURDATE()
                    ORDER BY o.id_ordenes_servicio DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo servicios del día: " . $e->getMessage());
            return [];
        }
    }
    public function cancelarServicio($idOrden)
    {
        try {
            // Eliminamos la fila completamente de la base de datos para no dejar basura
            $sql = "DELETE FROM ordenes_servicio WHERE id_ordenes_servicio = :id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':id' => $idOrden]);
        } catch (PDOException $e) {
            error_log("Error eliminando servicio: " . $e->getMessage());
            return false;
        }
    }

    // ── OBTENER LISTAS PARA LOS SELECTS DEL MODAL ──

    public function obtenerTecnicosActivos(): array
    {
        try {
            $sql = "SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }

    public function obtenerClientesActivos(): array
    {
        try {
            $sql = "SELECT id_cliente, nombre_cliente FROM cliente WHERE estado = 1 ORDER BY nombre_cliente ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }

    public function obtenerPuntosPorCliente(int $idCliente): array
    {
        try {
            $sql = "SELECT id_punto, nombre_punto, direccion FROM punto WHERE id_cliente = :id_cliente AND estado = 1 ORDER BY nombre_punto ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id_cliente' => $idCliente]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }

    public function obtenerMaquinasPorPunto(int $idPunto): array
    {
        try {
            $sql = "SELECT m.id_maquina, m.device_id, tm.nombre_tipo_maquina 
                    FROM maquina m 
                    INNER JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                    WHERE m.id_punto = :id_punto AND m.estado = 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id_punto' => $idPunto]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }

    public function obtenerTiposMantenimiento(): array
    {
        try {
            $sql = "SELECT id_tipo_mantenimiento, nombre_completo FROM tipo_mantenimiento WHERE estado = 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }

    // ── GUARDAR EL NUEVO SERVICIO ──
    public function guardarNuevoServicio(array $datos): bool
    {
        try {
            // 1. Obtener la modalidad del punto seleccionado
            $sqlPunto = "SELECT id_modalidad FROM punto WHERE id_punto = :id_punto";
            $stmtPunto = $this->conn->prepare($sqlPunto);
            $stmtPunto->execute([':id_punto' => $datos['id_punto']]);
            $punto = $stmtPunto->fetch(PDO::FETCH_ASSOC);
            $idModalidad = $punto ? $punto['id_modalidad'] : null;

            // 2. Insertar la orden (usando estado = 2 para que le aparezca al técnico)
            $sql = "INSERT INTO ordenes_servicio 
                    (id_cliente, id_punto, id_modalidad, id_maquina, id_tecnico, id_tipo_mantenimiento, fecha_visita, estado) 
                    VALUES 
                    (:cliente, :punto, :modalidad, :maquina, :tecnico, :tipo_mantenimiento, :fecha, 2)";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':cliente' => $datos['id_cliente'],
                ':punto' => $datos['id_punto'],
                ':modalidad' => $idModalidad,
                ':maquina' => $datos['id_maquina'],
                ':tecnico' => $datos['id_tecnico'],
                ':tipo_mantenimiento' => $datos['id_tipo_mantenimiento'],
                ':fecha' => $datos['fecha_visita']
            ]);
        } catch (PDOException $e) {
            error_log("Error guardando servicio supervisor: " . $e->getMessage());
            return false;
        }
    }
}