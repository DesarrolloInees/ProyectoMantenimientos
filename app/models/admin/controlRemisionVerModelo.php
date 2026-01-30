<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ControlRemisionVerModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function listarRemisiones()
    {
        // CAMBIO: Hacemos JOIN con 'estados_remision' (alias 'e')
        // Seleccionamos 'e.nombre_estado' para poder mostrar el texto en la tabla
        $sql = "SELECT 
                    cr.id_control,
                    cr.numero_remision,
                    cr.id_estado,            -- Traemos el ID por si acaso
                    e.nombre_estado,         -- Traemos el NOMBRE (Ej: DISPONIBLE)
                    cr.fecha_asignacion,
                    cr.fecha_uso, 
                    t.nombre_tecnico
                FROM control_remisiones cr
                INNER JOIN tecnico t ON cr.id_tecnico = t.id_tecnico
                INNER JOIN estados_remision e ON cr.id_estado = e.id_estado
                WHERE e.nombre_estado != 'ELIMINADO' 
                ORDER BY cr.id_control DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerSalteadasSandwich()
    {
        // 1. OBTENER TODO DE UNA VEZ (Consulta ligera y rápida)
        // Traemos solo USADA y DISPONIBLE, ordenadas por técnico y número
        $sql = "SELECT 
                    cr.id_control,
                    cr.numero_remision,
                    e.nombre_estado,
                    cr.fecha_asignacion,
                    cr.id_tecnico,
                    t.nombre_tecnico
                FROM control_remisiones cr
                INNER JOIN tecnico t ON cr.id_tecnico = t.id_tecnico
                INNER JOIN estados_remision e ON cr.id_estado = e.id_estado
                WHERE e.nombre_estado IN ('USADA', 'DISPONIBLE')
                ORDER BY cr.id_tecnico ASC, CAST(cr.numero_remision AS UNSIGNED) ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $todas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $detectadas = [];
        $total = count($todas);

        // 2. PROCESAR EN PHP (Esto es instantáneo)
        // Recorremos desde el segundo elemento hasta el penúltimo
        for ($i = 1; $i < $total - 1; $i++) {
            
            $curr = $todas[$i];     // Actual
            $prev = $todas[$i - 1]; // Anterior
            $next = $todas[$i + 1]; // Siguiente

            // CONDICIÓN 1: La actual debe estar DISPONIBLE
            if ($curr['nombre_estado'] !== 'DISPONIBLE') {
                continue;
            }

            // CONDICIÓN 2: Deben ser del MISMO TÉCNICO
            if ($curr['id_tecnico'] != $prev['id_tecnico'] || $curr['id_tecnico'] != $next['id_tecnico']) {
                continue;
            }

            // CONDICIÓN 3: La Anterior y la Siguiente deben estar USADAS
            if ($prev['nombre_estado'] !== 'USADA' || $next['nombre_estado'] !== 'USADA') {
                continue;
            }

            // CONDICIÓN 4: Secuencia numérica exacta (Ej: 100, 101, 102)
            // Convertimos a entero para comparar matemáticamente
            $numPrev = intval($prev['numero_remision']);
            $numCurr = intval($curr['numero_remision']);
            $numNext = intval($next['numero_remision']);

            if (($numPrev == $numCurr - 1) && ($numNext == $numCurr + 1)) {
                // ¡SÁNDWICH DETECTADO! 🥪
                // Agregamos los datos que necesita la vista
                $detectadas[] = [
                    'id_control'       => $curr['id_control'],
                    'numero_remision'  => $curr['numero_remision'],
                    'nombre_estado'    => $curr['nombre_estado'],
                    'fecha_asignacion' => $curr['fecha_asignacion'],
                    'nombre_tecnico'   => $curr['nombre_tecnico'],
                    'anterior'         => $prev['numero_remision'],
                    'siguiente'        => $next['numero_remision']
                ];
            }
        }

        return $detectadas;
    }

    // Método para actualizar el estado (usado por el botón)
   // Método para actualizar el estado (usado por el botón de Remisiones Pendientes/Salteadas)
    public function actualizarEstadoRapido($idRemision, $nombreEstado)
    {
        try {
            // CAMBIO: En lugar de guardar el valor directo, hacemos una SUBCONSULTA
            // Buscamos cuál es el ID que corresponde al texto (Ej: 'ANULADA' -> ID 3)
            $sql = "UPDATE control_remisiones 
                    SET id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = :estado LIMIT 1) 
                    WHERE id_control = :id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':estado', $nombreEstado); // Pasamos 'ANULADA', 'USADA', etc.
            $stmt->bindParam(':id', $idRemision);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}