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
        $sql = "SELECT 
                    cr.id_control,
                    cr.numero_remision,
                    cr.id_estado,
                    e.nombre_estado,
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
        // 1. OBTENEMOS TODAS (Solo USADAS y DISPONIBLES)
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

        // 2. AGRUPAMOS POR TÉCNICO
        $remisionesPorTecnico = [];
        foreach ($todas as $row) {
            $remisionesPorTecnico[$row['id_tecnico']][] = $row;
        }

        $detectadas = [];

        // 3. BUSCAMOS LOS SÁNDWICHES DE 1 O 2 PISOS
        foreach ($remisionesPorTecnico as $idTecnico => $rems) {
            $total = count($rems);
            
            // Necesitamos mínimo 3 remisiones para armar un sándwich
            if ($total < 3) continue;

            for ($i = 0; $i < $total - 2; $i++) {
                $r0 = $rems[$i];
                $r1 = $rems[$i+1];
                $r2 = $rems[$i+2];

                // SÁNDWICH DE 1 PISO: [USADA] -> [DISPONIBLE] -> [USADA]
                if ($r0['nombre_estado'] === 'USADA' && 
                    $r1['nombre_estado'] === 'DISPONIBLE' && 
                    $r2['nombre_estado'] === 'USADA') {
                    
                    // Verificamos que sean números consecutivos matemáticamente (ej. 100, 101, 102)
                    $n0 = intval($r0['numero_remision']);
                    $n1 = intval($r1['numero_remision']);
                    $n2 = intval($r2['numero_remision']);
                    
                    if ($n1 == $n0 + 1 && $n2 == $n1 + 1) {
                        $detectadas[] = $this->crearFormato($r1, $r0['numero_remision'], $r2['numero_remision']);
                    }
                }

                // SÁNDWICH DE 2 PISOS: [USADA] -> [DISPONIBLE] -> [DISPONIBLE] -> [USADA]
                // Aseguramos que haya un índice i+3
                if ($i < $total - 3) {
                    $r3 = $rems[$i+3];
                    
                    if ($r0['nombre_estado'] === 'USADA' && 
                        $r1['nombre_estado'] === 'DISPONIBLE' && 
                        $r2['nombre_estado'] === 'DISPONIBLE' && 
                        $r3['nombre_estado'] === 'USADA') {
                        
                        $n0 = intval($r0['numero_remision']);
                        $n1 = intval($r1['numero_remision']);
                        $n2 = intval($r2['numero_remision']);
                        $n3 = intval($r3['numero_remision']);
                        
                        // Verificamos que las 4 sean consecutivas (ej. 100, 101, 102, 103)
                        if ($n1 == $n0 + 1 && $n2 == $n1 + 1 && $n3 == $n2 + 1) {
                            // Guardamos las DOS que quedaron atrapadas en el medio
                            $detectadas[] = $this->crearFormato($r1, $r0['numero_remision'], $r3['numero_remision']);
                            $detectadas[] = $this->crearFormato($r2, $r0['numero_remision'], $r3['numero_remision']);
                        }
                    }
                }
            }
        }

        return $detectadas;
    }

    // Función auxiliar para mantener el código limpio y enviar a la vista
    private function crearFormato($actual, $anterior, $siguiente) {
        return [
            'id_control'       => $actual['id_control'],
            'numero_remision'  => $actual['numero_remision'],
            'nombre_estado'    => $actual['nombre_estado'],
            'fecha_asignacion' => $actual['fecha_asignacion'],
            'nombre_tecnico'   => $actual['nombre_tecnico'],
            'anterior'         => $anterior,
            'siguiente'        => $siguiente
        ];
    }

    public function actualizarEstadoRapido($idRemision, $nombreEstado)
    {
        try {
            $sql = "UPDATE control_remisiones 
                    SET id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = :estado LIMIT 1) 
                    WHERE id_control = :id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':estado', $nombreEstado); 
            $stmt->bindParam(':id', $idRemision);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}