<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class CostosCrearModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function obtenerMotorizados()
    {
        try {
            $sql = "SELECT 
                        id_tecnico as id, 
                        nombre_tecnico as nombre,
                        'Técnico' as cargo
                    FROM tecnico 
                    WHERE estado = 1
                    ORDER BY nombre_tecnico ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error obteniendo motorizados: " . $e->getMessage());
            return [];
        }
    }

    public function guardarCostosMotorizados($fechaReporte, $datos)
    {
        try {
            $this->conn->beginTransaction();

            $sql = "INSERT INTO costos_operativos (
                        id_tecnico, mes_reporte, salario, horas_extra, 
                        bono_meta, gasolina, auxilio_comunicacion, auxilio_rodamiento, estado
                    ) VALUES (
                        :id_tecnico, :mes_reporte, :salario, :horas_extra, 
                        :bono_meta, :gasolina, :auxilio_comunicacion, :auxilio_rodamiento, 1
                    ) ON DUPLICATE KEY UPDATE 
                        salario               = VALUES(salario),
                        horas_extra           = VALUES(horas_extra),
                        bono_meta             = VALUES(bono_meta),
                        gasolina              = VALUES(gasolina),
                        auxilio_comunicacion  = VALUES(auxilio_comunicacion),
                        auxilio_rodamiento    = VALUES(auxilio_rodamiento),
                        estado                = 1";  

            $stmt = $this->conn->prepare($sql);

            foreach ($datos as $idTecnico => $costo) {
                $salario      = !empty($costo['salario'])              ? floatval($costo['salario'])              : 0;
                $horas        = !empty($costo['horas_extra'])          ? floatval($costo['horas_extra'])          : 0;
                $bono         = !empty($costo['bono_meta'])            ? floatval($costo['bono_meta'])            : 0;
                $gasolina     = !empty($costo['gasolina'])             ? floatval($costo['gasolina'])             : 0;
                $comunicacion = !empty($costo['auxilio_comunicacion']) ? floatval($costo['auxilio_comunicacion']) : 0;
                $rodamiento   = !empty($costo['auxilio_rodamiento'])   ? floatval($costo['auxilio_rodamiento'])   : 0;

                if (($salario + $horas + $bono + $gasolina + $comunicacion + $rodamiento) == 0) continue;

                $stmt->bindParam(':id_tecnico',           $idTecnico);
                $stmt->bindParam(':mes_reporte',          $fechaReporte);
                $stmt->bindParam(':salario',              $salario);
                $stmt->bindParam(':horas_extra',          $horas);
                $stmt->bindParam(':bono_meta',            $bono);
                $stmt->bindParam(':gasolina',             $gasolina);
                $stmt->bindParam(':auxilio_comunicacion', $comunicacion);
                $stmt->bindParam(':auxilio_rodamiento',   $rodamiento);

                $stmt->execute();
            }

            $this->conn->commit();
            return true;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error guardando costos técnicos: " . $e->getMessage());
            return false;
        }
    }
}