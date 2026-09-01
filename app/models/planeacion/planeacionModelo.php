<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

class planeacionModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // ------------------------------------------------------------------
    // CATÁLOGOS
    // ------------------------------------------------------------------

    public function obtenerEstados()
    {
        try {
            $sql = "SELECT id_estado, nombre_estado FROM estados_planeacion ORDER BY id_estado ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obtenerEstados: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerUsuariosActivos()
    {
        try {
            $sql = "SELECT usuario_id, nombre FROM usuarios WHERE estado = 'activo' ORDER BY nombre ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obtenerUsuariosActivos: " . $e->getMessage());
            return [];
        }
    }

    // ------------------------------------------------------------------
    // ESTRATEGIAS
    // ------------------------------------------------------------------

    public function obtenerEstrategiasActivas()
    {
        try {
            $sql = "SELECT e.id_estrategia, e.nombre_estrategia, e.fecha_inicial, e.fecha_final,
                           e.avance_pct, ep.nombre_estado, u.nombre AS nombre_responsable
                    FROM estrategias e
                    LEFT JOIN estados_planeacion ep ON ep.id_estado = e.id_estado
                    LEFT JOIN usuarios u ON u.usuario_id = e.id_responsable
                    WHERE e.estado_registro = 1
                    ORDER BY e.id_estrategia ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obtenerEstrategiasActivas: " . $e->getMessage());
            return [];
        }
    }

    public function crearEstrategia($datos)
    {
        try {
            $sql = "INSERT INTO estrategias
                        (nombre_estrategia, id_responsable, id_estado, fecha_inicial, fecha_final)
                    VALUES
                        (:nombre, :id_responsable, :id_estado, :fecha_inicial, :fecha_final)";
            $stmt = $this->conn->prepare($sql);
            $ok = $stmt->execute([
                ':nombre'         => $datos['nombre_estrategia'],
                ':id_responsable' => $datos['id_responsable'],
                ':id_estado'      => $datos['id_estado'],
                ':fecha_inicial'  => $datos['fecha_inicial'],
                ':fecha_final'    => $datos['fecha_final'],
            ]);
            return $ok ? (int) $this->conn->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("Error crearEstrategia: " . $e->getMessage());
            return false;
        }
    }

    // ------------------------------------------------------------------
    // TAREAS / SUBTAREAS (árbol recursivo)
    // ------------------------------------------------------------------

    public function obtenerArbolTareas($idEstrategia)
    {
        try {
            $sql = "WITH RECURSIVE arbol AS (
                        SELECT t.*, 0 AS nivel
                        FROM tareas_estrategia t
                        WHERE t.id_estrategia = :id_estrategia
                          AND t.id_tarea_padre IS NULL
                          AND t.estado_registro = 1

                        UNION ALL

                        SELECT h.*, a.nivel + 1
                        FROM tareas_estrategia h
                        INNER JOIN arbol a ON h.id_tarea_padre = a.id_tarea
                        WHERE h.estado_registro = 1
                    )
                    SELECT arbol.*, ep.nombre_estado, u.nombre AS nombre_responsable
                    FROM arbol
                    LEFT JOIN estados_planeacion ep ON ep.id_estado = arbol.id_estado
                    LEFT JOIN usuarios u ON u.usuario_id = arbol.id_responsable
                    ORDER BY arbol.orden ASC, arbol.id_tarea ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id_estrategia' => $idEstrategia]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obtenerArbolTareas: " . $e->getMessage());
            return [];
        }
    }

    public function crearTarea($datos)
    {
        try {
            $sql = "INSERT INTO tareas_estrategia
                        (id_estrategia, id_tarea_padre, nombre_tarea, id_responsable, id_estado,
                         fecha_inicial, fecha_final, orden)
                    VALUES
                        (:id_estrategia, :id_tarea_padre, :nombre_tarea, :id_responsable, :id_estado,
                         :fecha_inicial, :fecha_final, :orden)";
            $stmt = $this->conn->prepare($sql);
            $ok = $stmt->execute([
                ':id_estrategia'  => $datos['id_estrategia'],
                ':id_tarea_padre' => $datos['id_tarea_padre'] ?: null,
                ':nombre_tarea'   => $datos['nombre_tarea'],
                ':id_responsable' => $datos['id_responsable'],
                ':id_estado'      => $datos['id_estado'],
                ':fecha_inicial'  => $datos['fecha_inicial'],
                ':fecha_final'    => $datos['fecha_final'],
                ':orden'          => $datos['orden'] ?? 0,
            ]);
            return $ok ? (int) $this->conn->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("Error crearTarea: " . $e->getMessage());
            return false;
        }
    }

    // ------------------------------------------------------------------
    // ACTUALIZACIONES DESDE EL GANTT (arrastrar barra / mover slider)
    // ------------------------------------------------------------------

    public function actualizarFechas($tipo, $id, $fechaInicial, $fechaFinal)
    {
        try {
            $tabla   = ($tipo === 'tarea') ? 'tareas_estrategia' : 'estrategias';
            $campoId = ($tipo === 'tarea') ? 'id_tarea' : 'id_estrategia';

            $sql = "UPDATE $tabla
                    SET fecha_inicial = :fecha_inicial, fecha_final = :fecha_final, updated_at = NOW()
                    WHERE $campoId = :id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':fecha_inicial' => $fechaInicial,
                ':fecha_final'   => $fechaFinal,
                ':id'            => $id,
            ]);
        } catch (PDOException $e) {
            error_log("Error actualizarFechas: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarAvance($tipo, $id, $avancePct)
    {
        try {
            $tabla   = ($tipo === 'tarea') ? 'tareas_estrategia' : 'estrategias';
            $campoId = ($tipo === 'tarea') ? 'id_tarea' : 'id_estrategia';

            $sql = "UPDATE $tabla
                    SET avance_pct = :avance, updated_at = NOW()
                    WHERE $campoId = :id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':avance' => $avancePct,
                ':id'     => $id,
            ]);
        } catch (PDOException $e) {
            error_log("Error actualizarAvance: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarEstado($tipo, $id, $idEstado)
    {
        try {
            $tabla   = ($tipo === 'tarea') ? 'tareas_estrategia' : 'estrategias';
            $campoId = ($tipo === 'tarea') ? 'id_tarea' : 'id_estrategia';

            $sql = "UPDATE $tabla
                    SET id_estado = :id_estado, updated_at = NOW()
                    WHERE $campoId = :id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':id_estado' => $idEstado,
                ':id'        => $id,
            ]);
        } catch (PDOException $e) {
            error_log("Error actualizarEstado: " . $e->getMessage());
            return false;
        }
    }
}