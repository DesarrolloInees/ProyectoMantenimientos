<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class CostosEditarModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function obtenerDatosPorMes($mes)
    {
        try {
            $sql = "SELECT 
                        c.id_costo,
                        c.id_tecnico,
                        t.nombre_tecnico,
                        c.salario,
                        c.auxilio_rodamiento,
                        c.gasolina,
                        c.bono_meta,
                        c.horas_extra,
                        c.auxilio_comunicacion
                    FROM costos_operativos c
                    INNER JOIN tecnico t ON c.id_tecnico = t.id_tecnico
                    WHERE DATE_FORMAT(c.mes_reporte, '%Y-%m') = :mes
                      AND c.id_tecnico IS NOT NULL
                      AND c.estado = 1
                    ORDER BY t.nombre_tecnico ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':mes', $mes);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error obteniendo datos para editar: " . $e->getMessage());
            return [];
        }
    }

    public function actualizarCostos($datos)
    {
        try {
            $this->conn->beginTransaction();

            $sql = "UPDATE costos_operativos SET 
                        salario               = :salario,
                        horas_extra           = :horas_extra,
                        bono_meta             = :bono_meta,
                        gasolina              = :gasolina,
                        auxilio_comunicacion  = :auxilio_comunicacion,
                        auxilio_rodamiento    = :auxilio_rodamiento
                    WHERE id_costo    = :id_costo
                      AND id_tecnico IS NOT NULL
                      AND estado = 1";

            $stmt = $this->conn->prepare($sql);

            foreach ($datos as $idCosto => $costo) {
                $salario       = isset($costo['salario'])               ? floatval($costo['salario'])               : 0;
                $horas         = isset($costo['horas_extra'])           ? floatval($costo['horas_extra'])           : 0;
                $bono          = isset($costo['bono_meta'])             ? floatval($costo['bono_meta'])             : 0;
                $gasolina      = isset($costo['gasolina'])              ? floatval($costo['gasolina'])              : 0;
                $comunicacion  = isset($costo['auxilio_comunicacion'])  ? floatval($costo['auxilio_comunicacion'])  : 0;
                $rodamiento    = isset($costo['auxilio_rodamiento'])    ? floatval($costo['auxilio_rodamiento'])    : 0;
                $idCostoInt    = intval($idCosto);

                $stmt->bindParam(':salario',              $salario);
                $stmt->bindParam(':horas_extra',          $horas);
                $stmt->bindParam(':bono_meta',            $bono);
                $stmt->bindParam(':gasolina',             $gasolina);
                $stmt->bindParam(':auxilio_comunicacion', $comunicacion);
                $stmt->bindParam(':auxilio_rodamiento',   $rodamiento);
                $stmt->bindParam(':id_costo',             $idCostoInt, PDO::PARAM_INT);

                $stmt->execute();
            }

            $this->conn->commit();
            return true;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error actualizando costos: " . $e->getMessage());
            return false;
        }
    }

    // Borrado lógico: pone estado = 0 en lugar de DELETE
    public function eliminarCosto($idCosto)
    {
        try {
            $sql = "UPDATE costos_operativos 
                    SET estado = 0 
                    WHERE id_costo = :id_costo
                      AND id_tecnico IS NOT NULL";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id_costo', $idCosto, PDO::PARAM_INT);
            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error eliminando costo: " . $e->getMessage());
            return false;
        }
    }
}